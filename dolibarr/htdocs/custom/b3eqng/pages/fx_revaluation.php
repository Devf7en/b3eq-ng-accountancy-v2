<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — FX Revaluation Engine
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/fx_revaluation.php
 * ============================================================================
 */

require_once dirname(__DIR__) . '/class/b3eqng_init.php';
if (empty($user->rights->b3eqng->read) && empty($user->admin)) accessforbidden();

$action      = GETPOST('action', 'none');
$reval_date  = GETPOST('reval_date', 'none') ?: date('Y-m-d');

// ── Handle manual rate save ───────────────────────────────────────────────────
if ($action === 'save_rate') {
    $ccy  = strtoupper(GETPOST('currency_code', 'aZ09'));
    $rate = (float) GETPOST('rate_to_ngn', 'none');
    $dt   = GETPOST('rate_date', 'none');

    if ($ccy && $rate > 0 && $dt) {
        $sql = "INSERT INTO llx_b3eqng_fx_rates
                (entity, currency_code, rate_date, rate_to_ngn, source, date_creation)
                VALUES (" . (int)$conf->entity . ", '" . $db->escape($ccy) . "',
                '" . $db->escape($dt) . "', " . (float)$rate . ", 'MANUAL', NOW())
                ON DUPLICATE KEY UPDATE rate_to_ngn=" . (float)$rate . ", source='MANUAL'";
        if ($db->query($sql)) {
            setEventMessages('Rate saved: 1 ' . $ccy . ' = ₦' . number_format($rate, 2), null, 'mesgs');
        } else {
            setEventMessages('Error saving rate: ' . $db->lasterror(), null, 'errors');
        }
    }
}

// ── Load saved rates ──────────────────────────────────────────────────────────
$rates_sql = "SELECT currency_code, rate_to_ngn, rate_date, source
              FROM llx_b3eqng_fx_rates
              WHERE entity=" . (int)$conf->entity . "
              ORDER BY rate_date DESC, currency_code ASC";
$rates_res = $db->query($rates_sql);
$rates = [];
if ($rates_res) { while ($o = $db->fetch_object($rates_res)) { $rates[] = $o; } }

// Latest rate per currency
$latest_rates = [];
foreach ($rates as $r) {
    if (!isset($latest_rates[$r->currency_code])) $latest_rates[$r->currency_code] = $r;
}

// ── Revaluation calculation ───────────────────────────────────────────────────
$reval_result = [];
if ($action === 'run_revaluation' && !empty($latest_rates)) {
    // Fetch open foreign currency invoices from Dolibarr
    $sql_fx = "SELECT f.rowid, f.ref, f.multicurrency_code as currency,
                      f.multicurrency_total_ht as fc_amount,
                      f.multicurrency_tx as original_rate,
                      f.total_ht as ngn_at_invoice,
                      f.date_creation
               FROM llx_facture f
               WHERE f.entity=" . (int)$conf->entity . "
                 AND f.fk_statut = 1
                 AND f.multicurrency_code IS NOT NULL
                 AND f.multicurrency_code != 'NGN'
                 AND f.multicurrency_code != ''
               ORDER BY f.date_creation DESC
               LIMIT 100";
    $fx_res = $db->query($sql_fx);
    $open_fx_invoices = [];
    if ($fx_res) { while ($o = $db->fetch_object($fx_res)) { $open_fx_invoices[] = $o; } }

    foreach ($open_fx_invoices as $inv) {
        $ccy = $inv->currency;
        if (!isset($latest_rates[$ccy])) continue;

        $current_rate   = (float)$latest_rates[$ccy]->rate_to_ngn;
        $original_rate  = (float)$inv->original_rate;
        $fc_amount      = (float)$inv->fc_amount;

        $ngn_original   = round($fc_amount * $original_rate, 2);
        $ngn_current    = round($fc_amount * $current_rate, 2);
        $fx_difference  = round($ngn_current - $ngn_original, 2);

        $reval_result[] = [
            'ref'           => $inv->ref,
            'currency'      => $ccy,
            'fc_amount'     => $fc_amount,
            'original_rate' => $original_rate,
            'current_rate'  => $current_rate,
            'ngn_original'  => $ngn_original,
            'ngn_current'   => $ngn_current,
            'fx_difference' => $fx_difference,
            'type'          => $fx_difference >= 0 ? 'GAIN' : 'LOSS',
        ];
    }

    if (empty($reval_result) && empty($open_fx_invoices)) {
        setEventMessages('No open multi-currency invoices found for revaluation.', null, 'warnings');
    }
}

