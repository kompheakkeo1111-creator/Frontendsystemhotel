<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Room;

/**
 * MaintenanceController - Manages room maintenance status
 * 
 * Handles setting rooms to and from maintenance status.
 * Prevents occupied rooms from being set to maintenance.
 * Displays all rooms with their current status for maintenance overview.
 * 
 * Access Roles: Administrator, Housekeeping Staff, Manager
 * 
 * Available Actions:
 *   - indexAction(): Display all rooms and process maintenance status changes
 */
class MaintenanceController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'maintenance';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Administrator, Housekeeping Staff, and Manager can access maintenance.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Housekeeping Staff', 'Manager']); // Role-based access control
        $this->room = new Room(); // Initialize Room model
    }

    /**
     * indexAction - Display rooms and process maintenance status changes.
     * 
     * Handles two POST actions:
     *   - 'set_maintenance': Sets a room to maintenance status
     *   - 'set_available'  : Sets a room back to available status
     * 
     * Safety: Occupied rooms cannot be set to maintenance to prevent
     * disrupting current guests.
     * 
     * Data Flow:
     *   Input:   POST data with action and room id
     *   Process: Validates room status, updates status if safe
     *   Output:  Renders maintenance/index view with room list and counts
     */
    public function indexAction()
    {
        // Handle POST: Toggle room maintenance status
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $id = (int)$_POST['id'];
            // Determine new status based on action type
            $newStatus = $_POST['action'] === 'set_maintenance' ? 'Maintenance' : 'Available';

            // Fetch current room to check its status
            $room = $this->room->find($id);

            // Safety check: Prevent setting occupied rooms to maintenance
            if ($newStatus === 'Maintenance' && $room && $room['status'] === 'Occupied') {
                $this->error = 'Occupied rooms cannot be set to maintenance.';
            } else {
                // Update room status
                $this->room->setStatus($id, $newStatus);
                $this->message = 'Room status updated.';
            }
        }

        // Fetch all rooms (no status filter) for maintenance overview
        $rooms = $this->room->all(['status' => '']);

        // Count rooms currently under maintenance
        $mtCount = 0;
        foreach ($rooms as $r) {
            if ($r['status'] === 'Maintenance') $mtCount++;
        }

        // Render the maintenance view
        $this->view('maintenance/index', [
            'rooms'   => $rooms,
            'mtCount' => $mtCount,
            'message' => $this->message ?? '',
            'error'   => $this->error ?? '',
        ], 'maintenance', 'Maintenance');
    }
}
