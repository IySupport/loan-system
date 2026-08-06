let clientDt;

const CLIENT_ORDER_COLUMNS = [
    'id_number', 'name', 'surname', 'account_number', 'phone', 'loan_count', 'created_at', null,
];

$(function () {
    clientDt = $('#clientTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        lengthMenu: [10, 25, 50, 100],
        pageLength: 25,
        order: [[6, 'desc']],
        ajax: function (data, callback) {
            const orderIdx = data.order[0].column;
            const orderDir = data.order[0].dir;
            const params = {
                draw: data.draw,
                start: data.start,
                length: data.length,
                order_col: CLIENT_ORDER_COLUMNS[orderIdx] || 'created_at',
                order_dir: orderDir,
                search: $('#clientSearch').val() || '',
                _: Date.now(),
            };

            $.get(window.APP_URL + '/clients/data', params, function (res) {
                callback(res);
            }).fail(function () {
                callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
            });
        },
        columns: [
            { data: 'id_number' },
            { data: 'name' },
            { data: 'surname' },
            { data: 'account_number', render: (d) => d || '<span class="text-muted">-</span>' },
            { data: 'phone', render: (d) => d || '<span class="text-muted">-</span>' },
            { data: 'loan_count', className: 'text-center' },
            { data: 'created_at', render: (d) => d ? new Date(d).toLocaleDateString('en-ZA') : '' },
            {
                data: 'id', orderable: false, className: 'text-nowrap',
                render: (id) => `
                    <button class="btn btn-sm btn-outline-brand view-client-btn" data-id="${id}" title="View"><i class="bi bi-eye"></i></button>
                    <button class="btn btn-sm btn-outline-brand edit-client-btn" data-id="${id}" title="Edit"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-client-btn" data-id="${id}" title="Delete"><i class="bi bi-trash"></i></button>`
            },
        ],
        language: { emptyTable: 'No clients found.' },
    });

    $('#clientSearchBtn').on('click', () => clientDt.ajax.reload());
    $('#clientSearch').on('keypress', function (e) { if (e.which === 13) clientDt.ajax.reload(); });
    $('#clientClearBtn').on('click', function () {
        $('#clientSearch').val('');
        clientDt.ajax.reload();
    });

    // ---- View client ----
    $('#clientTable tbody').on('click', '.view-client-btn', function () {
        const id = $(this).data('id');
        $.get(window.APP_URL + '/clients/' + id, { _: Date.now() }, function (res) {
            if (!res.success) { Toast.warning('Client not found.'); return; }
            const c = res.client;
            $('#view_id_number').text(c.id_number || '-');
            $('#view_name').text(c.name || '-');
            $('#view_surname').text(c.surname || '-');
            $('#view_account_number').text(c.account_number || '-');
            $('#view_phone').text(c.phone || '-');
            $('#view_created_at').text(c.created_at ? new Date(c.created_at).toLocaleDateString('en-ZA') : '-');
            $('#view_loan_count').text(res.loan_count);

            const tbody = $('#view_loans_tbody').empty();
            res.loans.forEach(function (l) {
                tbody.append(`<tr>
                    <td class="fw-semibold">${l.reference_number}</td>
                    <td>${l.branch_name}</td>
                    <td>${fmtMoney(l.amount)}</td>
                    <td>${fmtMoney(l.amount_due)}</td>
                    <td><span class="badge-status ${statusBadgeClass(l.status)}">${l.status}</span></td>
                    <td><span class="badge-status ${statusBadgeClass(l.repayment_status)}">${l.repayment_status}</span></td>
                    <td>${l.action_date ? new Date(l.action_date).toLocaleDateString('en-ZA') : ''}</td>
                </tr>`);
            });
            if (res.loans.length === 0) {
                tbody.append('<tr><td colspan="7" class="text-center text-muted py-3">No loans for this client yet.</td></tr>');
            }

            new bootstrap.Modal(document.getElementById('viewClientModal')).show();
        }).fail(() => Toast.error('Could not load client details.'));
    });

    // ---- Edit client ----
    function clearClientErrors() {
        $('#editClientForm .is-invalid').removeClass('is-invalid');
        $('#editClientForm [data-error-for]').text('');
    }
    function showClientErrors(errors) {
        clearClientErrors();
        Object.keys(errors).forEach(field => {
            $('#edit_client_' + field).addClass('is-invalid');
            $('#editClientForm [data-error-for="' + field + '"]').text(errors[field]);
        });
    }

    $('#clientTable tbody').on('click', '.edit-client-btn', function () {
        const id = $(this).data('id');
        $.get(window.APP_URL + '/clients/' + id, { _: Date.now() }, function (res) {
            if (!res.success) { Toast.warning('Client not found.'); return; }
            clearClientErrors();
            const c = res.client;
            $('#edit_client_id').val(c.id);
            $('#edit_client_name').val(c.name || '');
            $('#edit_client_surname').val(c.surname || '');
            $('#edit_client_id_number').val(c.id_number || '');
            $('#edit_client_account_number').val(c.account_number || '');
            $('#edit_client_phone').val(c.phone || '');
            new bootstrap.Modal(document.getElementById('editClientModal')).show();
        }).fail(() => Toast.error('Could not load client details.'));
    });

    $('#saveClientBtn').on('click', function () {
        const id = $('#edit_client_id').val();
        clearClientErrors();
        const payload = {
            csrf_token: window.CSRF_TOKEN,
            name: $('#edit_client_name').val(),
            surname: $('#edit_client_surname').val(),
            id_number: $('#edit_client_id_number').val(),
            account_number: $('#edit_client_account_number').val(),
            phone: $('#edit_client_phone').val(),
        };
        $.post(window.APP_URL + '/clients/' + id + '/update', payload, function (res) {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('editClientModal')).hide();
                Toast.success('Client updated successfully.');
                clientDt.ajax.reload(null, false);
            } else if (res.errors) {
                showClientErrors(res.errors);
            } else {
                Toast.error(res.message || 'Could not save changes.');
            }
        }).fail(function (xhr) {
            const res = xhr.responseJSON;
            if (res && res.errors) { showClientErrors(res.errors); return; }
            Toast.error((res && res.message) || 'Could not save changes.');
        });
    });

    // ---- Delete client ----
    $('#clientTable tbody').on('click', '.delete-client-btn', async function () {
        const id = $(this).data('id');
        const ok = await Toast.confirm('Delete this client? This cannot be undone.', { type: 'warning', confirmLabel: 'Delete' });
        if (!ok) return;
        $.post(window.APP_URL + '/clients/' + id + '/delete', { csrf_token: window.CSRF_TOKEN }, function (res) {
            if (res.success) {
                Toast.success('Client deleted.');
                clientDt.ajax.reload(null, false);
            } else {
                Toast.error(res.message || 'Could not delete client.');
            }
        }).fail(function (xhr) {
            const res = xhr.responseJSON;
            Toast.error((res && res.message) || 'Could not delete client. It may still have records referencing it.');
        });
    });
});
