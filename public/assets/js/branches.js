$(function () {
    $('#saveBranchBtn').on('click', function () {
        const name = $('#newBranchName').val().trim();
        if (!name) { Toast.warning('Branch name is required.'); return; }
        $.post(window.APP_URL + '/branches', { csrf_token: window.CSRF_TOKEN, branch_name: name }, function (res) {
            if (res.success) { location.reload(); } else { Toast.error(res.message || 'Could not create branch.'); }
        }).fail(function (xhr) {
            const msg = xhr.responseJSON?.message || 'Could not create branch.';
            Toast.error(msg);
        });
    });

    $('.edit-branch-btn').on('click', function () {
        $('#editBranchId').val($(this).data('id'));
        $('#editBranchName').val($(this).data('name'));
        $('#editBranchStatus').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editBranchModal')).show();
    });

    $('#updateBranchBtn').on('click', function () {
        const id = $('#editBranchId').val();
        const payload = { csrf_token: window.CSRF_TOKEN, branch_name: $('#editBranchName').val().trim(), status: $('#editBranchStatus').val() };
        $.post(window.APP_URL + '/branches/' + id + '/update', payload, function (res) {
            if (res.success) location.reload(); else Toast.error(res.message || 'Could not update branch.');
        }).fail(function (xhr) { Toast.error(xhr.responseJSON?.message || 'Could not update branch.'); });
    });

    $('.toggle-branch-btn').on('click', async function () {
        const id = $(this).data('id');
        const ok = await Toast.confirm('Change status of this branch?', { type: 'warning', confirmLabel: 'Confirmtion' });
        if (!ok) return;
        // if (!confirm('Change status of this branch?')) return;
        $.post(window.APP_URL + '/branches/' + id + '/toggle', { csrf_token: window.CSRF_TOKEN }, function (res) {
            if (res.success) location.reload(); else Toast.error("Action failed.");
        });
    });
});
