<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Admin Setup Page
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/admin/setup.php
 * ============================================================================
 */

// Use the shared bootstrap with a robust fallback
$res = @include dirname(__DIR__) . '/class/b3eqng_init.php';
if (!$res) { $res = @include __DIR__ . '/../class/b3eqng_init.php'; }
if (!$res) { die('Include of b3eqng_init.php failed'); }

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/b3eqng/class/b3eqng.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/b3eqng/scripts/install_data.php';

$langs->loadLangs(['b3eqng@b3eqng', 'admin']);

if (!$user->admin) accessforbidden();

$company_sizes = ['SMALL', 'MEDIUM', 'LARGE'];
$states = ['ABIA','ADAMAWA','AKWA IBOM','ANAMBRA','BAUCHI','BAYELSA','BENUE',
           'BORNO','CROSS RIVER','DELTA','EBONYI','EDO','EKITI','ENUGU','FCT',
           'GOMBE','IMO','JIGAWA','KADUNA','KANO','KATSINA','KEBBI','KOGI',
           'KWARA','LAGOS','NASARAWA','NIGER','OGUN','ONDO','OSUN','OYO',
           'PLATEAU','RIVERS','SOKOTO','TARABA','YOBE','ZAMFARA'];

$action = GETPOST('action', 'none');

