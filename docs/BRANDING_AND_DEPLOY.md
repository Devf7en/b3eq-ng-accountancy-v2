# b3Ɛq Nigerian Accountancy — Branding Guide
`tagged: b3Ɛq Nigerian Accountancy`

## f7en Sovereign Brand Tokens

Apply these everywhere: React dashboard, PHP module pages, n8n email templates.

```css
/* Palette */
--b3eq-bg:       #08080d;   /* near-black base */
--b3eq-surface:  #100e16;   /* card surface — purple-tinted */
--b3eq-surface2: #16121f;   /* elevated surface */
--b3eq-border:   #2a1a2e;   /* borders */
--b3eq-accent:   #e4001b;   /* f7en sovereign red — primary CTA */
--b3eq-accent2:  #ff4d1a;   /* orange-red — gradients */
--b3eq-gold:     #c8a84b;   /* authority gold — key metrics */
--b3eq-text:     #f0e8ff;   /* body text */
--b3eq-muted:    #6b5f7a;   /* secondary text */
--b3eq-cyan:     #22d3ee;   /* account codes, data */
--b3eq-green:    #34d399;   /* debit, positive, OK */
--b3eq-red:      #f87171;   /* credit, negative, alert */

/* Typography */
Headings:  Syne (700/800/900) — Google Fonts
Body:      DM Sans (400/500/600)
Monospace: Space Mono (400/700) — amounts, codes, journal entries

/* Gradients */
Header bg:    linear-gradient(135deg, #0a0610, #1a0a12, #0a0610)
CTA button:   linear-gradient(135deg, #e4001b, #ff4d1a)
Section line: linear-gradient(90deg, #e4001b, #ff4d1a, transparent)
Glow:         0 0 24px rgba(228,0,27,0.4)
```

## React Dashboard — Rebrand Checklist

1. **Replace header gradient** — swap `#1d4ed8 → #e4001b` and `#0891b2 → #ff4d1a`
2. **Replace tab active state** — `linear-gradient(135deg, #1d4ed8, #0891b2)` → red/orange
3. **Replace section title underline** — `#1d4ed8 →  #e4001b`
4. **Replace account code colour** — `#38bdf8 → #22d3ee` (cyan stays — it's data)
5. **Replace all `border: "1px solid #1e3a5f"` → `#2a1a2e`**
6. **Add Syne font** for `.b3eqng-section-title` and header
7. **Gold for key metrics** — WHT total, VAT payable, CIT amount → `#c8a84b`

## Dolibarr Module Pages — Rebrand Checklist

`css/b3eqng.css` already ships with all tokens.

In each PHP page, include at the top:
```php
llxHeader('', $langs->trans('B3EQNG_COA_TITLE'), '', '', '', '', [], ['b3eqng/css/b3eqng.css']);
```

Then wrap page content in:
```html
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Nigerian Accountancy</div>
      <div class="b3eqng-header-sub">NTA 2025 · IFRS for SMEs · b3Ɛq</div>
    </div>
  </div>
  <!-- page content -->
</div>
```

---
---

# b3Ɛq Nigerian Accountancy — Deploy Guide
`tagged: b3Ɛq Nigerian Accountancy`

## Prerequisites

| Item | Requirement |
|---|---|
| Dolibarr version | 17.0+ |
| PHP | 7.4+ |
| MySQL/MariaDB | 5.7+ / 10.3+ |
| Accounting module | Must be enabled in target instance |
| API key | My Account → API Key (for API/n8n deploy) |
| SSH key auth | For deploy.sh |

## Method 1 — SSH Deploy (Recommended for ten.f7en.net)

```bash
chmod +x scripts/deploy.sh

./scripts/deploy.sh \
  --host ten.f7en.net \
  --user deploy \
  --docroot /var/www/dolibarr/htdocs \
  --db-host 127.0.0.1 \
  --db-name dolibarr_db \
  --db-user dolibarr \
  --db-pass YOUR_DB_PASSWORD

# Dry run first:
./scripts/deploy.sh --host ten.f7en.net ... --dry-run
```

Then in Dolibarr UI:
1. `Home → Setup → Modules`
2. Search `b3eqng`
3. Click **Activate**
4. Navigate to `Financial → Nigerian Accountancy`

## Method 2 — REST API (No SSH)

```bash
export DOLI_URL="https://ten.f7en.net"
export DOLI_KEY="your_api_key"
chmod +x scripts/seed_via_api.sh
./scripts/seed_via_api.sh
```

Note: VAT and WHT codes require either SSH (SQL seed) or manual entry in Dolibarr UI. The Dolibarr REST API for these endpoints is read-heavy; write endpoints vary by version.

## Method 3 — n8n One-Click Deploy

1. Import `n8n/workflows/multi_instance_deploy.json` into b3Ɛq n8n
2. Set env vars: `DOLI_URL`, `DOLI_API_KEY`, `SLACK_WEBHOOK_URL`
3. Trigger via webhook:

```bash
curl -X POST https://your-n8n.f7en.net/webhook/b3eq-ng-deploy \
  -H "Content-Type: application/json" \
  -d '{
    "target_url": "https://ten.f7en.net",
    "api_key": "YOUR_API_KEY",
    "entity_id": 1,
    "dry_run": false
  }'
```

## Multi-Entity Deployment

For Dolibarr instances with multiple entities (e.g. entity 1 = head office, entity 2 = subsidiary):

```bash
# SSH method — sed substitutes entity ID in SQL
./scripts/deploy.sh --host ten.f7en.net --db-pass PASS ... 
# Then repeat with --entity 2 for second entity

# SQL method directly:
sed 's/entity=1/entity=2/g; s/, 1,/, 2,/g' \
  dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_seed.sql \
  | mysql -u user -p dbname
```

## Post-Deploy Configuration

After activating the module, configure in Dolibarr:
`Financial → Nigerian Accountancy → Settings`

| Setting | Value |
|---|---|
| Company Size | SMALL / MEDIUM / LARGE (auto-sets CIT rate) |
| FIRS TIN | Your 10-digit TIN (enables WHT exemption logic) |
| VAT Registration No | Your FIRS VAT number |
| State of Registration | Lagos / Rivers / etc (routes PAYE to correct SIRS) |
| Fiscal Year | Usually 01-01 to 12-31 |

## n8n Compliance Automation Setup

After deploy, import all 4 n8n workflows:

```
n8n/workflows/vat_monthly_reminder.json
n8n/workflows/wht_monthly_schedule.json
n8n/workflows/compliance_dashboard.json
n8n/workflows/multi_instance_deploy.json
```

Set these environment variables in n8n:
```
DOLI_URL=https://ten.f7en.net
DOLI_API_KEY=your_dolibarr_api_key
SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```

Activate all 4 workflows. The compliance dashboard fires daily at 08:00 WAT.

## Health Check

```bash
chmod +x scripts/health_check.sh
./scripts/health_check.sh \
  --host ten.f7en.net \
  --db-name dolibarr --db-user dolibarr --db-pass PASS
```

Expected output:
```
✓ Chart of Accounts:  90 accounts
✓ VAT codes:          3 codes
✓ WHT codes:          21 codes
✓ Journals:           9 journals
✓ Module flag:        ACTIVE
```

## Rollback / Uninstall

```bash
# Full data wipe (destructive — cannot undo):
mysql -u user -p dbname < dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_drop.sql

# Module only (leaves data intact):
# Dolibarr UI: Home → Setup → Modules → b3eqng → Deactivate
```
