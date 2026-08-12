<?php $pageTitle = 'Capture Loan'; ?>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= APP_URL ?>/loans/register" class="btn btn-outline-brand btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Register
    </a>
</div>

<form id="captureLoanForm" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <h6 class="panel-title"><i class="bi bi-person-vcard-fill me-1"></i> Client Information</h6>

                <div class="mb-3">
                    <label class="form-label">ID Number *</label>
                    <input type="text" class="form-control" name="id_number" id="idNumberInput" placeholder="Enter client ID number" required>
                    <div id="clientLookupHint" class="form-text"></div>
                    <div class="invalid-feedback" data-error-for="id_number"></div>
                </div>

                <div id="clientFoundBanner" class="client-banner d-none">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Existing client found - details auto-filled below.</span>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name *</label>
                        <input type="text" class="form-control" name="name" id="nameInput" required>
                        <div class="invalid-feedback" data-error-for="name"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Surname *</label>
                        <input type="text" class="form-control" name="surname" id="surnameInput" required>
                        <div class="invalid-feedback" data-error-for="surname"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" name="account_number" id="accountNumberInput">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="phoneInput" placeholder="071 123 4567">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="panel-card h-100">
                <h6 class="panel-title"><i class="bi bi-building me-1"></i> Workplace Information</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Workplace Name</label>
                        <input type="text" class="form-control" name="workplace_name" id="workplaceNameInput" placeholder="Employer / company name">
                        <div class="invalid-feedback" data-error-for="workplace_name"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Work Contact</label>
                        <input type="text" class="form-control" name="work_contact" id="workContactInput" placeholder="Workplace contact number">
                        <div class="invalid-feedback" data-error-for="work_contact"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel-card h-100">
                <h6 class="panel-title"><i class="bi bi-cash-coin me-1"></i> Loan Information</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount *</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" required>
                        <div class="invalid-feedback" data-error-for="amount"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Branch *</label>
                        <select class="form-select" name="branch_id" required>
                            <option value="">Select branch</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="branch_id"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Action Date * <span class="text-muted fw-normal">(client's expected payment date)</span></label>
                        <input type="date" class="form-control" name="action_date" value="<?= date('Y-m-d') ?>" required>
                        <div class="invalid-feedback" data-error-for="action_date"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Date Loaded * <span class="text-muted fw-normal">(sets the budget month)</span></label>
                        <input type="date" class="form-control" name="date_loaded" id="dateLoadedInput" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required>
                        <div class="form-text">Backdate this if you're capturing a loan from a previous month.</div>
                        <div class="invalid-feedback" data-error-for="date_loaded"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Loan Status *</label>
                        <select class="form-select" name="loan_status_id" required>
                            <option value="">Select loan status</option>
                            <?php foreach ($loanStatuses as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['status_name']==='Pending Review'?'selected':'' ?>><?= htmlspecialchars($s['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="loan_status_id"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Repayment Status *</label>
                        <select class="form-select" name="repayment_status_id" required>
                            <option value="">Select repayment status</option>
                            <?php foreach ($repaymentStatuses as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['status_name']==='Not Due'?'selected':'' ?>><?= htmlspecialchars($s['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback" data-error-for="repayment_status_id"></div>
                    </div>
                </div>

                <div class="budget-status-box mt-3 d-none" id="budgetStatusBox">
                    <div class="sg-title">BRANCH BUDGET STATUS (<span id="budgetMonthLabel"><?= date('F Y') ?></span>)</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="budget-figure"><span>Allocated</span><strong id="budgetAllocated">R0.00</strong></div>
                        </div>
                        <div class="col-md-4">
                            <div class="budget-figure"><span>Spent</span><strong id="budgetSpent">R0.00</strong></div>
                        </div>
                        <div class="col-md-4">
                            <div class="budget-figure"><span>Remaining</span><strong id="budgetRemaining">R0.00</strong></div>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height:6px;">
                        <div class="progress-bar" id="budgetProgressBar" role="progressbar" style="width:0%"></div>
                    </div>
                    <div id="budgetWarning" class="text-danger small mt-2 d-none">
                        <i class="bi bi-exclamation-triangle-fill"></i> This amount exceeds the branch's remaining budget for this month.
                    </div>
                    <div class="text-muted small mt-2">
                        Company-wide this month: <span id="companyBudgetSummary">-</span>
                    </div>
                    <div class="text-muted small mt-1">
                        "Spent" only counts loans with Loan Status <strong>Disbursed</strong> or <strong>Closed</strong> — funds aren't counted as spent until they've actually been paid out.
                    </div>
                </div>
                            <div class="system-generated-box mt-3">
                    <div class="sg-title">SYSTEM CALCULATED (Auto)</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Loan Count</label>
                            <input type="text" class="form-control" id="loanCountDisplay" value="1" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Group</label>
                            <input type="text" class="form-control" id="groupDisplay" value="Group 1 (1 - 3 Loans)" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Interest Amount <span class="text-muted fw-normal">(40%)</span></label>
                            <input type="text" class="form-control" id="interestAmountDisplay" value="R0.00" readonly tabindex="-1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount Due</label>
                            <input type="text" class="form-control" id="amountDueDisplay" value="R0.00" readonly tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="panel-card">
                <label class="form-label">Notes (Optional)</label>
                <textarea class="form-control" name="notes" rows="3" placeholder="Enter any additional notes here..."></textarea>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="reset" class="btn btn-outline-secondary" id="clearFormBtn"><i class="bi bi-arrow-counterclockwise"></i> Clear</button>
        <button type="submit" class="btn btn-primary-brand" id="saveLoanBtn"><i class="bi bi-check-lg"></i> Save Loan</button>
    </div>
</form>

<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/capture.js"></script>'; ?>
