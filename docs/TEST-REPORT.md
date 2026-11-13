# Test execution report — in progress

Local PHP 8.3.36 / WordPress 7.1.4 / MariaDB 11.4.

Passed: initial PHPUnit 14 tests / 16 assertions; PHP syntax lint; JavaScript ESLint; WPCS after formatting; PHPStan level 5; initial 27 WordPress integration assertions, later expanded with ordering and duplicate-output checks; 99 HTTP isolation assertions on each of a block and classic theme; initial 3 browser checks; npm audit; Composer audit after dependency upgrades.

In progress: final browser editor-state checks, packaged Plugin Check, clean ZIP installation, multisite/uninstall, stable CI matrix. Do not treat this as final release certification.

Setup failures resolved: large Docker Apache image exhausted vfs disk capacity, replaced locally by smaller WP-CLI/PHP server; container trust configured with supplied CA; default PHP memory raised for the full companion stack; temporary bind-mount ownership corrected; Composer/npm cache redirected to writable workspace. No TLS verification was disabled.
