<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — Payroll & PAYE Calculator
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/payroll.php
 * ============================================================================
 */

require_once dirname(__DIR__) . '/class/b3eqng_init.php';
if (empty($user->rights->b3eqng->calculate) && empty($user->admin)) accessforbidden();

$b3     = new B3eqNG($db);
$action = GETPOST('action', 'none');
$result = null;

if ($action === 'calculate') {
    $basic     = (float) str_replace(',', '', GETPOST('basic_monthly', 'none'));
    $housing   = (float) str_replace(',', '', GETPOST('housing_monthly', 'none'));
    $transport = (float) str_replace(',', '', GETPOST('transport_monthly', 'none'));
    $other     = (float) str_replace(',', '', GETPOST('other_monthly', 'none'));

    $gross_monthly = $basic + $housing + $transport + $other;
    $gross_annual  = $gross_monthly * 12;

    $paye    = $b3->calculatePAYE($gross_annual);
    $levies  = $b3->calculatePayrollLevies($basic, $housing, $transport);

    $result = [
        'basic'          => $basic,
        'housing'        => $housing,
        'transport'      => $transport,
        'other'          => $other,
        'gross_monthly'  => $gross_monthly,
        'gross_annual'   => $gross_annual,
        'paye'           => $paye,
        'levies'         => $levies,
        'net_monthly'    => round($gross_monthly
                                - $paye['paye_monthly']
                                - $levies['pension_employee']
                                - $levies['nhf'], 2),
        'total_employer_cost' => round($gross_monthly
                                     + $levies['pension_employer']
                                     + $levies['nsitf'], 2),
    ];
}

