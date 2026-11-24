# Features

Every feature shipped in CodeSlot 1.0.0 is free under GPL-2.0-or-later.

| Included capability | Scope |
|---|---|
| Global slots | Independent Header, Body and Footer fields and enable switches |
| Individual slots | Pages, posts, WooCommerce products, public custom post types and public taxonomy terms |
| Snippet types | PHP, JavaScript, CSS, HTML, plain text, reusable content and nested shortcodes |
| Placement | Frontend/Admin slots, main-content/paragraph positions, shortcode/manual insertion, WooCommerce hooks and five CSS-selector positions |
| Delivery | Any/Desktop/Mobile devices, start/end schedules, static JS/CSS assets and opt-in JavaScript compaction |
| Conditions | AND within groups, OR between groups, exclusions win; query types, IDs, terms, roles/login, URL paths, Woo pages and advisory mobile detection |
| Management | Search/filter/sort/pagination, selection column, Select page, Edit, Quick Edit, metadata Bulk Edit, Trash/Restore and confirmed permanent deletion, source-preserving status switches and native per-user Screen Options |
| Organization | Categories, tags, priorities, active/inactive state, locks and 142 local recipes/setup guides |
| Portability | Snippets-only JSON plus selected full-backup restore for global/individual slots, settings, files and pixels; default-disabled restore or explicit preservation of existing entry states; site-wide Safe Mode unchanged |
| Versions | Last 30 actual source/config changes; unchanged saves add no snapshot; escaped saved/current code diff, inactive rollback and confirmed permanent history clearing |
| One-time tasks | Nonce-confirmed PHP execution, atomic claim, captured output/status, no automatic retry |
| Tools | Private System Info with optional site/path details, copy/download, bounded local event logs and recovery guidance |
| Public files | Virtual ads.txt/app-ads.txt/service-worker.js plus appended robots.txt directives; physical files take precedence; worker registration opt-in |
| Pixels | Automatic base tags with optional consent gating for Meta, Google Analytics 4, Google Ads, Google Tag Manager, Pinterest, TikTok and Snapchat browser integrations; base/click and optional WooCommerce events; GTM codeslot_* dataLayer events |
| Early hooks | Explicit bootstrap PHP at init on all request types, without query conditions; Safe Mode applies |
| Recovery | PHP syntax checks, Safe Mode and best-effort direct-error recovery |
| Multisite | Per-site data, restricted to super-admin code management |


Global/individual slots rely on theme hooks. Automatic PHP starts after query setup or admin initialization. Run Once is explicitly manual, stays inactive and rejects automatic conditions. Paragraph insertion targets explicit closed `<p>` elements in main singular content, skips comments/raw-text containers and leaves other markup unchanged. Authorized previews use saved metadata. For tested configurations and cache requirements, see [compatibility](COMPATIBILITY.md) and [security](../SECURITY.md).

Tracking cards initialize enabled base tags automatically, with optional consent gating and provider-specific events. See [tracking setup](TRACKING.md). Snippets/full backups import from uploaded .json files; uploads are bounded and remain temporary. Shortcode mode offers the actual ID with Copy in the editor and All Snippets; Auto Insert has one searchable Location dropdown.

Imports skip exact duplicates and offer Skip/Keep both/Replace for same-name variants with import-specific result counts. Full restore preserves current Safe Mode and validates individual metadata, skipping missing identities. The full editor has one Lock checkbox. Unavailable WooCommerce events remain disabled/visible with retained selections. Global, individual and visual-snippet source output has scope/ID boundary comments without changing saved code.

Source labels preserve plain-text and URL-like shortcode values exactly. Inline shortcode/manual markup uses compact comments so labels add no visible whitespace. Selector payloads keep their target DOM unchanged; the server-side selector configuration has boundary comments instead. Multiline source labels are used for global/individual hooks and block snippet locations.

Conditions have readable labels and title/name search selectors for current public WordPress/plugin content, types, terms and roles. No-value rules have no extra text box. WooCommerce-only rules stay visible/disabled without WooCommerce. See [the beginner conditions guide](CONDITIONS.md). The editor's mobile toolbar remains in normal flow, and conversion base tags load from the head.

Dependency-aware execution preserves missing WooCommerce locations, pauses unavailable Include/Exclude references, and supports explicit Required plugins for custom code. Paused notices distinguish saved activation from execution eligibility. See [Dependencies](DEPENDENCIES.md).

The Content placement location group inserts around an individual item’s main content or paragraphs, including public products/CPTs; it does not insert into archive listings. Conditions narrow where an insertion runs. Global and individual fields have a 1 MiB UTF-8 byte limit per field.

**Duplicate as inactive** copies selected active or inactive snippets into new inactive snippets. The original snippets keep their existing state.
