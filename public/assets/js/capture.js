document.addEventListener('DOMContentLoaded', function () {
    const idInput       = document.getElementById('idNumberInput');
    const nameInput      = document.getElementById('nameInput');
    const surnameInput   = document.getElementById('surnameInput');
    const accInput        = document.getElementById('accountNumberInput');
    const phoneInput     = document.getElementById('phoneInput');
    const workplaceNameInput = document.getElementById('workplaceNameInput');
    const workContactInput   = document.getElementById('workContactInput');
    const hint           = document.getElementById('clientLookupHint');
    const banner         = document.getElementById('clientFoundBanner');
    const loanCountBox   = document.getElementById('loanCountDisplay');
    const groupBox       = document.getElementById('groupDisplay');
    const form           = document.getElementById('captureLoanForm');

    // const branchSelect   = form.querySelector('[name="branch_id"]');
    // const actionDateInput = form.querySelector('[name="action_date"]');
    // const amountInput     = form.querySelector('[name="amount"]');
    // const budgetBox       = document.getElementById('budgetStatusBox');

    // function refreshBudgetStatus() {
    //     const branchId = branchSelect.value;
    //     if (!branchId) { budgetBox.classList.add('d-none'); return; }

    //     const actionDate = actionDateInput.value || new Date().toISOString().slice(0, 10);
        // const month = actionDate.slice(0, 7) + '-01'; // YYYY-MM-01
        // const month = actionDate.slice(0, 7) + '-01'; // YYYY-MM-01
const branchSelect    = form.querySelector('[name="branch_id"]');
    const dateLoadedInput = form.querySelector('[name="date_loaded"]');
    const amountInput     = form.querySelector('[name="amount"]');
    const budgetBox       = document.getElementById('budgetStatusBox');
    const interestDisplay = document.getElementById('interestAmountDisplay');
    const amountDueDisplay = document.getElementById('amountDueDisplay');

    // Interest Amount / Amount Due are read-only, system-calculated fields:
    //   Interest Amount = Loan Amount x 40%
    //   Amount Due      = Loan Amount + Interest Amount
    // The authoritative figures are always computed server-side (generated
    // DB columns) - this listener just gives instant visual feedback as
    // the admin types, per the "must update instantly" requirement.
    function refreshCalculatedFigures() {
        const amount = parseFloat(amountInput.value) || 0;
        const interest = amount * 0.40;
        interestDisplay.value = fmtMoney(interest);
        amountDueDisplay.value = fmtMoney(amount + interest);
    }
    amountInput.addEventListener('input', refreshCalculatedFigures);

    function refreshBudgetStatus() {
        const branchId = branchSelect.value;
        if (!branchId) { budgetBox.classList.add('d-none'); return; }

        // Budget month comes from Date Loaded (when the loan was actually
        // captured), NOT Action Date (the client's repayment due date) -
        // these track different things.
        const dateLoaded = dateLoadedInput.value || new Date().toISOString().slice(0, 10);
        const month = dateLoaded.slice(0, 7) + '-01'; // YYYY-MM-01

        fetch(window.APP_URL + '/budgets/status?branch_id=' + encodeURIComponent(branchId) + '&month=' + encodeURIComponent(month))
            .then(r => r.json())
            .then(data => {
                if (!data.success) { budgetBox.classList.add('d-none'); return; }
                budgetBox.classList.remove('d-none');

                document.getElementById('budgetMonthLabel').textContent =
                    new Date(month + 'T00:00:00').toLocaleDateString('en-ZA', { month: 'long', year: 'numeric' });
                document.getElementById('budgetAllocated').textContent = fmtMoney(data.branch.allocated);
                document.getElementById('budgetSpent').textContent     = fmtMoney(data.branch.spent);
                document.getElementById('budgetRemaining').textContent = fmtMoney(data.branch.remaining);
                document.getElementById('companyBudgetSummary').textContent =
                    'Allocated ' + fmtMoney(data.company.allocated) + ' · Spent ' + fmtMoney(data.company.spent) +
                    ' · Remaining ' + fmtMoney(data.company.remaining);

                const pct = data.branch.allocated > 0 ? Math.min(100, (data.branch.spent / data.branch.allocated) * 100) : 0;
                const bar = document.getElementById('budgetProgressBar');
                bar.style.width = pct + '%';
                bar.classList.toggle('bg-danger', data.branch.remaining < 0);

                checkAmountAgainstBudget(data.branch.remaining);
            })
            .catch(() => budgetBox.classList.add('d-none'));
    }

    function checkAmountAgainstBudget(remaining) {
        const warning = document.getElementById('budgetWarning');
        const amount = parseFloat(amountInput.value) || 0;
        warning.classList.toggle('d-none', !(amount > 0 && amount > remaining));
    }

    branchSelect.addEventListener('change', refreshBudgetStatus);
    dateLoadedInput.addEventListener('change', refreshBudgetStatus);
    // branchSelect.addEventListener('change', refreshBudgetStatus);
    // actionDateInput.addEventListener('change', refreshBudgetStatus);
    amountInput.addEventListener('input', function () {
        const remainingText = document.getElementById('budgetRemaining').textContent;
        const remaining = parseFloat(remainingText.replace(/[^0-9.-]/g, '')) || 0;
        if (!budgetBox.classList.contains('d-none')) checkAmountAgainstBudget(remaining);
    });

    let lookupTimer = null;

    function resetClientState() {
        banner.classList.add('d-none');
        loanCountBox.value = '1';
        groupBox.value = 'Group 1 (1 - 3 Loans)';
    }

    function resetCalculatedFigures() {
        interestDisplay.value = fmtMoney(0);
        amountDueDisplay.value = fmtMoney(0);
    }

    idInput.addEventListener('input', function () {
        clearTimeout(lookupTimer);
        const val = idInput.value.trim();
        if (val.length < 4) { resetClientState(); hint.textContent = ''; return; }
        hint.textContent = 'Searching...';
        lookupTimer = setTimeout(() => doLookup(val), 400);
    });

    function doLookup(idNumber) {
        fetch(window.APP_URL + '/clients/lookup?id_number=' + encodeURIComponent(idNumber))
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    hint.textContent = '';
                    banner.classList.remove('d-none');
                    nameInput.value    = data.client.name;
                    surnameInput.value = data.client.surname;
                    accInput.value     = data.client.account_number || '';
                    phoneInput.value   = data.client.phone || '';
                    loanCountBox.value = data.next_loan_count;
                    groupBox.value     = groupLabel(data.group, data.next_loan_count);
                    // Prefill from their most recent loan as a convenience,
                    // but don't clobber anything the admin already typed.
                    if (!workplaceNameInput.value) workplaceNameInput.value = data.last_workplace_name || '';
                    if (!workContactInput.value)   workContactInput.value   = data.last_work_contact || '';
                } else {
                    hint.textContent = 'New client — please fill in the details below.';
                    banner.classList.add('d-none');
                    loanCountBox.value = '1';
                    groupBox.value = 'Group 1 (1 - 3 Loans)';
                }
            })
            .catch(() => { hint.textContent = ''; });
    }

    function groupLabel(group, count) {
        const ranges = { 'Group 1': '1 - 3 Loans', 'Group 2': '4 - 8 Loans', 'Group 3': '9+ Loans' };
        return group + ' (' + ranges[group] + ')';
    }

    document.getElementById('clearFormBtn').addEventListener('click', function () {
        setTimeout(() => { resetClientState(); resetCalculatedFigures(); hint.textContent = ''; clearErrors(); budgetBox.classList.add('d-none'); }, 10);
    });

    function clearErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('[data-error-for]').forEach(el => el.textContent = '');
    }

    function showErrors(errors) {
        clearErrors();
        Object.keys(errors).forEach(field => {
            const input = form.querySelector('[name="' + field + '"]');
            if (input) input.classList.add('is-invalid');
            const box = form.querySelector('[data-error-for="' + field + '"]');
            if (box) box.textContent = errors[field];
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();
        const btn = document.getElementById('saveLoanBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';

        const formData = new FormData(form);
        fetch(window.APP_URL + '/loans', { method: 'POST', body: formData })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                if (status === 200 && data.success) {
                    Toast.success('Loan saved successfully.\nReference Number: ' + data.loan.reference_number);
                    form.reset();
                    resetClientState();
                    resetCalculatedFigures();
                    hint.textContent = '';
                    budgetBox.classList.add('d-none');
                } else if (data.errors) {
                    showErrors(data.errors);
                } else {
                    Toast.error(data.message || 'Something went wrong. Please try again.');
                }
            })
            .catch(() => Toast.error('Network error. Please try again.'))
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Loan';
            });
    });
});
