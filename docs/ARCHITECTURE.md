# CodeSlot architecture and plan

1. Purpose: manage global and individual WordPress code slots plus a local snippet library, with no premium dependencies.
2. Environment: Docker WordPress, MariaDB, WP-CLI; PHP tooling and browser tests.
3. Core: three global slots, protected post/term metadata, ordered request-local renderer.
4. Snippets: private CPT, protected metadata, activation index, revisions, import/export.
5. Conditions and recovery: validated OR groups of AND rules; exclusion wins; fail closed on unknown rules; PHP execution after query setup; constant-based emergency switch.
6. Verification: real WordPress integration and HTTP isolation, permissions, browser tests, static analysis.
7. Package: allowlisted files, clean installation, honest reports.

Storage: `codeslot_global` (non-autoloaded option), `_codeslot_slots` post/term metadata; `codeslot_snippet` private CPT and `_codeslot_config` protected metadata; active snippets stored as published posts, inactive as draft. Native revisions include code and configuration through explicit snapshots. Queries retrieve active snippets by location and cache within the request; no public REST exposure. All writes require manage_options, unfiltered_html, edit_plugins and super-admin on multisite. Execution remains privileged arbitrary code, never a sandbox.

Output sequence: global → matching snippets ordered by numeric priority then ID → queried singular/term slots. No post-loop inference for individual slots. Body omission warning uses an observed frontend request and never relocates body code.

PHP starts at wp (frontend) or admin_init (explicit admin location). The separate bootstrap location executes at init priority 20 on all request types for early filters; query conditions are rejected there. plugins_loaded constants still belong in wp-config.php. Manual execution uses the fixed `codeslot_manual` action with a name, never arbitrary hook registration from imported data.

Numbered paragraph insertion preserves existing content bytes and ignores raw-text/code containers. One-time PHP tasks use a separate inactive location, explicit confirmation and nonce checks, plus an atomic per-site execution claim. A claimed task is never automatically retried after failure or interruption. Saved/current code comparisons use escaped WordPress text diffs.
