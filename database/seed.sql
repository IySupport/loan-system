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

INSERT INTO daily_counters (counter_date, last_value) VALUES (CURRENT_DATE, 0)
ON CONFLICT (counter_date) DO NOTHING;
