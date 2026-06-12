# b3Ɛq Nigerian Accountancy Module

This Dolibarr module provides Nigerian accounting and tax compliance features, including VAT, WHT, PAYE, CIT, fixed assets, FX revaluation, and an immutable audit trail.

## Structure
- `admin/` — module settings page
- `class/` — core bootstrap and logic classes
- `core/modules/` — Dolibarr module descriptor
- `css/`, `img/`, `langs/` — module assets
- `pages/` — user-facing module pages
- `scripts/` — install scripts and helpers
- `sql/` — create and seed SQL files
- `lib/` — shared module libraries
- `test/` — module quality tests

## Developer workflow
1. Run `composer dump-autoload` from the module root to register classmap autoloading.
2. Run `php test/test_runner.php` or `./test/run-tests.sh` to execute module quality checks.

## Quality and packaging
- `composer.json` defines autoloading for module classes and libraries.
- `modulebuilder.txt` captures module metadata for packaging.
- `.editorconfig` enforces formatting rules.
- `test/` includes lightweight validation for core business logic.

## Notes
This module is designed to work with Dolibarr v15.0 or newer and PHP 7.4+. The module uses a seed installer that is idempotent and safe for re-run during activation.
