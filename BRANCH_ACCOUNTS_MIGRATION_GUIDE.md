# Branch Accounts Migration Guide

This package was prepared from the current working project at Git commit
`6cdc425` (`added bank details`). The Branch Account changes were reconstructed
from their original base (`f8a0485`) and merged on top of `6cdc425`.

The merged result preserves:

- bank selection and `bank_id`;
- the 40% interest calculation;
- `workplace_name` and `work_contact`;
- the delete-confirmation fix;
- the Action Date timezone fix;
- the Neon transaction fix; and
- the Excel export fixes.

## Part 1 — Back up before changing anything

1. Make a ZIP copy of the working project.
2. Export the Neon PostgreSQL database with `pg_dump` or create a safe database
   backup using the database tools available to you.
3. Open a terminal in the working project and confirm the version:

   ```bash
   git rev-parse --short HEAD
   ```

   It should return `6cdc425`.
4. Confirm the project does not contain uncommitted work:

   ```bash
   git status
   ```

5. Create a migration branch:

   ```bash
   git switch -c feature/branch-accounts
   ```

## Part 2 — Apply the exact code changes

Choose one method only.

### Method A: use the ready-merged project

Use the files from `loan-system-branch-accounts-merged.zip`. Do not overwrite
your `.env` file. The prepared ZIP deliberately excludes `.env`, `.git`, and
`vendor`.

### Method B: apply the exact patch

Place `branch-accounts-on-6cdc425.patch` in the root of the current project and
run:

```bash
git apply --check -p1 branch-accounts-on-6cdc425.patch
git apply -p1 branch-accounts-on-6cdc425.patch
```

The first command must complete without an error. If it reports that a patch
does not apply, stop instead of forcing it.

## Part 3 — What the patch changes, file by file

1. `database/migration_branch_accounts.sql`
   - Adds nullable `users.branch_id` referencing `branches.id`.
   - Adds `idx_users_branch`.
   - Allows `Administrator`, `Operator`, and `Branch` roles.

2. `core/Auth.php`
   - Stores the user's branch ID and branch name in the session.
   - Adds `isBranch()`, `branchId()`, `landingPath()`, and `requireStaff()`.

3. `models/User.php`
   - Returns the assigned branch name with users.
   - Saves and updates `branch_id`.
   - Loads the branch name during authentication.

4. `controllers/UserController.php`
   - Supplies branches to User Management.
   - Validates that a Branch account has exactly one assigned branch.
   - Forces `branch_id` to `NULL` for Administrators and Operators.

5. `controllers/AuthController.php`
   - Sends Branch accounts to Loan Register after login.

6. `views/users/index.php` and `public/assets/js/users.js`
   - Add the Branch role.
   - Add the branch selector to Add User and Edit User.
   - Show the branch selector only when the Branch role is selected.

7. `controllers/LoanController.php`
   - Keeps bank lookup and `bank_id` validation.
   - Lets Administrators and Operators capture loans for any selected branch.
   - Forces a Branch account's `branch_id` from the login session.
   - Forces new Branch loans to `Pending Review` and `Not Due`.
   - Scopes Loan Register results to the logged-in branch.
   - Prevents a Branch account from opening another branch's loan.
   - Prevents Branch accounts from changing the branch or statuses during edit.
   - Makes bulk status, repayment-status, and delete actions staff-only.

8. `views/loans/capture.php` and `public/assets/js/capture.js`
   - Locks the branch selector for Branch accounts.
   - Shows the automatically assigned initial statuses.
   - Keeps bank selection available.
   - Loads the branch budget even though the branch selector is locked.

9. `views/loans/register.php` and `public/assets/js/register.js`
   - Hides branch filtering and bulk actions from Branch accounts.
   - Shows statuses as read-only during Branch-account editing.
   - Hides Delete from Branch accounts.
   - Preserves `bank_id` population and saving.

10. `controllers/BudgetController.php`
    - Forces Branch budget requests to the logged-in branch.
    - Withholds company-wide totals from Branch accounts.

11. `controllers/DashboardController.php`, `BranchController.php`,
    `ReportController.php`, `ExportController.php`, and `ClientController.php`
    - Restrict management screens/actions to Administrators and Operators.
    - Keep the client lookup used by Capture Loan available.

12. `views/layouts/app.php`
    - Hides restricted navigation from Branch accounts.
    - Shows the assigned branch name.

13. `database/schema.sql` and `database/seed.sql`
    - Update fresh installations without removing the bank schema or seed data.

## Part 4 — Inspect and commit locally

Run:

```bash
git diff --check
git status
```

Confirm that `.env` is not listed. Then run PHP syntax checks in your local
environment:

```bash
php -l core/Auth.php
php -l models/User.php
php -l controllers/AuthController.php
php -l controllers/UserController.php
php -l controllers/LoanController.php
php -l controllers/BudgetController.php
php -l views/users/index.php
php -l views/loans/capture.php
php -l views/loans/register.php
php -l views/layouts/app.php
```

Commit the code only after those checks pass:

```bash
git add core models controllers views public database
git commit -m "Add branch-level accounts and permissions"
```

## Part 5 — Apply the Neon database migration

1. Sign in to Neon.
2. Open the production project and correct database.
3. Open SQL Editor.
4. Open `database/migration_branch_accounts.sql` locally.
5. Copy all its SQL into Neon SQL Editor.
6. Run it once.
7. Verify the result:

   ```sql
   SELECT id, full_name, username, role, branch_id
   FROM users
   ORDER BY id;
   ```

Existing Administrator and Operator accounts should have `NULL` in
`branch_id`. That is correct.

## Part 6 — Test all roles before deployment

Start the local application:

```bash
php -S localhost:8000 -t public
```

Test an Administrator and Operator:

- Both can capture loans.
- Both can select any branch.
- Both can select bank, Loan Status, and Repayment Status.
- Both retain Dashboard, Reports, Exports, Branches, Clients, and Users access.

Create and test a Branch account:

- It must be assigned to one branch.
- It lands on Loan Register.
- It sees loans belonging only to its branch.
- Capture Loan is locked to its branch.
- It can still choose the client's bank.
- New loan statuses are automatically `Pending Review` and `Not Due`.
- It cannot change branch, Loan Status, or Repayment Status during edit.
- It cannot delete loans or run bulk actions.
- Restricted navigation is hidden.
- Manually entering restricted URLs results in HTTP 403.

## Part 7 — Deploy

Push the tested feature branch:

```bash
git push -u origin feature/branch-accounts
```

After final approval, merge it:

```bash
git switch main
git merge feature/branch-accounts
git push origin main
```

Wait for Render to finish deploying, inspect its logs, and repeat the
Administrator, Operator, and Branch tests on the live site.

## Rollback

If code deployment fails, redeploy the previous Render/Git commit. The new
database column is nullable and does not prevent the earlier code from running.
Do not delete the column during an emergency rollback; restoring the previous
code is the safer first action.
