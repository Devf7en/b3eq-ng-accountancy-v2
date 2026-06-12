<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — Fixed Assets & Depreciation
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/assets.php
 * ============================================================================
 */

require_once dirname(__DIR__) . '/class/b3eqng_init.php';
if (empty($user->rights->b3eqng->read) && empty($user->admin)) accessforbidden();

$action = GETPOST('action', 'none');

// ── Handle new asset ──────────────────────────────────────────────────────────
if ($action === 'add_asset' && (isset($user->rights->b3eqng->assets) || $user->admin)) {
    $db->begin();
    $ref       = GETPOST('ref', 'alphanohtml');
    $label     = GETPOST('label', 'alphanohtml');
    $cat       = GETPOST('category', 'alphanohtml');
    $acq_date  = GETPOST('acquisition_date', 'none');
    $acq_cost  = (float)GETPOST('acquisition_cost', 'none');
    $residual  = (float)GETPOST('residual_value', 'none');
    $life      = (int)GETPOST('useful_life_years', 'int');
    $method    = GETPOST('depreciation_method', 'aZ09');

    $sql = "INSERT INTO llx_b3eqng_assets
            (entity, ref, label, category, acquisition_date, acquisition_cost,
             residual_value, useful_life_years, depreciation_method,
             net_book_value, account_asset, account_depr, account_expense,
             status, fk_user_creat, date_creation)
            VALUES ("
        . (int)$conf->entity . ", '" . $db->escape($ref) . "', '" . $db->escape($label) . "', "
        . "'" . $db->escape($cat) . "', '" . $db->escape($acq_date) . "', "
        . $acq_cost . ", " . $residual . ", " . $life . ", '" . $db->escape($method) . "', "
        . $acq_cost . ", '1300', '1301', '6030', 'ACTIVE', "
        . (int)$user->id . ", NOW())";

    if ($db->query($sql)) {
        $db->commit();
        setEventMessages('Asset added: ' . dol_escape_htmltag($label), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages('Error: ' . $db->lasterror(), null, 'errors');
    }
}

// ── Load assets ───────────────────────────────────────────────────────────────
$sql = "SELECT * FROM llx_b3eqng_assets WHERE entity=" . (int)$conf->entity . " AND status != 'DISPOSED' ORDER BY acquisition_date DESC";
$res = $db->query($sql);
$assets = [];
if ($res) { while ($o = $db->fetch_object($res)) { $assets[] = $o; } }

// Depreciation calc helper
function calc_depreciation($cost, $residual, $life_years, $method, $acquired) {
    $depreciable = max($cost - $residual, 0);
    if ($method === 'STRAIGHT_LINE') {
        $annual = $life_years > 0 ? round($depreciable / $life_years, 2) : 0;
        $monthly = round($annual / 12, 2);
    } else { // Declining balance — 200% reducing
        $rate = $life_years > 0 ? 2 / $life_years : 0;
        $annual = round($cost * $rate, 2);
        $monthly = round($annual / 12, 2);
    }
    $months_held = max(0, (int)floor((time() - strtotime($acquired)) / (30.44 * 86400)));
    $total_depr  = min($monthly * $months_held, $depreciable);
    $nbv         = round($cost - $total_depr, 2);
    return compact('annual', 'monthly', 'total_depr', 'nbv', 'months_held');
}

$total_cost = array_sum(array_column($assets, 'acquisition_cost'));
$total_nbv  = array_sum(array_column($assets, 'net_book_value'));

llxHeader('', 'Fixed Assets — b3Ɛq NG');
b3eq_inject_css();
?>
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Fixed Assets Register</div>
      <div class="b3eqng-header-sub">IFRS for SMEs · IAS 16 · Straight-Line & Declining Balance · NTA 2025</div>
    </div>
    <div style="margin-left:auto;"><span class="b3eqng-tag">IAS 16</span></div>
  </div>
  <div style="padding:24px 28px;">
    <?php dol_htmloutput_events(); ?>

    <div class="b3eqng-grid-3" style="margin-bottom:24px;">
      <div class="b3eqng-stat"><div class="b3eqng-stat-number" style="color:var(--b3eq-cyan);"><?php echo count($assets); ?></div><div class="b3eqng-stat-label">Active Assets</div></div>
      <div class="b3eqng-stat"><div class="b3eqng-stat-number" style="color:var(--b3eq-accent);"><?php echo B3eqNG::formatNGN($total_cost); ?></div><div class="b3eqng-stat-label">Total Cost</div></div>
      <div class="b3eqng-stat"><div class="b3eqng-stat-number" style="color:var(--b3eq-gold);"><?php echo B3eqNG::formatNGN($total_nbv); ?></div><div class="b3eqng-stat-label">Net Book Value</div></div>
    </div>

    <div class="b3eqng-grid-2" style="gap:24px;">
      <!-- Asset list -->
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin-bottom:14px;">Asset Register</div>
        <?php if (empty($assets)): ?>
          <div class="b3eqng-card" style="text-align:center;padding:32px;color:var(--b3eq-muted);">No assets added yet.</div>
        <?php else: foreach ($assets as $a):
          $d = calc_depreciation($a->acquisition_cost, $a->residual_value, $a->useful_life_years, $a->depreciation_method, $a->acquisition_date);
          $depr_pct = $a->acquisition_cost > 0 ? round($d['total_depr'] / $a->acquisition_cost * 100) : 0;
        ?>
          <div class="b3eqng-card b3eqng-card-accent" style="margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
              <div>
                <div style="font-weight:800;font-size:14px;color:var(--b3eq-text);"><?php echo dol_escape_htmltag($a->label); ?></div>
                <div style="font-size:11px;color:var(--b3eq-muted);"><?php echo dol_escape_htmltag($a->ref); ?> · <?php echo dol_escape_htmltag($a->category); ?> · <?php echo dol_escape_htmltag($a->depreciation_method); ?></div>
              </div>
              <span class="b3eqng-tag-gold"><?php echo $depr_pct; ?>% depreciated</span>
            </div>
            <!-- Progress bar -->
            <div style="background:var(--b3eq-border);border-radius:4px;height:6px;margin-bottom:12px;">
              <div style="background:linear-gradient(90deg,var(--b3eq-accent),var(--b3eq-accent2));height:6px;border-radius:4px;width:<?php echo min($depr_pct,100); ?>%;"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;font-size:12px;">
              <div><div style="color:var(--b3eq-muted);font-size:10px;text-transform:uppercase;margin-bottom:2px;">Cost</div><div style="font-family:monospace;color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($a->acquisition_cost); ?></div></div>
              <div><div style="color:var(--b3eq-muted);font-size:10px;text-transform:uppercase;margin-bottom:2px;">Accum. Depr.</div><div style="font-family:monospace;color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($d['total_depr']); ?></div></div>
              <div><div style="color:var(--b3eq-muted);font-size:10px;text-transform:uppercase;margin-bottom:2px;">NBV</div><div style="font-family:monospace;color:var(--b3eq-gold);font-weight:700;"><?php echo B3eqNG::formatNGN($d['nbv']); ?></div></div>
            </div>
            <div style="margin-top:10px;font-size:11px;color:var(--b3eq-muted);">
              Annual depr: <strong style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($d['annual']); ?></strong> &nbsp;·&nbsp;
              Monthly: <strong style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($d['monthly']); ?></strong> &nbsp;·&nbsp;
              Acquired: <?php echo dol_escape_htmltag($a->acquisition_date); ?> &nbsp;·&nbsp;
              Life: <?php echo (int)$a->useful_life_years; ?> yrs
            </div>
            <!-- Journal entry for this month's depreciation -->
            <div style="margin-top:10px;" class="b3eqng-journal-entry">
              <div style="font-size:10px;color:var(--b3eq-muted);margin-bottom:5px;text-transform:uppercase;letter-spacing:.06em;">Monthly depreciation journal (JV-FA)</div>
              <div style="display:flex;gap:6px;margin-bottom:3px;">
                <span class="b3eqng-journal-dr">Dr</span>
                <span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">6030</span>
                <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Depreciation Expense</span>
                <span style="color:var(--b3eq-text);font-size:11px;"><?php echo B3eqNG::formatNGN($d['monthly']); ?></span>
              </div>
              <div style="display:flex;gap:6px;padding-left:18px;">
                <span class="b3eqng-journal-cr">Cr</span>
                <span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">1301</span>
                <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Accum. Depreciation</span>
                <span style="color:var(--b3eq-red);font-size:11px;"><?php echo B3eqNG::formatNGN($d['monthly']); ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Add asset form -->
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin-bottom:14px;">Add New Asset</div>
        <form method="POST">
          <input type="hidden" name="action" value="add_asset">
          <div class="b3eqng-card">
            <?php foreach ([
              ['ref',              'Asset Reference',    'text',   'e.g. ASSET-001'],
              ['label',            'Asset Description',  'text',   'e.g. HP Laptop ProBook'],
              ['category',         'Category',           'text',   'e.g. IT Equipment / Vehicle / Building'],
              ['acquisition_date', 'Acquisition Date',   'date',   ''],
              ['acquisition_cost', 'Cost (₦)',           'number', '0.00'],
              ['residual_value',   'Residual Value (₦)', 'number', '0.00'],
              ['useful_life_years','Useful Life (years)','number', '5'],
            ] as [$name, $lbl, $type, $ph]): ?>
              <div style="margin-bottom:14px;">
                <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;"><?php echo $lbl; ?></div>
                <input type="<?php echo $type; ?>" name="<?php echo $name; ?>" placeholder="<?php echo $ph; ?>"
                       class="b3eqng-input" <?php echo $type==='number'?'step="0.01" min="0"':''; ?>>
              </div>
            <?php endforeach; ?>
            <div style="margin-bottom:16px;">
              <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Depreciation Method</div>
              <select name="depreciation_method" class="b3eqng-input">
                <option value="STRAIGHT_LINE">Straight-Line (SLM)</option>
                <option value="DECLINING_BALANCE">Declining Balance (200%)</option>
              </select>
            </div>
            <button type="submit" class="b3eqng-btn" style="width:100%;">+ Add Asset</button>
          </div>
        </form>
        <div class="b3eqng-infobox" style="margin-top:14px;">
          <div class="b3eqng-infobox-title">ℹ Depreciation Notes</div>
          <p>FIRS aligns with IFRS for SMEs depreciation rules. Depreciation expense is charged to account <strong style="color:var(--b3eq-text);">6030</strong> (PPE) or 6031 (intangibles). The annual depreciation run should be processed at month-end via JV-FA journal.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
