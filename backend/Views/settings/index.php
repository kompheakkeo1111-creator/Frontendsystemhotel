<?php
/**
 * Settings View
 *
 * Displays and manages hotel configuration including property details
 * (name, address, phone, email), billing settings (currency, tax rate),
 * and database backup/restore functionality. Administrator-only access.
 *
 * Expected variables from controller:
 *   $settings - array  - Current hotel configuration values from database
 *   $backups  - array  - Available database backup files (name, size, date)
 *   $message  - string - Success flash message (optional)
 *   $error    - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all settings values in form fields (XSS prevention)
 *   - htmlspecialchars() on flash messages for safe display
 *   - htmlspecialchars() on backup filenames in hidden fields and display
 *   - POST method for all form submissions (save, backup, restore, delete_backup)
 *   - JavaScript confirm() dialog before destructive backup delete and restore
 *   - File upload validation: accept=".sql" restricts file type
 *   - enctype="multipart/form-data" for file upload support
 *   - Restore action uses double confirmation (modal + confirm dialog)
 */
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Settings</h2>
        <p class="page-subtitle mb-0">Hotel & property configuration</p>
    </div>
</div>

<!-- FLASH MESSAGES -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div> <!-- Security: Prevent XSS in success message -->
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div> <!-- Security: Prevent XSS in error message -->
<?php endif; ?>

<div class="row">
    <!-- HOTEL INFORMATION & BILLING SETTINGS -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-building"></i> Hotel Information
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <div class="mb-3">
                        <label class="form-label">Hotel Name</label>
                        <input type="text" name="hotel_name" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_name'] ?? ''); ?>" required> <!-- Security: Prevent XSS -->
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <input type="text" name="hotel_address" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_address'] ?? ''); ?>"> <!-- Security: Prevent XSS -->
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="hotel_phone" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_phone'] ?? ''); ?>"> <!-- Security: Prevent XSS -->
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="hotel_email" class="form-control" value="<?php echo htmlspecialchars($settings['hotel_email'] ?? ''); ?>"> <!-- Security: Prevent XSS -->
                        </div>
                    </div>
                    <h6 class="text-muted mb-3">Billing</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Currency Code</label>
                            <input type="text" name="currency" class="form-control" value="<?php echo htmlspecialchars($settings['currency'] ?? 'USD'); ?>" maxlength="10" required> <!-- Security: Prevent XSS -->
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax Rate (%)</label>
                            <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($settings['tax_rate'] ?? '10'); ?>"> <!-- Security: Prevent XSS -->
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- DATABASE BACKUP & RESTORE -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-shield-check"></i> Database Backup
            </div>
            <div class="card-body">
                <!-- BACKUP / RESTORE ACTIONS -->
                <div class="d-flex gap-2 mb-3">
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="backup">
                        <button type="submit" class="btn btn-success"><i class="bi bi-download"></i> Backup Now</button>
                    </form>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#restoreModal">
                        <i class="bi bi-upload"></i> Restore
                    </button>
                </div>
                <p class="text-muted small mb-3">Create a backup of your database before making changes.</p>

                <!-- AVAILABLE BACKUPS TABLE -->
                <?php if (!empty($backups)): ?>
                    <h6>Available Backups</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Size</th>
                                    <th>Date</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td><small><?php echo htmlspecialchars($b['name']); ?></small></td> <!-- Security: Prevent XSS -->
                                    <td><?php echo $b['size']; ?> KB</td>
                                    <td><?php echo $b['date']; ?></td>
                                    <td>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this backup?')">
                                            <input type="hidden" name="action" value="delete_backup">
                                            <input type="hidden" name="file" value="<?php echo htmlspecialchars($b['name']); ?>"> <!-- Security: Prevent XSS -->
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No backups yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- RESTORE DATABASE MODAL -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="restore">
                <div class="modal-header">
                    <h5 class="modal-title">Restore Database</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> This will overwrite your current database!
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Backup File (.sql)</label>
                        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('This will overwrite the current database. Continue?')">
                        <i class="bi bi-upload"></i> Restore
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
