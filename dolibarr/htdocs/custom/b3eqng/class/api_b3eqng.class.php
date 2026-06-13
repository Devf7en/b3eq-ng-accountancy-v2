<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — REST API Class
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/class/api_b3eqng.class.php
 *
 * Exposes b3eqng endpoints on Dolibarr's REST API:
 *   GET  /api/index.php/b3eqng/coa
 *   GET  /api/index.php/b3eqng/taxrates
 *   GET  /api/index.php/b3eqng/compliance/{period}
 *   POST /api/index.php/b3eqng/wht/calculate
 *   POST /api/index.php/b3eqng/vat/calculate
 *   POST /api/index.php/b3eqng/cit/calculate
 *   POST /api/index.php/b3eqng/paye/calculate
 *
 * Uses Dolibarr's Luracast Restler framework.
 * ============================================================================
 */

if (!defined('DOL_VERSION')) { die('Forbidden'); }

require_once DOL_DOCUMENT_ROOT . '/custom/b3eqng/class/b3eqng.class.php';

/**
 * @package DolibarrModules\b3eqng
 */
class B3eqNGApi extends DolibarrApi
{
    /** @var B3eqNG */
    private $b3;

    public function __construct()
    {
        global $db;
        parent::__construct($db, 'b3eqng');
        $this->b3 = new B3eqNG($db);
    }

    // =========================================================================
    // GET /b3eqng/coa
    // Returns the full Nigerian Chart of Accounts for this entity.
    // =========================================================================

