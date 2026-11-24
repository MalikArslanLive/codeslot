# Administration and local tools

Version remains 1.0.0. All included features are free.

## Lists, locks and versions

Select snippets and click Apply; there is no extra bulk-confirm checkbox. Normal deletion moves to WordPress Trash. Permanent Delete is available only for trashed snippets and asks for confirmation. All, Active, Inactive and Trash views show counts. Quick Edit loads saved metadata and the same ID-based untitled fallback as the list. An accessible status switch updates only activation, preserving source/conditions; locks and one-time-task restrictions apply. ID, Author, Location, Created, Last Updated, Categories, Tags, Shortcode, Code Type, Priority, Note and Status have separate columns. Status is last. Native WordPress Screen Options stores column visibility, page size and sorting for each user; the selection checkbox keeps its own column. Wide desktop tables scroll inside their container and mobile rows stack with field labels.

Lock prevents ordinary CodeSlot edits, status changes, history clearing and deletion until the Lock checkbox is unchecked and saved in the full editor. Active locked code continues running. Locks do not constrain a privileged administrator with database/filesystem access.

Only actual saved source/configuration changes create a revision. Repeated unchanged saves—including unchanged untitled snippets—do not change history or the modified timestamp. Metadata and status changes also count. New revision cards list changed fields; comparison headings are Previous Saved Version (left) and Current Version (right). Existing history is retained.

Clear revision history warns and deletes CodeSlot snapshots plus native WordPress child revisions, retaining the current snippet. It does not erase independent database backups or hosting logs.

Header/Body/Footer descriptions explain the head, wp_body_open and wp_footer positions, including individual content/term fields. Theme hooks are still required.

## Backup

Snippets-only JSON retains the existing schema. Full backup additionally includes global and individual slots, settings, virtual files and pixel settings. It excludes WordPress content, logs, saved revisions and one-time execution claims. Backup files can contain authored secrets: protect them.

Select sections explicitly when restoring. Selected global/individual/file/pixel sections offer Keep existing or Replace from backup; unselected sections remain unchanged. Individual records map by post type + slug/path or taxonomy + term slug; missing or unsupported matches are skipped and counted. Exact duplicate snippets are skipped. New snippets import inactive and unlocked; conflicting names use the chosen Skip/Keep both/Replace policy. Current site-wide Safe Mode is preserved. New entries are disabled; replaced entries default to disabled or can preserve their existing state. Worker registration is off by default and can retain the current setting in preserve mode. Restoring settings keeps uninstall deletion off. Review deliberately before enabling. This is not a transaction or whole-site restore. Import limit: 5 MiB, 200 snippets, 5000 individual records.

## Tools

System Info gathers actual local WordPress/theme/plugin/PHP/database/SAPI information. Copy/download is explicit; reports are never sent automatically. Identifying URLs and filesystem paths require the Include details checkbox. Credentials and snippet source are excluded in either mode. Review names and identifying information before sharing.

Logs hold the last 100 CodeSlot management and snippet-error events, with time, user ID and snippet ID. They exclude source, credentials and raw error messages. Clear is permanent and asks a warning; download is explicit. These are not full PHP/server logs. Individual snippet error state remains on the affected editor.

The four starter templates are available in Library; Tools contains System Info, Logs and Recovery. The System Info details checkbox and Refresh report action share a responsive toolbar.

Settings Safe Mode works when the dashboard is accessible. Emergency `define( 'CODESLOT_SAFE_MODE', true );` belongs in wp-config.php before WordPress loads and overrides Settings. Both suppress new CodeSlot execution/output. Existing caches, service workers and earlier code side effects require separate recovery.

## Virtual public files

File Editor allows only ads.txt, app-ads.txt, robots.txt and service-worker.js. No physical file is created or overwritten. Physical files take precedence. Custom robots directives append to WordPress output; they do not protect sensitive data. Standard pretty-permalink routing to WordPress is required. A proxy/CDN or static server rule can intercept these URLs; verify the public response after saving and purge caches.

Service-worker content is public privileged JavaScript. Automatic registration is off by default and requires a secure browser context. Disabling the plugin/Safe Mode cannot unregister previously installed workers or clear their caches.

## Conversion pixels

