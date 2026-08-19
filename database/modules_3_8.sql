-- ============================================================
-- FamilyWealth Management System
-- Additive schema for Modules 3-8 (Income, Expenses, Funding
-- Codes, Dashboard support, Document Vault, Notifications).
--
-- This file does NOT modify database/familywealth.sql in any
-- way. Import familywealth.sql first (Modules 1-2 + users),
-- then import this file into the same `familywealth` database.
-- Safe to re-run: every table uses CREATE TABLE IF NOT EXISTS.
-- ============================================================

USE familywealth;

-- Module 5 — Funding Codes -----------------------------------
CREATE TABLE IF NOT EXISTS funding_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(120) NOT NULL,
    budget DECIMAL(15,2) NOT NULL DEFAULT 0,
    alert_80 TINYINT(1) NOT NULL DEFAULT 1,
    alert_90 TINYINT(1) NOT NULL DEFAULT 1,
    alert_100 TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Module 3 — Income --------------------------------------------
CREATE TABLE IF NOT EXISTS income (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    source VARCHAR(120) NOT NULL,
    category VARCHAR(60) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    date_received DATE NOT NULL,
    credit_account_id INT NOT NULL,
    income_type ENUM('One-time','Recurring') NOT NULL DEFAULT 'One-time',
    notes VARCHAR(255) NULL,
    status ENUM('Pending','Approved','Credited') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE,
    FOREIGN KEY (credit_account_id) REFERENCES savings_accounts(id) ON DELETE CASCADE
);

-- Module 4 — Expense Management ---------------------------------
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(20) NOT NULL UNIQUE,
    funding_code_id INT NOT NULL,
    member_id INT NULL,
    account_id INT NULL,
    category VARCHAR(120) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    description VARCHAR(255) NULL,
    receipt_file VARCHAR(255) NULL,
    entry_by ENUM('Self','Accountant') NOT NULL DEFAULT 'Self',
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (funding_code_id) REFERENCES funding_codes(id),
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE SET NULL,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS expense_audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    action VARCHAR(180) NOT NULL,
    actor VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
);

-- Module 7 — Document Vault --------------------------------------
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NULL,
    category VARCHAR(60) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL DEFAULT 0,
    expiry_date DATE NULL,
    version INT NOT NULL DEFAULT 1,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
);

-- Module 8 — Notifications -----------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ref_key VARCHAR(120) NOT NULL UNIQUE,
    type VARCHAR(30) NOT NULL,
    severity ENUM('info','warning','danger') NOT NULL DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message VARCHAR(255) NOT NULL,
    related_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    passport_id_expiry TINYINT(1) NOT NULL DEFAULT 1,
    license_expiry TINYINT(1) NOT NULL DEFAULT 1,
    insurance_expiry TINYINT(1) NOT NULL DEFAULT 1,
    low_cash TINYINT(1) NOT NULL DEFAULT 1,
    funding_thresholds TINYINT(1) NOT NULL DEFAULT 1,
    pending_approvals TINYINT(1) NOT NULL DEFAULT 1,
    missing_documents TINYINT(1) NOT NULL DEFAULT 0,
    monthly_close_report TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- Demo data (only inserted the first time the tables are empty)
-- ============================================================

INSERT INTO funding_codes (code, name, category, budget, alert_80, alert_90, alert_100)
SELECT * FROM (SELECT 'UTL-001' code,'Utilities' name,'Electricity · Water · Gas' category, 8000 budget, 1 alert_80, 1 alert_90, 1 alert_100
UNION ALL SELECT 'EDU-002','Education','School Fees · Tuition',50000,1,1,1
UNION ALL SELECT 'MED-001','Medical','Insurance · Hospital',20000,1,1,1
UNION ALL SELECT 'OPS-003','Operations','Office · Admin',15000,1,1,1) t
WHERE NOT EXISTS (SELECT 1 FROM funding_codes);

