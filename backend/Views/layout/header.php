<?php
/**
 * Layout Header View
 *
 * Main layout file that opens the HTML document, loads CSS/JS dependencies,
 * renders the sidebar navigation with role-based menu items, and opens the
 * main content area. Included at the top of every authenticated page.
 *
 * Expected variables (set by Controller::view()):
 *   $active    - string - Active nav item key (e.g., 'dashboard', 'guests')
 *   $pageTitle - string - Page title suffix for browser tab (optional)
 *
 * Navigation is role-controlled using hasRole() helper:
 *   - Administrator: Full access to all modules
 *   - Manager: Access to most operational modules
 *   - Receptionist: Guest-facing operations (guests, reservations, check-in/out)
 *   - Accountant: Billing and reports
 *   - Housekeeping Staff: Room maintenance and housekeeping
 *
 * Security measures:
 *   - htmlspecialchars() on $pageTitle for XSS prevention in <title> tag
 *   - htmlspecialchars() on session user_name and user_role for sidebar display
 *   - hasRole() function controls navigation visibility per user role
 *   - Logout uses modal confirmation before redirect to logout endpoint
 *   - POST-based logout recommended (currently GET for simplicity)
 */

// Set defaults
if (empty($active)) $active = '';
$pageTitle = isset($pageTitle) ? $pageTitle : APP_NAME;

/**
 * Helper: Generate navigation item HTML with active state highlighting
 * @param string $key   - nav item key (matches $active for highlighting)
 * @param string $href  - link URL destination
 * @param string $icon  - Bootstrap icon class name
 * @param string $label - Display text for navigation link
 */
