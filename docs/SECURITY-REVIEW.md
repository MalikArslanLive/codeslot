# Security review findings

Scope: source review and executable permission/nonce/import/recovery assertions. Not an independent penetration test and not proof arbitrary PHP is safe.

Implemented boundaries: combined manage_options/unfiltered_html/edit_plugins gate, multisite super-admin restriction, object capabilities for metadata, nonce-protected POST writes, no public source REST exposure, inactive imports with limits and prevalidation, bounded revision history and error messages, opt-in uninstall deletion. Raw code is output only on matching frontend HTML routes and escaped in editors. PHP is query-ready and excluded from REST/AJAX/cron.

Resolved during development: insufficient test-container CA configuration; slashing verified through round-trip regression; duplicate hook output regression; query context reuse at snippet locations; outdated development-tool dependencies replaced with audited versions.

Known design risks: trusted administrators can execute arbitrary destructive PHP/JS; exception recovery covers only direct evaluation and fatal shutdown best effort, not later callbacks or side effects; page caches can retain output or leak condition-specific responses unless varied; metadata backups/exports contain executable code; imports are bounded but trusted code still requires review; snippet locks do not constrain another code administrator. These are documented limitations rather than sandbox guarantees.

Repository administration: branch-protection API request returned 403 Resource not accessible by integration. Issue tracks required owner action. Repository is public by explicit user instruction after initial request for private visibility. No credentials or real private snippet data are committed.


Automated review: production ZIP checked using WordPress Plugin Check 2.2.0 on a clean WordPress installation. Final observed output: “Success: Checks complete. No errors found.” Explicit line-level annotations explain intentional privileged raw output and eval. These are reviewed design exceptions, not a promise that arbitrary code is harmless. Nonce checks are explicit at the admin mutation entrypoint as well as centralized permission verification. One-time uninstall uses metadata APIs and a prepared prefix query for plugin-owned observation options.

Development dependencies were audited. Initial advisories in PHPUnit/PHPCS/WPCS/PHPCSUtils were resolved with PHPUnit 9.6.33, PHPCS 3.13.6, WPCS 3.4.1 and PHPCSUtils 1.2.3. Composer 2.8.12 was replaced with maintained Composer 2 (2.11.2 locally) after its known token-handling advisory surfaced in CI; setup-php had deliberately not configured token authentication for the old version. No credential value was added to source. npm and Composer final audits reported no known vulnerabilities.
