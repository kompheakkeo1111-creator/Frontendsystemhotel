<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * NotificationController - System notifications dashboard
 * 
 * Displays system-wide notifications for hotel operations including:
 *   - Today's scheduled check-ins
 *   - Today's scheduled check-outs
 *   - Rooms needing cleaning
 *   - Rooms under maintenance
 *   - Pending reservations
 *   - Today's revenue summary
 * 
 * Access Roles: Administrator, Receptionist, Manager, Housekeeping Staff, Accountant
 *   (All staff roles can view notifications)
 * 
 * Available Actions:
 *   - indexAction(): Query system status and display notifications
 */
class NotificationController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'notifications';

    /** @var string Page title for the layout */
    protected $pageTitle = 'Notifications';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * All staff roles are granted access to view notifications.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        // Role-based access: All staff roles can view notifications
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager', 'Housekeeping Staff', 'Accountant']);
        $this->db = getDB(); // Initialize database connection
    }

    /**
     * indexAction - Query system status and build notification list.
     * 
     * Each notification type is only added if there are relevant items.
     * Each notification includes a type, icon, title, description, and link.
     * 
     * Data Flow:
     *   Input:   None (queries database for current system status)
     *   Process: Queries counts for each notification category
     *   Output:  Renders notifications/index view with notification array and today's revenue
     */
    public function indexAction()
    {
        $notifications = [];

        // Check for today's scheduled check-ins
        $stmt = $this->db->query("SELECT COUNT(*) FROM reservations WHERE check_in_date=CURDATE() AND status IN ('Pending','Confirmed')");
        $n = (int)$stmt->fetchColumn();
        if ($n) $notifications[] = ['type'=>'success','icon'=>'bi-box-arrow-in-right','title'=>'Today\'s Check-ins','text'=>$n.' guest reservation(s) are scheduled to check in today.','link'=>'index.php?r=checkin/index'];

        // Check for today's scheduled check-outs
        $stmt = $this->db->query("SELECT COUNT(*) FROM reservations WHERE check_out_date=CURDATE() AND status IN ('Pending','Confirmed')");
        $n = (int)$stmt->fetchColumn();
        if ($n) $notifications[] = ['type'=>'warning','icon'=>'bi-box-arrow-right','title'=>'Today\'s Check-outs','text'=>$n.' guest reservation(s) are scheduled to check out today.','link'=>'index.php?r=checkout/index'];

        // Check for rooms waiting for housekeeping
        $stmt = $this->db->query("SELECT COUNT(*) FROM rooms WHERE status='Cleaning'");
        $n = (int)$stmt->fetchColumn();
        if ($n) $notifications[] = ['type'=>'warning','icon'=>'bi-broom','title'=>'Rooms Need Cleaning','text'=>$n.' room(s) are waiting for housekeeping.','link'=>'index.php?r=housekeeping/index'];

        // Check for rooms under maintenance
        $stmt = $this->db->query("SELECT COUNT(*) FROM rooms WHERE status='Maintenance'");
        $n = (int)$stmt->fetchColumn();
        if ($n) $notifications[] = ['type'=>'danger','icon'=>'bi-wrench','title'=>'Maintenance Required','text'=>$n.' room(s) are currently under maintenance.','link'=>'index.php?r=maintenance/index'];

        // Check for pending reservations needing confirmation
        $stmt = $this->db->query("SELECT COUNT(*) FROM reservations WHERE status='Pending'");
        $n = (int)$stmt->fetchColumn();
        if ($n) $notifications[] = ['type'=>'info','icon'=>'bi-calendar-check','title'=>'Pending Reservations','text'=>$n.' reservation(s) are still pending confirmation.','link'=>'index.php?r=reservations/index'];

        // Get today's total revenue for display
        $stmt = $this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)=CURDATE()");
        $todayRevenue = (float)$stmt->fetchColumn();

        // Render the notifications view
        $this->view('notifications/index', [
            'notifications' => $notifications,
            'todayRevenue'  => $todayRevenue,
        ], 'notifications', 'Notifications');
    }
}
