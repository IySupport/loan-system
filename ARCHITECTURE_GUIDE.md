# Loan Management System — Architecture Guide

A from-scratch walkthrough of how this project is put together: folder structure, the
front-controller/router/MVC plumbing in `core/`, and two fully traced requests
(login, and capturing a loan) showing exactly which file hands off to which, all the
way from a browser click down to a SQL query and back.

---

## 1. Why the folders are split this way

```
config/       - environment + constants (loaded once, first, by every request)
core/         - the "framework": Router, base Controller, base Model, Database, Auth
models/       - one class per database table/view - the ONLY files that write SQL
controllers/  - one class per feature - reads input, calls models, picks a view
views/        - pure HTML/PHP templates - no SQL, no business logic
public/       - the web root - the only folder your web server actually exposes
database/     - schema.sql, seed.sql, migrations - never touched at runtime
```

This is the MVC pattern. The rule it enforces: **a view never talks to a model
directly, and a model never renders HTML.** Every request is forced through
`Controller -> Model -> Controller -> View`, so "is my data wrong?" always means
"look at the model", and "does my page look wrong?" always means "look at the view".

---

## 2. The front controller - everything starts in one file

Only one PHP file is ever reachable from the browser: `public/index.php`.
`public/.htaccess` rewrites every URL to it:

```apache
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^ index.php [L]
```

This is the **front controller pattern**: one gate everything passes through, so
setup work (config, sessions, autoloading) happens exactly once instead of being
repeated at the top of every page file. `public/index.php` does four things, in order:

```php
require dirname(__DIR__) . '/config/config.php';   // 1. environment + session

spl_autoload_register(function ($class) {           // 2. class autoloading
    foreach (['core', 'models', 'controllers'] as $dir) {
        $path = APP_ROOT . "/{$dir}/{$class}.php";
        if (file_exists($path)) { require $path; return; }
    }
});

$router = new Router();                              // 3. route table
$router->get('/dashboard', ['DashboardController', 'index']);
// ... every other route ...

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']); // 4. go
```

**Step 2 is why no class file is ever `require`d by hand.** The moment PHP hits
`new LoanController()` anywhere, PHP doesn't know the class yet, so it calls the
autoloader, which checks `controllers/LoanController.php`, then
`models/LoanController.php`, then `core/LoanController.php`, in that order, until
it finds the file. This is why `models/BranchBudget.php` and
`controllers/BudgetController.php` worked instantly when they were created - no
registration step anywhere else in the codebase.

`config/config.php` runs first and sets up everything downstream depends on:
- reads `.env` into `getenv()`/`$_ENV` (so `env('DB_HOST')` works anywhere)
- defines `APP_ROOT`, `APP_NAME`, `APP_URL` as constants
- calls `session_start()` - this is what makes `$_SESSION['user']` available
  in every controller
- turns on error display for debugging

---

## 3. The `core/` layer - four small classes that do all the plumbing

**`core/Database.php`** - a singleton. The first model that asks for a connection
builds one PDO instance; every model after that reuses it (no reconnecting per query):

```php
public static function connect(): PDO {
    if (self::$instance === null) { self::$instance = new PDO($dsn, $user, $pass, [...]); }
    return self::$instance;
}
```

**`core/Model.php`** - the base class every model extends. Holds the `query()`
helper every model method calls:

```php
protected function query(string $sql, array $params = []): PDOStatement {
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
```
Every query in the app goes through `prepare()`/`execute()` with named parameters
(`:id`, `:branch_id`, etc). No model ever concatenates a variable directly into
SQL text - that's what makes the app immune to SQL injection.

**`core/Auth.php`** - wraps `$_SESSION['user']`. `Auth::attempt()` checks the
password and sets the session; `Auth::requireLogin()` is called at the top of
almost every controller method:
```php
if (!self::check()) { header('Location: ' . APP_URL . '/login'); exit; }
```

**`core/Router.php`** - a route is `[HTTP method, URL pattern, [ControllerClass, method]]`.
`dispatch()` loops the registered routes, turns `/loans/{id}/edit` into a regex,
and on a match does `new LoanController()` then
`call_user_func_array([$controller, 'editForm'], $params)`. That's the whole
routing engine - no magic, ~35 lines.

**`core/Controller.php`** - every controller extends this for two shared helpers:
- `view($name, $data, $layout)` - renders a view (see section 6)
- `json($data, $status)` - for AJAX endpoints; sets headers, echoes `json_encode($data)`

---

## 4. Trace #1 - a user logging in

1. Browser: `GET /login` -> rewritten to `public/index.php` -> `Router::dispatch()`
   matches `AuthController::showLogin`.
