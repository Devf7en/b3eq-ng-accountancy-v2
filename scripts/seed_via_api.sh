#!/usr/bin/env bash
# =============================================================================
# b3Ɛq Nigerian Accountancy — REST API Seeder (No SSH Required)
# tagged: b3Ɛq Nigerian Accountancy
# File:   scripts/seed_via_api.sh
#
# Seeds Nigerian COA, VAT codes, WHT codes, and journals into any remote
# Dolibarr instance using only the Dolibarr REST API. No server access needed.
#
# Usage:
#   export DOLI_URL="https://ten.f7en.net"
#   export DOLI_KEY="your_api_key_here"
#   export DOLI_ENTITY=1
#   ./seed_via_api.sh
#
#   Or inline:
#   DOLI_URL=https://ten.f7en.net DOLI_KEY=abc123 ./seed_via_api.sh
#
# Requirements:
#   - curl, jq
#   - Dolibarr API enabled (Home → Setup → Other setup → Enable REST API)
#   - API key from: My Account → API Key / Tokens
# =============================================================================

set -euo pipefail

# ── Colours ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'
log()  { echo -e "${CYAN}[b3Ɛq]${RESET} $*"; }
ok()   { echo -e "${GREEN}  ✓${RESET} $*"; }
warn() { echo -e "${YELLOW}  ⚠${RESET} $*"; }
fail() { echo -e "${RED}  ✗ FAIL:${RESET} $*"; exit 1; }
skip() { echo -e "${YELLOW}  →${RESET} SKIP (already exists): $*"; }
banner(){ echo -e "\n${BOLD}${CYAN}═══════════════════════════════════════${RESET}"; echo -e "${BOLD}  $*${RESET}"; echo -e "${BOLD}${CYAN}═══════════════════════════════════════${RESET}\n"; }

# ── Config ────────────────────────────────────────────────────────────────────
DOLI_URL="${DOLI_URL:-}"
DOLI_KEY="${DOLI_KEY:-}"
DOLI_ENTITY="${DOLI_ENTITY:-1}"

[[ -z "$DOLI_URL" ]] && fail "DOLI_URL not set. Export it or pass inline."
[[ -z "$DOLI_KEY" ]] && fail "DOLI_KEY not set. Export it or pass inline."

# Strip trailing slash
DOLI_URL="${DOLI_URL%/}"
API="${DOLI_URL}/api/index.php"

# Counters
CREATED=0; SKIPPED=0; ERRORS=0

# ── API helper ────────────────────────────────────────────────────────────────
api_get() {
  local endpoint="$1"
  curl -sf -H "DOLAPIKEY: ${DOLI_KEY}" "${API}/${endpoint}" 2>/dev/null || echo '{}'
}

api_post() {
  local endpoint="$1"
  local data="$2"
  curl -sf -X POST \
    -H "DOLAPIKEY: ${DOLI_KEY}" \
    -H "Content-Type: application/json" \
    -d "$data" \
    "${API}/${endpoint}" 2>/dev/null || echo '{"error":"curl_failed"}'
}

# ── Step 0: Test connection ────────────────────────────────────────────────────
banner "b3Ɛq Nigerian Accountancy — API Seed → ${DOLI_URL}"
log "Testing API connection..."

STATUS=$(api_get "status")
VERSION=$(echo "$STATUS" | jq -r '.success // empty' 2>/dev/null || echo "")
if [[ -z "$VERSION" ]]; then
  fail "Cannot reach Dolibarr API at ${API}/status — check URL and API key"
fi
ok "Connected to Dolibarr at ${DOLI_URL}"

# ── Step 1: Seed Chart of Accounts ────────────────────────────────────────────
log "Step 1/4 — Seeding Chart of Accounts (90 accounts)..."

