# Release verification

CodeSlot 1.0.0 is verified with disposable WordPress fixtures, Chromium browser checks, static analysis and clean-installed package checks. This report describes the tested scope and its limits; it is not a guarantee for arbitrary administrator-authored code or every hosting/plugin combination.

## Results

| Check | Verified result |
|---|---|
| Chromium administration/features | 85 distinct automated cases: complete 83-case suite on 15 November 2026, plus two focused cases on 20 November |
| PHP unit tests | 47 tests / 74 assertions passed |
| Backend fixtures | 63 dependency, 27 conditions/search, 24 restore-choice, 28 import/source, 165 extended, 9 Trash and 6 post/term validation assertions passed |
| WordPress integration | Capabilities/nonces, multisite restrictions, data/source isolation, recovery and local-feature checks passed |
| Apache, Nginx and OpenLiteSpeed | Each passed 108 HTTP source-isolation + 27 conditions/shortcode + 12 public-file assertions |
| Themes | Four themes each passed 108 source-isolation assertions |
| Tooling | WordPress coding standards, explicitly configured I18n domain, PHPStan, JavaScript lint and PHP 7.4 production syntax passed |
| Production ZIP | Clean installation, Plugin Check 2.2.0, archive CRC, allowlisted paths, 0644 permissions, exact source bytes and checksums passed |
| WordPress.org media | Eight actual screenshots, two icon sizes, two banner sizes and matching readme captions verified |

The complete browser run took 31.1 minutes. The two additional automated cases cover malformed condition submissions and the Plugins-list Code Snippets link. Focused real-browser checks also verified active/inactive originals produce inactive copies, original states are retained, individual-field checkbox branding/click behavior fits 1600/390px, and the assigned-folder installation loads its navigation/table/assets without JavaScript errors.

The final installable directory and translation domain are `codeslot-header-body-footer-code-snippet-manager`. Plugin Check was run against that actual directory, not just a development source path, and reported no errors. The I18n configuration explicitly enforces the assigned domain; a deliberately mismatched disposable probe was rejected. The build checks that the Text Domain header matches its archive root.

See [Compatibility](COMPATIBILITY.md) for precise versions and [Security review](SECURITY-REVIEW.md) for boundaries and residual risks. Dependency-audit observations are recorded at their audit date and do not certify the absence of every possible vulnerability.

## Environments

- WordPress 6.5 / PHP 7.4.33 CLI: integration, conditions/search, import/restore, extended features, paragraph/Run Once, Trash, Safe Mode, metadata validation and uninstall retain/delete.
- WordPress 7.1.4 / PHP 8.3.36: automated browser suite, separate multisite fixture, assigned-folder clean package checks and backend suites.
- PHP 8.5.12 CLI: unit tests, coding standards and PHPStan.
- WordPress 7.1.5: isolated gallery demonstration and focused native-editor styling checks; this does not represent the complete browser matrix on that patch.

All nine administration screens were exercised at 1440/768/390/320px. Classic Editor, Gutenberg, WooCommerce product fields and an Elementor preview are covered by the recorded fixtures. Server suites ran sequentially because they change the disposable site's URLs. Premium-plugin and every-hosting compatibility are not claimed.

## Feature coverage

- **Conditions:** title/name searches, public types/taxonomies/roles, missing identities, bounded/authorized results, stale/failed queries, keyboard operation, long multilingual titles, AND/OR/Exclude and missing dependencies.
- **Management:** code types, individual/bulk switches, source retention, lock/unlock, Quick/Bulk Edit, selection errors, Screen Options, inactive copies, Trash/Restore/permanent deletion and unchanged-save revision deduplication.
- **Import/restore:** uploaded JSON validation, exact duplicates, Skip/Keep both/Replace, locked/ambiguous matches, inactive new entries, Keep/Replace and Disable/Preserve policies, clear empty-selection/section-wise notices, mappings, prevalidation and rollback attempts.
- **Tracking:** one head configuration/loader, six mocked SDK requests before body parsing, consent gating/withdrawal, supported events, distinct GA4/Ads targets, cart/checkout/authorized paid-order data, invalid keys/unpaid orders and deduplication. DOM-dependent listeners wait for readiness.
- **Execution/security:** capability and nonce rejection, multisite super-admin boundaries, source isolation, paragraph/selector insertion, schedules/devices, Safe Mode, explicit Run Once, recovery and opt-in uninstall cleanup.
- **UI/media:** native editor integration, responsive controls, scoped checkbox branding, code modes, error notices, guide links, real demo captures and correctly sized/named WordPress.org assets.

## Run checks on a disposable site

```sh
composer install --no-interaction
composer test
composer lint
composer analyse
composer audit
npm ci
npm run lint
npm audit --audit-level=high

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/dependencies.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/condition-search.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/restore-choices.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/batch2-regressions.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/extended-features.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/local-features.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/final-validation.php
docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/trash-lifecycle.php
CS_WP_CLI='docker compose run --rm cli' python3 tests/isolation.py
npm run test:e2e
node tests/head-placement.mjs
python3 scripts/build.py
python3 scripts/build-media.py
```

The short codeslot path above is the Compose development mount. Install the production ZIP into a separate site and run `wp plugin check codeslot-header-body-footer-code-snippet-manager --format=table`. The head test accepts CODESLOT_TEST_PATH and CODESLOT_TEST_URL for that isolated site. HTTP isolation needs the test-only seed/registered-type fixtures. Never install these fixtures on a live site; they create content and execute test PHP. See [Development](DEVELOPMENT.md).

## Tracking limits

Automated suites mock third-party SDKs and use disposable WooCommerce orders. Queue/payload assertions establish browser adapter behavior, not account attribution, provider dashboard receipt or successful delivery through every browser/content blocker. No fake purchases are sent to real accounts by the normal suites.

Optional live provider tests require an ID whose owner has explicitly authorized test traffic. Consent solutions must manage their own cookies and existing vendor scripts; CodeSlot is not a cookie banner. Platform automatic events are independent of CodeSlot's event switches. Consult [Tracking](TRACKING.md) before enabling an integration.

## Package and gallery integrity

The production ZIP excludes tests, development dependencies, demo data and gallery artwork. The standalone readme and media-kit readme match the plugin's readme. Each release file is covered by SHA256SUMS; the media kit also includes per-image hashes. Gallery screenshots are actual WordPress/CodeSlot captures with generic sample data, disabled illustrative providers and no submitted restore. Icons/banners are separate branding artwork. See [the GitHub gallery and media guide](../media/README.md).

## GitHub distribution refresh — 24 November 2026

This documentation/media-only refresh removed directory gallery captions and submission/SVN instructions, moved artwork to media/, and added the GitHub gallery. Focused checks verified production archive CRC, all 29 allowlisted runtime files, identical embedded/standalone readmes, valid local image links, twelve PNG dimensions/checksums, and GitHub-rendered Markdown with eight screenshots, an icon and a banner without a link. Runtime PHP/JavaScript/CSS was unchanged; earlier runtime/browser results above were not rerun for this refresh.
