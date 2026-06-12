<?php
/* b3Ɛq NG Accountancy v2.0.0 — FIXED bootstrap */
require_once dirname(__DIR__) . '/class/b3eqng_init.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
$langs->loadLangs(['b3eqng@b3eqng', 'bills']);
if (!$user->rights->b3eqng->calculate) accessforbidden();

$b3     = new B3eqNG($db);
$action = GETPOST('action', 'none');

// Period selector — default to previous month
$period_year  = GETPOST('period_year', 'int')  ?: (int)date('Y', strtotime('first day of last month'));
$period_month = GETPOST('period_month', 'int') ?: (int)date('m', strtotime('first day of last month'));

$period_start = sprintf('%04d-%02d-01', $period_year, $period_month);
$period_end   = date('Y-m-t', strtotime($period_start));
$period_label = date('F Y', strtotime($period_start));
$filing_deadline = sprintf('%04d-%02d-21', $period_month == 12 ? $period_year+1 : $period_year, $period_month == 12 ? 1 : $period_month+1);

// ── Pull invoices from Dolibarr ────────────────────────────────────────────
$sales_lines = [];
$purchase_lines = [];

// Sales invoices — validated, in period
$sql_sales = "SELECT f.rowid, f.ref, f.total_ht, f.total_tva, f.total_ttc,
                     f.date_validation, f.fk_statut
              FROM llx_facture f
              WHERE f.entity = " . (int)$conf->entity . "
                AND f.fk_statut IN (1, 2)
                AND DATE(f.date_validation) BETWEEN '" . $db->escape($period_start) . "'
                    AND '" . $db->escape($period_end) . "'
              ORDER BY f.date_validation ASC";

$res_sales = $db->query($sql_sales);
$sales_invoices = [];
$total_sales_ht = 0;
$total_output_vat = 0;
if ($res_sales) {
    while ($obj = $db->fetch_object($res_sales)) {
        $sales_invoices[] = $obj;
        $total_sales_ht   += (float)$obj->total_ht;
        $total_output_vat += (float)$obj->total_tva;
        $sales_lines[] = ['amount' => (float)$obj->total_ht, 'type' => 'standard'];
    }
}

// Purchase invoices — validated, in period
$sql_purch = "SELECT f.rowid, f.ref, f.total_ht, f.total_tva, f.total_ttc,
                     f.date_validation, f.fk_statut,
                     s.nom as supplier_name
              FROM llx_facture_fourn f
              LEFT JOIN llx_societe s ON s.rowid = f.fk_soc
              WHERE f.entity = " . (int)$conf->entity . "
                AND f.fk_statut IN (1, 2)
                AND DATE(f.date_validation) BETWEEN '" . $db->escape($period_start) . "'
                    AND '" . $db->escape($period_end) . "'
              ORDER BY f.date_validation ASC";

$res_purch = $db->query($sql_purch);
$purchase_invoices = [];
$total_purch_ht = 0;
$total_input_vat = 0;
if ($res_purch) {
    while ($obj = $db->fetch_object($res_purch)) {
        $purchase_invoices[] = $obj;
        $total_purch_ht  += (float)$obj->total_ht;
        $total_input_vat += (float)$obj->total_tva;
        $purchase_lines[] = ['amount' => (float)$obj->total_ht, 'vat_recoverable' => true];
    }
}

// Calculate using B3eqNG class
$vat_result = $b3->calculateVAT($sales_lines, $purchase_lines);

