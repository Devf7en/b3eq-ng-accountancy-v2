<?php
/* b3Ɛq NG Accountancy v2.0.0 — FIXED bootstrap */
require_once dirname(__DIR__) . '/class/b3eqng_init.php';
$langs->loadLangs(['b3eqng@b3eqng']);
if (!$user->rights->b3eqng->calculate) accessforbidden();

$b3 = new B3eqNG($db);

// WHT types for dropdown
$WHT_TYPES = [
    'NG-WHT-DIV' => ['label' => 'Dividends',                               'rate' => '10%'],
    'NG-WHT-INT' => ['label' => 'Interest',                                'rate' => '10%'],
    'NG-WHT-RNT' => ['label' => 'Rent',                                    'rate' => '10%'],
    'NG-WHT-ROY' => ['label' => 'Royalties',                               'rate' => '10%'],
    'NG-WHT-DIR' => ['label' => 'Director Fees',                           'rate' => '10%'],
    'NG-WHT-PRF' => ['label' => 'Professional Fees (Legal/Audit/Tax)',     'rate' => '10%'],
    'NG-WHT-TEC' => ['label' => 'Technical / Management / Consulting',     'rate' => '10%'],
    'NG-WHT-COM' => ['label' => 'Commission / Agency Fees',                'rate' => '10%'],
    'NG-WHT-SUP' => ['label' => 'Contracts & Supply (Goods)',               'rate' => '5%'],
    'NG-WHT-CON' => ['label' => 'Construction / Drilling / Survey',         'rate' => '2.5%'],
];

$ACCOUNT_LABELS = [
    '2110' => 'WHT Payable – Dividends',     '2111' => 'WHT Payable – Interest',
    '2112' => 'WHT Payable – Rent',          '2113' => 'WHT Payable – Contracts/Supplies',
    '2114' => 'WHT Payable – Professional',  '2115' => 'WHT Payable – Director Fees',
    '2116' => 'WHT Payable – Technical Fees','2117' => 'WHT Payable – Commissions',
    '2118' => 'WHT Payable – Royalties',     '2119' => 'WHT Payable – Construction',
];

$EXPENSE_ACCOUNTS = [
    'NG-WHT-DIV' => '4102', 'NG-WHT-INT' => '4100', 'NG-WHT-RNT' => '6010',
    'NG-WHT-ROY' => '6021', 'NG-WHT-DIR' => '6020', 'NG-WHT-PRF' => '6020',
    'NG-WHT-TEC' => '6021', 'NG-WHT-COM' => '6022', 'NG-WHT-SUP' => '5002',
    'NG-WHT-CON' => '6013',
];

$result      = null;
$wht_code    = GETPOST('wht_code', 'aZ09') ?: 'NG-WHT-PRF';
$gross_input = GETPOST('gross_amount', 'none');
$has_tin     = GETPOST('has_tin', 'int') ?? 1;
$small_co    = GETPOST('small_co_exempt', 'int') ?? 0;
$vendor_name = GETPOST('vendor_name', 'alphanohtml');
$description = GETPOST('description', 'alphanohtml');

if (GETPOST('action', 'none') === 'calculate' && $gross_input !== '') {
    $gross  = (float) str_replace([',', ' '], '', $gross_input);
    $result = $b3->calculateWHT($gross, $wht_code, (bool)$has_tin, (bool)$small_co);
    $result['wht_code']       = $wht_code;
    $result['expense_account']= $EXPENSE_ACCOUNTS[$wht_code] ?? '6020';
    $result['wht_account']    = $result['account_payable'] ?? '2113';
}

