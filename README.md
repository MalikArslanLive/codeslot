# CodeSlot – Header, Body & Footer Code + Snippet Manager

![Version](https://img.shields.io/badge/version-1.0.0-blue)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)

By [Malik Arslan](https://malikarslan.com/) · [WordPress.org](https://wordpress.org/plugins/codeslot/)

**Add Header, Body and Footer code site-wide or to a single page, post, WooCommerce product, or public custom post type.** Categories, tags and public custom taxonomy terms also have their own slots for their archive pages. All included CodeSlot features are free under GPL-2.0-or-later.

## Where can I add code?

| Location | What CodeSlot provides |
|---|---|
| Entire site | Separate global Header, Body and Footer editors and enable switches |
| Individual pages and posts | Three independent slots in the native WordPress editor; output only on that item's singular URL |
| WooCommerce products | The same native product fields; four supported WooCommerce insertion hooks |
| Public custom post types | Individual fields, without leaking code to other content items |
| Categories, tags and public custom taxonomies | Individual term fields; output only on that term's own archive |
| Admin and content locations | Admin Header/Footer, before/after main content or a numbered paragraph |
| CSS selector | Before/after, prepend/append or replace matching frontend elements after DOM readiness |
| Shortcodes and manual insertion | Reusable snippets inserted exactly where you choose |
| PHP execution | Frontend, admin, everywhere or manual locations, with privileged access and recovery controls |

Themes must call `wp_head`, `wp_body_open` and `wp_footer`. Attachments and private internal post types are excluded. “Everywhere” PHP is a defined CodeSlot location; it does not promise execution during REST, AJAX, cron or before query setup.

## All features are free

- PHP, JavaScript, CSS, HTML, plain text, reusable content and nested-shortcode snippets.
- Global and individual Header/Body/Footer code with ordered output and duplicate-slot prevention.
- AND/OR conditions, exclusions, activation switches and execution priorities.
- Search, filters, sorting, pagination, a separate checkbox column, Edit, Quick Edit, Bulk Edit, Trash/Restore and confirmed permanent deletion from Trash. Native Screen Options controls separate metadata columns, page size and sorting; Status is last.
- Categories, tags, duplication, locking and library templates.
- All/Active/Inactive/Trash views, Undo, inactive restore and confirmed Empty Trash.
- Any/Desktop/Mobile device targeting, start/end schedules, static JavaScript/CSS files and opt-in JavaScript compaction.
- JSON import/export and the last 30 changed versions. Unchanged saves create no revision; comparisons label Previous Saved Version and Current Version, with inactive restore.
- Before/after numbered-paragraph insertion in main singular content.
- Confirmed Run Once PHP tasks with atomic claim, output/status records and no automatic retry.
- PHP syntax checks, Safe Mode, error reporting and best-effort direct-error recovery.
- Per-site multisite storage with super-admin management.
- A single-page local library with 142 recipes/setup guides, live search and category filters.
- Private System Info, bounded local logs and virtual ads.txt/app-ads.txt/robots.txt/service-worker.js tools.
- Consent-gated Meta, Google Analytics 4, Google Ads, Google Tag Manager, Pinterest, TikTok and Snapchat integrations, including optional WooCommerce events.

See [feature details and limits](docs/FEATURES.md).

## Install

[Download the current CodeSlot 1.0.0 package](https://github.com/MalikArslanLive/codeslot/raw/refs/heads/main/packages/codeslot-1.0.0.zip) · [SHA256SUMS](packages/SHA256SUMS)

This package follows the current `main` source, including the green responsive interface, editor fixes and Google Tag Manager integration. The original published `v1.0.0` release assets retain their original source snapshot; use the current-package link above for this updated build. Verify the checksum supplied with the package you download.

1. In WordPress, open **Plugins → Add New → Upload Plugin** and select the ZIP.
2. Activate CodeSlot. When replacing an earlier 1.0.0 build, back up first and choose **Replace current with uploaded**.
3. Open **CodeSlot → Header, Body & Footer** for global slots, or **CodeSlot → Add New** for snippets.
4. Open a page, post, product or public custom post type in its native editor and find **CodeSlot — Header, Body & Footer** for individual code. Use term add/edit screens for archive code.

Requires **WordPress 6.5+ and PHP 7.4+**. No Composer or Node dependencies are needed at runtime. See [requirements and compatibility](docs/COMPATIBILITY.md); companion plugins can require newer PHP versions.

## Add code to one page or post

1. Open the page or post in the native WordPress editor and find **CodeSlot — Header, Body & Footer**.
2. Paste your code into the relevant slot, turn on its enable switch and update the item.
3. Open the saved item's frontend URL to check the output. For example, a script added to one landing page's Footer slot appears on that page only.

WooCommerce products and public custom post types use the same fields. For category, tag or public custom taxonomy archive code, open the term's edit screen. Global code can remain active alongside individual code.

## Use snippets

Choose a type, location, conditions and priority. PHP is entered without PHP tags; JavaScript/CSS without script/style wrappers. Lower priorities run first. Output order is global slots → matching snippets by priority/ID → individual code.

Use `[codeslot id="123"]` for an active visual snippet configured for shortcode insertion. PHP is never exposed through a public shortcode. Manual insertion after query setup: `do_action( 'codeslot_manual', 'Exact snippet name' );`.

Imports create inactive, unlocked copies. Review code before activating it. Elementor content uses the underlying WordPress item metadata; edit the fields in WordPress's native editor. Unsaved changes and autosaves do not execute individual code.

## Security and recovery

Management requires `manage_options`, `unfiltered_html` and `edit_plugins`; multisite additionally requires super-admin. PHP has arbitrary full site/server privileges. Syntax validation and exception handling are **not a secure sandbox** and cannot undo destructive side effects.

For emergency suppression, add `define( 'CODESLOT_SAFE_MODE', true );` to `wp-config.php` before WordPress loads. If needed, deactivate with `wp plugin deactivate codeslot --skip-plugins=codeslot` or rename the plugin folder. Purge page caches after changes/recovery. See [SECURITY.md](SECURITY.md).

## Documentation

[Features](docs/FEATURES.md) · [Development](docs/DEVELOPMENT.md) · [Testing](docs/TESTING.md) · [Compatibility](docs/COMPATIBILITY.md) · [Import schema](docs/IMPORT-SCHEMA.md) · [Support](SUPPORT.md) · [Publishing](docs/PUBLISHING.md)

For code wrapper hints, tracking ID examples, consent setup, files and local tools, see [feature usage](docs/NEW-FEATURES.md) and the [library guide](docs/LIBRARY.md).
