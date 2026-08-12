let dtInstance;
let selectedIds = new Set();

function currentFilters() {
    return {
        search:                $('#globalSearch').val() || '',
        branch_id:             $('#f_branch_id').val() || '',
        loan_group:            $('#f_loan_group').val() || '',
        loan_status_id:        $('#f_loan_status_id').val() || '',
        repayment_status_id:   $('#f_repayment_status_id').val() || '',
        workplace:              $('#f_workplace').val() || '',
        date_loaded_from:      $('#f_date_loaded_from').val() || '',
        date_loaded_to:        $('#f_date_loaded_to').val() || '',
        action_date_from:      $('#f_action_date_from').val() || '',
        action_date_to:        $('#f_action_date_to').val() || '',
        amount_min:            $('#f_amount_min').val() || '',
        amount_max:            $('#f_amount_max').val() || '',
        loan_count_min:        $('#f_loan_count_min').val() || '',
        loan_count_max:        $('#f_loan_count_max').val() || '',
    };
}

const ORDER_COLUMNS = [
    null, 'reference_number', 'name', 'surname', 'id_number', 'account_number', 'amount',
    'branch_name', 'workplace_name', 'loan_count', 'loan_group', 'status', 'repayment_status', 'action_date', 'date_loaded', null,
];

$(function () {
    // preselect filters from query string (e.g. dashboard/report card links)
    const params = new URLSearchParams(window.location.search);
    if (params.get('loan_group'))       $('#f_loan_group').val(params.get('loan_group'));
    if (params.get('branch_id'))        $('#f_branch_id').val(params.get('branch_id'));
    if (params.get('action_date_from')) $('#f_action_date_from').val(params.get('action_date_from'));
    if (params.get('action_date_to'))   $('#f_action_date_to').val(params.get('action_date_to'));
    if (params.get('action_date_from') || params.get('action_date_to')) {
        // reveal the "More Filters" panel so the applied Action Date range is visible
        const moreFilters = document.getElementById('moreFilters');
        if (moreFilters && window.bootstrap) new bootstrap.Collapse(moreFilters, { toggle: true });
    }

    dtInstance = $('#loanTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        order: [[13, 'desc']],
        ajax: function (data, callback) {
            const orderIdx = data.order[0].column;
            const orderDir = data.order[0].dir;
            const params = Object.assign({
                draw: data.draw,
                start: data.start,
                length: data.length,
                order_col: ORDER_COLUMNS[orderIdx] || 'date_loaded',
                order_dir: orderDir,
                _: Date.now(), // cache-bust: never serve a stale page of results
            }, currentFilters());

            $.get(window.APP_URL + '/loans/data', params, function (res) {
                callback(res);
            }).fail(function () {
                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
            });
        },
        columns: [
            {
                data: 'id', orderable: false, className: 'text-center',
                render: (id) => `<input type="checkbox" class="row-check" value="${id}">`
            },
            { data: 'reference_number', render: (d) => `<span class="fw-semibold">${d}</span>` },
            { data: 'name' },
            { data: 'surname' },
            { data: 'id_number' },
            { data: 'account_number' },
            { data: 'amount', render: (d) => fmtMoney(d) },
            { data: 'branch_name' },
            { data: 'workplace_name', render: (d) => d ? d : '<span class="text-muted">-</span>' },
            { data: 'loan_count', className: 'text-center' },
            { data: 'loan_group', render: (d) => `<span class="group-pill ${groupBadgeClass(d)}">${d}</span>` },
            { data: 'status', render: (d) => `<span class="badge-status ${statusBadgeClass(d)}">${d}</span>` },
            { data: 'repayment_status', render: (d) => `<span class="badge-status ${statusBadgeClass(d)}">${d}</span>` },
            // { data: 'action_date', render: (d) => d ? new Date(d).toLocaleDateString('en-ZA') : '' },
            { data: 'action_date', render: (d) => fmtDate(d) },
            { data: 'date_loaded', render: (d) => d ? new Date(d).toLocaleDateString('en-ZA') : '' },
            {
                data: 'id', orderable: false, className: 'text-nowrap',
                render: (id) => `
                    <button class="btn btn-sm btn-outline-brand edit-loan-btn" data-id="${id}"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-loan-btn" data-id="${id}"><i class="bi bi-trash"></i></button>`
            },
        ],
        drawCallback: function () {
            $('#loanTable tbody .row-check').each(function () {
                if (selectedIds.has(String($(this).val()))) $(this).prop('checked', true);
            });
            updateBulkBar();
        },
        language: { emptyTable: 'No loans found for the selected filters.' },
    });

    $('#searchBtn').on('click', () => dtInstance.ajax.reload());
    $('#globalSearch, #f_workplace').on('keypress', function (e) { if (e.which === 13) dtInstance.ajax.reload(); });
    $('#f_branch_id, #f_loan_group, #f_loan_status_id, #f_repayment_status_id, #f_workplace, #f_date_loaded_from, #f_date_loaded_to, #f_action_date_from, #f_action_date_to, #f_amount_min, #f_amount_max, #f_loan_count_min, #f_loan_count_max')
        .on('change', () => dtInstance.ajax.reload());

    $('#clearFiltersBtn').on('click', function () {
        $('#globalSearch, #f_workplace, #f_amount_min, #f_amount_max, #f_loan_count_min, #f_loan_count_max, #f_date_loaded_from, #f_date_loaded_to, #f_action_date_from, #f_action_date_to').val('');
        $('#f_branch_id, #f_loan_group, #f_loan_status_id, #f_repayment_status_id').val('');
        dtInstance.ajax.reload();
    });

    $('#selectAll').on('change', function () {
        const checked = $(this).is(':checked');
        $('#loanTable tbody .row-check').each(function () {
            $(this).prop('checked', checked);
            checked ? selectedIds.add(String($(this).val())) : selectedIds.delete(String($(this).val()));
        });
        updateBulkBar();
    });

    $('#loanTable tbody').on('change', '.row-check', function () {
        const id = String($(this).val());
        $(this).is(':checked') ? selectedIds.add(id) : selectedIds.delete(id);
        updateBulkBar();
    });

    function updateBulkBar() {
        const count = selectedIds.size;
        $('#bulkActionBar').toggleClass('show', count > 0);
        $('#selectedCount').text(count + (count === 1 ? ' row selected' : ' rows selected'));
    }

    // ---- Export ----
    $('#exportSelectedBtn').on('click', function () {
        if (selectedIds.size === 0) { Toast.warning('Please select at least one row.'); return; }
        window.location = window.APP_URL + '/export/selected?ids=' + Array.from(selectedIds).join(',');
    });
    $('#exportFilteredBtn').on('click', function () {
        const qs = $.param(currentFilters());
        window.location = window.APP_URL + '/export/filtered?' + qs;
    });

    // ---- Bulk loan status / repayment status / delete ----
    $('#applyBulkStatusBtn').on('click', function () {
        postBulk('/loans/bulk-status', { loan_status_id: $('#bulkStatusSelect').val() }, '#changeStatusModal');
    });
    $('#applyBulkRepaymentStatusBtn').on('click', function () {
        postBulk('/loans/bulk-repayment-status', { repayment_status_id: $('#bulkRepaymentStatusSelect').val() }, '#changeRepaymentStatusModal');
    });
    $('#deleteSelectedBtn').on('click', function () {
        if (selectedIds.size === 0) { Toast.warning('Please select at least one row.'); return; }

        if (!Toast.confirm('Delete ' + selectedIds.size + ' selected loan(s)? This cannot be undone.')) return;
        postBulk('/loans/bulk-delete', {}, null);
    });

    function postBulk(path, extra, modalSelector) {
        if (selectedIds.size === 0) { Toast.warning('Please select at least one row.'); return; }
        const payload = Object.assign({ csrf_token: window.CSRF_TOKEN, ids: JSON.stringify(Array.from(selectedIds)) }, extra);
        $.post(window.APP_URL + path, payload, function (res) {
            if (res.success) {
                if (modalSelector) bootstrap.Modal.getInstance(document.querySelector(modalSelector))?.hide();
                selectedIds.clear();
                updateBulkBar();
                dtInstance.ajax.reload(null, false);
            } else {
                Toast.error(res.message || 'Action failed.');
            }
        }).fail(() => Toast.error('Action failed. Please try again.'));
    }

    // ---- Edit loan ----
    $('#loanTable tbody').on('click', '.edit-loan-btn', function () {
        const id = $(this).data('id');
        $.get(window.APP_URL + '/loans/' + id + '/edit', { _: Date.now() }, function (res) {
            if (!res.success) { Toast.warning('Loan not found.'); return; }
            const l = res.loan;
            $('#edit_loan_id').val(l.id);
            $('#edit_amount').val(l.amount);
            $('#edit_branch_id').val(l.branch_id);

            $('#edit_action_date').val(l.action_date);
            $('#edit_loan_status_id').val(l.loan_status_id);
            $('#edit_date_loaded').val(l.date_loaded ? String(l.date_loaded).slice(0, 10) : '');

            $('#edit_repayment_status_id').val(l.repayment_status_id);
            $('#edit_workplace_name').val(l.workplace_name || '');
            $('#edit_work_contact').val(l.work_contact || '');
            $('#edit_notes').val(l.notes || '');
            refreshEditCalculatedFigures();
            new bootstrap.Modal(document.getElementById('editLoanModal')).show();
        });
    });

    // Interest Amount / Amount Due are read-only and recompute instantly
    // as the Amount field changes. The authoritative values are always
    // recalculated server-side (generated DB columns) on save - this is
    // just for immediate visual feedback.
    function refreshEditCalculatedFigures() {
        const amount = parseFloat($('#edit_amount').val()) || 0;
        $('#edit_interest_amount').val(fmtMoney(amount * 0.40));
        $('#edit_amount_due').val(fmtMoney(amount * 1.40));
    }
    $(document).on('input', '#edit_amount', refreshEditCalculatedFigures);

    $('#saveEditLoanBtn').on('click', function () {
        const id = $('#edit_loan_id').val();
        const payload = {
            csrf_token: window.CSRF_TOKEN,
            amount: $('#edit_amount').val(),
            branch_id: $('#edit_branch_id').val(),
            action_date: $('#edit_action_date').val(),
            date_loaded: $('#edit_date_loaded').val(),
            loan_status_id: $('#edit_loan_status_id').val(),
            repayment_status_id: $('#edit_repayment_status_id').val(),
            workplace_name: $('#edit_workplace_name').val(),
            work_contact: $('#edit_work_contact').val(),
            notes: $('#edit_notes').val(),
        };
        $.post(window.APP_URL + '/loans/' + id + '/update', payload, function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('editLoanModal')).hide();
                dtInstance.ajax.reload(null, false);
            } else {
                Toast.error('Could not save changes.');
            }
        }).fail(() => Toast.error('Could not save changes.'));
    });

    // ---- Single delete ----
    $('#loanTable tbody').on('click', '.delete-loan-btn', async function () {
        const id = String($(this).data('id'));
         const ok = await Toast.confirm('Delete this loan? This cannot be undone.', { type: 'warning', confirmLabel: 'Confirmtion' });
        if (!ok) return;
        // if (!confirm('Delete this loan? This cannot be undone.')) return;
        $.post(window.APP_URL + '/loans/bulk-delete', { csrf_token: window.CSRF_TOKEN, ids: JSON.stringify([id]) }, function (res) {
            if (res.success) dtInstance.ajax.reload(null, false);
            else Toast.error(res.message || 'Delete failed.');
        }).fail(() => Toast.error('Delete failed.'));
    });
});
