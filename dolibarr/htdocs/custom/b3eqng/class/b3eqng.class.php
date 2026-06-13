<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Core Logic Engine
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/class/b3eqng.class.php
 * ============================================================================
 */

class B3eqNG
{
    /** @var DoliDB */
    public $db;

    // Rates & Thresholds (NTA 2025 / WHT Regs 2024)
    const VAT_STANDARD = 0.075;
    const CIT_SMALL_THRESHOLD = 100000000;
    const CIT_MEDIUM_THRESHOLD = 1000000000;
    const CIT_SMALL = 0;
    const CIT_MEDIUM = 0.20;
    const CIT_LARGE = 0.30;
    const DEV_LEVY = 0.04;
    const MIN_TAX = 0.005;
    const CGT_COMPANY = 0.30;
    const CGT_INDIVIDUAL = 0.10;

    const PENSION_EMPLOYER = 0.10;
    const PENSION_EMPLOYEE = 0.08;
    const NHF_RATE = 0.025;
    const NSITF_RATE = 0.01;
    const ITF_RATE = 0.01;
    const NITDA_RATE = 0.01;

    const WHT_RATES = [
        'NG-WHT-DIV' => 0.10, 'NG-WHT-INT' => 0.10, 'NG-WHT-RNT' => 0.10,
        'NG-WHT-ROY' => 0.10, 'NG-WHT-DIR' => 0.10, 'NG-WHT-PRF' => 0.10,
        'NG-WHT-TEC' => 0.10, 'NG-WHT-COM' => 0.10, 'NG-WHT-SUP' => 0.05,
        'NG-WHT-CON' => 0.025,
    ];

