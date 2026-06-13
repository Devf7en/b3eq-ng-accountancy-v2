# b3Ɛq Nigerian Accountancy v2.0.0 — FTP Install Guide
`tagged: b3Ɛq Nigerian Accountancy`

> For shared hosting with no SSH access. Works on cPanel, Plesk, DirectAdmin, or any host with FTP + phpMyAdmin.

---

## What You Need

- FTP client (FileZilla recommended — free)
- phpMyAdmin access (available on all major shared hosts via cPanel)
- Your Dolibarr admin login
- This zip unpacked on your local machine

---

## Step 1 — Unpack the zip

Unzip `b3eq-ng-accountancy-v2.0.0.zip` on your local machine.

You need the folder at:
```
b3eq-ng-accountancy-v2/dolibarr/htdocs/custom/b3eqng/
```
That entire `b3eqng/` folder is what you upload.

---

## Step 2 — Connect via FTP

Open FileZilla. Connect to your server with your FTP credentials (from cPanel → FTP Accounts).

Navigate on the **remote side** to your Dolibarr root. The landmark file is `main.inc.php` — look for it. Common paths:

| Host type | Dolibarr root |
|---|---|
| cPanel default | `public_html/dolibarr/htdocs/` |
| Subdomain install | `public_html/erp/htdocs/` |
| Root install | `public_html/htdocs/` |
| VPS/custom | `/var/www/dolibarr/htdocs/` |

Once you see `main.inc.php` in the remote panel, you are in `htdocs/`. Navigate into `custom/`. If `custom/` doesn't exist, right-click → Create directory → name it `custom`.

---

## Step 3 — Upload the module

Drag the entire `b3eqng/` folder from your local machine into the remote `custom/` directory.

After upload, remote path should look like:
```
htdocs/
└── custom/
    └── b3eqng/
        ├── └── modules/
        │       └── modB3eqng.class.php     ← MUST be here for Dolibarr 22.0.1
        ├── class/
        ├── css/
        ├── langs/
        ├── pages/
        ├── scripts/
        └── sql/
```

**File permissions** (set in FileZilla: right-click → File permissions):
- All `.php` files → `644`
- All `.sql`, `.json`, `.css`, `.lang` → `644`
- All directories → `755`

---

## Step 4 — Run the SQL seed via phpMyAdmin

Open phpMyAdmin (cPanel → Databases → phpMyAdmin).

1. Click your Dolibarr database in the left panel (usually named `dolibarr` or `cpanelusername_dolibarr`)
2. Click the **Import** tab at the top
3. Click **Choose File**
4. Upload: `b3eq-ng-accountancy-v2/dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_create.sql`
5. Scroll to bottom → click **Go**
6. You should see "Import has been successfully finished" — this creates the custom tables
7. Repeat for: `sql/llx_b3eqng_seed.sql`
8. You should see "Import has been successfully finished" — this seeds all 92 accounts, VAT codes, journals

**If you see errors:** The most common error is "Table already exists" — this is safe to ignore (INSERT IGNORE handles it). If you see column errors, check you are on Dolibarr 15+.

**Verify** by running this query in phpMyAdmin → SQL tab:
```sql
SELECT COUNT(*) as accounts FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME';
SELECT COUNT(*) as journals FROM llx_accounting_journal WHERE code LIKE 'JV-%';
SELECT COUNT(*) as wht_codes FROM llx_b3eqng_wht_codes WHERE entity=1;
```
Expected: `92`, `9`, `10`

---

## Step 5 — Activate the module in Dolibarr

1. Log into your Dolibarr instance as admin
2. Go to: **Home → Setup → Modules/Applications**
3. In the search box type: `b3eqng`
4. You should see **"B3eqng"** listed under Financial modules
5. Click the **toggle switch** to activate it
6. Dolibarr will show a green confirmation banner

**If the module does not appear:**
- Check that `modB3eqng.class.php` is exactly at `htdocs/custom/b3eqng/modB3eqng.class.php`
- The folder name must be exactly `b3eqng` (lowercase, no spaces, no hyphens)
- Check file permissions: `modB3eqng.class.php` must be `644`
- Try: Home → Setup → Security → Clear cache

