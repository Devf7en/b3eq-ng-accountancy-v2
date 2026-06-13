#!/usr/bin/env bash
# =============================================================================
# b3Ɛq Nigerian Accountancy — Health Check
# tagged: b3Ɛq Nigerian Accountancy
# File: scripts/health_check.sh
#
# Verifies a successful deployment by checking record counts, module state,
# and remote database connectivity. Supports human and JSON output.
#
# Usage:
#   ./health_check.sh --host ten.f7en.net --db-pass SECRET [options]
#
# Options:
#   --user deploy        SSH user (default: deploy)
#   --port 22            SSH port (default: 22)
#   --db-host 127.0.0.1  Remote MySQL host accessible from the server
#   --db-name dolibarr   Dolibarr database name
#   --db-user dolibarr   Dolibarr database user
#   --db-pass SECRET     Dolibarr database password
#   --entity 1           Dolibarr entity ID
#   --json               Output JSON status object
#   --help               Show this help
# =============================================================================

set -euo pipefail
IFS=$'\n\t'

HOST=""; SSH_USER="deploy"; DB_HOST="127.0.0.1"
DB_NAME="dolibarr"; DB_USER="dolibarr"; DB_PASS=""; ENTITY=1; SSH_PORT=22
OUTPUT_JSON=false

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

command_exists() {
  command -v "$1" >/dev/null 2>&1 || {
    echo "Error: $1 is required but not installed." >&2
    exit 1
  }
}

ok() {
  echo -e "  ${GREEN}✓ $*${RESET}"
}

warn() {
  echo -e "  ${YELLOW}⚠ $*${RESET}"
}

fail() {
  echo -e "  ${RED}✗ $*${RESET}"
}

usage() {
  sed -n '1,32p' "$0"
  exit 0
}

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
    --json)    OUTPUT_JSON=true; shift ;;
    --help)    usage ;;
    *) echo "Unknown argument: $1" >&2; exit 1 ;;
  esac
 done

[[ -z "$HOST" ]] && { echo "Error: --host is required." >&2; exit 1; }
[[ -z "${DB_PASS:-}" ]] && {
  read -s -p "Database password: " DB_PASS
  echo
}
[[ -z "${DB_PASS:-}" ]] && { echo "Error: --db-pass is required." >&2; exit 1; }

command_exists ssh
command_exists mysql
command_exists printf

SSH="ssh -o BatchMode=yes -p ${SSH_PORT} ${SSH_USER}@${HOST}"

echo -e "\n${BOLD}${CYAN}b3Ɛq Nigerian Accountancy — Health Check${RESET}"
echo -e "  Target: ${SSH_USER}@${HOST} | Entity: ${ENTITY}\n"

# Verify remote host is reachable
if ! $SSH 'echo connected' >/dev/null 2>&1; then
  echo -e "${RED}✗ Cannot connect to ${SSH_USER}@${HOST} via SSH.${RESET}" >&2
  exit 1
fi

if ! $SSH 'command -v mysql >/dev/null 2>&1'; then
  echo -e "${RED}✗ Remote host does not have mysql installed or available in PATH.${RESET}" >&2
  exit 1
fi

REMOTE_MY_CNF=$($SSH "mktemp /tmp/b3eqng_mysql.XXXXXX")
$SSH "cat > '${REMOTE_MY_CNF}' <<'EOF'
[client]
user=${DB_USER}
password=${DB_PASS}
host=${DB_HOST}
EOF
chmod 600 '${REMOTE_MY_CNF}'"

DB_CMD="mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -se"

RESULTS=()
fail_count=0
warn_count=0

add_result() {
  local name="$1" status="$2" value="$3" expected="$4" message="$5"
  RESULTS+=("{\"name\":\"${name}\",\"status\":\"${status}\",\"value\":\"${value}\",\"expected\":\"${expected}\",\"message\":\"${message}\"}")
}

