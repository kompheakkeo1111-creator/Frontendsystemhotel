<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Room;

/**
 * HousekeepingController - Manages housekeeping operations
 * 
 * Handles room cleaning status updates for the housekeeping staff.
 * Displays rooms that need cleaning or are under maintenance,
 * and allows marking rooms as cleaned and available.
 * 
 * Access Roles: Administrator, Housekeeping Staff
 * 
 * Available Actions:
 *   - indexAction(): Display rooms needing attention and process 'mark_clean' action
 */
class HousekeepingController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'housekeeping';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Only Administrator and Housekeeping Staff can access this controller.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Housekeeping Staff']); // Role-based access control
        $this->room = new Room(); // Initialize Room model
    }

    /**
     * indexAction - Display housekeeping dashboard and process room status updates.
     * 
     * Shows rooms with 'Cleaning' or 'Maintenance' status and allows
     * housekeeping staff to mark rooms as cleaned (set to 'Available').
     * 
     * Data Flow:
     *   Input:   POST data with action='mark_clean' and room id
     *   Process: Updates room status to 'Available', counts rooms by status
     *   Output:  Renders housekeeping/index view with room list and counts
     */
    public function indexAction()
    {
        // Handle POST: Mark a room as cleaned and available
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_clean') {
            $id = (int)$_POST['id'];
            // Update room status from 'Cleaning' to 'Available'
            $this->room->setStatus($id, 'Available');
            $this->message = 'Room marked as cleaned and available.';
        }

        // Fetch all rooms with 'Cleaning' or 'Maintenance' status
        $rooms = $this->room->byStatuses(['Cleaning', 'Maintenance']);

        // Count rooms by each status for display
        $cleaningCount = $maintenanceCount = 0;
        foreach ($rooms as $r) {
            if ($r['status'] === 'Cleaning') $cleaningCount++;
            else $maintenanceCount++;
        }

        // Render the housekeeping view
        $this->view('housekeeping/index', [
            'rooms'         => $rooms,
            'cleaningCount' => $cleaningCount,
            'maintenanceCount' => $maintenanceCount,
            'message'       => $this->message ?? '',
            'error'         => $this->error ?? '',
        ], 'housekeeping', 'Housekeeping');
    }
}
