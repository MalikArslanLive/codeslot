# Included features and boundaries

Every feature shipped in CodeSlot 1.0.0 is free under GPL-2.0-or-later. No Pro upgrade, subscription, account or license key unlocks functionality.

| Included capability | Scope |
|---|---|
| Global slots | Independent Header, Body and Footer fields and enable switches |
| Individual slots | Pages, posts, WooCommerce products, public custom post types and public taxonomy terms |
| Snippet types | PHP, JavaScript, CSS, HTML, plain text, reusable content and nested shortcodes |
| Placement | Header/body/footer, before/after main singular content, shortcode, manual and four WooCommerce hooks |
| Conditions | AND within groups, OR between groups, exclusions win; query types, IDs, terms, roles/login, URL paths, Woo pages and advisory mobile detection |
| Management | Search/filter/sort/pagination, selection column, Select page, Edit, Quick Edit, metadata Bulk Edit, confirmed Delete and duplication |
| Organization | Categories, tags, priorities, active/inactive state, locks and local templates |
| Portability | Validated CodeSlot JSON import/export; imported copies are inactive/unlocked |
| Versions | Last 30 saved source/config snapshots; inactive rollback |
| Recovery | PHP syntax checks, Safe Mode and best-effort direct-error recovery |
| Multisite | Per-site data, restricted to super-admin code management |

CodeSlot does not include a cloud/community service, AI generation, competitor-format import, shared network snippet library, builder-native canvas widgets, paragraph/selector targeting or guaranteed recovery from arbitrary PHP side effects. Attachments/private post types and REST/AJAX/cron PHP execution are excluded. These are scope boundaries, not paid features.

Global/individual slots rely on theme hooks. PHP starts after query setup or admin initialization. Authorized previews use saved metadata. Full companion workflows and every cache configuration are not certified; see [compatibility](COMPATIBILITY.md) and [security](../SECURITY.md).
