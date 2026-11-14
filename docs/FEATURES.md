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
| Management | Search/filter/sort/pagination, selection column, Select page, Edit, Quick Edit, metadata Bulk Edit, Trash/Restore and confirmed permanent deletion, legacy-safe status switches and native per-user Screen Options |
| Organization | Categories, tags, priorities, active/inactive state, locks and 142 local recipes/setup guides |
| Portability | Snippets-only JSON plus selected full-backup restore for global/individual slots, settings, files and pixels; inactive restore with Safe Mode |
| Versions | Last 30 actual source/config changes; unchanged saves add no snapshot; escaped saved/current code diff, inactive rollback and confirmed permanent history clearing |
| One-time tasks | Nonce-confirmed PHP execution, atomic claim, captured output/status, no automatic retry |
| Tools | Private System Info with optional site/path details, copy/download, bounded local event logs and recovery guidance |
| Public files | Virtual ads.txt/app-ads.txt/service-worker.js plus appended robots.txt directives; physical files take precedence; worker registration opt-in |
| Pixels | Consent-gated Meta, Google Analytics 4, Google Ads, Google Tag Manager, Pinterest, TikTok and Snapchat browser integrations; base/click and optional WooCommerce events; GTM codeslot_* dataLayer events |
| Early hooks | Explicit bootstrap PHP at init on all request types, without query conditions; Safe Mode applies |
| Recovery | PHP syntax checks, Safe Mode and best-effort direct-error recovery |
| Multisite | Per-site data, restricted to super-admin code management |


Global/individual slots rely on theme hooks. Automatic PHP starts after query setup or admin initialization. Run Once is explicitly manual, stays inactive and rejects automatic conditions. Paragraph insertion targets explicit closed `<p>` elements in main singular content, skips comments/raw-text containers and leaves other markup unchanged. Authorized previews use saved metadata. Full companion workflows and every cache configuration are not certified; see [compatibility](COMPATIBILITY.md) and [security](../SECURITY.md).
