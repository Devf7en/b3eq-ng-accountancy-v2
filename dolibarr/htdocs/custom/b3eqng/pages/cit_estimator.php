<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — CIT Estimator
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/cit_estimator.php
 * ============================================================================
 */

require_once dirname(__DIR__) . '/class/b3eqng_init.php';
if (empty($user->rights->b3eqng->calculate) && empty($user->admin)) accessforbidden();

$b3     = new B3eqNG($db);
$action = GETPOST('action', 'none');
$result = null;

$turnover = (float) str_replace(',', '', GETPOST('annual_turnover', 'none'));
$profit   = (float) str_replace(',', '', GETPOST('assessable_profit', 'none'));

if ($action === 'calculate' && $turnover > 0) {
    $result = $b3->calculateCIT($turnover, $profit);
    // Add CGT scenario
    $cgt_gain = (float) str_replace(',', '', GETPOST('cgt_gain', 'none'));
    $result['cgt_gain']   = $cgt_gain;
    $result['cgt_amount'] = $result['size'] !== 'SMALL' ? round($cgt_gain * B3eqNG::CGT_COMPANY, 2) : 0;
}

$company_size = b3eq_conf('B3EQNG_COMPANY_SIZE', 'LARGE');

llxHeader('', 'CIT Estimator — b3Ɛq NG');
b3eq_inject_css();
?>
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">CIT & Tax Estimator</div>
      <div class="b3eqng-header-sub">NTA 2025 §56 · CIT · Development Levy · CGT · Minimum Tax</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <span class="b3eqng-tag">NTA 2025</span>
      <span class="b3eqng-tag-gold">Registered: <?php echo dol_escape_htmltag($company_size); ?></span>
    </div>
  </div>

  <div style="padding:24px 28px;">
    <div class="b3eqng-grid-2" style="gap:28px;">

      <form method="POST">
        <input type="hidden" name="action" value="calculate">
        <div class="b3eqng-card b3eqng-card-accent">
          <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 18px;">
            Financial Year Inputs
          </h3>
          <?php foreach ([
            ['annual_turnover',   'Annual Gross Turnover (₦)',      $turnover > 0 ? number_format($turnover,2) : ''],
            ['assessable_profit', 'Assessable Profit (₦)',           $profit > 0   ? number_format($profit,2)   : ''],
            ['cgt_gain',          'Net Capital Gain on Disposals (₦) — optional', ''],
          ] as [$name,$label,$val]): ?>
          <div style="margin-bottom:16px;">
            <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;"><?php echo $label; ?></div>
            <input type="text" name="<?php echo $name; ?>" value="<?php echo dol_escape_htmltag($val); ?>"
                   placeholder="0.00" class="b3eqng-input" style="font-family:'Space Mono',monospace;">
          </div>
          <?php endforeach; ?>
          <button type="submit" class="b3eqng-btn" style="width:100%;">Estimate Tax</button>
        </div>

        <!-- Size thresholds reference -->
        <div class="b3eqng-card" style="margin-top:14px;">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">CIT Size Classification — NTA 2025 §56</div>
          <table class="b3eqng-table" style="font-size:11px;">
            <thead><tr><th>Size</th><th>Turnover</th><th>Rate</th><th>Notes</th></tr></thead>
            <tbody>
              <tr><td style="color:var(--b3eq-green);font-weight:700;">Small</td><td>≤ ₦100m</td><td><span class="b3eqng-rate-badge">0%</span></td><td>Also: no Dev Levy, no CGT</td></tr>
              <tr><td style="color:#fb923c;font-weight:700;">Medium</td><td>₦100m–₦1bn</td><td><span class="b3eqng-rate-badge">20%</span></td><td>Transitional rate</td></tr>
              <tr><td style="color:var(--b3eq-red);font-weight:700;">Large</td><td>> ₦1bn</td><td><span class="b3eqng-rate-badge">30%</span></td><td>25% from 2026 YOA</td></tr>
            </tbody>
          </table>
          <div style="font-size:11px;color:var(--b3eq-muted);margin-top:8px;">
            ⚠ Minimum Tax = 0.5% of gross turnover (applies when CIT &lt; minimum tax; medium/large only)
          </div>
        </div>
      </form>

      <!-- Result -->
      <div>
        <?php if ($result): ?>
        <div class="b3eqng-card b3eqng-card-<?php echo $result['size']==='SMALL'?'gold':'accent'; ?>" style="margin-bottom:14px;">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0;">
              Tax Estimate — <?php echo dol_escape_htmltag($result['size']); ?> Company
            </h3>
            <?php if ($result['size']==='SMALL'): ?>
              <span class="b3eqng-status-green">EXEMPT</span>
            <?php elseif ($result['min_tax_applies']): ?>
              <span class="b3eqng-status-amber">MIN TAX APPLIES</span>
            <?php endif; ?>
          </div>

          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Annual Turnover</span><span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($turnover); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Assessable Profit</span><span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($profit); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">CIT Rate</span><span class="b3eqng-calc-value"><?php echo ($result['cit_rate']*100); ?>%</span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">CIT on Profit</span><span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($result['cit_amount']); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Minimum Tax (0.5% turnover)</span><span class="b3eqng-calc-value <?php echo $result['min_tax_applies']?'red':''; ?>"><?php echo B3eqNG::formatNGN($result['minimum_tax']); ?></span></div>

          <?php if ($result['min_tax_applies']): ?>
          <div style="margin:8px 0;padding:8px 12px;background:rgba(251,146,60,0.1);border-radius:6px;font-size:12px;color:#fb923c;">
            ⚠ Minimum tax (<?php echo B3eqNG::formatNGN($result['minimum_tax']); ?>) exceeds CIT computed (<?php echo B3eqNG::formatNGN($result['cit_amount']); ?>) — minimum tax applies.
          </div>
          <?php endif; ?>

          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Effective CIT Payable</span><span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($result['effective_cit']); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Development Levy (4%)</span><span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($result['dev_levy_amount']); ?></span></div>

          <?php if (($result['cgt_gain'] ?? 0) > 0): ?>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Capital Gains Tax (30%)</span><span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($result['cgt_amount']); ?></span></div>
          <?php endif; ?>

          <div style="border-top:2px solid var(--b3eq-accent);margin:14px 0;"></div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:800;font-size:14px;color:var(--b3eq-text);">TOTAL TAX LIABILITY</span>
            <span class="b3eqng-calc-value red" style="font-size:22px;">
              <?php echo B3eqNG::formatNGN($result['total_tax'] + ($result['cgt_amount'] ?? 0)); ?>
            </span>
          </div>
          <div style="margin-top:8px;font-size:11px;color:var(--b3eq-muted);">
            Effective rate on profit: <strong style="color:var(--b3eq-text);"><?php echo $result['effective_rate']; ?>%</strong>
          </div>
          <?php if (!empty($result['note'])): ?>
          <div style="margin-top:10px;padding:8px 12px;background:rgba(255,255,255,0.04);border-radius:6px;font-size:11px;color:var(--b3eq-muted);">
            <?php echo dol_escape_htmltag($result['note']); ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Journal entries -->
        <div class="b3eqng-card">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">CIT Provision Journal Entry (JV-TX)</div>
          <div class="b3eqng-journal-entry">
            <?php foreach ([
              ['Dr','7000','CIT Expense',              $result['effective_cit'],    'var(--b3eq-green)'],
              ['Dr','7001','Development Levy Expense',  $result['dev_levy_amount'],  'var(--b3eq-green)'],
              ['Cr','2130','CIT Payable',               $result['effective_cit'],    'var(--b3eq-red)'],
              ['Cr','2131','Dev Levy Payable',          $result['dev_levy_amount'],  'var(--b3eq-red)'],
            ] as [$side,$acc,$lbl,$amt,$col]):
              if ($amt <= 0) continue;
            ?>
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:4px;<?php echo $side==='Cr'?'padding-left:18px;':''; ?>">
              <span style="font-weight:700;color:<?php echo $side==='Dr'?'var(--b3eq-green)':'var(--b3eq-red)'; ?>;width:20px;"><?php echo $side; ?></span>
              <span style="color:var(--b3eq-cyan);width:45px;font-size:11px;"><?php echo $acc; ?></span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;"><?php echo $lbl; ?></span>
              <span style="color:<?php echo $col; ?>;font-size:11px;"><?php echo B3eqNG::formatNGN($amt); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="margin-top:10px;font-size:11px;color:var(--b3eq-muted);">
            CIT return filing deadline: <strong style="color:var(--b3eq-text);">June 30</strong> (6 months after year-end). Portal: TaxPro Max.
          </div>
        </div>

        <?php else: ?>
        <div class="b3eqng-card" style="text-align:center;padding:48px 24px;">
          <div style="font-size:40px;margin-bottom:14px;">🏛</div>
          <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--b3eq-text);margin-bottom:8px;">Enter annual figures</div>
          <div style="color:var(--b3eq-muted);font-size:13px;">Enter turnover and assessable profit to estimate CIT, Development Levy, and CGT.</div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
