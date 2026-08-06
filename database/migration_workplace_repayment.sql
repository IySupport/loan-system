-- =====================================================================
-- Migration: Workplace info + auto-calculated Interest / Amount Due
-- Run this once on an existing database:
--   psql -d loan_system -f database/migration_workplace_repayment.sql
-- (Also folded into database/schema.sql for brand-new installs.)
--
-- interest_amount and amount_due are GENERATED (STORED) columns, not
-- plain columns filled in by the application. This means:
--   - They are computed by Postgres itself from `amount`, so they can
--     never drift out of sync and can never be edited directly (an
--     UPDATE/INSERT that tries to set them will be rejected).
--   - Existing rows are backfilled automatically the moment this
--     migration runs, using their current `amount` - no separate
--     backfill script needed.
--   - Any future UPDATE to `amount` (e.g. editing a loan) recalculates
--     both figures automatically.
-- =====================================================================

ALTER TABLE loans ADD COLUMN IF NOT EXISTS workplace_name VARCHAR(150);
ALTER TABLE loans ADD COLUMN IF NOT EXISTS work_contact    VARCHAR(30);

ALTER TABLE loans ADD COLUMN IF NOT EXISTS interest_amount
    NUMERIC(12,2) GENERATED ALWAYS AS (ROUND(amount * 0.40, 2)) STORED;

ALTER TABLE loans ADD COLUMN IF NOT EXISTS amount_due
    NUMERIC(12,2) GENERATED ALWAYS AS (ROUND(amount * 1.40, 2)) STORED;

CREATE INDEX IF NOT EXISTS idx_loans_workplace ON loans(workplace_name);

-- ---------------------------------------------------------------------
-- Rebuild the register view to expose the new columns.
-- New columns are appended at the end (Postgres requires CREATE OR
-- REPLACE VIEW to preserve the existing column order/positions).
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW loan_register_view AS
SELECT
    l.id,
    l.reference_number,
    c.id            AS client_id,
    c.name,
    c.surname,
    c.id_number,
    c.account_number,
    c.phone,
    l.amount,
    b.id            AS branch_id,
    b.branch_name,
    lc.loan_count,
    CASE
        WHEN lc.loan_count BETWEEN 1 AND 3 THEN 'Group 1'
        WHEN lc.loan_count BETWEEN 4 AND 8 THEN 'Group 2'
        ELSE 'Group 3'
    END AS loan_group,
    l.loan_status_id,
    ls.status_name  AS status,
    l.repayment_status_id,
    rs.status_name  AS repayment_status,
    l.action_date,
    l.created_at    AS date_loaded,
    l.updated_at,
    l.notes,
    l.created_by,
    l.workplace_name,
    l.work_contact,
    l.interest_amount,
    l.amount_due
FROM loans l
JOIN clients c              ON c.id = l.client_id
JOIN branches b              ON b.id = l.branch_id
JOIN loan_statuses ls         ON ls.id = l.loan_status_id
JOIN repayment_statuses rs    ON rs.id = l.repayment_status_id
JOIN (
    SELECT client_id, COUNT(*) AS loan_count
    FROM loans
    GROUP BY client_id
) lc ON lc.client_id = l.client_id;
