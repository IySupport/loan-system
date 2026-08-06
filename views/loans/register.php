<?php $pageTitle = 'Loan Register'; ?>

<div class="panel-card mb-3">
    <div class="row g-2">
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Branch</label>
            <select class="form-select form-select-sm" id="f_branch_id">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Group</label>
            <select class="form-select form-select-sm" id="f_loan_group">
                <option value="">All Groups</option>
                <option value="Group 1">Group 1</option>
                <option value="Group 2">Group 2</option>
                <option value="Group 3">Group 3</option>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Status</label>
            <select class="form-select form-select-sm" id="f_loan_status_id">
                <option value="">All Statuses</option>
                <?php foreach ($loanStatuses as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
               <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Repayment Status</label>
            <select class="form-select form-select-sm" id="f_repayment_status_id">
                <option value="">All Repayment Statuses</option>
                <?php foreach ($repaymentStatuses as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Date Loaded From</label>
            <input type="date" class="form-control form-control-sm" id="f_date_loaded_from">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Date Loaded To</label>
            <input type="date" class="form-control form-control-sm" id="f_date_loaded_to">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label class="form-label small">Workplace</label>
            <input type="text" class="form-control form-control-sm" id="f_workplace" placeholder="Search workplace...">
        </div>
    </div>

    <div class="collapse mt-2" id="moreFilters">
        <div class="row g-2">
            <div class="col-6 col-md-3"><label class="form-label small">Action Date From</label><input type="date" class="form-control form-control-sm" id="f_action_date_from"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Action Date To</label><input type="date" class="form-control form-control-sm" id="f_action_date_to"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Amount Min</label><input type="number" class="form-control form-control-sm" id="f_amount_min"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Amount Max</label><input type="number" class="form-control form-control-sm" id="f_amount_max"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Loan Count Min</label><input type="number" class="form-control form-control-sm" id="f_loan_count_min"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Loan Count Max</label><input type="number" class="form-control form-control-sm" id="f_loan_count_max"></div>
        </div>
    </div>

    <div class="row g-2 mt-2 align-items-center">
        <div class="col-12 col-md-6">
            <input type="text" class="form-control form-control-sm" id="globalSearch" placeholder="Search by Name, Surname, ID Number, Acc No. or Ref No...">
        </div>
        <div class="col-6 col-md-2">
            <button class="btn btn-primary-brand btn-sm w-100" id="searchBtn"><i class="bi bi-search"></i> Search</button>
        </div>
        <div class="col-6 col-md-2">
            <button class="btn btn-outline-secondary btn-sm w-100" id="clearFiltersBtn"><i class="bi bi-x-circle"></i> Clear</button>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-outline-brand btn-sm w-100" type="button" data-bs-toggle="collapse" data-bs-target="#moreFilters">
                <i class="bi bi-sliders"></i> More Filters
            </button>
        </div>
    </div>
</div>

<div class="panel-card">
    <div class="table-responsive">
        <table id="loanTable" class="table table-clean align-middle w-100">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Ref No.</th>
                    <th>Name</th>
                    <th>Surname</th>
                    <th>ID Number</th>
                    <th>Account No.</th>
                    <th>Amount</th>
                    <th>Branch</th>
                    <th>Workplace</th>
                    <th>Loan Count</th>
                    <th>Group</th>
                    <th>Loan Status</th>
                    <th>Repayment Status</th>
                    <th>Action Date</th>
                    <th>Date Loaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <div class="bulk-action-bar" id="bulkActionBar">
        <span id="selectedCount" class="me-2 fw-semibold">0 rows selected</span>
        <button class="btn btn-success btn-sm" id="exportSelectedBtn"><i class="bi bi-file-earmark-excel"></i> Export Selected</button>
        <button class="btn btn-outline-brand btn-sm" id="exportFilteredBtn"><i class="bi bi-file-earmark-excel"></i> Export Filtered</button>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-brand dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download"></i> Export</button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= APP_URL ?>/export/all">Export All</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/export/group/1">Group 1</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/export/group/2">Group 2</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/export/group/3">Group 3</a></li>
                <li><hr class="dropdown-divider"></li>
                <?php foreach ($branches as $b): ?>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/export/branch/<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button class="btn btn-info btn-sm text-white" id="changeStatusBtn" data-bs-toggle="modal" data-bs-target="#changeStatusModal"><i class="bi bi-arrow-repeat"></i> Change Status</button>
        <button class="btn btn-secondary btn-sm" id="changeReportStatusBtn" data-bs-toggle="modal" data-bs-target="#changeReportStatusModal"><i class="bi bi-send-check"></i> Change Report Status</button>
        <button class="btn btn-danger btn-sm" id="deleteSelectedBtn"><i class="bi bi-trash"></i> Delete</button>
    </div>
</div>

<!-- Edit Loan Modal -->
<div class="modal fade" id="editLoanModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Edit Loan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <form id="editLoanForm">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="loan_id" id="edit_loan_id">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Amount *</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="amount" id="edit_amount" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch *</label>
                    <select class="form-select" name="branch_id" id="edit_branch_id" required>
                        <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <!-- <div class="col-md-6">
                    <label class="form-label">Action Date *</label>
                    <input type="date" class="form-control" name="action_date" id="edit_action_date" required>
                </div> -->
                <div class="col-md-6">
                    <label class="form-label">Action Date * <span class="text-muted fw-normal">(payment due date)</span></label>
                    <input type="date" class="form-control" name="action_date" id="edit_action_date" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date Loaded * <span class="text-muted fw-normal">(sets budget month)</span></label>
                    <input type="date" class="form-control" name="date_loaded" id="edit_date_loaded" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select class="form-select" name="loan_status_id" id="edit_loan_status_id" required>
                        <?php foreach ($loanStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                      <div class="col-md-6">
                    <label class="form-label">Repayment Status *</label>
                    <select class="form-select" name="repayment_status_id" id="edit_repayment_status_id" required>
                        <?php foreach ($repaymentStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Workplace Name</label>
                    <input type="text" class="form-control" name="workplace_name" id="edit_workplace_name">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Work Contact</label>
                    <input type="text" class="form-control" name="work_contact" id="edit_work_contact">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Interest Amount <span class="text-muted fw-normal">(auto, 40%)</span></label>
                    <input type="text" class="form-control" id="edit_interest_amount" readonly tabindex="-1">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount Due <span class="text-muted fw-normal">(auto)</span></label>
                    <input type="text" class="form-control" id="edit_amount_due" readonly tabindex="-1">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" id="edit_notes" rows="2"></textarea>
                </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="saveEditLoanBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Change Status</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">New Status</label>
        <select class="form-select" id="bulkStatusSelect">
            <?php foreach ($loanStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="applyBulkStatusBtn">Apply</button>
      </div>
    </div>
  </div>
</div>

<!-- Bulk Change Report Status Modal -->
<div class="modal fade" id="changeReportStatusModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Change Report Status</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">New Report Status</label>
        <select class="form-select" id="bulkReportStatusSelect">
            <?php foreach ($reportStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary-brand" id="applyBulkReportStatusBtn">Apply</button>
      </div>
    </div>
  </div>
</div>

<?php $pageScripts = '<script>window.CSRF_TOKEN = "' . $csrf . '";</script><script src="' . APP_URL . '/assets/js/register.js"></script>'; ?>