seed_account() {
  local code="$1" label="$2" type="$3" subtype="$4"

  # Check if exists
  EXISTING=$(api_get "accountingaccounts?sqlfilters=(t.account_number:=:'${code}')" 2>/dev/null || echo '[]')
  COUNT=$(echo "$EXISTING" | jq 'if type=="array" then length else 0 end' 2>/dev/null || echo 0)

  if [[ "$COUNT" -gt 0 ]]; then
    skip "${code} ${label}"
    ((SKIPPED++)) || true
    return
  fi

  PAYLOAD=$(jq -n \
    --arg code "$code" --arg label "$label" \
    --arg type "$type" --arg sub "$subtype" \
    --arg pcg "NG-IFRS-SME" --arg entity "$DOLI_ENTITY" \
    '{
      account_number: $code,
      label: $label,
      pcg_type: $type,
      pcg_subtype: $sub,
      fk_pcg_version: $pcg,
      entity: ($entity | tonumber),
      active: 1
    }')

  RESULT=$(api_post "accountingaccounts" "$PAYLOAD")
  ID=$(echo "$RESULT" | jq -r '. | if type=="number" then . else -1 end' 2>/dev/null || echo -1)

  if [[ "$ID" -gt 0 ]]; then
    ok "${code} — ${label}"
    ((CREATED++)) || true
  else
    warn "Failed: ${code} ${label} — $(echo "$RESULT" | jq -r '.error // .errors[0] // "unknown"' 2>/dev/null)"
    ((ERRORS++)) || true
  fi
}

# Assets
seed_account "1000" "Cash and Cash Equivalents"          "ASSET" "CURRENT_ASSET"
seed_account "1001" "Petty Cash (Naira)"                 "ASSET" "CURRENT_ASSET"
seed_account "1002" "Current Account - CBN/Commercial Banks" "ASSET" "CURRENT_ASSET"
seed_account "1003" "Domiciliary Account (USD/GBP/EUR)"  "ASSET" "CURRENT_ASSET"
seed_account "1100" "Accounts Receivable (Trade Debtors)" "ASSET" "CURRENT_ASSET"
seed_account "1101" "VAT Recoverable (Input VAT)"        "ASSET" "CURRENT_ASSET"
seed_account "1102" "WHT Credit Receivable"              "ASSET" "CURRENT_ASSET"
seed_account "1103" "Staff Advances and Prepayments"     "ASSET" "CURRENT_ASSET"
seed_account "1104" "Prepaid Expenses"                   "ASSET" "CURRENT_ASSET"
seed_account "1105" "Accrued Income"                     "ASSET" "CURRENT_ASSET"
seed_account "1200" "Inventory - Finished Goods"         "ASSET" "CURRENT_ASSET"
seed_account "1201" "Inventory - Raw Materials"          "ASSET" "CURRENT_ASSET"
seed_account "1202" "Inventory - Work-in-Progress"       "ASSET" "CURRENT_ASSET"
seed_account "1300" "Property Plant and Equipment"       "ASSET" "NON_CURRENT_ASSET"
seed_account "1301" "Accumulated Depreciation - PPE"     "ASSET" "NON_CURRENT_ASSET"
seed_account "1302" "Intangible Assets"                  "ASSET" "NON_CURRENT_ASSET"
seed_account "1303" "Accumulated Amortisation"           "ASSET" "NON_CURRENT_ASSET"
seed_account "1304" "Right-of-Use Assets (IFRS 16)"      "ASSET" "NON_CURRENT_ASSET"
seed_account "1400" "Deferred Tax Asset"                 "ASSET" "NON_CURRENT_ASSET"

