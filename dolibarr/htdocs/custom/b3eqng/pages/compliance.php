<?php
/* b3Ɛq NG Accountancy v2.0.0 — FIXED bootstrap */
require_once dirname(__DIR__) . '/class/b3eqng_init.php';
$langs->loadLangs(['b3eqng@b3eqng']);
if (!$user->rights->b3eqng->read) accessforbidden();

$b3 = new B3eqNG($db);

// Current period
$today        = date('Y-m-d');
$year         = (int)date('Y');
$month        = (int)date('m');
$period_str   = date('Y-m');
$next_month   = $month === 12 ? 1   : $month + 1;
$next_year    = $month === 12 ? $year + 1 : $year;
$last_day     = (int)date('t');

$days_until = function(string $date_str) use ($today): int {
    return (int)round((strtotime($date_str) - strtotime($today)) / 86400);
};

$get_status = function(int $days): string {
    if ($days < 0)  return 'RED';
    if ($days <= 3) return 'RED';
    if ($days <= 7) return 'AMBER';
    return 'GREEN';
};

$pad = fn($n) => str_pad((string)$n, 2, '0', STR_PAD_LEFT);

// Build current month obligations
$obligations = [
  [
    'id'       => 'PAYE',
    'label'    => 'PAYE Monthly Remittance',
    'deadline' => sprintf('%04d-%s-10', $year, $pad($month)),
    'action'   => 'File PAYE schedule + remit to State IRS / FIRS',
    'penalty'  => '₦5,000 per day of default + arrears + 10% interest',
    'authority'=> 'State IRS / FIRS',
    'account'  => '2120',
    'ref'      => 'NTA 2025 §152',
  ],
  [
    'id'       => 'VAT',
    'label'    => 'VAT Return & Remittance',
    'deadline' => sprintf('%04d-%s-21', $year, $pad($month)),
    'action'   => 'File VAT return on TaxPro Max + remit net VAT',
    'penalty'  => '₦10,000 first month; ₦20,000 subsequent months',
    'authority'=> 'NRS/FIRS',
    'account'  => '2100',
    'ref'      => 'VATA / NTA 2025',
  ],
  [
    'id'       => 'WHT',
    'label'    => 'WHT Schedule & Remittance',
    'deadline' => sprintf('%04d-%s-21', $year, $pad($month)),
    'action'   => 'File Form A schedule + remit total WHT to NRS',
    'penalty'  => '40% of unremitted amount + 10% + CBN MPR interest',
    'authority'=> 'NRS/FIRS',
    'account'  => '2113',
    'ref'      => 'WHT Regs 2024',
  ],
  [
    'id'       => 'PENSION',
    'label'    => 'Pension Contributions',
    'deadline' => sprintf('%04d-%s-07', $year, $pad($month)),
    'action'   => 'Remit employer 10% + employee 8% to PFAs',
    'penalty'  => '2% per month on outstanding contributions',
    'authority'=> 'PENCOM',
    'account'  => '2121',
    'ref'      => 'PRA 2014 §11',
  ],
  [
    'id'       => 'NHF',
    'label'    => 'NHF Remittance (2.5%)',
    'deadline' => sprintf('%04d-%s-07', $year, $pad($month)),
    'action'   => 'Remit 2.5% of employee basic salary to FMBN',
    'penalty'  => '6 months imprisonment or fine per NHF Act',
    'authority'=> 'FMBN',
    'account'  => '2123',
    'ref'      => 'NHF Act Cap N45',
  ],
  [
    'id'       => 'NSITF',
    'label'    => 'NSITF Levy (1% of Payroll)',
    'deadline' => sprintf('%04d-%s-%s', $year, $pad($month), $pad($last_day)),
    'action'   => 'Remit 1% of gross monthly payroll to NSITF',
    'penalty'  => '2% per month on outstanding amount',
    'authority'=> 'NSITF',
    'account'  => '2124',
    'ref'      => 'ECA 2010 §33',
  ],
  [
    'id'       => 'CIT',
    'label'    => 'CIT Annual Return',
    'deadline' => sprintf('%04d-06-30', $year),
    'action'   => 'File CIT return + audited accounts + pay CIT + Dev Levy',
    'penalty'  => '₦25,000 first month + ₦5,000/day thereafter',
    'authority'=> 'NRS/FIRS',
    'account'  => '2130',
    'ref'      => 'NTA 2025 §356',
  ],
  [
    'id'       => 'PAYE_ANNUAL',
    'label'    => 'Annual PAYE Return (Form H1)',
    'deadline' => sprintf('%04d-01-31', $year),
    'action'   => 'File Form H1 to State IRS — all employees, full year',
    'penalty'  => '₦500,000 or 2% of payroll — whichever is greater',
    'authority'=> 'State IRS',
    'account'  => '2120',
    'ref'      => 'NTA 2025 §156',
  ],
  [
    'id'       => 'ITF',
    'label'    => 'ITF Annual Levy (1%)',
    'deadline' => sprintf('%04d-04-01', $year),
    'action'   => 'Pay 1% of prior year gross payroll to ITF',
    'penalty'  => 'Full levy + 10% surcharge',
    'authority'=> 'Industrial Training Fund',
    'account'  => '2125',
    'ref'      => 'ITF Act Cap I9',
  ],
];

