<?php
/* b3Ɛq NG Accountancy v2.0.0 — FIXED bootstrap */
require_once dirname(__DIR__) . '/class/b3eqng_init.php';
$langs->loadLangs(['b3eqng@b3eqng']);
if (!$user->rights->b3eqng->read) accessforbidden();

$open_section = GETPOST('section', 'aZ09') ?: 'vat';

// All tax data — mirrors data/tax_rates.json but rendered server-side
$TAX_GROUPS = [
  [
    'id'        => 'vat',
    'label'     => 'Value Added Tax (VAT)',
    'authority' => 'NRS/FIRS',
    'deadline'  => '21st of following month',
    'ref'       => 'VATA Cap V1 as amended NTA 2025',
    'rows' => [
      ['Standard Rate',  '7.5%',  'All taxable goods & services',            'VATA / NTA 2025 default rate'],
      ['Zero-Rated',     '0%',    'Exports, humanitarian, diplomatic',        'Input VAT fully recoverable'],
      ['Exempt',         'N/A',   'Basic food, medical, educational materials','No input VAT recovery'],
    ]
  ],
  [
    'id'        => 'cit',
    'label'     => 'Companies Income Tax (CIT)',
    'authority' => 'NRS/FIRS',
    'deadline'  => 'June 30 (6 months after year-end)',
    'ref'       => 'NTA 2025 §56',
    'rows' => [
      ['Small Company (≤₦100m turnover)',   '0%',               'Assessable profit', 'Also exempt: Dev Levy, CGT'],
      ['Medium Company (₦100m–₦1bn)',       '20%',              'Assessable profit', 'Transitional rate NTA 2025'],
      ['Large Company (>₦1bn)',             '30%',              'Assessable profit', '25% from 2026 YOA per NTA 2025 §56'],
      ['Minimum Tax',                       '0.5% of turnover', 'Gross turnover',    'Where CIT < minimum tax; not for small co.'],
    ]
  ],
  [
    'id'        => 'devlevy',
    'label'     => 'Development Levy',
    'authority' => 'NRS/FIRS',
    'deadline'  => 'With CIT annual return',
    'ref'       => 'NTA 2025 (replaces Education Tax)',
    'rows' => [
      ['Development Levy', '4%', 'Assessable profits', 'Small companies exempt'],
    ]
  ],
  [
    'id'        => 'wht',
    'label'     => 'Withholding Tax (WHT)',
    'authority' => 'NRS/FIRS',
    'deadline'  => '21st of following month',
    'ref'       => 'WHT Regulations 2024 (eff. 1 Jan 2025)',
    'rows' => [
      ['Dividends',                           '10%',  'Gross dividend',        'Final tax for non-residents'],
      ['Interest',                            '10%',  'Gross interest',        'Incl. CBN T-Bills'],
      ['Rent',                                '10%',  'Gross rent',            'Payable by tenant'],
      ['Royalties',                           '10%',  'Gross royalty',         ''],
      ['Director Fees',                       '10%',  'Gross fees',            ''],
      ['Professional Fees (Legal/Audit/Tax)', '10%',  'Gross fees',            'Legal, audit, tax consultancy'],
      ['Technical/Management/Consulting',     '10%',  'Gross fees',            ''],
      ['Commission / Agency Fees',            '10%',  'Gross commission',      ''],
      ['Contracts & Supply (Goods)',          '5%',   'Contract value',        ''],
      ['Construction / Drilling / Survey',    '2.5%', 'Contract value',        ''],
      ['No-TIN Penalty',                      '2× rate (max 20%)', 'Gross payment', 'Vendor without valid TIN'],
      ['Small Company Exemption',             'EXEMPT','≤₦2m/month with valid TIN','Payer is small co. (WHT Regs 2024)'],
    ]
  ],
  [
    'id'        => 'paye',
    'label'     => 'PAYE (Personal Income Tax)',
    'authority' => 'State IRS / FIRS',
    'deadline'  => '10th of following month',
    'ref'       => 'NTA 2025 §§130–160',
    'rows' => [
      ['Up to ₦800,000 p.a.',      '7%',  'Taxable income after CRA', 'NTA 2025 Band 1'],
      ['₦800,001 – ₦2.4m',        '11%', 'Taxable income',           'Band 2'],
      ['₦2.4m – ₦4.8m',           '15%', 'Taxable income',           'Band 3'],
      ['₦4.8m – ₦8m',             '19%', 'Taxable income',           'Band 4'],
      ['₦8m – ₦16m',              '21%', 'Taxable income',           'Band 5'],
      ['Above ₦16m',              '24%', 'Taxable income',           'Band 6 — headline cap'],
      ['Consolidated Relief (CRA)','Higher of ₦200k or 1% + 20% of gross','Before PAYE calc','NTA 2025 §30'],
    ]
  ],
  [
    'id'        => 'cgt',
    'label'     => 'Capital Gains Tax (CGT)',
    'authority' => 'NRS/FIRS',
    'deadline'  => 'With CIT return',
    'ref'       => 'NTA 2025 (CGT aligned with CIT)',
    'rows' => [
      ['Companies',   '30%', 'Net chargeable gain', 'Small companies exempt; 25% from 2026 YOA'],
      ['Individuals', '10%', 'Net chargeable gain', ''],
    ]
  ],
  [
    'id'        => 'payroll',
    'label'     => 'Statutory Payroll Levies',
    'authority' => 'Various agencies',
    'deadline'  => 'Various (see notes)',
    'ref'       => 'PRA 2014 · ECA 2010 · NHF Act · ITF Act',
    'rows' => [
      ['Employer Pension',   '10% of emoluments',   'Basic+Housing+Transport', '7 days after salary · to PFA'],
      ['Employee Pension',   '8% of emoluments',    'Basic+Housing+Transport', 'Deducted from employee · to PFA'],
      ['NHF',                '2.5% of basic salary','Employee deduction',       '1st week of month · to FMBN'],
      ['NSITF',              '1% of gross payroll', 'Employer cost',            'Month-end · to NSITF'],
      ['ITF',                '1% of annual payroll','≥5 staff or ≥₦50m T/O',   'April 1 annually · to ITF'],
      ['NITDA',              '1% of PBT',           'IT/telecom companies only','With CIT return · to NITDA'],
    ]
  ],
  [
    'id'        => 'stamp',
    'label'     => 'Stamp Duty',
    'authority' => 'NRS/FIRS (federal) · SIRS (state)',
    'deadline'  => '30 days of executing instrument',
    'ref'       => 'Stamp Duties Act Cap S8 as amended',
    'rows' => [
      ['Agreement / Contract',         '₦500 flat',  'Per document',       ''],
      ['Bank Electronic Transfer ≥₦10k','₦50 flat',   'Per transfer',       ''],
      ['Memoranda & Articles',         '0.75%',      'Nominal share capital','On incorporation'],
      ['Tenancy ≤1 year',              '0.78%',      'Annual rent',         ''],
      ['Tenancy 1–7 years',            '3%',         'Annual rent',         ''],
      ['Tenancy >7 years',             '6%',         'Annual rent',         ''],
      ['Deed of Conveyance',           '1.5%',       'Consideration value', ''],
    ]
  ],
];

