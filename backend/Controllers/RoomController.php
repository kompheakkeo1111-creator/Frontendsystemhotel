<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Room;
use App\Models\RoomType;

/**
 * RoomController - Manages individual room records
 * 
 * Handles CRUD operations for hotel rooms including:
 *   - Creating new rooms with type assignment
 *   - Editing room details (number, type, floor, capacity, price, status)
 *   - Deleting rooms (only if no reservations exist)
 *   - Filtering rooms by status, floor, and type
 * 
 * Access Roles: Administrator, Receptionist, Manager, Housekeeping Staff
 * 
 * Available Actions:
 *   - indexAction(): Display filtered room list and process CRUD operations
 *   - handlePost() : Process add, edit, delete actions (private)
 */
class RoomController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'rooms';

    /** @var array Allowed room status values */
    private $validStatus = ['Available', 'Reserved', 'Occupied', 'Cleaning', 'Maintenance'];

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Initializes Room and RoomType model instances.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager', 'Housekeeping Staff']); // Role-based access control
        $this->loginRequired(); // Redundant call (already called above)
        $this->room = new Room(); // Initialize Room model
        $this->roomType = new RoomType(); // Initialize RoomType model
    }

    /**
     * indexAction - Display room list and handle CRUD operations.
     * 
     * Supports filtering by status, floor, and room type via GET parameters.
     * 
     * Data Flow:
     *   Input:   GET parameters: status, floor, type (filters)
     *   Process: Fetches filtered rooms, room types, and distinct floors
     *   Output:  Renders rooms/index view with room data and filter options
     */
    public function indexAction()
    {
        // Handle POST form submissions (add, edit, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        // Extract filter parameters from GET request
        $filters = [
            'status' => $_GET['status'] ?? '',
            'floor'  => $_GET['floor'] ?? '',
            'type'   => $_GET['type'] ?? '',
        ];

        // Fetch filtered rooms, all room types, and distinct floor values
        $rooms = $this->room->all($filters);
        $roomTypes = $this->roomType->all();
        $floors = $this->room->distinctFloors();

        // Render the rooms view
        $this->view('rooms/index', [
            'rooms'      => $rooms,
            'roomTypes'  => $roomTypes,
            'floors'     => $floors,
            'validStatus'=> $this->validStatus,
            'filters'    => $filters,
            'message'    => $this->message ?? '',
            'error'      => $this->error ?? '',
        ], 'rooms', 'Rooms');
    }

    /**
     * Handle POST form submissions for room add, edit, and delete operations.
     * 
     * Validates input data and delegates to the Room model for database operations.
     * Prevents deletion of rooms with existing reservations.
     * 
     * Data Flow:
     *   Input:   POST data with action and room fields
     *   Process: Validates required fields, casts types, calls model methods
     *   Output:  Sets $this->message or $this->error for view rendering
     */
    private function handlePost()
    {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add' || $action === 'edit') {
                // Build room data array from POST input with type casting
                $data = [
                    'room_number'     => trim($_POST['room_number'] ?? ''),
                    'room_type_id'    => ($_POST['room_type_id'] ?? '') !== '' ? (int)$_POST['room_type_id'] : null,
                    'floor'           => ($_POST['floor'] ?? '') !== '' ? (int)$_POST['floor'] : null,
                    'capacity'        => ($_POST['capacity'] ?? '') !== '' ? (int)$_POST['capacity'] : null,
                    'price_per_night' => ($_POST['price_per_night'] ?? '') !== '' ? (float)$_POST['price_per_night'] : 0,
                    'description'     => trim($_POST['description'] ?? ''),
                    // Validate status against allowed values, default to 'Available'
                    'status'          => in_array($_POST['status'] ?? '', $this->validStatus, true) ? $_POST['status'] : 'Available',
                ];

                // Validate required field: room number
                if ($data['room_number'] === '') throw new \Exception('Room number is required.');

                if ($action === 'add') {
                    // Create new room record
                    $this->room->create($data);
                    $this->message = 'Room added successfully.';
                } else {
                    // Update existing room record
                    $id = (int)$_POST['id'];
                    $this->room->update($id, $data);
                    $this->message = 'Room updated successfully.';
                }
            } elseif ($action === 'delete') {
                // Delete room record with reservation check
                $id = (int)$_POST['id'];
                // Prevent deletion if room has any reservations (referential integrity)
                if ($this->room->hasReservations($id)) {
                    throw new \Exception('This room has reservations and cannot be deleted.');
                }
                $this->room->delete($id);
                $this->message = 'Room deleted successfully.';
            }
        } catch (\Exception $e) {
            // Capture error message for display
            $this->error = $e->getMessage();
        }
    }
}