llxHeader('', 'WHT Calculator — b3Ɛq NG', '', '', '', '', []);
b3eq_inject_css();
?>
<div class="b3eqng-page">

  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Withholding Tax Calculator</div>
      <div class="b3eqng-header-sub">NTA 2025 · WHT Regulations 2024 · Deduction at Source</div>
    </div>
    <div style="margin-left:auto;"><span class="b3eqng-tag">WHT Regs 2024</span></div>
  </div>

  <div style="padding:24px 28px;">
    <div class="b3eqng-grid-2" style="gap:28px;">

      <!-- Input form -->
      <div>
        <form method="POST">
          <input type="hidden" name="action" value="calculate">

          <div class="b3eqng-card b3eqng-card-accent" style="margin-bottom:0;">
            <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 18px;">
              Transaction Details
            </h3>

            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">Transaction Type</label>
            <select name="wht_code" class="b3eqng-input" style="margin-bottom:16px;">
              <?php foreach ($WHT_TYPES as $code => $info): ?>
                <option value="<?php echo $code; ?>" <?php echo $wht_code===$code?'selected':''; ?>>
                  <?php echo dol_escape_htmltag($info['label']); ?> (<?php echo $info['rate']; ?>)
                </option>
              <?php endforeach; ?>
            </select>

            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">Gross Payment Amount (₦)</label>
            <input type="text" name="gross_amount" value="<?php echo dol_escape_htmltag($gross_input); ?>"
                   placeholder="e.g. 500000" class="b3eqng-input" style="margin-bottom:16px;font-family:'Space Mono',monospace;">

            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">Vendor / Payee Name</label>
            <input type="text" name="vendor_name" value="<?php echo dol_escape_htmltag($vendor_name); ?>"
                   placeholder="e.g. Zenith Legal Associates" class="b3eqng-input" style="margin-bottom:16px;">

            <label style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;display:block;margin-bottom:6px;">Description / Invoice Ref</label>
            <input type="text" name="description" value="<?php echo dol_escape_htmltag($description); ?>"
                   placeholder="e.g. Legal fees — Q2 retainer" class="b3eqng-input" style="margin-bottom:20px;">

            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px;">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="has_tin" value="1" <?php echo $has_tin?'checked':''; ?> style="width:16px;height:16px;">
                <span style="font-size:13px;color:var(--b3eq-text);">Vendor has valid FIRS TIN</span>
              </label>
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" name="small_co_exempt" value="1" <?php echo $small_co?'checked':''; ?> style="width:16px;height:16px;">
                <span style="font-size:13px;color:var(--b3eq-text);">Small company exemption applies (≤₦2m/month with valid TIN)</span>
              </label>
            </div>

            <button type="submit" class="b3eqng-btn" style="width:100%;">Calculate WHT</button>
          </div>
        </form>

        <!-- Reference card -->
        <div class="b3eqng-infobox" style="margin-top:16px;">
          <div class="b3eqng-infobox-title">ℹ WHT Quick Reference</div>
          <p>
            <strong style="color:var(--b3eq-text);">Remit by:</strong> 21st of following month to NRS via TaxPro Max<br>
            <strong style="color:var(--b3eq-text);">Issue:</strong> Form A (WHT Credit Certificate) within 30 days<br>
            <strong style="color:var(--b3eq-text);">No TIN:</strong> Rate doubles — max 20% (WHT Regs 2024)<br>
            <strong style="color:var(--b3eq-text);">Small co.:</strong> Exempt if payer ≤₦100m turnover + vendor TIN + ≤₦2m/month
          </p>
        </div>
      </div>

      <!-- Result panel -->
      <div>
        <?php if ($result): ?>

        <div class="b3eqng-card <?php echo $result['exempt'] ? 'b3eqng-card-gold' : 'b3eqng-card-accent'; ?>" style="margin-bottom:16px;">
          <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 18px;">
            Calculation Result
            <?php if ($result['exempt']): ?>
              <span class="b3eqng-status-green" style="margin-left:10px;">EXEMPT</span>
            <?php elseif ($result['no_tin_penalty']): ?>
              <span class="b3eqng-status-red" style="margin-left:10px;">NO TIN PENALTY</span>
            <?php endif; ?>
          </h3>

          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Transaction Type</span>
            <span class="b3eqng-calc-value"><?php echo dol_escape_htmltag($WHT_TYPES[$result['wht_code']]['label'] ?? $result['wht_code']); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">WHT Code</span>
            <span class="b3eqng-calc-value" style="font-family:monospace;color:var(--b3eq-cyan);"><?php echo dol_escape_htmltag($result['wht_code']); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Standard Rate</span>
            <span class="b3eqng-calc-value"><?php echo round($result['rate'] * 100, 1); ?>%</span>
          </div>
          <?php if ($result['no_tin_penalty']): ?>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">No-TIN Effective Rate</span>
            <span class="b3eqng-calc-value red"><?php echo round($result['effective_rate'] * 100, 1); ?>% (doubled)</span>
          </div>
          <?php endif; ?>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Gross Payment</span>
            <span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($result['gross']); ?></span>
          </div>
          <div style="border-top:1px solid var(--b3eq-border);margin:10px 0;"></div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">WHT Deducted</span>
            <span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($result['wht_amount']); ?></span>
          </div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:700;color:var(--b3eq-text);">Net Payable to Vendor</span>
            <span class="b3eqng-calc-value green" style="font-size:18px;"><?php echo B3eqNG::formatNGN($result['net_payable']); ?></span>
          </div>

          <?php if ($result['note']): ?>
          <div style="margin-top:14px;padding:10px 12px;background:rgba(255,255,255,0.04);border-radius:7px;font-size:12px;color:var(--b3eq-muted);">
            <?php echo dol_escape_htmltag($result['note']); ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Journal Entry -->
        <?php if (!$result['exempt'] && $result['wht_amount'] > 0):
          $exp_acc = $result['expense_account'];
          $wht_acc = $result['wht_account'];
          $ap_acc  = '2000';
        ?>
        <div class="b3eqng-card" style="margin-bottom:16px;">
          <h4 style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--b3eq-muted);margin:0 0 14px;text-transform:uppercase;letter-spacing:.06em;">
            Suggested Journal Entry (JV-PU / JV-TX)
          </h4>
          <div class="b3eqng-journal-entry">
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px;">
              <span class="b3eqng-journal-dr">Dr</span>
              <span style="color:var(--b3eq-cyan);width:60px;font-size:11px;"><?php echo $exp_acc; ?></span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Expense Account</span>
              <span style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($result['gross']); ?></span>
            </div>
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px;padding-left:20px;">
              <span class="b3eqng-journal-cr">Cr</span>
              <span style="color:var(--b3eq-cyan);width:60px;font-size:11px;"><?php echo $wht_acc; ?></span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;"><?php echo dol_escape_htmltag($ACCOUNT_LABELS[$wht_acc] ?? 'WHT Payable'); ?></span>
              <span style="color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($result['wht_amount']); ?></span>
            </div>
            <div style="display:flex;gap:6px;align-items:center;padding-left:20px;">
              <span class="b3eqng-journal-cr">Cr</span>
              <span style="color:var(--b3eq-cyan);width:60px;font-size:11px;"><?php echo $ap_acc; ?></span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Accounts Payable / Bank</span>
              <span style="color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($result['net_payable']); ?></span>
            </div>
          </div>
          <div style="margin-top:12px;font-size:11px;color:var(--b3eq-muted);">
            Remit <strong style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($result['wht_amount']); ?></strong>
            to NRS by 21st of following month.
            Issue Form A credit certificate to vendor within 30 days.
          </div>
        </div>

        <!-- On Payment entry -->
        <div class="b3eqng-card">
          <h4 style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--b3eq-muted);margin:0 0 14px;text-transform:uppercase;letter-spacing:.06em;">
            On WHT Remittance to NRS (JV-TX)
          </h4>
          <div class="b3eqng-journal-entry">
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px;">
              <span class="b3eqng-journal-dr">Dr</span>
              <span style="color:var(--b3eq-cyan);width:60px;font-size:11px;"><?php echo $wht_acc; ?></span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;"><?php echo dol_escape_htmltag($ACCOUNT_LABELS[$wht_acc] ?? 'WHT Payable'); ?></span>
              <span style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($result['wht_amount']); ?></span>
            </div>
            <div style="display:flex;gap:6px;align-items:center;padding-left:20px;">
              <span class="b3eqng-journal-cr">Cr</span>
              <span style="color:var(--b3eq-cyan);width:60px;font-size:11px;">1002</span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Bank Account</span>
              <span style="color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($result['wht_amount']); ?></span>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- Empty state -->
        <div class="b3eqng-card" style="text-align:center;padding:48px 24px;">
          <div style="font-size:40px;margin-bottom:14px;">🧮</div>
          <div style="font-family:'Syne',sans-serif;font-size:16px;font-weight:800;color:var(--b3eq-text);margin-bottom:8px;">
            Enter transaction details
          </div>
          <div style="color:var(--b3eq-muted);font-size:13px;">
            Select transaction type, enter gross amount, and click Calculate.
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /grid -->
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
