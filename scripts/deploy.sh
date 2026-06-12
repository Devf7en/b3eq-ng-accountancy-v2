#!/usr/bin/env bash
# =============================================================================
# b3Ɛq Nigerian Accountancy — Multi-Instance Deploy Script (SSH)
# tagged: b3Ɛq Nigerian Accountancy
# File:   scripts/deploy.sh
#
# Deploys the b3eqng module to any remote Dolibarr / b3Ɛq instance over SSH.
#
# Usage:
#   ./deploy.sh \
#     --host ten.f7en.net \
#     --user deploy \
#     --docroot /var/www/dolibarr/htdocs \
#     --db-host 127.0.0.1 \
#     --db-name dolibarr \
#     --db-user dolibarr \
#     --db-pass SECRET \
#     [--entity 1] \
#     [--dry-run]
#
# Prerequisites on local machine:
#   - SSH key-based auth to target server
#   - rsync installed locally
#
# Prerequisites on remote:
#   - Dolibarr 17+ installed at --docroot
#   - MySQL / MariaDB accessible at --db-host
# =============================================================================

set -euo pipefail

# ── Defaults ─────────────────────────────────────────────────────────────────
HOST=""
SSH_USER="deploy"
DOCROOT="/var/www/dolibarr/htdocs"
DB_HOST="127.0.0.1"
DB_NAME="dolibarr"
DB_USER="dolibarr"
DB_PASS=""
ENTITY=1
DRY_RUN=false
SSH_PORT=22
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MODULE_SRC="$SCRIPT_DIR/../dolibarr/htdocs/custom/b3eqng"

# ── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

log()    { echo -e "${CYAN}[b3Ɛq]${RESET} $*"; }
ok()     { echo -e "${GREEN}[OK]${RESET} $*"; }
warn()   { echo -e "${YELLOW}[WARN]${RESET} $*"; }
fail()   { echo -e "${RED}[FAIL]${RESET} $*"; exit 1; }
banner() { echo -e "\n${BOLD}${CYAN}═══════════════════════════════════════${RESET}"; echo -e "${BOLD}  $*${RESET}"; echo -e "${BOLD}${CYAN}═══════════════════════════════════════${RESET}\n"; }
usage() {
  cat <<EOF
Usage: ${0##*/} --host HOST --db-pass PASSWORD [options]

Options:
  --host HOST         Remote host or IP address
  --user USER         SSH user (default: deploy)
  --port PORT         SSH port (default: 22)
  --docroot PATH      Dolibarr document root (default: /var/www/dolibarr/htdocs)
  --db-host HOST      MySQL host on remote (default: 127.0.0.1)
  --db-name NAME      MySQL database name (default: dolibarr)
  --db-user USER      MySQL user (default: dolibarr)
  --db-pass PASS      MySQL password
  --entity ID         Dolibarr entity ID (default: 1)
  --dry-run           Show actions without making changes
  --help              Print this help and exit
EOF
  exit 0
}
command_exists() {
  command -v "$1" >/dev/null 2>&1 || fail "$1 is required but not installed"
}

# ── Argument parsing ──────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
  case "$1" in
    --host)       HOST="$2";       shift 2 ;;
    --user)       SSH_USER="$2";   shift 2 ;;
    --docroot)    DOCROOT="$2";    shift 2 ;;
    --db-host)    DB_HOST="$2";    shift 2 ;;
    --db-name)    DB_NAME="$2";    shift 2 ;;
    --db-user)    DB_USER="$2";    shift 2 ;;
    --db-pass)    DB_PASS="$2";    shift 2 ;;
    --entity)     ENTITY="$2";     shift 2 ;;
    --port)       SSH_PORT="$2";   shift 2 ;;
    --dry-run)    DRY_RUN=true;    shift ;;
    --help)       usage ;;
    *) fail "Unknown argument: $1" ;;
  esac
 done

[[ -z "$HOST" ]] && fail "--host is required (e.g. ten.f7en.net)"
if [[ -z "${DB_PASS:-}" ]]; then
  read -s -p "Database password: " DB_PASS
  echo
fi
[[ -z "${DB_PASS:-}" ]] && fail "--db-pass is required"
[[ -d "$MODULE_SRC" ]] || fail "Module source not found at: $MODULE_SRC"

command_exists ssh
command_exists rsync
command_exists mysql
command_exists sed

SSH_CMD="ssh -o BatchMode=yes -p ${SSH_PORT} ${SSH_USER}@${HOST}"
RSYNC_CMD="rsync -avz --delete --chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r -e 'ssh -o BatchMode=yes -p ${SSH_PORT}'"
log "Step 3/5 — Syncing module files..."
REMOTE_MODULE="${DOCROOT}/custom/b3eqng"
REMOTE_BACKUP="${DOCROOT}/custom/b3eqng.bak.$(date +%Y%m%d%H%M%S)"

if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would rsync: $MODULE_SRC → ${SSH_USER}@${HOST}:${REMOTE_MODULE}"
  warn "DRY RUN — would create remote backup if ${REMOTE_MODULE} exists"
