<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\RoomType;

/**
 * RoomTypeController - Manages room type definitions
 * 
 * Handles CRUD operations for room types including:
 *   - Creating new room types with name, description, price, and capacity
 *   - Editing existing room type details
 *   - Deleting room types
 * 
 * Room types define categories of rooms (e.g., Deluxe, Suite, VIP)
 * and their base pricing and capacity.
 * 
 * Access Roles: Administrator, Receptionist, Manager
 * 
 * Available Actions:
 *   - indexAction(): Display room type list and process CRUD operations
 *   - handlePost() : Process add, edit, delete actions (private)
 */
class RoomTypeController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'room_types';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Initializes the RoomType model.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager']); // Role-based access control
        $this->model = new RoomType(); // Initialize RoomType model
    }

    /**
     * indexAction - Display room type list and handle CRUD operations.
     * 
     * Data Flow:
     *   Input:   POST data with action and room type fields
     *   Process: Fetches all room types, handles POST actions
     *   Output:  Renders room_types/index view with room type list and messages
     */
    public function indexAction()
    {
        // Handle POST form submissions (add, edit, delete)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost();
        }

        // Fetch all room types from database
        $roomTypes = $this->model->all();

        // Render the room types view
        $this->view('room_types/index', [
            'roomTypes' => $roomTypes,
            'message'   => $this->message ?? '',
            'error'     => $this->error ?? '',
        ], 'room_types', 'Room Types');
    }

    /**
     * Handle POST form submissions for room type add, edit, and delete operations.
     * 
     * Validates input data and delegates to the RoomType model.
     * 
     * Data Flow:
     *   Input:   POST data with action and room type fields
     *   Process: Validates required fields (type_name, capacity), calls model methods
     *   Output:  Sets $this->message or $this->error for view rendering
     */
    private function handlePost()
    {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'add' || $action === 'edit') {
                // Extract and sanitize room type fields
                $type_name   = trim($_POST['type_name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price       = (float)($_POST['price_per_night'] ?? 0);
                $capacity    = (int)($_POST['capacity'] ?? 0);

                // Validate required fields: type name must not be empty, capacity must be at least 1
                if ($type_name === '' || $capacity < 1) {
                    throw new \Exception('Type name and valid capacity are required.');
                }

                if ($action === 'add') {
                    // Create new room type record
                    $this->model->create($type_name, $description, $price, $capacity);
                    $this->message = 'Room type added successfully.';
                } else {
                    // Update existing room type record
                    $id = (int)($_POST['id'] ?? 0);
                    $this->model->update($id, $type_name, $description, $price, $capacity);
                    $this->message = 'Room type updated successfully.';
                }
            } elseif ($action === 'delete') {
                // Delete room type record
                $id = (int)($_POST['id'] ?? 0);
                $this->model->delete($id);
                $this->message = 'Room type deleted successfully.';
            }
        } catch (\Exception $e) {
            // Capture error message for display
            $this->error = 'Error: ' . $e->getMessage();
        }
    }
}
