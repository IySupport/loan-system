<?php $pageTitle = 'Reports'; ?>

<div class="panel-card mb-3">
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <label class="form-label small">Branch</label>
            <select class="form-select form-select-sm" id="r_branch_id">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Group</label>
            <select class="form-select form-select-sm" id="r_loan_group">
                <option value="">All Groups</option>
                <option value="Group 1">Group 1</option>
                <option value="Group 2">Group 2</option>
                <option value="Group 3">Group 3</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Loan Status</label>
            <select class="form-select form-select-sm" id="r_loan_status_id">
                <option value="">All Loan Statuses</option>
                <?php foreach ($loanStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Repayment Status</label>
            <select class="form-select form-select-sm" id="r_repayment_status_id">
                <option value="">All Repayment Statuses</option>
                <?php foreach ($repaymentStatuses as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['status_name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Date From</label>
            <input type="date" class="form-control form-control-sm" id="r_date_from">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label small">Date To</label>
            <input type="date" class="form-control form-control-sm" id="r_date_to">
        </div>
        <div class="col-12 col-md-3 d-flex align-items-end">
            <button class="btn btn-primary-brand btn-sm w-100" id="generateReportBtn"><i class="bi bi-graph-up"></i> Generate Report</button>
        </div>
    </div>
</div>

<div class="kpi-grid" id="reportKpis">
    <div class="kpi-card"><div><div class="kpi-label">Total Loans</div><div class="kpi-value" id="rk_total_loans">0</div></div></div>
    <div class="kpi-card"><div><div class="kpi-label">Total Amount</div><div class="kpi-value" id="rk_total_amount">R0.00</div></div></div>
    <div class="kpi-card"><div><div class="kpi-label">Active Loans</div><div class="kpi-value" id="rk_active_loans">0</div></div></div>
    <div class="kpi-card"><div><div class="kpi-label">Paid Loans</div><div class="kpi-value" id="rk_paid_loans">0</div></div></div>
    <div class="kpi-card"><div><div class="kpi-label">Defaulted Loans</div><div class="kpi-value" id="rk_overdue_loans">0</div></div></div>
</div>

<div class="panel-card mt-3" id="paymentsDueTodayCard">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <h6 class="panel-title mb-0">Payments Due Today (<?= date('d M Y') ?>)</h6>
        <span class="text-muted small">Loans with an Action Date of today that aren't already Paid.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0" id="dueTodayTable">
            <thead>
                <tr><th>Branch</th><th>Loans Due</th><th>Total Payment Due</th></tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="fw-semibold">
                    <td>Combined Total (All Branches)</td>
                    <td id="dueTodayGrandCount">0</td>
                    <td>
                        <a href="#" id="dueTodayGrandTotalLink" class="text-decoration-none"><span id="dueTodayGrandTotal">R0.00</span> <i class="bi bi-arrow-right-circle"></i></a>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="panel-card mt-3">
    <h6 class="panel-title">Branch Breakdown</h6>
    <div class="table-responsive">
        <table class="table table-clean align-middle" id="reportBranchTable">
            <thead>
                <tr><th>Branch</th><th>Total Loans</th><th>Total Amount</th><th>Active Loans</th><th>Paid Loans</th><th>Defaulted Loans</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
        <button class="btn btn-success btn-sm" id="reportExportExcelBtn"><i class="bi bi-file-earmark-excel"></i> Export to Excel</button>
        <button class="btn btn-outline-brand btn-sm" id="reportPrintBtn"><i class="bi bi-printer"></i> Print Report</button>
    </div>
</div>

<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/reports.js"></script>'; ?>
