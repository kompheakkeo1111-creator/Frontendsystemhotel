<?php
/**
 * Room Types Management View
 *
 * Displays a table of all room type categories (e.g., Single, Double, Suite)
 * with options to add new types, edit existing records, and delete types.
 * Each room type includes name, description, price per night, capacity,
 * and count of rooms using that type.
 *
 * Expected variables from controller:
 *   $roomTypes - array  - All room type records with room counts
 *   $message   - string - Success flash message (optional)
 *   $error     - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on all room type IDs in buttons, hidden fields, and JS calls
 *   - (int) casts on capacity and room_count for safe numeric display
 *   - POST method for delete form submissions
 *   - JavaScript confirm() dialog before destructive delete actions
 *   - json_encode() for safe JavaScript data embedding (typeData array)
 *   - Hidden action fields to distinguish add/edit/delete operations
 */
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Room Types</h2>
        <p class="page-subtitle mb-0"><?php echo count($roomTypes); ?> room categories</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTypeModal">
        <i class="bi bi-plus-circle"></i> Add Room Type
    </button>
</div>

<!-- FLASH MESSAGES -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div> <!-- Security: Prevent XSS in success message -->
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div> <!-- Security: Prevent XSS in error message -->
<?php endif; ?>

<!-- ROOM TYPES TABLE -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type Name</th>
                        <th>Description</th>
                        <th class="text-end">Price/Night</th>
                        <th class="text-center">Capacity</th>
                        <th class="text-center">Rooms</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($roomTypes as $t): ?>
                    <tr>
                        <td><?php echo (int)$t['id']; ?></td> <!-- Security: Cast to integer -->
                        <td><strong><?php echo htmlspecialchars($t['type_name']); ?></strong></td> <!-- Security: Prevent XSS -->
                        <td><?php echo htmlspecialchars($t['description'] ?? '—'); ?></td> <!-- Security: Prevent XSS -->
                        <td class="text-end"><?php echo formatCurrency($t['price_per_night']); ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?php echo (int)$t['capacity']; ?></span></td> <!-- Security: Cast to integer -->
                        <td class="text-center"><?php echo (int)$t['room_count']; ?></td> <!-- Security: Cast to integer -->
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick="editType(<?php echo (int)$t['id']; ?>)"><i class="bi bi-pencil"></i></button> <!-- Security: Cast ID to integer -->
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this room type?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$t['id']; ?>"> <!-- Security: Cast ID to integer -->
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$roomTypes): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No room types found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD ROOM TYPE MODAL -->
<div class="modal fade" id="addTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Room Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night</label>
                            <input type="number" name="price_per_night" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Add Room Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT ROOM TYPE MODAL -->
<div class="modal fade" id="editTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Room Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="type_name" id="edit-name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit-desc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price per Night</label>
                            <input type="number" name="price_per_night" id="edit-price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" id="edit-capacity" class="form-control" min="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JAVASCRIPT -->
<script>
const typeData = <?php echo json_encode($roomTypes); ?>;

function editType(id) {
    const t = typeData.find(x => String(x.id) === String(id));
    if (!t) return;
    document.getElementById('edit-id').value = t.id;
    document.getElementById('edit-name').value = t.type_name;
    document.getElementById('edit-desc').value = t.description || '';
    document.getElementById('edit-price').value = t.price_per_night;
    document.getElementById('edit-capacity').value = t.capacity;
    new bootstrap.Modal(document.getElementById('editTypeModal')).show();
}
</script>
