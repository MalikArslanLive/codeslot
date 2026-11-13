# Changelog

## 1.0.0 — release candidate

- Added saved/current code diffs, byte-preserving numbered-paragraph insertion and confirmed one-time PHP tasks with atomic claims and execution records.

- Redesigned all six admin pages with responsive navigation, forms, buttons and mobile snippet cards.
- Added always-visible Edit links and fallback names for legacy unnamed snippets; new editor submissions require a name.
- Added Quick Edit, bulk metadata updates, page selection, guarded Delete and first/previous/next/last pagination.
- Preserved list filters and added a reset action and empty state.
- Added author branding, descriptive plugin title, UI regression tests and HTTP server/SAPI coverage.
- Styles refresh by content hash even when reinstalling version 1.0.0.


- Global and queried-item/term Header, Body and Footer injection with strict isolation and ordered output.
- Privileged local snippet CRUD, bulk actions, priorities, categories/tags, conditions, import/export, locks and last-30 revision rollback.
- Syntax-highlighted native code fields, local templates and shortcode/manual/WooCommerce locations.
- PHP syntax validation, Safe Mode, best-effort failure deactivation and private error records.
- Docker, WordPress integration, unit and browser tests, quality workflows and deterministic ZIP build.

No public release has been created. See release gates and actual test results before production use.

## 1.0.0 — additional administration and tools

- Apply bulk actions without the redundant confirmation checkbox; prefill legacy untitled Quick Edit fields and add guarded accessible status switches.
- Add confirmed permanent history clearing, slot placement help, private diagnostics/local event logs, and a searchable original 142-item local library including three clearly marked setup guides.
- Add selected full-backup restore, safe virtual public-file handling and consent-gated browser pixel adapters with optional WooCommerce events.
- Add explicit init bootstrap PHP for early-hook recipes without query conditions. Keep version 1.0.0.