---

## Step 6 — Configure the module

After activation, a new left-menu section **"NG Accountancy"** appears under Financial.

Go to: **Financial → NG Accountancy → Settings**

Configure:
| Setting | Your value |
|---|---|
| Company Size | SMALL / MEDIUM / LARGE (sets your CIT rate) |
| FIRS TIN | Your 10-digit TIN |
| VAT Number | Your FIRS VAT registration number |
| State | Your state of registration |
| VAT Registered | Check if turnover ≥ ₦25m |

Click **Save Settings**.

---

## Step 7 — Verify everything works

Visit each menu item in order:

- **Chart of Accounts** → should show 92 accounts in a searchable table
- **Tax Rates & Codes** → expandable accordion, all NTA 2025 rates
- **WHT Calculator** → enter a gross amount, select type, click Calculate
- **VAT Return** → select previous month, should pull live Dolibarr invoices
- **Payroll & PAYE** → enter salary components, calculates PAYE + levies
- **CIT Estimator** → enter turnover + profit, see full tax breakdown
- **Fixed Assets** → add an asset, see depreciation schedule
- **FX Revaluation** → save exchange rates, run period-end revaluation
- **Compliance Calendar** → live RED/AMBER/GREEN dashboard
- **Audit Trail** → shows logged events (empty until you post entries)

---

## Troubleshooting Common Issues

| Symptom | Cause | Fix |
|---|---|---|
| Module not in list | Wrong folder name or path | Ensure folder is `b3eqng` at `htdocs/custom/b3eqng/` |
| 500 error on pages | PHP error in include | Check `b3eqng_init.php` — it walks up to find `main.inc.php` |
| "Class not found" | File permission issue | Set all `.php` to `644`, directories to `755` |
| Pages show "No accounts" | SQL seed not run | Run `llx_b3eqng_create.sql` then `llx_b3eqng_seed.sql` via phpMyAdmin |
| Menu items give 404 | URL path wrong in module | Should be fixed in v2 — `modB3eqng.class.php` uses `/custom/b3eqng/pages/` |
| "Access forbidden" | Rights not propagated | Log out and log back in, or visit Home → Setup → Permissions |
| CSS not loading | cPanel server caching | Hard refresh (Ctrl+Shift+R), or clear cache in Dolibarr admin |
| `getDolGlobalString` error | Dolibarr < 17 | Use `b3eq_conf()` wrapper — already used in all v2 pages |

---

## Multi-Entity Deployment

If your Dolibarr has multiple entities (companies):

In phpMyAdmin, run the seed for each entity. For entity 2:
```sql
-- Copy accounts for entity 2
INSERT INTO llx_accounting_account (entity, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active)
SELECT 2, fk_pcg_version, pcg_type, pcg_subtype, numero_compte, label_compte, active
FROM llx_accounting_account WHERE entity=1 AND fk_pcg_version='NG-IFRS-SME';

-- Copy journals for entity 2
INSERT INTO llx_accounting_journal (entity, code, label, nature, active)
SELECT 2, code, label, nature, active FROM llx_accounting_journal WHERE entity=1 AND code LIKE 'JV-%';

-- Copy WHT codes for entity 2
INSERT INTO llx_b3eqng_wht_codes (entity, code, label, rate, account_dr, account_cr, active, date_creation)
SELECT 2, code, label, rate, account_dr, account_cr, active, NOW() FROM llx_b3eqng_wht_codes WHERE entity=1;
```

---

## Uninstall (if needed)

1. Dolibarr: Home → Setup → Modules → B3eqng → **Deactivate** (leaves all data intact)
2. FTP: Delete the `htdocs/custom/b3eqng/` folder
3. phpMyAdmin (only if full wipe): Import `sql/llx_b3eqng_drop.sql`

---

*b3Ɛq Nigerian Accountancy v2.0.0 · © 2026 Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)*
