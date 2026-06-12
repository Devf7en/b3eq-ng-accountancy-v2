# b3Ɛq Nigerian Accountancy v2.0.0
### `tagged: b3Ɛq Nigerian Accountancy`

> **Sovereign, self-hosted, NTA 2025–compliant accounting infrastructure**
> for Dolibarr / b3Ɛq instances operating under Nigerian tax law.
> v2.0.0 — 10 bug fixes from v1 + enterprise-grade feature expansion.

---

## What's New in v2

| Feature | Status |
|---|---|
| All 10 v1 bugs fixed (module invisible, SQL column names, include paths, rights, CSS, etc.) | ✅ |
| Payroll & PAYE Calculator (6-band, CRA, pension, NHF, NSITF, journal entry) | ✅ |
| CIT Estimator (size classification, Dev Levy, CGT, minimum tax) | ✅ |
| Fixed Assets Register (straight-line + declining balance depreciation) | ✅ |
| FX Revaluation Engine (IAS 21, unrealised gain/loss, CBN rate management) | ✅ |
| Immutable Audit Trail (SHA-256 hash-chained, Storno reversal engine) | ✅ |
| n8n FX Rate Sync (daily open.er-api pull, 8 currencies) | ✅ |
| n8n OCR Invoice Ingestion (Mindee OCR, auto WHT+VAT detection) | ✅ |
| n8n Open Banking Sync (Mono Nigeria nightly bank feed) | ✅ |
| React Advanced UI: Drilldown COA Tree + Reconciliation Board | ✅ |
| FTP Install Guide (shared hosting, phpMyAdmin seed) | ✅ |
| `asset_depreciation_rules.json` — 10 IFRS-aligned categories | ✅ |
| `open_banking_providers.json` — Mono, Okra, Nordigen, Plaid routing | ✅ |

---

> Note: Chat and debug notes are saved in `CoPilotChat.mdc`.
>
> Module quality tooling lives in `dolibarr/htdocs/custom/b3eqng/` and includes `composer.json`, `modulebuilder.txt`, `.editorconfig`, and `test/test_runner.php`.
>
> Run `cd dolibarr/htdocs/custom/b3eqng && php test/test_runner.php` to validate core business logic.

## Three delivery layers

| Layer | What | Path |
|---|---|---|
| **Dolibarr Module** | Native PHP module — 11 pages, full NTA 2025 compliance | `dolibarr/htdocs/custom/b3eqng/` |
| **React Dashboard** | Interactive reference UI + Drilldown COA + Reconciliation Board | `react/` |
| **n8n Workflows** | Quantum Middleware — 7 automation workflows | `n8n/workflows/` |

---

## FTP Install (Shared Hosting — No SSH Required)

See `docs/FTP_INSTALL_GUIDE.md` for the full walkthrough.

**Short version:**

1. Upload `dolibarr/htdocs/custom/b3eqng/` → `htdocs/custom/b3eqng/` on your server
2. Set file permissions: `.php` → `644`, directories → `755`
3. phpMyAdmin → Import `sql/llx_b3eqng_create.sql`, then `sql/llx_b3eqng_seed.sql`
4. Dolibarr: **Home → Setup → Modules → B3eqng → Activate**
5. **Financial → NG Accountancy → Settings** → enter TIN, VAT number, state

---

## Module Pages (v2 — 11 pages)

| Page | URL | What it does |
|---|---|---|
| Chart of Accounts | `/custom/b3eqng/pages/coa.php` | 92-account IFRS COA, searchable, filterable, with tax codes |
| Tax Rates & Codes | `/custom/b3eqng/pages/taxes.php` | All NTA 2025 rates, expandable by category |
| WHT Calculator | `/custom/b3eqng/pages/wht_calc.php` | WHT calc + journal entry + TIN toggle + small co. exemption |
| VAT Return | `/custom/b3eqng/pages/vat_return.php` | Live Dolibarr invoice pull + net VAT + journal posting |
| Payroll & PAYE | `/custom/b3eqng/pages/payroll.php` | PAYE bands + pension + NHF + NSITF + payslip + journal |
| CIT Estimator | `/custom/b3eqng/pages/cit_estimator.php` | CIT + Dev Levy + CGT + minimum tax + journal entry |
| Fixed Assets | `/custom/b3eqng/pages/assets.php` | Asset register + straight-line/declining depr + monthly journal |
| FX Revaluation | `/custom/b3eqng/pages/fx_revaluation.php` | CBN rate management + IAS 21 unrealised gain/loss |
| Compliance Calendar | `/custom/b3eqng/pages/compliance.php` | Live RED/AMBER/GREEN status, all NRS/FIRS deadlines |
| Audit Trail | `/custom/b3eqng/pages/audit_trail.php` | Immutable hash-chained log of all postings |
| Settings | `/custom/b3eqng/admin/setup.php` | TIN, VAT no., state, company size, fiscal year |

---

## n8n Workflows (7)

