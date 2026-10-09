# Loan Register: Cards view, readable table, Excel fixes

Patch: `loan-register-cards-view.patch` (git format, same style as your earlier patches).

## Apply

From the project root:

```bash
git apply --check loan-register-cards-view.patch   # dry run
git apply loan-register-cards-view.patch
```

If git says `patch does not apply` (your working files are CRLF, the patch is LF):

```bash
git apply --ignore-whitespace --whitespace=nowarn loan-register-cards-view.patch
```

Still conflicts because of your own local edits? Use `git apply --reject loan-register-cards-view.patch`.
It applies every hunk it can and leaves the rest in `*.rej` files. The two new files never conflict.
Undo everything with `git apply -R loan-register-cards-view.patch`.

No database change, no composer change. Hard-refresh the browser once after deploying.

## Files

| File | Change |
|---|---|
| `views/loans/register.php` | Table/Cards toggle, cards container, fixed scrollbar element, loads the new CSS/JS (with cache-busting `?v=`) |
| `public/assets/js/register-views.js` | **new**: view toggle, card rendering, pinned-column offsets, fixed scrollbar |
| `public/assets/css/loan-register.css` | **new**: all styling for this page only (nothing added to `style.css`) |
| `public/assets/js/register.js` | sort-column map fixed, text escaping added (see "Bugs found"), Date Loaded formatting |
| `controllers/ExportController.php` | Excel export fixes (see below) |
| `models/Loan.php` | one word: `'bank_name'` added to `SORTABLE` |

## What you get

**Table / Cards toggle.** Two buttons above the list. The choice is yours, never decided by screen size, and
is remembered per browser. Both views use the same data, so filters, sorting, paging, search and the selected
rows are shared. Selecting in one view shows in the other, and the bulk bar works from either.
Cards have their own "Select all on page" and a **Sort by** dropdown, since cards have no clickable headers.
Card Edit/Delete use the exact same handlers as the table buttons (Branch accounts still get Edit only).

**Readable table.** Cells no longer wrap or squash. Every column keeps its natural width on one line and the
table scrolls sideways.

**Pinned columns.** Name, Surname and ID Number stay on the left while you scroll right, with a soft
shadow once content slides under them. The checkbox and Ref No. sit *before* Name, so they scroll away like
the other columns. On phones (< 576px) only Name is pinned, because three pinned columns would fill the screen.

**Fixed horizontal scrollbar.** A scrollbar is pinned to the bottom of the screen and mirrors the table's scroll
position (drag it and the table follows). It appears only while the table's own scrollbar is below the fold, and
hides itself once you scroll down to the real one.

**Colour loop.** Table rows and cards alternate smoke white (`#f5f5f3`) and greenish white (`#e8f5ee`).
Change them in one place: the `--lr-row-a` / `--lr-row-b` variables at the top of `loan-register.css`.
Hover and selected rows have their own tints.

**Card design.** Avatar initials (stable colour per client), name, ID number, group pill, reference and loan
number, a money strip (Amount / Interest / **Amount due**), status badges, then Branch, Workplace, Work contact,
Client phone, Bank, Account no., Action date and Date loaded. Work/client phone numbers are tap-to-call. The left
edge is coloured by Loan Status.

## Excel export audit

I read all five exports (selected, filtered, all, by group, by branch) plus the Reports-page export. They all share
one `stream()` method and the same `loan_register_view`, so the numbers match the screen. Problems found, all fixed:

| # | Problem | Fix |
|---|---|---|
| 1 | **ID numbers came out as numbers.** The library stores numeric-looking strings as numbers (I checked your vendored PhpSpreadsheet 1.30.6), so Excel shows `9001011234567` as `9.00101E+12`. Account and phone numbers lose digits the same way | ID, account, work-contact and all text columns are written as explicit text |
| 2 | **Bank Name and Workplace missing**, although both are on the register screen | added |
| 3 | **Interest Amount, Amount Due, Work Contact missing**. Amount alone understates what the client owes | added |
| 4 | **Dates were text**, so Excel date filters/sorting don't work | now real Excel dates, shown `yyyy-mm-dd` |
| 5 | Amounts had no format | now `R #,##0.00` |
| 6 | **Export Selected with an empty list exported the whole register** (an empty id list adds no WHERE clause) | now returns "No rows selected" (HTTP 400) |
| 7 | Any text starting with `=` was stored as a **formula** (e.g. a workplace typed as `=HYPERLINK(...)`) | text is never evaluated |
| 8 | Header said "Status" | now "Loan Status" (matches the screen) |

Also added: header-row auto-filter. Column order now follows the register screen (18 columns, A to R).

Checked and fine: the filter keys sent by "Export Filtered" and the Reports page match what `Loan::buildFilterClause()`
reads; group and branch exports use the same view columns as the screen.

**Not executed:** there is no PHP in my sandbox, so the PHP changes were reviewed line by line against the vendored
library's API but not run. Please do one export after applying and check the columns, ID numbers and dates.
Watch item (not a regression): PhpSpreadsheet holds every cell in memory and the export now has 18 columns instead of
13, so a very large "Export All" (many thousands of rows) could hit PHP's default 128 MB `memory_limit`.

## Bugs found in existing code (fixed in this patch, flagged as asked)

1. **Sorting was broken from Bank Name onward.** When the Bank Name column was added, `ORDER_COLUMNS` in `register.js`
   wasn't updated, so every header after it sorted by the wrong field (Amount sorted by Branch, Bank by Amount,
   and so on). The default sort also pointed at Action Date instead of Date Loaded, with the sort arrow on the Repayment Status header.
   Fixed; `Loan::SORTABLE` also listed `'bank'`, which isn't a column in the view.
2. **Stored XSS in the table.** Names, surname, ID/account, bank, branch and workplace were inserted as raw HTML,
   so a Branch user typing `<img src=x onerror=...>` as a workplace would run script in an admin's browser. All
   text is now escaped (the cards escape everything too).
3. **Date Loaded** used `new Date('YYYY-MM-DD hh:mm:ss')`, which is "Invalid Date" on Safari/iOS. It now uses the
   same safe formatter as Action Date (same `yyyy/mm/dd` output).
4. The register had no per-page cache-busting, so a stale `register.js` could outlive a deploy. The new includes carry `?v=<file time>`.

## Tested

In headless Chromium at 1440px and 390px with your real `style.css`, `app.js`, `register.js` and the new files:
75 browser checks (pinned columns hold at the far-right end, fixed bar position/sync/hide, colour loop on rows and
cards, toggle persistence across reload with no flash, selection sync, sort mapping for every header, Edit/Delete
proxying, no horizontal page overflow on phones, Branch account has no Delete) and 22 card-builder checks including
hostile input, all passing. Real DataTables isn't available offline, so those runs used a small stand-in that mimics the
DataTables calls this code makes. Please smoke-test once with the real thing (a quick look at Table, Cards, and
sorting a couple of headers is enough).

## Tweaks

* Pin a different set of columns: the `:nth-child(3|4|5)` selectors in the "Pinned columns" block of `loan-register.css`
  (and `ths[2]`/`ths[3]` in `updateStickyOffsets()` in `register-views.js`).
* Want the checkbox pinned as well? Say so; it's a small change.