llxHeader('', 'Tax Rates — b3Ɛq NG', '', '', '', '', []);
b3eq_inject_css();
?>
<div class="b3eqng-page">

  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Nigeria Tax Rates & Codes</div>
      <div class="b3eqng-header-sub">Nigeria Tax Act 2025 (NTA) · All federal taxes · NRS/FIRS</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <span class="b3eqng-tag">NTA 2025</span>
      <span class="b3eqng-tag-gold">WHT Regs 2024</span>
    </div>
  </div>

  <div style="padding:24px 28px;">

    <?php foreach ($TAX_GROUPS as $group):
      $is_open = ($open_section === $group['id']);
    ?>
    <div style="margin-bottom:14px;">
      <a href="?section=<?php echo dol_escape_htmltag($group['id']); ?>"
         style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;
                background:<?php echo $is_open ? 'rgba(228,0,27,0.12)' : 'rgba(255,255,255,0.03)'; ?>;
                border:1px solid <?php echo $is_open ? 'rgba(228,0,27,0.35)' : 'var(--b3eq-border)'; ?>;
                border-radius:10px;padding:14px 18px;">
        <div>
          <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:14px;color:var(--b3eq-text);">
            <?php echo dol_escape_htmltag($group['label']); ?>
          </span>
          <span style="margin-left:14px;font-size:11px;color:var(--b3eq-muted);">
            <?php echo dol_escape_htmltag($group['authority']); ?> &nbsp;·&nbsp; Deadline: <?php echo dol_escape_htmltag($group['deadline']); ?>
          </span>
        </div>
        <span style="color:var(--b3eq-accent);font-size:16px;"><?php echo $is_open ? '▲' : '▼'; ?></span>
      </a>

      <?php if ($is_open): ?>
      <div style="border:1px solid rgba(228,0,27,0.25);border-top:none;border-radius:0 0 10px 10px;overflow:hidden;">
        <table class="b3eqng-table">
          <thead>
            <tr style="background:rgba(228,0,27,0.08);">
              <th style="color:var(--b3eq-accent);">Transaction / Class</th>
              <th style="color:var(--b3eq-accent);">Rate</th>
              <th style="color:var(--b3eq-accent);">Tax Base</th>
              <th style="color:var(--b3eq-accent);">Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($group['rows'] as $ii => $row): ?>
            <tr style="<?php echo $ii%2===0?'':'background:rgba(255,255,255,0.02)'; ?>">
              <td style="font-weight:600;"><?php echo dol_escape_htmltag($row[0]); ?></td>
              <td><span class="b3eqng-rate-badge"><?php echo dol_escape_htmltag($row[1]); ?></span></td>
              <td style="color:var(--b3eq-muted);font-size:12px;"><?php echo dol_escape_htmltag($row[2]); ?></td>
              <td style="color:var(--b3eq-muted);font-size:11px;"><?php echo dol_escape_htmltag($row[3]); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div style="padding:10px 18px;font-size:11px;color:var(--b3eq-muted);border-top:1px solid var(--b3eq-border);">
          Reference: <?php echo dol_escape_htmltag($group['ref']); ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="b3eqng-infobox">
      <div class="b3eqng-infobox-title">⚠ CIT Transition Note</div>
      <p>Under NTA 2025 §56, the CIT headline rate for large companies transitions to <strong style="color:var(--b3eq-text);">25%</strong> from the 2026 Year of Assessment. FIRS implementation guidance pending. This module will be updated to v1.1.0 when guidance is published.</p>
    </div>

  </div>
</div>
<?php llxFooter(); $db->close(); ?>
