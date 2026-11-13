# CodeSlot – Header, Body & Footer Code + Snippet Manager

[![CI](https://github.com/MalikArslanLive/codeslot/actions/workflows/quality.yml/badge.svg)](https://github.com/MalikArslanLive/codeslot/actions/workflows/quality.yml)
![Version](https://img.shields.io/badge/version-1.0.0-blue)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)

By [Malik Arslan](https://malikarslan.com/) · [WordPress.org](https://wordpress.org/plugins/codeslot/)

**Add code across your site or only where you need it. Every included CodeSlot feature is free.** Manage global Header, Body and Footer code, individual content slots, and a local PHP/JavaScript/CSS/HTML snippet library from WordPress.

No Pro tier, license key, subscription, cloud account, telemetry or feature paywall. CodeSlot is GPL-2.0-or-later. Third-party plugins have their own licenses and requirements.

## Where can I add code?

| Location | What CodeSlot provides |
|---|---|
| Entire site | Separate global Header, Body and Footer editors and enable switches |
| Individual pages and posts | Three independent slots in the native WordPress editor; output only on that item's singular URL |
| WooCommerce products | The same native product fields; four supported WooCommerce insertion hooks |
| Public custom post types | Individual fields, without leaking code to other content items |
| Categories, tags and public custom taxonomies | Individual term fields; output only on that term's own archive |
| Before/after main singular content | Visual snippets with conditions and priorities |
| Shortcodes and manual insertion | Reusable snippets inserted exactly where you choose |
| PHP execution | Frontend, admin, everywhere or manual locations, with privileged access and recovery controls |

Themes must call `wp_head`, `wp_body_open` and `wp_footer`. Attachments and private internal post types are excluded. “Everywhere” PHP is a defined CodeSlot location; it does not promise execution during REST, AJAX, cron or before query setup.

## All features are free

- PHP, JavaScript, CSS, HTML, plain text, reusable content and nested-shortcode snippets.
- Global and individual Header/Body/Footer code with ordered output and duplicate-slot prevention.
- AND/OR conditions, exclusions, activation switches and execution priorities.
- Search, filters, sorting, pagination, a separate checkbox column, Select page, Edit, Quick Edit, Bulk Edit and confirmed Delete.
- Categories, tags, duplication, locking and local templates.
- JSON import/export and the last 30 saved versions, restored inactive for review.
- PHP syntax checks, Safe Mode, error reporting and best-effort direct-error recovery.
- Per-site multisite storage with super-admin management.

See [feature details and limits](docs/FEATURES.md).

## Install

[Download CodeSlot 1.0.0](https://github.com/MalikArslanLive/codeslot/raw/refs/heads/main/packages/codeslot-1.0.0.zip) · [SHA256SUMS](packages/SHA256SUMS)

1. In WordPress, open **Plugins → Add New → Upload Plugin** and select the ZIP.
2. Activate CodeSlot. When replacing an earlier 1.0.0 build, back up first and choose **Replace current with uploaded**.
3. Open **CodeSlot → Header, Body & Footer** for global slots, or **CodeSlot → Add New** for snippets.
4. Open a page, post, product or public custom post type in its native editor and find **CodeSlot — Header, Body & Footer** for individual code. Use term add/edit screens for archive code.

Requires **WordPress 6.5+ and PHP 7.4+**. No Composer or Node dependencies are needed at runtime. See [actual compatibility results](docs/COMPATIBILITY.md); companion plugins can require newer PHP versions.

## Use snippets

Choose a type, location, conditions and priority. PHP is entered without PHP tags; JavaScript/CSS without script/style wrappers. Lower priorities run first. Output order is global slots → matching snippets by priority/ID → individual code.

Use `[codeslot id="123"]` for an active visual snippet configured for shortcode insertion. PHP is never exposed through a public shortcode. Manual insertion after query setup: `do_action( 'codeslot_manual', 'Exact snippet name' );`.

Imports create inactive, unlocked copies. Review code before activating it. Elementor content uses the underlying WordPress item metadata; edit the fields in WordPress's native editor. Unsaved changes and autosaves do not execute individual code.

## Security and recovery

Management requires `manage_options`, `unfiltered_html` and `edit_plugins`; multisite additionally requires super-admin. PHP has arbitrary full site/server privileges. Syntax validation and exception handling are **not a secure sandbox** and cannot undo destructive side effects.

For emergency suppression, add `define( 'CODESLOT_SAFE_MODE', true );` to `wp-config.php` before WordPress loads. If needed, deactivate with `wp plugin deactivate codeslot --skip-plugins=codeslot` or rename the plugin folder. Purge page caches after changes/recovery. See [SECURITY.md](SECURITY.md).

## Documentation

[Features](docs/FEATURES.md) · [Development](docs/DEVELOPMENT.md) · [Testing](docs/TESTING.md) · [Test report](docs/TEST-REPORT.md) · [Compatibility](docs/COMPATIBILITY.md) · [Import schema](docs/IMPORT-SCHEMA.md) · [Support](SUPPORT.md) · [Publishing](docs/PUBLISHING.md)
