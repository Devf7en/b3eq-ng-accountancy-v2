# b3Ɛq Nigerian Accountancy — Master TODO

`tagged: b3Ɛq Nigerian Accountancy`

> Living roadmap. Keep this file current as the single source of truth for delivery, quality, and future-proof decisions.
> Status: [ ] = todo [~] = in progress [x] = done [!] = blocked/needs decision

---

## Current status

- **Foundation is built:** repo, docs, module scaffold, data assets, n8n workflows, deploy scripts, and a working devcontainer exist.
- **Next focus:** solidify Dolibarr native module behavior, complete deployment automation, and add compliance-grade process automation.
- **Future-proofing themes:** containerized developer environment, API-first design, seed automation, clear documentation, and compliance-ready workflow orchestration.

---

## CROSS-DEVICE WORKSPACE & HANDOFF

_Any machine opening this project should inherit the same workspace, config, and handoff context._

- [x] Add `.vscode/` workspace settings, recommended extensions, and tasks
- [x] Add `b3eq-ng-accountancy-v2.code-workspace` for consistent project opens
- [x] Document cross-machine workflow in `README.md`
- [ ] Add explicit handoff checklist for the next device owner
- [ ] Add a `git pull origin main` / `git status` reminder into the workspace onboarding flow
- [ ] Add portable workspace notes for remote container entry and follow-through

---

## PHASE 0 — Foundation & Data Layer

_Single source of truth. Everything downstream depends on this._

- [x] Create project monorepo structure
- [x] Write `README.md`
- [x] Write `TODO.md` (this file)
- [x] Write `CHANGELOG.md`
- [x] Write `VERSION`
- [x] Write `data/chart_of_accounts.json`
- [x] Write `data/tax_rates.json`
- [x] Write `data/compliance_calendar.json`
- [x] Write `data/journals.json`
- [x] Write `data/asset_depreciation_rules.json`
- [x] Write `data/open_banking_providers.json`
- [ ] Add `data/wht_types.json` — WHT table by transaction type and payer category
- [ ] Add `docs/TAX_NOTES.md` — tax rule reference, FIRS/NTA citations, field mappings
- [x] Write `docs/ARCHITECTURE.md`
- [x] Write `docs/BRANDING_AND_DEPLOY.md`
- [ ] Add `docs/DEPLOY.md` — multi-instance deploy and release checklist

### Best-practice foundation

- [ ] Extend existing CI workflow in `.github/workflows/ci.yml` to cover linting, formatting, and artifact validation
- [ ] Add automated end-to-end smoke tests for the devcontainer and Docker stack
- [ ] Keep `data/*.json` as the authoritative seed source for both React and Dolibarr
- [ ] Add a `docs/CHANGELOG_GUIDE.md` or release notes standard for future versions
- [ ] Standardize semantic versioning and release tagging in `VERSION`

---

## PHASE 1 — React Dashboard & Data App

_Modern reference UI that validates the product experience and accelerates customer feedback._

- [x] Initial React app shell: `react/b3eq-advanced-ui.jsx`
- [ ] Replace hardcoded UI data with live JSON-driven data from `data/*.json`
- [ ] Add reusable dashboard components for COA, taxes, journals, compliance, WHT, VAT, payroll, and CIT
- [ ] Add dark/light theme support with a default brand-forward dark mode
- [ ] Make the app mobile-first and responsive for field use
- [ ] Build export features: COA → CSV, tax tables → PDF, compliance checklist → PDF
- [ ] Rebrand UI to the f7en palette and typography system
- [ ] Add an interactive annual compliance checklist with state and print support
- [ ] Add a payroll calculator for PAYE, pension, NHF, and NSITF
- [ ] Add a CIT estimator with size classification, minimum tax, and development levy logic
- [ ] Add state tax reference content for LIRS/RIRS and multi-state notes
- [ ] Add a deployable SPA mode for customer demos and partner review

### React future-proof upgrades

- [ ] Separate UI/logic into data, business, and presentation layers
- [ ] Use JSON-driven configuration for tabs, tables, and data mappings
- [ ] Add analytics hooks for feature usage and onboarding friction
- [ ] Add a theme config file and design tokens for consistent branding