run_check() {
  local name="$1" sql="$2" expected="$3" level="$4" message="$5"
  local raw
  raw=$($SSH "${DB_CMD} \"${sql}\"" 2>/dev/null || echo "0")
  raw="${raw##*( )}"
  raw="${raw%%*( )}"
  if [[ "${level}" == "warn" ]]; then
    if [[ "$raw" -ge ${expected} ]]; then
      ok "${message}: ${raw}"
      add_result "$name" "PASS" "$raw" ">=${expected}" "${message}: ${raw}"
    else
      warn "${message}: ${raw} (expected >=${expected})"
      add_result "$name" "WARN" "$raw" ">=${expected}" "${message}: ${raw}"
      warn_count=$((warn_count + 1))
    fi
  else
    if [[ "$raw" -ge ${expected} ]]; then
      ok "${message}: ${raw}"
      add_result "$name" "PASS" "$raw" ">=${expected}" "${message}: ${raw}"
    else
      fail "${message}: ${raw} (expected >=${expected})"
      add_result "$name" "FAIL" "$raw" ">=${expected}" "${message}: ${raw}"
      fail_count=$((fail_count + 1))
    fi
  fi
}

query_exists() {
  local name="$1" sql="$2" message="$3"
  local raw
  raw=$($SSH "${DB_CMD} \"${sql}\"" 2>/dev/null || echo "0")
  raw="${raw##*( )}"
  raw="${raw%%*( )}"
  if [[ "$raw" -ge 1 ]]; then
    ok "$message"
    add_result "$name" "PASS" "$raw" ">=1" "$message"
  else
    fail "$message"
    add_result "$name" "FAIL" "$raw" ">=1" "$message"
    fail_count=$((fail_count + 1))
  fi
}

# Checks
run_check "coa" "SELECT COUNT(*) FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME' AND entity=${ENTITY};" 90 "pass" "Chart of Accounts"
run_check "vat" "SELECT COUNT(*) FROM llx_c_tva WHERE note LIKE 'Nigeria%' AND entity=${ENTITY};" 3 "pass" "VAT codes"
run_check "wht" "SELECT COUNT(*) FROM llx_c_chargesociales WHERE code LIKE 'NG-%' AND entity=${ENTITY};" 21 "pass" "WHT/levy codes"
run_check "journals" "SELECT COUNT(*) FROM llx_accounting_journal WHERE code LIKE 'JV-%' AND entity=${ENTITY};" 9 "pass" "Journals"

MOD=$($SSH "${DB_CMD} \"SELECT COUNT(*) FROM llx_const WHERE name='MAIN_MODULE_B3EQNG' AND value='1' AND entity=${ENTITY};\"" 2>/dev/null || echo "0")
if [[ "$MOD" -ge 1 ]]; then
  ok "Module activation flag: SET"
  add_result "module_flag" "PASS" "$MOD" ">=1" "Module activation flag: SET"
else
  warn "Module activation flag not set — activate in Dolibarr UI"
  add_result "module_flag" "WARN" "$MOD" ">=1" "Module activation flag not set"
  warn_count=$((warn_count + 1))
fi

for ACC in 2100 2113 2120 2131; do
  query_exists "account_${ACC}" "SELECT COUNT(*) FROM llx_accounting_account WHERE account_number='${ACC}' AND entity=${ENTITY};" "Account ${ACC} exists"
done

for JV in JV-TX JV-PY JV-SA; do
  query_exists "journal_${JV}" "SELECT COUNT(*) FROM llx_accounting_journal WHERE code='${JV}' AND entity=${ENTITY};" "Journal ${JV} exists"
done

$SSH "rm -f '${REMOTE_MY_CNF}'" >/dev/null 2>&1 || true

echo ""
if [[ "$OUTPUT_JSON" == true ]]; then
  printf '{"overall":{"pass":%s,"warns":%s,"fails":%s},"results":[%s]}\n' \
    "$((fail_count == 0 ? 1 : 0))" "$warn_count" "$fail_count" "$(IFS=,; echo "${RESULTS[*]}")"
  [[ "$fail_count" -gt 0 ]] && exit 1 || exit 0
fi

if [[ "$fail_count" -eq 0 ]]; then
  if [[ "$warn_count" -gt 0 ]]; then
    echo -e "  ${YELLOW}${BOLD}⚠ Health check passed with warnings — review above.${RESET}"
    exit 0
  fi
  echo -e "  ${GREEN}${BOLD}✓ All checks passed — b3Ɛq NG Accountancy is healthy${RESET}"
  exit 0
fi

echo -e "  ${RED}${BOLD}✗ Some checks failed — review above and re-run seed${RESET}"
echo -e "  Run: mysql -u${DB_USER} -p ${DB_NAME} < dolibarr/htdocs/custom/b3eqng/sql/llx_b3eqng_seed.sql"
exit 1
