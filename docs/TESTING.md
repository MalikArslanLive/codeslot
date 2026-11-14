# Tests and quality gates

Use a disposable database only. Test fixtures deliberately create content and execute test PHP. See DEVELOPMENT.md for provisioning; No premium compatibility or full-matrix claim follows from one local run.

```
composer install --no-interaction
composer test                           # isolated unit tests
composer lint                           # WordPress coding standards
composer analyse                        # PHPStan, 2 GiB ceiling
composer audit
npm ci
npm run lint
npm audit --audit-level=high

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/integration.php
CS_WP_CLI='docker compose run --rm cli' python3 tests/isolation.py
npm run test:e2e

docker compose run --rm cli eval-file wp-content/plugins/codeslot/tests/benchmark.php
python3 scripts/build.py
```

Integration assertions run on actual WordPress APIs through WP-CLI rather than pretending a stubbed WordPress runtime establishes plugin compatibility. PHPUnit unit tests isolate condition logic and validation. Browser tests exercise actual login, editors and admin POST routes. HTTP source tests verify both positive and negative unique markers, not just rendered DOM text.

Mandatory release gates: lint, unit, standards, static analysis, dependency audit, core integration, privilege and nonce tests, multisite checks, classic/block theme isolation, Gutenberg/Classic/Woo product browser tests, clean ZIP installation and Plugin Check. Any blocked mandatory gate prevents a production-readiness claim.

Missing premium licenses produce **not tested** entries, never a synthetic pass. Failures during test setup (TLS trust, permissions, container disk/memory limits) are recorded and retried only after correcting the setup. Keep local test results with the change being reviewed.

## Current administration and pixel checks

The revised 1.0.0 interface passed 50 distinct browser tests (49 in the complete suite, plus the subsequently added private editor-draft regression in a focused run), 33 PHPUnit tests (38 assertions), 165 extended WordPress assertions, the core integration suite, coding standards, PHPStan and JavaScript lint. The responsive suite checks all nine admin pages at 1440, 768, 390 and 320 pixels. The production ZIP was clean-installed and checked with WordPress Plugin Check. See COMPATIBILITY.md for the separate runtime/server coverage and its limits.

Normal browser tests mock vendor SDKs. They verify enabled/disabled event selection, consent, Meta commerce payloads, actual WooCommerce cart/checkout/order fixtures, purchase deduplication and GTM dataLayer events without sending fake purchases to real accounts.

On 2026-11-14, separate authorized live checks used the actual Meta SDK in headed Chromium on an isolated localhost fixture and on a real page in the clean WordPress installation of the final production ZIP. The SDK and Pixel configuration returned HTTP 200. PageView and CodeSlotClick requests to Meta's /tr/ endpoint returned HTTP 200 in both checks. No vendor requests occurred before marketing consent, and a click after withdrawal generated no new CodeSlot event. No commerce events were sent in those live checks. The clean site's temporary page and pixel/settings options were removed/restored afterward. This establishes browser/network behavior, not Events Manager receipt, account attribution, or production-site behavior. Headless Chromium was suppressed by that Pixel's configured bot protection; it is not counted as a successful live delivery test.

To repeat the **opt-in live check**, use a public Pixel ID whose owner has authorized test traffic. It sends one PageView and one custom click; it does not send a purchase or contact data:

```sh
# Use your desktop display, or xvfb-run with Xvfb installed.
export CODESLOT_META_PIXEL_ID=YOUR_AUTHORIZED_PUBLIC_PIXEL_ID
CODESLOT_HEADED=1 node tests/live-meta.mjs
# For a Linux machine without a display:
CODESLOT_HEADED=1 xvfb-run -a node tests/live-meta.mjs
# Only when a managed HTTPS inspection proxy supplies a public CA file:
# export CODESLOT_PROXY_CA_FILE=/path/to/the/supplied-public-ca.crt
```

The script writes /tmp/codeslot-live-meta.json and fails if expected HTTP responses or consent assertions fail. It is excluded from the normal Playwright suite. It uses HTTPS_PROXY when configured and default certificate verification. If CODESLOT_PROXY_CA_FILE is explicitly supplied, only that public CA key is scoped into the temporary test browser using Chromium's SPKI option; no persistent trust store is changed and arbitrary certificate errors are not ignored. The production plugin does not change TLS verification. Confirm provider-side receipt independently in Meta Events Manager → Test Events.

For a configured disposable WordPress page, set CODESLOT_META_PAGE_URL to its URL. Configure the same authorized Pixel ID with Page view and Click enabled, the selector .qa-click, and a visible button labeled Test click with that class. The script then uses the installed plugin's actual frontend output instead of its standalone fixture. Use a page without automatic consent so the initial consent assertion remains meaningful; restore temporary settings after the check.

The review regressions in tests/review-fixes.spec.js cover all seven code types plus legacy JS/CSS wrappers, mixed bulk actions, unchanged-save revision deduplication, actual metadata changes, native Screen Options persistence, branded checkbox/keyboard/save behavior, inline Pixel/selection errors, device/schedule HTTP isolation, static-file/minifier semantics, and all five selector insertion positions. tests/local-features.spec.js checks the previous/current comparison labels and escaping. Standard capability/nonce/recovery/uninstall/editor regressions remain included.

Additional lifecycle/provider regressions cover WordPress Trash, Undo, inactive restore, permanent-delete guards, native row/bulk workflows, simultaneous GA4/Ads with distinct send_to targets and one shared SDK, plus legacy Ads migration. PHP 7.4 separately passed production syntax checks and validation/minifier smoke tests; this is not a repeat of the full older WordPress/PHP matrix.

On the isolated clean ZIP installation, tests/trash-lifecycle.php passed nine additional WordPress assertions for safe Trash/restore, locked-entry preflight, Empty Trash and preserving active snippets. Run it only with an initially empty CodeSlot Trash: `docker compose run --rm cli --path=/var/www/html/clean eval-file wp-content/plugins/codeslot/tests/trash-lifecycle.php`.
