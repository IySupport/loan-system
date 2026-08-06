<?php $pageTitle = 'Clients'; ?>

<div class="panel-card mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-12 col-md-8">
            <input type="text" class="form-control form-control-sm" id="clientSearch" placeholder="Search by Name, Surname, ID Number, Acc No. or Phone...">
        </div>
        <div class="col-6 col-md-2">
            <button class="btn btn-primary-brand btn-sm w-100" id="clientSearchBtn"><i class="bi bi-search"></i> Search</button>
        </div>
        <div class="col-6 col-md-2">
            <button class="btn btn-outline-secondary btn-sm w-100" id="clientClearBtn"><i class="bi bi-x-circle"></i> Clear</button>
        </div>
    </div>
</div>

<div class="panel-card">
    <div class="table-responsive">
        <table id="clientTable" class="table table-clean align-middle w-100">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Surname</th>
                    <th>Account No.</th>
                    <th>Phone</th>
                    <th>Loans</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- View Client Modal -->
<div class="modal fade" id="viewClientModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Client Details</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6"><div class="text-muted small">ID Number</div><div class="fw-semibold" id="view_id_number">-</div></div>
            <div class="col-md-6"><div class="text-muted small">Name</div><div class="fw-semibold" id="view_name">-</div></div>
            <div class="col-md-6"><div class="text-muted small">Surname</div><div class="fw-semibold" id="view_surname">-</div></div>
            <div class="col-md-6"><div class="text-muted small">Account Number</div><div class="fw-semibold" id="view_account_number">-</div></div>
            <div class="col-md-6"><div class="text-muted small">Phone</div><div class="fw-semibold" id="view_phone">-</div></div>
            <div class="col-md-6"><div class="text-muted small">Registered</div><div class="fw-semibold" id="view_created_at">-</div></div>
        </div>
        <h6 class="panel-title">Loan History (<span id="view_loan_count">0</span>)</h6>
        <div class="table-responsive">
            <table class="table table-clean align-middle mb-0">
                <thead>
                    <tr><th>Ref No.</th><th>Branch</th><th>Amount</th><th>Amount Due</th><th>Loan Status</th><th>Repayment Status</th><th>Action Date</th></tr>
                </thead>
                <tbody id="view_loans_tbody"></tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Client</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="editClientForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" id="edit_client_id">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" class="form-control" id="edit_client_name" required>
                    <div class="invalid-feedback" data-error-for="name"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Surname *</label>
                    <input type="text" class="form-control" id="edit_client_surname" required>
                    <div class="invalid-feedback" data-error-for="surname"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">ID Number *</label>
                    <input type="text" class="form-control" id="edit_client_id_number" required>
                    <div class="invalid-feedback" data-error-for="id_number"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Account Number</label>
                    <input type="text" class="form-control" id="edit_client_account_number">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" id="edit_client_phone" placeholder="071 123 4567">
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="saveClientBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<?php $pageScripts = '<script>window.CSRF_TOKEN = "' . $csrf . '";</script><script src="' . APP_URL . '/assets/js/clients.js"></script>'; ?>
