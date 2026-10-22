<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * ReportController - Hotel analytics and reporting
 * 
 * Generates comprehensive hotel reports including:
 *   - Occupancy metrics (total rooms, occupied, occupancy rate)
 *   - Revenue analytics (period revenue, total revenue, ADR, RevPAR)
 *   - 6-month revenue trend data
 *   - Revenue breakdown by room type
 *   - Top 5 guests by stay count
 *   - CSV export of report data
 * 
 * Access Roles: Administrator, Manager, Accountant
 * 
 * Available Actions:
 *   - indexAction(): Display report dashboard with date range filtering
 *   - exportCsv()  : Export report data to CSV format (private)
 */
class ReportController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'reports';

    /** @var string Page title for the layout */
    protected $pageTitle = 'Reports';

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Only Administrator, Manager, and Accountant can access reports.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Manager', 'Accountant']); // Role-based access control
        $this->db = getDB(); // Initialize database connection
    }

    /**
     * indexAction - Display report dashboard with date range filtering.
     * 
     * Supports date range filtering via GET parameters 'from' and 'to'.
     * Calculates occupancy rates, revenue metrics, ADR, RevPAR, and
     * provides data for charts and tables.
     * 
     * Data Flow:
     *   Input:   GET parameters: from, to (date range), export (csv trigger)
     *   Process: Validates dates, queries multiple metrics, calculates KPIs
     *   Output:  Renders reports/index view with all report data
     */
    public function indexAction()
    {
        // Extract and validate date range parameters
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        // Validate dates are parseable
        if (!strtotime($from)) $from = date('Y-m-01');
        if (!strtotime($to)) $to = date('Y-m-d');
        // Ensure 'from' is not after 'to'; swap if needed
        if (strtotime($to) < strtotime($from)) { $tmp = $from; $from = $to; $to = $tmp; }
        $rangeParams = [$from, $to];

        // --- Occupancy Metrics ---
        $totalRooms = (int)$this->db->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        $occupiedRooms = (int)$this->db->query("SELECT COUNT(*) FROM rooms WHERE status='Occupied'")->fetchColumn();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // --- Revenue Metrics ---
        // Revenue within the selected date range
        $revStmt = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?");
        $revStmt->execute($rangeParams);
        $periodRevenue = (float)$revStmt->fetchColumn();
        // Total revenue across all time
        $totalRevenue = (float)$this->db->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
        // Current number of guests in-house
        $currentGuests = (int)$this->db->query("SELECT COUNT(*) FROM check_ins WHERE status='Active'")->fetchColumn();

        // CSV Export: If export=csv is requested, generate CSV and exit
        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($from, $to, $rangeParams, $totalRooms, $occupiedRooms, $occupancyRate, $currentGuests, $periodRevenue, $totalRevenue);
        }

        // --- Average Daily Rate (ADR) and Revenue Per Available Room (RevPAR) ---
        // Calculate total room nights in the current month
        $roomNights = (int)$this->db->query("SELECT COALESCE(SUM(DATEDIFF(r.check_out_date, r.check_in_date)),0)
                                             FROM reservations r WHERE r.status IN ('Completed','Confirmed')
                                             AND MONTH(r.check_in_date)=MONTH(CURDATE())")->fetchColumn();
        // ADR = period revenue / room nights
        $adr = ($roomNights > 0) ? round($periodRevenue / max($roomNights, 1), 2) : 0;
        // RevPAR = period revenue / (total rooms * 30 days)
        $revpar = ($totalRooms > 0) ? round($periodRevenue / max($totalRooms * 30, 1), 2) : 0;

        // --- 6-Month Revenue Trend ---
        // Fetch monthly revenue for the last 6 months for chart display
        $months = [];
        $mlabels = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} months"));
            $row = $this->db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE DATE_FORMAT(payment_date,'%Y-%m')=?");
            $row->execute([$m]);
            $months[] = (float)$row->fetchColumn();
            $mlabels[] = date('M', strtotime($m . '-01'));
        }

        // --- Revenue by Room Type ---
        // Aggregate revenue grouped by room type within the selected date range
        $revTypeStmt = $this->db->prepare("SELECT rt.type_name, COALESCE(SUM(p.amount),0) revenue
                                           FROM room_types rt
                                           LEFT JOIN rooms r ON r.room_type_id=rt.id
                                           LEFT JOIN reservations res ON res.room_id=r.id
                                           LEFT JOIN payments p ON p.reservation_id=res.id AND DATE(p.payment_date) BETWEEN ? AND ?
                                           GROUP BY rt.id, rt.type_name");
        $revTypeStmt->execute($rangeParams);
        $revenueByType = $revTypeStmt->fetchAll(\PDO::FETCH_ASSOC);

        // --- Top 5 Guests ---
        // Guests ranked by number of stays and total spend
        $topGuests = $this->db->query("SELECT g.full_name, COUNT(res.id) stays, COALESCE(SUM(res.total_amount),0) spend
                                       FROM guests g
                                       JOIN reservations res ON res.guest_id=g.id
                                       GROUP BY g.id, g.full_name ORDER BY stays DESC LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);

        // Prepare chart data for revenue by room type
        $revByTypeLabels = [];
        $revByTypeData = [];
        foreach ($revenueByType as $t) { $revByTypeLabels[] = $t['type_name']; $revByTypeData[] = (float)$t['revenue']; }

        // Render the reports view with all collected data
        $this->view('reports/index', [
            'from' => $from, 'to' => $to,
            'totalRooms' => $totalRooms, 'occupiedRooms' => $occupiedRooms, 'occupancyRate' => $occupancyRate,
            'currentGuests' => $currentGuests, 'periodRevenue' => $periodRevenue, 'totalRevenue' => $totalRevenue,
            'adr' => $adr, 'revpar' => $revpar, 'months' => $months, 'mlabels' => $mlabels,
            'revenueByType' => $revenueByType, 'topGuests' => $topGuests,
            'revByTypeLabels' => $revByTypeLabels, 'revByTypeData' => $revByTypeData,
        ], 'reports', 'Reports');
    }

    /**
     * exportCsv - Export report data to CSV format.
     * Includes summary metrics and daily revenue breakdown.
     * 
     * Data Flow:
     *   Input:   Date range and pre-calculated metrics
     *   Process: Streams CSV headers and data rows to browser output
     *   Output:  CSV file download
     */
    private function exportCsv($from, $to, $rangeParams, $totalRooms, $occupiedRooms, $occupancyRate, $currentGuests, $periodRevenue, $totalRevenue)
    {
        // Set HTTP headers for CSV file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hotel_report_' . $from . '_to_' . $to . '.csv"');

        // Open output stream to browser
        $out = fopen('php://output', 'w');

        // Write summary metrics as key-value pairs
        fputcsv($out, ['Metric', 'Value']);
        fputcsv($out, ['Period From', $from]);
        fputcsv($out, ['Period To', $to]);
        fputcsv($out, ['Total Rooms', $totalRooms]);
        fputcsv($out, ['Occupied Rooms', $occupiedRooms]);
        fputcsv($out, ['Occupancy Rate (%)', $occupancyRate]);
        fputcsv($out, ['Current Guests In-house', $currentGuests]);
        fputcsv($out, ['Revenue in Period', number_format($periodRevenue, 2)]);
        fputcsv($out, ['Total Revenue (all time)', number_format($totalRevenue, 2)]);

        // Write daily revenue breakdown section
        $dayStmt = $this->db->prepare("SELECT DATE(payment_date) d, COALESCE(SUM(amount),0) amt FROM payments WHERE DATE(payment_date) BETWEEN ? AND ? GROUP BY DATE(payment_date) ORDER BY d");
        $dayStmt->execute($rangeParams);
        fputcsv($out, ['']); // Empty separator row
        fputcsv($out, ['Daily Revenue']); // Section header
        fputcsv($out, ['Date', 'Amount']); // Column headers

        // Write each day's revenue row
        foreach ($dayStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($out, [$row['d'], number_format($row['amt'], 2)]);
        }

        // Close output stream and stop script execution
        fclose($out);
        exit;
    }
}
