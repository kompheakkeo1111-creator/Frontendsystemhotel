<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\Guest;
use App\Models\Room;

/**
 * CheckInController - Handles guest check-in operations
 * 
 * Manages the check-in process for both reserved guests and walk-in guests.
 * Handles creating check-in records, updating room statuses, and
 * creating walk-in reservations on the fly.
 * 
 * Access Roles: Administrator, Receptionist, Manager
 * 
 * Available Actions:
 *   - indexAction()  : Display check-in page, process check-in and walk-in actions
 *   - availRooms()   : Get list of available rooms for walk-in check-in (private)
 */
class CheckInController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'checkin';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Initializes model instances for check-in, reservation, guest, and room operations.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager']); // Role-based access control
        $this->loginRequired(); // Redundant call (already called above)
        // Initialize model instances for database operations
        $this->checkIn = new CheckIn();
        $this->reservation = new Reservation();
        $this->guest = new Guest();
        $this->room = new Room();
    }

    /**
     * indexAction - Display check-in page and process POST actions.
     * 
     * Handles two POST actions:
     *   1. 'checkin' - Check in a guest with an existing reservation
     *   2. 'walkin'  - Check in a walk-in guest without a prior reservation
     * 
     * Data Flow:
     *   Input:   POST data with action type and relevant fields
     *   Process: Validates data, creates check-in record, updates room status
     *   Output:  Renders checkin/index view with active stays and ready reservations
     */
    public function indexAction()
    {
        $db = getDB();

        // Handle POST form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            try {
                if ($action === 'checkin') {
                    // Process reservation-based check-in
                    $resId = (int)($_POST['reservation_id'] ?? 0);

                    // Use database transaction for atomicity
                    $db->beginTransaction();

                    // Fetch the reservation and validate it exists with eligible status
                    $stmt = $db->prepare("SELECT * FROM reservations WHERE id=? AND status IN ('Pending','Confirmed')");
                    $stmt->execute([$resId]);
                    $res = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if (!$res) throw new \Exception('Reservation not found or not eligible for check-in.');
                    if (!$res['room_id']) throw new \Exception('Reservation has no room assigned.');

                    // Prevent duplicate check-ins for the same reservation
                    if ($this->checkIn->activeCountForReservation($resId) > 0) throw new \Exception('Guest is already checked in.');

                    // Create check-in record and update room status to 'Occupied'
                    $this->checkIn->insert($res['id'], $res['guest_id'], $res['room_id'], $res['check_out_date'], $_SESSION['user_id']);
                    $this->room->setStatus($res['room_id'], 'Occupied');

                    // Commit transaction
                    $db->commit();
                    $this->message = 'Guest checked in successfully.';

                } elseif ($action === 'walkin') {
                    // Process walk-in check-in (no prior reservation)
                    $guestId = (int)($_POST['guest_id'] ?? 0);
                    $roomId = (int)($_POST['room_id'] ?? 0);
                    $checkIn = $_POST['check_in_date'] ?? date('Y-m-d');
                    $checkOut = $_POST['check_out_date'] ?? '';
                    $numGuests = max(1, (int)($_POST['number_of_guests'] ?? 1));

                    // Validate required walk-in fields
                    if (!$guestId || !$roomId || !$checkOut) throw new \Exception('Guest, room, and check-out date are required.');
                    // Validate check-out is after check-in
                    if (strtotime($checkOut) <= strtotime($checkIn)) throw new \Exception('Check-out must be after check-in.');
                    // Ensure room is not currently occupied
                    if ($this->checkIn->roomCurrentlyOccupied($roomId)) throw new \Exception('This room is currently occupied.');
                    // Ensure room is not reserved for the selected dates
                    if (!$this->reservation->roomIsAvailable($roomId, $checkIn, $checkOut)) throw new \Exception('This room is already reserved for the selected dates.');

                    // Use database transaction for atomicity
                    $db->beginTransaction();

                    // Get nightly rate and calculate total cost
                    $nightly = $this->reservation->roomNightlyRate($roomId);
                    $nights = max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400));
                    $total = round($nights * $nightly, 2);

                    // Generate a reservation number for the walk-in
                    $resNumber = generateReservationNumber();

                    // Create a 'Confirmed' reservation for the walk-in guest
                    $db->prepare("INSERT INTO reservations (reservation_number, guest_id, room_id, check_in_date, check_out_date, number_of_guests, status, total_amount, created_by)
                                  VALUES (?,?,?,?,?,?,'Confirmed',?,?)")
                        ->execute([$resNumber, $guestId, $roomId, $checkIn, $checkOut, $numGuests, $total, $_SESSION['user_id']]);
                    $reservationId = (int)$db->lastInsertId();

                    // Create the check-in record and update room status
                    $this->checkIn->insert($reservationId, $guestId, $roomId, $checkOut, $_SESSION['user_id']);
                    $this->room->setStatus($roomId, 'Occupied');

                    // Commit transaction
                    $db->commit();
                    $this->message = 'Walk-in guest checked in. Reservation: ' . $resNumber;
                }
            } catch (\Exception $e) {
                // Rollback transaction on any error to maintain data integrity
                if ($db->inTransaction()) $db->rollBack();
                $this->error = $e->getMessage();
            }
        }

        // Render the check-in view with all necessary data
        $this->view('checkin/index', [
            'ready'     => $this->checkIn->readyReservations(), // Reservations ready for check-in
            'stays'     => $this->checkIn->activeStays(),       // Currently active check-ins
            'guests'    => $this->guest->all(),                  // All guests for walk-in selection
            'availRooms'=> $this->availRooms(),                 // Available rooms for walk-in
            'message'   => $this->message ?? '',
            'error'     => $this->error ?? '',
        ], 'checkin', 'Check-in');
    }

    /**
     * Retrieves rooms with 'Available' or 'Cleaning' status for walk-in check-in selection.
     * 
     * Data Flow:
     *   Input:   None
     *   Process: Queries rooms table for available/cleaning status
     *   Output:  Array of room records with id, room_number, price_per_night
     */
    private function availRooms()
    {
        $db = getDB();
        return $db->query("SELECT id, room_number, price_per_night FROM rooms WHERE status IN ('Available','Cleaning') ORDER BY room_number")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
