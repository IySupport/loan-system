-- =====================================================================
-- Loan Processing and Tracking System - PostgreSQL Schema
-- =====================================================================
DROP VIEW IF EXISTS loan_register_view CASCADE;
DROP TABLE IF EXISTS loans CASCADE;
DROP TABLE IF EXISTS clients CASCADE;
DROP TABLE IF EXISTS branches CASCADE;
DROP TABLE IF EXISTS loan_statuses CASCADE;
DROP TABLE IF EXISTS repayment_statuses CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS daily_counters CASCADE;
DROP TABLE IF EXISTS branch_budgets CASCADE;

-- ---------------------------------------------------------------------
-- Users
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              SERIAL PRIMARY KEY,
    full_name       VARCHAR(150) NOT NULL,
    username        VARCHAR(60)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            VARCHAR(20)  NOT NULL DEFAULT 'Operator'
                    CHECK (role IN ('Administrator','Operator')),
    status          VARCHAR(20)  NOT NULL DEFAULT 'Active'
                    CHECK (status IN ('Active','Inactive')),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Branches
-- ---------------------------------------------------------------------
CREATE TABLE branches (
    id              SERIAL PRIMARY KEY,
    branch_name     VARCHAR(100) NOT NULL UNIQUE,
    status          VARCHAR(20)  NOT NULL DEFAULT 'Active'
                    CHECK (status IN ('Active','Inactive')),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- Loan Statuses (lookup)
-- ---------------------------------------------------------------------
CREATE TABLE loan_statuses (
    id              SERIAL PRIMARY KEY,
    status_name     VARCHAR(50) NOT NULL UNIQUE
);

-- ---------------------------------------------------------------------
-- Repayment Statuses (lookup) - where the client's repayment stands
-- ---------------------------------------------------------------------
CREATE TABLE repayment_statuses (
    id              SERIAL PRIMARY KEY,
    status_name     VARCHAR(50) NOT NULL UNIQUE
);

-- ---------------------------------------------------------------------
-- Clients (unique per ID Number)
-- ---------------------------------------------------------------------
CREATE TABLE clients (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    surname         VARCHAR(100) NOT NULL,
    id_number       VARCHAR(20)  NOT NULL UNIQUE,
    account_number  VARCHAR(50),
    phone           VARCHAR(30),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_clients_id_number ON clients(id_number);

-- ---------------------------------------------------------------------
-- Daily counters -> used to generate reference numbers LN-YYYYMMDD-0001
-- ---------------------------------------------------------------------
CREATE TABLE daily_counters (
    counter_date    DATE PRIMARY KEY,
    last_value      INTEGER NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------------------
-- Loans (main business table)
-- ---------------------------------------------------------------------
CREATE TABLE loans (
    id                    SERIAL PRIMARY KEY,
    reference_number      VARCHAR(30) NOT NULL UNIQUE,
    client_id             INTEGER NOT NULL REFERENCES clients(id),
    branch_id             INTEGER NOT NULL REFERENCES branches(id),
    loan_status_id        INTEGER NOT NULL REFERENCES loan_statuses(id),
    repayment_status_id   INTEGER NOT NULL REFERENCES repayment_statuses(id),
    amount                NUMERIC(12,2) NOT NULL CHECK (amount >= 0),
    -- Workplace info: where the client works, for repayment follow-up.
    workplace_name        VARCHAR(150),
    work_contact           VARCHAR(30),
    -- Interest / Amount Due are DERIVED, never entered manually:
    --   interest_amount = amount * 40%
    --   amount_due      = amount + interest_amount
    -- Generated columns guarantee this at the database level and
    -- recalculate automatically whenever `amount` changes.
    interest_amount        NUMERIC(12,2) GENERATED ALWAYS AS (ROUND(amount * 0.40, 2)) STORED,
    amount_due              NUMERIC(12,2) GENERATED ALWAYS AS (ROUND(amount * 1.40, 2)) STORED,
    action_date            DATE NOT NULL,
    notes                  TEXT,
    created_by             INTEGER REFERENCES users(id),
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX idx_loans_client     ON loans(client_id);
CREATE INDEX idx_loans_branch     ON loans(branch_id);
CREATE INDEX idx_loans_status     ON loans(loan_status_id);
CREATE INDEX idx_loans_repayment  ON loans(repayment_status_id);
CREATE INDEX idx_loans_created    ON loans(created_at);
CREATE INDEX idx_loans_workplace  ON loans(workplace_name);
CREATE INDEX idx_loans_action_date ON loans(action_date);

-- ---------------------------------------------------------------------
-- View: computes loan_count and group DYNAMICALLY (never stored)
-- ---------------------------------------------------------------------
CREATE VIEW loan_register_view AS
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
