<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * DashboardController - Main dashboard for hotel management
 * 
 * Provides an overview of hotel operations including room occupancy,
 * revenue metrics, check-in/out statistics, arrivals/departures,
 * and a visual room status map.
 * 
 * Access Roles: Any logged-in user (no specific role restriction beyond login)
 * 
 * Available Actions:
 *   - indexAction(): Display the main dashboard with all overview data
 */
class DashboardController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'dashboard';

    /** @var string Page title for the layout */
    protected $pageTitle = 'Dashboard';

    /**
     * Constructor - Enforce authentication.
     * All logged-in users can access the dashboard.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->db = getDB(); // Initialize database connection
    }

    /**
     * indexAction - Display the main dashboard with comprehensive hotel metrics.
     * 
     * Gathers and displays:
     *   - Room statistics (total, available, occupied, occupancy rate)
     *   - Guest counts (current, today's check-ins, today's check-outs)
     *   - Revenue data (today, monthly, 7-day trend, by room type)
     *   - Today's arrivals and departures
     *   - Recent reservations
     *   - Full room status map with occupant names
     * 
     * Data Flow:
     *   Input:   None (all data is fetched from database)
     *   Process: Executes multiple queries for each metric category
     *   Output:  Renders dashboard/index view with all metric data
     */
    public function indexAction()
    {
        $db = $this->db;

        // --- Room Statistics ---
        // Total number of rooms in the hotel
        $totalRooms = (int)$db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        // Rooms with 'Available' status
        $availableRooms = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status='Available'")->fetchColumn();
        // Rooms with 'Occupied' status
        $occupiedRooms = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status='Occupied'")->fetchColumn();
        // Calculate occupancy rate as percentage
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // --- Guest Statistics ---
        // Currently active check-ins (guests in-house)
        $currentGuests = (int)$db->query("SELECT COUNT(*) FROM check_ins WHERE status='Active'")->fetchColumn();
        // Today's check-in count
        $todayCheckins = (int)$db->query("SELECT COUNT(*) FROM check_ins WHERE DATE(check_in_time)=CURDATE()")->fetchColumn();
        // Today's check-out count
        $todayCheckouts = (int)$db->query("SELECT COUNT(*) FROM check_ins WHERE DATE(actual_check_out)=CURDATE()")->fetchColumn();

        // --- Revenue Statistics ---
        // Today's total revenue
        $todayRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date)=CURDATE()")->fetchColumn();
        // Current month's total revenue
        $monthlyRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();

        // --- 7-Day Revenue Trend ---
        // Fetch daily revenue for the past 7 days for chart display
        $revenueDays = [];
        $revenueLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $row = $db->prepare("SELECT COALESCE(SUM(amount),0) v FROM payments WHERE DATE(payment_date)=?");
            $row->execute([$d]);
            $revenueDays[] = (float)$row->fetchColumn();
            $revenueLabels[] = date('M d', strtotime($d));
        }

        // --- Revenue by Room Type ---
        // Aggregate revenue grouped by room type for chart display
        $revByTypeLabels = [];
        $revByTypeData = [];
        foreach ($db->query("SELECT rt.type_name, COALESCE(SUM(p.amount),0) revenue
                            FROM room_types rt
                            LEFT JOIN rooms r ON r.room_type_id=rt.id
                            LEFT JOIN reservations res ON res.room_id=r.id
                            LEFT JOIN payments p ON p.reservation_id=res.id
                            GROUP BY rt.id, rt.type_name ORDER BY revenue DESC") as $t) {
            $revByTypeLabels[] = $t['type_name'];
            $revByTypeData[] = (float)$t['revenue'];
        }

        // --- Today's Arrivals ---
        // Reservations scheduled to check in today
        $arrivals = $db->query("SELECT r.reservation_number, g.full_name, rm.room_number
                                FROM reservations r
                                JOIN guests g ON r.guest_id=g.id
                                LEFT JOIN rooms rm ON r.room_id=rm.id
                                WHERE r.check_in_date=CURDATE() AND r.status IN ('Pending','Confirmed')
                                ORDER BY r.id")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Today's Departures ---
        // Active check-ins where expected check-out is today or earlier
        $departures = $db->query("SELECT g.full_name, rm.room_number, c.expected_check_out
                                  FROM check_ins c
                                  JOIN guests g ON c.guest_id=g.id
                                  LEFT JOIN rooms rm ON c.room_id=rm.id
                                  WHERE c.status='Active' AND c.expected_check_out <= CURDATE()
                                  ORDER BY c.expected_check_out")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Recent Reservations ---
        // Last 5 reservations for quick overview
        $recentReservations = $db->query("SELECT r.*, g.full_name guest_name, rm.room_number
                                          FROM reservations r
                                          JOIN guests g ON r.guest_id=g.id
                                          LEFT JOIN rooms rm ON r.room_id=rm.id
                                          ORDER BY r.created_at DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);

        // --- Room Status Map ---
        // All rooms with status, type, and current occupant name (if occupied)
        $rooms = $db->query("SELECT r.id, r.room_number, r.status, rt.type_name,
                                    (SELECT g.full_name FROM check_ins c JOIN guests g ON g.id=c.guest_id
                                      WHERE c.room_id=r.id AND c.status='Active' LIMIT 1) occupant
                             FROM rooms r
                             LEFT JOIN room_types rt ON r.room_type_id=rt.id
                             ORDER BY r.room_number")->fetchAll(\PDO::FETCH_ASSOC);

        // Render the dashboard view with all collected data
        $this->view('dashboard/index', [
            'totalRooms' => $totalRooms, 'availableRooms' => $availableRooms, 'occupiedRooms' => $occupiedRooms,
            'occupancyRate' => $occupancyRate, 'currentGuests' => $currentGuests,
            'todayCheckins' => $todayCheckins, 'todayCheckouts' => $todayCheckouts,
            'todayRevenue' => $todayRevenue, 'monthlyRevenue' => $monthlyRevenue,
            'revenueDays' => $revenueDays, 'revenueLabels' => $revenueLabels,
            'revByTypeLabels' => $revByTypeLabels, 'revByTypeData' => $revByTypeData,
            'arrivals' => $arrivals, 'departures' => $departures,
            'recentReservations' => $recentReservations, 'rooms' => $rooms,
        ], 'dashboard', 'Dashboard');
    }
}
