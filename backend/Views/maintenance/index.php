<?php
/**
 * Maintenance Management View
 *
 * Displays all rooms with their current status and maintenance controls.
 * Allows staff to set rooms into maintenance mode or mark them as available
 * after maintenance work is completed. Occupied rooms cannot be set to maintenance.
 *
 * Expected variables from controller:
 *   $rooms   - array  - All rooms with status, type name, and ID
 *   $message - string - Success flash message (optional)
 *   $error   - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on room IDs in hidden form fields
 *   - POST method for set_maintenance and clear_maintenance actions
 *   - JavaScript confirm() dialog before setting maintenance mode
 *   - Disabled button for occupied rooms (prevents invalid state transitions)
 *   - Hidden action fields to distinguish set/clear maintenance operations
 */
?>

<!-- PAGE HEADER: Title with total room count -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Maintenance</h2>
        <!-- count() shows total rooms in the system -->
        <p class="page-subtitle mb-0"><?php echo count($rooms); ?> rooms</p>
    </div>
</div>

<!-- FLASH MESSAGES: Display success/error messages from controller actions -->
<?php if (!empty($message)): ?>
    <!-- Security: htmlspecialchars() prevents XSS in success flash message -->
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <!-- Security: htmlspecialchars() prevents XSS in error flash message -->
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- ROOMS TABLE: Lists all rooms with maintenance controls -->
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
                <!-- Loop through all rooms to display status and actions -->
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                        <td><strong><?php echo htmlspecialchars($r['room_number']); ?></strong></td>
                        <!-- Security: htmlspecialchars() prevents XSS from type_name; null coalescing handles missing type -->
                        <td><?php echo htmlspecialchars($r['type_name'] ?? '—'); ?></td>
                        <td>
                            <!-- Status badge: dynamic color based on room status -->
                            <span class="badge bg-<?php echo $r['status']==='Maintenance'?'danger':($r['status']==='Available'?'success':'secondary'); ?>">
                                <?php echo htmlspecialchars($r['status']); ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <!-- Conditional: different actions based on current room status -->
                            <?php if ($r['status'] === 'Maintenance'): ?>
                                <!-- Clear maintenance: Mark room as available after work is done -->
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="clear_maintenance">
                                    <!-- Security: (int) cast prevents SQL injection in hidden field -->
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                    <button class="btn btn-sm btn-success">Mark Available</button>
                                </form>
                            <?php else: ?>
                                <!-- Set maintenance: Put room into maintenance mode -->
                                <form method="POST" style="display:inline" onsubmit="return confirm('Put this room under maintenance?')">
                                    <input type="hidden" name="action" value="set_maintenance">
                                    <!-- Security: (int) cast prevents SQL injection in hidden field -->
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                    <!-- Disabled button for occupied rooms with explanatory tooltip -->
                                    <button class="btn btn-sm btn-warning" <?php echo $r['status']==='Occupied'?'disabled title="Occupied rooms cannot be set to maintenance"':''; ?>>Set Maintenance</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Empty state: shown when no rooms exist in the system -->
                <?php if (!$rooms): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No rooms found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
