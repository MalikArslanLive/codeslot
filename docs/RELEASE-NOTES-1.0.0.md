# CodeSlot 1.0.0

CodeSlot – Header, Body & Footer Code + Snippet Manager, by Malik Arslan (https://malikarslan.com/). GPL-2.0-or-later. Slug and version remain codeslot / 1.0.0.

This build replaces the original 1.0.0 release candidate with a responsive administration interface and WordPress-style snippet management. Existing data and executable-code semantics are preserved.

- Branded header, clear navigation and polished layouts across all six CodeSlot admin pages.
- Separate selection column on desktop and dedicated selection controls on mobile, tested down to 320 pixels.
- Visible names and Edit actions; ID-based fallback titles for legacy unnamed snippets.
- Quick Edit for metadata, bulk metadata updates, Select page, confirmed permanent deletion and search-preserving first/previous/next/last pagination.
- Guarded mutations preserve existing code/conditions, reject missing nonces and respect locks/capabilities.
- Content-based asset versions refresh CSS/JS when reinstalling version 1.0.0.
- Author/website metadata, WordPress.org URL, contributor templates and reproducible server/CI coverage.

Requirements: WordPress 6.5+ and PHP 7.4+. Prefer maintained WordPress/PHP versions in production. Exact tested versions, SAPIs, server scopes, commands, failed attempts and limitations are in docs/TEST-REPORT.md and COMPATIBILITY.md. Expanded CI covers eight WordPress targets and seven PHP versions; workflow results, rather than configuration alone, establish passes.

Upload codeslot-1.0.0.zip through Plugins -> Add New -> Upload Plugin. If replacing the earlier 1.0.0 build, choose Replace current with uploaded after taking a backup. The archive contains codeslot/codeslot.php and only 12 production files. No Composer or Node dependencies are required at runtime.

SHA-256:

```
7e07b3d268450e63a83f02a52450074f64e56d3ac1bb4019936cb1449dffb95c
```

PHP snippets execute arbitrary privileged code; this is not a secure sandbox or independent penetration-test certification. Safe Mode recovery, permissions, imports, rollback, multisite and uninstall behavior are covered by regression checks. Page caches must be purged/varied appropriately. LiteSpeed Enterprise, premium plugins, every payment flow and arbitrary production configurations are not certified.

WordPress.org: https://wordpress.org/plugins/codeslot/ (listing availability/approval was not independently verified). No WordPress.org submission or public GitHub Release publication was performed. Public release publication requires the owner's explicit approval.

Plugin website/Plugin URI: https://github.com/MalikArslanLive/codeslot. All included features are free. README self-links and duplicate historical reports were removed; development and compatibility guidance is organized under docs/.
