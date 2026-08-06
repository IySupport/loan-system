-- =====================================================================
-- Migration: Monthly branch loan budgets
-- Run this once on an existing database:
--   psql -d loan_system -f database/migration_branch_budgets.sql
-- (Also folded into database/schema.sql for brand-new installs.)
-- =====================================================================
CREATE TABLE IF NOT EXISTS branch_budgets (
    id                SERIAL PRIMARY KEY,
    branch_id         INTEGER NOT NULL REFERENCES branches(id),
    budget_month      DATE NOT NULL,              -- always stored as the 1st of the month, e.g. 2026-07-01
    allocated_amount  NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (allocated_amount >= 0),
    created_by        INTEGER REFERENCES users(id),
    created_at        TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMP NOT NULL DEFAULT NOW(),
    UNIQUE (branch_id, budget_month)
);
CREATE INDEX IF NOT EXISTS idx_branch_budgets_month ON branch_budgets(budget_month);

