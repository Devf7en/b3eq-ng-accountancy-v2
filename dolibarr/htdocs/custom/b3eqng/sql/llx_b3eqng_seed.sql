-- =============================================================================
-- b3Ɛq Nigerian Accountancy v2.0.0 — Data Seed
-- tagged: b3Ɛq Nigerian Accountancy
-- File:   sql/llx_b3eqng_seed.sql
--
-- Called by scripts/install_data.php (NOT by _load_tables directly).
-- Run via phpMyAdmin Import tab if manual seed is needed.
--
-- FIXED v1 → v2 (BUG 2):
--   llx_accounting_account correct columns for Dolibarr 15-19:
--     numero_compte  (was: account_number)
--     label_compte   (was: label)
--     fk_pcg_version (correct)
--     pcg_type       (correct)
--     pcg_subtype    (correct — note: nullable in some versions)
--
--   llx_c_tva INSERT corrected — no 'label' column in core, uses 'taux' + 'note'
--
--   WHT codes moved to llx_b3eqng_wht_codes (custom table)
--
--   llx_accounting_journal: nature must be smallint 1-9, code varchar(20)
--
-- Safe to run multiple times (INSERT IGNORE throughout).
-- =============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- =============================================================================
-- 1. CHART OF ACCOUNTS  →  llx_accounting_account
-- Correct columns: entity, fk_pcg_version, pcg_type, pcg_subtype,
--                  numero_compte, label_compte, active
-- =============================================================================

-- ── Assets ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1000','Cash and Cash Equivalents',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1001','Petty Cash (Naira)',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1002','Current Account – CBN/Commercial Banks',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1003','Domiciliary Account (USD/GBP/EUR)',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1100','Accounts Receivable (Trade Debtors)',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1101','VAT Recoverable (Input VAT)',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1102','WHT Credit Receivable',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1103','Staff Advances and Prepayments',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1104','Prepaid Expenses',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1105','Accrued Income',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1200','Inventory - Finished Goods',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1201','Inventory - Raw Materials',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1202','Inventory - Work-in-Progress',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1300','Property Plant and Equipment (PPE)',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1301','Accumulated Depreciation - PPE',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1302','Intangible Assets (Software/Licences)',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1303','Accumulated Amortisation - Intangibles',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1304','Right-of-Use Assets (IFRS 16)',1),
(1,'NG-IFRS-SME','ASSET','NONCURRENTASSET', '1400','Deferred Tax Asset',1),
(1,'NG-IFRS-SME','ASSET','CURRENTASSET',    '1500','Unrealised FX Gain Receivable',1);

-- ── Liabilities ───────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2000','Accounts Payable (Trade Creditors)',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2001','Accrued Expenses',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2100','VAT Payable - Output VAT 7.5%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2101','VAT Payable - Zero-Rated Output',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2102','VAT Control Account (Net)',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2110','WHT Payable - Dividends 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2111','WHT Payable - Interest 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2112','WHT Payable - Rent 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2113','WHT Payable - Contracts Supplies 5%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2114','WHT Payable - Professional Fees 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2115','WHT Payable - Director Fees 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2116','WHT Payable - Technical Fees 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2117','WHT Payable - Commissions 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2118','WHT Payable - Royalties 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2119','WHT Payable - Construction 2.5%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2120','PAYE Tax Payable',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2121','Pension Payable - Employer 10%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2122','Pension Payable - Employee 8%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2123','NHF Payable 2.5%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2124','NSITF Levy Payable 1%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2125','ITF Levy Payable 1%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2126','NITDA Levy Payable 1%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2130','CIT Payable - Current Year',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2131','Development Levy Payable 4%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2132','Capital Gains Tax Payable 30%',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2133','Stamp Duty Payable',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2140','Short-term Loans and Borrowings',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2141','Customer Deposits and Advances',1),
(1,'NG-IFRS-SME','LIABILITIES','NONCURRENTLIABILITIES', '2200','Long-term Loans and Borrowings',1),
(1,'NG-IFRS-SME','LIABILITIES','NONCURRENTLIABILITIES', '2201','Lease Liabilities IFRS 16',1),
(1,'NG-IFRS-SME','LIABILITIES','NONCURRENTLIABILITIES', '2202','Deferred Tax Liability',1),
(1,'NG-IFRS-SME','LIABILITIES','CURRENTLIABILITIES',    '2300','Unrealised FX Loss Payable',1);

-- ── Equity ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','EQUITY','EQUITY','3000','Share Capital / Proprietors Capital',1),
(1,'NG-IFRS-SME','EQUITY','EQUITY','3001','Share Premium',1),
(1,'NG-IFRS-SME','EQUITY','EQUITY','3002','Retained Earnings',1),
(1,'NG-IFRS-SME','EQUITY','EQUITY','3003','General Reserve',1),
(1,'NG-IFRS-SME','EQUITY','EQUITY','3004','Statutory Reserve',1),
(1,'NG-IFRS-SME','EQUITY','EQUITY','3005','Foreign Currency Translation Reserve',1);

