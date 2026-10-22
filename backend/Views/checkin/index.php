<?php
/**
 * Check-in Management View
 *
 * Displays reservations ready for check-in and currently checked-in guests.
 * Includes a modal for walk-in check-in of guests without prior reservations.
 * Supports both reservation-based and walk-in check-in workflows.
 *
 * Expected variables from controller:
 *   $ready      - array  - Reservations with status ready for check-in
 *   $stays      - array  - Currently checked-in guest stays
 *   $guests     - array  - All guests for walk-in guest selection dropdown
 *   $availRooms - array  - Available rooms for walk-in room assignment
 *   $message    - string - Success flash message (optional)
 *   $error      - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on reservation_id and guest/room IDs in forms
 *   - POST method for all form submissions (check-in actions)
 *   - JavaScript confirm() dialog for destructive actions
 *   - Hidden action fields to distinguish form purposes
 */
?>

<!-- PAGE HEADER: Title and walk-in check-in button -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Check-in</h2>
        <p class="page-subtitle mb-0">Front desk arrivals</p>
    </div>
    <!-- Walk-in check-in button: opens modal for guests without reservations -->
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#walkinModal">
        <i class="bi bi-person-plus"></i> Walk-in Check-in
    </button>
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

<!-- RESERVATIONS READY FOR CHECK-IN: Table of guests arriving today with check-in action -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-calendar-check text-primary"></i> Reservations Ready for Check-in
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Reservation</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Arrival</th>
                        <th>Departure</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <!-- Loop through $ready array of reservations awaiting check-in -->
                <?php foreach ($ready as $r): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS from reservation_number -->
                        <td><strong><?php echo htmlspecialchars($r['reservation_number']); ?></strong></td>
                        <!-- Security: htmlspecialchars() prevents XSS from guest_name -->
                        <td><?php echo htmlspecialchars($r['guest_name']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($r['room_number']); ?></span></td>
                        <!-- Date fields: output directly as they come from controller (pre-formatted) -->
                        <td><?php echo $r['check_in_date']; ?></td>
                        <td><?php echo $r['check_out_date']; ?></td>
                        <!-- Status badge with info styling -->
                        <td><span class="badge bg-info"><?php echo $r['status']; ?></span></td>
                        <td class="text-end">
                            <!-- Check-in form: POST method with JavaScript confirmation dialog -->
                            <form method="POST" onsubmit="return confirm('Check in this guest?')">
                                <input type="hidden" name="action" value="checkin">
                                <!-- Security: (int) cast prevents SQL injection in hidden field -->
                                <input type="hidden" name="reservation_id" value="<?php echo (int)$r['id']; ?>">
                                <button class="btn btn-success btn-sm"><i class="bi bi-box-arrow-in-right"></i> Check In</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Empty state: shown when no reservations are ready for check-in -->
                <?php if (!$ready): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No reservations waiting for check-in.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- CURRENTLY CHECKED-IN GUESTS: Table showing all active stays with expected checkout dates -->
<div class="card mt-4">
    <div class="card-header">
        <!-- Header shows count of currently checked-in guests via count() -->
        <i class="bi bi-people text-success"></i> Currently Checked In (<?php echo count($stays); ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Checked In</th>
                        <th>Expected Out</th>
                    </tr>
                </thead>
                <tbody>
                <!-- Loop through $stays array of currently checked-in guests -->
                <?php foreach ($stays as $c): ?>
                    <tr>
                        <!-- Security: htmlspecialchars() prevents XSS from guest_name -->
                        <td><?php echo htmlspecialchars($c['guest_name']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from room_number -->
                        <td><span class="badge bg-primary"><?php echo htmlspecialchars($c['room_number']); ?></span></td>
                        <!-- Check-in timestamp displayed directly from controller -->
                        <td><?php echo $c['check_in_time']; ?></td>
                        <!-- Expected checkout date displayed directly from controller -->
                        <td><?php echo $c['expected_check_out']; ?></td>
                    </tr>
                <?php endforeach; ?>
                <!-- Empty state: shown when no guests are currently checked in -->
                <?php if (!$stays): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No guests currently checked in.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- WALK-IN CHECK-IN MODAL: Form for checking in guests without prior reservations -->
<div class="modal fade" id="walkinModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Walk-in form: POST method with action="walkin" to distinguish from reservation check-in -->
            <form method="POST">
                <input type="hidden" name="action" value="walkin">
                <div class="modal-header">
                    <h5 class="modal-title">Walk-in Check-in</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Guest selection dropdown: populated from $guests array -->
                    <div class="mb-3">
                        <label class="form-label">Guest</label>
                        <select name="guest_id" class="form-select" required>
                            <option value="">Select Existing Guest</option>
                            <!-- Loop through guests; value is guest ID, display is full_name -->
                            <?php foreach ($guests as $g): ?>
                                <!-- Security: htmlspecialchars() prevents XSS on guest full_name -->
                                <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Need a new guest? <a href="index.php?r=guests/index">Add one in Guests</a> first.</small>
                    </div>
                    <!-- Room selection and guest count row -->
                    <div class="row">
                        <!-- Available rooms dropdown: shows room number and nightly rate -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Room</label>
                            <select name="room_id" class="form-select" required>
                                <option value="">Available Rooms</option>
                                <!-- Loop through available rooms with price display -->
                                <?php foreach ($availRooms as $r): ?>
                                    <!-- Security: htmlspecialchars() prevents XSS on room_number -->
                                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['room_number']); ?> — <?php echo formatCurrency($r['price_per_night']); ?>/night</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Number of guests input (defaults to 1) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Guests</label>
                            <input type="number" name="number_of_guests" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <!-- Check-in and check-out date row -->
                    <div class="row">
                        <!-- Arrival date: defaults to today's date -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arrival</label>
                            <input type="date" name="check_in_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <!-- Departure date: required field -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departure</label>
                            <input type="date" name="check_out_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Submit button: triggers walk-in check-in action -->
                    <button class="btn btn-success" type="submit"><i class="bi bi-box-arrow-in-right"></i> Check In</button>
                </div>
            </form>
        </div>
    </div>
</div>
