<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * UserController - Manages system user accounts
 * 
 * Administrator-only controller for managing staff user accounts.
 * Handles the full user lifecycle including:
 *   - Creating new user accounts with role assignment
 *   - Editing user profiles and passwords
 *   - Deactivating/deleting user accounts
 *   - Preventing self-deletion for safety
 * 
 * Security Features:
 *   - Password minimum length enforcement (4 characters)
 *   - Duplicate username/email prevention
 *   - Email format validation
 *   - Self-deletion prevention
 * 
 * Access Roles: Administrator only
 * 
 * Available Actions:
 *   - indexAction(): Display user list and process CRUD operations
 *   - handlePost() : Process add, edit, delete actions (private)
 */
class UserController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'users';

    /** @var array Available user roles for assignment */
    private $roles = ['Administrator', 'Receptionist', 'Manager', 'Housekeeping Staff', 'Accountant'];

    /** @var array Allowed user status values */
    private $statuses = ['Active', 'Inactive'];

    /**
     * Constructor - Enforce Administrator-only access.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator']); // Administrator-only access control
        $this->loginRequired(); // Redundant call (already called above)
        $this->user = new User(); // Initialize User model
    }

    /**
     * indexAction - Display user list and handle CRUD operations.
     * 
     * Data Flow:
     *   Input:   POST data with action and user fields
     *   Process: Fetches all users, handles POST actions
     *   Output:  Renders users/index view with user list and messages
     */
    public function indexAction()
    {
        // Handle POST form submissions (add, edit, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        // Render the users view with all users and form options
        $this->view('users/index', [
            'users'    => $this->user->all(),
            'roles'    => $this->roles,
            'statuses' => $this->statuses,
            'message'  => $this->message ?? '',
            'error'    => $this->error ?? '',
        ], 'users', 'Users');
    }

    /**
     * Handle POST form submissions for user add, edit, and delete operations.
     * 
     * Security validations:
     *   - Required fields: username, full_name, email
     *   - Email format validation via FILTER_VALIDATE_EMAIL
     *   - Password minimum length: 4 characters
     *   - Duplicate username prevention (excluding current user on edit)
     *   - Duplicate email prevention (excluding current user on edit)
     *   - Self-deletion prevention
     * 
     * Data Flow:
     *   Input:   POST data with action and user fields
     *   Process: Validates all fields, checks for duplicates, calls model methods
     *   Output:  Sets $this->message or $this->error for view rendering
     */
    private function handlePost()
    {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add' || $action === 'edit') {
                // Build user data array from POST input
                $data = [
                    'username' => trim($_POST['username'] ?? ''),
                    'full_name'=> trim($_POST['full_name'] ?? ''),
                    'email'    => trim($_POST['email'] ?? ''),
                    'phone'    => trim($_POST['phone'] ?? ''),
                    // Validate role against allowed values, default to 'Receptionist'
                    'role'     => in_array($_POST['role'] ?? '', $this->roles, true) ? $_POST['role'] : 'Receptionist',
                    // Validate status against allowed values, default to 'Active'
                    'status'   => in_array($_POST['status'] ?? '', $this->statuses, true) ? $_POST['status'] : 'Active',
                    'password' => $_POST['password'] ?? '',
                ];

                // Validate required fields
                if ($data['username'] === '' || $data['full_name'] === '' || $data['email'] === '') {
                    throw new \Exception('Username, full name, and email are required.');
                }
                // Validate email format
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Invalid email address.');
                }

                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

                if ($action === 'add') {
                    // --- Create New User ---
                    // Enforce password requirement for new users
                    if ($data['password'] === '') throw new \Exception('Password is required for new users.');
                    // Enforce minimum password length
                    if (strlen($data['password']) < 4) throw new \Exception('Password must be at least 4 characters.');
                    // Check for duplicate username
                    if ($this->user->usernameExists($data['username'])) throw new \Exception('Username already exists.');
                    // Check for duplicate email
                    if ($this->user->emailExists($data['email'])) throw new \Exception('Email already exists.');
                    $this->user->create($data);
                    $this->message = 'User added successfully.';
                } else {
                    // --- Update Existing User ---
                    // Enforce minimum password length only if password is being changed
                    if ($data['password'] !== '' && strlen($data['password']) < 4) throw new \Exception('Password must be at least 4 characters.');
                    // Check for duplicate username (excluding current user)
                    if ($this->user->usernameExists($data['username'], $id)) throw new \Exception('Username already exists.');
                    // Check for duplicate email (excluding current user)
                    if ($this->user->emailExists($data['email'], $id)) throw new \Exception('Email already exists.');
                    $this->user->update($id, $data);
                    $this->message = 'User updated successfully.';
                }
            } elseif ($action === 'delete') {
                // --- Delete User ---
                $id = (int)$_POST['id'];
                // Security: Prevent admin from deleting their own account
                if ($id === (int)($_SESSION['user_id'] ?? 0)) throw new \Exception('You cannot delete your own account.');
                $this->user->delete($id);
                $this->message = 'User deleted successfully.';
            }
        } catch (\Exception $e) {
            // Capture error message for display
            $this->error = $e->getMessage();
        }
    }
}
