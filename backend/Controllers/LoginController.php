<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\GoogleAuth;

/**
 * LoginController - Handles user authentication
 * 
 * Manages the login process including session creation,
 * credential verification, and role-based session storage.
 * Redirects authenticated users to the dashboard.
 * 
 * Access: Public (no authentication required)
 * 
 * Available Actions:
 *   - indexAction(): Display login form and process login credentials
 *   - googleAction(): Verify Google ID token and create a session (AJAX)
 */
class LoginController extends Controller
{
    /** @var bool No authentication required for this controller */
    public $authRequired = false;

    /**
     * indexAction - Display login form and process authentication.
     * 
     * On GET: Displays the login form.
     * On POST: Validates credentials against the database and creates a session.
     * 
     * Security Measures:
     *   - Only active users can log in (status = 'Active')
     *   - Passwords are verified using password_verify() with bcrypt hashing
     *   - Prepared statements prevent SQL injection
     *   - Session variables store user_id, username, user_role, and user_name
     * 
     * Data Flow:
     *   Input:   POST data with username and password
     *   Process: Queries users table, verifies password hash, creates session
     *   Output:  Redirects to dashboard on success, shows error on failure
     */
    public function indexAction()
    {
        // Redirect already authenticated users to dashboard
        if (isLoggedIn()) {
            $this->redirect('index.php?r=dashboard/index');
        }

        $error = '';

        // Process login form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            $db = getDB();
            // Security: Query only active users with prepared statement to prevent SQL injection
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Security: Verify password against stored bcrypt hash
            if ($user && password_verify($password, $user['password'])) {
                // Security: Store user identification in session for access control
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role']; // Used for role-based access control
                $_SESSION['user_name'] = $user['full_name'];
                // Redirect to dashboard after successful authentication
                $this->redirect('index.php?r=dashboard/index');
            }
            // Generic error message to prevent username enumeration
            $error = 'Invalid username or password!';
        }

        // Render the login form
        require __DIR__ . '/../Views/login/index.php';
    }

    /**
     * googleAction - Verify a Google ID token and create a session.
     *
     * Called via AJAX by the "Sign in with Google" button on the login page.
     * Receives the ID token (POST credential), verifies it server-side with
     * App\Helpers\GoogleAuth (signature + issuer/audience/expiry checks), then
     * matches the verified Google email against the users table.
     *
     * Access policy: only existing ACTIVE staff accounts can sign in with
     * Google - no accounts are created automatically.
     *
     * Security measures:
     *   - ID token signature is verified with Google's public keys (no trust
     *     is placed in the decoded data alone)
     *   - Prepared statements prevent SQL injection
     *   - Case-insensitive, trimmed email comparison
     *   - Only status = 'Active' users can authenticate
     *   - Session keys identical to the password login flow
     *
     * Output: JSON {success: true, redirect} or {success: false, error}
     */
    public function googleAction()
    {
        // Already logged in - nothing to do
        if (isLoggedIn()) {
            $this->jsonResponse(['success' => true, 'redirect' => 'index.php?r=dashboard/index']);
        }

        $credential = trim($_POST['credential'] ?? '');
        if ($credential === '') {
            $this->jsonResponse(['success' => false, 'error' => 'No Google credential received.'], 400);
        }

        // Verify the ID token (signature + claims) with Google's public keys
        $payload = GoogleAuth::verifyIdToken($credential);
        if ($payload === null) {
            $this->jsonResponse(['success' => false, 'error' => 'Google sign-in could not be verified.'], 401);
        }

        $email = strtolower(trim($payload['email']));

        // Only active staff accounts whose email matches the Google account may sign in
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = ? AND status = 'Active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            // Generic message - does not reveal which accounts exist
            $this->jsonResponse([
                'success' => false,
                'error'   => 'No active staff account is registered with this Google email.',
            ], 403);
        }

        // Same session structure as the username/password login
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];

        $this->jsonResponse(['success' => true, 'redirect' => 'index.php?r=dashboard/index']);
    }
}
