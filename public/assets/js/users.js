$(function () {
    $('#saveUserBtn').on('click', function () {
        const payload = {
            csrf_token: window.CSRF_TOKEN,
            full_name: $('#newFullName').val().trim(),
            username: $('#newUsername').val().trim(),
            password: $('#newPassword').val(),
            role: $('#newRole').val(),
        };
        $.post(window.APP_URL + '/users', payload, function (res) {
            if (res.success) location.reload();
            else Toast.error(Object.values(res.errors || { m: res.message }).join('\n'));
        }).fail(function (xhr) {
            Toast.error(Object.values(xhr.responseJSON?.errors || { m: 'Could not create user.' }).join('\n'));
        });
    });

    $('.edit-user-btn').on('click', function () {
        $('#editUserId').val($(this).data('id'));
        $('#editFullName').val($(this).data('name'));
        $('#editUsername').val($(this).data('username'));
        $('#editRole').val($(this).data('role'));
        $('#editStatus').val($(this).data('status'));
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    });

    $('#updateUserBtn').on('click', function () {
        const id = $('#editUserId').val();
        const payload = {
            csrf_token: window.CSRF_TOKEN,
            full_name: $('#editFullName').val().trim(),
            username: $('#editUsername').val().trim(),
            role: $('#editRole').val(),
            status: $('#editStatus').val(),
        };
        $.post(window.APP_URL + '/users/' + id + '/update', payload, function (res) {
            if (res.success) location.reload();
            else Toast.error(Object.values(res.errors || { m: res.message }).join('\n'));
        }).fail(function (xhr) {
            Toast.error(Object.values(xhr.responseJSON?.errors || { m: 'Could not update user.' }).join('\n'));
        });
    });

    $('.reset-pw-btn').on('click', function () {
        $('#resetPwUserId').val($(this).data('id'));
        $('#resetPwValue').val('');
        new bootstrap.Modal(document.getElementById('resetPwModal')).show();
    });

    $('#confirmResetPwBtn').on('click', function () {
        const id = $('#resetPwUserId').val();
        const pw = $('#resetPwValue').val();
        if (pw.length < 6) { Toast.warning('Password must be at least 6 characters.'); return; }
        $.post(window.APP_URL + '/users/' + id + '/reset-password', { csrf_token: window.CSRF_TOKEN, password: pw }, function (res) {
            if (res.success) { bootstrap.Modal.getInstance(document.getElementById('resetPwModal')).hide(); Toast.success('Password reset successfully.'); }
            else Toast.error(res.message || 'Could not reset password.');
        });
    });

    $('.toggle-user-btn').on('click', async function () {
        const id = $(this).data('id');
        const ok = await Toast.confirm('Change status of this user?', { type: 'warning', confirmLabel: 'Confirmtion' });
        if (!ok) return;
        // if (!confirm('Change status of this user?')) return;
        $.post(window.APP_URL + '/users/' + id + '/toggle', { csrf_token: window.CSRF_TOKEN }, function (res) {
            if (res.success) location.reload(); else Toast.error('Action failed.');
        });
    });
});