---

## PHASE 2 — Dolibarr Native PHP Module

_The production-grade module that installs into Dolibarr and owns Nigerian accounting workflows._

### 2A — Module scaffold and registration

- [x] Create directory structure: `dolibarr/htdocs/custom/b3eqng/`
- [ ] Confirm or complete `modB3eqng.class.php` — descriptor, menus, permissions, version
- [ ] Confirm or complete `core/modules/modB3eqng.class.php` — module registration and dependencies
- [ ] Update `langs/en_US/b3eqng.lang` with all module labels and user-facing strings
- [ ] Verify activation/deactivation flow in Dolibarr module list

### 2B — SQL layer and seed automation

- [x] Provide `sql/llx_b3eqng_create.sql`
- [x] Provide `sql/llx_b3eqng_seed.sql`
- [x] Provide `sql/llx_b3eqng_drop.sql`
- [ ] Validate the seed implementation against clean Dolibarr 17+ and 18+ instances
- [ ] Add idempotent seed semantics and safe rerun behavior
- [ ] Add schema comments or docs for custom tables and audit fields

### 2C — Admin & configuration

- [x] Provide `admin/setup.php`
- [ ] Add `admin/about.php` for module info, changelog, and support guidance
- [ ] Add entity registration settings for multi-company support
- [ ] Add VAT registration, TIN, state registration, and fiscal year configuration
- [ ] Add security controls for admin access and config changes

### 2D — Functional business pages

- [x] Provide `pages/coa.php`
- [x] Provide `pages/taxes.php`
- [x] Provide `pages/wht_calc.php`
- [x] Provide `pages/vat_return.php`
- [x] Provide `pages/compliance.php`
- [x] Provide `pages/payroll.php`
- [x] Provide `pages/cit_estimator.php`
- [x] Provide `pages/assets.php`
- [x] Provide `pages/audit_trail.php`
- [x] Provide `pages/fx_revaluation.php`
- [ ] Enhance `vat_return.php` to auto-aggregate invoice values and generate FIRS-ready summaries
- [ ] Add compliance status badges and links to related journals

### 2E — Business logic classes

- [x] Provide `class/b3eqng.class.php`
- [x] Provide `class/api_b3eqng.class.php`
- [x] Provide `class/b3eqng_init.php`
- [x] Provide `class/audit_logger.class.php`
- [ ] Review and consolidate business logic for WHT, VAT, PAYE, CIT, pension, and compliance
- [ ] Add API endpoints for chart of accounts, tax rates, WHT calc, VAT return, and compliance status
- [ ] Document internal class responsibilities and extension points

### 2F — Hooks and workflow automation

- [ ] Add invoice validation hook to post output VAT automatically
- [ ] Add supplier invoice hook to post input VAT automatically
- [ ] Add supplier payment hook to apply WHT deductions automatically
- [ ] Add payroll hook to generate PAYE, pension, NHF, and NSITF journal drafts
- [ ] Add scheduled reminders for compliance milestones and filings

### 2G — CSS / brand polish

- [x] Provide `css/b3eqng.css`
- [ ] Apply f7en brand overrides to Dolibarr module pages
- [ ] Add dark module skin, typographic scale, and token-driven spacing
- [ ] Ensure consistent brand experience across Dolibarr and React UIs

---

## PHASE 3 — Deployment & provisioning

_One command deploys the module to any b3Ɛq instance, with validation and rollback support._

### 3A — SSH / CLI deploy

- [x] Provide `scripts/deploy.sh`
- [x] Provide `scripts/health_check.sh`
- [x] Harden `deploy.sh` for safe rsync, remote SQL execution, and configuration validation
- [x] Harden `health_check.sh` to return machine-readable status and fail fast on missing module assets
- [ ] Add deploy validation for Dolibarr version, module path, and DB seed state

### 3B — API-based provisioning

- [x] Provide `scripts/seed_via_api.sh`
- [ ] Expand API deploy to support idempotent seeding, retries, and soft validation
- [ ] Add support for Dolibarr API credential rotation and secure storage
- [ ] Add a snapshot / dry-run mode for pre-deploy validation

