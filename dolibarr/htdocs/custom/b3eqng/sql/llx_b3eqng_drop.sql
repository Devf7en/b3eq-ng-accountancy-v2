-- =============================================================================
-- b3Ɛq Nigerian Accountancy — Clean Uninstall / Drop
-- tagged: b3Ɛq Nigerian Accountancy
--
-- WARNING: This permanently removes all Nigerian accounting data from this entity.
-- Only run if you want a full wipe. Deactivating the module does NOT run this.
--
-- Usage (replace 1 with your entity ID):
--   mysql -u user -p dbname < llx_b3eqng_drop.sql
-- =============================================================================

SET @ENTITY = 1;

-- Remove Chart of Accounts
DELETE FROM llx_accounting_account
WHERE fk_pcg_version = 'NG-IFRS-SME' AND entity = @ENTITY;

-- Remove accounting plan header
DELETE FROM llx_accounting_system WHERE pcg_version = 'NG-IFRS-SME';

-- Remove VAT rates
DELETE FROM llx_c_tva
WHERE note LIKE 'Nigeria%' AND entity = @ENTITY;

-- Remove WHT / social charge codes
DELETE FROM llx_c_chargesociales
WHERE code LIKE 'NG-%' AND entity = @ENTITY;

-- Remove journals
DELETE FROM llx_accounting_journal
WHERE code LIKE 'JV-%' AND entity = @ENTITY;

-- Remove module constants
DELETE FROM llx_const
WHERE name LIKE 'B3EQNG_%' AND entity = @ENTITY;

-- Deactivate module flag
DELETE FROM llx_const
WHERE name = 'MAIN_MODULE_B3EQNG' AND entity = @ENTITY;

SELECT 'b3Ɛq NG Accountancy data removed for entity ' AS msg, @ENTITY AS entity_id;
