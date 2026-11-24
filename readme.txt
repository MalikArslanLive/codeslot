=== CodeSlot – Header, Body & Footer Code + Snippet Manager ===
Tags: code snippets, header, footer, custom code, scripts
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add Header, Body and Footer code site-wide or to individual pages, posts and products. Manage PHP, JavaScript, CSS and HTML snippets.

== Description ==

**Add code across your website or to one individual page, post or product.**

CodeSlot provides separate Header, Body and Footer fields for your entire site and for individual WordPress content items. Add a script to one landing page, a style to one post, or an embed to one WooCommerce product without adding it to every page.

Open an item in its native WordPress editor, find **CodeSlot — Header, Body & Footer**, paste your code, enable the required field and save. Each item has independent switches. Public custom post types have the same fields. Categories, tags and public custom taxonomy terms also have individual fields for their own archive pages.

A separate snippet manager lets you organize PHP, JavaScript, CSS, HTML, plain text, reusable content and shortcode content. Choose an insertion location, narrow it with conditions, and control when it runs.

All listed features are included under GPL-2.0-or-later. No paid upgrade is required.

= Main features =

* **Site-wide code:** independent global Header, Body and Footer editors.
* **Individual content:** separate code for one page, post, WooCommerce product, public custom post type or public taxonomy term archive.
* **Seven snippet types:** PHP, JavaScript, CSS, HTML, text, reusable content and shortcode content.
* **Flexible insertion:** automatic locations, generated shortcodes, On Demand, content/paragraph positions, supported WooCommerce hooks and CSS-selector positions.
* **Searchable conditions:** select content, public post types, terms and roles by name; combine AND/OR rules and exclusions.
* **Organized management:** categories, tags, priorities, search, filters, native Screen Options, Quick Edit and Bulk Edit.
* **Safe copying and Trash:** Duplicate as inactive creates inactive copies; originals keep their status. Trash preserves code until restore or permanent deletion.
* **Saved versions:** the last 30 actual changes, previous/current code comparison and inactive restoration. Unchanged saves create no extra version.
* **Execution controls:** device targeting, start/end schedules, locks, Safe Mode and explicit plugin requirements.
* **Delivery options:** public static JavaScript/CSS files and optional JavaScript compaction without changing saved source.
* **Local library:** 142 bundled recipes and setup guides to review and customize before activation.
* **JSON file backups:** snippet export/import and selected full CodeSlot backup restore, with duplicate detection, replacement choices and clear results.
* **Optional conversion pixels:** independent Meta, GA4, Google Ads, GTM, Pinterest, TikTok and Snapchat settings, with supported browser events.
* **Tools:** private system information, bounded local event logs, recovery guidance and virtual public-file configuration.

= Conditions and plugin availability =

Locations decide where code is inserted; conditions decide which requests or visitors qualify. Search at least two characters and choose a result. Only the URL-path condition needs a manually entered path, such as /contact/.

WooCommerce-only locations, conditions and commerce events remain visible but disabled when WooCommerce is unavailable. Saved unavailable references or declared required plugins pause execution without discarding the code or changing the saved Active preference. CodeSlot cannot infer every dependency inside arbitrary custom code.

= Optional tracking integrations =

Each provider is disabled by default. Enter its public ID and enable it to install its base tag from the frontend head. GA4 and Google Ads have separate cards and can run together. Google Ads purchase conversions need the matching conversion label; GTM requires published tags/triggers in its container.

Choose the supported events you need. WooCommerce supplies product, cart, checkout and authorized paid-order data. Optional marketing-consent gating waits for your consent solution before loading tags. Platform automatic measurement is separate from CodeSlot event selections. See External services below before enabling a provider.

= Documentation and support =