| Workflow | Trigger | What it does |
|---|---|---|
| `vat_monthly_reminder` | 15th of month | Pulls invoices, calculates VAT, sends summary |
| `wht_monthly_schedule` | 20th of month | Generates Form A WHT schedule CSV |
| `compliance_dashboard` | Daily 08:00 WAT | RED/AMBER/GREEN Slack digest |
| `multi_instance_deploy` | Webhook POST | Deploys COA + journals to any remote instance |
| `fx_rate_sync` | Weekdays 07:00 | Pulls 8 currency rates from open.er-api, saves to b3Ɛq |
| `invoice_ocr_ingest` | Webhook POST (PDF) | Mindee OCR → extracts WHT + VAT from supplier invoices |
| `open_banking_sync` | Nightly 02:00 | Mono Nigeria bank feed → categorised transactions in Dolibarr |

---

## Key Classes

| Class | File | Purpose |
|---|---|---|
| `B3eqNG` | `class/b3eqng.class.php` | All calculations: WHT, VAT, PAYE, CIT, pension, FX reval, depreciation |
| `B3eqAuditLogger` | `class/audit_logger.class.php` | SHA-256 hash-chained audit trail + Storno reversal engine |
| `B3eqNGApi` | `class/api_b3eqng.class.php` | 8 REST API endpoints on Dolibarr's API layer |
| Bootstrap | `class/b3eqng_init.php` | Shared page init: finds main.inc.php, loads CSS, guards rights |

---

## Repository Structure

```
b3eq-ng-accountancy-v2/
├── README.md / TODO.md / CHANGELOG.md / BUGFIXES.md / VERSION
├── data/
│   ├── chart_of_accounts.json       ← 92 accounts, full metadata
│   ├── tax_rates.json               ← All NTA 2025 rates
│   ├── compliance_calendar.json     ← Deadlines + penalties
│   ├── journals.json                ← 9 journal definitions
│   ├── asset_depreciation_rules.json← 10 IFRS asset categories
│   └── open_banking_providers.json  ← Mono, Okra, Nordigen, Plaid routing
├── docs/
│   ├── FTP_INSTALL_GUIDE.md         ← Shared hosting step-by-step
│   ├── ARCHITECTURE.md
│   └── BRANDING_AND_DEPLOY.md
├── dolibarr/htdocs/custom/b3eqng/
│   ├── modB3eqng.class.php          ← Module descriptor (FIXED v2)
│   ├── admin/setup.php              ← Admin config page
│   ├── class/
│   │   ├── b3eqng_init.php          ← Shared page bootstrap (NEW v2)
│   │   ├── b3eqng.class.php         ← Core calculations
│   │   ├── audit_logger.class.php   ← Immutable audit trail (NEW v2)
│   │   └── api_b3eqng.class.php     ← REST API
│   ├── css/b3eqng.css               ← f7en sovereign brand
│   ├── langs/en_US/b3eqng.lang
│   ├── pages/                       ← 11 functional pages
│   ├── scripts/install_data.php     ← One-time data seeder (NEW v2)
│   └── sql/
│       ├── llx_b3eqng_create.sql    ← Custom table structure (FIXED v2)
│       ├── llx_b3eqng_seed.sql      ← Data seed (FIXED v2 column names)
│       └── llx_b3eqng_drop.sql
├── n8n/workflows/                   ← 7 automation workflows
├── react/
│   ├── b3eq-nigerian-accountancy.jsx← 7-tab reference dashboard
│   └── b3eq-advanced-ui.jsx         ← Drilldown COA + Reconciliation Board (NEW v2)
└── scripts/
    ├── deploy.sh                    ← SSH deployer
    ├── seed_via_api.sh              ← REST API seeder
    └── health_check.sh              ← Post-deploy verification
```

---

## Coverage

### Taxes (NTA 2025)
VAT 7.5% / Zero-rated / Exempt · CIT 0%/20%/30% · Development Levy 4% ·
WHT (10 transaction types, WHT Regs 2024) · PAYE 6-band · CGT 30%/10% ·
Stamp Duty · Pension 10%/8% · NSITF 1% · NHF 2.5% · ITF 1% · NITDA 1%

### New in v2
FX revaluation (IAS 21) · Fixed asset depreciation (IAS 16) ·
Immutable audit trail · Storno reversal engine · OCR invoice ingestion ·
Open banking sync (Mono Nigeria) · Drilldown COA tree · Reconciliation board

---

## Development Environment

This repository includes a full local stack and dev container:

- `docker-compose.yml` boots:
  - Dolibarr PHP/Apache on `http://localhost:8080`
  - MySQL database
  - phpMyAdmin on `http://localhost:8081`
  - n8n workflow engine on `http://localhost:5678`
- `.devcontainer/` is ready for VS Code Remote - Containers.

Start the stack locally:

```bash
docker compose up -d
```

Stop it:

```bash
docker compose down
```

Open the project in VS Code and choose **Remote-Containers: Reopen in Container**.

---

## License
© 2026 Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)
Internal sovereign infrastructure — not for redistribution without written consent.

*b3Ɛq: Bodacious Business Builder · Exponential Quantum*
