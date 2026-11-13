# Compatibility evidence

CodeSlot targets WordPress 6.8+ and PHP 7.4+. Tested runtime combinations are listed below; these do not certify every third-party configuration. Tests use disposable sites, not a production deployment. Repository source and ZIP are release candidates pending final gate reporting.

## WordPress/PHP

GitHub-hosted jobs on commit `dd2585c` passed all 21 runtime combinations below, including source activation, installed production ZIP, actual WordPress integration, multisite, constant recovery and uninstall checks. The overall run was canceled after these jobs completed because Compose provisioning needed correction; canceled browser/Plugin Check jobs are not passes. The corrected full run 43392868687 subsequently passed all 33 jobs; see docs/TEST-REPORT.md.

| WordPress | PHP 7.4 | 8.0 | 8.1 | 8.2 | 8.3 | 8.4 | 8.5 |
|---|---|---|---|---|---|---|---|
| 6.8.3 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.9 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| latest stable (7.1.4 during session) | Passed | Passed | Passed | Passed | Passed | Passed | Passed |

Local runtime: PHP 8.3.36, WordPress 7.1.4, MariaDB 11.4. PHPUnit/WPCS/PHPStan/dependency checks also ran in separate CI jobs for PHP 7.4–8.5. No claim is made for PHP older than 7.4, WordPress older than 6.8, or every intermediate patch release. PHP 7.4 and 8.0 are legacy environments; test compatibility does not make an end-of-life runtime maintained.

Nightly WordPress: a separate non-blocking workflow currently downloads nightly and lints source. This is a provisioning probe, **not a successful nightly runtime compatibility test**. No beta/RC installation is certified.

## Local theme and plugin checks

| Combination | Result | Actual scope |
|---|---|---|
| Twenty Twenty-Five 1.5 | Passed | Block theme, 99 isolation assertions |
| Twenty Twenty-One 2.9 | Passed | Classic theme, 99 isolation assertions |
| Hello Elementor 3.5.2 | Passed | 99 isolation assertions |
| Storefront 4.6.2 | Passed | 99 isolation assertions |
| Missing-body-hook fixture theme | Passed negative test | Body code suppressed; compatibility warning recorded |
| WP Super Cache 3.1.5 | Passed targeted isolation | Real cache files generated; cold and warm marker isolation; purge still required after code edits |
| WooCommerce 11.3.0 | Passed targeted integration | Product body isolation and native product code fields; no checkout transaction test |
| Classic Editor 1.7.0 | Passed browser | Native page code fields with default editor switching |
| Gutenberg bundled in 7.1.4 | Passed browser | Native meta box pane, three code fields |
| Gutenberg plugin 24.4.0 | Passed targeted browser + isolation | Native meta box fields and 99 source assertions |
| Elementor 4.4.0 | Passed targeted preview isolation | Live editor preview iframe retains Page A slots exactly once; Page B absent; full canvas authoring not certified |
| ACF 6.8.11 | Partial | Active in tested stack; field-specific scenarios not exercised |
| Beaver Builder Lite 2.11.1.2 | Partial | Active in tested stack; builder editing not certified |
| Contact Form 7 6.2.5 | Partial | Active in tested stack; submissions not exercised |
| Yoast SEO 28.8 | Partial | Active in tested stack; SEO semantics not audited |
| Rank Math 1.0.282 | Passed targeted isolation | 99 source assertions; tested with Yoast deactivated |
| Polylang 3.9.2 | Partial | Active in tested stack; translation workflows not exercised |
| Elementor Pro, Bricks, Divi, WPML | Not tested | Legitimate licenses unavailable; nothing downloaded from unauthorized sources |
| Twenty Twenty-Four / Twenty Twenty-Three | Not tested | Installed, not included in executed theme-switch isolation suite |

WooCommerce native/classic editing is supported through WordPress meta boxes. Experimental React product editors may omit them and are not certified. Elementor fields are managed in the native WordPress editor, not its canvas. Public post types/taxonomies are supported; private internal types and attachments are excluded. Multisite is per-site with super-admin management; no shared network snippet library exists.

Page caches must vary or bypass role/login/device conditions and be purged after code changes and recovery. CodeSlot cannot rewrite another cache's policies. Device targeting uses advisory wp_is_mobile user-agent hints, not reliable tablet classification. No premium, cloud or proprietary feature parity is claimed.
