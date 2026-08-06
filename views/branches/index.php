<?php $pageTitle = 'Branches'; ?>

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addBranchModal"><i class="bi bi-plus-lg"></i> Add Branch</button>
</div>

<div class="panel-card">
    <div class="table-responsive">
        <table class="table table-clean align-middle">
            <thead><tr><th>#</th><th>Branch Name</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($branches as $i => $b): ?>
                <tr data-id="<?= $b['id'] ?>">
                    <td><?= $i + 1 ?></td>
                    <td class="branch-name"><?= htmlspecialchars($b['branch_name']) ?></td>
                    <td><span class="badge-status status-<?= strtolower($b['status']) ?>"><?= $b['status'] ?></span></td>
                    <td><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-brand edit-branch-btn" data-id="<?= $b['id'] ?>" data-name="<?= htmlspecialchars($b['branch_name']) ?>" data-status="<?= $b['status'] ?>"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-secondary toggle-branch-btn" data-id="<?= $b['id'] ?>"><?= $b['status']==='Active' ? '<i class="bi bi-pause-circle"></i> Deactivate' : '<i class="bi bi-play-circle"></i> Activate' ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="addBranchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add Branch</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Branch Name *</label>
        <input type="text" class="form-control" id="newBranchName" placeholder="e.g. Bloemfontein">
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="saveBranchBtn">Save Branch</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editBranchModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Branch</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="editBranchId">
        <label class="form-label">Branch Name *</label>
        <input type="text" class="form-control mb-3" id="editBranchName">
        <label class="form-label">Status</label>
        <select class="form-select" id="editBranchStatus"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="updateBranchBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<script>window.CSRF_TOKEN = "<?= $csrf ?>";</script>
<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/branches.js"></script>'; ?>
