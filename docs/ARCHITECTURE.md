# b3Ɛq Nigerian Accountancy — Architecture
`tagged: b3Ɛq Nigerian Accountancy`

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    b3Ɛq QUANTUM MIDDLEWARE                      │
│                      (n8n Automation)                           │
│  vat_monthly_reminder  │  wht_monthly_schedule  │  compliance  │
│  multi_instance_deploy │  paye_monthly_export   │  dashboard   │
└────────────┬───────────────────────┬────────────────────────────┘
             │ Dolibarr REST API     │ Slack/WhatsApp/Email
             ▼                       ▼
┌─────────────────────────┐   ┌─────────────────────┐
│   b3Ɛq / Dolibarr       │   │  Notification Layer │
│   ten.f7en.net          │   │  (alerts, digests)  │
│                         │   └─────────────────────┘
│  ┌───────────────────┐  │
│  │  b3eqng Module    │  │
│  │  (PHP native)     │  │
│  │                   │  │
│  │  pages/           │  │
│  │  class/           │  │
│  │  sql/             │  │
│  │  css/             │  │
│  └───────────────────┘  │
│                         │
│  llx_accounting_account │  ← 90-account NG COA
│  llx_c_tva              │  ← VAT codes (3)
│  llx_c_chargesociales   │  ← WHT/levy codes (21)
│  llx_accounting_journal │  ← 9 journals
│  llx_const              │  ← module config
└─────────────────────────┘
             ▲
             │ rsync + SQL seed
             │
┌─────────────────────────┐
│   Deploy Pipeline       │
│   scripts/deploy.sh     │  ← SSH-based
│   scripts/seed_via_api  │  ← REST API-based
│   n8n/multi_instance    │  ← Webhook-triggered
└─────────────────────────┘
             ▲
             │
┌─────────────────────────┐
│   Single Source of Truth│
│   data/*.json           │
│   chart_of_accounts     │
│   tax_rates             │
│   compliance_calendar   │
│   journals              │
└─────────────────────────┘
```

## Data Flow: Invoice → Tax Posting

```
Customer Invoice Created
        │
        ▼
[Hook: invoicecard]
b3eqng trigger fires
        │
        ├─ Line items scanned for VAT code
        │  NG-VAT-75 → Debit 1100 (AR), Credit 4000 (Revenue) + 2100 (Output VAT)
        │
        └─ On Validation:
           Post to JV-SA journal
           Update VAT control account (2102)

Supplier Invoice Created
        │
        ▼
[Hook: supplierinvoicecard]
        │
        ├─ Scan supplier category for WHT code
        │  NG-WHT-PRF → Debit 6020 (Prof Fees), Credit 2114 (WHT Payable)
        │                                         Credit 2000 (AP net)
        │
        └─ Input VAT → Debit 1101 (Input VAT)

Month-end (n8n triggers)
        │
        ├─ 5th:  PAYE export → SIRS
        ├─ 7th:  Pension / NHF reminder
        ├─ 15th: VAT summary → prepare return
        ├─ 20th: WHT schedule → Form A draft
        └─ Daily: compliance status → Slack
```

## Multi-Instance Deploy Flow

```
Developer / Admin
      │
      │  POST { target_url, api_key, entity_id }
      ▼
n8n Webhook (multi_instance_deploy.json)
      │
      ├─ 1. Validate input
      ├─ 2. Test API connection
      ├─ 3. Seed COA (90 accounts via API)
      ├─ 4. Seed journals (9 via API)
      ├─ 5. Build deploy report
      ├─ 6. Notify Slack
      └─ 7. Return JSON response
           { success, created, skipped, errors }

For VAT/WHT codes (API endpoint limitations):
      └─ SSH: mysql < llx_b3eqng_seed.sql
         OR
         scripts/deploy.sh --host target.domain.com
```
