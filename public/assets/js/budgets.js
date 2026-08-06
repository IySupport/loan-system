$(function () {
    function loadBudgets() {
        const month = $('#budgetMonth').val() + '-01'; // YYYY-MM -> YYYY-MM-01
        $.get(window.APP_URL + '/budgets/list', { month: month }, function (res) {
            if (!res.success) return;

            $('#companyAllocated').text(fmtMoney(res.company.allocated));
            $('#companySpent').text(fmtMoney(res.company.spent));
            $('#companyRemaining').text(fmtMoney(res.company.remaining));

            const tbody = $('#budgetTableBody').empty();
            if (res.branches.length === 0) {
                tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">No active branches found.</td></tr>');
                return;
            }
            res.branches.forEach(function (b) {
                const remainingClass = b.remaining < 0 ? 'text-danger fw-semibold' : '';
                tbody.append(`
                    <tr data-branch-id="${b.branch_id}">
                        <td class="fw-semibold">${b.branch_name}</td>
                        <td>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">R</span>
                                <input type="number" step="0.01" min="0" class="form-control allocated-input" value="${b.allocated}">
                            </div>
                        </td>
                        <td>${fmtMoney(b.spent)}</td>
                        <td class="${remainingClass}">${fmtMoney(b.remaining)}</td>
                        <td><button class="btn btn-sm btn-primary-brand save-budget-btn"><i class="bi bi-check-lg"></i> Save</button></td>
                    </tr>`);
            });
        });
    }

    $('#loadBudgetsBtn').on('click', loadBudgets);
    $('#budgetMonth').on('change', loadBudgets);
    loadBudgets();

    $('#budgetTableBody').on('click', '.save-budget-btn', function () {
        const row = $(this).closest('tr');
        const branchId = row.data('branch-id');
        const amount = row.find('.allocated-input').val();
        const month = $('#budgetMonth').val() + '-01';

        if (amount === '' || parseFloat(amount) < 0) { Toast.warning('Please enter a valid, non-negative amount.'); return; }

        $.post(window.APP_URL + '/budgets/save', {
            csrf_token: window.CSRF_TOKEN,
            branch_id: branchId,
            month: month,
            allocated_amount: amount,
        }, function (res) {
            if (res.success) { loadBudgets(); }
            else { Toast.error(res.message || 'Could not save budget.'); }
        }).fail(function (xhr) {
            Toast.error(xhr.responseJSON?.message || 'Could not save budget.');
        });
    });
});
