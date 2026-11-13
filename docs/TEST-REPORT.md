# CodeSlot 1.0.0 test execution report

Date: 2026-11-13. Status: **installable release candidate; gated CI package available**. No public release, production deployment or WordPress.org submission was performed.

## Gated CI evidence

The complete quality workflow [43392868687](https://github.com/MalikArslanLive/codeslot/actions/runs/43392868687) on `3bbf7d7f0dd562d125e47509288edfe6aabc016f` completed **successfully: all 33 jobs passed**. This includes 21 WordPress/PHP integration combinations, seven PHP tooling jobs, JavaScript checks, browser tests, Plugin Check, the experimental probe and gated packaging. The separate security workflow passed as well. Machine-readable job conclusions are in [evidence/ci-3bbf7d7.json](evidence/ci-3bbf7d7.json).

The produced CI ZIP was downloaded and compared byte-for-byte with `packages/codeslot-1.0.0.zip`: **identical**. SHA-256:

```
a580ed3101d538ca0ea22b8dead58f48e5d1100fe6166b99caeefa658202c064
```

The later `b1f85fa` commit adds an Elementor browser regression only; it does not change production files. Its complete quality run [43394764238](https://github.com/MalikArslanLive/codeslot/actions/runs/43394764238) also passed **all 33 jobs**, including the expanded **7-test browser suite**. Evidence is in evidence/ci-b1f85fa.json. Documentation/evidence/package-only commits do not change the tested runtime. The project was merged into main in b81202b.

## Executed local commands and results

Managed-session CLI container: `codeslot-wp`; project root inside it: `/var/www/html/wp-content/plugins/codeslot`. Local PHP 8.3.36, WordPress 7.1.4, MariaDB 11.4. Reproducible Compose equivalents are in DEVELOPMENT.md.

| Command | Result |
|---|---|
| `docker exec -u root -w /var/www/html/wp-content/plugins/codeslot codeslot-wp sh -c 'find includes templates -name "*.php" -exec php -l {} \; && php -l codeslot.php && php -l uninstall.php'` | Passed; all 8 production PHP files |
| `docker exec -u root -w /var/www/html/wp-content/plugins/codeslot codeslot-wp php vendor/bin/phpunit` | Passed: 14 tests, 16 assertions; PHPUnit 9.6.33 |
| Same container/workdir: `php vendor/bin/phpcs` | Passed WordPress-Core standards |
| Same container/workdir: `php vendor/bin/phpstan analyse --memory-limit=2G --no-progress` | Passed PHPStan level 5; no errors |
| Same container/workdir: `php /tmp/composer.phar audit --format=json` | Passed; no known advisories/abandoned dependencies |
| `npm run lint` | Passed ESLint |
| `npm audit --json` | Passed; zero known vulnerabilities |
| `docker exec codeslot-wp wp eval-file wp-content/plugins/codeslot/tests/integration.php` | Passed: 33 assertions on single-site, including nonce/capability checks, autosaves, term fields, import prevalidation, inactive import, locking, rollback, ordering, duplicate suppression, safe mode and recovery |
| `docker exec codeslot-wp wp --path=/var/www/clean eval-file /var/www/html/wp-content/plugins/codeslot/tests/integration.php` | Passed: 36 assertions on installed ZIP/multisite, including reusable fixture, site isolation and non-super-admin denial |
| `docker exec codeslot-wp wp eval-file wp-content/plugins/codeslot/tests/safe-constant.php` | Passed: constant-based emergency suppression |
| `CS_WP_CLI='docker exec codeslot-wp wp' python3 tests/isolation.py` | Passed: 99 assertions per run; block/classic/Hello Elementor/Storefront, Rank Math separately, custom post types/taxonomies and unrelated posts/products |
| `PLAYWRIGHT_BROWSERS_PATH=/workspace/work/browsers npm run test:e2e` | Passed local 6-test admin suite (expanded 7-test suite passed CI): settings, editor/snippet/conditions/frontend, Classic, Gutenberg, Woo product, missing nonce |
| Same browser command with `-- --grep 'Gutenberg'` and standalone Gutenberg 24.4.0 active | Passed: native meta box and three fields |
| Same browser command with `-- --grep 'Elementor preview'` | Passed: live Elementor preview iframe retains Page A marker exactly once; Page B absent |
| `docker exec codeslot-wp wp eval-file wp-content/plugins/codeslot/tests/benchmark.php` | Completed 100/1,000 inactive and active-candidate measurements; see PERFORMANCE.md |
| `python3 scripts/build.py` | Passed deterministic allowlisted ZIP and archive CRC/path checks |
| `wp plugin install .../dist/codeslot-1.0.0.zip --path=/var/www/clean --activate` | Passed on clean database; header/body/footer each emitted once |
| `wp plugin check codeslot --path=/var/www/clean --format=json` | Passed Plugin Check 2.2.0: “Success: Checks complete. No errors found.” |
| `wp ... eval-file tests/uninstall.php`, with and without `CS_DELETE_TEST=1` | Passed: 5 checks each on multisite; retain/delete preferences respected |
| Separate `wp plugin deactivate codeslot`, then `wp plugin uninstall codeslot` on clean site | Passed actual deactivated-plugin uninstall; opted-in data removed; ZIP reinstalled successfully |

WP Super Cache 3.1.5 generated actual per-URL HTML cache files. The 99 isolation assertions passed on cold and warmed cache runs. An intentionally incomplete theme emitted no body marker and recorded `codeslot_body_codeslot-no-body = missing`. No body code was relocated.

The archive contains `codeslot/codeslot.php`, uninstall.php, readme.txt, LICENSE, includes, assets and local templates. It excludes databases, credentials, vendor, node_modules, tests, development files and repository metadata. No invented author identity is in the plugin header; an owner can configure an Author header explicitly.

## Compatibility matrix

| WordPress | PHP 7.4 | 8.0 | 8.1 | 8.2 | 8.3 | 8.4 | 8.5 |
|---|---|---|---|---|---|---|---|
| 6.8.3 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.9 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| latest stable, 7.1.4 during session | Passed | Passed | Passed | Passed | Passed | Passed | Passed |

The matrix runs actual core integration, an installed ZIP, multisite, recovery and uninstall checks. It does not run every companion plugin on every combination. Companion/theme versions and partial coverage are explicit in COMPATIBILITY.md. The non-blocking nightly job is only a download/lint probe, not a runtime integration pass.

## Failure history and limits

Initial CI failures were inspected and corrected: PHP 7.4/8.0 lockfile incompatibility (dependency resolution now targets PHP 7.4.33), unsupported Playwright Ubuntu runner (pinned Ubuntu 24.04), missing `wp` in the Compose entrypoint, missing custom rewrite flush and fresh companion-plugin onboarding redirects. Superseded runs were canceled; canceled/skipped jobs are not reported as passes. Earlier local browser failures were resolved through actual editor selection, onboarding handling and opening Gutenberg's native Meta Boxes pane.

Local setup failures included Docker vfs disk exhaustion for the large Apache image, CA trust, bind-mount permissions and PHP's 128 MiB limit under the combined free-plugin stack. The smaller WP-CLI/PHP server was used locally; the corrected Compose Apache environment ran in CI. TLS verification remained enabled.

**Blocked:** branch-protection API configuration returned HTTP 403 “Resource not accessible by integration”; repository Administration write permission or owner configuration is required (issue #2). The chat's designated `/codex/.../output` directory could not be created because this executor lacks filesystem permission; the ZIP is instead committed under `packages/` and available as the gated Actions artifact.

**Not tested:** licensed Elementor Pro, Bricks, Divi and WPML; complete checkout/payment flows; every ACF/translation/builder workflow; beta/RC runtime; all cache/CDN products; concurrent production load; every PHP fatal/side effect. No independent penetration-test claim is made. There is no PHP sandbox, network-wide snippet library, cloud sync, AI service, early hook execution or guaranteed callback-fatal recovery. See FEATURE-MATRIX.md and SECURITY.md.