    const PAYE_BANDS = [
        ['limit' => 800000,   'rate' => 0.07],
        ['limit' => 1600000,  'rate' => 0.11],
        ['limit' => 2400000,  'rate' => 0.15],
        ['limit' => 3200000,  'rate' => 0.19],
        ['limit' => 8000000,  'rate' => 0.21],
        ['limit' => 1e15,     'rate' => 0.24], // cap
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Fetch the Nigerian Chart of Accounts for a specific entity.
     * Filtered by the NG-IFRS-SME plan version.
     *
     * @param int    $entity Entity ID
     * @param string $type   Optional account type (ASSET, LIABILITY, etc.)
     * @return array
     */
    public function getChartOfAccounts($entity = 1, $type = '')
    {
        $sql = "SELECT rowid, numero_compte, label_compte, pcg_type, pcg_subtype, active";
        $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account";
        $sql .= " WHERE entity = " . (int)$entity;
        $sql .= " AND fk_pcg_version = 'NG-IFRS-SME'";

        if ($type) {
            $sql .= " AND pcg_type = '" . $this->db->escape($type) . "'";
        }

        $sql .= " ORDER BY numero_compte ASC";

        $res = $this->db->query($sql);
        $accounts = [];
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $accounts[] = $obj;
            }
        }
        return $accounts;
    }

    public function calculateWHT($gross, $code, $hasTIN = true, $isSmallCo = false)
    {
        $rate = self::WHT_RATES[$code] ?? 0.10;
        $exempt = ($isSmallCo && $gross <= 2000000 && $hasTIN);
        $no_tin_penalty = !$hasTIN;
        $effective_rate = $exempt ? 0 : ($no_tin_penalty ? min($rate * 2, 0.20) : $rate);

        $wht_amount = round($gross * $effective_rate, 2);

        return [
            'gross' => $gross,
            'rate' => $rate,
            'effective_rate' => $effective_rate,
            'exempt' => $exempt,
            'no_tin_penalty' => $no_tin_penalty,
            'wht_amount' => $wht_amount,
            'net_payable' => $gross - $wht_amount,
            'note' => $exempt ? 'Exempt under WHT Regs 2024' : ($no_tin_penalty ? 'Double rate applied due to missing TIN' : ''),
            'account_payable' => str_replace('NG-WHT-', '', $code) == 'DIV' ? '2110' : '2113' // simplify for demo
        ];
    }

    public function calculateVAT($sales_lines, $purchase_lines)
    {
        $output = 0;
        foreach ($sales_lines as $line) {
            $output += ($line['amount'] * self::VAT_STANDARD);
        }
        $input = 0;
        foreach ($purchase_lines as $line) {
            if ($line['vat_recoverable']) $input += ($line['amount'] * self::VAT_STANDARD);
        }

        $net = round($output - $input, 2);
        return [
            'output_vat' => $output,
            'input_vat'  => $input,
            'net_vat'    => $net,
            'payable'    => max($net, 0),
            'refundable' => $net < 0 ? abs($net) : 0
        ];
    }

    public function calculateCIT($turnover, $profit)
    {
        $size = 'LARGE';
        if ($turnover <= self::CIT_SMALL_THRESHOLD) $size = 'SMALL';
        elseif ($turnover <= self::CIT_MEDIUM_THRESHOLD) $size = 'MEDIUM';

        $cit_rate = $size === 'SMALL' ? self::CIT_SMALL : ($size === 'MEDIUM' ? self::CIT_MEDIUM : self::CIT_LARGE);
        $cit_amount = round($profit * $cit_rate, 2);
        $min_tax = round($turnover * self::MIN_TAX, 2);

        $min_tax_applies = ($size !== 'SMALL' && $cit_amount < $min_tax);
        $effective_cit = $min_tax_applies ? $min_tax : $cit_amount;
        $dev_levy = $size === 'SMALL' ? 0 : round($profit * self::DEV_LEVY, 2);

        return [
            'size' => $size,
            'cit_rate' => $cit_rate,
            'cit_amount' => $cit_amount,
            'minimum_tax' => $min_tax,
            'min_tax_applies' => $min_tax_applies,
            'effective_cit' => $effective_cit,
            'dev_levy_amount' => $dev_levy,
            'total_tax' => $effective_cit + $dev_levy,
            'effective_rate' => $profit > 0 ? round((($effective_cit + $dev_levy) / $profit) * 100, 1) : 0,
            'note' => $size === 'SMALL' ? 'Small companies are exempt from CIT and Dev Levy.' : ''
        ];
    }

    public function calculatePAYE($annualGross, $additionalRelief = 0)
    {
        // Consolidated Relief Allowance (CRA) - NTA 2025 §30
        $relief_base = max(200000, 0.01 * $annualGross);
        $cra = $relief_base + (0.20 * $annualGross) + $additionalRelief;
        $taxable = max(0, $annualGross - $cra);

        $paye = 0;
        $remaining = $taxable;
        $breakdown = [];

        $prev_limit = 0;
        foreach (self::PAYE_BANDS as $band) {
            $chunk_size = $band['limit'] - $prev_limit;
            $taxable_in_band = min($remaining, $chunk_size);
            if ($taxable_in_band <= 0) break;

            $tax = $taxable_in_band * $band['rate'];
            $paye += $tax;
            $breakdown[] = ['from' => $prev_limit, 'to' => $band['limit'] > 1e12 ? 0 : $band['limit'], 'rate' => $band['rate'], 'tax' => $tax];

            $remaining -= $taxable_in_band;
            $prev_limit = $band['limit'];
        }

        return [
            'gross' => $annualGross,
            'cra' => $cra,
            'taxable_income' => $taxable,
            'band_breakdown' => $breakdown,
            'paye_annual' => round($paye, 2),
            'paye_monthly' => round($paye / 12, 2),
            'effective_rate' => $annualGross > 0 ? round(($paye / $annualGross) * 100, 1) : 0
        ];
    }

    public function calculatePayrollLevies($basic, $housing, $transport)
    {
        $emoluments = $basic + $housing + $transport;
        $gross = $emoluments; // simplified

        return [
            'pension_employer' => round($emoluments * self::PENSION_EMPLOYER, 2),
            'pension_employee' => round($emoluments * self::PENSION_EMPLOYEE, 2),
            'nhf' => round($basic * self::NHF_RATE, 2),
            'nsitf' => round($gross * self::NSITF_RATE, 2)
        ];
    }

    public static function formatNGN($amount)
    {
        return '₦' . number_format($amount, 2);
    }
}
