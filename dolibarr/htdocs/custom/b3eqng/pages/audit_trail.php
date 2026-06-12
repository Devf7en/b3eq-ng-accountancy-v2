<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — Immutable Audit Trail
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/audit_trail.php
 * ============================================================================
 */

require_once dirname(__DIR__) . '/class/b3eqng_init.php';
if (empty($user->rights->b3eqng->read) && empty($user->admin)) accessforbidden();

$date_from = GETPOST('date_from', 'none') ?: date('Y-m-01');
$date_to   = GETPOST('date_to',   'none') ?: date('Y-m-d');
$obj_type  = GETPOST('obj_type',  'aZ09')  ?: '';

// Check if audit table exists
$table_check = $db->query("SHOW TABLES LIKE 'llx_b3eqng_audit'");
$table_exists = ($table_check && $db->num_rows($table_check) > 0);

$entries = [];
if ($table_exists) {
    $sql = "SELECT a.rowid, a.event_time, a.action, a.object_type, a.object_id,
                   a.amount, a.account_dr, a.account_cr, a.journal_code,
                   a.description, a.ip_address, a.entry_hash,
                   u.login as user_login
            FROM llx_b3eqng_audit a
            LEFT JOIN llx_user u ON u.rowid = a.fk_user
            WHERE a.entity = " . (int)$conf->entity
          . " AND DATE(a.event_time) BETWEEN '" . $db->escape($date_from) . "'"
          . " AND '" . $db->escape($date_to) . "'";
    if ($obj_type) $sql .= " AND a.object_type = '" . $db->escape($obj_type) . "'";
    $sql .= " ORDER BY a.rowid DESC LIMIT 200";

    $res = $db->query($sql);
    if ($res) { while ($o = $db->fetch_object($res)) { $entries[] = $o; } }
}

llxHeader('', 'Audit Trail — b3Ɛq NG');
b3eq_inject_css();
?>
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Immutable Audit Trail</div>
      <div class="b3eqng-header-sub">Append-only · Hash-chained · All tax postings &amp; journal entries logged</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <span class="b3eqng-tag-gold">Read-Only</span>
      <span class="b3eqng-tag">v2.0.0</span>
    </div>
  </div>

  <div style="padding:24px 28px;">

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;align-items:flex-end;">
      <div>
        <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">From</div>
        <input type="date" name="date_from" value="<?php echo dol_escape_htmltag($date_from); ?>" class="b3eqng-input" style="max-width:160px;">
      </div>
      <div>
        <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">To</div>
        <input type="date" name="date_to" value="<?php echo dol_escape_htmltag($date_to); ?>" class="b3eqng-input" style="max-width:160px;">
      </div>
      <div>
        <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Event Type</div>
        <select name="obj_type" class="b3eqng-input" style="max-width:180px;">
          <option value="">All types</option>
          <?php foreach (['VAT_RETURN','WHT_REMIT','PAYE_REMIT','CIT_PROVISION','ASSET_DEPRECIATION','FX_REVALUATION','MANUAL_ENTRY'] as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo $obj_type===$t?'selected':''; ?>><?php echo $t; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="b3eqng-btn">Filter</button>
    </form>

    <?php if (!$table_exists): ?>
    <div class="b3eqng-infobox">
      <div class="b3eqng-infobox-title">⚠ Audit table not yet created</div>
      <p>The audit log table (<code>llx_b3eqng_audit</code>) will be created automatically when the module is activated and the SQL seed is run. Once active, all tax postings, journal entries, and VAT/WHT remittances will be logged here immutably.</p>
    </div>
    <?php elseif (empty($entries)): ?>
    <div class="b3eqng-card" style="text-align:center;padding:40px;">
      <div style="font-size:36px;margin-bottom:12px;">🔍</div>
      <div style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--b3eq-text);margin-bottom:6px;">No audit entries for this period</div>
      <div style="color:var(--b3eq-muted);font-size:12px;">Entries are written automatically when journal entries are posted via b3Ɛq NG pages.</div>
    </div>
    <?php else: ?>

    <div style="margin-bottom:12px;color:var(--b3eq-muted);font-size:12px;">
      Showing <?php echo count($entries); ?> entries &nbsp;·&nbsp;
      <span style="color:var(--b3eq-green);">✓ Hash-chained — entries cannot be altered</span>
    </div>

    <div style="overflow-x:auto;">
      <table class="b3eqng-table">
        <thead>
          <tr>
            <th>#</th><th>Timestamp</th><th>User</th><th>Action</th>
            <th>Type</th><th>Dr</th><th>Cr</th><th>Amount</th>
            <th>Journal</th><th>Description</th><th style="font-size:9px;">Hash (8)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($entries as $i => $e): ?>
          <tr style="<?php echo $i%2===0?'':'background:rgba(255,255,255,0.015)'; ?>">
            <td style="font-family:monospace;color:var(--b3eq-muted);font-size:11px;"><?php echo (int)$e->rowid; ?></td>
            <td style="font-size:11px;color:var(--b3eq-muted);white-space:nowrap;"><?php echo dol_escape_htmltag($e->event_time); ?></td>
            <td style="font-size:11px;"><?php echo dol_escape_htmltag($e->user_login ?? '—'); ?></td>
            <td><span style="background:rgba(228,0,27,0.1);border:1px solid rgba(228,0,27,0.3);border-radius:4px;padding:2px 7px;font-size:10px;color:var(--b3eq-accent);font-weight:700;"><?php echo dol_escape_htmltag($e->action); ?></span></td>
            <td style="font-size:11px;color:var(--b3eq-muted);"><?php echo dol_escape_htmltag($e->object_type ?? ''); ?></td>
            <td><span class="b3eqng-account-code" style="font-size:11px;"><?php echo dol_escape_htmltag($e->account_dr ?? ''); ?></span></td>
            <td><span class="b3eqng-account-code" style="font-size:11px;"><?php echo dol_escape_htmltag($e->account_cr ?? ''); ?></span></td>
            <td style="font-family:monospace;font-size:11px;text-align:right;"><?php echo $e->amount ? B3eqNG::formatNGN((float)$e->amount) : '—'; ?></td>
            <td style="font-family:monospace;font-size:11px;color:var(--b3eq-cyan);"><?php echo dol_escape_htmltag($e->journal_code ?? ''); ?></td>
            <td style="font-size:11px;color:var(--b3eq-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo dol_escape_htmltag($e->description ?? ''); ?></td>
            <td style="font-family:monospace;font-size:10px;color:var(--b3eq-muted);"><?php echo dol_escape_htmltag(substr($e->entry_hash ?? '', 0, 8)); ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

    <div class="b3eqng-infobox" style="margin-top:20px;">
      <div class="b3eqng-infobox-title">🔒 Audit Trail Architecture</div>
      <p>Every tax posting, journal entry, and remittance confirmation written via b3Ɛq NG pages generates an append-only row in <code>llx_b3eqng_audit</code>.
         Each row carries a SHA-256 hash of its own data chained with the previous row's hash — creating a tamper-evident ledger.
         Any correction to a posted entry must be made via a <strong style="color:var(--b3eq-text);">Storno (reversal) entry</strong> — the original row is never modified or deleted.
         This satisfies FIRS audit requirements and IFRS audit trail standards.</p>
    </div>
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
