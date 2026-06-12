# b3Ɛq Nigerian Accountancy — Master TODO
`tagged: b3Ɛq Nigerian Accountancy`

> Living task list. Update status inline. Add new items at bottom of relevant phase.
> Status: [ ] = todo  [~] = in progress  [x] = done  [!] = blocked/needs decision

---

## PHASE 0 — Foundation & Data Layer
*Single source of truth. Everything downstream depends on this.*

- [x] Create project monorepo structure
- [x] Write README.md
- [x] Write TODO.md (this file)
- [x] Write CHANGELOG.md
- [x] Write VERSION file (1.0.0)
- [ ] Write `data/chart_of_accounts.json` — 90 accounts, full metadata
- [ ] Write `data/tax_rates.json` — all NTA 2025 rates + effective dates
- [ ] Write `data/compliance_calendar.json` — deadlines + penalties
- [ ] Write `data/journals.json` — 9 journal definitions
- [ ] Write `data/wht_types.json` — WHT table by transaction type
- [ ] Write `docs/TAX_NOTES.md` — deep-dive references for each tax type
- [ ] Write `docs/ARCHITECTURE.md` — system design and data flow diagram
- [ ] Write `docs/BRANDING.md` — f7en token map (fonts, colours, variables)
- [ ] Write `docs/DEPLOY.md` — multi-instance deploy walkthrough

---

## PHASE 1 — React Dashboard UI
*Reference tool. Lives in Claude artifact. Deployable as standalone SPA.*