Meta, Google Analytics 4, Google Ads, Google Tag Manager, Pinterest, TikTok and Snapchat accept public tracking IDs, never account secrets. Each integration and event is opt-in. Enabled base tags load automatically. With optional Wait for marketing consent selected, no vendor script loads until permission is granted. Base/click events need no companion plugin. WooCommerce view-content/add-to-cart/checkout/paid-purchase events are optional integrations and remain included.

If the optional consent gate is selected, an existing consent solution or reviewed integration grants consent:

```js
window.dispatchEvent(new CustomEvent('codeslot:consent', {
  detail: { marketing: true }
}));
```

Alternatively set `window.CodeSlotConsent = {marketing: true}` before the head loader runs. Send marketing:false to stop further CodeSlot events. Existing SDKs/cookies require consent-manager handling after withdrawal; this is not a consent banner or legal-compliance guarantee. Duplicate trackers installed elsewhere can duplicate events.

Paid purchase payloads require an authorized order-received URL/key (and matching logged-in customer where applicable). They include value/currency, product IDs and a site-specific opaque event ID, never contact details. Purchase retries are deduplicated in the current browser tab/session where storage is available. Browser blocking or closing a tab can prevent browser event delivery. Google Ads purchases require an AW- ID and conversion label. Other providers' accounts must be independently configured.

## Early hook recipes

Normal query-ready PHP behavior is preserved. The explicit bootstrap PHP location runs at init priority20, including login/AJAX/REST/cron/CLI requests, and supports no query conditions. Templates needing early filters use this mode and remain inactive until deliberately saved/enabled. Constants required before WordPress/plugin load stay manual wp-config.php guides and cannot be activated as library snippets. Review privileged PHP before activation and keep a backup.

## Editor and library layout

The green CodeSlot interface keeps editing controls together and adapts to mobile screens. Saved untitled snippets use the same ID-based title in Quick Edit and the full editor. JavaScript/CSS/PHP wrapper hints are read-only and stay outside saved code. Choose HTML when pasting a complete embed containing script/style tags; JavaScript and CSS bodies are wrapped by CodeSlot.

The library shows all 142 cards on one page. Search and category filters update the visible cards immediately; Clear filters restores the whole collection.

## Tracking IDs and verification

Each provider card shows an example ID format and setup hint. Google Tag Manager uses a GTM- container ID; GA4/Google Ads use G-/AW-/GT- IDs in their own card. Publish tags and custom-event triggers in the GTM container for the selected codeslot_page_view, codeslot_view_content, codeslot_add_to_cart, codeslot_begin_checkout, codeslot_purchase and codeslot_click dataLayer events.

Selected events are sent only when the integration has an ID, is enabled, has consent and its trigger occurs. Product/cart/checkout/purchase triggers use WooCommerce. Non-AJAX cart submits and classic/block AJAX events are covered. Authorized paid orders provide purchase value/currency and an opaque event ID; session deduplication avoids repeated purchase sends.

To verify Meta receipt, open Events Manager → your data source → Test Events, visit a test page with your Pixel ID and marketing consent, and perform the selected actions. Verify the actual received event names there. Browser extensions, connectivity and account configuration affect delivery. You can inspect window.CodeSlotPixels.getStatus() on the frontend for consent, SDK load state and the last queued events; queued/loaded is not a provider receipt confirmation.

The opt-in live Meta check and its measured network results are documented in [TESTING.md](TESTING.md). Tracking requires provider-account configuration and, if its optional gate is selected, the real site's consent integration; a successful local network check cannot establish reporting or attribution for another site.

## Placement, devices and delivery

Use Auto Insert, Shortcode or On Demand; the saved location determines execution. Searchable groups cover global, page/content, eCommerce, PHP and CSS-selector positions. Admin Header/Footer runs only in wp-admin. Normal PHP Run Everywhere runs at wp/admin_init and excludes AJAX, REST, cron and CLI. The explicit Early hooks location uses init on all request types without query conditions.

Any device is the default. Desktop/Mobile uses wp_is_mobile(), including its client-hint/user-agent behavior; it is not an exact physical-device detector. Caches must vary accordingly. Start is inclusive and End exclusive; both use the site timezone in the editor and UTC timestamps in storage. Empty dates have no restriction. Execution occurs only when a request/action happens, never automatically at the scheduled time. Run Once also checks its device/time restrictions before claiming a task.

