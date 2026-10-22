<?php
/**
 * Payment Receipt View (Modal Content)
 *
 * Displays a detailed payment receipt with guest information, reservation details,
 * line-item charges (room charges, extra charges, tax), and total amount paid.
 * Used inside the billing index modal via AJAX and also for print view.
 *
 * Expected variables from controller:
 *   $payment       - array  - Payment record with joined guest, room, reservation data
 *   $bill          - array  - Bill breakdown (nights, rate, room_charge, tax)
 *   $extra_charges - array  - Additional charges (charge_type, description, amount)
 *   $settings      - array  - Hotel configuration (hotel_name, etc.)
 *   $tax_rate      - string - Tax percentage rate for display
 *
 * Security measures:
 *   - htmlspecialchars() on all output to prevent XSS
 *   - (int) cast on numeric values like nights
 *   - formatCurrency() for safe monetary display
 */
?>

<!-- RECEIPT CONTAINER: Main wrapper for the payment receipt display -->
<div class="receipt-container p-3">

    <!-- RECEIPT HEADER: Hotel name, invoice number, and payment date -->
    <div class="text-center border-bottom pb-3 mb-3">
        <!-- Security: htmlspecialchars() prevents XSS from hotel_name setting -->
        <h3><?php echo htmlspecialchars($settings['hotel_name'] ?? 'My Hotel'); ?></h3>
        <h5>Payment Receipt</h5>
        <!-- Security: htmlspecialchars() prevents XSS from invoice_number -->
        <p class="mb-0"><strong>Invoice #:</strong> <?php echo htmlspecialchars($payment['invoice_number']); ?></p>
        <!-- Security: htmlspecialchars() prevents XSS from formatted payment_date -->
        <p class="text-muted small">Date: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($payment['payment_date']))); ?></p>
    </div>

    <!-- GUEST & RESERVATION INFO: Two-column layout with guest and reservation details -->
    <div class="row mb-3">
        <!-- Guest information column -->
        <div class="col-md-6">
            <h6>Guest Information</h6>
            <!-- Security: htmlspecialchars() prevents XSS from full_name -->
            <p class="mb-0"><strong><?php echo htmlspecialchars($payment['full_name']); ?></strong></p>
            <!-- Security: htmlspecialchars() prevents XSS; null coalescing handles missing phone -->
            <p class="mb-0">Phone: <?php echo htmlspecialchars($payment['phone'] ?? 'N/A'); ?></p>
            <!-- Security: htmlspecialchars() prevents XSS; null coalescing handles missing email -->
            <p class="mb-0">Email: <?php echo htmlspecialchars($payment['email'] ?? 'N/A'); ?></p>
        </div>
        <!-- Reservation details column -->
        <div class="col-md-6">
            <h6>Reservation Details</h6>
            <!-- Security: htmlspecialchars() prevents XSS; shows WALK-IN if no reservation -->
            <p class="mb-0">Reservation: <?php echo htmlspecialchars($payment['reservation_number'] ?? 'WALK-IN'); ?></p>
            <!-- Security: htmlspecialchars() prevents XSS on concatenated room/floor string -->
            <p class="mb-0">Room: <?php echo htmlspecialchars($payment['room_number'] . ' (Floor ' . $payment['floor'] . ')'); ?></p>
            <!-- Security: htmlspecialchars() prevents XSS from room_type -->
            <p class="mb-0">Room Type: <?php echo htmlspecialchars($payment['room_type'] ?? 'N/A'); ?></p>
        </div>
    </div>

    <!-- STAY & PAYMENT DETAILS: Check-in/out dates and payment method info -->
    <div class="row mb-3">
        <!-- Stay period information -->
        <div class="col-md-6">
            <h6>Stay Period</h6>
            <!-- Security: htmlspecialchars() prevents XSS from check_in_time -->
            <p class="mb-0">Check-in: <?php echo htmlspecialchars($payment['check_in_time']); ?></p>
            <!-- Security: htmlspecialchars() prevents XSS from check_out_date -->
            <p class="mb-0">Check-out: <?php echo htmlspecialchars($payment['check_out_date']); ?></p>
        </div>
        <!-- Payment method and transaction details -->
        <div class="col-md-6">
            <h6>Payment Details</h6>
            <p class="mb-0">
                <!-- Payment method badge with dynamic color; Security: htmlspecialchars() prevents XSS -->
                Method: <span class="badge bg-<?php echo $payment['payment_method']=='Cash'?'success':($payment['payment_method']=='Credit/Debit Card'?'primary':'warning'); ?>">
                    <?php echo htmlspecialchars($payment['payment_method']); ?>
                </span>
            </p>
            <!-- Conditional: show transaction ID only if present -->
            <?php if ($payment['transaction_id']): ?>
                <!-- Security: htmlspecialchars() prevents XSS from transaction_id -->
                <p class="mb-0">Transaction ID: <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- CHARGES TABLE: Itemized list of all charges including room, extras, and tax -->
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Room charge line item: shows nights x rate calculation -->
                <tr>
                    <!-- Security: htmlspecialchars() prevents XSS on room_number; (int) cast on nights prevents injection -->
                    <td>Room Charges — <?php echo htmlspecialchars($payment['room_number']); ?> (<?php echo (int)($bill['nights'] ?? 0); ?> night<?php echo ($bill['nights'] ?? 0) == 1 ? '' : 's'; ?> &times; <?php echo formatCurrency($bill['rate'] ?? 0); ?>)</td>
                    <td class="text-end"><?php echo formatCurrency($bill['room_charge'] ?? 0); ?></td>
                </tr>
                <!-- Loop through extra charges array to display additional line items -->
                <?php foreach ($extra_charges as $charge): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS on concatenated charge type and description -->
                        <td><?php echo htmlspecialchars($charge['charge_type'] . ($charge['description'] ? ': ' . $charge['description'] : '')); ?></td>
                        <td class="text-end"><?php echo formatCurrency($charge['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <!-- Conditional: show tax line only if tax amount is greater than zero -->
                <?php if (($bill['tax'] ?? 0) > 0): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS on tax_rate -->
                        <td>Tax (<?php echo htmlspecialchars($tax_rate); ?>%)</td>
                        <td class="text-end"><?php echo formatCurrency($bill['tax']); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <!-- Total row with green highlight -->
                <tr class="table-success">
                    <th class="text-end">TOTAL</th>
                    <!-- Security: formatCurrency() safe for monetary total display -->
                    <th class="text-end"><?php echo formatCurrency($payment['amount']); ?></th>
                </tr>
                <!-- Payment status badge -->
                <tr>
                    <td colspan="2" class="text-center text-muted small">Payment Status: <span class="badge bg-success">PAID</span></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- RECEIPT FOOTER: Thank you message and computer-generated notice -->
    <div class="text-center mt-3 text-muted">
        <small>Thank you for staying with us!</small><br>
        <small>This is a computer-generated receipt</small>
    </div>
</div>