-- ── Revenue ───────────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4000','Sales Revenue - Goods',1),
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4001','Sales Revenue - Services',1),
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4002','Sales Revenue - Digital SaaS',1),
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4003','Export Sales Revenue Zero-rated VAT',1),
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4004','Commission Income',1),
(1,'NG-IFRS-SME','INCOME','OPERATIONALREVENUE','4005','Franchise and Licence Income',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4100','Interest Income',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4101','Rental Income',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4102','Dividend Income',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4103','Foreign Exchange Gain (Realised)',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4104','Gain on Disposal of Assets',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4105','Grant and Subsidy Income',1),
(1,'NG-IFRS-SME','INCOME','OTHERINCOME',        '4106','Unrealised FX Gain',1);

-- ── Cost of Sales ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','EXPENSE','COSTOFSALES','5000','Cost of Goods Sold',1),
(1,'NG-IFRS-SME','EXPENSE','COSTOFSALES','5001','Direct Labour / Production Wages',1),
(1,'NG-IFRS-SME','EXPENSE','COSTOFSALES','5002','Direct Materials and Consumables',1),
(1,'NG-IFRS-SME','EXPENSE','COSTOFSALES','5003','Import Duties and Customs Levies',1),
(1,'NG-IFRS-SME','EXPENSE','COSTOFSALES','5004','Freight and Delivery Inbound',1);

-- ── Operating Expenses ────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6000','Staff Salaries and Wages',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6001','PAYE Tax Expense',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6002','Employer Pension Contribution 10%',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6003','NSITF Premium 1%',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6004','ITF Levy 1%',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6005','Group Life Insurance',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6010','Rent and Lease',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6011','Electricity and Power',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6012','Internet and Telecoms',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6013','Repairs and Maintenance',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6014','Fuel and Transportation',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6020','Professional Fees (Legal/Audit/Tax)',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6021','Consulting and Outsourcing Fees',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6022','Advertising and Marketing',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6023','Bank Charges and Commission',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6024','Travel and Accommodation Domestic',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6025','Travel and Accommodation International',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6026','Printing Stationery Office Supplies',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6027','Security Services',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6028','Cleaning and Janitorial Services',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6029','Insurance Premiums',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6030','Depreciation - PPE',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6031','Amortisation - Intangibles',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','6032','Depreciation - Right-of-Use Assets',1);

-- ── Tax Expenses ──────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','7000','Companies Income Tax CIT Expense',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','7001','Development Levy 4% Expense',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','7002','Capital Gains Tax Expense',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','7003','Deferred Tax Expense',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','7004','Minimum Tax Expense 0.5%',1);

-- ── Finance Costs ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO llx_accounting_account
  (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
VALUES
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','8000','Interest Expense Borrowings',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','8001','Finance Cost Lease IFRS 16',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','8002','Foreign Exchange Loss (Realised)',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','8003','Loss on Disposal of Assets',1),
(1,'NG-IFRS-SME','EXPENSE','EXPENSE','8004','Unrealised FX Loss',1);