# Liabilities — tax accounts
seed_account "2000" "Accounts Payable (Trade Creditors)" "LIABILITY" "CURRENT_LIABILITY"
seed_account "2001" "Accrued Expenses"                   "LIABILITY" "CURRENT_LIABILITY"
seed_account "2100" "VAT Payable - Output VAT 7.5%"      "LIABILITY" "CURRENT_LIABILITY"
seed_account "2101" "VAT Payable - Zero-Rated"           "LIABILITY" "CURRENT_LIABILITY"
seed_account "2102" "VAT Control Account"                "LIABILITY" "CURRENT_LIABILITY"
seed_account "2110" "WHT Payable - Dividends 10%"        "LIABILITY" "CURRENT_LIABILITY"
seed_account "2111" "WHT Payable - Interest 10%"         "LIABILITY" "CURRENT_LIABILITY"
seed_account "2112" "WHT Payable - Rent 10%"             "LIABILITY" "CURRENT_LIABILITY"
seed_account "2113" "WHT Payable - Contracts/Supplies 5%" "LIABILITY" "CURRENT_LIABILITY"
seed_account "2114" "WHT Payable - Professional Fees 10%" "LIABILITY" "CURRENT_LIABILITY"
seed_account "2115" "WHT Payable - Director Fees 10%"    "LIABILITY" "CURRENT_LIABILITY"
seed_account "2116" "WHT Payable - Technical Fees 10%"   "LIABILITY" "CURRENT_LIABILITY"
seed_account "2117" "WHT Payable - Commissions 10%"      "LIABILITY" "CURRENT_LIABILITY"
seed_account "2118" "WHT Payable - Royalties 10%"        "LIABILITY" "CURRENT_LIABILITY"
seed_account "2119" "WHT Payable - Construction 2.5%"    "LIABILITY" "CURRENT_LIABILITY"
seed_account "2120" "PAYE Tax Payable"                   "LIABILITY" "CURRENT_LIABILITY"
seed_account "2121" "Pension Payable - Employer 10%"     "LIABILITY" "CURRENT_LIABILITY"
seed_account "2122" "Pension Payable - Employee 8%"      "LIABILITY" "CURRENT_LIABILITY"
seed_account "2123" "NHF Payable 2.5%"                   "LIABILITY" "CURRENT_LIABILITY"
seed_account "2124" "NSITF Levy Payable 1%"              "LIABILITY" "CURRENT_LIABILITY"
seed_account "2125" "ITF Levy Payable 1%"                "LIABILITY" "CURRENT_LIABILITY"
seed_account "2126" "NITDA Levy Payable 1%"              "LIABILITY" "CURRENT_LIABILITY"
seed_account "2130" "CIT Payable - Current Year"         "LIABILITY" "CURRENT_LIABILITY"
seed_account "2131" "Development Levy Payable 4%"        "LIABILITY" "CURRENT_LIABILITY"
seed_account "2132" "Capital Gains Tax Payable"          "LIABILITY" "CURRENT_LIABILITY"
seed_account "2133" "Stamp Duty Payable"                 "LIABILITY" "CURRENT_LIABILITY"
seed_account "2140" "Short-term Loans and Borrowings"    "LIABILITY" "CURRENT_LIABILITY"
seed_account "2141" "Customer Deposits and Advances"     "LIABILITY" "CURRENT_LIABILITY"
seed_account "2200" "Long-term Loans and Borrowings"     "LIABILITY" "NON_CURRENT_LIABILITY"
seed_account "2201" "Lease Liabilities IFRS 16"          "LIABILITY" "NON_CURRENT_LIABILITY"
seed_account "2202" "Deferred Tax Liability"             "LIABILITY" "NON_CURRENT_LIABILITY"

# Equity
seed_account "3000" "Share Capital / Proprietors Capital" "EQUITY" "EQUITY"
seed_account "3001" "Share Premium"                      "EQUITY" "EQUITY"
seed_account "3002" "Retained Earnings"                  "EQUITY" "EQUITY"
seed_account "3003" "General Reserve"                    "EQUITY" "EQUITY"
seed_account "3004" "Statutory Reserve"                  "EQUITY" "EQUITY"
seed_account "3005" "Foreign Currency Translation Reserve" "EQUITY" "EQUITY"

# Revenue
seed_account "4000" "Sales Revenue - Goods"              "INCOME" "OPERATING_REVENUE"
seed_account "4001" "Sales Revenue - Services"           "INCOME" "OPERATING_REVENUE"
seed_account "4002" "Sales Revenue - Digital/SaaS"       "INCOME" "OPERATING_REVENUE"
seed_account "4003" "Export Sales Revenue (Zero-rated)"  "INCOME" "OPERATING_REVENUE"
seed_account "4004" "Commission Income"                  "INCOME" "OPERATING_REVENUE"
seed_account "4005" "Franchise and Licence Income"       "INCOME" "OPERATING_REVENUE"
seed_account "4100" "Interest Income"                    "INCOME" "OTHER_INCOME"
seed_account "4101" "Rental Income"                      "INCOME" "OTHER_INCOME"
seed_account "4102" "Dividend Income"                    "INCOME" "OTHER_INCOME"
seed_account "4103" "Foreign Exchange Gain"              "INCOME" "OTHER_INCOME"
seed_account "4104" "Gain on Disposal of Assets"         "INCOME" "OTHER_INCOME"
seed_account "4105" "Grant and Subsidy Income"           "INCOME" "OTHER_INCOME"

# Cost of Sales
seed_account "5000" "Cost of Goods Sold"                 "EXPENSE" "COST_OF_SALES"
seed_account "5001" "Direct Labour / Production Wages"   "EXPENSE" "COST_OF_SALES"
seed_account "5002" "Direct Materials and Consumables"   "EXPENSE" "COST_OF_SALES"
seed_account "5003" "Import Duties and Customs Levies"   "EXPENSE" "COST_OF_SALES"
seed_account "5004" "Freight and Delivery Inbound"       "EXPENSE" "COST_OF_SALES"

