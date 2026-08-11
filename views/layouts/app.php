<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.11/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="<?= APP_URL ?>/assets/css/style.css" rel="stylesheet">
<!-- <link rel="icon" type="image/png" href="<?= APP_URL ?>/public/assets/images/iylogo.png"> -->
<!-- <link rel="icon" type="image/png" href="assets/images/iylogo.png"> -->
<link rel="icon" type="image/png" href="/assets/images/iylogo.png">


</head>
<body>
<div class="app-shell">

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon"><i class="bi bi-bank2"></i></span>
            <div>
                <div class="brand-title">IY-Loan-Management</div>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr(Auth::user()['full_name'] ?? 'U', 0, 1)) ?></div>
            <div>
                <div class="user-name"><?= htmlspecialchars(Auth::user()['full_name'] ?? '') ?></div>
                <div class="user-role"><?= htmlspecialchars(Auth::user()['role'] ?? '') ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= APP_URL ?>/dashboard" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/dashboard')?'active':'' ?>">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>
            <a href="<?= APP_URL ?>/loans/capture" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/loans/capture')?'active':'' ?>">
                <i class="bi bi-plus-square-fill"></i><span>Capture Loan</span>
            </a>
            <a href="<?= APP_URL ?>/loans/register" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/loans/register')?'active':'' ?>">
                <i class="bi bi-table"></i><span>Loan Register</span>
            </a>
            <a href="<?= APP_URL ?>/clients" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/clients')?'active':'' ?>">
                <i class="bi bi-person-lines-fill"></i><span>Clients</span>
            </a>
            <a href="<?= APP_URL ?>/reports" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/reports')?'active':'' ?>">
                <i class="bi bi-bar-chart-fill"></i><span>Reports</span>
            </a>
            <a href="<?= APP_URL ?>/branches" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/branches')?'active':'' ?>">
                <i class="bi bi-diagram-3-fill"></i><span>Branches</span>
            </a>
            <?php if (Auth::isAdmin()): ?>
            <a href="<?= APP_URL ?>/budgets" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/budgets')?'active':'' ?>">
                <i class="bi bi-wallet2"></i><span>Branch Budgets</span>
            </a>
            <?php endif; ?>
            <?php if (Auth::isAdmin()): ?>
            <a href="<?= APP_URL ?>/users" class="nav-link <?= str_contains($_SERVER['REQUEST_URI'],'/users')?'active':'' ?>">
                <i class="bi bi-people-fill"></i><span>Users</span>
            </a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/settings" class="nav-link">
                <i class="bi bi-gear-fill"></i><span>Settings</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= APP_URL ?>/logout" class="nav-link logout-link">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </div>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Main -->
    <div class="main-content">
        <header class="topbar">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
            <div class="topbar-right">
                <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('l, j F Y') ?></span>
            </div>
        </header>

        <main class="page-content">
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>window.APP_URL = "<?= APP_URL ?>";</script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script src="<?= APP_URL ?>/assets/js/toast.js"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>
