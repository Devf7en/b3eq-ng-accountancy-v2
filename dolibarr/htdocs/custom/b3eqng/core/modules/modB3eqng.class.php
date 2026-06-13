<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Module Descriptor v2.0.0
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/core/modules/modB3eqng.class.php
 *
 * FIXES v1 → v2:
 *   - picto corrected to custom naira icon
 *   - removed hard modAccounting dependency
 *   - menu URLs corrected: /custom/b3eqng/pages/...
 *   - CSS path corrected: /custom/b3eqng/css/b3eqng.css
 *   - _load_tables now points ONLY to create.sql
 *   - init() delegates seed to install_data.php, called once
 *   - rights array structure corrected
 *   - pathing aligned for Dolibarr 22.0.1
 * ============================================================================
 */

if (!defined('DOL_VERSION')) { die('Forbidden'); }

require_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

class modB3eqng extends DolibarrModules
{
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        // ── Identity ─────────────────────────────────────────────────────────
        $this->numero          = 500701;
        $this->rights_class    = 'b3eqng';
        $this->family          = 'financial';
        $this->family_position = 500;
        $this->module_position = 500;
        $this->name            = 'b3eqng';
        $this->description     = 'b3Ɛq Nigerian Accountancy — NTA 2025, IFRS for SMEs, '
                               . 'WHT, VAT, PAYE, CIT, Compliance Calendar, Asset Depreciation, '
                               . 'FX Revaluation, Audit Trail.';
        $this->descriptionlong = $this->description;
        $this->editor_name     = 'Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)';
        $this->editor_url      = 'https://f7en.net';
        $this->version         = '2.0.0';
        $this->const_name      = 'MAIN_MODULE_B3EQNG';

        // ALIGNMENT: Using custom currency-specific icon as per NTA 2025 branding
        $this->picto           = 'naira@b3eqng';

        $this->depends         = [];
        $this->requiredby      = [];
        $this->conflictwith    = [];
        $this->phpmin          = [7, 4];
        $this->need_dolibarr_version = [15, 0];
        $this->langfiles       = ['b3eqng@b3eqng'];

        // ALIGNMENT: Pathing requirement for Dolibarr 22.0.1 (no leading slash)
        $this->module_sql_dir  = 'b3eqng/sql';
        $this->_load_tables    = array('llx_b3eqng_create.sql');

        $this->module_parts = [
            'triggers'      => 1,
            'login'         => 0,
            'substitutions' => 0,
            'menus'         => 0,
            'tpl'           => 0,
            'barcode'       => 0,
            'models'        => 0,
            'theme'         => 0,
            'css'           => ['/custom/b3eqng/css/b3eqng.css'],
            'js'            => [],
            'hooks'         => [],
            'moduleforexternal' => 0,
        ];

        // ── Constants ─────────────────────────────────────────────────────────
        $this->const = [
            ['B3EQNG_VERSION',        'chaine', '2.0.0',  'b3Ɛq NG module version',           0],
            ['B3EQNG_COMPANY_SIZE',   'chaine', 'LARGE',  'SMALL/MEDIUM/LARGE — sets CIT rate',1],
            ['B3EQNG_VAT_REGISTERED', 'chaine', '1',      '1 = VAT registered with FIRS',      1],
            ['B3EQNG_TIN',            'chaine', '',        'Company FIRS TIN',                  1],
            ['B3EQNG_VAT_NUMBER',     'chaine', '',        'Company VAT Registration Number',   1],
            ['B3EQNG_STATE',          'chaine', 'LAGOS',  'State of registration (SIRS routing)',1],
            ['B3EQNG_SEEDED',         'chaine', '0',      '1 once seed data inserted',          0],
        ];

        // ── Menus ─────────────────────────────────────────────────────────────
        $r = 0;

        // Parent menu entry
        $this->menu[$r++] = [
            'fk_menu'  => 'fk_mainmenu=accountancy',
            'type'     => 'left',
            'titre'    => 'NG Accountancy',
            'mainmenu' => 'accountancy',
            'leftmenu' => 'b3eqng',
            'url'      => '/custom/b3eqng/pages/coa.php',
            'langs'    => 'b3eqng@b3eqng',
            'position' => 1000,
            'enabled'  => 'isModEnabled("B3eqng")',
            'perms'    => '1',
            'target'   => '',
            'user'     => 0,
        ];