# Operating Expenses
seed_account "6000" "Staff Salaries and Wages"           "EXPENSE" "OPERATING_EXPENSE"
seed_account "6001" "PAYE Tax Expense"                   "EXPENSE" "OPERATING_EXPENSE"
seed_account "6002" "Employer Pension Contribution 10%"  "EXPENSE" "OPERATING_EXPENSE"
seed_account "6003" "NSITF Premium 1%"                   "EXPENSE" "OPERATING_EXPENSE"
seed_account "6004" "ITF Levy 1%"                        "EXPENSE" "OPERATING_EXPENSE"
seed_account "6005" "Group Life Insurance"               "EXPENSE" "OPERATING_EXPENSE"
seed_account "6010" "Rent and Lease"                     "EXPENSE" "OPERATING_EXPENSE"
seed_account "6011" "Electricity and Power"              "EXPENSE" "OPERATING_EXPENSE"
seed_account "6012" "Internet and Telecoms"              "EXPENSE" "OPERATING_EXPENSE"
seed_account "6013" "Repairs and Maintenance"            "EXPENSE" "OPERATING_EXPENSE"
seed_account "6014" "Fuel and Transportation"            "EXPENSE" "OPERATING_EXPENSE"
seed_account "6020" "Professional Fees"                  "EXPENSE" "OPERATING_EXPENSE"
seed_account "6021" "Consulting and Outsourcing Fees"    "EXPENSE" "OPERATING_EXPENSE"
seed_account "6022" "Advertising and Marketing"          "EXPENSE" "OPERATING_EXPENSE"
seed_account "6023" "Bank Charges and Commission"        "EXPENSE" "OPERATING_EXPENSE"
seed_account "6024" "Travel and Accommodation Domestic"  "EXPENSE" "OPERATING_EXPENSE"
seed_account "6025" "Travel and Accommodation International" "EXPENSE" "OPERATING_EXPENSE"
seed_account "6026" "Printing Stationery Office Supplies" "EXPENSE" "OPERATING_EXPENSE"
seed_account "6027" "Security Services"                  "EXPENSE" "OPERATING_EXPENSE"
seed_account "6028" "Cleaning and Janitorial Services"   "EXPENSE" "OPERATING_EXPENSE"
seed_account "6029" "Insurance Premiums"                 "EXPENSE" "OPERATING_EXPENSE"
seed_account "6030" "Depreciation - PPE"                 "EXPENSE" "OPERATING_EXPENSE"
seed_account "6031" "Amortisation - Intangibles"         "EXPENSE" "OPERATING_EXPENSE"
seed_account "6032" "Depreciation - Right-of-Use Assets" "EXPENSE" "OPERATING_EXPENSE"

# Tax Expenses
seed_account "7000" "Companies Income Tax Expense"       "EXPENSE" "TAX_EXPENSE"
seed_account "7001" "Development Levy 4% Expense"        "EXPENSE" "TAX_EXPENSE"
seed_account "7002" "Capital Gains Tax Expense"          "EXPENSE" "TAX_EXPENSE"
seed_account "7003" "Deferred Tax Expense"               "EXPENSE" "TAX_EXPENSE"
seed_account "7004" "Minimum Tax Expense"                "EXPENSE" "TAX_EXPENSE"

# Finance Costs
seed_account "8000" "Interest Expense Borrowings"        "EXPENSE" "FINANCE_COST"
seed_account "8001" "Finance Cost Lease IFRS 16"         "EXPENSE" "FINANCE_COST"
seed_account "8002" "Foreign Exchange Loss"              "EXPENSE" "FINANCE_COST"
seed_account "8003" "Loss on Disposal of Assets"         "EXPENSE" "FINANCE_COST"

echo ""
log "COA seeded: ${CREATED} created, ${SKIPPED} skipped, ${ERRORS} errors"

# ── Step 2: Seed Journals ─────────────────────────────────────────────────────
log "Step 2/4 — Seeding Journals..."
CREATED=0; SKIPPED=0; ERRORS=0

