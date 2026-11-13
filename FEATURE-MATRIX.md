# Independently implemented feature comparison

Public reference descriptions read on 2026-11-13:
- https://wordpress.org/plugins/insert-headers-and-footers/
- https://wordpress.org/plugins/code-snippets/

Reference descriptions may mix free and commercial features and may change. This is a scope comparison, not a claim of interface equivalence, code reuse or proprietary feature parity.

| Capability described by references | CodeSlot status | Scope |
|---|---|---|
| Global header/body/footer | Implemented | Independent toggles, raw code, ordered hooks |
| Page-specific scripts | Implemented | Native WordPress post/CPT and term fields; saved previews |
| PHP, JS, CSS, HTML, text | Implemented | Privileged local execution/insertion |
| Reusable HTML and shortcodes | Implemented | Explicit shortcode location; no PHP shortcode exposure |
| Automatic/manual insertion | Implemented | Fixed reliable hooks; PHP begins at query-ready stage |
| Conditions and priority | Implemented | Visual AND/OR builder; exclusion wins |
| CRUD/duplicate/bulk/search/organization | Implemented | Confirmed bulk actions; 30-item pagination |
| Revisions | Implemented | Last 30 source/config snapshots; inactive restore; no visual diff |
| Syntax editor/PHP syntax checks | Implemented | WordPress CodeMirror/TOKEN_PARSE |
| Locking | Implemented | Explicit unlock; accidental-edit guard |
| Error feedback/recovery | Partial | Direct Throwable/shutdown best effort; not a PHP sandbox |
| Usage information and execution status | Partial | Insertion instructions, active state, latest error; no per-request ran-on-page tracing |
| Local library/templates | Implemented | Four original reviewed examples, initially inactive |
| WooCommerce locations | Partial | Four public hooks and page conditions; no deep commerce rule suite |
| Device targeting | Partial | wp_is_mobile advisory signal; no tablet/desktop certainty |
| Multisite | Partial | Per-site data with super-admin control; no shared network library |
| JSON portability | Implemented | Documented CodeSlot format; no competitor-format converter |
| Paragraph-level/selector-based insertion | Unsupported | Main content before/after only |
| Run-once PHP jobs | Unsupported | No destructive one-time task runner |
| Early site-wide PHP hooks | Unsupported | Query-dependent conditions would be unavailable |
| File-based generated execution | Unsupported | No executable file generation |
| Cloud/community sync/deployment | Unsupported | Local only; no cloud service or license parity |
| AI assistant/code generation/Blueprints | Unsupported | No external AI or generator service |
| Builder-native widgets/canvas integration | Unsupported | Native content metadata works independently of builder internals |
| Role delegation and telemetry | Unsupported | Strict privileged gate; no telemetry |

“Implemented” describes source functionality, not compatibility certification. Quality-gate and compatibility evidence are tracked separately in TESTING.md and COMPATIBILITY.md.