2. `AuthController::showLogin()`:
   ```php
   if (Auth::check()) $this->redirect('/dashboard');
   $this->view('auth/login', ['error' => null], null);
   ```
   The third argument `null` means "no layout" - `views/auth/login.php` is a
   complete standalone `<html>` page (login screens shouldn't have the sidebar).
3. Browser renders the form; user submits -> `POST /login` with
   `username`/`password`.
4. `AuthController::login()` calls `Auth::attempt($username, $password)`:
   ```php
   $user = (new User())->findByUsername($username);          // SELECT
   if (!password_verify($password, $user['password_hash'])) return false;
   session_regenerate_id(true);                               // stop session fixation
   $_SESSION['user'] = ['id'=>..., 'full_name'=>..., 'role'=>...];
   ```
5. On success: `$this->redirect('/dashboard')` - a real HTTP 302, so the browser
   makes a *fresh* `GET /dashboard` request. This matters: a page refresh never
   resubmits the login form.
6. `DashboardController::index()` - `Auth::requireLogin()` now passes because
   `$_SESSION['user']` exists. It gathers KPIs from `Loan` and `BranchBudget`
   models, then:
   ```php
   $this->view('dashboard/index', [...], 'layouts/app');  // default layout this time
   ```

---

## 5. How a view becomes a full page - the `$content` trick

This is the one piece of "magic" in the project, worth understanding precisely
since every page uses it. `Controller::view()`:

```php
protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void {
    extract($data);                          // turns $data['kpis'] into a local $kpis variable
    ob_start();                              // start capturing output instead of printing it
    require APP_ROOT . "/views/{$view}.php"; // e.g. dashboard/index.php runs, echoing HTML
    $content = ob_get_clean();               // grab everything it echoed into a string

    require APP_ROOT . "/views/{$layout}.php"; // NOW render the layout, which can use $content
}
```

`views/dashboard/index.php` never knows about the sidebar - it just outputs its
own HTML fragment (KPI cards, charts, tables). `views/layouts/app.php` is the
actual `<html>` shell - sidebar, topbar, `<script>` tags - and at the exact spot
where the page content belongs, it does:

```php
<main class="page-content">
    <?= $content ?>
</main>
```

Two more variables ride along on this same mechanism: every view can set
`$pageTitle` (shown in the topbar) and `$pageScripts` (a `<script>` tag string)
*before* the layout runs, because the view executes first and those variables
are still in scope when the layout is included afterward. This is how each page
loads its own JS file without a bundler:

```php
// bottom of views/loans/capture.php
<?php $pageScripts = '<script src="' . APP_URL . '/assets/js/capture.js"></script>'; ?>
```
```php
// bottom of views/layouts/app.php
<?= $pageScripts ?? '' ?>
```

---

## 6. Trace #2 - capturing a loan, the full round trip

This is where every layer gets exercised, including AJAX. Following it end to end:

### a) Loading the form
`GET /loans/capture` -> `LoanController::captureForm()`:
```php
$this->view('loans/capture', array_merge($this->lookups(), ['csrf' => $this->csrfToken()]));
```
`lookups()` runs three queries - `Branch::activeBranches()`, `LoanStatus::all()`,
`RepaymentStatus::all()` - and hands the results to the view to populate the
`<select>` dropdowns server-side. `csrfToken()` generates a random token stored
in `$_SESSION['csrf_token']` and drops it into a hidden `<input>` - checked on
every write so a malicious site can't silently submit forms on the user's behalf.

### b) Typing an ID Number - client identification, live
`capture.js` has a debounced `input` listener on the ID field:
```js
fetch(APP_URL + '/clients/lookup?id_number=' + idNumber)
```
-> `ClientController::lookup()` -> `Client::findByIdNumber()` (a
`SELECT ... WHERE id_number = :i`, hitting the unique constraint on that column)
-> if found, `Client::loanCount()` runs `SELECT COUNT(*) FROM loans WHERE client_id = :id`.
The controller returns JSON, and `capture.js` autofills Name/Surname/Account and
updates the read-only "Loan Count"/"Group" boxes. **Nothing here writes to the
database** - it's pure lookup, so typing, deleting, retyping an ID Number is free
of side effects.

### c) Picking a branch - budget status, live
`capture.js`'s `change` listener on the branch `<select>` fires:
```js
fetch(APP_URL + '/budgets/status?branch_id=' + id + '&month=' + month)
```
-> `BudgetController::status()` -> `BranchBudget::find()` (this month's allocated
amount) + `Loan::spentForBranchMonth()` (sums `amount` for loans at that branch
with Loan Status `Disbursed`/`Closed` this month) -> JSON
`{branch:{allocated,spent,remaining}, company:{...}}`. JS paints the budget box
and turns the warning red if `amount > remaining`.

### d) Clicking "Save Loan" - the actual write
`capture.js`'s submit handler builds a `FormData` (name, surname, id_number,
amount, branch_id, loan_status_id, repayment_status_id, action_date, notes,
csrf_token) and does `POST /loans`.

