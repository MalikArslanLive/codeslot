# Tracking setup and supported events

All providers start disabled. Enter a public ID and enable its card to initialize the base tag automatically. Saving an enabled card is the administrator's opt-in to that external service. No account password or API token is used. Optional **Wait for marketing consent** holds every vendor SDK until the documented CodeSlot consent signal. Without that setting tags load automatically, unless an explicit false consent signal is already present. Withdrawal stops new CodeSlot events; existing SDKs/cookies need handling by the site's consent solution.

| Provider | CodeSlot event choices | Notes |
|---|---|---|
| Meta | PageView, ViewContent, AddToCart, InitiateCheckout, Purchase, CodeSlotClick | Click is a custom event, not a standard conversion. |
| GA4 / connected Google tag | page_view, view_item, add_to_cart, begin_checkout, purchase, codeslot_click | Google Ads is configured separately; optional GA4 Enhanced Measurement may send additional events. |
| Google Ads | Purchase | AW-ID initializes the base tag; the matching conversion label routes Purchase as conversion. |
| GTM | codeslot_page_view, codeslot_view_content, codeslot_add_to_cart, codeslot_begin_checkout, codeslot_purchase, codeslot_click | These are dataLayer custom events, not automatically configured Google tags. Publish your container and matching triggers/tags. |
| Pinterest | pagevisit, addtocart, checkout | Purchase maps to checkout. Unsupported generic checkout/click choices are not shown. |
| TikTok | page, ViewContent, AddToCart, InitiateCheckout, Purchase, ClickButton | Purchase uses the current event name in the public TikTok integration. |
| Snapchat | PAGE_VIEW, VIEW_CONTENT, ADD_CART, START_CHECKOUT, PURCHASE, CUSTOM_EVENT_1 | Click uses the custom event slot shown in the interface. |

The base SDK initializes even if optional events are unchecked. Page view is initially checked for new supported cards; saved unchecked choices remain unchecked. GA4 uses send_page_view:false and sends page_view only when selected, avoiding a second automatic initial Page View from the adapter. Account automatic tracking remains independent of CodeSlot event checkboxes. Meta uses standard initialization without disabling the account automatic configuration; CodeSlot itself supplies no advanced-matching fields. Installing the same GA4 tag directly and inside GTM can duplicate events.

Commerce triggers use WooCommerce. Product and cart payloads contain IDs, unit prices/quantities and currency; checkout uses the current cart; Purchase requires a paid order with its valid order key and customer authorization. The current WooCommerce session supplies asynchronous cart event data, so a cart action on another product is not attributed to the product currently being viewed. Purchase/cart deduplication is scoped to site, provider and tracking ID in browser session storage. Browser blocking, cache configuration and closing the page can prevent delivery. Platform-side receipt and conversion attribution should be checked using that account's test tools.

## Consent API

When the optional consent gate is on, set window.CodeSlotConsent = {marketing: true} before the head loader runs, or dispatch window.dispatchEvent(new CustomEvent('codeslot:consent', {detail:{marketing:true}})) after acceptance. A false signal stops new CodeSlot events. The gate is not a cookie-banner UI.

## Implementation review references

Reviewed public source and provider references on 2026-11-14. Behavior is independently implemented; these plugins are not bundled dependencies.

- [Meta for WooCommerce pixel](https://github.com/woocommerce/facebook-for-woocommerce/blob/trunk/facebook-commerce-pixel-event.php) and [commerce tracker](https://github.com/woocommerce/facebook-for-woocommerce/blob/trunk/facebook-commerce-events-tracker.php): base initialization, standard/custom calls, product data and event IDs.
- [Pinterest for WooCommerce public source](https://plugins.svn.wordpress.org/pinterest-for-woocommerce/trunk/): base page initialization, pagevisit/addtocart/checkout mappings and line items.
- [TikTok public source](https://plugins.svn.wordpress.org/tiktok-for-business/trunk/): ViewContent, AddToCart, InitiateCheckout and Purchase payload conventions.
- [GTM4WP public source](https://plugins.svn.wordpress.org/duracelltomi-google-tag-manager/trunk/): ContainerCode/ConsentDefaults, dataLayer initialization, WooCommerce purchase handling and Store API item data. The review confirms the separation between loading a container and configuring its tags/triggers, preserving the existing dataLayer, clearing previous ecommerce data and including item quantities/prices. CodeSlot uses its documented codeslot_* custom event names; configure matching triggers in GTM.
- [GA4 event reference](https://developers.google.com/analytics/devguides/collection/ga4/reference/events), [Google Ads conversion setup](https://support.google.com/google-ads/answer/7548399), [GTM web installation](https://developers.google.com/tag-platform/tag-manager/web).
- [Snap Pixel documentation](https://developers.snap.com/api/marketing-api/Conversions-API/Parameters).

See readme.txt External services for service/privacy links and data disclosures.

## Base tag placement

Enabled providers' configuration and the CodeSlot loader are emitted once inside the frontend `<head>`, before `</head>`. The loader initializes vendor queues immediately and appends the enabled asynchronous vendor SDK scripts to that same head. Initialization no longer waits for the footer. Optional consent still blocks SDK initialization until acceptance. The public IDs are browser configuration, not credentials.

Product, checkout and authorized order-received context is prepared for that request before head output. Paid purchase/order-key/customer checks and browser-session deduplication still apply. Classic/Blocks cart event listeners bind once the DOM is ready so the head loader does not access a missing body; click delegation can attach to the document immediately. Head placement does not enable events that are disabled in CodeSlot.

In View Source, find `codeslot-pixel-config` and `codeslot-pixels-js` before `</head>`. Inspect the live head or Network panel for vendor SDK URLs. A theme must call `wp_head()`. Performance plugins can move/delay scripts; exclude the `codeslot-pixels` handle from those features if they change the required head placement, and purge page/CDN caches after updating. Avoid installing the same provider ID again through another snippet or plugin.

For a consent decision made after the head loader has run (including a consent script inserted later in the header or footer), dispatch the consent event or call `window.CodeSlotPixels.setConsent({marketing:true})`. Assigning only the global variable after initialization does not notify the running loader.
