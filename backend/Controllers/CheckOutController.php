<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\CheckIn;

/**
 * CheckOutController - Handles guest check-out and payment processing
 * 
 * Manages the check-out process including bill calculation, extra charges,
 * payment recording, room status updates, and invoice generation.
 * 
 * Access Roles: Administrator, Receptionist, Manager
 * 
 * Available Actions:
 *   - indexAction()     : Display check-out page with active stays and bill calculations
 *   - processCheckout() : Process the check-out and payment (private)
 */
class CheckOutController extends Controller
{
    /** @var string Active navigation menu item identifier */
    protected $active = 'checkout';

    /** @var array Allowed payment methods */
    private $methods = ['Cash', 'Credit/Debit Card', 'QR Payment', 'Bank Transfer'];

    /** @var array Allowed extra charge types */
    private $chargeTypes = ['Room Service', 'Laundry', 'Restaurant', 'Mini Bar', 'Damage Fee', 'Other'];

    /**
     * Constructor - Enforce authentication and initialize settings.
     * Loads system settings and tax rate for bill calculations.
     */
    public function __construct()
    {
        $this->loginRequired(); // Ensure user is logged in
        $this->roleRequired(['Administrator', 'Receptionist', 'Manager']); // Role-based access control
        $this->loginRequired(); // Redundant call (already called above)
        $this->checkIn = new CheckIn(); // Initialize CheckIn model
        $this->settings = getSystemSettings(); // Load system settings
        $this->taxRate = (float)($this->settings['tax_rate'] ?? 0); // Extract tax rate
    }

    /**
     * indexAction - Display check-out page and process check-out submissions.
     * 
     * Shows active stays with calculated bills and processes check-out
     * with optional extra charges and payment recording.
     * 
     * Data Flow:
     *   Input:   POST data with checkout action and payment details
     *   Process: Calculates bills for all stays, processes checkout if POST
     *   Output:  Renders checkout/index view with stays, receipt, and bill data
     */
    public function indexAction()
    {
        $db = getDB();
        $receipt = null;
        $message = $this->message ?? '';
        $error = $this->error ?? '';

        // Process check-out form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
            $result = $this->processCheckout($_POST);
            $db = getDB(); // Reinitialize DB connection (helper may open its own)
            if ($result['error']) {
                $error = $result['error'];
            } else {
                $message = $result['message'];
                $receipt = $result['receipt'];
            }
        }

        // Fetch all active stays eligible for check-out
        $stays = $this->checkIn->activeStaysForCheckout();

        // Calculate bill for each stay (nights, room charge, tax, total)
        foreach ($stays as &$s) {
            $t = computeCheckinBill(getDB(), (int)$s['id'], $this->taxRate);
            $s['nights'] = $t['nights'] ?? 0;
            $s['room_charge'] = $t['room_charge'] ?? 0;
            $s['tax'] = $t['tax'] ?? 0;
            $s['stay_total'] = $t['total'] ?? 0;
        }
        unset($s); // Break reference to avoid unintended side effects