-- Approved expenses across the last 6 months, driving realistic
-- utilization on the Funding Codes and Dashboard trend charts.
INSERT INTO expenses (ref, funding_code_id, member_id, account_id, category, amount, expense_date, description, entry_by, status)
SELECT * FROM (
SELECT 'EXP-001' ref,(SELECT id FROM funding_codes WHERE code='UTL-001') funding_code_id,1 member_id,1 account_id,'Utilities' category,900 amount,'2026-03-05' expense_date,'Electricity Bill' description,'Accountant' entry_by,'Approved' status
UNION ALL SELECT 'EXP-002',(SELECT id FROM funding_codes WHERE code='UTL-001'),1,1,'Utilities',950,'2026-04-05','Electricity Bill','Accountant','Approved'
UNION ALL SELECT 'EXP-003',(SELECT id FROM funding_codes WHERE code='UTL-001'),1,1,'Utilities',1000,'2026-05-05','Electricity Bill','Accountant','Approved'
UNION ALL SELECT 'EXP-004',(SELECT id FROM funding_codes WHERE code='UTL-001'),1,1,'Utilities',1050,'2026-06-05','Electricity Bill','Accountant','Approved'
UNION ALL SELECT 'EXP-005',(SELECT id FROM funding_codes WHERE code='UTL-001'),1,1,'Utilities',1100,'2026-07-05','Electricity Bill','Accountant','Approved'
UNION ALL SELECT 'EXP-006',(SELECT id FROM funding_codes WHERE code='UTL-001'),1,1,'Utilities',3420,'2026-08-02','Electricity + Water + Gas','Accountant','Approved'
UNION ALL SELECT 'EXP-007',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',6000,'2026-03-08','School Fees','Accountant','Approved'
UNION ALL SELECT 'EXP-008',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',6500,'2026-04-08','School Fees','Accountant','Approved'
UNION ALL SELECT 'EXP-009',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',7000,'2026-05-08','School Fees','Accountant','Approved'
UNION ALL SELECT 'EXP-010',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',7200,'2026-06-08','School Fees','Accountant','Approved'
UNION ALL SELECT 'EXP-011',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',7700,'2026-07-08','School Fees','Accountant','Approved'
UNION ALL SELECT 'EXP-012',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',8000,'2026-08-01','School Fees — Adam','Accountant','Approved'
UNION ALL SELECT 'EXP-013',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',1500,'2026-03-10','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-014',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',1600,'2026-04-10','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-015',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',1700,'2026-05-10','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-016',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',1900,'2026-06-10','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-017',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',2100,'2026-07-10','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-018',(SELECT id FROM funding_codes WHERE code='MED-001'),1,1,'Medical',2400,'2026-08-03','Medical Insurance','Self','Approved'
UNION ALL SELECT 'EXP-019',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',900,'2026-03-12','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-020',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',950,'2026-04-12','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-021',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',1000,'2026-05-12','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-022',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',1150,'2026-06-12','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-023',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',1300,'2026-07-12','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-024',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',300,'2026-08-04','Office Supplies','Self','Approved'
UNION ALL SELECT 'EXP-025',(SELECT id FROM funding_codes WHERE code='UTL-001'),2,3,'Utilities',840,'2026-08-10','Electricity Bill','Accountant','Pending'
UNION ALL SELECT 'EXP-026',(SELECT id FROM funding_codes WHERE code='OPS-003'),1,1,'Operations',560,'2026-08-12','Office Supplies','Self','Pending'
UNION ALL SELECT 'EXP-027',(SELECT id FROM funding_codes WHERE code='EDU-002'),4,5,'Education',12400,'2026-08-14','Tuition Fee','Accountant','Pending'
) t
WHERE NOT EXISTS (SELECT 1 FROM expenses);

