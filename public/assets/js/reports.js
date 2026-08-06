$(function () {
    function filters() {
        return {
            branch_id: $('#r_branch_id').val() || '',
            loan_group: $('#r_loan_group').val() || '',
            loan_status_id: $('#r_loan_status_id').val() || '',
            repayment_status_id: $('#r_repayment_status_id').val() || '',
            date_from: $('#r_date_from').val() || '',
            date_to: $('#r_date_to').val() || '',
        };
    }

    function generate() {
        $.get(window.APP_URL + '/reports/generate', Object.assign({ _: Date.now() }, filters()), function (res) {
            if (!res.success) return;
            $('#rk_total_loans').text(Number(res.totals.total_loans).toLocaleString());
            $('#rk_total_amount').text(fmtMoney(res.totals.total_amount));
            $('#rk_active_loans').text(Number(res.totals.active_loans).toLocaleString());
            $('#rk_paid_loans').text(Number(res.totals.paid_loans).toLocaleString());
            $('#rk_overdue_loans').text(Number(res.totals.overdue_loans).toLocaleString());

            const tbody = $('#reportBranchTable tbody').empty();
            res.by_branch.forEach(function (b) {
                tbody.append(`<tr>
                    <td class="fw-semibold">${b.branch_name}</td>
                    <td>${Number(b.total_loans).toLocaleString()}</td>
                    <td>${fmtMoney(b.total_amount)}</td>
                    <td>${Number(b.active_loans).toLocaleString()}</td>
                    <td>${Number(b.paid_loans).toLocaleString()}</td>
                    <td>${Number(b.overdue_loans).toLocaleString()}</td>
                </tr>`);
            });
            if (res.by_branch.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted py-3">No data for the selected filters.</td></tr>');
            }
        });
    }

    $('#generateReportBtn').on('click', generate);
    generate();
    loadPaymentsDueToday();

    // ---- Payments Due Today (independent of the filters above - always "today") ----
    function loadPaymentsDueToday() {
        $.get(window.APP_URL + '/reports/payments-due-today', { _: Date.now() }, function (res) {
            if (!res.success) return;

            const today = new Date().toISOString().slice(0, 10);
            const tbody = $('#dueTodayTable tbody').empty();

            res.by_branch.forEach(function (b) {
                const url = window.APP_URL + '/loans/register?branch_id=' + encodeURIComponent(b.branch_id)
                    + '&action_date_from=' + today + '&action_date_to=' + today;
                tbody.append(`<tr>
                    <td class="fw-semibold">${b.branch_name}</td>
                    <td>${Number(b.loan_count).toLocaleString()}</td>
                    <td><a href="${url}" class="text-decoration-none">${fmtMoney(b.total_due)} <i class="bi bi-arrow-right-circle"></i></a></td>
                </tr>`);
            });
            if (res.by_branch.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center text-muted py-3">No payments due today.</td></tr>');
            }

            $('#dueTodayGrandCount').text(Number(res.grand_total.loan_count).toLocaleString());
            $('#dueTodayGrandTotal').text(fmtMoney(res.grand_total.total_due));
            $('#dueTodayGrandTotalLink').attr('href',
                window.APP_URL + '/loans/register?action_date_from=' + today + '&action_date_to=' + today);
        });
    }

    $('#reportExportExcelBtn').on('click', function () {
        const qs = $.param({
            branch_id: filters().branch_id,
            loan_group: filters().loan_group,
            loan_status_id: filters().loan_status_id,
            repayment_status_id: filters().repayment_status_id,
            date_loaded_from: filters().date_from,
            date_loaded_to: filters().date_to,
        });
        window.location = window.APP_URL + '/export/filtered?' + qs;
    });

    $('#reportPrintBtn').on('click', function () { window.print(); });
});
