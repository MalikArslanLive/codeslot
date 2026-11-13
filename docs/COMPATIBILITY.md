# Requirements and verified compatibility

CodeSlot 1.0.0 requires **WordPress 6.5+ and PHP 7.4+**. Prefer maintained runtimes. Other plugins can require newer PHP; CodeSlot compatibility does not override their requirements.

The completed [69-job quality run](https://github.com/MalikArslanLive/codeslot/actions/runs/43442468901) on `4741eef` passed the matrix below. Each runtime job activates source, installs the production ZIP, runs actual WordPress integration, converts to multisite and checks recovery/uninstall. These are 56 matrix jobs across eight targets; `latest` can duplicate a pinned stable version and does not represent an additional unique release.

| WordPress target | PHP 7.4 | 8.0 | 8.1 | 8.2 | 8.3 | 8.4 | 8.5 |
|---|---|---|---|---|---|---|---|
| 6.5 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.6 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.7 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.8.3 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 6.9 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 7.0 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| 7.1.4 | Passed | Passed | Passed | Passed | Passed | Passed | Passed |
| latest | Passed | Passed | Passed | Passed | Passed | Passed | Passed |

Pinned 6.5/6.6/6.7 targets are initial releases, not claims about every subsequent patch. Local stable was WordPress 7.1.4. Nightly source integration also passed in a separate non-blocking job; this does not certify future versions or every beta/RC.

## Servers, execution modes and operating systems

All HTTP suites use WordPress 7.1.4 with disposable fixtures. Each checks Header/Body/Footer, page/post/product/public CPT and public-term isolation, duplicates, conditions/exclusions and shortcodes.

| Server | PHP | Actual execution mode | Result |
|---|---|---|---|
| Apache 2.4.69 | 8.3.36 | apache2handler / mod_php | Passed 108 +27 assertions |
| Nginx 1.30.6 | 8.3.36 | fpm-fcgi / PHP-FPM | Passed 108 +27 assertions |
| OpenLiteSpeed 1.9.4 | 8.3.36 | litespeed / LSAPI | Passed 108 +27 assertions |
| PHP CLI tooling | 8.5.12 locally; 7.4–8.5 in CI | cli | Passed tooling/runtime jobs |

Apache/FPM containers use Debian 13; OLS uses Ubuntu 26.04.1. Hosted runners use Ubuntu 24.04. These are distinct from the PHP versions, SAPIs and web servers. LiteSpeed Enterprise, CGI and Windows/macOS are not tested.

An earlier OLS PHP8.2.35 run passed with the smaller four-companion stack. The current full stack's Contact Form 7 dependencies require PHP >=8.3; that combination on LSAPI8.2 failed and is unsupported for that companion version. OLS workers run as www-data to match shared WordPress file ownership.

## Themes and companion plugins

- Twenty Twenty-Five 1.5, Twenty Twenty-Four 1.6, Twenty Twenty-Three 1.7 and Storefront 4.6.2: 108 HTTP isolation assertions passed per theme.
- Twenty Twenty-One 1.0.0 GitHub snapshot: failed on a Woo product warning; not supported by this result. The historical 2.9 result is a different version.
- WooCommerce 11.3.0: native product fields and individual product output passed; no complete payment/checkout certification.
- Classic Editor 1.7.0 and Gutenberg 24.4.0: native meta box integration passed.
- Elementor 4.4.0: preview isolation passed; fields remain in the native WordPress editor, not its canvas.
- ACF, Beaver Builder Lite, Contact Form 7, Yoast SEO and Polylang were active in hosted stacks; this is not certification of all their features.
- Premium Elementor Pro, Bricks, Divi, WPML and other licensed products: not tested; no unauthorized downloads.

Public post types/taxonomies are supported; attachments/private internal types are excluded. Multisite data is per site with super-admin management. Themes must provide WordPress insertion hooks. Device conditions are advisory; caches need correct variation and purging. PHP error recovery is best effort, not a sandbox.
