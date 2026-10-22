<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * BillingController - Handles payments, receipts, and billing reports
 * 
 * Manages the billing system including viewing payment records,
 * generating receipts, printing receipts, and exporting billing
 * data to CSV format.
 * 
 * Access Roles: Administrator, Accountant, Manager
 * 
 * Available Actions:
 *   - indexAction()  : Display billing list with filters and CSV export
 *   - receiptAction(): Display a payment receipt
 *   - printAction()  : Print-friendly version of payment receipt
 *   - exportCsv()    : Export filtered billing data to CSV (private)
 */
class BillingController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'billing';

    /** @var string Page title for the layout */
    protected $pageTitle = 'Billing';

    /** @var array Allowed payment methods */
    private $methods = ['Cash', 'Credit/Debit Card', 'QR Payment', 'Bank Transfer'];

    /** @var int Number of records per page for pagination */
    private $perPage = 15;

    /**
     * Constructor - Enforce authentication and role-based access control.
     * Only Administrator, Accountant, and Manager roles can access billing.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Accountant', 'Manager']); // Role-based access control
        $this->loginRequired(); // Redundant call (already called above)
        $this->db = getDB(); // Initialize database connection
    }

    /**
     * indexAction - Display billing records with filtering, pagination, and CSV export.
     * 
     * Supports filtering by search term, payment method, and date range.
     * Includes summary statistics by payment method.
     * 
     * Data Flow:
     *   Input:   GET parameters: search, payment_method, start_date, end_date, export, page
     *   Process: Builds filtered SQL query, paginates results, calculates method summaries
     *   Output:  Renders billing/index view with payment data and summary
     */
    public function indexAction()
    {
        // Extract filter parameters from GET request
        $search = trim($_GET['search'] ?? '');
        $method = trim($_GET['payment_method'] ?? '');
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $export = $_GET['export'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        // CSV Export: If export=csv is requested, generate CSV and exit
        if ($export === 'csv') {
            $this->exportCsv($search, $method, $startDate, $endDate);
        }

        // Build base SQL query with JOINs across payments, check_ins, guests, rooms, reservations
        $base = "FROM payments p
                 JOIN check_ins ci ON p.check_in_id = ci.id
                 JOIN guests g ON ci.guest_id = g.id
                 JOIN rooms rm ON ci.room_id = rm.id
                 LEFT JOIN reservations r ON p.reservation_id = r.id
                 WHERE 1=1";
        $params = [];

        // Add search filter: matches guest name, room number, or invoice number
        if ($search !== '') {
            $base .= " AND (g.full_name LIKE ? OR rm.room_number LIKE ? OR p.invoice_number LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }
        // Add payment method filter
        if ($method !== '') { $base .= " AND p.payment_method = ?"; $params[] = $method; }
        // Add date range filters
        if ($startDate !== '') { $base .= " AND DATE(p.payment_date) >= ?"; $params[] = $startDate; }
        if ($endDate !== '') { $base .= " AND DATE(p.payment_date) <= ?"; $params[] = $endDate; }

        // Calculate total records for pagination
        $countStmt = $this->db->prepare("SELECT COUNT(*) " . $base);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $this->perPage));
        if ($page > $pages) $page = $pages;
        $offset = ($page - 1) * $this->perPage;

        // Fetch paginated payment records with related data
        $stmt = $this->db->prepare("SELECT p.*, g.full_name, rm.room_number, r.reservation_number, ci.check_in_time " . $base . " ORDER BY p.payment_date DESC LIMIT {$this->perPage} OFFSET $offset");
        $stmt->execute($params);
        $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calculate summary statistics grouped by payment method
        $summary = [];
        foreach ($this->methods as $m) $summary[$m] = ['count' => 0, 'amount' => 0];
        $sumStmt = $this->db->prepare("SELECT payment_method, COUNT(*) cnt, COALESCE(SUM(amount),0) amt " . $base . " GROUP BY payment_method");
        $sumStmt->execute($params);
        foreach ($sumStmt->fetchAll(\PDO::FETCH_ASSOC) as $s) {
            $summary[$s['payment_method']] = ['count' => $s['cnt'], 'amount' => $s['amt']];
        }
        // Calculate grand total across all payment methods
        $grandTotal = array_sum(array_column($summary, 'amount'));

        // Render the billing view with all data
        $this->view('billing/index', [
            'payments'   => $payments,
            'summary'    => $summary,
            'grandTotal' => $grandTotal,
            'methods'    => $this->methods,
            'search'     => $search,
            'method'     => $method,
            'startDate'  => $startDate,
            'endDate'    => $endDate,
            'page'       => $page,
            'pages'      => $pages,
        ], 'billing', 'Billing');
    }

    /**
     * receiptAction - Display a payment receipt for a specific payment.
     * 
     * Data Flow:
     *   Input:   GET parameter 'id' (payment record ID)
     *   Process: Fetches payment with all related data, computes bill, loads receipt view
     *   Output:  Renders billing/receipt.php with payment and bill data
     */
    public function receiptAction()
    {
        // Validate payment ID parameter
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) { echo '<div class="alert alert-danger">Invalid payment ID</div>'; return; }

        // Fetch payment record with all related data (guest, room, room type, check-in, reservation)
        $stmt = $this->db->prepare("
            SELECT p.*,
                   g.full_name, g.phone, g.email,
                   rm.room_number, rm.floor,
                   rt.type_name AS room_type,
                   ci.check_in_time,
                   r.reservation_number, r.check_out_date
            FROM payments p
            JOIN check_ins ci ON p.check_in_id = ci.id
            JOIN guests g ON ci.guest_id = g.id
            JOIN rooms rm ON ci.room_id = rm.id
            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
            LEFT JOIN reservations r ON p.reservation_id = r.id
            WHERE p.id = ?");
        $stmt->execute([$id]);
        $payment = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Return error if payment not found
        if (!$payment) { echo '<div class="alert alert-danger">Payment record not found</div>'; return; }

        // Get system settings for tax rate
        $settings = getSystemSettings();
        $tax_rate = (float)($settings['tax_rate'] ?? 0);

        // Fetch any extra charges associated with this check-in
        $chars = $this->db->prepare("SELECT * FROM extra_charges WHERE check_in_id=?");
        $chars->execute([$payment['check_in_id']]);
        $extra_charges = $chars->fetchAll(\PDO::FETCH_ASSOC);

        // Compute the full bill (room charge + extras + tax)
        $bill = computeCheckinBill($this->db, $payment['check_in_id'], $tax_rate);

        // Render the receipt view
        require __DIR__ . '/../Views/billing/receipt.php';
    }

    /**
     * printAction - Print-friendly version of the payment receipt.
     * 
     * Data Flow:
     *   Input:   GET parameter 'id' (payment record ID)
     *   Process: Same as receiptAction but renders print-friendly layout
     *   Output:  Renders billing/print.php with payment and bill data
     */
    public function printAction()
    {
        // Validate payment ID parameter
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$id) { die('Invalid payment ID'); }

        // Fetch payment record with all related data
        $stmt = $this->db->prepare("
            SELECT p.*,
                   g.full_name, g.phone, g.email,
                   rm.room_number, rm.floor,
                   rt.type_name AS room_type,
                   ci.check_in_time,
                   r.reservation_number, r.check_out_date
            FROM payments p
            JOIN check_ins ci ON p.check_in_id = ci.id
            JOIN guests g ON ci.guest_id = g.id
            JOIN rooms rm ON ci.room_id = rm.id
            LEFT JOIN room_types rt ON rm.room_type_id = rt.id
            LEFT JOIN reservations r ON p.reservation_id = r.id
            WHERE p.id = ?");
        $stmt->execute([$id]);
        $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$payment) { die('Payment record not found'); }

        // Get tax rate from system settings
        $settings = getSystemSettings();
        $tax_rate = (float)($settings['tax_rate'] ?? 0);

        // Fetch extra charges for this check-in
        $chars = $this->db->prepare("SELECT * FROM extra_charges WHERE check_in_id=?");
        $chars->execute([$payment['check_in_id']]);
        $extra_charges = $chars->fetchAll(\PDO::FETCH_ASSOC);

        // Compute the full bill
        $bill = computeCheckinBill($this->db, $payment['check_in_id'], $tax_rate);

        // Render the print-friendly receipt
        require __DIR__ . '/../Views/billing/print.php';
    }

    /**
     * exportCsv - Export filtered billing records to CSV format.
     * Sets appropriate headers and streams CSV data directly to the browser.
     * 
     * Data Flow:
     *   Input:   Filter parameters (search, method, startDate, endDate)
     *   Process: Builds filtered query, streams CSV rows to output
     *   Output:  CSV file download with payment records
     */
    private function exportCsv($search, $method, $startDate, $endDate)
    {
        // Build base SQL query with same filters as indexAction
        $base = "FROM payments p
                 JOIN check_ins ci ON p.check_in_id = ci.id
                 JOIN guests g ON ci.guest_id = g.id
                 JOIN rooms rm ON ci.room_id = rm.id
                 LEFT JOIN reservations r ON p.reservation_id = r.id
                 WHERE 1=1";
        $params = [];
        if ($search !== '') {
            $base .= " AND (g.full_name LIKE ? OR rm.room_number LIKE ? OR p.invoice_number LIKE ?)";
            $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
        }
        if ($method !== '') { $base .= " AND p.payment_method = ?"; $params[] = $method; }
        if ($startDate !== '') { $base .= " AND DATE(p.payment_date) >= ?"; $params[] = $startDate; }
        if ($endDate !== '') { $base .= " AND DATE(p.payment_date) <= ?"; $params[] = $endDate; }

        // Set HTTP headers for CSV file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="billing_report_' . date('Y-m-d') . '.csv"');

        // Open output stream to browser
        $output = fopen('php://output', 'w');

        // Write CSV column headers
        fputcsv($output, ['Invoice #', 'Guest', 'Room', 'Reservation', 'Amount', 'Method', 'Transaction ID', 'Date']);

        // Fetch and write each payment row to CSV
        $stmt = $this->db->prepare("SELECT p.*, g.full_name, rm.room_number, r.reservation_number " . $base . " ORDER BY p.payment_date DESC");
        $stmt->execute($params);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            fputcsv($output, [
                $row['invoice_number'], $row['full_name'], $row['room_number'],
                $row['reservation_number'] ?? 'N/A', number_format($row['amount'], 2),
                $row['payment_method'], $row['transaction_id'] ?? '', $row['payment_date']
            ]);
        }

        // Close output stream and stop script execution
        fclose($output);
        exit;
    }
}