// Compute statuses
foreach ($obligations as &$ob) {
    $days          = $days_until($ob['deadline']);
    $ob['days']    = $days;
    $ob['status']  = $get_status($days);
    if ($days < 0) {
        $ob['days_label'] = abs($days) . ' day(s) OVERDUE';
    } elseif ($days === 0) {
        $ob['days_label'] = 'DUE TODAY';
    } else {
        $ob['days_label'] = $days . ' day(s)';
    }
}
unset($ob);

usort($obligations, fn($a,$b) => $a['days'] <=> $b['days']);

$red_count   = count(array_filter($obligations, fn($o) => $o['status']==='RED'));
$amber_count = count(array_filter($obligations, fn($o) => $o['status']==='AMBER'));
$green_count = count(array_filter($obligations, fn($o) => $o['status']==='GREEN'));
$overall     = $red_count > 0 ? 'RED' : ($amber_count > 0 ? 'AMBER' : 'GREEN');

$overall_label = ['RED'=>'Immediate Action Required','AMBER'=>'Attention Required','GREEN'=>'All Clear'];
$overall_color = ['RED'=>'var(--b3eq-red)','AMBER'=>'#fb923c','GREEN'=>'var(--b3eq-green)'];

llxHeader('', 'Compliance Calendar — b3Ɛq NG', '', '', '', '', []);
b3eq_inject_css();
?>
<div class="b3eqng-page">

  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">Compliance Calendar</div>
      <div class="b3eqng-header-sub">NRS/FIRS · NTA 2025 · All filing & remittance deadlines</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center;">
      <span style="font-size:11px;color:var(--b3eq-muted);"><?php echo date('l, d F Y'); ?></span>
      <span class="b3eqng-tag" style="color:<?php echo $overall_color[$overall]; ?>;border-color:<?php echo $overall_color[$overall]; ?>33;">
        <?php echo dol_escape_htmltag($overall_label[$overall]); ?>
      </span>
    </div>
  </div>

  <div style="padding:24px 28px;">

    <!-- Summary stats -->
    <div class="b3eqng-grid-3" style="margin-bottom:24px;">
      <div class="b3eqng-stat" style="border-color:rgba(248,113,113,0.4);">
        <div class="b3eqng-stat-number" style="color:var(--b3eq-red);"><?php echo $red_count; ?></div>
        <div class="b3eqng-stat-label">🔴 Critical / Overdue</div>
      </div>
      <div class="b3eqng-stat" style="border-color:rgba(251,146,60,0.4);">
        <div class="b3eqng-stat-number" style="color:#fb923c;"><?php echo $amber_count; ?></div>
        <div class="b3eqng-stat-label">🟡 Due Within 7 Days</div>
      </div>
      <div class="b3eqng-stat" style="border-color:rgba(52,211,153,0.4);">
        <div class="b3eqng-stat-number" style="color:var(--b3eq-green);"><?php echo $green_count; ?></div>
        <div class="b3eqng-stat-label">🟢 Clear</div>
      </div>
    </div>

    <!-- Obligation rows -->
    <div style="display:grid;gap:10px;">
      <?php foreach ($obligations as $ob):
        $sc = $ob['status'];
        $border = ['RED'=>'rgba(248,113,113,0.5)','AMBER'=>'rgba(251,146,60,0.5)','GREEN'=>'rgba(52,211,153,0.25)'][$sc];
        $bg     = ['RED'=>'rgba(248,113,113,0.05)','AMBER'=>'rgba(251,146,60,0.05)','GREEN'=>'transparent'][$sc];
        $dot    = ['RED'=>'🔴','AMBER'=>'🟡','GREEN'=>'🟢'][$sc];
        $badge_class = ['RED'=>'b3eqng-status-red','AMBER'=>'b3eqng-status-amber','GREEN'=>'b3eqng-status-green'][$sc];
      ?>
      <div style="background:<?php echo $bg; ?>;border:1px solid <?php echo $border; ?>;border-radius:10px;padding:14px 18px;
                  display:grid;grid-template-columns:260px 160px 1fr 1fr;align-items:center;gap:16px;">
        <div>
          <div style="font-weight:800;font-size:13px;color:var(--b3eq-text);">
            <?php echo $dot; ?> <?php echo dol_escape_htmltag($ob['label']); ?>
          </div>
          <div style="font-size:10px;color:var(--b3eq-muted);margin-top:3px;"><?php echo dol_escape_htmltag($ob['ref']); ?></div>
        </div>
        <div>
          <div style="font-size:10px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Deadline</div>
          <div style="font-size:13px;font-weight:600;color:var(--b3eq-text);"><?php echo dol_escape_htmltag($ob['deadline']); ?></div>
          <span class="<?php echo $badge_class; ?>" style="display:inline-block;margin-top:4px;font-size:10px;">
            <?php echo dol_escape_htmltag($ob['days_label']); ?>
          </span>
        </div>
        <div>
          <div style="font-size:10px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Required Action</div>
          <div style="font-size:12px;color:var(--b3eq-text);"><?php echo dol_escape_htmltag($ob['action']); ?></div>
          <div style="font-size:11px;color:var(--b3eq-muted);margin-top:2px;"><?php echo dol_escape_htmltag($ob['authority']); ?></div>
        </div>
        <div>
          <div style="font-size:10px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px;">Late Penalty</div>
          <div style="font-size:11.5px;color:var(--b3eq-red);font-weight:600;">⚠ <?php echo dol_escape_htmltag($ob['penalty']); ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Annual obligations notice -->
    <div class="b3eqng-infobox" style="margin-top:24px;">
      <div class="b3eqng-infobox-title">📅 Annual Obligations (Current Year <?php echo $year; ?>)</div>
      <p>
        <strong style="color:var(--b3eq-text);">Jan 31:</strong> Annual PAYE Return (Form H1) to State IRS &nbsp;·&nbsp;
        <strong style="color:var(--b3eq-text);">Apr 1:</strong> ITF Annual Levy (1% of prior year payroll) &nbsp;·&nbsp;
        <strong style="color:var(--b3eq-text);">Jun 30:</strong> CIT + Development Levy + CGT annual returns &nbsp;·&nbsp;
        <strong style="color:var(--b3eq-text);">Within 6 months of incorporation:</strong> TIN Registration with NRS
      </p>
    </div>

    <div class="b3eqng-footer" style="margin-top:28px;border-radius:10px;">
      <span><strong style="color:var(--b3eq-accent);">b3Ɛq</strong> Nigerian Accountancy · Compliance status as at <?php echo $today; ?></span>
      <span>© 2026 Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)</span>
    </div>

  </div>
</div>
<?php llxFooter(); $db->close(); ?>
