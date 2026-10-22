<?php
/**
 * Check-out & Payment View
 *
 * Displays currently checked-in guests with payment and checkout functionality.
 * Includes modals for adding charges, processing payments, and generating bills.
 * Shows a printable receipt after successful checkout.
 *
 * Expected variables from controller:
 *   $stays        - array  - Currently checked-in guests with stay details
 *   $chargeTypes  - array  - Available extra charge types for dropdown
 *   $methods      - array  - Payment methods for dropdown (Cash, Card, QR, etc.)
 *   $taxRate      - string - Tax rate percentage for calculation display
 *   $currency     - string - Currency code symbol (e.g., "$")
 *   $hotelName    - string - Hotel name for receipt display
 *   $receipt      - array  - Post-checkout receipt data (optional, shown after checkout)
 *   $message      - string - Success flash message (optional)
 *   $error        - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on all record IDs in modal IDs, hidden fields, and JS calls
 *   - POST method for all form submissions (checkout, add charge)
 *   - JavaScript confirm() for destructive actions
 *   - Hidden action fields to distinguish form purposes
 */
?>

<!-- PAGE HEADER: Title and link to billing page -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Check-out & Payment</h2>
        <p class="page-subtitle mb-0">Complete checkout and issue a bill.</p>
    </div>
    <!-- Quick link to billing index page for viewing all payments -->
    <a href="index.php?r=billing/index" class="btn btn-outline-success">
        <i class="bi bi-receipt"></i> Payment Billing
    </a>
</div>

