<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * LogoutController - Handles user logout
 * 
 * Destroys the current user session and redirects to the login page.
 * Ensures complete session cleanup for security.
 * 
 * Access: Public (no authentication required)
 * 
 * Available Actions:
 *   - indexAction(): Destroy session and redirect to login
 */
class LogoutController extends Controller
{
    /** @var bool No authentication required for this controller */
    public $authRequired = false;

    /**
     * indexAction - Destroy session and redirect to login page.
     * 
     * Security: Completely destroys the session to prevent session fixation
     * and ensure the user is fully logged out.
     * 
     * Data Flow:
     *   Input:   Current session
     *   Process: Starts session (if not already), destroys all session data
     *   Output:  Redirects to login page
     */
    public function indexAction()
    {
        session_start(); // Ensure session is active
        session_destroy(); // Destroy all session data
        // Redirect to login page after logout
        $this->redirect('index.php?r=login/index');
    }
}
