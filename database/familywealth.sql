CREATE DATABASE IF NOT EXISTS familywealth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE familywealth;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE family_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    relationship VARCHAR(60) NOT NULL,
    date_of_birth DATE NULL,
    blood_group VARCHAR(10) NULL,
    nationality VARCHAR(80) NULL,
    phone VARCHAR(40) NULL,
    personal_net_worth DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE member_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    document_type ENUM('Passport','National ID','Driver''s License','Other') NOT NULL,
    document_number VARCHAR(100) NULL,
    expiry_date DATE NULL,
    file_name VARCHAR(255) NULL,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
);

CREATE TABLE savings_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    account_name VARCHAR(120) NOT NULL,
    bank_institution VARCHAR(120) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    account_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES family_members(id) ON DELETE CASCADE
);

CREATE TABLE savings_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    transaction_type ENUM('credit','debit') NOT NULL,
    transaction_date DATE NOT NULL,
    note VARCHAR(255) NULL,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(id) ON DELETE CASCADE
);

-- Demo family data matching the submitted wireframe
INSERT INTO family_members
(full_name, relationship, date_of_birth, blood_group, nationality, phone, personal_net_worth)
VALUES
('Khalid Al-Rashid','Head of Family','1968-03-14','O+','Bangladeshi','+8801712345678',3200000),
('Sara Al-Rashid','Spouse','1972-08-21','A+','Bangladeshi','+8801712345679',820000),
('Fatima Hussain','Daughter-in-law','1995-05-12','B+','Bangladeshi','+8801712345680',240000),
('Adam Al-Rashid','Son','2001-11-09','O+','Bangladeshi','+8801712345681',120000),
('Leila Al-Rashid','Daughter','1999-02-18','A+','Bangladeshi','+8801712345682',95000),
('Rayan Al-Rashid','Son','2004-07-26','B+','Bangladeshi','+8801712345683',60000);

INSERT INTO member_documents (member_id, document_type, document_number, expiry_date)
VALUES
(1,'Passport','P1234567','2027-12-31'),
(1,'National ID','784-1968-1234567-1','2025-06-30'),
(1,'Driver''s License','DL-1968-8899','2026-01-20'),
(2,'Passport','P2233445','2028-08-21'),
(3,'Passport','P3344556','2029-05-12'),
(4,'Passport','P4455667','2028-11-09'),
(5,'Passport','P5566778','2029-02-18'),
(6,'Passport','P6677889','2028-07-26');

INSERT INTO savings_accounts (member_id, account_name, bank_institution, balance, account_type)
VALUES
(1,'Main Savings','Emirates NBD',840000,'Savings'),
(1,'Investment A/C','ADCB',400000,'Investment'),
(2,'Personal Savings','Mashreq',820000,'Savings'),
(3,'Family Savings','FAB',240000,'Savings'),
(4,'Education Fund','FAB',120000,'Education'),
(5,'Personal Savings','Mashreq',95000,'Savings'),
(6,'Personal Savings','Emirates NBD',60000,'Savings');

INSERT INTO savings_transactions (account_id, amount, transaction_type, transaction_date, note)
VALUES
(1,22000,'credit','2026-08-03','Monthly credit'),
(2,4000,'credit','2026-07-30','Investment return'),
(3,8400,'credit','2026-08-01','Monthly savings'),
(4,5000,'credit','2026-08-02','Family savings'),
(5,3200,'credit','2026-07-15','Education contribution'),
(6,1800,'credit','2026-08-01','Monthly savings'),
(7,1200,'credit','2026-08-02','Monthly savings');
