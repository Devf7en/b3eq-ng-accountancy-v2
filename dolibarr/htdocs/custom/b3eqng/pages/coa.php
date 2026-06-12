<?php
require_once '../class/b3eqng_init.php';

/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Chart of Accounts
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/pages/coa.php
 * ============================================================================
 */

$langs->loadLangs(['b3eqng@b3eqng', 'comptabilite']);

if (empty($user->rights->b3eqng->read)) accessforbidden();

llxHeader('', 'b3Ɛq Chart of Accounts (Nigeria)');
b3eq_inject_css();

$b3 = new B3eqNG($db);
$accounts = $b3->getChartOfAccounts($conf->entity);
$version = b3eq_conf('B3EQNG_VERSION', '2.0.0');
?>

<div class="b3eqng-page">
  <div class="b3eqng-header">
    <div class="b3eqng-logo">b3</div>
    <div>
      <div class="b3eqng-header-title" style="font-family:'Syne',sans-serif; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Nigerian Chart of Accounts</div>
      <div class="b3eqng-header-sub">IFRS for SMEs · NTA 2025 · NG-IFRS-SME</div>
    </div>
    <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
        <span class="b3eqng-tag-gold"><?php echo count($accounts); ?> Accounts</span>
    </div>
  </div>

  <div style="padding:24px 28px;">
    <div class="b3eqng-card">
      <table class="tagtable centpercent">
        <thead>
          <tr class="liste_titre" style="border-bottom: 2px solid var(--b3eq-border);">
            <th style="font-family:'Space Mono',monospace; color:var(--b3eq-cyan);">Code</th>
            <th style="font-family:'Syne',sans-serif; text-transform:uppercase;">Label</th>
            <th>Type</th>
            <th>Subtype</th>
            <th class="right">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($accounts)): ?>
            <tr><td colspan="5" class="center opacitymedium">No accounts found. Please ensure the module was seeded correctly in Settings.</td></tr>
          <?php else: ?>
            <?php foreach ($accounts as $acc): ?>
              <tr class="oddeven">
                <td style="font-family:'Space Mono',monospace; font-weight:700; color:var(--b3eq-cyan);">
                  <?php echo dol_escape_htmltag($acc->numero_compte); ?>
                </td>
                <td style="font-weight:600; color:var(--b3eq-text);">
                  <?php echo dol_escape_htmltag($acc->label_compte); ?>
                </td>
                <td class="opacitymedium"><?php echo dol_escape_htmltag($acc->pcg_type); ?></td>
                <td class="opacitymedium"><?php echo dol_escape_htmltag($acc->pcg_subtype); ?></td>
                <td class="right">
                  <?php if ($acc->active): ?>
                    <span class="b3eqng-tag" style="background:var(--b3eq-green); color:#000;">Active</span>
                  <?php else: ?>
                    <span class="b3eqng-tag" style="background:var(--b3eq-red); color:#fff;">Inactive</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
llxFooter();
$db->close();
?>
