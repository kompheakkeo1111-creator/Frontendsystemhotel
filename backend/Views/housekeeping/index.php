<?php
/**
 * Housekeeping Management View
 *
 * Displays rooms requiring cleaning or maintenance attention. Allows housekeeping
 * staff to mark rooms as clean and available after servicing. Shows a filtered
 * list of rooms with Cleaning or Maintenance status only.
 *
 * Expected variables from controller:
 *   $rooms           - array  - Rooms with Cleaning or Maintenance status
 *   $cleaningCount   - int    - Number of rooms needing cleaning
 *   $maintenanceCount - int   - Number of rooms in maintenance status
 *   $message         - string - Success flash message (optional)
 *   $error           - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on room IDs in hidden form fields
 *   - POST method for mark_clean action
 *   - JavaScript confirm() dialog before marking room as clean
 *   - Hidden action field to identify the mark_clean operation
 */
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Housekeeping</h2>
        <p class="page-subtitle mb-0"><?php echo $cleaningCount; ?> rooms to clean · <?php echo $maintenanceCount; ?> in maintenance</p>
    </div>
</div>

<!-- FLASH MESSAGES -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div> <!-- Security: Prevent XSS in success message -->
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div> <!-- Security: Prevent XSS in error message -->
<?php endif; ?>

<!-- ROOMS TABLE -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Room #</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($r['room_number']); ?></strong></td> <!-- Security: Prevent XSS -->
                        <td><?php echo htmlspecialchars($r['type_name'] ?? '—'); ?></td> <!-- Security: Prevent XSS -->
                        <td>
                            <span class="badge bg-<?php echo $r['status']==='Cleaning'?'info':'secondary'; ?>">
                                <?php echo htmlspecialchars($r['status']); ?> <!-- Security: Prevent XSS -->
                            </span>
                        </td>
                        <td class="text-end">
                            <form method="POST" style="display:inline" onsubmit="return confirm('Mark room as cleaned and available?')">
                                <input type="hidden" name="action" value="mark_clean">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"> <!-- Security: Cast ID to integer -->
                                <button class="btn btn-sm btn-success">Mark Clean / Available</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rooms): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No rooms need attention right now.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
