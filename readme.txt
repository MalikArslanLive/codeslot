=== CodeSlot – Header, Body & Footer Code + Snippet Manager ===
Tags: code snippets, header, footer, custom code, scripts
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Header, Body and Footer code site-wide or on individual pages, posts and WooCommerce products. Manage snippets and conditions.

== Description ==
By Malik Arslan: https://malikarslan.com/
Plugin website: https://github.com/MalikArslanLive/codeslot
WordPress.org: https://wordpress.org/plugins/codeslot/
Source and documentation: https://github.com/MalikArslanLive/codeslot

All included CodeSlot features are free under GPL-2.0-or-later.

Add code to a single page, post, WooCommerce product or public custom post type from its native WordPress editor. Each item has independent Header, Body and Footer fields with their own enable switches. Saved code appears on that item's own frontend URL. Categories, tags and public custom taxonomy terms have individual fields for their own archive pages.

For example, open a landing page, paste a script into its Footer field, enable that slot and update the page. The script appears on that landing page only. Global code can remain active. Output order is global slots, matching snippets, then individual code.

A separate local snippet manager supports PHP, JavaScript, CSS, HTML, plain text, reusable content and shortcode content, priorities, conditions, import/export, locks, local templates and the last 30 saved versions with code comparison. Visual snippets can also run before or after a selected numbered paragraph. Explicit Run Once PHP tasks stay inactive, require confirmation, record output/status and cannot be automatically retried.

Executable code is privileged functionality. Access requires manage_options, unfiltered_html and edit_plugins; multisite additionally requires super-admin. PHP is not sandboxed. Review every snippet and keep backups and filesystem access.

This is a release candidate; see the repository compatibility and test reports for actual tested combinations. A minimum version expresses a target, not proof of every combination.

= Tools, library, files and pixels =

Browse 142 original local recipes and setup guides without an account. Executable templates open inactive for review. Manual wp-config.php guides are labeled and are not offered for activation.

Tools provides private System Info, copy/download, bounded local logs and recovery instructions. Identifying site URLs and paths are excluded unless explicitly requested. No report is sent automatically. Logs exclude source and credentials.

Virtual File Editor serves ads.txt, app-ads.txt and service-worker.js, and appends custom robots.txt directives. Physical files take precedence and are never overwritten. Server routing to WordPress and pretty permalinks are required. Service-worker registration is off by default; existing workers/caches require separate browser recovery.

Conversion Pixels supports Meta, Google, Pinterest, TikTok and Snapchat. All integrations are off by default and no vendor script loads before explicit marketing consent. Optional WooCommerce product/cart/checkout/paid-purchase events run only with WooCommerce. These browser integrations are not a cookie banner, legal-compliance certification, payment test or server-side conversion API. Enabling a provider sends tracking events to that provider after consent; review its policies and configure your consent handling.

Full backup JSON additionally includes global/individual code slots, settings, files and pixel settings; revisions, logs, execution records and WordPress content are excluded. Restore selected sections replaces matching data, maps existing content by type/path or taxonomy/slug, reports missing matches, disables restored execution and enables Safe Mode. Back up first.

== Installation ==
1. Upload the codeslot directory to wp-content/plugins, or upload the generated plugin ZIP.
2. Activate CodeSlot.
3. Open CodeSlot -> Header, Body & Footer for global slots. Open CodeSlot -> All Snippets to manage snippets.
4. For individual code, open the page, post, WooCommerce product or public custom post type in its native editor. Find CodeSlot — Header, Body & Footer, enable the slot you want and save the item. Edit a category, tag or public custom taxonomy term to set code for its archive.

== Configuration ==
Use CodeSlot -> Header, Body & Footer for global slots. Open an item in its native WordPress editor for individual slots. Create snippets through CodeSlot -> Add New, give each a name, choose its type and location, review conditions and save. Only activate code you trust. Edit existing snippets using their name or the Edit link in All Snippets. Quick Edit updates metadata without changing code. Select page and Bulk Edit update selected snippets; Delete requires confirmation. Search, filters and first/previous/next/last pagination help navigate larger libraries. Unnamed legacy entries have an ID-based fallback title.

== Frequently Asked Questions ==

= Is everything free? =
Yes. All features included in CodeSlot are free under GPL-2.0-or-later: global and individual slots, every supported snippet type, conditions, priorities, shortcodes/manual insertion, import/export, saved versions and code comparison, paragraph insertion, confirmed one-time tasks, locking, templates and recovery controls.

= Can I add code to one page or post only? =
Yes. Open the native WordPress editor and use the CodeSlot — Header, Body & Footer meta box. Each Header, Body and Footer field has its own enable switch. The code runs only on that item's singular URL. Public custom post types and WooCommerce products have the same native fields. Categories, tags and public custom taxonomy terms have fields for their own archive URLs. Private internal post types and attachments are excluded.


= Can PHP break my site? =
Yes. PHP has full server privileges. Syntax validation and best-effort exception recovery cannot prevent every failure or reverse destructive side effects.

= How do I recover? =
Add define( 'CODESLOT_SAFE_MODE', true ); to wp-config.php before WordPress loads. This disables all CodeSlot output and execution. If necessary rename the plugin directory or deactivate with WP-CLI using --skip-plugins=codeslot. Fix the snippet before leaving Safe Mode.

= What if my theme omits wp_body_open? =
Body code does not run. After a frontend request with wp_footer, CodeSlot records a warning. It never silently moves body code to a different hook.

= Do previews execute unsaved code? =
No. Authorized previews use the item's saved metadata. Autosaves, revisions and unsaved editor changes do not alter individual code.

= Does it work with Elementor and WooCommerce? =
Individual code belongs to the underlying WordPress content item. Use the native WordPress editor to change its fields. See the compatibility report for tested versions and editor limitations.

= Does uninstall delete code? =
Data is retained by default. Enable the explicit deletion option in CodeSlot Settings before uninstalling to remove site-local CodeSlot data. Back up first.

== Changelog ==

= 1.0.0 =
* Remove redundant bulk-confirm checkbox; prefill untitled Quick Edit names, metadata and accessible status switches.
* Add confirmed permanent revision clearing, 142 local recipes/guides, System Info and local event logs.
* Add virtual public files, consent-gated browser pixels/WooCommerce events and selected full-backup restore.
* Add explicit all-request bootstrap PHP location for early hooks; query conditions are unsupported in this mode.
* Local snippet manager, global and individual slots, conditions and recovery controls.
* Responsive admin screens, visible Edit actions, fallback snippet titles and retained list filters.

== External services ==

Conversion Pixels is optional and disabled by default. After the administrator enables a provider and the visitor explicitly grants marketing consent, that provider's browser SDK receives selected page/click events and, when WooCommerce is available, opted-in commerce events. Browser requests inherently disclose IP address/user agent and may include page/referrer information or set provider cookies. CodeSlot does not add customer contact data or advanced matching. The administrator is responsible for provider-account configuration and consent handling, including third-party cookies after withdrawal. No SDK loads from CodeSlot before consent.

* Meta: https://www.facebook.com/legal/terms and https://www.facebook.com/privacy/policy/
* Google: https://policies.google.com/terms and https://policies.google.com/privacy
* Pinterest: https://policy.pinterest.com/en/terms-of-service and https://policy.pinterest.com/en/privacy-policy
* TikTok: https://www.tiktok.com/legal/page/row/terms-of-service/en and https://www.tiktok.com/legal/page/row/privacy-policy/en
* Snapchat: https://www.snap.com/terms and https://values.snap.com/privacy/privacy-policy