seed_journal() {
  local code="$1" label="$2" nature="$3"

  EXISTING=$(api_get "accountingjournals?sqlfilters=(t.code:=:'${code}')" 2>/dev/null || echo '[]')
  COUNT=$(echo "$EXISTING" | jq 'if type=="array" then length else 0 end' 2>/dev/null || echo 0)
  if [[ "$COUNT" -gt 0 ]]; then skip "${code}"; ((SKIPPED++)) || true; return; fi

  PAYLOAD=$(jq -n \
    --arg code "$code" --arg label "$label" \
    --arg nature "$nature" --arg entity "$DOLI_ENTITY" \
    '{ code: $code, label: $label, nature: ($nature | tonumber),
       entity: ($entity | tonumber), active: 1 }')

  RESULT=$(api_post "accountingjournals" "$PAYLOAD")
  ID=$(echo "$RESULT" | jq -r '. | if type=="number" then . else -1 end' 2>/dev/null || echo -1)

  if [[ "$ID" -gt 0 ]]; then
    ok "${code} — ${label}"
    ((CREATED++)) || true
  else
    warn "Failed: ${code} — $(echo "$RESULT" | jq -r '.error // "unknown"' 2>/dev/null)"
    ((ERRORS++)) || true
  fi
}

seed_journal "JV-SA" "Sales Journal"        "2"
seed_journal "JV-PU" "Purchase Journal"     "3"
seed_journal "JV-BK" "Bank Journal"         "4"
seed_journal "JV-CS" "Cash Journal"         "5"
seed_journal "JV-TX" "Tax Journal"          "1"
seed_journal "JV-PY" "Payroll Journal"      "1"
seed_journal "JV-FA" "Fixed Assets Journal" "1"
seed_journal "JV-MV" "General Journal"      "9"
seed_journal "JV-FX" "Forex Journal"        "1"

log "Journals seeded: ${CREATED} created, ${SKIPPED} skipped, ${ERRORS} errors"

# ── Step 3: Seed VAT rates ────────────────────────────────────────────────────
log "Step 3/4 — Seeding VAT rates..."
CREATED=0; SKIPPED=0; ERRORS=0

seed_vat() {
  local rate="$1" label="$2" recoverable="$3" sell_acct="$4" buy_acct="$5"

  PAYLOAD=$(jq -n \
    --arg rate "$rate" --arg label "$label" \
    --arg rec "$recoverable" --arg sell "$sell_acct" --arg buy "$buy_acct" \
    --arg entity "$DOLI_ENTITY" \
    '{
      taux: ($rate | tonumber),
      label: $label,
      recuperableintegral: ($rec | tonumber),
      accountancy_code_sell: $sell,
      accountancy_code_buy: $buy,
      entity: ($entity | tonumber),
      active: 1
    }')

  RESULT=$(api_post "setup/vat" "$PAYLOAD" 2>/dev/null || echo '{"error":"endpoint_unavailable"}')
  # Fallback: log manual action if API endpoint unavailable
  ERR=$(echo "$RESULT" | jq -r '.error // empty' 2>/dev/null || echo "")
  if [[ -n "$ERR" ]]; then
    warn "VAT API endpoint may not support POST — use SQL seed or Dolibarr admin UI for: ${label}"
  else
    ok "${label} (${rate}%)"
    ((CREATED++)) || true
  fi
}

seed_vat "7.5" "Nigeria VAT 7.5% Standard Rate (NTA 2025)"          "1" "2100" "1101"
seed_vat "0"   "Nigeria VAT 0% Zero-Rated (Exports/Diplomatic)"      "1" "2101" "1101"
seed_vat "0"   "Nigeria VAT Exempt (Food/Medical/Educational)"        "0" ""     ""

log "VAT rates: ${CREATED} created via API (others may need SQL seed or UI)"

# ── Step 4: Final verification ─────────────────────────────────────────────────
log "Step 4/4 — Verifying seeded data..."

COA_RESULT=$(api_get "accountingaccounts?limit=200" 2>/dev/null || echo '[]')
COA_COUNT=$(echo "$COA_RESULT" | jq 'if type=="array" then length else 0 end' 2>/dev/null || echo 0)

JNL_RESULT=$(api_get "accountingjournals" 2>/dev/null || echo '[]')
JNL_COUNT=$(echo "$JNL_RESULT" | jq 'if type=="array" then length else 0 end' 2>/dev/null || echo 0)

echo ""
echo "  Accounting Accounts: ${COA_COUNT}"
echo "  Journals:            ${JNL_COUNT}"
echo ""

# ── Done ──────────────────────────────────────────────────────────────────────
banner "API Seed Complete"
echo -e "  ${GREEN}Instance:${RESET} ${DOLI_URL}"
echo -e "  ${GREEN}Next:${RESET}     Home → Setup → Modules → Activate b3eqng"
echo -e "  ${YELLOW}Note:${RESET}     For VAT/WHT codes, run SQL seed if API endpoints are limited"
echo -e "            mysql -u user -p db < dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_seed.sql\n"
