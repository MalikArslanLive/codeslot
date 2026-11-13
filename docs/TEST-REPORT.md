# CodeSlot 1.0.0 validation

The completed quality run [43407872155](https://github.com/MalikArslanLive/codeslot/actions/runs/43407872155) on `2fa2ef3b163c5327b0327ef3a086b08c79f9b375` passed all **69 jobs**, including 56 WordPress/PHP matrix jobs, seven PHP tooling jobs, JavaScript, 18 browser tests, Plugin Check, three-server HTTP validation, the non-blocking nightly integration probe and gated packaging. [Security run 43407872042](https://github.com/MalikArslanLive/codeslot/actions/runs/43407872042) passed dependency audits.

The current follow-up corrects the Plugin URI, explains the free features and individual fields, and consolidates repository documentation. Its rebuilt package/checksum and validation are recorded in [evidence/validation-1.0.0.json](evidence/validation-1.0.0.json). A configured workflow is not evidence of a pass; follow-up CI results must be verified before marking them complete.

## Executed scope

| Check | Result |
|---|---|
| PHPUnit | 14 tests / 16 assertions passed |
| WordPress Coding Standards | Passed |
| PHPStan level 5 | Passed |
| PHP lint | All eight production PHP files passed |
| ESLint / npm audit | Passed; no reported npm vulnerabilities |
| Composer advisory audit | Passed on hosted CI; local packagist.org access is blocked |
| Source / installed ZIP integration | 33 assertions passed per single-site run |
| Multisite integration | 36 assertions passed |
| Uninstall retain/delete | Five assertions passed for each preference |
| Constant Safe Mode | Passed emergency suppression |
| Browser suite | 18 passed: global slots, snippets/conditions/frontend, nonce rejection, Classic/Gutenberg, Woo product fields, Elementor preview, responsive layouts, Quick/Bulk Edit, confirmed Delete, locks and pagination |
| Responsive scope | All six admin pages at 1440, 768, 390 and 320 pixels; dedicated selection controls and visible mobile labels |
| Server HTTP output | Each server passed 108 isolation +27 conditions/exclusion/shortcode assertions; identities and SAPIs measured through HTTP |
| ZIP | Production-only archive, CRC/path/source-byte checks and SHA-256; installed independently and Plugin Check reported no errors |

Commands from the repository root:

```sh
npm ci
npm run lint
npm audit --audit-level=high
composer install --no-interaction --prefer-dist
composer test
composer lint
composer analyse
composer audit
./scripts/provision.sh
npm run test:e2e
python3 tests/isolation.py
./scripts/test-servers.sh
python3 scripts/build.py
```

Provisioning requires Docker/Compose; browser tests require the pinned Playwright Chromium (`npx playwright install --with-deps chromium`). Local browser runs also used system Chromium via an ignored config. Server, theme and browser tests must run sequentially because server tests change disposable-site URLs.

WP-CLI checks use `docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php`, `tests/safe-constant.php` and `tests/uninstall.php`. Run multisite/uninstall only on disposable sites. CI installs official WP-CLI 2.12.0 with its published SHA-512; certificate/checksum verification stays enabled.

## Compatibility and honest limits

See [COMPATIBILITY.md](COMPATIBILITY.md) for the complete matrix and exact server/SAPI versions. Actual free companion coverage includes WooCommerce 11.3.0, Classic Editor 1.7.0, Gutenberg 24.4.0 and Elementor 4.4.0. Three bundled block themes and Storefront 4.6.2 passed isolation. The obsolete Twenty Twenty-One 1.0.0 source snapshot failed on a WooCommerce product warning; it is not reported as compatible. This differs from the earlier baseline's Twenty Twenty-One 2.9 tests.

Initial failures were investigated rather than hidden: the old 6.8 activation minimum blocked lower WordPress probes; 6.5 was activated/tested before lowering the minimum. OLS worker ownership caused Elementor FTP fallback and was aligned to www-data. Contact Form 7 dependencies require PHP >=8.3, so the full companion-stack LSAPI image was moved from 8.2 to 8.3. Missing/partial WP-CLI tooling was replaced with a pinned, checksum-verified installation. One earlier runtime job exited 255 and did not reproduce in subsequent complete runs; diagnostics now expose underlying output. Failed/canceled runs remain failed/canceled.

PHP executes arbitrary privileged code and is not a secure sandbox. Source review and automated tests are not an independent penetration-test certificate. Premium products, full payment flows, LiteSpeed Enterprise, CGI, Windows/macOS, every cache/CDN and all PHP side effects are not tested. Cache variation/purging remains necessary. Third-party MCP Adapter deprecations and blocked background update warnings were retained in logs.

Old duplicate raw logs and incomplete reports were consolidated into this report and structured evidence. Their prior contents remain in Git history and a local pre-cleanup backup; test infrastructure and lockfiles remain in the repository.

## Additional local features

Saved/current code diffs, numbered-paragraph insertion and confirmed Run Once PHP were added after the baseline. Local verification passed 18 unit tests/20 assertions, 14 WordPress feature assertions and three new browser regressions (nonce/confirmation/repeat prevention, escaped mobile diffs, and paragraph HTTP isolation). The matrix now runs local-features.php for source, ZIP and multisite. The new combined CI result must be checked before claiming full validation. Cloud sync and AI are excluded by explicit user instruction. These additions do not claim every possible feature or guaranteed recovery from arbitrary privileged PHP.
