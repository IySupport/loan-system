<?php $pageTitle = 'Branch Budgets'; ?>

<div class="panel-card mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-6 col-md-3">
            <label class="form-label small">Budget Month</label>
            <input type="month" class="form-control form-control-sm" id="budgetMonth" value="<?= date('Y-m') ?>">
        </div>
        <div class="col-6 col-md-3">
            <button class="btn btn-primary-brand btn-sm w-100" id="loadBudgetsBtn"><i class="bi bi-arrow-repeat"></i> Load</button>
        </div>
    </div>
</div>

<div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);">
    <div class="kpi-card">
        <div>
            <div class="kpi-label">Company Allocated</div>
            <div class="kpi-value" id="companyAllocated">R0.00</div>
        </div>
    </div>
    <div class="kpi-card">
        <div>
            <div class="kpi-label">Company Spent</div>
            <div class="kpi-value" id="companySpent">R0.00</div>
        </div>
    </div>
    <div class="kpi-card">
        <div>
            <div class="kpi-label">Company Remaining</div>
            <div class="kpi-value" id="companyRemaining">R0.00</div>
        </div>
    </div>
</div>

<div class="panel-card mt-3">
    <h6 class="panel-title">Monthly Allocation per Branch</h6>
    <div class="table-responsive">
        <table class="table table-clean align-middle">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th style="width:220px;">Allocated Amount</th>
                    <th>Spent (This Month)</th>
                    <th>Remaining</th>
                    <th style="width:110px;">Action</th>
                </tr>
            </thead>
            <tbody id="budgetTableBody">
                <tr><td colspan="5" class="text-center text-muted py-4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <p class="text-muted small mb-0">
        "Spent" totals all captured loans for that branch in the selected month, excluding loans marked
        <strong>Rejected</strong>. Editing an amount and clicking Save updates only that branch's allocation
        for the selected month — past months are kept for historical reporting.
    </p>
</div>

<script>window.CSRF_TOKEN = "<?= $csrf ?>";</script>
<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/budgets.js"></script>'; ?>