    /**
     * Get Nigerian Chart of Accounts
     *
     * @param  string $type  Optional filter: ASSET, LIABILITY, EQUITY, INCOME, EXPENSE
     * @return array
     *
     * @url GET /coa
     */
    public function getCOA(string $type = ''): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->read) {
            throw new RestException(403, 'Permission denied');
        }
        return $this->b3->getChartOfAccounts($this->getEntity('b3eqng'), strtoupper($type));
    }

    // =========================================================================
    // GET /b3eqng/taxrates
    // Returns all NTA 2025 tax rates as structured data.
    // =========================================================================

    /**
     * Get all Nigeria tax rates (NTA 2025)
     *
     * @return array
     *
     * @url GET /taxrates
     */
    public function getTaxRates(): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->read) {
            throw new RestException(403, 'Permission denied');
        }

        return [
            'meta'       => ['version' => '1.0.0', 'legislation' => 'NTA 2025', 'effective' => '2025-01-01'],
            'vat_standard'   => B3eqNG::VAT_STANDARD,
            'cit' => [
                'small'  => B3eqNG::CIT_SMALL,
                'medium' => B3eqNG::CIT_MEDIUM,
                'large'  => B3eqNG::CIT_LARGE,
                'small_threshold'  => B3eqNG::CIT_SMALL_THRESHOLD,
                'medium_threshold' => B3eqNG::CIT_MEDIUM_THRESHOLD,
            ],
            'development_levy' => B3eqNG::DEV_LEVY,
            'minimum_tax'      => B3eqNG::MIN_TAX,
            'cgt' => [
                'company'    => B3eqNG::CGT_COMPANY,
                'individual' => B3eqNG::CGT_INDIVIDUAL,
            ],
            'wht_rates'     => B3eqNG::WHT_RATES,
            'pension' => [
                'employer' => B3eqNG::PENSION_EMPLOYER,
                'employee' => B3eqNG::PENSION_EMPLOYEE,
            ],
            'nhf_rate'   => B3eqNG::NHF_RATE,
            'nsitf_rate' => B3eqNG::NSITF_RATE,
            'itf_rate'   => B3eqNG::ITF_RATE,
            'nitda_rate' => B3eqNG::NITDA_RATE,
            'paye_bands' => B3eqNG::PAYE_BANDS,
        ];
    }

    // =========================================================================
    // GET /b3eqng/compliance/{period}
    // Returns compliance status for a given YYYY-MM period.
    // =========================================================================

    /**
     * Get compliance status for a period
     *
     * @param  string $period  Format: YYYY-MM (e.g. 2026-06)
     * @return array
     *
     * @url GET /compliance/{period}
     */
    public function getComplianceStatus(string $period): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->read) {
            throw new RestException(403, 'Permission denied');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            throw new RestException(400, 'period must be in YYYY-MM format');
        }

        return $this->b3->getComplianceStatus($period, $this->getEntity('b3eqng'));
    }

    // =========================================================================
    // POST /b3eqng/wht/calculate
    // Calculate WHT on a gross payment.
    // =========================================================================

    /**
     * Calculate Withholding Tax
     *
     * @param  array $body  { gross_amount, wht_code, has_tin, small_co_exempt }
     * @return array
     *
     * @url POST /wht/calculate
     */
    public function calculateWHT(array $body): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->calculate) {
            throw new RestException(403, 'Permission denied');
        }

        $gross    = (float)($body['gross_amount'] ?? 0);
        $code     = (string)($body['wht_code'] ?? 'NG-WHT-PRF');
        $has_tin  = (bool)($body['has_tin'] ?? true);
        $small_co = (bool)($body['small_co_exempt'] ?? false);

        if ($gross <= 0) {
            throw new RestException(400, 'gross_amount must be a positive number');
        }

        if (!isset(B3eqNG::WHT_RATES[$code])) {
            throw new RestException(400, 'Invalid wht_code. Valid codes: ' . implode(', ', array_keys(B3eqNG::WHT_RATES)));
        }

        return $this->b3->calculateWHT($gross, $code, $has_tin, $small_co);
    }

    // =========================================================================
    // POST /b3eqng/vat/calculate
    // Calculate VAT return from arrays of sales and purchase lines.
    // =========================================================================

    /**
     * Calculate VAT return
     *
     * @param  array $body  { sales_lines: [{amount, type}], purchase_lines: [{amount, vat_recoverable}] }
     * @return array
     *
     * @url POST /vat/calculate
     */
    public function calculateVAT(array $body): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->calculate) {
            throw new RestException(403, 'Permission denied');
        }

        $sales     = $body['sales_lines']    ?? [];
        $purchases = $body['purchase_lines'] ?? [];

        if (!is_array($sales) || !is_array($purchases)) {
            throw new RestException(400, 'sales_lines and purchase_lines must be arrays');
        }

        return $this->b3->calculateVAT($sales, $purchases);
    }

    // =========================================================================
    // POST /b3eqng/cit/calculate
    // Calculate Companies Income Tax and Development Levy.
    // =========================================================================

    /**
     * Calculate CIT and Development Levy
     *
     * @param  array $body  { annual_turnover, assessable_profit }
     * @return array
     *
     * @url POST /cit/calculate
     */
    public function calculateCIT(array $body): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->calculate) {
            throw new RestException(403, 'Permission denied');
        }

        $turnover = (float)($body['annual_turnover']   ?? 0);
        $profit   = (float)($body['assessable_profit'] ?? 0);

        if ($turnover <= 0) {
            throw new RestException(400, 'annual_turnover must be positive');
        }

        return $this->b3->calculateCIT($turnover, $profit);
    }

    // =========================================================================
    // POST /b3eqng/paye/calculate
    // Calculate annual PAYE for an employee.
    // =========================================================================

    /**
     * Calculate PAYE (Personal Income Tax)
     *
     * @param  array $body  { gross_annual, additional_relief? }
     * @return array
     *
     * @url POST /paye/calculate
     */
    public function calculatePAYE(array $body): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->calculate) {
            throw new RestException(403, 'Permission denied');
        }

        $gross   = (float)($body['gross_annual']       ?? 0);
        $relief  = (float)($body['additional_relief']  ?? 0);

        if ($gross <= 0) {
            throw new RestException(400, 'gross_annual must be positive');
        }

        return $this->b3->calculatePAYE($gross, $relief);
    }

    // =========================================================================
    // POST /b3eqng/payroll/levies
    // Calculate statutory payroll levies for one employee.
    // =========================================================================

    /**
     * Calculate statutory payroll levies (Pension, NHF, NSITF)
     *
     * @param  array $body  { basic_monthly, housing_monthly, transport_monthly }
     * @return array
     *
     * @url POST /payroll/levies
     */
    public function calculatePayrollLevies(array $body): array
    {
        if (!DolibarrApiAccess::$user->rights->b3eqng->calculate) {
            throw new RestException(403, 'Permission denied');
        }

        $basic     = (float)($body['basic_monthly']     ?? 0);
        $housing   = (float)($body['housing_monthly']   ?? 0);
        $transport = (float)($body['transport_monthly'] ?? 0);

        if ($basic <= 0) {
            throw new RestException(400, 'basic_monthly must be positive');
        }

        return $this->b3->calculatePayrollLevies($basic, $housing, $transport);
    }

    // =========================================================================
    // GET /b3eqng/version
    // Module version and health check.
    // =========================================================================

    /**
     * Get module version and status
     *
     * @return array
     *
     * @url GET /version
     */
    public function getVersion(): array
    {
        global $conf;
        return [
            'module'     => 'b3eqng',
            'name'       => 'b3Ɛq Nigerian Accountancy',
            'version'    => getDolGlobalString('B3EQNG_VERSION', '1.0.0'),
            'legislation'=> 'Nigeria Tax Act 2025',
            'standard'   => 'IFRS for SMEs',
            'entity'     => $conf->entity,
            'active'     => true,
            'tag'        => 'b3Ɛq Nigerian Accountancy',
            'built_by'   => 'Foundations Aesthetics Resource / DCRI-PPS SmartAPPS (f7en)',
        ];
    }
}
