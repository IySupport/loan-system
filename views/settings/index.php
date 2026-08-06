<?php $pageTitle = 'Settings'; ?>

<div class="panel-card" style="max-width:480px;">
    <h6 class="panel-title"><i class="bi bi-shield-lock-fill me-1"></i> Change Password</h6>
    <form id="changePasswordForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="mb-3">
            <label class="form-label">Current Password *</label>
            <input type="password" class="form-control" name="current_password" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password *</label>
            <input type="password" class="form-control" name="new_password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary-brand">Update Password</button>
    </form>
</div>

<?php $pageScripts = '<script>
$(function () {
    $("#changePasswordForm").on("submit", function (e) {
        e.preventDefault();
        $.post(window.APP_URL + "/settings/password", $(this).serialize(), function (res) {
            if (res.success) { alert("Password updated successfully."); $("#changePasswordForm")[0].reset(); }
            else alert(res.message || "Could not update password.");
        }).fail(function (xhr) { alert(xhr.responseJSON?.message || "Could not update password."); });
    });
});
</script>'; ?>