        // Render the check-out view with all data
        $this->view('checkout/index', [
            'stays'    => $stays,
            'methods'  => $this->methods,
            'chargeTypes' => $this->chargeTypes,
            'taxRate'  => $this->taxRate,
            'currency' => $this->settings['currency'] ?? 'USD',
            'hotelName'=> $this->settings['hotel_name'] ?? 'My Hotel',
            'message'  => $message,
            'error'    => $error,
            'receipt'  => $receipt,
        ], 'checkout', 'Check-out & Payment');
    }

    /**
     * Process a guest check-out with optional extra charges and payment.
     * 
     * Handles the full check-out workflow:
     *   1. Validate input data
     *   2. Optionally add extra charges
     *   3. Recalculate bill total
     *   4. Mark check-in as completed
     *   5. Update reservation status to 'Completed'
     *   6. Set room status to 'Cleaning'
     *   7. Record payment and generate invoice number
     * 
     * Data Flow:
     *   Input:   POST data array with check_in_id, amount, method, transaction_id, extra charges
     *   Process: Validates, computes bill, records payment, updates all related records
     *   Output:  Array with error/message/receipt data
     */
    private function processCheckout(array $post)
    {
        $db = getDB();
        $checkInId = (int)($post['check_in_id'] ?? 0);
        $amount = trim($post['amount'] ?? '');
        $method = $post['method'] ?? 'Cash';
        $transactionId = trim($post['transaction_id'] ?? '');
        $addChargeType = $post['charge_type'] ?? '';
        $addChargeDesc = trim($post['charge_desc'] ?? '');
        $addChargeAmt = trim($post['charge_amount'] ?? '');

        // Validate payment method against allowed list
        if (!in_array($method, $this->methods, true)) $method = 'Cash';
        // Validate payment amount is a valid non-negative number
        if ($amount === '' || !is_numeric($amount) || (float)$amount < 0) {
            return ['error' => 'Please enter a valid payment amount.', 'message' => '', 'receipt' => null];
        }

        try {
            $db->beginTransaction(); // Start transaction for atomicity

            // Fetch the check-in record with all related data
            $ci = $this->checkIn->checkoutDetail($checkInId);
            if (!$ci) throw new \Exception('Check-in record not found or already checked out.');

            // Add extra charge if provided and valid
            if ($addChargeType !== '' && $addChargeAmt !== '' && is_numeric($addChargeAmt) && (float)$addChargeAmt > 0) {
                $this->checkIn->addExtraCharge($ci['id'], $addChargeType, $addChargeDesc ?: $addChargeType, (float)$addChargeAmt);
                // Recalculate bill total after adding extra charge
                $tot = computeCheckinBill($db, (int)$ci['id'], $this->taxRate);
                $amount = (string)$tot['total']; // Update amount to reflect new total
            }

            $paymentAmount = (float)$amount;

            // Mark check-in as completed (sets actual_check_out timestamp)
            $this->checkIn->markCheckedOut($ci['id']);

            // Update reservation status to 'Completed' if reservation exists
            if ($ci['reservation_id']) {
                $db->prepare("UPDATE reservations SET status='Completed' WHERE id=?")->execute([$ci['reservation_id']]);
            }
            // Set room status to 'Cleaning' so housekeeping is notified
            $db->prepare("UPDATE rooms SET status='Cleaning' WHERE id=?")->execute([$ci['room_id']]);

            // Generate unique invoice number: INV-YYYYMMDD-XXXXX
            $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad((string)(int)$ci['id'], 5, '0', STR_PAD_LEFT);
            $paymentId = null;

            // Record payment if amount is greater than zero
            if ($paymentAmount > 0) {
                // Ensure invoice number is unique by incrementing if needed
                $finalInvoice = $invoiceNo;
                $n = (int)$ci['id'];
                $countStmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE invoice_number=?");
                while (true) {
                    $countStmt->execute([$finalInvoice]);
                    if ((int)$countStmt->fetchColumn() === 0) break;
                    $n++;
                    $finalInvoice = 'INV-' . date('Ymd') . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
                }
                // Insert payment record
                $db->prepare("INSERT INTO payments (reservation_id, check_in_id, amount, payment_method, transaction_id, payment_date, invoice_number)
                              VALUES (?,?,?,?,?,NOW(),?)")
                    ->execute([$ci['reservation_id'], $ci['id'], $paymentAmount, $method, $transactionId ?: null, $finalInvoice]);
                $paymentId = (int)$db->lastInsertId();
                $invoiceNo = $finalInvoice;
            }

            $db->commit(); // Commit all changes

            // Build receipt data for display
            return [
                'error' => '',
                'message' => 'Guest checked out successfully. Bill generated.',
                'receipt' => [
                    'invoice_no' => $invoiceNo,
                    'payment_id' => $paymentId,
                    'reservation_number' => $ci['reservation_number'] ?? 'WALK-IN',
                    'guest_name' => $ci['guest_name'],
                    'room_number' => $ci['room_number'],
                    'check_in_time' => $ci['check_in_time'],
                    'checkout_time' => date('Y-m-d H:i:s'),
                    'amount' => $paymentAmount,
                    'method' => $method,
                    'transaction_id' => $transactionId,
                    'status' => $paymentAmount > 0 ? 'PAID' : 'NO PAYMENT',
                ],
            ];
        } catch (\Exception $e) {
            // Rollback on error to maintain data consistency
            if ($db->inTransaction()) $db->rollBack();
            return ['error' => 'Error: ' . $e->getMessage(), 'message' => '', 'receipt' => null];
        }
    }
}
