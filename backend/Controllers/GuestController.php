<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Guest;

/**
 * GuestController - Manages guest records
 * 
 * Handles CRUD operations for hotel guests including adding, editing,
 * viewing, and deleting guest records. Provides guest history with
 * reservation and payment information.
 * 
 * Access Roles: Administrator, Receptionist, Manager
 * 
 * Available Actions:
 *   - indexAction(): Display guest list and process add/edit/delete actions
 *   - viewAction() : Display detailed guest profile with stay history
 */
class GuestController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'guests';

    /** @var array Allowed gender values for validation */
    private $validGender = ['Male', 'Female', 'Other'];

    /**
     * Constructor - Enforce authentication and role-based access control.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager']); // Role-based access control
        $this->guest = new Guest(); // Initialize Guest model
    }

    /**
     * indexAction - Display guest list and handle CRUD operations.
     * 
     * Data Flow:
     *   Input:   POST data with action (add/edit/delete) and guest fields
     *   Process: Delegates to handlePost() for POST requests, fetches all guests
     *   Output:  Renders guests/index view with guest list and messages
     */
    public function indexAction()
    {
        // Handle POST form submissions (add, edit, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        // Fetch all guests from database
        $guests = $this->guest->all();

        // Render the guest list view
        $this->view('guests/index', [
            'guests'   => $guests,
            'validGender' => $this->validGender,
            'message'  => $this->message ?? '',
            'error'    => $this->error ?? '',
        ], 'guests', 'Guests');
    }

    /**
     * viewAction - Display detailed guest profile with stay history.
     * 
     * Data Flow:
     *   Input:   GET parameter 'id' (guest ID)
     *   Process: Fetches guest record and reservation history
     *   Output:  Renders guests/view.php with guest details and history
     */
    public function viewAction()
    {
        // Validate guest ID parameter
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $db = getDB();

        if (!$id) {
            echo '<div class="alert alert-danger">Invalid guest ID</div>';
            return;
        }

        // Fetch guest record by ID
        $stmt = $db->prepare("SELECT * FROM guests WHERE id=?");
        $stmt->execute([$id]);
        $g = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$g) {
            echo '<div class="alert alert-danger">Guest not found</div>';
            return;
        }

        // Fetch guest's reservation history with payment counts
        $histStmt = $db->prepare("
            SELECT r.reservation_number, r.check_in_date, r.check_out_date, r.status, rm.room_number,
                   (SELECT COUNT(*) FROM payments p WHERE p.reservation_id=r.id) payments
            FROM reservations r
            LEFT JOIN rooms rm ON r.room_id=rm.id
            WHERE r.guest_id=? ORDER BY r.created_at DESC");
        $histStmt->execute([$id]);
        $history = $histStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Render the guest detail view
        require __DIR__ . '/../Views/guests/view.php';
    }

    /**
     * Handle POST form submissions for guest add, edit, and delete operations.
     * 
     * Validates input data and delegates to the Guest model for database operations.
     * Prevents deletion of guests with existing reservations.
     * 
     * Data Flow:
     *   Input:   POST data with action and guest fields
     *   Process: Validates required fields, calls model methods
     *   Output:  Sets $this->message or $this->error for view rendering
     */
    private function handlePost()
    {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add' || $action === 'edit') {
                // Build guest data array from POST input
                $data = [
                    'full_name'            => trim($_POST['full_name'] ?? ''),
                    'gender'               => in_array($_POST['gender'] ?? '', $this->validGender, true) ? $_POST['gender'] : 'Male',
                    'date_of_birth'        => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                    'nationality'          => trim($_POST['nationality'] ?? ''),
                    'phone'                => trim($_POST['phone'] ?? ''),
                    'email'                => trim($_POST['email'] ?? ''),
                    'address'              => trim($_POST['address'] ?? ''),
                    'identification_number'=> trim($_POST['identification_number'] ?? ''),
                ];

                // Validate required fields
                if ($data['full_name'] === '' || $data['phone'] === '' || $data['identification_number'] === '') {
                    throw new \Exception('Full name, phone, and ID number are required.');
                }

                if ($action === 'add') {
                    // Create new guest record
                    $this->guest->create($data);
                    $this->message = 'Guest added successfully.';
                } else {
                    // Update existing guest record
                    $id = (int)$_POST['id'];
                    $this->guest->update($id, $data);
                    $this->message = 'Guest updated successfully.';
                }
            } elseif ($action === 'delete') {
                // Delete guest record with reservation check
                $id = (int)$_POST['id'];
                // Prevent deletion if guest has any reservations (referential integrity)
                if ($this->guest->hasReservations($id)) {
                    throw new \Exception('Guest has reservations and cannot be deleted.');
                }
                $this->guest->delete($id);
                $this->message = 'Guest deleted successfully.';
            }
        } catch (\Exception $e) {
            // Capture error message for display
            $this->error = $e->getMessage();
        }
    }
}