else
  $SSH_CMD "mkdir -p '${DOCROOT}/custom'"

  if $SSH_CMD "test -d '${REMOTE_MODULE}'" &>/dev/null; then
    log "Backing up existing remote module to ${REMOTE_BACKUP}"
    $SSH_CMD "cp -a '${REMOTE_MODULE}' '${REMOTE_BACKUP}'"
    ok "Remote backup created"
  fi

  $RSYNC_CMD \
    "${MODULE_SRC}/" \
    "${SSH_USER}@${HOST}:${REMOTE_MODULE}/"

  $SSH_CMD "find '${REMOTE_MODULE}' -type f -exec chmod 644 {} \; && \
            find '${REMOTE_MODULE}' -type d -exec chmod 755 {} \;"

  ok "Module files synced"
fi

# ── Step 4: Run SQL seed ──────────────────────────────────────────────────────
log "Step 4/5 — Seeding Nigerian accounting data..."
if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would run seed SQL on ${DB_NAME}"
else
  SEED_SQL="${DOCROOT}/custom/b3eqng/sql/llx_b3eqng_seed.sql"
  if ! $SSH_CMD "test -f '${SEED_SQL}'" >/dev/null 2>&1; then
    fail "Seed SQL not found at ${SEED_SQL} on remote host"
  fi

  REMOTE_MY_CNF=$($SSH_CMD "mktemp /tmp/b3eqng_mysql.XXXXXX")
  $SSH_CMD "cat > '${REMOTE_MY_CNF}' <<'EOF'
[client]
user=${DB_USER}
password=${DB_PASS}
host=${DB_HOST}
EOF
chmod 600 '${REMOTE_MY_CNF}'"

  if [[ "$ENTITY" != "1" ]]; then
    $SSH_CMD "sed 's/entity=1/entity=${ENTITY}/g; s/, 1,/, ${ENTITY},/g' '${SEED_SQL}' | mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME}"
  else
    $SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} < '${SEED_SQL}'"
  fi

  $SSH_CMD "rm -f '${REMOTE_MY_CNF}'"
  ok "SQL seed executed"
fi

# ── Step 5: Health check ──────────────────────────────────────────────────────
log "Step 5/5 — Running health check..."
if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would verify seeded record counts"
else
  REMOTE_MY_CNF=$($SSH_CMD "mktemp /tmp/b3eqng_mysql.XXXXXX")
  $SSH_CMD "cat > '${REMOTE_MY_CNF}' <<'EOF'
[client]
user=${DB_USER}
password=${DB_PASS}
host=${DB_HOST}
EOF
chmod 600 '${REMOTE_MY_CNF}'"

  COA_COUNT=$($SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -se \"SELECT COUNT(*) FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  VAT_COUNT=$($SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -se \"SELECT COUNT(*) FROM llx_c_tva WHERE note LIKE 'Nigeria%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  WHT_COUNT=$($SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -se \"SELECT COUNT(*) FROM llx_c_chargesociales WHERE code LIKE 'NG-%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  JNL_COUNT=$($SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -se \"SELECT COUNT(*) FROM llx_accounting_journal WHERE code LIKE 'JV-%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)

  echo ""
  echo "  Chart of Accounts:  ${COA_COUNT} accounts  (expected ≥90)"
  echo "  VAT codes:          ${VAT_COUNT} codes      (expected 3)"
  echo "  WHT codes:          ${WHT_COUNT} codes      (expected 21)"
  echo "  Journals:           ${JNL_COUNT} journals   (expected 9)"
  echo ""

  PASS=true
  [[ "$COA_COUNT" -lt 90 ]] && { warn "COA count low: $COA_COUNT"; PASS=false; }
  [[ "$VAT_COUNT" -lt 3 ]]  && { warn "VAT count low: $VAT_COUNT"; PASS=false; }
  [[ "$WHT_COUNT" -lt 21 ]] && { warn "WHT count low: $WHT_COUNT"; PASS=false; }
  [[ "$JNL_COUNT" -lt 9 ]]  && { warn "Journal count low: $JNL_COUNT"; PASS=false; }

  if [[ "$PASS" == "true" ]]; then
    ok "Health check PASSED"
    $SSH_CMD "mysql --defaults-extra-file='${REMOTE_MY_CNF}' ${DB_NAME} -e \"INSERT IGNORE INTO llx_const (name, value, type, entity) VALUES ('MAIN_MODULE_B3EQNG', '1', 'chaine', ${ENTITY});\"" 2>/dev/null
    ok "Module activation flag set in llx_const"
  else
    warn "Health check failed — deployment halted"
    $SSH_CMD "rm -f '${REMOTE_MY_CNF}'"
    fail "One or more health checks did not pass"
  fi

  $SSH_CMD "rm -f '${REMOTE_MY_CNF}'"
fi

# ── Done ─────────────────────────────────────────────────────────────────────
banner "Deploy Complete — ${HOST}"
echo -e "  ${GREEN}Next:${RESET} Log into b3Ɛq admin at https://${HOST}"
echo -e "  Navigate: Home → Setup → Modules → search 'b3eqng' → Activate"
echo -e "  Then: Financial → Nigerian Accountancy\n"