$total_gain = array_sum(array_filter(array_column($reval_result, 'fx_difference'), fn($v) => $v > 0));
$total_loss = abs(array_sum(array_filter(array_column($reval_result, 'fx_difference'), fn($v) => $v < 0)));
$net_fx     = $total_gain - $total_loss;

llxHeader('', 'FX Revaluation — b3Ɛq NG');
b3eq_inject_css();
?>
<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title">FX Revaluation Engine</div>
      <div class="b3eqng-header-sub">Unrealised Gain/Loss · IAS 21 · CBN Rate Benchmarking · Period-End Adjustments</div>
    </div>
    <div style="margin-left:auto;"><span class="b3eqng-tag">IAS 21</span></div>
  </div>

  <div style="padding:24px 28px;">
    <?php dol_htmloutput_events(); ?>
    <div class="b3eqng-grid-2" style="gap:24px;">

      <!-- Rate management -->
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin-bottom:14px;">Exchange Rates (NGN)</div>

        <!-- Add rate form -->
        <form method="POST" style="margin-bottom:16px;">
          <input type="hidden" name="action" value="save_rate">
          <div class="b3eqng-card b3eqng-card-accent">
            <div style="font-size:12px;color:var(--b3eq-muted);margin-bottom:14px;">Enter CBN or market mid-rate. Updated daily via n8n FX sync workflow.</div>
            <div class="b3eqng-grid-2" style="gap:12px;margin-bottom:12px;">
              <div>
                <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Currency</div>
                <select name="currency_code" class="b3eqng-input">
                  <?php foreach (['USD','GBP','EUR','CHF','CAD','AUD','CNY','ZAR'] as $c): ?>
                    <option value="<?php echo $c; ?>"><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Rate to ₦</div>
                <input type="text" name="rate_to_ngn" placeholder="e.g. 1650.00"
                       class="b3eqng-input" style="font-family:'Space Mono',monospace;">
              </div>
            </div>
            <div style="margin-bottom:14px;">
              <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Rate Date</div>
              <input type="date" name="rate_date" value="<?php echo date('Y-m-d'); ?>" class="b3eqng-input">
            </div>
            <button type="submit" class="b3eqng-btn" style="width:100%;">Save Rate</button>
          </div>
        </form>

        <!-- Current rates table -->
        <?php if (!empty($latest_rates)): ?>
        <div class="b3eqng-card">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Latest Saved Rates</div>
          <table class="b3eqng-table" style="font-size:12px;">
            <thead><tr><th>CCY</th><th>Rate (₦)</th><th>Date</th><th>Source</th></tr></thead>
            <tbody>
              <?php foreach ($latest_rates as $lr): ?>
              <tr>
                <td style="font-weight:700;color:var(--b3eq-cyan);"><?php echo dol_escape_htmltag($lr->currency_code); ?></td>
                <td style="font-family:monospace;color:var(--b3eq-gold);">₦<?php echo number_format((float)$lr->rate_to_ngn, 2); ?></td>
                <td style="color:var(--b3eq-muted);"><?php echo dol_escape_htmltag($lr->rate_date); ?></td>
                <td style="color:var(--b3eq-muted);font-size:10px;"><?php echo dol_escape_htmltag($lr->source); ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Revaluation run -->
      <div>
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:15px;color:var(--b3eq-text);margin-bottom:14px;">Period-End Revaluation</div>

        <form method="POST">
          <input type="hidden" name="action" value="run_revaluation">
          <div class="b3eqng-card" style="margin-bottom:14px;">
            <div style="font-size:12px;color:var(--b3eq-muted);margin-bottom:12px;">Evaluates all open multi-currency invoices against current saved rates and calculates unrealised gain/loss.</div>
            <div style="margin-bottom:14px;">
              <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px;">Revaluation Date</div>
              <input type="date" name="reval_date" value="<?php echo dol_escape_htmltag($reval_date); ?>" class="b3eqng-input">
            </div>
            <button type="submit" class="b3eqng-btn" style="width:100%;">Run Revaluation</button>
          </div>
        </form>

        <?php if (!empty($reval_result)): ?>
        <!-- Summary -->
        <div class="b3eqng-card b3eqng-card-<?php echo $net_fx >= 0 ? 'gold' : 'accent'; ?>" style="margin-bottom:14px;">
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Unrealised FX Gain</span><span class="b3eqng-calc-value green"><?php echo B3eqNG::formatNGN($total_gain); ?></span></div>
          <div class="b3eqng-calc-row"><span class="b3eqng-calc-label">Unrealised FX Loss</span><span class="b3eqng-calc-value red">(<?php echo B3eqNG::formatNGN($total_loss); ?>)</span></div>
          <div style="border-top:1px solid var(--b3eq-border);margin:10px 0;"></div>
          <div class="b3eqng-calc-row" style="border-bottom:none;">
            <span class="b3eqng-calc-label" style="font-weight:800;color:var(--b3eq-text);">Net <?php echo $net_fx >= 0 ? 'Gain' : 'Loss'; ?></span>
            <span class="b3eqng-calc-value <?php echo $net_fx >= 0 ? 'green' : 'red'; ?>" style="font-size:18px;"><?php echo B3eqNG::formatNGN(abs($net_fx)); ?></span>
          </div>
        </div>

        <!-- Adjusting journal entry -->
        <div class="b3eqng-card" style="margin-bottom:14px;">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Adjusting Journal Entry (JV-FX)</div>
          <div class="b3eqng-journal-entry">
            <?php if ($net_fx >= 0): ?>
            <div style="display:flex;gap:6px;margin-bottom:4px;"><span class="b3eqng-journal-dr">Dr</span><span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">1500</span><span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Unrealised FX Gain Receivable</span><span style="color:var(--b3eq-green);font-size:11px;"><?php echo B3eqNG::formatNGN($net_fx); ?></span></div>
            <div style="display:flex;gap:6px;padding-left:18px;"><span class="b3eqng-journal-cr">Cr</span><span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">4106</span><span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Unrealised FX Gain (P&amp;L)</span><span style="color:var(--b3eq-red);font-size:11px;"><?php echo B3eqNG::formatNGN($net_fx); ?></span></div>
            <?php else: ?>
            <div style="display:flex;gap:6px;margin-bottom:4px;"><span class="b3eqng-journal-dr">Dr</span><span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">8004</span><span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Unrealised FX Loss (P&amp;L)</span><span style="color:var(--b3eq-green);font-size:11px;"><?php echo B3eqNG::formatNGN(abs($net_fx)); ?></span></div>
            <div style="display:flex;gap:6px;padding-left:18px;"><span class="b3eqng-journal-cr">Cr</span><span style="color:var(--b3eq-cyan);width:45px;font-size:11px;">2300</span><span style="color:var(--b3eq-muted);flex:1;font-size:11px;">Unrealised FX Loss Payable</span><span style="color:var(--b3eq-red);font-size:11px;"><?php echo B3eqNG::formatNGN(abs($net_fx)); ?></span></div>
            <?php endif; ?>
          </div>
          <div style="margin-top:8px;font-size:11px;color:var(--b3eq-muted);">Reverse this entry at start of next period per IAS 21.</div>
        </div>

        <!-- Invoice detail -->
        <div class="b3eqng-card">
          <div style="font-size:11px;color:var(--b3eq-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">Invoice Breakdown (<?php echo count($reval_result); ?>)</div>
          <div style="overflow-x:auto;">
            <table class="b3eqng-table" style="font-size:11px;">
              <thead><tr><th>Invoice</th><th>CCY</th><th>FC Amt</th><th>At Invoice</th><th>At Today</th><th>Gain/Loss</th></tr></thead>
              <tbody>
                <?php foreach ($reval_result as $rv): ?>
                <tr>
                  <td style="color:var(--b3eq-cyan);"><?php echo dol_escape_htmltag($rv['ref']); ?></td>
                  <td style="font-weight:700;"><?php echo dol_escape_htmltag($rv['currency']); ?></td>
                  <td style="font-family:monospace;"><?php echo number_format($rv['fc_amount'], 2); ?></td>
                  <td style="font-family:monospace;"><?php echo B3eqNG::formatNGN($rv['ngn_original']); ?></td>
                  <td style="font-family:monospace;"><?php echo B3eqNG::formatNGN($rv['ngn_current']); ?></td>
                  <td style="font-family:monospace;font-weight:700;color:<?php echo $rv['fx_difference']>=0?'var(--b3eq-green)':'var(--b3eq-red)'; ?>;">
                    <?php echo ($rv['fx_difference']>=0?'+':'') . B3eqNG::formatNGN($rv['fx_difference']); ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div><!-- /grid -->

    <div class="b3eqng-infobox" style="margin-top:20px;">
      <div class="b3eqng-infobox-title">ℹ FX Rate Automation</div>
      <p>Connect the <strong style="color:var(--b3eq-text);">n8n FX Rate Sync workflow</strong> (<code>fx_rate_sync.json</code>) to automatically pull CBN and market rates daily and populate this table. The revaluation engine then runs automatically at month-end via the compliance workflow.</p>
    </div>
  </div>
</div>
<?php llxFooter(); $db->close(); ?>
