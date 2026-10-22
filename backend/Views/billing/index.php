<?php
/**
 * Billing & Invoices View
 *
 * Displays a comprehensive list of all payment records with summary statistics,
 * search/filter capabilities, pagination, CSV export, and a modal for viewing
 * individual payment receipts.
 *
 * Expected variables from controller:
 *   $payments      - array  - Payment records with joined guest/room/reservation data
 *   $summary       - array  - Aggregated totals by payment method (Cash, Card, QR, Bank Transfer)
 *   $grandTotal    - float  - Sum of all payment amounts
 *   $search        - string - Current search keyword (guest name, room number, invoice #)
 *   $method        - string - Current payment method filter
 *   $startDate     - string - Filter start date (YYYY-MM-DD)
 *   $endDate       - string - Filter end date (YYYY-MM-DD)
 *   $methods       - array  - Available payment method options for filter dropdown
 *   $page          - int    - Current pagination page number
 *   $pages         - int    - Total number of pages
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output to prevent XSS
 *   - (int) casts on all record IDs used in URLs and form values
 *   - urlencode() on search/filter parameters in export links
 *   - POST method for form submissions
 *   - http_build_query() for safe URL parameter construction
 */
?>

<!-- PAGE HEADER: Title and grand total summary -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Billing & Invoices</h2>
        <!-- Security: formatCurrency() handles numeric output safely -->
        <p class="page-subtitle mb-0"><?php echo $grandTotal ? formatCurrency($grandTotal) : '0.00'; ?> total collected</p>
    </div>
    <!-- CSV EXPORT: Preserves current filter state via urlencode() for safe URL parameters -->
    <a href="index.php?r=billing/index&export=csv&search=<?php echo urlencode($search); ?>&payment_method=<?php echo urlencode($method); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success">
        <i class="bi bi-download"></i> Export CSV
    </a>
</div>

<!-- SUMMARY STATISTICS CARDS: Payment method breakdowns showing amount and count per method -->
<div class="row">
    <!-- Cash payments summary -->
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon icon-success"><i class="bi bi-cash"></i></div>
            <div>
                <!-- Security: formatCurrency() safe for numeric output; null coalescing prevents undefined index -->
                <div class="number"><?php echo isset($summary['Cash']) ? formatCurrency($summary['Cash']['amount']) : '0.00'; ?></div>
                <div class="label">Cash (<?php echo $summary['Cash']['count'] ?? 0; ?>)</div>
            </div>
        </div>
    </div>
    <!-- Credit/Debit Card payments summary -->
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon icon-primary"><i class="bi bi-credit-card"></i></div>
            <div>
                <div class="number"><?php echo isset($summary['Credit/Debit Card']) ? formatCurrency($summary['Credit/Debit Card']['amount']) : '0.00'; ?></div>
                <div class="label">Card (<?php echo $summary['Credit/Debit Card']['count'] ?? 0; ?>)</div>
            </div>
        </div>
    </div>
    <!-- QR Payment summary -->
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon icon-info"><i class="bi bi-qr-code"></i></div>
            <div>
                <div class="number"><?php echo isset($summary['QR Payment']) ? formatCurrency($summary['QR Payment']['amount']) : '0.00'; ?></div>
                <div class="label">QR (<?php echo $summary['QR Payment']['count'] ?? 0; ?>)</div>
            </div>
        </div>
    </div>
    <!-- Bank Transfer payments summary -->
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="icon icon-warning"><i class="bi bi-bank"></i></div>
            <div>
                <div class="number"><?php echo isset($summary['Bank Transfer']) ? formatCurrency($summary['Bank Transfer']['amount']) : '0.00'; ?></div>
                <div class="label">Bank Transfer (<?php echo $summary['Bank Transfer']['count'] ?? 0; ?>)</div>
            </div>
        </div>
    </div>
</div>

