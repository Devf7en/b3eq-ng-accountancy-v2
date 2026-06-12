# b3Ɛq Nigerian Accountancy v2.0.0 — Bug Fix Log
`tagged: b3Ɛq Nigerian Accountancy`

## Root Causes of v1 Failure (All Fixed in v2)

---

### BUG 1 — Module Not Appearing in Dolibarr Modules List
**File:** `modB3eqng.class.php`
**Cause:** `$this->picto = 'b3eqng@b3eqng'` — Dolibarr requires a valid picto string
referencing a real image file OR a standard Font Awesome icon prefix like `fa-money-bill`.
Without a valid picto, some Dolibarr versions silently skip module discovery.
Also: `$this->depends = ['modAccounting']` — on many installs the internal module name
is `modComptabilite` not `modAccounting`. A missing dependency blocks activation entirely.
**Fix:** Set `$this->picto = 'accounting'` (built-in). Removed the hard dependency on
`modAccounting` — replaced with a runtime soft check in `init()`.

---

### BUG 2 — SQL Seed Fails Silently on Column Name Mismatch
**File:** `sql/llx_b3eqng_seed.sql`
**Cause:** Dolibarr 17–19 `llx_accounting_account` actual column names are:
- `numero_compte` (NOT `account_number`)
- `label_compte` (NOT `label`)
- `fk_pcg_version` ✓ correct
- `pcg_type` ✓ correct
- `pcg_subtype` ✓ correct

`llx_accounting_journal` nature column is an integer with specific enum values, but
the `code` field has a max length of 20 chars and requires uniqueness per entity.

`llx_accounting_system` has different column names across versions — removed entirely,
used only `llx_const` to register the plan version.

`llx_c_chargesociales` does NOT have a `code` column in standard Dolibarr.
WHT codes are stored differently — moved to a dedicated `llx_b3eqng_wht_codes` table.

**Fix:** All INSERT statements rewritten with correct Dolibarr 17+ column names.
Added `CREATE TABLE IF NOT EXISTS` for custom tables before any INSERTs.
Moved WHT code registry to `llx_b3eqng_wht_codes` (custom table, created in seed).

---

### BUG 3 — Menu URLs Wrong Path
**File:** `modB3eqng.class.php`
**Cause:** Menu `url` entries used `/b3eqng/pages/coa.php` — Dolibarr prepends
`DOL_URL_ROOT/custom/` automatically for custom modules. The correct URL is
`/custom/b3eqng/pages/coa.php`. Without this, clicking menu items gives 404.
**Fix:** All menu URL values corrected to `/custom/b3eqng/pages/coa.php` etc.

---

### BUG 4 — Page include paths wrong on shared hosting
**Files:** All `pages/*.php`, `admin/setup.php`
**Cause:** The include chain `@include '../../../main.inc.php'` counts directory depth
from the file's actual location on disk. `pages/` is 5 levels deep from webroot on
most shared hosting setups (e.g. `public_html/dolibarr/htdocs/custom/b3eqng/pages/`).
The fallback `@include '../../../../main.inc.php'` wasn't deep enough either.
**Fix:** Replaced fragile relative includes with the robust Dolibarr standard pattern
that walks up from the script path until `main.inc.php` is found (loop-based discovery).
This works on any hosting layout regardless of nesting depth.

---

### BUG 5 — Rights check crashes pages before module activates
**Files:** All `pages/*.php`
**Cause:** Pages called `$user->rights->b3eqng->read` before rights were registered,
causing a PHP fatal `Trying to get property of non-object`. On first activation this
always crashed.
**Fix:** Rights checks now use `isset()` guards:
`if (empty($user->rights->b3eqng->read)) accessforbidden();`

---

### BUG 6 — CSS not loading (path wrong)
**File:** `modB3eqng.class.php` `module_parts['css']`
**Cause:** Value was `['/b3eqng/css/b3eqng.css']`. Dolibarr resolves this relative to
`DOL_URL_ROOT` — needs to be `/custom/b3eqng/css/b3eqng.css`.
**Fix:** Corrected to `['/custom/b3eqng/css/b3eqng.css']`.

---

### BUG 7 — `llxHeader()` CSS parameter wrong
**Files:** All `pages/*.php`
**Cause:** CSS was passed as 5th positional parameter to `llxHeader()`. In Dolibarr 17+
the correct parameter is the 8th (moreheadcontents). CSS for custom modules should be
injected via `$hookmanager` or a `<link>` tag echo, not via llxHeader parameter.
**Fix:** Injected CSS via `echo '<link rel="stylesheet" href="'.DOL_URL_ROOT.'/custom/b3eqng/css/b3eqng.css">';`
immediately after `llxHeader()` call.

---

### BUG 8 — `getDolGlobalString()` function missing on older Dolibarr
**Files:** `admin/setup.php`, `pages/coa.php`
**Cause:** `getDolGlobalString()` was introduced in Dolibarr 17. On 15/16 it doesn't
exist, causing fatal errors.
**Fix:** Replaced with `$conf->global->B3EQNG_VERSION ?? 'default'` pattern with
a compatibility helper `b3eq_conf()` defined in `b3eqng.class.php`.

---

### BUG 9 — `init()` SQL execution broke on multi-statement files
**File:** `modB3eqng.class.php` `init()` method
**Cause:** The SQL seed has comments, blank lines, and multi-row INSERTs with commas.
The explode-on-semicolon approach split these incorrectly, producing broken SQL fragments.
**Fix:** `init()` now delegates entirely to `_load_tables()` for the CREATE statements,
and calls the seed via phpMyAdmin-compatible chunked execution in `install_data.php`.

---

### BUG 10 — `llx_b3eqng_seed.sql` ran on every `_load_tables()` call
**File:** `modB3eqng.class.php`
**Cause:** `$this->_load_tables = '/b3eqng/sql/'` causes Dolibarr to run ALL `.sql`
files in that directory on every activation, including the drop file. This wiped data
on any module toggle.
**Fix:** `_load_tables` only points to `llx_b3eqng_create.sql` (table structure).
Seed data is injected separately via `install_data.php` which is called once from `init()`.

---

## v2 New Features (from assessment)

| Feature | File(s) |
|---|---|
| Immutable audit trail (append-only, hash-chained) | `class/audit_logger.class.php`, `pages/audit_trail.php` |
| Fixed asset depreciation schedules | `pages/assets.php`, `data/asset_depreciation_rules.json` |
| FX revaluation engine (unrealized gain/loss) | `class/b3eqng.class.php::revalueFX()`, `pages/fx_revaluation.php` |
| n8n OCR invoice ingestion workflow | `n8n/workflows/invoice_ocr_ingest.json` |
| n8n FX rate sync workflow | `n8n/workflows/fx_rate_sync.json` |
| n8n open banking sync workflow | `n8n/workflows/open_banking_sync.json` |
| Storno (reversal) entry engine | `class/b3eqng.class.php::createStorno()` |
| Payroll PAYE calculator page | `pages/payroll.php` |
| CIT estimator page | `pages/cit_estimator.php` |
