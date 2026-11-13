# Security review findings

Scope: source review and executable permission/nonce/import/recovery assertions. Not an independent penetration test and not proof arbitrary PHP is safe.

Implemented boundaries: combined manage_options/unfiltered_html/edit_plugins gate, multisite super-admin restriction, object capabilities for metadata, nonce-protected POST writes, no public source REST exposure, inactive imports with limits and prevalidation, bounded revision history and error messages, opt-in uninstall deletion. Raw code is output only on matching frontend HTML routes and escaped in editors. Normal PHP is query-ready and excluded from REST/AJAX/cron. Explicit bootstrap PHP runs at init across request types and rejects query conditions.


Known design risks: trusted administrators can execute arbitrary destructive PHP/JS; exception recovery covers only direct evaluation and fatal shutdown best effort, not later callbacks or side effects; page caches can retain output or leak condition-specific responses unless varied; metadata backups/exports contain executable code; imports are bounded but trusted code still requires review; snippet locks do not constrain another code administrator. These are documented limitations rather than sandbox guarantees.



Automated review: production ZIP checked using WordPress Plugin Check 2.2.0 on a clean WordPress installation. Final observed output: “Success: Checks complete. No errors found.” Explicit line-level annotations explain intentional privileged raw output and eval. These are reviewed design exceptions, not a promise that arbitrary code is harmless. Nonce checks are explicit at the admin mutation entrypoint as well as centralized permission verification. One-time uninstall uses metadata APIs and a prepared prefix query for plugin-owned observation options.


New boundaries: extra mutation routes require the same combined capabilities and nonce. Full restore prevalidates sections before writes, disables imported execution and enables Safe Mode; it is not a database transaction or full-site backup. Diagnostics omit paths/URLs by default and never dump credentials/source. Event logs are capped at 100 records without error-message/source bodies. Virtual routes have fixed filenames/MIME types, deny direct query-string access and defer to physical files; they cannot affect static server files or unregister existing workers. Pixel settings validate public IDs; script-context JSON is hex-escaped; marketing consent is required before vendor loading. No customer contact details are sent by CodeSlot's WooCommerce event payloads. Vendor SDK behavior and browser delivery remain outside a local adapter test's guarantees.
