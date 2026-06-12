#!/usr/bin/env bash
# =============================================================================
# b3Ɛq Nigerian Accountancy — Health Check
# tagged: b3Ɛq Nigerian Accountancy
# File: scripts/health_check.sh
#
# Verifies a successful deployment by checking record counts and module state.
#
# Usage:
#   ./health_check.sh \
#     --host ten.f7en.net \
#     --user deploy \
#     --db-host 127.0.0.1 \
#     --db-name dolibarr \
#     --db-user dolibarr \
#     --db-pass SECRET \
#     [--entity 1]
# =============================================================================

set -euo pipefail

HOST=""; SSH_USER="deploy"; DB_HOST="127.0.0.1"
DB_NAME="dolibarr"; DB_USER="dolibarr"; DB_PASS=""; ENTITY=1; SSH_PORT=22

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'
ok()   { echo -e "  ${GREEN}✓${RESET} $*"; }
fail() { echo -e "  ${RED}✗${RESET} $*"; OVERALL_PASS=false; }
warn() { echo -e "  ${YELLOW}⚠${RESET} $*"; }
OVERALL_PASS=true

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host)    HOST="$2";     shift 2 ;;
    --user)    SSH_USER="$2"; shift 2 ;;
    --db-host) DB_HOST="$2";  shift 2 ;;
    --db-name) DB_NAME="$2";  shift 2 ;;
    --db-user) DB_USER="$2";  shift 2 ;;
    --db-pass) DB_PASS="$2";  shift 2 ;;
    --entity)  ENTITY="$2";   shift 2 ;;
    --port)    SSH_PORT="$2"; shift 2 ;;
    *) echo "Unknown: $1"; exit 1 ;;
  esac
done

[[ -z "$HOST" ]]    && { echo "Usage: --host required"; exit 1; }
[[ -z "$DB_PASS" ]] && { echo "Usage: --db-pass required"; exit 1; }

SSH="ssh -p ${SSH_PORT} ${SSH_USER}@${HOST}"
DB_CMD="mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} -se"

echo -e "\n${BOLD}${CYAN}b3Ɛq Nigerian Accountancy — Health Check${RESET}"
echo -e "  Target: ${SSH_USER}@${HOST} | Entity: ${ENTITY}\n"

query() { $SSH "${DB_CMD} \"$1\"" 2>/dev/null || echo 0; }

# Chart of Accounts
COA=$(query "SELECT COUNT(*) FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME' AND entity=${ENTITY};")
[[ "$COA" -ge 90 ]] && ok "Chart of Accounts: ${COA} accounts (≥90)" || fail "Chart of Accounts: ${COA} (expected ≥90)"

# VAT codes
VAT=$(query "SELECT COUNT(*) FROM llx_c_tva WHERE note LIKE 'Nigeria%' AND entity=${ENTITY};")
[[ "$VAT" -ge 3 ]] && ok "VAT codes: ${VAT} (≥3)" || fail "VAT codes: ${VAT} (expected 3)"

# WHT codes
WHT=$(query "SELECT COUNT(*) FROM llx_c_chargesociales WHERE code LIKE 'NG-%' AND entity=${ENTITY};")
[[ "$WHT" -ge 21 ]] && ok "WHT/levy codes: ${WHT} (≥21)" || fail "WHT codes: ${WHT} (expected 21)"

# Journals
JNL=$(query "SELECT COUNT(*) FROM llx_accounting_journal WHERE code LIKE 'JV-%' AND entity=${ENTITY};")
[[ "$JNL" -ge 9 ]] && ok "Journals: ${JNL} (≥9)" || fail "Journals: ${JNL} (expected 9)"

# Module flag
MOD=$(query "SELECT COUNT(*) FROM llx_const WHERE name='MAIN_MODULE_B3EQNG' AND value='1' AND entity=${ENTITY};")
[[ "$MOD" -ge 1 ]] && ok "Module activation flag: SET" || warn "Module flag not set — activate in Dolibarr UI"

# Key accounts spot-check
for ACC in 2100 2113 2120 2131; do
  EXISTS=$(query "SELECT COUNT(*) FROM llx_accounting_account WHERE account_number='${ACC}' AND entity=${ENTITY};")
  [[ "$EXISTS" -ge 1 ]] && ok "Account ${ACC} exists" || fail "Account ${ACC} MISSING"
done

# Key journals spot-check
for JV in JV-TX JV-PY JV-SA; do
  EXISTS=$(query "SELECT COUNT(*) FROM llx_accounting_journal WHERE code='${JV}' AND entity=${ENTITY};")
  [[ "$EXISTS" -ge 1 ]] && ok "Journal ${JV} exists" || fail "Journal ${JV} MISSING"
done

echo ""
if [[ "$OVERALL_PASS" == "true" ]]; then
  echo -e "  ${GREEN}${BOLD}✓ All checks passed — b3Ɛq NG Accountancy is healthy${RESET}"
else
  echo -e "  ${RED}${BOLD}✗ Some checks failed — review above and re-run seed${RESET}"
  echo -e "  Run: mysql -u${DB_USER} -p ${DB_NAME} < dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_seed.sql"
fi
echo ""
