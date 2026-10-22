<?php
/**
 * Layout Footer View
 *
 * Closes the main content area opened in header.php and includes all
 * JavaScript utilities used across the application. Loaded at the bottom
 * of every page to ensure DOM is available before script execution.
 *
 * Expected variables: None (layout partial)
 *
 * JavaScript functions provided:
 *   - toggleSidebar() / closeSidebar() - Mobile sidebar navigation
 *   - Auto-dismiss alerts after 5 seconds
 *   - Data confirmation dialogs for delete actions
 *   - Table row click highlighting
 *   - printReceipt() - Browser print dialog trigger
 *   - exportToCSV() - Client-side HTML table to CSV export
 *   - formatCurrency() / formatDate() / formatDateTime() - Display formatting
 *
 * Security measures:
 *   - Bootstrap JS CDN loaded for consistent component behavior
 *   - Event delegation patterns for dynamically added elements
 *   - CSV export escapes double quotes to prevent injection
 */
?>
        </div><!-- /.main: Close main content column opened in header.php -->
    </div><!-- /.row: Close Bootstrap row opened in header.php -->
</div><!-- /.container-fluid: Close Bootstrap container opened in header.php -->

<!-- Bootstrap JS: Core JavaScript bundle including Popper.js for tooltips/popovers -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ==================== SIDEBAR FUNCTIONS: Mobile responsive navigation ====================

/**
 * Toggles sidebar visibility on mobile devices.
 * Adds/removes 'active' class to sidebar and overlay elements.
 * Locks body scroll when sidebar is open to prevent background scrolling.
 */
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
    // Lock body scroll when sidebar is open
    document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
}

/**
 * Closes the sidebar and overlay on mobile devices.
 * Restores body scroll to normal behavior.
 */
function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

// Close sidebar when clicking outside on mobile (improves UX)
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('mobileToggle');
    // Only close if click is outside sidebar and toggle button
    if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        closeSidebar();
    }
});

// Auto-close sidebar when window resizes to desktop breakpoint
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});

// Close sidebar on Escape key press (accessibility feature)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
    }
});

// ==================== UI UTILITIES: Common interface behaviors ====================

// Auto-hide dismissible alerts after 5 seconds with fade animation
document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
    setTimeout(function() {
        alert.classList.add('fade'); // Bootstrap fade class
        setTimeout(function() {
            alert.remove(); // Remove from DOM after fade completes
        }, 150);
    }, 5000);
});

// Confirm delete actions: Elements with data-confirm attribute get confirmation dialogs
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault(); // Cancel action if user declines
        }
    });
});

// Table row click highlight: Toggles active state on row click for visual feedback
document.querySelectorAll('.table tbody tr').forEach(function(row) {
    row.addEventListener('click', function() {
        this.classList.toggle('table-active');
    });
});

// ==================== PRINT FUNCTION ====================

/**
 * Triggers the browser's native print dialog.
 * Used for printing receipts and bills.
 */
function printReceipt() {
    window.print();
}

// ==================== CSV EXPORT ====================

/**
 * Exports an HTML table to a CSV file (client-side).
 * Reads table content, escapes quotes, and creates a downloadable file.
 * @param {string} tableId - DOM ID of the table element to export
 * @param {string} filename - Desired filename for the downloaded CSV
 */
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Build CSV content from table rows
    let csv = [];
    const rows = table.querySelectorAll('tr');

    rows.forEach(function(row) {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(function(col) {
            // Escape double quotes by doubling them, then wrap field in quotes
            rowData.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    // Create downloadable file using Blob and dynamic link
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.click(); // Trigger download
}

// ==================== FORMATTING: Display helper functions ====================

/**
 * Formats a numeric amount as currency string.
 * @param {number} amount - Numeric value to format
 * @param {string} currency - Currency code (default: 'USD')
 * @returns {string} Formatted currency string (e.g., "USD 123.45")
 */
function formatCurrency(amount, currency) {
    currency = currency || 'USD';
    return currency + ' ' + parseFloat(amount).toFixed(2);
}

/**
 * Formats a date string to localized short date format.
 * @param {string} dateStr - ISO date string
 * @returns {string} Formatted date (e.g., "Jan 15, 2024") or "-" if empty
 */
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Formats a datetime string to localized date and time format.
 * @param {string} dateStr - ISO datetime string
 * @returns {string} Formatted datetime (e.g., "Jan 15, 2024, 02:30 PM") or "-" if empty
 */
function formatDateTime(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>
</body>
</html>
