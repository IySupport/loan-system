<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <span class="brand-icon"><i class="bi bi-bank2"></i></span>
                <h1>IY Loan Management</h1>
                <p>Sign in to access your dashboard</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= APP_URL ?>/login">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" name="username" placeholder="Enter your username" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-brand w-100 mt-2">Sign In</button>
            </form>
            <p class="login-footnote">Loan Processing &amp; Tracking System</p>
        </div>
    </div>
</body>
</html>