// ── Post journal entry ─────────────────────────────────────────────────────
$posted = false;
$post_error = '';
if ($action === 'post_journal_entry' && $user->rights->b3eqng->post) {
    require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';

    $bk = new BookKeeping($db);
    $db->begin();

    $net = $vat_result['net_vat'];
    $label_entry = 'VAT Return ' . $period_label . ' — b3Ɛq NG';

    if ($net > 0) {
        // VAT payable: Dr 2102 (VAT control), Cr 1002 (Bank — will be paid)
        $bk->doc_date           = dol_now();
        $bk->doc_ref            = 'VAT-' . $period_year . '-' . str_pad($period_month,2,'0',STR_PAD_LEFT);
        $bk->doc_type           = 'various';
        $bk->fk_doc             = 0;
        $bk->fk_docdet          = 0;
        $bk->label_compte       = $label_entry;
        $bk->label_operation    = $label_entry;
        $bk->numero_compte      = '2102';
        $bk->label_compte       = 'VAT Control Account';
        $bk->debit              = $net;
        $bk->credit             = 0;
        $bk->montant            = $net;
        $bk->sens               = 'D';
        $bk->code_journal       = 'JV-TX';
        $bk->journal_label      = 'Tax Journal';
        $bk->entity             = $conf->entity;
        $res1 = $bk->create($user);

        $bk->rowid = 0;
        $bk->numero_compte = '2100';
        $bk->label_compte  = 'VAT Payable – Output 7.5%';
        $bk->debit         = 0;
        $bk->credit        = $net;
        $bk->sens          = 'C';
        $res2 = $bk->create($user);

        if ($res1 > 0 && $res2 > 0) {
            $db->commit();
            $posted = true;
            // Write immutable audit entry
            $audit = new B3eqAuditLogger($db);
            $audit->log([
                'action'      => B3eqAuditLogger::ACTION_VAT_POSTED,
                'object_type' => 'VAT_RETURN',
                'object_id'   => 0,
                'amount'      => $net,
                'account_dr'  => '2102',
                'account_cr'  => '2100',
                'journal_code'=> 'JV-TX',
                'description' => $label_entry . ' — entity ' . (int)$conf->entity,
            ]);
            setEventMessages('VAT journal entry posted: ' . $label_entry, null, 'mesgs');
        } else {
            $db->rollback();
            $post_error = 'Failed to post journal entry. Check accounting permissions.';
            setEventMessages($post_error, null, 'errors');
        }
    } else {
        $db->rollback();
        setEventMessages('No net VAT payable — journal entry not required.', null, 'warnings');
    }
}

