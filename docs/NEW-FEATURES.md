# Administration and local tools

Version remains 1.0.0. All included features are free.

## Lists, locks and versions

Select snippets and click Apply; there is no extra bulk-confirm checkbox. Normal deletion moves to WordPress Trash. Permanent Delete is available only for trashed snippets and asks for confirmation. All, Active, Inactive and Trash views show counts. Quick Edit loads saved metadata and the same ID-based untitled fallback as the list. An accessible status switch updates only activation, preserving source/conditions; locks and one-time-task restrictions apply. ID, Author, Location, Created, Last Updated, Categories, Tags, Shortcode, Code Type, Priority, Note and Status have separate columns. Status is last. Native WordPress Screen Options stores column visibility, page size and sorting for each user; the selection checkbox keeps its own column. Wide desktop tables scroll inside their container and mobile rows stack with field labels.

Lock prevents ordinary CodeSlot edits, status changes, history clearing and deletion until explicitly unlocked in the full editor. Active locked code continues running. Locks do not constrain a privileged administrator with database/filesystem access.

Only actual saved source/configuration changes create a revision. Repeated unchanged saves—including unchanged untitled legacy snippets—do not change history or the modified timestamp. Metadata and status changes also count. New revision cards list changed fields; comparison headings are Previous Saved Version (left) and Current Version (right). Existing history is retained.

Clear revision history warns and deletes CodeSlot snapshots plus native WordPress child revisions, retaining the current snippet. It does not erase independent database backups or hosting logs.

Header/Body/Footer descriptions explain the head, wp_body_open and wp_footer positions, including individual content/term fields. Theme hooks are still required.

## Backup

Snippets-only JSON retains the existing schema. Full backup additionally includes global and individual slots, settings, virtual files and pixel settings. It excludes WordPress content, logs, saved revisions and one-time execution claims. Backup files can contain authored secrets: protect them.

Select sections explicitly when restoring. Selected global/individual/file/pixel data replaces current data. Individual records map by post type + slug/path or taxonomy + term slug; missing/private/unsupported matches are skipped and counted. Snippets become new inactive copies. Safe Mode is enabled, all restored slots/pixels/files are disabled, service-worker registration is off and uninstall deletion is off. Review deliberately before enabling. This is not a transaction or whole-site restore. Import limit: 5 MiB, 200 snippets, 5000 individual records.

## Tools

System Info gathers actual local WordPress/theme/plugin/PHP/database/SAPI information. Copy/download is explicit; reports are never sent automatically. Identifying URLs and filesystem paths require the Include details checkbox. Credentials and snippet source are excluded in either mode. Review names and identifying information before sharing.

Logs hold the last 100 CodeSlot management and snippet-error events, with time, user ID and snippet ID. They exclude source, credentials and raw error messages. Clear is permanent and asks a warning; download is explicit. These are not full PHP/server logs. Individual snippet error state remains on the affected editor.

The four starter templates are available in Library; Tools contains System Info, Logs and Recovery. The System Info details checkbox and Refresh report action share a responsive toolbar.

Settings Safe Mode works when the dashboard is accessible. Emergency `define( 'CODESLOT_SAFE_MODE', true );` belongs in wp-config.php before WordPress loads and overrides Settings. Both suppress new CodeSlot execution/output. Existing caches, service workers and earlier code side effects require separate recovery.

## Virtual public files

File Editor allows only ads.txt, app-ads.txt, robots.txt and service-worker.js. No physical file is created or overwritten. Physical files take precedence. Custom robots directives append to WordPress output; they do not protect sensitive data. Standard pretty-permalink routing to WordPress is required. A proxy/CDN or static server rule can intercept these URLs; verify the public response after saving and purge caches.

Service-worker content is public privileged JavaScript. Automatic registration is off by default and requires a secure browser context. Disabling the plugin/Safe Mode cannot unregister previously installed workers or clear their caches.

## Conversion pixels

Meta, Google Analytics 4, Google Ads, Google Tag Manager, Pinterest, TikTok and Snapchat accept public tracking IDs, never account secrets. Each integration and event is opt-in. With no marketing consent, no CodeSlot vendor script/request is loaded. Base/click events need no companion plugin. WooCommerce view-content/add-to-cart/checkout/paid-purchase events are optional integrations and remain included.

