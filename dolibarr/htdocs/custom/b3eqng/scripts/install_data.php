<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy v2.0.0 — Data Installer
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/scripts/install_data.php
 *
 * Wraps the SeedInstaller library for backward compatibility.
 * ============================================================================
 */

if (!defined('DOL_VERSION')) { die('Forbidden'); }
require_once DOL_DOCUMENT_ROOT . '/custom/b3eqng/lib/SeedInstaller.php';

/**
 * Execute the Nigerian accounting data seed.
 *
 * @param  DoliDB $db       Dolibarr database handler
 * @param  int    $entity   Entity ID
 * @return int              0 on success, -1 on error
 */
function b3eqng_install_data($db, $entity = 1)
{
    return B3eqngSeedInstaller::executeSeed($db, $entity);
}
