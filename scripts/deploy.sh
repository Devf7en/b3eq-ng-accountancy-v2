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
    *) fail "Unknown argument: $1" ;;
  esac
done

[[ -z "$HOST" ]]    && fail "--host is required (e.g. ten.f7en.net)"
[[ -z "$DB_PASS" ]] && fail "--db-pass is required"
[[ -d "$MODULE_SRC" ]] || fail "Module source not found at: $MODULE_SRC"

SSH_CMD="ssh -p $SSH_PORT ${SSH_USER}@${HOST}"
RSYNC_CMD="rsync -avz --delete -e 'ssh -p $SSH_PORT'"

# ── Start ─────────────────────────────────────────────────────────────────────
banner "b3Ɛq Nigerian Accountancy — Deploy to $HOST"
log "Target:   ${SSH_USER}@${HOST}:${DOCROOT}/custom/b3eqng"
log "Database: ${DB_USER}@${DB_HOST}/${DB_NAME} (entity ${ENTITY})"
log "Dry run:  ${DRY_RUN}"

# ── Step 1: Test SSH connection ───────────────────────────────────────────────
log "Step 1/5 — Testing SSH connection..."
if $SSH_CMD "echo 'SSH OK'" &>/dev/null; then
  ok "SSH connection established"
else
  fail "Cannot connect to ${SSH_USER}@${HOST}. Check SSH key auth."
fi

# ── Step 2: Verify Dolibarr installation ─────────────────────────────────────
log "Step 2/5 — Verifying Dolibarr installation..."
DOLI_CHECK=$($SSH_CMD "test -f ${DOCROOT}/main.inc.php && echo FOUND || echo MISSING")
if [[ "$DOLI_CHECK" == "FOUND" ]]; then
  ok "Dolibarr found at ${DOCROOT}"
else
  fail "Dolibarr main.inc.php not found at ${DOCROOT}. Check --docroot."
fi

# ── Step 3: Rsync module files ────────────────────────────────────────────────
log "Step 3/5 — Syncing module files..."
if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would rsync: $MODULE_SRC → ${SSH_USER}@${HOST}:${DOCROOT}/custom/b3eqng"
else
  # Create custom dir if it doesn't exist
  $SSH_CMD "mkdir -p ${DOCROOT}/custom"

  rsync -avz --delete \
    -e "ssh -p ${SSH_PORT}" \
    "${MODULE_SRC}/" \
    "${SSH_USER}@${HOST}:${DOCROOT}/custom/b3eqng/"

  # Set correct permissions
  $SSH_CMD "find ${DOCROOT}/custom/b3eqng -type f -exec chmod 644 {} \; && \
            find ${DOCROOT}/custom/b3eqng -type d -exec chmod 755 {} \;"

  ok "Module files synced"
fi

# ── Step 4: Run SQL seed ──────────────────────────────────────────────────────
log "Step 4/5 — Seeding Nigerian accounting data..."
if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would run: llx_b3eqng_seed.sql on ${DB_NAME}"
else
  # Substitute entity if not 1
  SEED_SQL="${DOCROOT}/custom/b3eqng/sql/llx_b3eqng_seed.sql"

  if [[ "$ENTITY" != "1" ]]; then
    $SSH_CMD "sed 's/entity=1/entity=${ENTITY}/g; s/, 1,/, ${ENTITY},/g' \
              ${SEED_SQL} | mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME}"
  else
    $SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} < ${SEED_SQL}"
  fi

  ok "SQL seed executed"
fi

# ── Step 5: Health check ──────────────────────────────────────────────────────
log "Step 5/5 — Running health check..."
if [[ "$DRY_RUN" == "true" ]]; then
  warn "DRY RUN — would verify seeded record counts"
else
  COA_COUNT=$($SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -se \"SELECT COUNT(*) FROM llx_accounting_account WHERE fk_pcg_version='NG-IFRS-SME' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  VAT_COUNT=$($SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -se \"SELECT COUNT(*) FROM llx_c_tva WHERE note LIKE 'Nigeria%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  WHT_COUNT=$($SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -se \"SELECT COUNT(*) FROM llx_c_chargesociales WHERE code LIKE 'NG-%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)
  JNL_COUNT=$($SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -se \"SELECT COUNT(*) FROM llx_accounting_journal WHERE code LIKE 'JV-%' AND entity=${ENTITY};\"" 2>/dev/null || echo 0)

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
  else
    warn "Health check had warnings — review above counts"
  fi

  # ── Activate module via Dolibarr constant store ───────────────────────────
  $SSH_CMD "mysql -h${DB_HOST} -u${DB_USER} -p${DB_PASS} ${DB_NAME} \
    -e \"INSERT IGNORE INTO llx_const (name, value, type, entity) \
        VALUES ('MAIN_MODULE_B3EQNG', '1', 'chaine', ${ENTITY});\"" 2>/dev/null
  ok "Module activation flag set in llx_const"
fi

# ── Done ─────────────────────────────────────────────────────────────────────
banner "Deploy Complete — ${HOST}"
echo -e "  ${GREEN}Next:${RESET} Log into b3Ɛq admin at https://${HOST}"
echo -e "  Navigate: Home → Setup → Modules → search 'b3eqng' → Activate"
echo -e "  Then: Financial → Nigerian Accountancy\n"
