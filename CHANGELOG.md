# Changelog — b3Ɛq Nigerian Accountancy
`tagged: b3Ɛq Nigerian Accountancy`

---

## [2.0.0] — 2026-06-11

### Bug Fixes (10 critical issues resolved from v1)

- **BUG 1 FIXED** — Module not appearing: `$this->picto` corrected to `'accounting'`; hard `modAccounting` dependency removed
- **BUG 2 FIXED** — SQL seed column mismatch: all INSERTs now use correct Dolibarr 15-19 column names (`numero_compte`, `label_compte`); WHT codes moved to custom `llx_b3eqng_wht_codes` table
- **BUG 3 FIXED** — Menu 404s: all URL paths corrected to `/custom/b3eqng/pages/...`
- **BUG 4 FIXED** — Broken include chain on shared hosting: replaced with walk-up `b3eqng_init.php` bootstrap that finds `main.inc.php` regardless of nesting depth
- **BUG 5 FIXED** — Rights crash on fresh activation: all rights checks guarded with `empty()` + admin fallback
- **BUG 6 FIXED** — CSS not loading: path corrected to `/custom/b3eqng/css/b3eqng.css`
- **BUG 7 FIXED** — CSS `llxHeader` parameter wrong: CSS now injected via `b3eq_inject_css()` after header
- **BUG 8 FIXED** — `getDolGlobalString()` missing on Dolibarr <17: compatibility wrapper `b3eq_conf()` used throughout
- **BUG 9 FIXED** — SQL multi-statement execution breaking: `init()` delegates to `install_data.php` with proper statement chunking
- **BUG 10 FIXED** — Drop SQL running on every activation toggle: `_load_tables` now points only to `llx_b3eqng_create.sql`; seed is one-time via `install_data.php`

### New Features (v2 additions)

- **Payroll & PAYE Calculator** — Full monthly payroll breakdown, 6-band PAYE, CRA, pension, NHF, NSITF, complete journal entry output
- **CIT Estimator** — Company size classification, CIT + Development Levy + CGT + minimum tax calculation, provision journal entry
- **Fixed Assets Register** — Add assets, straight-line and declining-balance depreciation, monthly depreciation journal entry per asset, progress bar visualisation
- **FX Revaluation Engine** — Manual CBN rate entry, unrealised gain/loss calculation on open multi-currency invoices, IAS 21 adjusting journal entry
- **Immutable Audit Trail** — Append-only `llx_b3eqng_audit` table with SHA-256 hash chain; all tax postings logged; Storno-only correction model
- **n8n FX Rate Sync workflow** — Daily open.er-api rate pull, saves NGN rates for 8 currencies, Slack notification
- **n8n OCR Invoice Ingestion workflow** — Mindee OCR pipeline, extracts vendor/amount/VAT/WHT, flags for review if variance detected
- **`b3eqng_init.php`** — Shared bootstrap, eliminates all fragile include paths across pages
- **`install_data.php`** — Safe one-time data seeder called from `init()`, handles multi-entity substitution
- **FTP Install Guide** — Step-by-step shared hosting deployment with phpMyAdmin seed instructions
- **`asset_depreciation_rules.json`** — 10 asset categories with IFRS-aligned methods and useful lives

### Accounts Added (v2)
- `1500` Unrealised FX Gain Receivable
- `2300` Unrealised FX Loss Payable
- `4106` Unrealised FX Gain (P&L)
- `8004` Unrealised FX Loss (P&L)

---

## [1.0.0] — 2026-06-07

Initial release. 7-tab React dashboard, Dolibarr module scaffold, SQL seed (with v1 column name bugs), deploy scripts, n8n compliance workflows.

---

## Tax Law Watch List

| Item | Status | Source |
|---|---|---|
| CIT 25% from 2026 YOA | FIRS implementation guidance pending | NTA 2025 §56 |
| TaxPro Max API public docs | Not yet published | NRS |
| NHF Act amendment | Under review | FMF |
