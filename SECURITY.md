# Security model and recovery

CodeSlot grants arbitrary server/site code execution to an already privileged administrator. `manage_options`, `unfiltered_html` and `edit_plugins` are all mandatory, plus super-admin on multisite. It does not create roles or delegate executable-code access. DISALLOW_FILE_MODS and capability filters may deny management. Site administrators on multisite cannot write code through CodeSlot.

All admin mutation forms use WordPress nonces and POST. Nonces do not replace authorization. Post and term saves additionally require object edit capabilities, reject autosaves/revisions and only accept CodeSlot nonces. Raw source is intentionally not KSES-filtered by CodeSlot. Editor text is escaped; frontend raw output is intentional. Code is stored using WordPress APIs and correct slashing. No custom public REST endpoints or REST-exposed source metadata exist. Imports are bounded, schema-validated before writes, and create inactive unlocked copies; imported IDs never overwrite existing objects.

PHP is checked with TOKEN_PARSE before saving, including activation. Type/location combinations are validated. Runtime Throwable handling deactivates and records failures during direct snippet evaluation. Shutdown recovery attempts to deactivate fatal snippets while evaluation is still in progress. This cannot reliably catch callbacks registered by a snippet, process termination, exit/die, hangs, resource exhaustion, declarations that poison subsequent requests, or code that changes recovery itself. Full PHP privileges include filesystem, database, network and WordPress account access. There is no secure sandbox.

## Emergency procedure

1. Back up the database and files where feasible.
2. Add `define( 'CODESLOT_SAFE_MODE', true );` to wp-config.php before the line that loads WordPress. All snippets and all code slots are suppressed, including shortcodes/manual insertion.
3. If WordPress still cannot load, rename `wp-content/plugins/codeslot` or run `wp plugin deactivate codeslot --skip-plugins=codeslot`.
4. Open the snippet manager and deactivate or fix the offending code. Errors are displayed only to authorized code managers. Review saved revisions and restore inactive if useful.
5. Test with backups on a disposable site, then remove the constant/disable Safe Mode deliberately. Undo any side effects separately.

Safe Mode is constant-based or stored in authenticated settings. No unauthenticated URL parameter toggles it. Safe Mode does not reverse side effects. Page caches may continue serving previously injected HTML: purge relevant caches after changes and during recovery.

## Review findings

See docs/SECURITY-REVIEW.md. Report vulnerabilities privately via GitHub's private vulnerability reporting if enabled, or contact the repository owner privately. Do not publish working exploits or secret source in public issues. Author website: https://malikarslan.com/. Do not include credentials or private snippets in reports.
