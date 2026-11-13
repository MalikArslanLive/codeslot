# CodeSlot 1.0.0 validation

The completed quality run [43474411107](https://github.com/MalikArslanLive/codeslot/actions/runs/43474411107) on `6bdde4d` passed all **69 jobs**, including 56 WordPress/PHP matrix jobs, seven PHP tooling jobs, JavaScript, 29 browser tests, Plugin Check, three-server HTTP validation, the non-blocking nightly integration probe and gated packaging. [Security run 43474411465](https://github.com/MalikArslanLive/codeslot/actions/runs/43474411465) passed dependency audits.

This build includes the corrected Plugin URI, free features/individual fields, revision diffs, paragraph insertion, guarded one-time PHP tasks and the local tools described below. The rebuilt package/checksum and completed validation are recorded in [evidence/validation-1.0.0.json](evidence/validation-1.0.0.json).

## Executed scope

| Check | Result |
|---|---|
| PHPUnit | 18 tests / 20 assertions passed |
| WordPress Coding Standards | Passed |
| PHPStan level 5 | Passed |
| PHP lint | All 15 production PHP files passed |
| ESLint / npm audit | Passed; no reported npm vulnerabilities |
| Composer advisory audit | Passed on hosted CI; local packagist.org access is blocked |
| Source / installed ZIP integration | 33 assertions passed per single-site run |
| Multisite integration | 36 assertions passed |
| Uninstall retain/delete | Five assertions passed for each preference |
| Constant Safe Mode | Passed emergency suppression |
| Browser suite | 29 passed: global slots, snippets/conditions/frontend, nonce rejection, Classic/Gutenberg, Woo product fields, Elementor preview, responsive layouts, Quick/Bulk Edit, confirmed Delete, locks and pagination |
| Responsive scope | All nine admin pages at 1440, 768, 390 and 320 pixels; dedicated selection controls and visible mobile labels |
| Server HTTP output | Each server passed 108 isolation +27 conditions/exclusion/shortcode +12 virtual-file HTTP assertions; identities and SAPIs measured through HTTP |
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

WP-CLI checks use `docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php`, `tests/local-features.php`, `tests/safe-constant.php` and `tests/uninstall.php`. Run multisite/uninstall only on disposable sites. CI installs official WP-CLI 2.12.0 with its published SHA-512; certificate/checksum verification stays enabled.

## Compatibility and honest limits

See [COMPATIBILITY.md](COMPATIBILITY.md) for the complete matrix and exact server/SAPI versions. Actual free companion coverage includes WooCommerce 11.3.0, Classic Editor 1.7.0, Gutenberg 24.4.0 and Elementor 4.4.0. Three bundled block themes and Storefront 4.6.2 passed isolation. The obsolete Twenty Twenty-One 1.0.0 source snapshot failed on a WooCommerce product warning; it is not reported as compatible. This differs from the earlier baseline's Twenty Twenty-One 2.9 tests.

Initial failures were investigated rather than hidden: the old 6.8 activation minimum blocked lower WordPress probes; 6.5 was activated/tested before lowering the minimum. OLS worker ownership caused Elementor FTP fallback and was aligned to www-data. Contact Form 7 dependencies require PHP >=8.3, so the full companion-stack LSAPI image was moved from 8.2 to 8.3. Missing/partial WP-CLI tooling was replaced with a pinned, checksum-verified installation. One earlier runtime job exited 255 and did not reproduce in subsequent complete runs; diagnostics now expose underlying output. Failed/canceled runs remain failed/canceled.

PHP executes arbitrary privileged code and is not a secure sandbox. Source review and automated tests are not an independent penetration-test certificate. Premium products, full payment flows, LiteSpeed Enterprise, CGI, Windows/macOS, every cache/CDN and all PHP side effects are not tested. Cache variation/purging remains necessary. Third-party MCP Adapter deprecations and blocked background update warnings were retained in logs.

Old duplicate raw logs and incomplete reports were consolidated into this report and structured evidence. Their prior contents remain in Git history and a local pre-cleanup backup; test infrastructure and lockfiles remain in the repository.

## Additional local features

Saved/current code diffs, numbered-paragraph insertion and confirmed Run Once PHP were added after the baseline. Local verification passed 18 unit tests/20 assertions, 14 WordPress feature assertions and three new browser regressions (nonce/confirmation/repeat prevention, escaped mobile diffs, and paragraph HTTP isolation). The matrix now runs local-features.php for source, ZIP and multisite. The combined 69-job CI run above passed, including the 29-test browser suite and the added source/ZIP/multisite assertions. Cloud sync and AI are excluded by explicit user instruction. These additions do not claim every possible feature or guaranteed recovery from arbitrary privileged PHP.

## Expanded local tools candidate (6bdde4d)

The updated 1.0.0 ZIP passes clean installed-package Plugin Check with no errors/warnings, real WordPress multisite regression and 162 extended assertions. The extended scope validates every bundled recipe/guide's syntax/schema and inactive defaults, backup mapping/prevalidation/inactive restore, file/ID validation, capability rejection, log bounds/source exclusion and diagnostics redaction. Recipe schema/syntax validity is not universal behavioral compatibility certification.

Local browser coverage is now 29 tests. A full run passed 28 and exposed local fixture contamination in the Elementor baseline; the backup test had disabled pre-existing fixture slots. The test was narrowed to its own objects, fixtures were reseeded, and Elementor passed its unchanged assertions on rerun. The eight new tests passed, including all-five-provider adapter queues and an authorized paid WooCommerce purchase with wrong-key rejection, omitted contact data and browser-session deduplication. Vendor SDK requests are mocked for these regression checks; live provider-account delivery and payment gateways are not certified.

Local server tests passed all three actual server/SAPI combinations: 108 isolation +27 conditional/shortcode +12 virtual-file HTTP status/MIME/content assertions per server. Static gates passed: 18 PHPUnit tests/20 assertions, WPCS, PHPStan and ESLint. All nine admin pages fit at 1440/768/390/320 pixels. Updated hosted quality run: https://github.com/MalikArslanLive/codeslot/actions/runs/43474411107 (verified success: all 69 jobs passed). Dependency security run43474411465 passed.

Additional commands:

```sh
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/extended-features.php
npx playwright test tests/extended-features.spec.js
npx playwright test tests/ui.spec.js
```

The final hosted run completed successfully on source 6bdde4d: all 69 jobs passed, including all 29 browser tests on freshly provisioned fixtures. The earlier local fixture-contamination failure and its correction remain documented above; no failed run was relabeled as passing.