llxHeader('', 'VAT Return — b3Ɛq NG', '', '', '', '', []);
b3eq_inject_css();
?>
<div class="b3eqng-page">

  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">VAT Return Workbench</div>
      <div class="b3eqng-header-sub">VATA · 7.5% Standard Rate · Monthly Filing by 21st · NRS/FIRS</div>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;">
      <span class="b3eqng-tag">VATA</span>
      <span class="b3eqng-tag-cyan">NTA 2025</span>
    </div>
  </div>

  <div style="padding:24px 28px;">
    <?php dol_htmloutput_events(); ?>

    <!-- Period selector -->
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;margin-bottom:24px;flex-wrap:wrap;">
      <div>
        <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Month</div>
        <select name="period_month" class="b3eqng-input" style="max-width:160px;">
          <?php for ($m=1;$m<=12;$m++): ?>
            <option value="<?php echo $m; ?>" <?php echo $period_month==$m?'selected':''; ?>>
              <?php echo date('F', mktime(0,0,0,$m,1)); ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div>
        <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Year</div>
        <select name="period_year" class="b3eqng-input" style="max-width:110px;">
          <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?php echo $y; ?>" <?php echo $period_year==$y?'selected':''; ?>><?php echo $y; ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="b3eqng-btn">Load Period</button>
    </form>

    <div class="b3eqng-grid-2" style="gap:28px;">

      <!-- Summary -->
      <div>
        <div class="b3eqng-card b3eqng-card-accent" style="margin-bottom:16px;">
          <h3 style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin:0 0 4px;">
            VAT Return — <?php echo dol_escape_htmltag($period_label); ?>
          </h3>
          <div style="font-size:11px;color:var(--b3eq-muted);margin-bottom:20px;">
            Period: <?php echo $period_start; ?> → <?php echo $period_end; ?> &nbsp;·&nbsp;
            <span style="color:<?php echo strtotime($filing_deadline) < time() ? 'var(--b3eq-red)' : 'var(--b3eq-text)'; ?>">
              Filing deadline: <?php echo $filing_deadline; ?>
            </span>
          </div>

          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Sales Invoices (count)</span>
            <span class="b3eqng-calc-value"><?php echo count($sales_invoices); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Total Sales (excl. VAT)</span>
            <span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($total_sales_ht); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Output VAT Collected (7.5%)</span>
            <span class="b3eqng-calc-value red"><?php echo B3eqNG::formatNGN($vat_result['output_vat']); ?></span>
          </div>

          <div style="border-top:1px solid var(--b3eq-border);margin:12px 0;"></div>

          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Purchase Invoices (count)</span>
            <span class="b3eqng-calc-value"><?php echo count($purchase_invoices); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Total Purchases (excl. VAT)</span>
            <span class="b3eqng-calc-value"><?php echo B3eqNG::formatNGN($total_purch_ht); ?></span>
          </div>
          <div class="b3eqng-calc-row">
            <span class="b3eqng-calc-label">Input VAT Recoverable</span>
            <span class="b3eqng-calc-value green"><?php echo B3eqNG::formatNGN($vat_result['input_vat']); ?></span>
          </div>

          <div style="border-top:2px solid var(--b3eq-accent);margin:14px 0;"></div>

          <?php if ($vat_result['payable'] > 0): ?>
            <div class="b3eqng-calc-row" style="border-bottom:none;">
              <span class="b3eqng-calc-label" style="font-weight:800;color:var(--b3eq-text);font-size:14px;">VAT PAYABLE TO NRS</span>
              <span class="b3eqng-calc-value red" style="font-size:22px;"><?php echo B3eqNG::formatNGN($vat_result['payable']); ?></span>
            </div>
          <?php elseif ($vat_result['refundable'] > 0): ?>
            <div class="b3eqng-calc-row" style="border-bottom:none;">
              <span class="b3eqng-calc-label" style="font-weight:800;color:var(--b3eq-text);font-size:14px;">VAT REFUND / CREDIT</span>
              <span class="b3eqng-calc-value green" style="font-size:22px;"><?php echo B3eqNG::formatNGN($vat_result['refundable']); ?></span>
            </div>
          <?php else: ?>
            <div class="b3eqng-calc-row" style="border-bottom:none;">
              <span class="b3eqng-calc-label" style="font-weight:800;color:var(--b3eq-text);font-size:14px;">NIL RETURN</span>
              <span class="b3eqng-calc-value" style="font-size:22px;">₦0.00</span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Journal entry display -->
        <div class="b3eqng-card" style="margin-bottom:16px;">
          <h4 style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--b3eq-muted);margin:0 0 12px;text-transform:uppercase;letter-spacing:.06em;">VAT Payment Journal Entry (JV-TX)</h4>
          <div class="b3eqng-journal-entry">
            <div style="display:flex;gap:6px;align-items:center;margin-bottom:5px;">
              <span class="b3eqng-journal-dr">Dr</span>
              <span style="color:var(--b3eq-cyan);width:52px;font-size:11px;">2100</span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">VAT Payable – Output 7.5%</span>
              <span style="color:var(--b3eq-text);"><?php echo B3eqNG::formatNGN($vat_result['payable']); ?></span>
            </div>
            <div style="display:flex;gap:6px;align-items:center;padding-left:20px;">
              <span class="b3eqng-journal-cr">Cr</span>
              <span style="color:var(--b3eq-cyan);width:52px;font-size:11px;">1002</span>
              <span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Bank Account (CBN/Commercial)</span>
              <span style="color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN($vat_result['payable']); ?></span>
            </div>
          </div>
        </div>

        <?php if ($user->rights->b3eqng->post && $vat_result['payable'] > 0 && !$posted): ?>
        <form method="POST">
          <input type="hidden" name="action" value="post_journal_entry">
          <input type="hidden" name="period_year"  value="<?php echo (int)$period_year; ?>">
          <input type="hidden" name="period_month" value="<?php echo (int)$period_month; ?>">
          <button type="submit" class="b3eqng-btn" style="width:100%;"
                  onclick="return confirm('Post VAT journal entry for <?php echo dol_escape_htmltag($period_label); ?>?');">
            📝 Post VAT Journal Entry
          </button>
        </form>
        <?php endif; ?>
      </div>

      <!-- Invoice lists -->
      <div>
        <!-- Sales -->
        <div class="b3eqng-card" style="margin-bottom:14px;">
          <h4 style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--b3eq-green);margin:0 0 12px;text-transform:uppercase;letter-spacing:.06em;">
            Sales Invoices (<?php echo count($sales_invoices); ?>)
          </h4>
          <?php if (empty($sales_invoices)): ?>
            <div style="color:var(--b3eq-muted);font-size:12px;text-align:center;padding:16px;">No validated sales invoices for this period.</div>
          <?php else: ?>
            <div style="overflow-x:auto;max-height:200px;overflow-y:auto;">
              <table class="b3eqng-table" style="font-size:11.5px;">
                <thead><tr><th>Ref</th><th>Date</th><th>HT (excl.VAT)</th><th>VAT</th><th>TTC</th></tr></thead>
                <tbody>
                  <?php foreach ($sales_invoices as $inv): ?>
                  <tr>
                    <td><span class="b3eqng-account-code" style="font-size:11px;"><?php echo dol_escape_htmltag($inv->ref); ?></span></td>
                    <td style="color:var(--b3eq-muted);"><?php echo dol_print_date($inv->date_validation,'day'); ?></td>
                    <td style="text-align:right;"><?php echo B3eqNG::formatNGN((float)$inv->total_ht); ?></td>
                    <td style="text-align:right;color:var(--b3eq-red);"><?php echo B3eqNG::formatNGN((float)$inv->total_tva); ?></td>
                    <td style="text-align:right;font-weight:600;"><?php echo B3eqNG::formatNGN((float)$inv->total_ttc); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- Purchases -->
        <div class="b3eqng-card">
          <h4 style="font-family:'Syne',sans-serif;font-weight:800;font-size:13px;color:var(--b3eq-cyan);margin:0 0 12px;text-transform:uppercase;letter-spacing:.06em;">
            Purchase Invoices (<?php echo count($purchase_invoices); ?>)
          </h4>
          <?php if (empty($purchase_invoices)): ?>
            <div style="color:var(--b3eq-muted);font-size:12px;text-align:center;padding:16px;">No validated purchase invoices for this period.</div>
          <?php else: ?>
            <div style="overflow-x:auto;max-height:200px;overflow-y:auto;">
              <table class="b3eqng-table" style="font-size:11.5px;">
                <thead><tr><th>Ref</th><th>Supplier</th><th>HT</th><th>Input VAT</th></tr></thead>
                <tbody>
                  <?php foreach ($purchase_invoices as $inv): ?>
                  <tr>
                    <td><span class="b3eqng-account-code" style="font-size:11px;"><?php echo dol_escape_htmltag($inv->ref); ?></span></td>
                    <td style="color:var(--b3eq-muted);max-width:120px;overflow:hidden;text-overflow:ellipsis;"><?php echo dol_escape_htmltag($inv->supplier_name); ?></td>
                    <td style="text-align:right;"><?php echo B3eqNG::formatNGN((float)$inv->total_ht); ?></td>
                    <td style="text-align:right;color:var(--b3eq-green);"><?php echo B3eqNG::formatNGN((float)$inv->total_tva); ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /grid -->

    <div class="b3eqng-infobox" style="margin-top:20px;">
      <div class="b3eqng-infobox-title">ℹ Filing Notes</div>
      <p>File VAT return on <strong style="color:var(--b3eq-text);">TaxPro Max (taxpromax.firs.gov.ng)</strong> by the 21st of every month.
         Late filing: ₦10,000 first month + ₦20,000 each subsequent month.
         Under NTA 2025, all VAT-registered businesses must adopt e-invoicing — ensure all invoices carry a FIRS IRN.</p>
    </div>

  </div>
</div>
<?php llxFooter(); $db->close(); ?>
