# CodeSlot 1.0.0 responsive UI and package validation

Date: 2026-11-13. Version stays 1.0.0. No public release, production deployment or WordPress.org submission is authorized or claimed. This report supersedes the previous package's checksum for the current build.

## Changes

A navy branded header, original code mark, metadata grid and refined spacing give all six CodeSlot admin pages scoped styling, consistent navigation, responsive forms and accessible focus indicators. The list turns into labeled cards on narrow screens. Buttons remain within the viewport. Snippet names and explicit Edit links remain visible; legacy empty names display an ID-based title. New editor saves require a name in the browser. Quick Edit and bulk metadata updates preserve code, location and conditions. Each row has a confirmed Delete action; locked snippets and missing nonces are rejected. Selection has a dedicated checkbox column and a mobile Select page control. First/previous/next/last pagination preserves search filters. Existing import/API behavior remains compatible. Filters retain their selections and have a reset action and an empty state. Code editor, conditions, recovery/templates, import/export, settings and global slots have updated layouts. CSS uses a content hash to refresh caches when reinstalling the same version.

Display title: **CodeSlot – Header, Body & Footer Code + Snippet Manager**. Author: Malik Arslan, https://malikarslan.com/. Slug: codeslot. License: GPL-2.0-or-later. https://wordpress.org/plugins/codeslot/ is the official intended plugin URL; availability/approval was not independently verified.

## Executed checks

| Check | Actual result |
|---|---|
| `npm run lint` | Passed |
| `npm audit --cache /workspace/.npm-cache --audit-level=high` | Passed, zero vulnerabilities |
| `php vendor/bin/phpunit --do-not-cache-result` | Passed, 14 tests / 16 assertions |
| `php vendor/bin/phpcs` | Passed WordPress-Core |
| `php vendor/bin/phpstan analyse --memory-limit=2G --no-progress` | Passed level 5 |
| All eight production PHP files: `php -l` | Passed |
| WP-CLI `eval-file tests/integration.php` | Passed 33 assertions on source and 33 on a separately installed current ZIP |
| WP-CLI `eval-file tests/safe-constant.php` | Passed emergency suppression |
| Disposable multisite integration and uninstall retain/delete | Passed 36 integration assertions and five checks for each uninstall preference during this session |
| `npx playwright test --config work/playwright.ui.config.mjs` | Passed the 14-test regression suite and four additional list-action tests using system Chromium; the final combined 18-test run is pending |
| UI scope within those 14 tests | Six admin pages at 1440, 768, 390 and 320 pixels; legacy unnamed editing; retained filters; mobile conditions |
| Companion scope within those 14 tests | WooCommerce 11.3.0 native product fields; Classic Editor 1.7.0; Gutenberg 24.4.0 native meta boxes; Elementor 4.4.0 preview isolation |
| `wp plugin check codeslot --format=json` on the clean ZIP installation | Passed Plugin Check 2.2.0: no errors |
| `python3 scripts/build.py` | Passed deterministic archive CRC, paths and checksum |
| Archive content/source comparison | Passed, 12 production files match source bytes; no development dependencies, tests, databases or credentials |

Browser tests use existing `tests/admin.spec.js` and `tests/elementor.spec.js` plus `tests/ui.spec.js` and `tests/list-actions.spec.js`. The local ignored Playwright config only selects `/usr/bin/chromium`; normal CI uses `npm run test:e2e`. Logs are in [evidence/ui-1.0.0/](evidence/ui-1.0.0/).

PHP tooling ran on PHP 8.5.12 CLI. WordPress integration ran on PHP 8.3.36 CLI. Because Composer's normal GitHub API downloads are blocked, exact lockfile source commits were fetched from official GitHub repositories over verified TLS and archived locally for an ignored Composer package repository. All 35 installed versions and source references were compared against the unchanged composer.lock and matched. No certificate or expected-checksum verification was disabled. This is a local provisioning workaround; normal CI continues to install the original lockfile.

## Server coverage

WordPress 7.1.4 on Linux, using the same disposable database and seeded fixtures. Each server run checks global Header/Body/Footer, page/post/product/CPT and category/custom-term isolation, duplicate prevention, conditional inclusion/exclusion and shortcode output. Identity and SAPI are obtained from actual HTTP output; version commands are retained alongside results. `scripts/test-servers.sh` reproduces the suite. See server evidence for the final results and whether real companion plugins were active.

| Server | PHP | Actual SAPI | Base fixture checks |
|---|---|---|---|
| Apache 2.4.69 | 8.3.36 | apache2handler | Passed 108 isolation + 27 conditional/shortcode assertions |
| Nginx 1.30.6 + PHP-FPM | 8.3.36 | fpm-fcgi | Passed 108 + 27 assertions |
| OpenLiteSpeed 1.9.4 | 8.2.35 | litespeed | Passed 108 + 27 assertions |

Base fixture runs use a test-only public product post type when WooCommerce is absent. They do not substitute for the separate actual WooCommerce browser checks. LiteSpeed Enterprise is not tested. CGI, Windows/macOS and arbitrary server configurations are not tested.

## Security and external limits

Nonce and privilege failures, import prevalidation/inactive copies, source escaping, locking/rollback, output ordering/duplicate prevention, multisite separation and recovery are covered by the existing executable regression suites. PHP is arbitrary privileged code, not a secure sandbox. This is source review and automated testing, not an independent penetration-test certification. Page caches still need appropriate variation/purging.

Local Composer advisory auditing is **blocked**: packagist.org destination access is denied. The new [security workflow 43400701487](https://github.com/MalikArslanLive/codeslot/actions/runs/43400701487) on ae9398c passed, including Composer and npm audits on the hosted runner. This was verified through the public Actions summary despite local API denial. GitHub API access is also blocked at the cloud proxy, so repository About/topics/protection/environments, CI API status (public Actions summaries remain readable) and draft-release creation cannot be confirmed through that API. This is a current network blocker, distinct from the previous integration permission denial. Required domains are saved in the environment draft.

The expanded GitHub workflow defines eight WordPress targets across seven PHP versions (56 runtime combinations), a non-blocking nightly integration job and a separate server gate. These jobs are **not passed merely because they are configured**. WordPress 6.5 activation and the 33-assertion integration suite passed locally on PHP 8.3.36. The header/readme minimum is now 6.5; the full cross-version matrix still needs its new CI run. PHP remains 7.4+. Current-run evidence and the historical 21-combination baseline remain distinct.

Companion plugins emitted a Gutenberg bundled MCP Adapter deprecation notice; WordPress background update requests also warn because WordPress.org is blocked here. These are retained in logs, not hidden or attributed to CodeSlot. Full payment flows, premium plugins, every cache, every PHP failure mode and production load are not tested.

## Package

`packages/codeslot-1.0.0.zip` contains `codeslot/codeslot.php`. SHA-256:

```
0a5ffa9e92e33d7e4a53462ee8e3feb0cc15705ec049df2af5f9d4647f739d28
```

See `packages/SHA256SUMS`. Reinstalling 1.0.0 preserves normal plugin data; backups remain recommended.

## Additional regression results

The four new list-action tests passed: Quick Edit and Bulk Edit preserve code while updating selected metadata; cancel/confirm Delete; missing nonce and locked-snippet rejection; pagination retains search and mobile Select page checks 30 visible entries. A mobile checkbox hit-target overlap was found and fixed using WordPress check-column markup and non-interactive labels.

WordPress 6.5 activated the current ZIP and passed all 33 integration assertions on PHP 8.3.36. The initial expanded CI run encountered failures on the older branches before the minimum was updated from 6.8; its failures are not passes. A new run is required for the final source.
