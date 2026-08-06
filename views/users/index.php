<?php $pageTitle = 'User Management'; ?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-person-plus-fill"></i> Add User</button>
</div>

<div class="panel-card">
    <div class="table-responsive">
        <table class="table table-clean align-middle">
            <thead><tr><th>#</th><th>Full Name</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
                <tr data-id="<?= $u['id'] ?>">
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><span class="badge-status status-role"><?= $u['role'] ?></span></td>
                    <td><span class="badge-status status-<?= strtolower($u['status']) ?>"><?= $u['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-brand edit-user-btn"
                            data-id="<?= $u['id'] ?>" data-name="<?= htmlspecialchars($u['full_name']) ?>"
                            data-username="<?= htmlspecialchars($u['username']) ?>" data-role="<?= $u['role'] ?>" data-status="<?= $u['status'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary reset-pw-btn" data-id="<?= $u['id'] ?>"><i class="bi bi-key"></i></button>
                        <button class="btn btn-sm btn-outline-danger toggle-user-btn" data-id="<?= $u['id'] ?>"><?= $u['status']==='Active' ? 'Deactivate' : 'Activate' ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" id="newFullName"></div>
        <div class="mb-3"><label class="form-label">Username *</label><input type="text" class="form-control" id="newUsername"></div>
        <div class="mb-3"><label class="form-label">Password *</label><input type="password" class="form-control" id="newPassword"></div>
        <div class="mb-3"><label class="form-label">Role *</label>
            <select class="form-select" id="newRole"><option value="Operator">Operator</option><option value="Administrator">Administrator</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="saveUserBtn">Create User</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit User</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="editUserId">
        <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" id="editFullName"></div>
        <div class="mb-3"><label class="form-label">Username *</label><input type="text" class="form-control" id="editUsername"></div>
        <div class="mb-3"><label class="form-label">Role *</label>
            <select class="form-select" id="editRole"><option value="Operator">Operator</option><option value="Administrator">Administrator</option></select>
        </div>
        <div class="mb-3"><label class="form-label">Status</label>
            <select class="form-select" id="editStatus"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="updateUserBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="resetPwModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Reset Password</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="resetPwUserId">
        <label class="form-label">New Password *</label>
        <input type="password" class="form-control" id="resetPwValue">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="confirmResetPwBtn">Reset Password</button>
      </div>
    </div>
  </div>
</div>

<script>window.CSRF_TOKEN = "<?= $csrf ?>";</script>
<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/users.js"></script>'; ?>
