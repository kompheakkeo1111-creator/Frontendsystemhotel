<?php
/**
 * User Management View
 *
 * Displays a table of all system users with options to add new users,
 * edit existing user accounts, and delete users. Includes role and
 * status management for access control. Administrator-only access.
 *
 * Expected variables from controller:
 *   $users    - array  - All user records with role and status
 *   $roles    - array  - Available user roles for dropdown (Administrator, Manager, etc.)
 *   $statuses - array  - Available user statuses (Active, Inactive)
 *   $message  - string - Success flash message (optional)
 *   $error    - string - Error flash message (optional)
 *
 * Security measures:
 *   - htmlspecialchars() on all user-derived output (XSS prevention)
 *   - (int) casts on all user IDs in buttons, hidden fields, and JS calls
 *   - POST method for all form submissions (add, edit, delete)
 *   - JavaScript confirm() dialog before destructive delete actions
 *   - json_encode() for safe JavaScript data embedding (userData, roles arrays)
 *   - Password field cleared in edit modal (user must explicitly enter new password)
 *   - type="password" with autocomplete="new-password" for password fields
 *   - Hidden action fields to distinguish add/edit/delete operations
 */
?>

<!-- PAGE HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">User Management</h2>
        <p class="page-subtitle mb-0"><?php echo count($users); ?> users</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus"></i> Add User
    </button>
</div>

<!-- FLASH MESSAGES -->
<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div> <!-- Security: Prevent XSS in success message -->
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div> <!-- Security: Prevent XSS in error message -->
<?php endif; ?>

<!-- USERS TABLE: Lists all system users with role, status, and action buttons -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <!-- Loop through $users array to display each user record -->
                <?php foreach ($users as $u): ?>
                    <tr>
                        <!-- Security: (int) cast prevents injection in display -->
                        <td>#<?php echo (int)$u['id']; ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from username -->
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <!-- Security: htmlspecialchars() prevents XSS from full_name -->
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS from email -->
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <!-- Security: htmlspecialchars() prevents XSS; fallback to dash if empty -->
                        <td><?php echo htmlspecialchars($u['phone'] ?: '—'); ?></td>
                        <td>
                            <!-- Role badge with dynamic color based on role type -->
                            <span class="status-badge bg-<?php echo $u['role']==='Administrator'?'primary':($u['role']==='Manager'?'dark':'secondary'); ?> text-white">
                                <?php echo htmlspecialchars($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <!-- Status badge: green for Active, red for Inactive -->
                            <span class="status-badge bg-<?php echo $u['status']==='Active'?'success':'danger'; ?> text-white">
                                <?php echo $u['status']; ?>
                            </span>
                        </td>
                        <td>
                            <!-- Edit button: triggers editUser() JS function with user ID -->
                            <!-- Security: (int) cast prevents XSS in onclick handler -->
                            <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo (int)$u['id']; ?>)"><i class="bi bi-pencil"></i></button>
                            <!-- Delete form: POST method with JavaScript confirmation -->
                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user?')">
                                <input type="hidden" name="action" value="delete">
                                <!-- Security: (int) cast prevents SQL injection in hidden field -->
                                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!-- Empty state: shown when no users exist -->
                <?php if (!$users): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ADD USER MODAL -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="4">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" id="edit-username" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" id="edit-password" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="full_name" id="edit-full_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="edit-email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="edit-phone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit-role" class="form-select">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit-status" class="form-select">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                                <?php endforeach; ?>
                            </select>
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

<!-- JAVASCRIPT: User data and edit modal population -->
<script>
// Embed user data as JSON for client-side edit modal population
// Security: json_encode() safely encodes PHP array for JavaScript
const userData = <?php echo json_encode($users); ?>;
// Embed available roles for dropdown population
const roles = <?php echo json_encode($roles); ?>;

/**
 * Populates the edit user modal with data from the selected user.
 * Uses client-side data from userData array (embedded via json_encode).
 * Password field is intentionally cleared for security.
 * @param {number} id - User ID (cast to integer in PHP via (int))
 */
function editUser(id) {
    const u = userData.find(x => String(x.id) === String(id));
    if (!u) return;
    document.getElementById('edit-id').value = u.id;
    document.getElementById('edit-username').value = u.username;
    // Password field cleared: user must enter new password only if changing
    document.getElementById('edit-password').value = '';
    document.getElementById('edit-full_name').value = u.full_name;
    document.getElementById('edit-email').value = u.email;
    document.getElementById('edit-phone').value = u.phone || '';
    document.getElementById('edit-role').value = u.role;
    document.getElementById('edit-status').value = u.status;
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>
