<?php
/**
 * Notifications View
 *
 * Displays hotel activity notifications that require attention, such as
 * overdue check-outs, upcoming arrivals, low room availability, etc.
 * Shows an "All Clear" state with a check icon when no notifications are pending.
 * Each notification has a type-based color (danger, warning, success) and a
 * link to the relevant page for action.
 *
 * Expected variables from controller:
 *   $notifications - array  - Notification items with type, icon, title, text, link
 *   $todayRevenue  - float  - Today's collected revenue for header badge display
 *
 * Notification item structure:
 *   $n['type']  - string - Color category: 'danger', 'warning', or 'success'
 *   $n['icon']  - string - Bootstrap icon class (e.g., 'bi-exclamation-triangle')
 *   $n['title'] - string - Notification headline
 *   $n['text']  - string - Notification description/details
 *   $n['link']  - string - URL to navigate for action
 *
 * Security measures:
 *   - htmlspecialchars() NOT applied to notification content (trusted from controller)
 *   - formatCurrency() for safe monetary display
 *   - Note: Notification content should be sanitized by the controller before passing
 */
?>

<!-- PAGE HEADER: Title with today's revenue badge -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Notifications</h2>
        <p class="page-subtitle mb-0">Hotel activities that need attention</p>
    </div>
    <!-- Today's revenue badge: formatCurrency() ensures safe monetary display -->
    <span class="badge bg-success fs-6">Today Revenue: <?php echo formatCurrency($todayRevenue); ?></span>
</div>

<!-- NOTIFICATION LIST: Conditionally renders notifications or all-clear state -->
<?php if (!$notifications): ?>
    <!-- ALL CLEAR STATE: Shown when no notifications are pending -->
    <div class="card p-5 text-center">
        <i class="bi bi-check-circle text-success" style="font-size:50px"></i>
        <h4 class="mt-3">All Clear</h4>
        <p class="text-muted mb-0">There are no urgent notifications right now.</p>
    </div>
<?php else: ?>
    <!-- NOTIFICATION ITEMS: List of active notifications with type-based styling -->
    <div class="notification-list">
    <!-- Loop through $notifications array to display each notification -->
    <?php foreach ($notifications as $n): ?>
        <!-- Notification card: left border color matches notification type (danger/warning/success) -->
        <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-white rounded border-start border-4 border-<?php echo $n['type']=='danger'?'danger':($n['type']=='warning'?'warning':'success'); ?> shadow-sm">
            <!-- Notification icon: Bootstrap icon class from controller -->
            <div class="n-icon">
                <i class="bi <?php echo $n['icon']; ?>"></i>
            </div>
            <!-- Notification content: title and descriptive text -->
            <div class="flex-grow-1">
                <h6 class="mb-1"><?php echo $n['title']; ?></h6>
                <div class="text-muted small"><?php echo $n['text']; ?></div>
            </div>
            <!-- Action link: navigates to relevant page for handling the notification -->
            <a class="btn btn-outline-dark btn-sm" href="<?php echo $n['link']; ?>">View</a>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
