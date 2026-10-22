<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Reservation;
use App\Models\Guest;
use App\Models\Room;

/**
 * ReservationController - Manages hotel reservations
 * 
 * Handles the full reservation lifecycle including:
 *   - Creating new reservations with room availability checks
 *   - Editing existing reservations (dates, status, guest count)
 *   - Cancelling reservations and releasing rooms
 *   - Deleting reservations with active stay protection
 * 
 * Automatically calculates total cost including tax.
 * 
 * Access Roles: Administrator, Receptionist, Manager
 * 
 * Available Actions:
 *   - indexAction()   : Display reservation list with filtering and process CRUD actions
 *   - handlePost()    : Process add, edit, cancel, delete actions (private)
 *   - computeNights() : Calculate number of nights between two dates (private)
 *   - computeTotal()  : Calculate total cost including tax (private)
 */
class ReservationController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'reservations';

    /** @var array Allowed reservation status values */
    private $validStatus = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];

    /**
     * Constructor - Enforce authentication and initialize models and settings.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager']); // Role-based access control
        $this->loginRequired(); // Redundant call (already called above)
        // Initialize models for reservation operations
        $this->reservation = new Reservation();
        $this->guest = new Guest();
        $this->room = new Room();
        // Load system settings for tax calculations
        $this->settings = getSystemSettings();
        $this->taxRate = (float)($this->settings['tax_rate'] ?? 0);
    }

    /**
     * indexAction - Display reservation list and handle CRUD operations.
     * 
     * Supports filtering by status and search query via GET parameters.
     * 
     * Data Flow:
     *   Input:   GET parameters: status (filter), q (search query)
     *   Process: Fetches filtered reservations, handles POST actions
     *   Output:  Renders reservations/index view with reservation list and form data
     */
    public function indexAction()
    {
        // Handle POST form submissions (add, edit, cancel, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        // Extract filter parameters
        $fStatus = $_GET['status'] ?? '';
        $q = trim($_GET['q'] ?? '');

        // Render the reservations view
        $this->view('reservations/index', [
            'reservations' => $this->reservation->all($fStatus, $q),
            'guests'       => $this->guest->all(),
            'rooms'        => $this->dbAllRooms(),
            'validStatus'  => $this->validStatus,
            'taxRate'      => $this->taxRate,
            'currency'     => $this->settings['currency'] ?? 'USD',
            'fStatus'      => $fStatus,
            'q'            => $q,
            'message'      => $this->message ?? '',
            'error'        => $this->error ?? '',
        ], 'reservations', 'Reservations');
    }

    /**
     * Fetch all rooms for reservation form dropdown.
     * 
     * Data Flow:
     *   Input:   None
     *   Process: Queries rooms table for id, room_number, price_per_night
     *   Output:  Array of room records
     */
    private function dbAllRooms()
    {
        $db = getDB();
        return $db->query("SELECT id, room_number, price_per_night FROM rooms ORDER BY room_number")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Handle POST form submissions for reservation CRUD operations.
     * 
     * Supports four actions:
     *   - 'add'    : Create a new reservation with availability check
     *   - 'edit'   : Update reservation dates, status, and recalculate total
     *   - 'cancel' : Cancel reservation and release the room
     *   - 'delete' : Delete reservation (only if no active check-in)
     * 
     * Data Flow:
     *   Input:   POST data with action and relevant fields
     *   Process: Validates data, checks room availability, creates/updates/deletes
     *   Output:  Sets $this->message or $this->error for view rendering
     */
    private function handlePost()
    {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add') {
                // Extract and validate reservation fields
                $guestId = (int)($_POST['guest_id'] ?? 0);
                $roomId = (int)($_POST['room_id'] ?? 0);
                $checkIn = $_POST['check_in_date'] ?? '';
                $checkOut = $_POST['check_out_date'] ?? '';
                $numGuests = max(1, (int)($_POST['number_of_guests'] ?? 1));
                $status = in_array($_POST['status'] ?? '', $this->validStatus, true) ? $_POST['status'] : 'Pending';
                $requests = trim($_POST['special_requests'] ?? '');

                // Validate required fields
                if (!$guestId || !$roomId || !$checkIn || !$checkOut) throw new \Exception('Guest, room, and dates are required.');
                // Validate check-out is after check-in
                if (strtotime($checkOut) <= strtotime($checkIn)) throw new \Exception('Check-out must be after check-in.');
                // Check room availability for the selected dates
                if (!$this->reservation->roomIsAvailable($roomId, $checkIn, $checkOut)) throw new \Exception('This room is already booked for the selected dates.');

                // Calculate cost: nights * nightly rate + tax
                $nightly = $this->reservation->roomNightlyRate($roomId);
                $nights = $this->computeNights($checkIn, $checkOut);
                $total = $this->computeTotal($nights, $nightly);

                // Generate unique reservation number
                $number = generateReservationNumber();

                // Create reservation record
                $this->reservation->create([
                    'reservation_number' => $number, 'guest_id' => $guestId, 'room_id' => $roomId,
                    'check_in_date' => $checkIn, 'check_out_date' => $checkOut, 'number_of_guests' => $numGuests,
                    'status' => $status, 'special_requests' => $requests, 'total_amount' => $total,
                    'created_by' => $_SESSION['user_id'],
                ]);
                $this->message = 'Reservation ' . $number . ' created.';

            } elseif ($action === 'edit') {
                // Update existing reservation
                $id = (int)($_POST['id'] ?? 0);
                $checkIn = $_POST['check_in_date'] ?? '';
                $checkOut = $_POST['check_out_date'] ?? '';
                $numGuests = max(1, (int)($_POST['number_of_guests'] ?? 1));
                $status = in_array($_POST['status'] ?? '', $this->validStatus, true) ? $_POST['status'] : 'Pending';
                $requests = trim($_POST['special_requests'] ?? '');

                // Validate required fields
                if (!$id || !$checkIn || !$checkOut) throw new \Exception('Invalid data.');
                // Validate check-out is after check-in
                if (strtotime($checkOut) <= strtotime($checkIn)) throw new \Exception('Check-out must be after check-in.');

                // Recalculate total based on updated dates
                $roomId = (int)$this->reservation->roomIdFor($id);
                $nightly = $this->reservation->roomNightlyRate($roomId);
                $nights = $this->computeNights($checkIn, $checkOut);
                $total = $this->computeTotal($nights, $nightly);

                // Update reservation record
                $this->reservation->updateDatesAndStatus($id, $checkIn, $checkOut, $numGuests, $status, $requests, $total);
                $this->message = 'Reservation updated.';

            } elseif ($action === 'cancel') {
                // Cancel reservation and release the room
                $id = (int)($_POST['id'] ?? 0);
                $this->reservation->setStatus($id, 'Cancelled');
                // Release the room by setting status back to 'Available'
                $rid = (int)$this->reservation->roomIdFor($id);
                if ($rid) $this->reservation->setRoomStatus($rid, 'Available');
                $this->message = 'Reservation cancelled and room released.';

            } elseif ($action === 'delete') {
                // Delete reservation (only if no active check-in exists)
                $id = (int)($_POST['id'] ?? 0);
                // Prevent deletion if guest is currently checked in
                if ($this->reservation->hasActiveCheckIn($id)) throw new \Exception('Cannot delete an active stay.');
                $rid = (int)$this->reservation->roomIdFor($id);
                $this->reservation->delete($id);
                // Release room if no other non-cancelled reservations exist for it
                if ($rid && !$this->reservation->hasOtherNonCancelledForRoom($rid)) {
                    $this->reservation->setRoomStatus($rid, 'Available');
                }
                $this->message = 'Reservation deleted.';
            }
        } catch (\Exception $e) {
            // Capture error message for display
            $this->error = $e->getMessage();
        }
    }

    /**
     * Calculate the number of nights between check-in and check-out dates.
     * 
     * @param string $in  Check-in date (Y-m-d format)
     * @param string $out Check-out date (Y-m-d format)
     * @return int Number of nights (minimum 1)
     */
    private function computeNights($in, $out)
    {
        return max(1, (int)((strtotime($out) - strtotime($in)) / 86400));
    }

    /**
     * Calculate total cost including tax.
     * 
     * @param int   $nights Number of nights
     * @param float $nightly Nightly rate
     * @return float Total cost with tax applied
     */
    private function computeTotal($nights, $nightly)
    {
        $subtotal = $nights * $nightly;
        // Apply tax rate percentage to subtotal
        return round($subtotal + ($subtotal * $this->taxRate / 100), 2);
    }
}