- [x] Initial React component — b3eq-nigerian-accountancy.jsx
- [x] Tab 1: Chart of Accounts (searchable, filterable, 90 accounts)
- [x] Tab 2: Tax Rates & Codes (expandable by category, NTA 2025)
- [x] Tab 3: Journals Setup (9 journals with Dolibarr path refs)
- [x] Tab 4: Compliance Calendar (deadlines + penalties)
- [x] Tab 5: WHT Calculator (live calc + journal entry + TIN toggle)
- [x] Tab 6: VAT Declaration Workbench (input/output + net payable)
- [x] Tab 7: Dolibarr Import Guide (8-step config walkthrough)
- [ ] **Rebrand UI to f7en sovereign palette** — see docs/BRANDING.md
  - [ ] Replace blue accent (#0891b2) with f7en red (#e4001b)
  - [ ] Replace DM Sans with Syne (headings) + Space Mono (code)
  - [ ] Apply gold (#c8a84b) authority accents on key metrics
  - [ ] Update background gradient to near-black purple-tinted base
  - [ ] Update tab active state to red/orange gradient
  - [ ] Update section title accent line to red → orange → transparent
- [ ] Pull live data from `data/*.json` (replace hardcoded arrays)
- [ ] Add Tab 8: Payroll Calculator (PAYE bands + pension + NHF + NSITF)
- [ ] Add Tab 9: CIT Estimator (size classification + dev levy + minimum tax)
- [ ] Add Tab 10: Annual Compliance Checklist (printable, checkable)
- [ ] Add Tab 11: State Taxes reference (LIRS, RIRS, cross-state notes)
- [ ] Add export buttons: COA → CSV, Tax Rates → PDF, WHT result → PDF
- [ ] Mobile responsive pass (works on phone for field use)
- [ ] Dark/light toggle (default dark)

---

## PHASE 2 — Dolibarr Native PHP Module
*The real thing. Installs into b3Ɛq / Dolibarr as a first-class module.*

### 2A — Module Scaffold
- [x] Create directory structure: `dolibarr/htdocs/custom/b3eqng/`
- [ ] Write `modB3eqng.class.php` — module descriptor, version, menus, rights
- [ ] Write `core/modules/modB3eqng.class.php` — module registration
- [ ] Write `langs/en_US/b3eqng.lang` — all English string keys
- [ ] Register module in Dolibarr module list (test activation/deactivation)

### 2B — SQL Layer
- [ ] Write `sql/llx_b3eqng_create.sql` — custom tables (audit log, WHT register)
- [ ] Write `sql/llx_b3eqng_seed.sql` — full seed:
  - [ ] Chart of Accounts → `llx_accounting_account`
  - [ ] VAT rates → `llx_c_tva`
  - [ ] WHT codes → `llx_c_chargesociales`
  - [ ] Journals → `llx_accounting_journal`
  - [ ] Tax accounts binding → `llx_accounting_account_chartofaccount`
- [ ] Write `sql/llx_b3eqng_drop.sql` — clean uninstall (idempotent)
- [ ] Test seed on clean Dolibarr 17+ instance
- [ ] Test seed idempotency (run twice, no duplicates)

### 2C — Admin & Config Pages
- [ ] Write `admin/setup.php` — module settings page
  - [ ] Entity selector (multi-entity support)
  - [ ] Company size toggle (small/medium/large → auto-sets CIT rate)
  - [ ] VAT registration status + VAT number field
  - [ ] TIN field (FIRS TIN for WHT exemption logic)
  - [ ] State of registration (for PAYE → routes to correct SIRS)
  - [ ] Fiscal year start/end
- [ ] Write `admin/about.php` — module info + version + changelog

### 2D — Functional Pages
- [ ] Write `pages/coa.php` — Chart of Accounts browser
- [ ] Write `pages/taxes.php` — Tax rates reference
- [ ] Write `pages/wht_calc.php` — WHT calculator (PHP version)
- [ ] Write `pages/vat_return.php` — VAT declaration workbench
  - [ ] Pull from Dolibarr invoices for the period (auto-aggregate)
  - [ ] Generate FIRS-format VAT return summary
  - [ ] One-click journal entry creation for VAT payment
- [ ] Write `pages/compliance.php` — Compliance calendar + status dashboard
  - [ ] Show RED/AMBER/GREEN status for each obligation
  - [ ] Link to Dolibarr journal for each completed remittance

### 2E — Business Logic (Class Layer)
- [ ] Write `class/b3eqng.class.php` — core logic:
  - [ ] `calculateWHT($amount, $type, $hasTIN)` → returns deduction breakdown
  - [ ] `calculateVAT($invoiceLines)` → returns output/input/net
  - [ ] `calculatePAYE($grossSalary, $reliefs)` → returns PAYE deduction
  - [ ] `calculatePension($basicHousingTransport)` → employer + employee
  - [ ] `calculateCIT($turnover, $profit)` → size classification + levy
  - [ ] `getComplianceStatus($period)` → all obligations status for period
  - [ ] `seedNigerianData($db, $entity)` → programmatic seed call
- [ ] Write `class/api_b3eqng.class.php` — REST API wrapper:
  - [ ] GET `/api/b3eqng/coa` — return chart of accounts
  - [ ] GET `/api/b3eqng/taxrates` — return current tax rates
  - [ ] POST `/api/b3eqng/wht/calculate` — WHT calc endpoint
  - [ ] POST `/api/b3eqng/vat/return` — VAT return calculation
  - [ ] GET `/api/b3eqng/compliance/{period}` — compliance status

### 2F — Hooks & Triggers (Auto-behaviour)
- [ ] Hook: on Customer Invoice validation → auto-post Output VAT to 2100
- [ ] Hook: on Supplier Invoice validation → auto-post Input VAT to 1101
- [ ] Hook: on Supplier Payment → auto-deduct WHT based on supplier category
- [ ] Hook: on Payroll run → auto-post PAYE (2120), Pension (2121/2122), NSITF (2124), NHF (2123)
- [ ] Trigger: on month-end → generate compliance reminder notifications
- [ ] Trigger: on 20th of month → auto-generate WHT schedule draft

### 2G — CSS / Brand
- [ ] Write `css/b3eqng.css` — f7en brand overrides for module pages:
  - [ ] Import Syne + Space Mono from Google Fonts
  - [ ] Override `.dolibarr-module-header` with dark/red palette
  - [ ] Style `.b3eq-tax-badge`, `.b3eq-calc-row`, `.b3eq-journal-entry`
  - [ ] Match sovereign dark aesthetic from apps.f7en.biz

---

## PHASE 3 — Multi-Instance Deploy Pipeline
*One command pushes the module to any b3Ɛq Dolibarr instance.*

### 3A — SSH Deploy (direct server access)
- [ ] Write `scripts/deploy.sh`:
  - [ ] Accept `--target` (host), `--user`, `--path` (docroot), `--db-name`
  - [ ] rsync `dolibarr/htdocs/custom/b3eqng/` to target
  - [ ] Execute SQL seed on remote DB
  - [ ] Activate module via Dolibarr DB flag (`llx_const`)
  - [ ] Run health check
- [ ] Write `scripts/health_check.sh`:
  - [ ] Verify module directory exists and is readable
  - [ ] Verify `llx_accounting_account` has ≥ 90 NG records
  - [ ] Verify `llx_c_tva` has NG-VAT-75 rate
  - [ ] Verify journals JV-TX and JV-SA exist
  - [ ] Output JSON status report

### 3B — API Deploy (no SSH, REST only)
- [ ] Write `scripts/seed_via_api.sh`:
  - [ ] Accept `DOLI_URL` + `DOLI_KEY` env vars
  - [ ] POST each COA account via `/api/index.php/accountingaccounts`
  - [ ] POST each VAT code via `/api/index.php/setup/vat`
  - [ ] Verify seeded counts
  - [ ] Output pass/fail per entity type
- [ ] Handle idempotency (check before POST, skip if exists)
- [ ] Handle pagination for large seeds

### 3C — n8n Multi-Instance Deployer Workflow
- [ ] Write `n8n/workflows/multi_instance_deploy.json`:
  - [ ] Webhook trigger: accepts `{ target_url, api_key, entity_id }`
  - [ ] HTTP Request: test connection to target
  - [ ] HTTP Requests: seed COA, VAT codes, WHT codes, journals
  - [ ] Verification step: count seeded records
  - [ ] Notification: Slack/email with pass/fail report
  - [ ] Error handling: rollback on partial failure
- [ ] Build admin UI for the deployer (small dashboard card in b3Ɛq)
  - [ ] Input: target URL + API key
  - [ ] Button: "Deploy b3Ɛq NG Accounting"
  - [ ] Live log: streaming deploy status

---

## PHASE 4 — n8n Compliance Automation
*Quantum Middleware keeps you compliant automatically.*

- [ ] Write `n8n/workflows/vat_monthly_reminder.json`:
  - [ ] Cron: 15th of every month, 09:00 WAT
  - [ ] Query Dolibarr API for all sales invoices in previous month
  - [ ] Query Dolibarr API for all purchase invoices in previous month
  - [ ] Calculate Output VAT, Input VAT, net payable
  - [ ] Send summary to WhatsApp/Slack/email: "VAT due 21st — ₦X,XXX payable"
  - [ ] Create draft journal entry in b3Ɛq for VAT payment
- [ ] Write `n8n/workflows/wht_monthly_schedule.json`:
  - [ ] Cron: 20th of every month, 09:00 WAT
  - [ ] Query all supplier payments in previous month with WHT codes
  - [ ] Generate WHT schedule CSV (Form A format)
  - [ ] Email schedule to finance team
  - [ ] Create remittance journal entry draft
- [ ] Write `n8n/workflows/paye_monthly_export.json`:
  - [ ] Cron: 5th of every month, 09:00 WAT
  - [ ] Pull payroll data from b3Ɛq HR module
  - [ ] Calculate PAYE per employee + totals
  - [ ] Export SIRS-format CSV
  - [ ] Post reminder: "PAYE due 10th — ₦X,XXX for N employees"
- [ ] Write `n8n/workflows/compliance_dashboard.json`:
  - [ ] Cron: daily 08:00 WAT
  - [ ] Check each obligation against last-filed date
  - [ ] Output RED (overdue) / AMBER (due within 5 days) / GREEN (filed)
  - [ ] Post daily digest to Slack/WhatsApp
- [ ] Write `n8n/templates/vat_return_template.csv` — FIRS format
- [ ] Write `n8n/templates/wht_schedule_template.csv` — Form A format
- [ ] Write `n8n/templates/paye_schedule_template.csv` — SIRS format

---

## PHASE 5 — TaxPro Max Integration (e-invoicing NTA 2025 Mandate)
*All VAT-registered businesses must adopt FIRS e-invoicing under NTA 2025.*

- [ ] Research TaxPro Max API endpoints and authentication
- [ ] Write n8n workflow: on Invoice Validated → POST to TaxPro Max
- [ ] Map Dolibarr invoice fields to TaxPro Max schema
- [ ] Handle TaxPro Max IRN (Invoice Reference Number) → store in invoice notes
- [ ] Handle API failures gracefully (queue + retry)
- [ ] Write compliance check: verify all invoices >₦25k have IRN
- [ ] Document TaxPro Max field mapping in docs/TAX_NOTES.md

---

## PHASE 6 — State-Level Taxes
*LIRS (Lagos), RIRS (Rivers), OIRSA (Ogun), etc.*

- [ ] Map state-level personal income tax rules (where they differ from federal)
- [ ] Add `data/state_tax_rules.json` — per-state PAYE routing + rates
- [ ] Admin setting: entity state registration → auto-routes PAYE remittance
- [ ] Add LIRS-specific levies (Development Levy, Business Premises Levy)
- [ ] Add RIRS-specific levies
- [ ] n8n workflow: PAYE export split by state if multi-state employees

---

## PHASE 7 — Multi-Tenant SaaS Mode
*Monetise: sell b3Ɛq NG Accounting to other Nigerian SMEs.*

- [ ] Design multi-tenant entity model (one b3Ɛq instance, many client entities)
- [ ] Per-entity onboarding: company size, state, VAT status, TIN
- [ ] Per-entity tax rate overrides (pioneer status, incentives)
- [ ] Billing integration: charge per-entity per-month
- [ ] White-label: rebrand module for client-facing delivery
- [ ] Build landing page: "b3Ɛq NG Accounting — Nigeria's first NTA 2025-native ERP module"
- [ ] Dolistore listing (optional public distribution)

---

## BACKLOG / IDEAS
*Not scheduled. Add when relevant.*

- [ ] Transfer Pricing documentation module (for MNE groups)
- [ ] Pioneer Status / EDI tax credit tracker (5% annual credit)
- [ ] Audit trail: immutable log of all tax filings and remittances
- [ ] FIRS TIN verification API integration
- [ ] CAC (Corporate Affairs Commission) annual return reminder
- [ ] Pension fund (PFA) remittance tracker with RSA PIN validation
- [ ] PENCOM compliance reports
- [ ] Foreign currency tax treatment (CBN rate sourcing for FX gain/loss)
- [ ] Sector-specific: Oil & Gas (PPT), Banking (FSS), Insurance modules

---

## SESSION LOG
*Track which Claude session built what.*

| Date | Session | What Was Built |
|---|---|---|
| 2026-06-07 | Session 1 | React dashboard (7 tabs), all data arrays, WHT calc, VAT workbench, Dolibarr guide |
| 2026-06-07 | Session 2 | README, TODO, CHANGELOG, VERSION, data JSONs, SQL seed, PHP module skeleton, deploy scripts, n8n workflows |

---

*Last updated: 2026-06-07*  
*Owner: dev.f7en / Foundations Aesthetics Resource / DCRI-PPS SmartAPPS*
