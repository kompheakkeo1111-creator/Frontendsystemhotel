<?php
/**
 * Printable Payment Receipt View
 *
 * Standalone HTML page for printing a payment receipt. Opens in a new browser
 * tab/window for print-friendly output with dedicated print CSS styles.
 * Contains the same receipt data as receipt.php but in a full-page layout.
 *
 * Expected variables from controller:
 *   $payment       - array  - Payment record with guest, room, reservation data
 *   $bill          - array  - Bill breakdown (nights, rate, room_charge, tax)
 *   $extra_charges - array  - Additional charges (charge_type, description, amount)
 *   $settings      - array  - Hotel configuration (hotel_name)
 *   $tax_rate      - string - Tax percentage rate for display
 *
 * Security measures:
 *   - htmlspecialchars() on all output to prevent XSS
 *   - (int) cast on numeric values like nights
 *   - formatCurrency() for safe monetary display
 *   - @media print CSS hides non-printable elements
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo htmlspecialchars($payment['invoice_number']); ?></title> <!-- Security: Prevent XSS in page title -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .receipt { max-width: 820px; margin: 30px auto; background: #fff; padding: 40px; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .receipt-header { text-align: center; border-bottom: 2px solid #198754; padding-bottom: 20px; margin-bottom: 25px; }
        .receipt-header h2 { color: #198754; font-weight: 700; }
        .receipt-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .info-box { background: #f8f9fa; padding: 14px; border-radius: 8px; }
        .label { font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .value { font-weight: 600; margin-top: 4px; }
        .total-box { margin-top: 22px; background: #198754; color: #fff; padding: 18px; border-radius: 10px; display: flex; justify-content: space-between; font-size: 22px; font-weight: 700; }
        .no-print { margin-top: 25px; text-align: center; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="receipt">

        <!-- RECEIPT HEADER: Hotel branding, title, invoice number, and timestamp -->
        <div class="receipt-header">
            <!-- Hotel icon (HTML entity for building emoji) -->
            <div style="font-size:42px">&#127976;</div>
            <!-- Security: htmlspecialchars() prevents XSS from hotel_name setting -->
            <h2><?php echo htmlspecialchars($settings['hotel_name'] ?? 'My Hotel'); ?></h2>
            <div>PAYMENT RECEIPT</div>
            <!-- Security: htmlspecialchars() prevents XSS from invoice_number -->
            <small class="text-muted">Invoice: <strong><?php echo htmlspecialchars($payment['invoice_number']); ?></strong></small>
            <!-- Security: htmlspecialchars() prevents XSS from formatted payment_date -->
            <div><small class="text-muted"><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($payment['payment_date']))); ?></small></div>
        </div>

        <!-- RECEIPT INFO GRID: 2-column grid layout for guest and reservation details -->
        <div class="receipt-grid">
            <!-- Guest name info box -->
            <div class="info-box">
                <div class="label">Guest</div>
                <!-- Security: htmlspecialchars() prevents XSS from full_name -->
                <div class="value"><?php echo htmlspecialchars($payment['full_name']); ?></div>
            </div>
            <!-- Room number info box -->
            <div class="info-box">
                <div class="label">Room</div>
                <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                <div class="value"><?php echo htmlspecialchars($payment['room_number']); ?></div>
            </div>
            <!-- Reservation number info box (WALK-IN fallback) -->
            <div class="info-box">
                <div class="label">Reservation</div>
                <!-- Security: htmlspecialchars() prevents XSS; null coalescing for walk-in guests -->
                <div class="value"><?php echo htmlspecialchars($payment['reservation_number'] ?? 'WALK-IN'); ?></div>
            </div>
            <!-- Payment method info box -->
            <div class="info-box">
                <div class="label">Payment Method</div>
                <!-- Security: htmlspecialchars() prevents XSS from payment_method -->
                <div class="value"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
            </div>
            <!-- Check-in date info box -->
            <div class="info-box">
                <div class="label">Check-in</div>
                <!-- Security: htmlspecialchars() prevents XSS from check_in_time -->
                <div class="value"><?php echo htmlspecialchars($payment['check_in_time']); ?></div>
            </div>
            <!-- Checkout date info box -->
            <div class="info-box">
                <div class="label">Checkout</div>
                <!-- Security: htmlspecialchars() prevents XSS; empty string fallback -->
                <div class="value"><?php echo htmlspecialchars($payment['check_out_date'] ?? ''); ?></div>
            </div>
        </div>

        <!-- CHARGES TABLE: Itemized list of room charges, extras, and tax -->
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Room charge line: nights x nightly rate calculation -->
                <tr>
                    <!-- Security: htmlspecialchars() on room_number; (int) cast on nights prevents injection -->
                    <td>Room Charges — <?php echo htmlspecialchars($payment['room_number']); ?> (<?php echo (int)($bill['nights'] ?? 0); ?> night<?php echo ($bill['nights'] ?? 0) == 1 ? '' : 's'; ?> &times; <?php echo formatCurrency($bill['rate'] ?? 0); ?>)</td>
                    <td class="text-end"><?php echo formatCurrency($bill['room_charge'] ?? 0); ?></td>
                </tr>
                <!-- Loop through extra charges to display additional line items -->
                <?php foreach ($extra_charges as $charge): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS on charge_type and description -->
                        <td><?php echo htmlspecialchars($charge['charge_type'] . ($charge['description'] ? ': ' . $charge['description'] : '')); ?></td>
                        <td class="text-end"><?php echo formatCurrency($charge['amount']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <!-- Conditional: display tax line only when tax > 0 -->
                <?php if (($bill['tax'] ?? 0) > 0): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS on tax_rate -->
                        <td>Tax (<?php echo htmlspecialchars($tax_rate); ?>%)</td>
                        <td class="text-end"><?php echo formatCurrency($bill['tax']); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- TOTAL & STATUS: Prominent total display and paid badge -->
        <div class="total-box">
            <span>TOTAL</span>
            <!-- Security: formatCurrency() safe for monetary display -->
            <span><?php echo formatCurrency($payment['amount']); ?></span>
        </div>
        <div class="text-center mt-3">
            <span class="badge bg-success fs-6">PAID</span>
        </div>
        <div class="text-center text-muted mt-4">Thank you for staying with us!</div>

        <!-- PRINT ACTIONS: Buttons hidden during printing via @media print CSS -->
        <div class="no-print">
            <!-- Print button: triggers browser print dialog -->
            <button onclick="window.print()" class="btn btn-success btn-lg"><i class="bi bi-printer"></i> Print</button>
            <!-- Back button: returns to billing index page -->
            <a href="index.php?r=billing/index" class="btn btn-secondary btn-lg">Back</a>
        </div>
    </div>
</body>
</html>
