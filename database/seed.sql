-- =====================================================================
-- Seed data
-- Default administrator: username = admin / password = Admin@123
-- IMPORTANT: generate a fresh hash for your server with:
--   php public/tools/make_hash.php Admin@123
-- and paste it below before running this file, since bcrypt hashes are
-- tied to the PHP build that generated them.
-- =====================================================================
INSERT INTO users (full_name, username, password_hash, role, status) VALUES
('Admin User', 'admin', '$2y$10$R10xkzV2EaJZK.BYdYKilOpxptjaAEF1IW/fDLZavSwDmx.5lrZi2', 'Administrator', 'Active');

INSERT INTO branches (branch_name) VALUES
('Durban'), ('Pietermaritzburg'), ('Bizana'), ('New Castle'), ('Pine Town'), ('WitBank'), ('Middleburg');

-- Loan Status: application / disbursement lifecycle
INSERT INTO loan_statuses (status_name) VALUES
('Pending Review'),
('Approved'),
('Disbursed'),
('Rejected'),
('Closed');

-- Repayment Status: where the client's repayment stands
INSERT INTO repayment_statuses (status_name) VALUES
('Not Due'),
('Pending Payment'),
('Partially Paid'),
('Paid'),
('Defaulted'),
('Rolled Over');

-- Banks: South African banks + universal branch codes
INSERT INTO banks (bank_name, branch_code) VALUES
('Absa Bank', '632005'),
('Access Bank South Africa', '410506'),
('African Bank', '430000'),
('Bank Zero', '888000'),
('Bidvest Bank', '462005'),
('Capitec Bank', '470010'),
('Discovery Bank', '679000'),
('First National Bank (FNB)', '250655'),
('Grindrod Bank', '584000'),
('Investec Bank', '580105'),
('Mercantile Bank', '450905'),
('Nedbank', '198765'),
('Postbank (SAPO)', '460005'),
('Sasfin Bank', '683000'),
('Standard Bank', '051001'),
('TymeBank', '678910'),
('Ubank', '431010');

INSERT INTO daily_counters (counter_date, last_value) VALUES (CURRENT_DATE, 0)
ON CONFLICT (counter_date) DO NOTHING;

-- ---------------------------------------------------------------------
-- Example: creating a Branch account (commented out - not run automatically,
-- since this file has already been run against a live database).
-- 1. Generate a hash:  php public/tools/make_hash.php "SomePassword123"
-- 2. Find the branch's id:  SELECT id, branch_name FROM branches;
-- 3. Insert, using that hash and branch id:
--
-- INSERT INTO users (full_name, username, password_hash, role, branch_id, status)
-- VALUES ('Durban Branch', 'durban.branch', '<paste generated hash>', 'Branch', 1, 'Active');
--
-- Or simply use the User Management screen (Administrator login required) -
-- Add User -> Role: Branch -> pick the branch from the dropdown.
-- ---------------------------------------------------------------------