// ── Handle form save ──────────────────────────────────────────────────────────
if ($action === 'save_settings') {
    $size       = GETPOST('company_size', 'aZ09');
    $tin        = trim(GETPOST('tin', 'none'));
    $vat_no     = trim(GETPOST('vat_number', 'none'));
    $state      = GETPOST('reg_state', 'none');
    $vat_reg    = GETPOST('vat_registered', 'int');
    $fy_start   = trim(GETPOST('fiscal_year_start', 'none'));
    $fy_end     = trim(GETPOST('fiscal_year_end', 'none'));

    if (!in_array($size, $company_sizes, true)) {
        $size = 'LARGE';
    }
    if (!in_array($state, $states, true)) {
        $state = 'LAGOS';
    }
    if (!preg_match('/^[0-1][0-9]-[0-3][0-9]$/', $fy_start)) {
        $fy_start = '01-01';
    }
    if (!preg_match('/^[0-1][0-9]-[0-3][0-9]$/', $fy_end)) {
        $fy_end = '12-31';
    }

    $vat_reg = $vat_reg ? '1' : '0';

    dolibarr_set_const($db, 'B3EQNG_COMPANY_SIZE',    $size,    'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_TIN',             $tin,     'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_VAT_NUMBER',      $vat_no,  'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_STATE',           $state,   'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_VAT_REGISTERED',  $vat_reg, 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_FISCAL_YEAR_START',$fy_start,'chaine',0, '', $conf->entity);
    dolibarr_set_const($db, 'B3EQNG_FISCAL_YEAR_END',  $fy_end,  'chaine',0, '', $conf->entity);

    setEventMessages('Settings saved.', null, 'mesgs');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Re-seed action ────────────────────────────────────────────────────────────
if ($action === 'reseed') {
    if (!$user->admin) accessforbidden();

    $seed_file = dol_buildpath('/b3eqng/sql/llx_b3eqng_seed.sql', 0);

    if (file_exists($seed_file)) {
        $result = b3eqng_install_data($db, $conf->entity);
        setEventMessages($result === 0 ? 'Nigerian accounting data re-seeded successfully.' : 'Errors occurred during re-seed. Check logs.', null, $result === 0 ? 'mesgs' : 'errors');
    } else {
        setEventMessages('Seed file not found.', null, 'errors');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Load current values ───────────────────────────────────────────────────────
$size     = b3eq_conf('B3EQNG_COMPANY_SIZE',     'LARGE');
$tin      = b3eq_conf('B3EQNG_TIN',              '');
$vat_no   = b3eq_conf('B3EQNG_VAT_NUMBER',       '');
$state    = b3eq_conf('B3EQNG_STATE',            'LAGOS');
$vat_reg  = b3eq_conf('B3EQNG_VAT_REGISTERED',   '1');
$fy_start = b3eq_conf('B3EQNG_FISCAL_YEAR_START','01-01');
$fy_end   = b3eq_conf('B3EQNG_FISCAL_YEAR_END',  '12-31');
$version  = b3eq_conf('B3EQNG_VERSION',          '1.0.0');

// ── Page header ───────────────────────────────────────────────────────────────
llxHeader('', 'b3Ɛq NG Accountancy — Settings', '', '', '', '', [], ['b3eqng/css/b3eqng.css']);

$b3 = new B3eqNG($db);
$coa_count = count($b3->getChartOfAccounts($conf->entity));

?>
<div class="b3eqng-page">

  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title" style="font-family:'Syne',sans-serif; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Nigerian Accountancy — Settings</div>
      <div class="b3eqng-header-sub">NTA 2025 · IFRS for SMEs · Module v<?php echo dol_escape_htmltag($version); ?></div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
      <span class="b3eqng-tag">Admin</span>
      <span class="b3eqng-tag-gold">v<?php echo dol_escape_htmltag($version); ?></span>
    </div>
  </div>

  <div style="padding:24px 28px;max-width:900px;">

    <?php dol_htmloutput_events(); ?>

    <!-- Stats row -->
    <div class="b3eqng-grid-3" style="margin-bottom:24px;">
      <div class="b3eqng-stat">
        <div class="b3eqng-stat-number" style="color:var(--b3eq-cyan, #22d3ee);"><?php echo (int)$coa_count; ?></div>
        <div class="b3eqng-stat-label">Accounts Seeded</div>
      </div>
      <div class="b3eqng-stat">
        <div class="b3eqng-stat-number" style="color:var(--b3eq-accent, #e4001b);"><?php echo dol_escape_htmltag($size); ?></div>
        <div class="b3eqng-stat-label">Company Size</div>
      </div>
      <div class="b3eqng-stat">
        <div class="b3eqng-stat-number" style="color:var(--b3eq-gold, #c8a84b);"><?php echo $vat_reg ? 'YES' : 'NO'; ?></div>
        <div class="b3eqng-stat-label">VAT Registered</div>
      </div>
    </div>

    <!-- Settings form -->
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
      <input type="hidden" name="action" value="save_settings">
      <?php echo dol_get_fiche_begin(); ?>

      <div class="b3eqng-card b3eqng-card-accent" style="margin-bottom:20px;">
        <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--b3eq-text);margin:0 0 18px;">
          Company Classification
        </h3>

        <div class="b3eqng-grid-2">
          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              Company Size (determines CIT rate)
            </label>
            <select name="company_size" class="b3eqng-input">
              <option value="SMALL"  <?php echo $size==='SMALL'  ? 'selected':'' ?>>Small  — Turnover ≤₦100m (0% CIT)</option>
              <option value="MEDIUM" <?php echo $size==='MEDIUM' ? 'selected':'' ?>>Medium — ₦100m–₦1bn (20% CIT)</option>
              <option value="LARGE"  <?php echo $size==='LARGE'  ? 'selected':'' ?>>Large  — Turnover >₦1bn (30% CIT)</option>
            </select>
            <div style="font-size:11px;color:var(--b3eq-muted);margin-top:5px;">NTA 2025 §56. Small companies are also exempt from Development Levy and CGT.</div>
          </div>

          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              State of Registration
            </label>
            <select name="reg_state" class="b3eqng-input">
              <?php
              $states = ['ABIA','ADAMAWA','AKWA IBOM','ANAMBRA','BAUCHI','BAYELSA','BENUE',
                         'BORNO','CROSS RIVER','DELTA','EBONYI','EDO','EKITI','ENUGU','FCT',
                         'GOMBE','IMO','JIGAWA','KADUNA','KANO','KATSINA','KEBBI','KOGI',
                         'KWARA','LAGOS','NASARAWA','NIGER','OGUN','ONDO','OSUN','OYO',
                         'PLATEAU','RIVERS','SOKOTO','TARABA','YOBE','ZAMFARA'];
              foreach ($states as $s) {
                  echo '<option value="'.dol_escape_htmltag($s).'"'.($state===$s?' selected':'').'>'.dol_escape_htmltag($s).'</option>';
              }
              ?>
            </select>
            <div style="font-size:11px;color:var(--b3eq-muted);margin-top:5px;">Routes PAYE remittances to the correct State Internal Revenue Service (SIRS).</div>
          </div>
        </div>
      </div>

      <div class="b3eqng-card b3eqng-card-gold" style="margin-bottom:20px;">
        <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--b3eq-text);margin:0 0 18px;">
          FIRS / NRS Registration
        </h3>
        <div class="b3eqng-grid-2">
          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              FIRS TIN (10-digit)
            </label>
            <input type="text" name="tin" value="<?php echo dol_escape_htmltag($tin); ?>"
                   placeholder="e.g. 1234567890" maxlength="20" class="b3eqng-input">
            <div style="font-size:11px;color:var(--b3eq-muted);margin-top:5px;">Required for WHT exemption logic. Vendor payments without TIN → double WHT rate.</div>
          </div>
          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              VAT Registration Number
            </label>
            <input type="text" name="vat_number" value="<?php echo dol_escape_htmltag($vat_no); ?>"
                   placeholder="e.g. VAT/XXXXXX" maxlength="30" class="b3eqng-input">
          </div>
        </div>
        <div style="margin-top:16px;">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="checkbox" name="vat_registered" value="1" <?php echo $vat_reg ? 'checked' : ''; ?>
                   style="width:16px;height:16px;">
            <span style="font-size:13px;color:var(--b3eq-text);">Company is VAT Registered with NRS/FIRS (turnover ≥₦25m)</span>
          </label>
        </div>
      </div>

      <div class="b3eqng-card b3eqng-card-accent" style="margin-bottom:24px; border-left: 4px solid var(--b3eq-accent, #e4001b);">
        <h3 style="font-family:'Syne',sans-serif;font-size:15px;font-weight:800;color:var(--b3eq-text);margin:0 0 18px;">
          Fiscal Year
        </h3>
        <div class="b3eqng-grid-2">
          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              Start (MM-DD)
            </label>
            <input type="text" name="fiscal_year_start" value="<?php echo dol_escape_htmltag($fy_start); ?>"
                   placeholder="01-01" maxlength="5" class="b3eqng-input">
          </div>
          <div>
            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">
              End (MM-DD)
            </label>
            <input type="text" name="fiscal_year_end" value="<?php echo dol_escape_htmltag($fy_end); ?>"
                   placeholder="12-31" maxlength="5" class="b3eqng-input">
          </div>
        </div>
      </div>

      <button type="submit" class="b3eqng-btn" style="background: linear-gradient(135deg, #e4001b, #ff4d1a); color: white; border: none; font-weight: 700;">💾 Save Settings</button>
    </form>

    <!-- Re-seed section -->
    <div class="b3eqng-infobox" style="margin-top:28px;">
      <div class="b3eqng-infobox-title">⚙ Data Management</div>
      <p>The SQL seed is idempotent — safe to re-run at any time. Use this if accounts or tax codes appear missing.</p>
      <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin-top:12px;"
            onsubmit="return confirm('Re-run the Nigerian accounting data seed on this entity? This is safe — it will not duplicate existing records.');">
        <input type="hidden" name="action" value="reseed">
        <button type="submit" class="b3eqng-btn" style="background:linear-gradient(135deg,#334155,#1e293b);">
          ↺ Re-seed Nigerian Accounting Data
        </button>
      </form>
    </div>

    <div class="b3eqng-footer" style="margin-top:32px;border-radius:10px;">
      <span><strong style="color:var(--b3eq-accent);">b3Ɛq</strong> Nigerian Accountancy · NTA 2025 · Entity <?php echo (int)$conf->entity; ?></span>
      <span>© 2026 Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)</span>
    </div>

  </div>
</div>

<?php llxFooter(); $db->close(); ?>
