<?php
/* ============================================================================
 * b3Ɛq Nigerian Accountancy — Shared Page Bootstrap
 * tagged: b3Ɛq Nigerian Accountancy
 * File:   htdocs/custom/b3eqng/class/b3eqng_init.php
 * ============================================================================
 */

if (!defined('DOL_VERSION')) {
    $dir = __DIR__;
    $res = 0;
    // Walk up until we find main.inc.php (Dolibarr root)
    while (!file_exists($dir . '/main.inc.php') && strlen($dir) > 3) {
        $dir = dirname($dir);
    }
    if (file_exists($dir . '/main.inc.php')) {
        $res = @include $dir . '/main.inc.php';
    }
    if (!$res) die("Include of main.inc.php failed. Check your Dolibarr installation path.");
}

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/custom/b3eqng/class/b3eqng.class.php';

// ── Compatibility Helper ─────────────────────────────────────────────────────
if (!function_exists('b3eq_conf')) {
    /**
     * Version-safe global variable access
     */
    function b3eq_conf($name, $default = '') {
        global $conf;
        // Use native getDolGlobalString (available since v17) or fallback to $conf
        if (function_exists('getDolGlobalString')) {
            $val = getDolGlobalString($name);
            return ($val !== '') ? $val : $default;
        }
        return isset($conf->global->$name) ? $conf->global->$name : $default;
    }
}

// ── CSS Injection ────────────────────────────────────────────────────────────
if (!function_exists('b3eq_inject_css')) {
    /**
     * Injects the f7en sovereign branding CSS
     */
    function b3eq_inject_css() {
        $url = dol_buildpath('/b3eqng/css/b3eqng.css', 1);
        echo '<link rel="stylesheet" type="text/css" href="' . $url . '">';
    }
}

if (!isset($langs)) global $langs;
