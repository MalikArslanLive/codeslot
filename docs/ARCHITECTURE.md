# CodeSlot architecture and plan

1. Research and architecture: independently implement publicly described local capabilities of WPCode and Code Snippets. No copied code or premium dependencies.
2. Environment: Docker WordPress, MariaDB, WP-CLI; PHP tooling and browser tests; CI matrix.
3. Core: three global slots, protected post/term metadata, ordered request-local renderer.
4. Snippets: private CPT, protected metadata, activation index, revisions, import/export.
5. Conditions and recovery: validated OR groups of AND rules; exclusion wins; fail closed on unknown rules; PHP execution after query setup; constant-based emergency switch.
6. Verification: real WordPress integration and HTTP isolation, permissions, browser tests, static analysis.
7. Package: allowlisted files, clean installation, honest reports.

Storage: `codeslot_global` (non-autoloaded option), `_codeslot_slots` post/term metadata; `codeslot_snippet` private CPT and `_codeslot_config` protected metadata; active snippets stored as published posts, inactive as draft. Native revisions include code and configuration through explicit snapshots. Queries retrieve active snippets by location and cache within the request; no public REST exposure. All writes require manage_options, unfiltered_html, edit_plugins and super-admin on multisite. Execution remains privileged arbitrary code, never a sandbox.

Output sequence: global → matching snippets ordered by numeric priority then ID → queried singular/term slots. No post-loop inference for individual slots. Body omission warning uses an observed frontend request and never relocates body code.

PHP starts at wp (frontend) or admin_init (explicit admin location). Early plugins_loaded/init hooks are intentionally unavailable because query conditions cannot be evaluated there. Manual execution uses the fixed `codeslot_manual` action with a name, never arbitrary hook registration from imported data.
