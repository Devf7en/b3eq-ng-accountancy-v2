-- =============================================================================
-- b3Ɛq Nigerian Accountancy v2.0.0 — Table Structure
-- tagged: b3Ɛq Nigerian Accountancy
-- File:   sql/llx_b3eqng_create.sql
--
-- This file is run by _load_tables() on module ACTIVATION.
-- Creates ONLY custom tables (not Dolibarr core tables).
-- All CREATE statements are idempotent (IF NOT EXISTS).
--
-- Dolibarr core tables used (already exist):
--   llx_accounting_account  — COA entries
--   llx_c_tva               — VAT rates
--   llx_accounting_journal  — journals
--   llx_const               — module constants
-- =============================================================================

SET NAMES utf8mb4;

-- ── Custom WHT code registry ──────────────────────────────────────────────────
-- WHT codes cannot go into llx_c_chargesociales (missing 'code' column in core)
CREATE TABLE IF NOT EXISTS llx_b3eqng_wht_codes (
    rowid        INT           NOT NULL AUTO_INCREMENT,
    entity       INT           NOT NULL DEFAULT 1,
    code         VARCHAR(30)   NOT NULL,
    label        VARCHAR(200)  NOT NULL,
    rate         DECIMAL(6,4)  NOT NULL DEFAULT 0.1000,
    account_dr   VARCHAR(20)   DEFAULT NULL,
    account_cr   VARCHAR(20)   DEFAULT NULL,
    active       TINYINT(1)    NOT NULL DEFAULT 1,
    date_creation DATETIME     DEFAULT NULL,
    PRIMARY KEY (rowid),
    UNIQUE KEY uk_b3eqng_wht_entity_code (entity, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Fixed Assets Register ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS llx_b3eqng_assets (
    rowid            INT          NOT NULL AUTO_INCREMENT,
    entity           INT          NOT NULL DEFAULT 1,
    ref              VARCHAR(50)  NOT NULL,
    label            VARCHAR(255) NOT NULL,
    category         VARCHAR(100) DEFAULT NULL,
    acquisition_date DATE         DEFAULT NULL,
    acquisition_cost DOUBLE(24,8) NOT NULL DEFAULT 0,
    residual_value   DOUBLE(24,8) NOT NULL DEFAULT 0,
    useful_life_years INT         NOT NULL DEFAULT 5,
    depreciation_method VARCHAR(20) NOT NULL DEFAULT 'STRAIGHT_LINE',
    accumulated_depreciation DOUBLE(24,8) NOT NULL DEFAULT 0,
    net_book_value   DOUBLE(24,8) NOT NULL DEFAULT 0,
    disposal_date    DATE         DEFAULT NULL,
    disposal_amount  DOUBLE(24,8) DEFAULT NULL,
    account_asset    VARCHAR(20)  DEFAULT '1300',
    account_depr     VARCHAR(20)  DEFAULT '1301',
    account_expense  VARCHAR(20)  DEFAULT '6030',
    status           VARCHAR(20)  NOT NULL DEFAULT 'ACTIVE',
    note             TEXT         DEFAULT NULL,
    fk_user_creat    INT          DEFAULT NULL,
    date_creation    DATETIME     DEFAULT NULL,
    tms              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rowid),
    KEY idx_b3eqng_assets_entity (entity),
    KEY idx_b3eqng_assets_ref (ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Audit Trail (immutable, append-only) ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS llx_b3eqng_audit (
    rowid        BIGINT       NOT NULL AUTO_INCREMENT,
    entity       INT          NOT NULL DEFAULT 1,
    event_time   DATETIME     NOT NULL,
    fk_user      INT          DEFAULT NULL,
    action       VARCHAR(50)  NOT NULL,
    object_type  VARCHAR(50)  DEFAULT NULL,
    object_id    INT          DEFAULT NULL,
    amount       DOUBLE(24,8) DEFAULT NULL,
    account_dr   VARCHAR(20)  DEFAULT NULL,
    account_cr   VARCHAR(20)  DEFAULT NULL,
    journal_code VARCHAR(20)  DEFAULT NULL,
    description  VARCHAR(500) DEFAULT NULL,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    entry_hash   VARCHAR(64)  DEFAULT NULL,
    prev_hash    VARCHAR(64)  DEFAULT NULL,
    PRIMARY KEY (rowid)
    -- No updates ever — only INSERT is permitted on this table
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── FX Revaluation Log ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS llx_b3eqng_fx_rates (
    rowid         INT          NOT NULL AUTO_INCREMENT,
    entity        INT          NOT NULL DEFAULT 1,
    currency_code VARCHAR(3)   NOT NULL,
    rate_date     DATE         NOT NULL,
    rate_to_ngn   DOUBLE(20,6) NOT NULL,
    source        VARCHAR(50)  DEFAULT 'MANUAL',
    date_creation DATETIME     DEFAULT NULL,
    PRIMARY KEY (rowid),
    UNIQUE KEY uk_b3eqng_fx_entity_ccy_date (entity, currency_code, rate_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Done. Seed data is inserted separately by install_data.php