An existing consent manager or your own reviewed code must explicitly grant consent:

```js
window.dispatchEvent(new CustomEvent('codeslot:consent', {
  detail: { marketing: true }
}));
```

Alternatively set `window.CodeSlotConsent = {marketing: true}` before footer scripts. Send marketing:false to stop further CodeSlot events. Existing SDKs/cookies require consent-manager handling after withdrawal; this is not a consent banner or legal-compliance guarantee. Duplicate trackers installed elsewhere can duplicate events.

Paid purchase payloads require an authorized order-received URL/key (and matching logged-in customer where applicable). They include value/currency, product IDs and a site-specific opaque event ID, never contact details. Purchase retries are deduplicated in the current browser tab/session where storage is available. Browser blocking/closed tabs can prevent events; no server-side conversion API or full payment certification is claimed. Google Ads purchases require an AW- ID and conversion label. Other providers' accounts must be independently configured.

## Early hook recipes

Normal query-ready PHP behavior is preserved. The explicit bootstrap PHP location runs at init priority20, including login/AJAX/REST/cron/CLI requests, and supports no query conditions. Templates needing early filters use this mode and remain inactive until deliberately saved/enabled. Constants required before WordPress/plugin load stay manual wp-config.php guides and cannot be activated as library snippets. Privileged PHP remains arbitrary code, not a sandbox.

## Editor and library layout

The green CodeSlot interface keeps editing controls together and adapts to mobile screens. Saved untitled snippets use the same ID-based title in Quick Edit and the full editor. JavaScript/CSS/PHP wrapper hints are read-only and stay outside saved code. Choose HTML when pasting a complete embed containing script/style tags; JavaScript and CSS bodies are wrapped by CodeSlot.

The library shows all 142 cards on one page. Search and category filters update the visible cards immediately; Clear filters restores the whole collection.

## Tracking IDs and verification

Each provider card shows an example ID format and setup hint. Google Tag Manager uses a GTM- container ID; GA4/Google Ads use G-/AW-/GT- IDs in their own card. Publish tags and custom-event triggers in the GTM container for the selected codeslot_page_view, codeslot_view_content, codeslot_add_to_cart, codeslot_begin_checkout, codeslot_purchase and codeslot_click dataLayer events.

Selected events are sent only when the integration has an ID, is enabled, has consent and its trigger occurs. Product/cart/checkout/purchase triggers use WooCommerce. Non-AJAX cart submits and classic/block AJAX events are covered. Authorized paid orders provide purchase value/currency and an opaque event ID; session deduplication avoids repeated purchase sends.

To verify Meta receipt, open Events Manager → your data source → Test Events, visit a test page with your Pixel ID and marketing consent, and perform the selected actions. Verify the actual received event names there. Browser extensions, connectivity and account configuration affect delivery. You can inspect window.CodeSlotPixels.getStatus() on the frontend for consent, SDK load state and the last queued events; queued/loaded is not a provider receipt confirmation.

The opt-in live Meta check and its measured network results are documented in [TESTING.md](TESTING.md). Tracking still requires the real site's consent setup and provider-account configuration; a successful local network check cannot establish reporting or attribution for another site.

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

Google Analytics 4 and Google Ads have independent cards, enabled states, public IDs and event selections. GA4 accepts G- measurement IDs or connected GT- tags. Ads uses AW- IDs; selected purchase conversions require the Google Ads conversion label. Both can run together through one shared gtag SDK loaded after marketing consent. GA4 events target the GA4 ID; Ads purchase conversions target AW-ID/label. Other selected Ads events are emitted as tag events and require appropriate account configuration. Google Tag Manager remains a separate container integration; avoid configuring the same trackers twice.

Existing combined-card G-/GT- settings remain Analytics settings. An existing AW- setting maps to the Ads card while preserving its enabled state, events and label. Editing the new form validates each ID against its own card. Provider reporting/attribution still requires account-side verification; SDK calls alone do not certify reporting.

CodeSlot JSON imports also preserve older wrapped JavaScript/CSS source in inactive, unlocked copies. This permits existing backups to round-trip while ordinary editing still guides new embeds to HTML.

Invalid snippet saves preserve bounded editor values in a private, per-user and snippet-matched draft for correction. Drafts expire after five minutes and are not executed, exported or included in event logs. Saved source/history remains unchanged when validation fails.