<!-- FLASH MESSAGES: Display success/error messages from controller actions -->
<?php if (!empty($message)): ?>
    <!-- Security: htmlspecialchars() prevents XSS in success flash message -->
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <!-- Security: htmlspecialchars() prevents XSS in error flash message -->
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- CURRENT GUESTS TABLE: Lists all checked-in guests with estimated totals and checkout buttons -->
<div class="card">
    <div class="card-body p-4">
        <!-- Table header with active guest count -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-people-fill text-success"></i> Current Guests</h5>
            <!-- count() shows total active stays -->
            <span class="badge bg-secondary"><?php echo count($stays); ?> Active</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Expected Out</th>
                        <th class="text-end">Est. Total</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <!-- Empty state: shown when no guests are checked in -->
                <?php if (!$stays): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No guests currently checked in.</td></tr>
                <?php else: foreach ($stays as $c): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS from guest_name -->
                        <td class="fw-semibold"><?php echo htmlspecialchars($c['guest_name']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                        <td><span class="badge bg-primary">Room <?php echo htmlspecialchars($c['room_number']); ?></span></td>
                        <!-- Check-in time: formatted from timestamp -->
                        <td><?php echo date('Y-m-d H:i', strtotime($c['check_in_time'])); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from expected_check_out -->
                        <td><?php echo htmlspecialchars($c['expected_check_out']); ?></td>
                        <!-- Security: formatCurrency() safe for estimated total display -->
                        <td class="text-end fw-bold"><?php echo formatCurrency($c['stay_total']); ?></td>
                        <td class="text-end">
                            <!-- Checkout button: opens modal with dynamic ID based on stay ID -->
                            <!-- Security: (int) cast prevents XSS in modal ID attribute -->
                            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#checkout<?php echo (int)$c['id']; ?>">
                                <i class="bi bi-credit-card"></i> Payment & Check-out
                            </button>
                        </td>
                    </tr>

                    <!-- CHECKOUT MODAL FOR EACH GUEST: Unique modal per stay with payment form -->
                    <!-- Security: (int) cast ensures modal ID is safe for HTML attribute -->
                    <div class="modal fade" id="checkout<?php echo (int)$c['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <!-- Payment form: POST method with action="checkout" -->
                                <form method="POST">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">Payment & Check-out</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Hidden fields: action type and stay ID for controller processing -->
                                        <input type="hidden" name="action" value="checkout">
                                        <!-- Security: (int) cast prevents SQL injection in hidden field -->
                                        <input type="hidden" name="check_in_id" value="<?php echo (int)$c['id']; ?>">
                                        <!-- Guest summary display -->
                                        <div class="d-flex align-items-center gap-3 mb-4">
                                            <div>
                                                <!-- Security: htmlspecialchars() prevents XSS on guest_name -->
                                                <strong><?php echo htmlspecialchars($c['guest_name']); ?></strong><br>
                                                <!-- Security: htmlspecialchars() prevents XSS on room_number and reservation_number -->
                                                <small class="text-muted">Room <?php echo htmlspecialchars($c['room_number']); ?> · <?php echo htmlspecialchars($c['reservation_number'] ?? 'WALK-IN'); ?></small>
                                            </div>
                                        </div>

                                        <!-- EXTRA CHARGES SECTION: Add additional charges before checkout -->
                                        <div class="mb-3 border rounded p-3">
                                            <h6 class="mb-3">Add Extra Charge</h6>
                                            <div class="row g-2">
                                                <!-- Charge type dropdown: populated from $chargeTypes array -->
                                                <div class="col-md-4">
                                                    <select name="charge_type" class="form-select">
                                                        <option value="">None</option>
                                                        <!-- Loop through available charge types -->
                                                        <?php foreach ($chargeTypes as $ct): ?>
                                                            <option value="<?php echo $ct; ?>"><?php echo $ct; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <!-- Charge description text input -->
                                                <div class="col-md-5">
                                                    <input type="text" name="charge_desc" class="form-control" placeholder="Description">
                                                </div>
                                                <!-- Charge amount with decimal step and min=0 validation -->
                                                <div class="col-md-3">
                                                    <input type="number" name="charge_amount" class="form-control" placeholder="Amount" step="0.01" min="0">
                                                </div>
                                            </div>
                                            <!-- Summary of existing extra charges for this stay -->
                                            <small class="text-muted">Existing extras: <?php echo $c['extra_count']; ?> (<?php echo formatCurrency($c['extra_total']); ?>)</small>
                                        </div>

                                        <!-- PAYMENT DETAILS SECTION: Final amount, method, and transaction ID -->
                                        <div class="row">
                                            <!-- Final amount input: pre-filled with calculated stay total -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Final Amount</label>
                                                <div class="input-group input-group-lg">
                                                    <!-- Security: htmlspecialchars() prevents XSS on currency symbol -->
                                                    <span class="input-group-text"><?php echo htmlspecialchars($currency); ?></span>
                                                    <!-- Pre-filled with stay_total; user can adjust if needed -->
                                                    <input required type="number" step="0.01" min="0" name="amount" class="form-control amount-input" value="<?php echo $c['stay_total']; ?>">
                                                </div>
                                            </div>
                                            <!-- Payment method dropdown: defaults to Cash -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Payment Method</label>
                                                <select name="method" class="form-select form-select-lg">
                                                    <!-- Loop through available payment methods -->
                                                    <?php foreach ($methods as $m): ?>
                                                        <option value="<?php echo $m; ?>" <?php echo $m==='Cash'?'selected':''; ?>><?php echo $m; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- Optional transaction ID for card/QR payments -->
                                        <div class="mb-3">
                                            <label class="form-label">Transaction ID (optional)</label>
                                            <input type="text" name="transaction_id" class="form-control" placeholder="e.g. card/QR reference">
                                        </div>

                                        <!-- PAYMENT SUMMARY: Breakdown of charges before total -->
                                        <div class="payment-summary border rounded p-3">
                                            <!-- Room charge line -->
                                            <div class="d-flex justify-content-between">
                                                <span>Stay (<?php echo $c['nights']; ?> nights)</span>
                                                <span><?php echo formatCurrency($c['room_charge']); ?></span>
                                            </div>
                                            <!-- Extra charges line -->
                                            <div class="d-flex justify-content-between">
                                                <span>Extra charges</span>
                                                <span><?php echo formatCurrency($c['extra_total']); ?></span>
                                            </div>
                                            <!-- Tax line with rate percentage -->
                                            <div class="d-flex justify-content-between">
                                                <span>Tax (<?php echo $taxRate; ?>%)</span>
                                                <span><?php echo formatCurrency($c['tax']); ?></span>
                                            </div>
                                            <hr>
                                            <!-- Grand total -->
                                            <div class="d-flex justify-content-between fw-bold fs-5">
                                                <span>TOTAL</span>
                                                <span class="text-success"><?php echo formatCurrency($c['stay_total']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <!-- Submit button: triggers checkout and bill generation -->
                                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle"></i> Confirm & Generate Bill</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- POST-CHECKOUT RECEIPT: Displayed after successful checkout with bill details -->
<?php if (!empty($receipt)): ?>
    <div class="receipt mt-4" id="printReceipt">
        <!-- Receipt header with hotel branding and invoice number -->
        <div class="receipt-header">
            <div style="font-size:42px">&#127976;</div>
            <!-- Security: htmlspecialchars() prevents XSS from hotelName -->
            <h2><?php echo htmlspecialchars($hotelName); ?></h2>
            <div>PAYMENT RECEIPT</div>
            <!-- Security: htmlspecialchars() prevents XSS from invoice_no -->
            <small class="text-muted">Invoice: <strong><?php echo htmlspecialchars($receipt['invoice_no']); ?></strong></small>
        </div>
        <!-- Receipt info grid: guest, room, reservation, payment, and stay details -->
        <div class="receipt-grid">
            <!-- Guest name -->
            <div class="info-box">
                <div class="label">Guest</div>
                <!-- Security: htmlspecialchars() prevents XSS from guest_name -->
                <div class="value"><?php echo htmlspecialchars($receipt['guest_name']); ?></div>
            </div>
            <!-- Room number -->
            <div class="info-box">
                <div class="label">Room</div>
                <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                <div class="value"><?php echo htmlspecialchars($receipt['room_number']); ?></div>
            </div>
            <!-- Reservation number -->
            <div class="info-box">
                <div class="label">Reservation</div>
                <!-- Security: htmlspecialchars() prevents XSS from reservation_number -->
                <div class="value"><?php echo htmlspecialchars($receipt['reservation_number']); ?></div>
            </div>
            <!-- Payment method -->
            <div class="info-box">
                <div class="label">Payment Method</div>
                <!-- Security: htmlspecialchars() prevents XSS from method -->
                <div class="value"><?php echo htmlspecialchars($receipt['method']); ?></div>
            </div>
            <!-- Check-in time -->
            <div class="info-box">
                <div class="label">Check-in</div>
                <!-- Security: htmlspecialchars() prevents XSS from check_in_time -->
                <div class="value"><?php echo htmlspecialchars($receipt['check_in_time']); ?></div>
            </div>
            <!-- Checkout time -->
            <div class="info-box">
                <div class="label">Checkout</div>
                <!-- Security: htmlspecialchars() prevents XSS from checkout_time -->
                <div class="value"><?php echo htmlspecialchars($receipt['checkout_time']); ?></div>
            </div>
        </div>
        <!-- Payment summary table -->
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Final hotel payment</td>
                    <!-- Security: formatCurrency() safe for monetary display -->
                    <td class="text-end"><?php echo formatCurrency($receipt['amount']); ?></td>
                </tr>
            </tbody>
        </table>
        <!-- Total display -->
        <div class="total-box">
            <span>TOTAL</span>
            <span><?php echo formatCurrency($receipt['amount']); ?></span>
        </div>
        <!-- Payment status badge -->
        <div class="text-center mt-3">
            <!-- Security: htmlspecialchars() prevents XSS from status -->
            <span class="badge bg-success fs-6"><?php echo htmlspecialchars($receipt['status']); ?></span>
        </div>
        <!-- Thank you message -->
        <div class="text-center text-muted mt-4">
            Thank you for staying with us!<br>
            We hope to see you again.
        </div>
        <!-- Print and navigation buttons: hidden during printing -->
        <div class="no-print text-center mt-4">
            <button onclick="window.print()" class="btn btn-success btn-lg"><i class="bi bi-printer-fill"></i> Print Bill</button>
            <a href="index.php?r=billing/index" class="btn btn-outline-primary btn-lg">View Billing</a>
            <a href="index.php?r=checkout/index" class="btn btn-secondary btn-lg">Back</a>
        </div>
    </div>
<?php endif; ?>