INSERT INTO expense_audit_trail (expense_id, action, actor, created_at)
SELECT e.id, x.action, x.actor, CONCAT(e.expense_date,' 09:00:00')
FROM expenses e
JOIN (
    SELECT 'EXP-001' ref,'Expense submitted by Accountant' action,'Mohammed K.' actor
    UNION ALL SELECT 'EXP-001','Funding code UTL-001 balance checked','System'
    UNION ALL SELECT 'EXP-001','Approved','Khalid A.'
    UNION ALL SELECT 'EXP-025','Expense submitted by Accountant','Mohammed K.'
    UNION ALL SELECT 'EXP-025','Funding code UTL-001 balance checked','System'
    UNION ALL SELECT 'EXP-025','Routed to approval queue','System'
    UNION ALL SELECT 'EXP-026','Expense submitted by Self','Khalid A.'
    UNION ALL SELECT 'EXP-026','Funding code OPS-003 balance checked','System'
    UNION ALL SELECT 'EXP-026','Routed to approval queue','System'
    UNION ALL SELECT 'EXP-027','Expense submitted by Accountant','Mohammed K.'
    UNION ALL SELECT 'EXP-027','Funding code EDU-002 balance checked','System'
    UNION ALL SELECT 'EXP-027','Routed to approval queue','System'
) x ON x.ref = e.ref
WHERE NOT EXISTS (SELECT 1 FROM expense_audit_trail);

INSERT INTO income (member_id, source, category, amount, date_received, credit_account_id, income_type, status)
SELECT * FROM (
SELECT 1 member_id,'Rental Income' source,'Rental Income' category,20000 amount,'2026-03-01' date_received,1 credit_account_id,'Recurring' income_type,'Credited' status
UNION ALL SELECT 1,'Rental Income','Rental Income',20500,'2026-04-01',1,'Recurring','Credited'
UNION ALL SELECT 1,'Rental Income','Rental Income',21000,'2026-05-01',1,'Recurring','Credited'
UNION ALL SELECT 1,'Rental Income','Rental Income',21500,'2026-06-01',1,'Recurring','Credited'
UNION ALL SELECT 1,'Rental Income','Rental Income',22000,'2026-07-01',1,'Recurring','Credited'
UNION ALL SELECT 1,'Rental Income','Rental Income',22000,'2026-08-01',1,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',11500,'2026-03-03',3,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',11600,'2026-04-03',3,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',11700,'2026-05-03',3,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',11800,'2026-06-03',3,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',11900,'2026-07-03',3,'Recurring','Credited'
UNION ALL SELECT 2,'Salary','Salary',12000,'2026-08-03',3,'Recurring','Credited'
UNION ALL SELECT 1,'Business Dividends','Business Dividends',18000,'2026-08-02',1,'One-time','Pending'
UNION ALL SELECT 1,'Investment Returns','Investment Returns',5800,'2026-07-15',2,'Recurring','Approved'
UNION ALL SELECT 1,'Investment Returns','Investment Returns',6000,'2026-08-03',2,'Recurring','Approved'
) t
WHERE NOT EXISTS (SELECT 1 FROM income);

INSERT INTO documents (member_id, category, original_name, stored_name, file_size, expiry_date, version, uploaded_at)
SELECT * FROM (
SELECT 1 member_id,'Personal IDs' category,'Passport_Khalid.pdf' original_name,'passport_khalid.pdf' stored_name,192 file_size,'2027-12-31' expiry_date,2 version,'2026-07-10 09:00:00' uploaded_at
UNION ALL SELECT NULL,'Property Titles','DXB_Title_Deed.pdf','dxb_title_deed.pdf',192,NULL,1,'2026-06-22 09:00:00'
UNION ALL SELECT 2,'Medical','Med_Sara_2026.pdf','med_sara_2026.pdf',192,NULL,1,'2026-08-01 09:00:00'
UNION ALL SELECT NULL,'Business Licenses','License_Holdings.pdf','license_holdings.pdf',192,'2026-09-30',3,'2026-01-15 09:00:00'
UNION ALL SELECT 4,'Education','Adam_Diploma.pdf','adam_diploma.pdf',192,NULL,1,'2026-05-30 09:00:00'
UNION ALL SELECT NULL,'Legal','Insurance_Policy_Family.pdf','insurance_policy_family.pdf',192,'2026-11-30',1,'2026-02-01 09:00:00'
) t
WHERE NOT EXISTS (SELECT 1 FROM documents);
