# CodeSlot architecture

1. Purpose: manage global and individual WordPress code slots plus a local snippet library, with no premium dependencies.
2. Environment: Docker WordPress, MariaDB, WP-CLI; PHP tooling and browser tests.
3. Core: three global slots, protected post/term metadata, ordered request-local renderer.
4. Snippets: private CPT, protected metadata, activation index, revisions, import/export.
5. Conditions and recovery: validated OR groups of AND rules; exclusion wins; fail closed on unknown rules; PHP execution after query setup; constant-based emergency switch.
6. Verification: real WordPress integration and HTTP isolation, permissions, browser tests, static analysis.
7. Package: allowlisted files, clean installation, honest reports.

Storage: `codeslot_global` (non-autoloaded option), `_codeslot_slots` post/term metadata; `codeslot_snippet` private CPT and `_codeslot_config` protected metadata; active snippets stored as published posts, inactive as draft. CodeSlot’s bounded revision-history metadata snapshots both code and configuration. Queries retrieve active snippets by location and cache within the request; no public REST exposure. All writes require manage_options, unfiltered_html, edit_plugins and super-admin on multisite. Review code before activation and retain backups and recovery access.

Output sequence: global → matching snippets ordered by numeric priority then ID → queried singular/term slots. No post-loop inference for individual slots. Body omission warning uses an observed frontend request and never relocates body code.

PHP starts at wp (frontend) or admin_init (explicit admin location). The separate bootstrap location executes at init priority 20 on all request types for early filters; query conditions are rejected there. plugins_loaded constants still belong in wp-config.php. Manual execution uses the fixed `codeslot_manual` action with a name, never arbitrary hook registration from imported data.

Numbered paragraph insertion preserves existing content bytes and ignores raw-text/code containers. One-time PHP tasks use a separate inactive location, explicit confirmation and nonce checks, plus an atomic per-site execution claim. A claimed task is never automatically retried after failure or interruption. Saved/current code comparisons use escaped WordPress text diffs.

Conditions use shared definitions for validation and editor labels. assets/conditions.js owns the searchable builder separately from general administration. Condition_Editor handles authorized, nonce-protected AJAX name searches (20 results maximum, 200-byte query/value limit); content titles are returned only for editable items in registered public types/taxonomies. Rule JSON stores identities rather than mutable display titles. Editor, import and restore use the same current-schema validation.

Pixels emits configuration at wp_head priority 5 and enqueues its loader for the standard head-script pass. Vendor queues initialize before body parsing, with asynchronous SDK requests. WooCommerce order-received verification runs at wp priority 30 before head output; DOM-dependent classic/Blocks cart bindings wait for DOMContentLoaded. The mobile editor toolbar remains static below 783px to avoid overlaying fields.

Dependencies centralizes plugin-file validation, WordPress active/valid plugin checks (including network activation), WooCommerce location availability and editor/list notices. Conditions checks registered references before evaluating either inclusion or exclusion. Delivery applies dependency checks before automatic/manual/shortcode/selector execution; Jobs checks before claiming a task. No dependency check rewrites stored state. A hidden location mirror preserves a selected disabled option during native form submission; the picker never substitutes a different location for a missing dependency.

Global and individual writes share Permissions::validate_slots with backup validation. Invalid shapes or oversized UTF-8 fields are rejected before metadata writes; native editors receive a transient error notice, and the browser retains oversized edits without submitting. Unselected full-backup restore sections never trigger writes.
