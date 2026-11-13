# CodeSlot 1.0.0

CodeSlot – Header, Body & Footer Code + Snippet Manager, by Malik Arslan (https://malikarslan.com/). GPL-2.0-or-later. Slug and version remain codeslot / 1.0.0.

This build replaces the original 1.0.0 release candidate with a responsive administration interface and WordPress-style snippet management. Existing data and executable-code semantics are preserved.

- Branded header, clear navigation and polished layouts across all nine CodeSlot admin pages.
- Separate selection column on desktop and dedicated selection controls on mobile, tested down to 320 pixels.
- Visible names and Edit actions; ID-based fallback titles for legacy unnamed snippets.
- Quick Edit for metadata, bulk metadata updates, Select page, confirmed permanent deletion and search-preserving first/previous/next/last pagination.
- Guarded mutations preserve existing code/conditions, reject missing nonces and respect locks/capabilities.
- Content-based asset versions refresh CSS/JS when reinstalling version 1.0.0.
- Author/website metadata, WordPress.org URL, contributor templates and reproducible server/CI coverage.

Requirements: WordPress 6.5+ and PHP 7.4+. Prefer maintained WordPress/PHP versions in production. Exact tested versions, SAPIs, server scopes, commands, failed attempts and limitations are in docs/TEST-REPORT.md and docs/COMPATIBILITY.md. Expanded CI covers eight WordPress targets and seven PHP versions; workflow results, rather than configuration alone, establish passes.

Upload codeslot-1.0.0.zip through Plugins -> Add New -> Upload Plugin. If replacing the earlier 1.0.0 build, choose Replace current with uploaded after taking a backup. The archive contains codeslot/codeslot.php and only 21 production files. No Composer or Node dependencies are required at runtime.

SHA-256:

```
7bd9b49bea23ee2f112cb944a3f2e5996f5d6336b49e1b907175a5577ada56c1
```

PHP snippets execute arbitrary privileged code; this is not a secure sandbox or independent penetration-test certification. Safe Mode recovery, permissions, imports, rollback, multisite and uninstall behavior are covered by regression checks. Page caches must be purged/varied appropriately. LiteSpeed Enterprise, premium plugins, every payment flow and arbitrary production configurations are not certified.

WordPress.org: https://wordpress.org/plugins/codeslot/ (listing availability/approval was not independently verified). No WordPress.org submission or public GitHub Release publication was performed. Public release publication requires the owner's explicit approval.

Plugin website/Plugin URI: https://github.com/MalikArslanLive/codeslot. All included features are free. README self-links and duplicate historical reports were removed; development and compatibility guidance is organized under docs/.

- Saved/current revision code diffs, before/after numbered-paragraph insertion, and confirmed one-time PHP tasks with repeat protection and recorded outcomes.
- Cloud sync and AI generation are excluded from this release.

Validation: [69 quality jobs passed](https://github.com/MalikArslanLive/codeslot/actions/runs/43474411107), including 56 runtime combinations and 29 browser tests; [dependency security audit passed](https://github.com/MalikArslanLive/codeslot/actions/runs/43474411465).

## Updated unpublished 1.0.0 candidate

- Bulk Apply no longer needs a redundant confirmation checkbox. Quick Edit preloads untitled fallback names and saved metadata; guarded accessible status switches preserve source.
- Permanent history clearing removes stored versions with a warning while retaining current code. Slot descriptions explain insertion positions.
- Private System Info and bounded local logs support copy/download and deliberate clearing.
- Full CodeSlot backup restores selected global/individual/settings/file/pixel sections with inactive output and Safe Mode.
- Virtual ads.txt, app-ads.txt, service-worker.js and appended robots.txt directives preserve physical files; worker registration is opt-in.
- Consent-gated Meta/Google/Pinterest/TikTok/Snapchat pixels include optional WooCommerce events, order authorization and session deduplication.
- 142 original local recipes/setup guides; library entries requiring a companion plugin are excluded. WooCommerce integration remains separate and included. See docs/LIBRARY-SCOPE.md for the skipped categories and reasons.

The owner authorized reconciling the unpublished v1.0.0 tag with the final source. Its production files match the verified ZIP built from 6bdde4d; later source commits only update documentation. The old annotated tag is preserved in a local Git bundle before replacement. Public release publication and WordPress.org submission still require explicit approval.