        $sub_pages = [
            ['b3eqng_coa',         'Chart of Accounts',   '/custom/b3eqng/pages/coa.php',         1001],
            ['b3eqng_taxes',       'Tax Rates & Codes',   '/custom/b3eqng/pages/taxes.php',        1002],
            ['b3eqng_wht',         'WHT Calculator',      '/custom/b3eqng/pages/wht_calc.php',     1003],
            ['b3eqng_vat',         'VAT Return',          '/custom/b3eqng/pages/vat_return.php',   1004],
            ['b3eqng_payroll',     'Payroll & PAYE',      '/custom/b3eqng/pages/payroll.php',      1005],
            ['b3eqng_cit',         'CIT Estimator',       '/custom/b3eqng/pages/cit_estimator.php',1006],
            ['b3eqng_assets',      'Fixed Assets',        '/custom/b3eqng/pages/assets.php',       1007],
            ['b3eqng_fx',          'FX Revaluation',      '/custom/b3eqng/pages/fx_revaluation.php',1008],
            ['b3eqng_compliance',  'Compliance Calendar', '/custom/b3eqng/pages/compliance.php',   1009],
            ['b3eqng_audit',       'Audit Trail',         '/custom/b3eqng/pages/audit_trail.php',  1010],
            ['b3eqng_setup',       'Settings',            '/custom/b3eqng/admin/setup.php',        1099],
        ];

        foreach ($sub_pages as [$lm, $title, $url, $pos]) {
            $this->menu[$r++] = [
                'fk_menu'  => 'fk_leftmenu=b3eqng',
                'type'     => 'left',
                'titre'    => $title,
                'mainmenu' => 'accountancy',
                'leftmenu' => $lm,
                'url'      => $url,
                'langs'    => 'b3eqng@b3eqng',
                'position' => $pos,
                'enabled'  => 'isModEnabled("B3eqng")',
                'perms'    => $lm === 'b3eqng_setup' ? '$user->admin' : '1',
                'user'     => 0,
            ];
        }

        // ── Rights ─────────────────────────────────────────────────────────────
        $r = 0;
        $this->rights[$r][0] = $this->numero + 1;
        $this->rights[$r][1] = 'Read Nigerian accounting data';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'read';
        $r++;

        $this->rights[$r][0] = $this->numero + 2;
        $this->rights[$r][1] = 'Calculate WHT, VAT, PAYE, CIT';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'calculate';
        $r++;

        $this->rights[$r][0] = $this->numero + 3;
        $this->rights[$r][1] = 'Post journal entries (VAT, WHT, payroll, depreciation)';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'post';
        $r++;

        $this->rights[$r][0] = $this->numero + 4;
        $this->rights[$r][1] = 'Manage fixed assets (add, depreciate, dispose)';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'assets';
        $r++;

        $this->rights[$r][0] = $this->numero + 9;
        $this->rights[$r][1] = 'Administer b3Ɛq NG Accountancy module settings';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'admin';
    }

    // ── Activation ────────────────────────────────────────────────────────────
    public function init($options = '')
    {
        global $conf, $db;

        // ALIGNMENT: Pathing requirement for Dolibarr 22.0.1
        $this->module_sql_dir = 'b3eqng/sql';

        // Fix: Reset error state to ensure toggle stays "ON"
        $this->error = '';

        // Step 1: Create custom tables
        $result = $this->_load_tables();
        if ($result < 0) {
            dol_syslog("B3eqng::init Failed to load tables", LOG_ERR);
            return -1;
        }

        // Step 2: Seed Nigerian accounting data (run once only)
        $already_seeded = (isset($conf->global->B3EQNG_SEEDED) && $conf->global->B3EQNG_SEEDED == '1');

        if (!$already_seeded) {
            $install_file = dol_buildpath('/b3eqng/scripts/install_data.php', 0);
            if (file_exists($install_file)) {
                include_once $install_file;
                if (function_exists('b3eqng_install_data')) {
                    $seed_result = b3eqng_install_data($db, $conf->entity);
                    if ($seed_result >= 0) {
                        dolibarr_set_const($db, 'B3EQNG_SEEDED', '1', 'chaine', 0, '', $conf->entity);
                    }
                }
            }
        }

        // ALIGNMENT: Run standard init and explicitly return 1
        $res = $this->_init([], $options);
        if ($res < 0) return $res;

        // Clear any warnings that Dolibarr might treat as activation failures
        $this->error = '';
        return 1;
    }

    // ── Deactivation ─────────────────────────────────────────────────────────
    public function remove($options = '')
    {
        return $this->_remove([], $options);
    }
}
