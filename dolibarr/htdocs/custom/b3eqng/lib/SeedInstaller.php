<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Seed Installer Library
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/lib/SeedInstaller.php
 * ============================================================================
 */

if (!defined('DOL_VERSION')) { die('Forbidden'); }

class B3eqngSeedInstaller
{
    public const DUPLICATE_SQL_ERROR = '1062';
    public const SEED_FILE = '/custom/b3eqng/sql/llx_b3eqng_seed.sql';

    public static function getSeedFilePath(): string
    {
        return DOL_DOCUMENT_ROOT . self::SEED_FILE;
    }

    public static function executeSeed($db, int $entity = 1): int
    {
        $seedFile = self::getSeedFilePath();
        if (!file_exists($seedFile)) {
            dol_syslog('b3eqng install_data: seed file not found at ' . $seedFile, LOG_ERR);
            return -1;
        }

        $sqlRaw = file_get_contents($seedFile);
        if ($sqlRaw === false) {
            dol_syslog('b3eqng install_data: could not read seed file', LOG_ERR);
            return -1;
        }

        $sqlRaw = str_replace(["\r\n", "\r"], "\n", $sqlRaw);
        $statements = self::splitSqlStatements($sqlRaw, $entity);

        $db->begin();
        $errors = 0;

        foreach ($statements as $statement) {
            if ($statement === '') {
                continue;
            }
            $res = $db->query($statement);
            if (!$res) {
                $errors++;
                $err = $db->lasterrno() . ': ' . $db->lasterror();
                if (strpos($err, self::DUPLICATE_SQL_ERROR) === false && strpos($err, 'Duplicate') === false) {
                    dol_syslog('b3eqng install_data SQL error: ' . $err . ' — Statement: ' . substr($statement, 0, 200), LOG_WARNING);
                }
            }
        }

        if ($errors > 5) {
            $db->rollback();
            dol_syslog('b3eqng install_data: too many errors, rolled back', LOG_ERR);
            return -1;
        }

        $db->commit();
        dol_syslog('b3eqng install_data: seed completed for entity ' . $entity, LOG_INFO);
        return 0;
    }

    private static function splitSqlStatements(string $sqlRaw, int $entity): array
    {
        if ($entity !== 1) {
            $sqlRaw = str_replace(
                ['(1,\'NG-IFRS-SME', '(1,\'NG-WHT-', '(1,\'JV-', ', 1,\'chaine', 'entity=1'],
                ['(' . $entity . ',\'NG-IFRS-SME', '(' . $entity . ',\'NG-WHT-', '(' . $entity . ',\'JV-', ', ' . $entity . ',\'chaine', 'entity=' . $entity],
                $sqlRaw
            );
        }

        $lines = explode("\n", $sqlRaw);
        $statements = [];
        $buffer = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                continue;
            }
            $buffer .= $line . "\n";
            if (substr(rtrim($line), -1) === ';') {
                $statements[] = trim($buffer);
                $buffer = '';
            }
        }

        if ($buffer !== '') {
            $statements[] = trim($buffer);
        }

        return $statements;
    }
}
