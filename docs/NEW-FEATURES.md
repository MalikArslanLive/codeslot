# Administration and local tools

Version remains 1.0.0. All included features are free.

## Lists, locks and versions

Select snippets and click Apply; there is no extra bulk-confirm checkbox. Permanent Delete still asks for a warning confirmation in the browser. Quick Edit loads saved metadata and the same ID-based untitled fallback as the list. An accessible status switch updates only activation, preserving source/conditions; locks and one-time-task restrictions apply. Author, creation date and categories accompany each name without adding crowded mobile columns.

Lock prevents ordinary CodeSlot edits, status changes, history clearing and deletion until explicitly unlocked in the full editor. Active locked code continues running. Locks do not constrain a privileged administrator with database/filesystem access.

Clear revision history warns and deletes CodeSlot snapshots plus native WordPress child revisions, retaining the current snippet. It does not erase independent database backups or hosting logs.

Header/Body/Footer descriptions explain the head, wp_body_open and wp_footer positions, including individual content/term fields. Theme hooks are still required.

## Backup

Snippets-only JSON retains the existing schema. Full backup additionally includes global and individual slots, settings, virtual files and pixel settings. It excludes WordPress content, logs, saved revisions and one-time execution claims. Backup files can contain authored secrets: protect them.

Select sections explicitly when restoring. Selected global/individual/file/pixel data replaces current data. Individual records map by post type + slug/path or taxonomy + term slug; missing/private/unsupported matches are skipped and counted. Snippets become new inactive copies. Safe Mode is enabled, all restored slots/pixels/files are disabled, service-worker registration is off and uninstall deletion is off. Review deliberately before enabling. This is not a transaction or whole-site restore. Import limit: 5 MiB, 200 snippets, 5000 individual records.

## Tools

System Info gathers actual local WordPress/theme/plugin/PHP/database/SAPI information. Copy/download is explicit; reports are never sent automatically. Identifying URLs and filesystem paths require the Include details checkbox. Credentials and snippet source are excluded in either mode. Review names and identifying information before sharing.

Logs hold the last 100 CodeSlot management and snippet-error events, with time, user ID and snippet ID. They exclude source, credentials and raw error messages. Clear is permanent and asks a warning; download is explicit. These are not full PHP/server logs. Individual snippet error state remains on the affected editor.

Settings Safe Mode works when the dashboard is accessible. Emergency `define( 'CODESLOT_SAFE_MODE', true );` belongs in wp-config.php before WordPress loads and overrides Settings. Both suppress new CodeSlot execution/output. Existing caches, service workers and earlier code side effects require separate recovery.

## Virtual public files

File Editor allows only ads.txt, app-ads.txt, robots.txt and service-worker.js. No physical file is created or overwritten. Physical files take precedence. Custom robots directives append to WordPress output; they do not protect sensitive data. Standard pretty-permalink routing to WordPress is required. A proxy/CDN or static server rule can intercept these URLs; verify the public response after saving and purge caches.

Service-worker content is public privileged JavaScript. Automatic registration is off by default and requires a secure browser context. Disabling the plugin/Safe Mode cannot unregister previously installed workers or clear their caches.

## Conversion pixels

Meta, Google Ads/Analytics, Pinterest, TikTok and Snapchat accept public tracking IDs, never account secrets. Each integration and event is opt-in. With no marketing consent, no CodeSlot vendor script/request is loaded. Base/click events need no companion plugin. WooCommerce view-content/add-to-cart/checkout/paid-purchase events are optional integrations and remain included.

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
