#!/usr/bin/env bash
set -euo pipefail
composer install --no-interaction
find includes templates -name '*.php' -print0 | xargs -0 -n1 php -l
php -l codeslot.php
php -l uninstall.php
composer test
composer analyse
composer lint
composer audit
npm ci
npm run lint
npm audit --audit-level=high
