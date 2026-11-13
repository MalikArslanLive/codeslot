# Security review findings

Scope: source review and executable permission/nonce/import/recovery assertions. Not an independent penetration test and not proof arbitrary PHP is safe.

Implemented boundaries: combined manage_options/unfiltered_html/edit_plugins gate, multisite super-admin restriction, object capabilities for metadata, nonce-protected POST writes, no public source REST exposure, inactive imports with limits and prevalidation, bounded revision history and error messages, opt-in uninstall deletion. Raw code is output only on matching frontend HTML routes and escaped in editors. PHP is query-ready and excluded from REST/AJAX/cron.

Resolved during development: insufficient test-container CA configuration; corrected slashing verified through round-trip regression; duplicate hook output regression; query context reuse at snippet locations; outdated development-tool dependencies replaced with audited versions.

Known design risks: trusted administrators can execute arbitrary destructive PHP/JS; exception recovery covers only direct evaluation and fatal shutdown best effort, not later callbacks or side effects; page caches can retain output or leak condition-specific responses unless varied; metadata backups/exports contain executable code; imports are bounded but trusted code still requires review; snippet locks do not constrain another code administrator. These are documented limitations rather than sandbox guarantees.

Repository administration: branch-protection API request returned 403 Resource not accessible by integration. Issue tracks required owner action. Repository is public by explicit user instruction after initial request for private visibility. No credentials or real private snippet data are committed.
