<?php
declare(strict_types=1);

function fail(string $message): void
{
    fwrite(STDERR, "FAIL: $message\n");
    exit(1);
}

function pass(string $message): void
{
    fwrite(STDOUT, "PASS: $message\n");
}

function assert_equal($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        if (is_numeric($expected) && is_numeric($actual) && abs((float)$expected - (float)$actual) < 0.00001) {
            pass($message);
            return;
        }
        fail(sprintf("%s — expected %s, got %s", $message, var_export($expected, true), var_export($actual, true)));
    }
    pass($message);
}

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
        fail($message);
    }
    pass($message);
}

$base = dirname(__DIR__);
$dolibarrRoot = realpath($base . '/../../');
if ($dolibarrRoot === false) {
    fail('Unable to determine Dolibarr root for DOL_DOCUMENT_ROOT.');
}
define('DOL_VERSION', '24.0');
define('DOL_DOCUMENT_ROOT', $dolibarrRoot);

$seedInstallerFile = $base . '/lib/SeedInstaller.php';
assert_true(file_exists($seedInstallerFile), 'SeedInstaller class file exists');
require_once $seedInstallerFile;
assert_true(class_exists('B3eqngSeedInstaller'), 'B3eqngSeedInstaller is loadable');

$composerFile = $base . '/composer.json';
assert_true(file_exists($composerFile), 'composer.json exists');
$composerJson = json_decode(file_get_contents($composerFile), true);
if (!is_array($composerJson) || !isset($composerJson['autoload'])) {
    fail('composer.json must contain an autoload section');
}
pass('composer.json is valid JSON with autoload');

$createSql = $base . '/sql/llx_b3eqng_create.sql';
$seedSql   = $base . '/sql/llx_b3eqng_seed.sql';
assert_true(file_exists($createSql), 'Create SQL file exists');
assert_true(file_exists($seedSql), 'Seed SQL file exists');

require_once $base . '/class/b3eqng.class.php';
assert_true(class_exists('B3eqNG'), 'B3eqNG class is loadable');
$b3 = new B3eqNG(null);

$wht = $b3->calculateWHT(1000, 'NG-WHT-PRF', true, false);
assert_equal(1000.0, $wht['gross'], 'WHT gross amount');
assert_equal(0.1, $wht['rate'], 'WHT rate default');
assert_equal(100.0, $wht['wht_amount'], 'WHT amount for 10%');

$vat = $b3->calculateVAT([['amount' => 1000]], [['amount' => 200, 'vat_recoverable' => true]]);
assert_equal(75.0, $vat['output_vat'], 'VAT output calculation');
assert_equal(15.0, $vat['input_vat'], 'VAT input calculation');
assert_equal(60.0, $vat['net_vat'], 'VAT net calculation');

$citMedium = $b3->calculateCIT(600000000, 150000000);
assert_equal('MEDIUM', $citMedium['size'], 'CIT medium company size');
assert_equal(0.2, $citMedium['cit_rate'], 'CIT medium rate');
assert_equal(30000000.0, $citMedium['cit_amount'], 'CIT medium amount');

$citLarge = $b3->calculateCIT(1500000000, 150000000);
assert_equal('LARGE', $citLarge['size'], 'CIT large company size');
assert_equal(0.3, $citLarge['cit_rate'], 'CIT large rate');
assert_equal(45000000.0, $citLarge['cit_amount'], 'CIT large amount');

$paye = $b3->calculatePAYE(2000000);
assert_equal(122000.0, $paye['paye_annual'], 'PAYE annual for 2M gross');
assert_equal(10166.67, round($paye['paye_monthly'], 2), 'PAYE monthly for 2M gross');

$levies = $b3->calculatePayrollLevies(100000, 20000, 10000);
assert_equal(10400.0, $levies['pension_employee'], 'Pension employee');
assert_equal(13000.0, $levies['pension_employer'], 'Pension employer');
assert_equal(2500.0, $levies['nhf'], 'NHF calculation');

pass('Core calculator logic checks passed');

fwrite(STDOUT, "\nAll tests passed successfully.\n");