Static-file loading is opt-in for bare JavaScript/CSS. CodeSlot generates content-addressed .js/.css files under WordPress uploads/codeslot-assets; it never generates PHP files. If writing uploads fails, inline output remains available. Generated assets are public and may remain cached/accessible after disabling a snippet. Explicit delete-data uninstall removes only CodeSlot's hashed assets. Full backup stores authored source/configuration; generated assets are recreated as needed.

Compact JavaScript output uses bundled JShrink 1.7.0 (BSD-3-Clause). Editable source/revisions remain unchanged; compaction does not transpile JavaScript. Compaction is unavailable for other code types. If the minifier cannot process code, original JavaScript is used. Review the browser output after enabling it.

CSS-selector placement supports before, after, prepend, append and replace. Selectors target the initial frontend DOM; each matching element receives the snippet once. Insertions follow priority/ID order. Replacement can remove existing content; use a specific selector. CodeSlot does not observe later AJAX mutations. JavaScript inserted by this feature executes with normal page privileges.

Bulk actions require a selection and preflight missing/locked/non-activatable entries before writes. Individual/bulk status updates preserve source bytes, including older wrapper-containing JS/CSS. Deactivation does not require broken source to validate. New/edited JavaScript and CSS bodies still reject outer wrapper tags; choose HTML for complete embeds. Ordinary validation appears on the relevant screen, while capability/nonce violations remain rejected. Invalid pixel form values are bounded, safely redisplayed and never activated or saved as valid settings. Validation event codes are logged without submitted source/IDs.

## Trash and restore

Row and bulk Trash actions preserve source, configuration and history while preventing execution. A POST-protected Undo restores the affected snippets inactive. Trash offers Restore, Delete Permanently and confirmed Empty Trash; permanent deletion cannot target an ordinary active/inactive snippet. Restore remains inactive even if another plugin changes WordPress's default untrash status. Locked snippets must be unlocked before trashing/deleting; a locked trashed snippet can be restored inactive before unlocking it. WordPress controls automatic Trash retention/purging (normally 30 days). If global Trash retention is disabled, CodeSlot refuses to turn a Trash action into immediate permanent deletion.

## Independent GA4 and Google Ads

Google Analytics 4 and Google Ads have independent cards, enabled states, public IDs and event selections. GA4 accepts G- measurement IDs or connected GT- tags. Ads uses AW- IDs; selected purchase conversions require the Google Ads conversion label. Both can run together through one shared gtag SDK, with optional consent gating. GA4 events target the GA4 ID; Ads purchase conversions target AW-ID/label. The Ads card offers Purchase with its conversion label; other events can be configured through the platform or GTM. Google Tag Manager remains a separate container integration; avoid configuring the same trackers twice.

Each provider card validates its own current ID format. Provider reporting/attribution still requires account-side verification; SDK calls alone do not certify reporting.

JavaScript/CSS imports require inner source without script/style wrappers, consistently with the editor. Choose HTML for a complete embed.

Invalid snippet saves preserve bounded editor values in a private, per-user and snippet-matched draft for correction. Drafts expire after five minutes and are not executed, exported or included in event logs. Saved source/history remains unchanged when validation fails.

## Current tracking and import interface

Enabled IDs install base tags automatically. Optional Wait for marketing consent delays SDK loading. See [provider event matrix and setup](TRACKING.md) for exact mappings and public implementation review references. Upload .json files for snippets and full restore; the paste textarea is removed. Shortcode mode displays the saved shortcode with Copy in the editor and list column. Auto Insert uses one searchable Location dropdown; the old global Usage line is removed.

## Missing plugins and references

WooCommerce locations are visible/disabled without WooCommerce and retain saved selections. Missing references in either Include or Exclude pause the complete snippet. The editor/list distinguish that paused execution from the saved Active switch. Use Required plugins (optional) for custom integrations; removed requirements are retained until explicitly edited. See [the dependency lifecycle guide](DEPENDENCIES.md).

## Final validation and restore feedback

Full restore requires a selected section and reports that nothing was restored when none is selected. The preview gives the same guidance before submission. Global/individual code fields share a 1 MiB UTF-8 limit with restore validation; invalid writes retain existing code. Malformed condition JSON, explicit null rules/requirements and nonpositive term identities are rejected. See [Testing](TESTING.md) for current results.