llxHeader('', 'Payroll & PAYE — b3Ɛq NG');
b3eq_inject_css();
?>
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Payroll & PAYE Calculator</div>
      <div class="b3eqng-header-sub">NTA 2025 · PRA 2014 · NHF · NSITF · 6-Band PAYE Schedule</div>
    </div>
    <div style="margin-left:auto;"><span class="b3eqng-tag">NTA 2025 §152</span></div>
  </div>

  <div style="padding:24px 28px;">
    <div class="b3eqng-grid-2" style="gap:28px;">

      <!-- Input form -->
      <form method="POST">
        <input type="hidden" name="action" value="calculate">
        <div class="b3eqng-card b3eqng-card-accent">
          <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 18px;">
            Monthly Salary Breakdown
          </h3>
          <?php foreach ([
            ['basic_monthly',    'Basic Salary (₦/month)',       '300000'],
            ['housing_monthly',  'Housing Allowance (₦/month)',  '100000'],
            ['transport_monthly','Transport Allowance (₦/month)', '50000'],
            ['other_monthly',    'Other Allowances (₦/month)',   '0'],
          ] as [$name, $label, $ph]):
            $val = GETPOST($name,'alpha'); ?>
          <div style="margin-bottom:14px;">
            <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;"><?php echo $label; ?></div>
            <input type="text" name="<?php echo $name; ?>"
                   value="<?php echo dol_escape_htmltag($val); ?>"
                   placeholder="<?php echo $ph; ?>"
                   class="b3eqng-input" style="font-family:'Space Mono',monospace;">
          </div>
          <?php endforeach; ?>
          <button type="submit" class="b3eqng-btn" style="width:100%;margin-top:4px;">Calculate Payroll</button>
        </div>

        <!-- PAYE band reference -->
        <div class="b3eqng-card" style="margin-top:14px;">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:10px;">PAYE Bands — NTA 2025</div>
          <table class="b3eqng-table" style="font-size:11px;">
            <thead><tr><th>Annual Income</th><th>Rate</th></tr></thead>
            <tbody>
              <?php foreach ([
                ['Up to ₦800,000','7%'],['₦800k–₦2.4m','11%'],['₦2.4m–₦4.8m','15%'],
                ['₦4.8m–₦8m','19%'],['₦8m–₦16m','21%'],['Above ₦16m','24%'],
              ] as [$band,$rate]): ?>
              <tr><td><?php echo $band; ?></td><td><span class="b3eqng-rate-badge"><?php echo $rate; ?></span></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <div style="font-size:11px;color:var(--b3eq-muted);margin-top:8px;">
            CRA: Higher of ₦200k or 1% of gross + 20% of gross (NTA 2025 §30)
          </div>
        </div>
      </form>

      <!-- Results -->
      <div>
        <?php if ($result): $p = $result['paye']; $l = $result['levies']; ?>

        <!-- Payslip summary -->
        <div class="b3eqng-card b3eqng-card-gold" style="margin-bottom:14px;">
          <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 16px;">
            Monthly Payslip Summary
          </h3>
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px;">EARNINGS</div>
          <?php foreach ([
            ['Basic Salary',       $result['basic']],
            ['Housing Allowance',  $result['housing']],
            ['Transport Allowance',$result['transport']],
            ['Other Allowances',   $result['other']],
          ] as [$lbl,$amt]): if ($amt <= 0) continue; ?>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label"><?php echo $lbl; ?></span><span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($amt); ?></span></div>
          <?php endforeach; ?>
          <div class="b3eqng-calc-row" style="border-top:1px solid var(--b3eq-border);padding-top:8px;">
            <span class="b3eqng-calc-label" style="font-weight:700;color:var(--b3eq-text);">Gross Monthly</span>
            <span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($result['gross_monthly']); ?></span>
          </div>

          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin:14px 0 8px;">DEDUCTIONS (Employee)</div>
          <?php foreach ([
            ['PAYE Tax',                       $p['paye_monthly'],       'red'],
            ['Pension (Employee 8%)',           $l['pension_employee'],   'red'],
            ['NHF (2.5% of Basic)',             $l['nhf'],                'red'],
          ] as [$lbl,$amt,$col]): ?>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label"><?php echo $lbl; ?></span><span class="b3eqng-calc-value <?php echo $col; ?>">(<?php echo B3eqNG::formatNGN($amt); ?>)</span></div>
          <?php endforeach; ?>

          <div style="border-top:2px solid var(--b3eq-gold);margin:12px 0;"></div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:800;font-size:14px;color:var(--b3eq-text);">NET PAY</span>
            <span class="b3eqng-calc-value green" style="font-size:22px;"><?php echo B3eqNG::formatNGN($result['net_monthly']); ?></span>
          </div>
        </div>

        <!-- Employer cost -->
        <div class="b3eqng-card" style="margin-bottom:14px;">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">EMPLOYER TOTAL COST</div>
          <?php foreach ([
            ['Gross Salary',         $result['gross_monthly'], 'var(--b3eq-text)'],
            ['Pension (Employer 10%)',$l['pension_employer'],  'var(--b3eq-red)'],
            ['NSITF (1%)',           $l['nsitf'],              'var(--b3eq-red)'],
          ] as [$lbl,$amt,$col]): ?>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label"><?php echo $lbl; ?></span><span class="b3eqng-calc-value" style="color:<?php echo $col; ?>;"><?php echo B3eqNG::formatNGN($amt); ?></span></div>
          <?php endforeach; ?>
          <div style="border-top:1px solid var(--b3eq-border);margin:10px 0;"></div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:700;">Total Employer Cost</span>
            <span class="b3eqng-calc-value gold" style="font-size:16px;"><?php echo B3eqNG::formatNGN($result['total_employer_cost']); ?></span>
          </div>
        </div>

        <!-- PAYE band breakdown -->
        <div class="b3eqng-card" style="margin-bottom:14px;">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">PAYE Band Breakdown (Annual)</div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Gross Annual</span><span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($p['gross']); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">CRA Deduction</span><span class="b3eqng-calc-value green">(<?php echo B3eqNG::formatNGN($p['cra']); ?>)</span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Taxable Income</span><span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($p['taxable_income']); ?></span></div>
          <?php foreach ($p['band_breakdown'] as $band): ?>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label" style="font-size:11px;">
              Band <?php echo (int)($band['rate']*100); ?>%
              (<?php echo $band['to'] ? '₦'.number_format($band['from']).'–₦'.number_format($band['to']) : 'Above ₦'.number_format($band['from']); ?>)
            </span>
            <span class="b3eqng-calc-value red" style="font-size:12px;"><?php echo B3eqNG::formatNGN($band['tax']); ?></span>
          </div>
          <?php endforeach; ?>
          <div style="border-top:1px solid var(--b3eq-border);margin:10px 0;"></div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:700;">Annual PAYE</span>
            <span class="b3eqng-calc-value red" style="font-size:16px;"><?php echo B3eqNG::formatNGN($p['paye_annual']); ?></span>
          </div>
          <div style="margin-top:6px;font-size:11px;color:var(--b3eq-muted);">
            Effective rate: <strong style="color:var(--b3eq-text);"><?php echo $p['effective_rate']; ?>%</strong>
            &nbsp;·&nbsp; Monthly PAYE: <strong style="color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($p['paye_monthly']); ?></strong>
          </div>
        </div>

        <!-- Journal entry -->
        <div class="b3eqng-card">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Payroll Journal Entry (JV-PY)</div>
          <div class="b3eqng-journal-entry">
            <?php
            $entries = [
              ['Dr','6000','Staff Salaries & Wages',             $result['gross_monthly'],    'var(--b3eq-green)'],
              ['Dr','6002','Employer Pension (10%)',              $l['pension_employer'],      'var(--b3eq-green)'],
              ['Dr','6003','NSITF (1%)',                         $l['nsitf'],                 'var(--b3eq-green)'],
              ['Cr','2120','PAYE Tax Payable',                   $p['paye_monthly'],          'var(--b3eq-red)'],
              ['Cr','2121','Pension Payable – Employer',         $l['pension_employer'],      'var(--b3eq-red)'],
              ['Cr','2122','Pension Payable – Employee',         $l['pension_employee'],      'var(--b3eq-red)'],
              ['Cr','2123','NHF Payable',                        $l['nhf'],                   'var(--b3eq-red)'],
              ['Cr','2124','NSITF Payable',                      $l['nsitf'],                 'var(--b3eq-red)'],
              ['Cr','1002','Bank (Net Pay)',                     $result['net_monthly'],      'var(--b3eq-red)'],
            ];
            foreach ($entries as [$side,$acc,$lbl,$amt,$col]):
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
        </div>

        <?php else: ?>
        <div class="b3eqng-card" style="text-align:center;padding:48px 24px;">
          <div style="font-size:40px;margin-bottom:14px;">💰</div>
          <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--b3eq-text);margin-bottom:8px;">Enter salary components</div>
          <div style="color:var(--b3eq-muted);font-size:13px;">Fill in the monthly salary breakdown and click Calculate.</div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /grid -->
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