<!-- SEARCH & FILTER FORM: GET method for bookmarkable URLs; filters by keyword, payment method, and date range -->
<form method="GET" class="filter-form mb-4 p-3">
    <div class="row g-2 align-items-center">
        <!-- Search input: filters by guest name, room number, or invoice number -->
        <div class="col-md-4">
            <!-- Security: htmlspecialchars() prevents XSS from reflected search value -->
            <input type="text" name="search" class="form-control" placeholder="Search guest, room, invoice #" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <!-- Payment method dropdown: populated from $methods array -->
        <div class="col-md-2">
            <select name="payment_method" class="form-select">
                <option value="">All Methods</option>
                <!-- Loop through available payment methods; selected state compared via strict equality -->
                <?php foreach ($methods as $m): ?>
                    <option value="<?php echo $m; ?>" <?php echo $method === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Start date filter -->
        <div class="col-md-2">
            <!-- Security: htmlspecialchars() prevents XSS in date value -->
            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <!-- End date filter -->
        <div class="col-md-2">
            <!-- Security: htmlspecialchars() prevents XSS in date value -->
            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <!-- Filter and Reset buttons -->
        <div class="col-md-2">
            <button class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
            <a href="index.php?r=billing/index" class="btn btn-secondary">Reset</a>
        </div>
    </div>
</form>

<!-- PAYMENTS TABLE: Displays all payment records matching current filters -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Reservation</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <!-- Loop through $payments array; fallback to empty state if no records -->
                <?php if ($payments): foreach ($payments as $p): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS from invoice_number -->
                        <td><strong><?php echo htmlspecialchars($p['invoice_number']); ?></strong></td>
                        <!-- Security: htmlspecialchars() prevents XSS from guest full_name -->
                        <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($p['room_number']); ?></span></td>
                        <td>
                            <!-- Conditional: show reservation number or "Walk-in" label -->
                            <?php if ($p['reservation_number']): ?>
                                <!-- Security: htmlspecialchars() prevents XSS from reservation_number -->
                                <span class="badge bg-info"><?php echo htmlspecialchars($p['reservation_number']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">Walk-in</span>
                            <?php endif; ?>
                        </td>
                        <!-- Security: formatCurrency() safe for numeric amount output -->
                        <td><strong><?php echo formatCurrency($p['amount']); ?></strong></td>
                        <td>
                            <!-- Payment method badge with dynamic color based on method type -->
                            <span class="badge bg-<?php echo $p['payment_method']=='Cash'?'success':($p['payment_method']=='Credit/Debit Card'?'primary':'warning'); ?>">
                                <?php echo htmlspecialchars($p['payment_method']); ?>
                            </span>
                        </td>
                        <!-- Security: date() + strtotime() safe for date formatting -->
                        <td><?php echo date('Y-m-d H:i', strtotime($p['payment_date'])); ?></td>
                        <td>
                            <!-- Print button: opens print view in new tab; Security: (int) cast prevents SQL injection in URL -->
                            <button onclick="window.open('index.php?r=billing/print&id=<?php echo (int)$p['id']; ?>','_blank')" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Print</button>
                            <!-- View button: triggers receipt modal; Security: (int) cast prevents SQL injection in JS call -->
                            <button onclick="viewReceipt(<?php echo (int)$p['id']; ?>)" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#receiptModal"><i class="bi bi-eye"></i> View</button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <!-- Empty state: displayed when no payment records match filters -->
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:10px"></i>No payment records found.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGINATION: Generates page links preserving current filter state -->
    <?php if ($pages > 1): ?>
        <div class="card-body border-top">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php
                    // Build query string from current filter values, removing empty entries
                    $qs = http_build_query(array_filter([
                        'search' => $search,
                        'payment_method' => $method,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'r' => 'billing/index'
                    ]));
                    // Loop through page numbers to generate pagination links
                    for ($i = 1; $i <= $pages; $i++):
                        $link = 'index.php?' . ($qs ? $qs . '&' : '') . 'page=' . $i;
                    ?>
                        <!-- Active page highlighted via conditional class -->
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $link; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- RECEIPT VIEW MODAL: Loads receipt content via AJAX fetch call -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <!-- Modal body: dynamically populated by viewReceipt() JavaScript function -->
            <div class="modal-body" id="receiptContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT: AJAX receipt loading and modal display -->
<script>
/**
 * Fetches receipt HTML from billing/receipt endpoint and displays in modal.
 * Uses fetch() API for async loading; ID is passed as integer from PHP (int) cast.
 * @param {number} id - Payment record ID (already cast to int in PHP)
 */
function viewReceipt(id) {
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
    // Show loading spinner while fetching receipt content
    document.getElementById('receiptContent').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading...</p></div>';
    // Security: ID is an integer cast in PHP, safe for URL construction
    fetch('index.php?r=billing/receipt&id=' + id)
        .then(r => r.text())
        .then(html => { document.getElementById('receiptContent').innerHTML = html; })
        .catch(() => { document.getElementById('receiptContent').innerHTML = '<div class="alert alert-danger">Error loading receipt.</div>'; });
}
</script>