`LoanController::store()`:
```php
Auth::requireLogin();
if (!$this->verifyCsrf()) $this->json([...], 419);
$errors = $this->validate($_POST);
if ($errors) $this->json(['success'=>false,'errors'=>$errors], 422);   // JS shows red field errors
$result = (new Loan())->create([...]);
$this->json(['success' => true, 'loan' => $result]);
```

`Loan::create()` is the heart of the business logic, wrapped in a **database
transaction** so a half-finished loan is never saved:
```php
$this->db->beginTransaction();
$client = (new Client())->findOrCreate([...]);   // reuse existing client, or INSERT a new one
$reference = $this->nextReferenceNumber();        // LN-20260722-0001
$this->query("INSERT INTO loans (...) VALUES (...)", [...]);
$this->db->commit();
```
`findOrCreate()` is what makes "a person can borrow multiple times" work: it runs
`SELECT ... WHERE id_number = :i` first, and only `INSERT`s if nothing came back -
so the same client row is reused across every loan they ever take, which is
exactly why `loan_count` for that client naturally increases each time.

`nextReferenceNumber()` has its own nested transaction against a tiny
`daily_counters` table (`counter_date`, `last_value`), doing an atomic
`UPDATE ... SET last_value = last_value + 1 ... RETURNING last_value`. That's
what guarantees `LN-20260722-0001` never collides even if two operators save a
loan in the same second.

### e) The response, back in the browser
The controller returns `{success:true, loan:{reference_number, loan_count, group, ...}}`.
`capture.js` shows the reference number, calls `form.reset()`, and hides the
budget box. No page reload happens at any point in steps b-e - it's all
`fetch()`/AJAX against JSON endpoints.

---

## 7. Where "Loan Count" and "Group" actually live

Nowhere, as a stored value - deliberately. `database/schema.sql` defines a
**view**, `loan_register_view`, that every read (Register, Reports, Export,
Dashboard) queries instead of the raw `loans` table:

```sql
CREATE VIEW loan_register_view AS
SELECT ..., lc.loan_count,
    CASE WHEN lc.loan_count BETWEEN 1 AND 3 THEN 'Group 1'
         WHEN lc.loan_count BETWEEN 4 AND 8 THEN 'Group 2'
         ELSE 'Group 3' END AS loan_group, ...
FROM loans l
JOIN (SELECT client_id, COUNT(*) AS loan_count FROM loans GROUP BY client_id) lc
    ON lc.client_id = l.client_id
...
```
Every time `SELECT * FROM loan_register_view` runs, PostgreSQL recalculates the
count and group live from however many rows currently exist in `loans` for that
client. This is why a Group can never drift out of sync - it's mathematically
impossible for it to disagree with the actual loan count, because it's computed
from it on every single read.

---

## 8. Loan Register - the same pieces, driven by DataTables instead of a form

`views/loans/register.php` renders an empty `<table>`; `register.js` boots
DataTables in **server-side mode**, meaning DataTables never holds the full
dataset - every page turn, sort, or filter change fires:
```js
$.get(APP_URL + '/loans/data', {draw, start, length, order_col, order_dir, ...filters})
```
-> `LoanController::listData()` -> `Loan::registerList()`, which builds a dynamic
`WHERE` clause from whichever filters are non-empty (`Loan::buildFilterClause()`),
runs a `COUNT(*)` and a paginated `SELECT` against `loan_register_view`, and
returns rows as JSON. DataTables' `columns` config in `register.js` maps each
JSON field to a `<td>` renderer - that's where `status` becomes
`<span class="badge-status status-approved">Approved</span>`, with the CSS class
built dynamically from the status name (`statusBadgeClass()` in `app.js`). This
is exactly why the Repayment Status refactor never had to touch any
badge-generation code - only the CSS class *definitions* needed new entries.

---

## 9. Quick reference - route to file map

| Request | Controller method | Model(s) touched | View / response |
|---|---|---|---|
| `GET /login` | `AuthController::showLogin` | `User` (only on POST) | `auth/login.php` (no layout) |
| `POST /login` | `AuthController::login` | `User` | redirect -> `/dashboard` |
| `GET /dashboard` | `DashboardController::index` | `Loan`, `BranchBudget` | `dashboard/index.php` + `layouts/app.php` |
| `GET /loans/capture` | `LoanController::captureForm` | `Branch`, `LoanStatus`, `RepaymentStatus` | `loans/capture.php` |
| `GET /clients/lookup` | `ClientController::lookup` | `Client` | JSON |
| `GET /budgets/status` | `BudgetController::status` | `BranchBudget`, `Loan` | JSON |
| `POST /loans` | `LoanController::store` | `Loan` (which uses `Client` internally) | JSON |
| `GET /loans/data` | `LoanController::listData` | `Loan` (via `loan_register_view`) | JSON (DataTables format) |
| `GET /export/*` | `ExportController::*` | `Loan` | `.xlsx` file stream |
