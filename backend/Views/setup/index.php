<?php
/**
 * Initial Setup View - First-Time Database Installation
 *
 * Standalone page for first-time system setup. Creates all required database
 * tables and a default administrator account. Only accessible when the system
 * has not been initialized yet (checked by controller). Uses its own HTML
 * structure without the main application layout.
 *
 * Expected variables from controller:
 *   $message - string - Setup status message (success or error)
 *   $success - bool   - Whether setup completed successfully
 *
 * Database constants used (from config):
 *   DB_HOST - Database server hostname
 *   DB_NAME - Database name
 *   DB_USER - Database username
 *
 * Security measures:
 *   - htmlspecialchars() on all output to prevent XSS
 *   - Database credentials displayed in disabled fields (read-only, not editable)
 *   - POST method for installation form submission
 *   - Setup only accessible when system is not initialized (controller-enforced)
 *   - Default admin account created with known credentials (should be changed)
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .setup-card { max-width: 500px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="setup-card">
            <div class="card shadow-lg">
                <div class="card-body p-5">

                    <!-- SETUP HEADER: System branding and title -->
                    <div class="text-center mb-4">
                        <i class="fas fa-hotel fa-3x text-primary mb-3"></i>
                        <h2>Hotel Management System</h2>
                        <p class="text-muted">First-time Setup</p>
                    </div>

                    <!-- FLASH MESSAGE: Success or error message from setup process -->
                    <?php if ($message): ?>
                        <!-- Dynamic alert class based on $success boolean -->
                        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
                            <!-- Security: htmlspecialchars() prevents XSS from setup message -->
                            <?php echo htmlspecialchars($message); ?>
                            <!-- Show login link on successful setup -->
                            <?php if ($success): ?>
                                <br><a href="index.php?r=login/index" class="alert-link">Go to Login &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- DATABASE INSTALLATION FORM: Only shown when setup hasn't completed -->
                    <?php if (!$success): ?>
                        <form method="POST">
                            <!-- Database host: read-only display from config constant -->
                            <div class="mb-3">
                                <label class="form-label">Database Host</label>
                                <!-- Security: htmlspecialchars() prevents XSS; disabled prevents editing -->
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(DB_HOST); ?>" disabled>
                            </div>
                            <!-- Database name: read-only display from config constant -->
                            <div class="mb-3">
                                <label class="form-label">Database Name</label>
                                <!-- Security: htmlspecialchars() prevents XSS; disabled prevents editing -->
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(DB_NAME); ?>" disabled>
                            </div>
                            <!-- Database user: read-only display from config constant -->
                            <div class="mb-3">
                                <label class="form-label">Database User</label>
                                <!-- Security: htmlspecialchars() prevents XSS; disabled prevents editing -->
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(DB_USER); ?>" disabled>
                            </div>
                            <p class="text-muted small">This will create all tables and a default admin account.</p>
                            <!-- Install button: triggers POST to setup controller -->
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="fas fa-download me-2"></i>Install Database
                            </button>
                        </form>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