function navItem($key, $href, $icon, $label) {
    global $active;
    // Compare current active nav item with this item's key for active state
    $cls = ($active === $key) ? ' active' : '';
    return "<a class=\"nav-link$cls\" href=\"$href\"><i class=\"bi $icon\"></i> $label</a>\n";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Hotel Management</title> <!-- Security: htmlspecialchars() prevents XSS in page title -->

    <!-- Bootstrap CSS: Core framework styles -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons: Icon font for UI elements -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- Custom CSS: Application-specific styles (layout, components, themes) -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-toggle" id="mobileToggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Sidebar Overlay (for mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="container-fluid">
    <div class="row">

        <!-- ==================== SIDEBAR: Navigation panel with role-based menu items ==================== -->
        <div class="col-md-2 sidebar" id="sidebar">
            <!-- Brand Logo: Hotel logo with wordmark, links to dashboard home -->
            <a class="brand" href="index.php?r=dashboard/index">
                <img class="brand-logo" src="assets/img/logo-full.png" alt="Demo Hotel">
            </a>

            <!-- User Info Box: Displays logged-in user name and role from session -->
            <div class="user-box">
                <!-- Security: htmlspecialchars() prevents XSS from session user_name -->
                <small>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></small><br>
                <!-- Security: htmlspecialchars() prevents XSS from session user_role -->
                <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_role'] ?? ''); ?></small>
            </div>

            <!-- Navigation Menu: Role-controlled links using hasRole() helper -->
            <nav class="nav flex-column">
                <!-- Dashboard: Visible to all authenticated users -->
                <?php echo navItem('dashboard', 'index.php?r=dashboard/index', 'bi-speedometer2', 'Dashboard'); ?>

                <!-- Users: Administrator role only - user account management -->
                <?php if (hasRole(['Administrator'])): ?>
                    <?php echo navItem('users', 'index.php?r=users/index', 'bi-people', 'Users'); ?>
                <?php endif; ?>

                <!-- Room Types: Admin, Receptionist, Manager - room category management -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager'])): ?>
                    <?php echo navItem('room_types', 'index.php?r=room_types/index', 'bi-grid', 'Room Types'); ?>
                <?php endif; ?>

                <!-- Rooms: Admin, Receptionist, Manager, Housekeeping - room inventory -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager', 'Housekeeping Staff'])): ?>
                    <?php echo navItem('rooms', 'index.php?r=rooms/index', 'bi-door-open', 'Rooms'); ?>
                <?php endif; ?>

                <!-- Guests: Admin, Receptionist, Manager - guest record management -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager'])): ?>
                    <?php echo navItem('guests', 'index.php?r=guests/index', 'bi-person', 'Guests'); ?>
                <?php endif; ?>

                <!-- Reservations: Admin, Receptionist, Manager - booking management -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager'])): ?>
                    <?php echo navItem('reservations', 'index.php?r=reservations/index', 'bi-calendar-check', 'Reservations'); ?>
                <?php endif; ?>

                <!-- Check-in: Admin, Receptionist, Manager - guest arrival processing -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager'])): ?>
                    <?php echo navItem('checkin', 'index.php?r=checkin/index', 'bi-box-arrow-in-right', 'Check-in'); ?>
                <?php endif; ?>

                <!-- Check-out: Admin, Receptionist, Manager - guest departure and payment -->
                <?php if (hasRole(['Administrator', 'Receptionist', 'Manager'])): ?>
                    <?php echo navItem('checkout', 'index.php?r=checkout/index', 'bi-box-arrow-right', 'Check-out'); ?>
                <?php endif; ?>

                <!-- Billing: Admin, Accountant, Manager - payment records and invoices -->
                <?php if (hasRole(['Administrator', 'Accountant', 'Manager'])): ?>
                    <?php echo navItem('billing', 'index.php?r=billing/index', 'bi-receipt', 'Billing'); ?>
                <?php endif; ?>

                <!-- Notifications: Visible to all users - system alerts and activity -->
                <?php echo navItem('notifications', 'index.php?r=notifications/index', 'bi-bell', 'Notifications'); ?>

                <!-- Housekeeping: Admin, Housekeeping Staff - room cleaning status -->
                <?php if (hasRole(['Administrator', 'Housekeeping Staff'])): ?>
                    <?php echo navItem('housekeeping', 'index.php?r=housekeeping/index', 'bi-broom', 'Housekeeping'); ?>
                <?php endif; ?>

                <!-- Maintenance: Admin, Housekeeping, Manager - room maintenance mode -->
                <?php if (hasRole(['Administrator', 'Housekeeping Staff', 'Manager'])): ?>
                    <?php echo navItem('maintenance', 'index.php?r=maintenance/index', 'bi-wrench', 'Maintenance'); ?>
                <?php endif; ?>

                <!-- Reports: Admin, Manager, Accountant - analytics and performance data -->
                <?php if (hasRole(['Administrator', 'Manager', 'Accountant'])): ?>
                    <?php echo navItem('reports', 'index.php?r=reports/index', 'bi-file-text', 'Reports'); ?>
                <?php endif; ?>

                <!-- Settings: Administrator only - system configuration -->
                <?php if (hasRole(['Administrator'])): ?>
                    <?php echo navItem('settings', 'index.php?r=settings/index', 'bi-gear', 'Settings'); ?>
                <?php endif; ?>

                <!-- Logout: Triggers confirmation modal before logout redirect -->
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </div>

        <!-- ==================== MAIN CONTENT: Page-specific content rendered here ==================== -->
        <div class="col-md-10 main" id="mainContent">

<!-- Logout Confirmation Modal: Prevents accidental logout with confirmation dialog -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <!-- Logout link: redirects to logout controller endpoint -->
                <a href="index.php?r=logout/index" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar JavaScript: Handles responsive sidebar toggle on small screens -->
<script>
    /**
     * Toggles sidebar visibility on mobile devices.
     * Adds/removes 'active' class to sidebar and overlay elements.
     * Prevents body scroll when sidebar is open.
     */
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        // Lock body scroll when sidebar is open to prevent background scrolling
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }

    /**
     * Closes the sidebar and overlay on mobile devices.
     * Restores body scroll to normal behavior.
     */
    function closeSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Auto-close sidebar when window resizes to desktop breakpoint
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            closeSidebar();
        }
    });

    // Close sidebar when any nav link is clicked on mobile (improves UX)
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });
</script>