* [Getting started and feature guide](https://github.com/MalikArslanLive/codeslot)
* [Conditions guide for beginners](https://github.com/MalikArslanLive/codeslot/blob/main/docs/CONDITIONS.md)
* [Import and restore guide](https://github.com/MalikArslanLive/codeslot/blob/main/docs/RESTORE.md)
* [Tracking setup and event mappings](https://github.com/MalikArslanLive/codeslot/blob/main/docs/TRACKING.md)
* [Plugin dependency behavior](https://github.com/MalikArslanLive/codeslot/blob/main/docs/DEPENDENCIES.md)
* [Compatibility and test scope](https://github.com/MalikArslanLive/codeslot/blob/main/docs/COMPATIBILITY.md)
* [Report a reproducible issue](https://github.com/MalikArslanLive/codeslot/issues)

By [Malik Arslan](https://malikarslan.com/). Source code and development documentation are available on [GitHub](https://github.com/MalikArslanLive/codeslot).

== Installation ==

Download the installable ZIP from https://github.com/MalikArslanLive/codeslot/releases/latest. CodeSlot is distributed through GitHub.

1. In WordPress, open **Plugins → Add New → Upload Plugin** and choose the CodeSlot plugin ZIP. Alternatively, place its codeslot-header-body-footer-code-snippet-manager directory inside wp-content/plugins.
2. Install and activate CodeSlot. It requires WordPress 6.5+ and PHP 7.4+.
3. Open **CodeSlot → All Snippets**, or use the **Code Snippets** action link beneath CodeSlot on the Plugins screen.
4. For site-wide code, open **CodeSlot → Header, Body & Footer**. Enable each field you want to use and save.
5. For one page, post, product or public custom post type, open that item in its native WordPress editor. Find **CodeSlot — Header, Body & Footer**, enter code, enable the required field and save the item.
6. To target a category, tag or public custom taxonomy archive, edit that term and use its CodeSlot fields.
7. Review the resulting frontend page and purge relevant page/CDN caches after changes.

Code management requires manage_options, unfiltered_html and edit_plugins capabilities; multisite additionally requires super-admin. WooCommerce is optional and needed only for its own integrations. Elementor content uses the underlying WordPress item metadata; CodeSlot fields are edited in WordPress, not its canvas.


== Frequently Asked Questions ==

= Can code run on just one page or post? =

Yes. Each individual item has its own Header, Body and Footer fields and enable switches. Its saved code runs on that item's singular frontend URL. Products and other public custom post types work the same way. Term fields run on that term's own archive. Attachments and private internal post types are excluded.

= Should I use global fields, individual fields or a snippet? =

Use global fields for site-wide output, individual fields for one content item, and a snippet when you need reusable code, conditions, delivery settings or another insertion location. Normal output order is global fields, matching snippets in priority order, then individual fields. Avoid adding the same tracker in multiple places.

= Is a paid upgrade required? =

No. All documented CodeSlot features are included under GPL-2.0-or-later. External tracking services have their own accounts and terms.

= Which code type should I choose? =

Choose HTML for complete embeds containing script/style tags. JavaScript and CSS expect only the inner code because CodeSlot adds wrappers. PHP expects code without opening/closing PHP tags. Review code before activation.

= How do shortcodes and On Demand work? =

Select Shortcode, save the snippet, then copy its actual [codeslot id="123"] value from the editor or the optional All Snippets Shortcode column. Replace 123 with the generated ID; do not copy this example unchanged. On Demand uses the exact saved snippet name through the codeslot_manual WordPress action. PHP snippets use their supported execution modes instead of shortcode insertion.

= What is the difference between a location and a condition? =

A location selects the insertion point, such as Site-wide Header or Before main content. A condition narrows matching pages or visitors. Content/paragraph locations apply to individual main content, not archive listings. A condition cannot create a missing theme hook or turn a manual snippet into an automatic one.

= How do schedules and device rules work? =

Schedules use the site timezone. Start is inclusive, end is exclusive, and empty dates mean no time limit. Execution is checked on requests, not by a background cron task. Device rules rely on user-agent detection. Configure cache variation where needed and purge caches after changes; these rules are not access controls.

= What happens when I duplicate a snippet? =

Duplicate as inactive copies an active or inactive snippet into a new inactive entry. The original keeps its existing state. Review the copy before enabling it.

= When are revisions created? =

Only actual code or configuration changes create a saved version. Repeated unchanged saves do not fill the 30-version history. Previous Saved Version appears on the left and Current Version on the right. Restore returns the selected version inactive.

= Can I import a backup without creating duplicates? =

Exact snippet duplicates are skipped unchanged. Same-name variants use your Skip, Keep both or protected Replace choice. Replacement requires one unlocked, unambiguous match; an uploaded ID cannot choose a replacement target. Newly created entries remain inactive.

= Does a full restore replace existing code or enable Safe Mode? =

Choose the sections to restore and Keep existing or Replace for each supported section. Replacements default to disabled, or you can explicitly preserve existing enabled/disabled states. New entries always start disabled. Unselected sections remain unchanged, and site-wide Safe Mode keeps its current state. Settings restore keeps uninstall deletion off. Back up first: replacing enabled code while preserving its state can execute the changed code immediately.

Individual fields map existing content by post type plus slug/path, or taxonomy plus term slug. Missing matches are skipped and counted; WordPress posts/pages and physical files are not created or overwritten. No selected sections means nothing is restored, and the notice explains what to select. Result notices distinguish written, skipped, unchanged and unselected entries. Full restore is not a whole-site database transaction.

= What are the import and code limits? =

Uploaded JSON must be a genuine .json file no larger than 5 MiB, with at most 200 snippets per import and 5000 individual mappings in a full restore. Each snippet or individual/global code field accepts up to 1 MiB of UTF-8 source. Invalid submissions retain existing saved code. Full backups exclude revisions, logs, execution records and WordPress content itself.

= What does Load as static file do? =

For JavaScript/CSS, it generates a public content-addressed file in WordPress uploads, with inline fallback if uploads are unavailable. Existing files or caches may remain accessible after deactivation, so never put secrets in frontend code. Compact JavaScript output uses bundled JShrink; it does not transpile code, and saved source/revisions remain unchanged.

= Can PHP break my site, and how do I recover? =

PHP and scripts have full site privileges. Syntax validation and best-effort exception/fatal recovery cannot prevent every failure or reverse side effects. Keep backups and recovery access.

To suppress all CodeSlot execution and output, add `define( 'CODESLOT_SAFE_MODE', true );` to wp-config.php before WordPress loads. If necessary, rename the plugin directory or deactivate it with WP-CLI using --skip-plugins=codeslot. Correct or deactivate the offending code before deliberately leaving Safe Mode. Purge relevant caches.

= Are unsaved edits or autosaves executed? =

No. Individual fields use saved metadata, including authorized previews. Unsaved edits, autosaves and revisions do not change the executing individual code.

= Why is my saved code not appearing? =

Check its enable switch, Safe Mode, insertion method, matching conditions, schedule, device rules, declared dependencies and caches. Themes must call wp_head, wp_body_open and wp_footer for the corresponding slots. Missing body hooks do not silently move code to another location. See the linked conditions and dependency guides for troubleshooting.

= What happens on uninstall? =

Data is retained by default. You can explicitly enable deletion in CodeSlot Settings before uninstalling. Back up first. Opt-in deletion removes site-local CodeSlot data and generated owned assets; it does not reverse prior code side effects, remove physical files configured outside CodeSlot, or unregister existing service workers. Multisite respects each site's deletion choice.

== Advanced usage ==

Normal frontend PHP starts after query setup, and admin PHP starts during admin initialization. The explicit Early hooks mode runs at init across request types, including login, AJAX, REST, cron and CLI; query conditions are unsupported there. Earlier plugin-load constants belong in wp-config.php. Run Once tasks stay inactive and require their separate confirmed action; claimed tasks are not automatically retried.

Use Required plugins for custom code that depends on another plugin. Missing saved references in either Include or Exclude pause the entire snippet, including OR/negated rules. Restore the dependency or deliberately edit the requirement; CodeSlot does not substitute another insertion location.

CSS-selector insertion operates on the initial DOM after readiness and does not observe later AJAX mutations. Replacing an element can remove existing content. Virtual public files use fixed routes for ads.txt, app-ads.txt, service-worker.js and custom robots.txt directives; physical files take precedence. Pretty permalinks and routing requests through WordPress are required. Service-worker registration is off by default.

See the linked guides for complete condition examples, event mappings, restore policies, supported environments and security limits. Report sensitive vulnerabilities privately as described in the repository's SECURITY.md.

== External services ==

Conversion Pixels is optional and disabled by default. Enabling a provider explicitly installs its base browser tag on frontend pages and shares browser data with that service. Base tags initialize even without optional events. Selected page/click events and WooCommerce commerce data (product IDs, quantities, prices, currency and an opaque transaction identifier) are sent when their triggers occur. CodeSlot does not add customer contact data or advanced-matching fields.

Vendor SDK requests disclose the visitor's IP address/user agent and may include page URLs/referrers or set provider cookies. SDKs may apply automatic measurement or matching configured in the provider account. GTM loads the published container and its administrator-configured tags. Avoid duplicate tracking configurations.

Optional marketing-consent gating prevents vendor loading until your consent solution grants marketing permission. A false signal stops new CodeSlot events; your consent solution must also manage existing vendor scripts/cookies. CodeSlot does not provide a cookie banner. Configure appropriate privacy notices, consent behavior and provider accounts for your website. Normal CodeSlot administration and snippet management do not send developer telemetry.

* Meta: SDK connect.facebook.net; events www.facebook.com. [Terms](https://www.facebook.com/legal/terms) and [Privacy](https://www.facebook.com/privacy/policy/).
* Google Analytics, Google Ads and Google Tag Manager: SDKs www.googletagmanager.com; tracking can use Google collection/advertising domains. Published GTM containers may load additional services chosen by their administrator. [Terms](https://policies.google.com/terms) and [Privacy](https://policies.google.com/privacy).
* Pinterest: SDK s.pinimg.com; requests can use Pinterest domains. [Terms](https://policy.pinterest.com/en/terms-of-service) and [Privacy](https://policy.pinterest.com/en/privacy-policy).
* TikTok: SDK analytics.tiktok.com. [Terms](https://www.tiktok.com/legal/page/row/terms-of-service/en) and [Privacy](https://www.tiktok.com/legal/page/row/privacy-policy/en).
* Snapchat: SDK sc-static.net; events can use tr.snapchat.com. [Terms](https://www.snap.com/terms) and [Privacy](https://values.snap.com/privacy/privacy-policy).

== Changelog ==

= 1.0.0 =

* Initial public release with site-wide and individual Header, Body and Footer code fields.
* Add snippet types, searchable conditions/locations, priorities, schedules, device rules and declared plugin dependencies.
* Add native Screen Options, Quick/Bulk Edit, inactive copies, Trash/Restore, locks and changed-only saved versions.
* Add JSON uploads, duplicate detection, protected replacement choices and section-wise restore notices.
* Add head-loaded optional conversion integrations and authorized WooCommerce commerce events.
* Include the local library, virtual public-file tools, diagnostics, Safe Mode and best-effort PHP recovery.
* Include linked beginner and advanced documentation.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.

== Credits ==

CodeSlot is licensed GPL-2.0-or-later. JavaScript compaction bundles JShrink by Robert Hafner under the BSD 3-Clause license; its notice and license are included in includes/minifier. Administration uses WordPress's bundled editor libraries.
