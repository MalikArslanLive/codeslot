# Compatibility evidence

Version targets: PHP 7.4+ and WordPress 6.8+. Targets are not a certification of every combination. Only executed results below are evidence. No Elementor Pro, Bricks, Divi or WPML binaries/licenses were available; those are **not tested** and are not bundled.

Local runtime: PHP 8.3.36, WordPress 7.1.4 (downloaded from official WordPress API during this session), MariaDB 11.4. Actual commands/logs and current release status are in TESTING.md and docs/TEST-REPORT.md.

| Combination | Status | Evidence / limits |
|---|---|---|
| WP 7.1.4 / PHP 8.3.36 / MariaDB 11.4 | Passed local integration | Core, permissions, imports, recovery, ordering |
| Twenty Twenty-Five 1.5 (block) | Passed isolation | 99 HTTP source assertions |
| Twenty Twenty-One (classic) | Passed isolation | 99 HTTP source assertions; version recorded in test report |
| WooCommerce 11.3.0 | Passed native product fields + output | Product body isolation and browser field presence; no checkout transaction test |
| Elementor 4.4.0 | Partial | Active with isolation/browser tests; canvas workflow not certified |
| ACF 6.8.11 | Partial | Active in tested stack; ACF field scenarios not exercised |
| Beaver Builder Lite 2.11.1.2 | Partial | Active in tested stack; builder editing not certified |
| Contact Form 7 6.2.5 | Partial | Active in tested stack; form submission not exercised |
| Yoast SEO 28.8 | Partial | Active in tested stack; SEO output semantics not audited |
| Polylang 3.9.2 | Partial | Active in tested stack; translations not exercised |
| Classic Editor 1.7.0 | Passed initial browser check | Expanded editor configuration checks pending |
| Gutenberg (bundled) | In progress | Native meta box checks under investigation |
| Multisite | In progress | Dedicated isolated database run planned |
| PHP 7.4, 8.0, 8.1, 8.2, 8.4, 8.5 | Not tested locally | CI matrix, results must be checked separately |
| Previous WP 6.8/6.9 releases | Not tested locally | CI matrix, no inferred passes |
| WP nightly/beta/RC | Not tested | Separate non-blocking CI probe |
| Rank Math, standalone Gutenberg, caching plugins | Not tested yet | No compatibility assertion |
| Premium builders/plugins | Not tested | Legitimate licenses unavailable |

Page caches can serve HTML across condition boundaries if configured incorrectly. Vary or bypass caches for role/login/device conditions and purge after edits. CodeSlot cannot rewrite cache policies of unrelated software. Individual metadata uses WordPress object cache APIs; no persistent snippet result cache is introduced.

WooCommerce's classic/native product editor is supported by ordinary WordPress meta boxes. Experimental/new WooCommerce React product editors may omit WordPress meta boxes and are not certified. Private/internal post types and attachment screens intentionally do not receive code boxes. Network-wide snippet libraries and sync are not implemented.