### 3C — n8n multi-instance deploy

- [x] Provide `n8n/workflows/multi_instance_deploy.json`
- [ ] Add webhook-triggered deploy workflow with full success/failure reporting
- [ ] Add rollback or cleanup step for partial deployments
- [ ] Add live deploy reporting to Slack/Teams/WhatsApp

---

## PHASE 4 — Compliance automation

_Middleware that keeps the business on time with VAT, WHT, PAYE, and regulatory filings._

- [x] Provide `n8n/workflows/vat_monthly_reminder.json`
- [x] Provide `n8n/workflows/wht_monthly_schedule.json`
- [x] Provide `n8n/workflows/compliance_dashboard.json`
- [ ] Add `n8n/workflows/paye_monthly_export.json`
- [ ] Add template files for CSV exports and forms
- [ ] Add calendar-driven reminders and cross-channel notifications
- [ ] Add audit trail for automated compliance actions and approvals

---

## PHASE 5 — E-invoicing & tax portal integration

_Prepare for NTA 2025 mandates and future government API requirements._

- [ ] Research TaxPro Max / NTA API contracts and auth flows
- [ ] Add invoice IRN generation and capture for VAT filings
- [ ] Add API retry, queueing, and fallback handling for external tax services
- [ ] Validate every invoice > ₦25,000 is traceable to a tax reference
- [ ] Document the integration and compliance monitoring process

---

## PHASE 6 — State tax support

_Add coverage for Lagos, Rivers, Ogun, and other regional tax regimes._

- [ ] Add `data/state_tax_rules.json` for PAYE routing and state levies
- [ ] Add state registration config and compliance workflow
- [ ] Add state-specific calculation engines for LIRS, RIRS, and OIRSA
- [ ] Add split exports for multi-state payroll and remittance

---

## PHASE 7 — SaaS / multi-tenant mode

_Build the platform foundation to sell Nigerian accounting as a service._

- [ ] Define a multi-tenant data model for entities, tax profiles, and billing
- [ ] Add per-entity onboarding and configurable tax preferences
- [ ] Add billing, metering, and subscription controls
- [ ] Add white-label packaging for reseller distribution
- [ ] Add landing page and commercial messaging for product launch

---

## Cross-cutting best practices

- [ ] Add CI/CD as code for Docker, PHP, JS, and n8n artifacts
- [ ] Add static analysis / linting for PHP, JSON, shell scripts, and React
- [ ] Add automated smoke tests for module install, seed, and page load
- [ ] Add security review checklist for configuration, secrets, and network access
- [ ] Keep business logic data-driven and decoupled from presentation
- [ ] Maintain a single source of truth in `data/` and use it for both UI and seed logic
- [ ] Keep docs living: update architecture, branding, deploy, and compliance docs with every release

---

## Backlog / future ideas

- [ ] Transfer pricing and related documentation module
- [ ] Pioneer status and special incentive tracker
- [ ] Immutable audit log of all tax filings and remittances
- [ ] FIRS TIN verification API integration
- [ ] CAC annual return reminder and business registry sync
- [ ] PFA / pension fund remittance tracker with RSA PIN validation
- [ ] PENCOM compliance reports and state processing
- [ ] Foreign currency tax treatment with CBN rate sourcing
- [ ] Sector-specific compliance submodules (Oil & Gas, Banking, Insurance)

---

## SESSION LOG

_Track which Claude session built what._

| Date       | Session   | What Was Built                                                                                             |
| ---------- | --------- | ---------------------------------------------------------------------------------------------------------- |
| 2026-06-07 | Session 1 | React dashboard (7 tabs), all data arrays, WHT calc, VAT workbench, Dolibarr guide                         |
| 2026-06-07 | Session 2 | README, TODO, CHANGELOG, VERSION, data JSONs, SQL seed, PHP module skeleton, deploy scripts, n8n workflows |
| 2026-06-12 | Session 3 | Refined TODO roadmap, confirmed repo status, updated CI and compliance delivery notes                      |

---

_Last updated: 2026-06-12_
_Owner: dev.f7en / Foundations Aesthetics Resource / DCRI-PPS SmartAPPS_
