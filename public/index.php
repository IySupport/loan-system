<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require dirname(__DIR__) . '/config/config.php';

spl_autoload_register(function ($class) {
    $dirs = ['core', 'models', 'controllers'];
    foreach ($dirs as $dir) {
        $path = APP_ROOT . "/{$dir}/{$class}.php";
        if (file_exists($path)) {
            require $path;
            return;
        }
    }
});

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require APP_ROOT . '/vendor/autoload.php';
}

$router = new Router();

// Auth
$router->get('/login',  ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);

// Dashboard
$router->get('/dashboard', ['DashboardController', 'index']);

// Client AJAX lookup + Clients admin page (search, view, update, delete)
$router->get('/clients',              ['ClientController', 'index']);
$router->get('/clients/data',         ['ClientController', 'listData']);
$router->get('/clients/lookup',       ['ClientController', 'lookup']);
$router->get('/clients/{id}',         ['ClientController', 'show']);
$router->post('/clients/{id}/update', ['ClientController', 'update']);
$router->post('/clients/{id}/delete', ['ClientController', 'delete']);

// Loans - Capture
$router->get('/loans/capture',  ['LoanController', 'captureForm']);
$router->post('/loans',         ['LoanController', 'store']);

// Loans - Register + DataTables + Edit + Bulk
$router->get('/loans/register',            ['LoanController', 'registerView']);
$router->get('/loans/data',                ['LoanController', 'listData']);
$router->get('/loans/{id}/edit',           ['LoanController', 'editForm']);
$router->post('/loans/{id}/update',        ['LoanController', 'update']);
$router->post('/loans/bulk-status',        ['LoanController', 'bulkStatus']);
$router->post('/loans/bulk-repayment-status', ['LoanController', 'bulkRepaymentStatus']);
$router->post('/loans/bulk-delete',        ['LoanController', 'bulkDelete']);

// Exports
$router->get('/export/selected',            ['ExportController', 'exportSelected']);
$router->get('/export/filtered',            ['ExportController', 'exportFiltered']);
$router->get('/export/all',                 ['ExportController', 'exportAll']);
$router->get('/export/group/{group}',       ['ExportController', 'exportByGroup']);
$router->get('/export/branch/{branchId}',   ['ExportController', 'exportByBranch']);

// Reports
$router->get('/reports',          ['ReportController', 'index']);
$router->get('/reports/generate', ['ReportController', 'generate']);
$router->get('/reports/payments-due-today', ['ReportController', 'paymentsDueToday']);

// Branches
$router->get('/branches',              ['BranchController', 'index']);
$router->post('/branches',             ['BranchController', 'store']);
$router->post('/branches/{id}/update', ['BranchController', 'update']);
$router->post('/branches/{id}/toggle', ['BranchController', 'deactivate']);

// Users (Administrator only)
$router->get('/users',                       ['UserController', 'index']);
$router->post('/users',                      ['UserController', 'store']);
$router->post('/users/{id}/update',          ['UserController', 'update']);
$router->post('/users/{id}/reset-password',  ['UserController', 'resetPassword']);
$router->post('/users/{id}/toggle',          ['UserController', 'deactivate']);


// Settings
$router->get('/settings',          ['SettingsController', 'index']);
$router->post('/settings/password',['SettingsController', 'changePassword']);

// Branch Budgets (monthly loan-out allocation per branch)
$router->get('/budgets',        ['BudgetController', 'index']);   // admin-only management screen
$router->get('/budgets/list',   ['BudgetController', 'list']);    // admin-only AJAX table data
$router->post('/budgets/save',  ['BudgetController', 'save']);    // admin-only AJAX save
$router->get('/budgets/status', ['BudgetController', 'status']);  // any logged-in user - used by Capture Loan

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