-- =============================================================================
-- 2. VAT RATES  →  llx_c_tva
-- FIX: removed 'label' column (doesn't exist in core), use only: taux, note, etc.
-- Correct columns: entity, taux, recuperableintegral,
--                  accountancy_code_sell, accountancy_code_buy, note, active
-- =============================================================================

INSERT IGNORE INTO llx_c_tva
  (entity, taux, recuperableintegral, accountancy_code_sell, accountancy_code_buy, note, active)
SELECT 1, 7.5, 1, '2100', '1101', 'Nigeria VAT 7.5% Standard Rate (NTA 2025 / VATA)', 1
WHERE NOT EXISTS (
  SELECT 1 FROM llx_c_tva
  WHERE entity=1 AND taux=7.5 AND note LIKE 'Nigeria VAT 7.5%'
);

INSERT IGNORE INTO llx_c_tva
  (entity, taux, recuperableintegral, accountancy_code_sell, accountancy_code_buy, note, active)
SELECT 1, 0.0, 1, '2101', '1101', 'Nigeria VAT 0% Zero-Rated (Exports/Diplomatic)', 1
WHERE NOT EXISTS (
  SELECT 1 FROM llx_c_tva
  WHERE entity=1 AND taux=0.0 AND note LIKE 'Nigeria VAT 0%'
);

INSERT IGNORE INTO llx_c_tva
  (entity, taux, recuperableintegral, accountancy_code_sell, accountancy_code_buy, note, active)
SELECT 1, 0.0, 0, NULL, NULL, 'Nigeria VAT Exempt (Basic food/Medical/Educational)', 1
WHERE NOT EXISTS (
  SELECT 1 FROM llx_c_tva
  WHERE entity=1 AND taux=0.0 AND note LIKE 'Nigeria VAT Exempt%'
);


-- =============================================================================
-- 3. WHT CODES  →  llx_b3eqng_wht_codes (custom table — created in create.sql)
-- =============================================================================

INSERT IGNORE INTO llx_b3eqng_wht_codes
  (entity, code, label, rate, account_dr, account_cr, active, date_creation)
VALUES
(1,'NG-WHT-DIV','WHT - Dividends',                               0.1000,'6000','2110',1,NOW()),
(1,'NG-WHT-INT','WHT - Interest',                                0.1000,'4100','2111',1,NOW()),
(1,'NG-WHT-RNT','WHT - Rent',                                    0.1000,'6010','2112',1,NOW()),
(1,'NG-WHT-SUP','WHT - Contracts and Supplies',                  0.0500,'5002','2113',1,NOW()),
(1,'NG-WHT-PRF','WHT - Professional Fees (Legal/Audit/Tax)',     0.1000,'6020','2114',1,NOW()),
(1,'NG-WHT-DIR','WHT - Director Fees',                           0.1000,'6020','2115',1,NOW()),
(1,'NG-WHT-TEC','WHT - Technical/Management/Consulting',         0.1000,'6021','2116',1,NOW()),
(1,'NG-WHT-COM','WHT - Commissions',                             0.1000,'6022','2117',1,NOW()),
(1,'NG-WHT-ROY','WHT - Royalties',                               0.1000,'6021','2118',1,NOW()),
(1,'NG-WHT-CON','WHT - Construction/Drilling/Survey',            0.0250,'6013','2119',1,NOW());


-- =============================================================================
-- 4. ACCOUNTING JOURNALS  →  llx_accounting_journal
-- FIX: nature must be a smallint: 1=divers, 2=sale, 3=purchase, 4=bank, 5=expense
-- code is varchar(20) max, UNIQUE per entity
-- =============================================================================

INSERT IGNORE INTO llx_accounting_journal
  (entity, code, label, nature, active)
VALUES
(1,'JV-SA','Sales Journal',        2, 1),
(1,'JV-PU','Purchase Journal',     3, 1),
(1,'JV-BK','Bank Journal',         4, 1),
(1,'JV-CS','Cash Journal',         5, 1),
(1,'JV-TX','Tax Journal',          1, 1),
(1,'JV-PY','Payroll Journal',      1, 1),
(1,'JV-FA','Fixed Assets Journal', 1, 1),
(1,'JV-MV','General Journal',      1, 1),
(1,'JV-FX','Forex Journal',        1, 1);


-- =============================================================================
-- 5. MODULE CONSTANTS  →  llx_const
-- =============================================================================

INSERT IGNORE INTO llx_const (name, value, type, note, visible, entity)
VALUES
('B3EQNG_VERSION',        '2.0.0',   'chaine', 'b3Ɛq NG Accountancy module version',           0, 1),
('B3EQNG_COMPANY_SIZE',   'LARGE',   'chaine', 'SMALL/MEDIUM/LARGE – sets CIT rate',            1, 1),
('B3EQNG_VAT_REGISTERED', '1',       'chaine', '1=VAT registered with NRS/FIRS',                1, 1),
('B3EQNG_TIN',            '',        'chaine', 'Company FIRS Tax Identification Number',         1, 1),
('B3EQNG_VAT_NUMBER',     '',        'chaine', 'Company VAT Registration Number',               1, 1),
('B3EQNG_STATE',          'LAGOS',   'chaine', 'State of registration (routes PAYE to SIRS)',   1, 1),
('B3EQNG_SEEDED',         '1',       'chaine', '1 once seed data has been inserted',            0, 1),
('B3EQNG_SEEDED_AT',      NOW(),     'chaine', 'Timestamp of last data seed',                   0, 1);


-- =============================================================================
-- Verification queries (run manually to confirm):
--
--   SELECT COUNT(*) FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME';
--   -- Expected: 92
--
--   SELECT COUNT(*) FROM llx_c_tva WHERE note LIKE 'Nigeria%';
--   -- Expected: 3
--
--   SELECT COUNT(*) FROM llx_b3eqng_wht_codes WHERE entity=1;
--   -- Expected: 10
--
--   SELECT COUNT(*) FROM llx_accounting_journal WHERE code LIKE 'JV-%';
--   -- Expected: 9
-- =============================================================================

SET foreign_key_checks = 1;
