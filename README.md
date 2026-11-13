# CodeSlot – Header, Body & Footer Code + Snippet Manager

[![CI](https://github.com/MalikArslanLive/codeslot/actions/workflows/quality.yml/badge.svg)](https://github.com/MalikArslanLive/codeslot/actions/workflows/quality.yml)
![Version](https://img.shields.io/badge/version-1.0.0-blue)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)](LICENSE)

By [Malik Arslan](https://malikarslan.com/) · [WordPress.org](https://wordpress.org/plugins/codeslot/) · [GitHub](https://github.com/MalikArslanLive/codeslot)

WordPress code management with global and individual Header, Body and Footer slots, plus a local snippet manager. GPL-2.0-or-later. Version 1.0.0.

**Version 1.0.0 with responsive admin screens.** Read [TESTING.md](TESTING.md), [COMPATIBILITY.md](COMPATIBILITY.md) and [RELEASE-CHECKLIST.md](RELEASE-CHECKLIST.md) before production use. Passing a local check does not establish compatibility with every theme or plugin.

## Install

Build with `python3 scripts/build.py`, then upload `dist/codeslot-1.0.0.zip` through Plugins → Add New → Upload Plugin. The archive contains `codeslot/codeslot.php`. Activate CodeSlot. No runtime Composer or Node dependencies are required.

## Global and individual code

Settings → CodeSlot (also CodeSlot → Header & Footer) provides independent Header, Body and Footer editors and enable switches. Enter complete HTML, script/style tags, tracking pixels or verification markup. Code is preserved as privileged raw code.

For a page, post, public custom post type or classic WooCommerce product editor, open the **CodeSlot — Header, Body & Footer** meta box. Categories, tags and public custom taxonomies have the same fields on their add/edit screens. An item’s code runs only on its singular URL; a term’s code runs only on its own archive. Elementor content uses its underlying WordPress item metadata; the fields are in WordPress’s native editor, not Elementor’s canvas.

Order is global → matching snippets ordered by priority then ID → individual item. Hooks are `wp_head`, `wp_body_open` and `wp_footer` at priority 20. Themes must call these hooks. Missing `wp_body_open` is reported after a frontend request with a footer; body code is never silently moved elsewhere.

**Previews:** previously saved metadata is used on authorized previews, including saved drafts. Autosaves, unsaved edits and revision restores do not change individual slot metadata. Revision rollback in the snippet manager restores an inactive copy of the saved snippet configuration. Do not put secrets in code that can render publicly.

## Snippets

CodeSlot → All Snippets supports names, descriptions, categories, tags, search, status/category filtering, name/priority sorting, pagination and confirmed bulk operations. Edit a snippet to change its code, location, priority and conditions. Lower priorities run first. Locks require explicit unlock before modification; locked snippets are skipped with an error in bulk operations. This is an accidental-change control, not a boundary against another privileged administrator.

PHP, JavaScript, CSS, HTML, plain text, reusable content and nested-shortcode content are supported. PHP is entered **without PHP tags**; JS/CSS without script/style wrappers. HTML/reusable content is emitted as stored; plain text is HTML-escaped. Nested-shortcode content explicitly invokes WordPress shortcodes. PHP locations are frontend, admin, everywhere or the fixed manual action. Visual snippets can use header/body/footer, before/after main singular content, shortcode, manual action and four documented WooCommerce hooks.

`[codeslot id="123"]` inserts an active visual snippet whose location is shortcode. PHP execution is never exposed through a public shortcode. For manual insertion after query setup: `do_action( 'codeslot_manual', 'Exact snippet name' );`. Use unique names for manual snippets. All matching names execute; PHP executes at most once per request.

Condition groups use OR between groups and AND within a group; any matching exclusion wins. Empty inclusion matches all at the chosen location. Supported conditions include query types, singular IDs, post types, assigned terms/term archives (`taxonomy:ID`), roles, login state, exact site-relative URL path and WooCommerce pages. Use `logged_in` with `not` for logged-out visitors. `front_page` is the front page; `home` is the blog index. URL query strings are ignored. Mobile detection uses WordPress user-agent hints and is advisory; caches must vary accordingly.

Imports use [codeslot/v1](docs/IMPORT-SCHEMA.md) JSON and always create inactive, unlocked copies. Review imports and bundled templates before activating. Exports contain executable code; handle them as confidential source where appropriate.

## Security and recovery

Only users with `manage_options`, `unfiltered_html` and `edit_plugins` can manage code. Multisite additionally requires super-admin. File-modification restrictions may therefore disable CodeSlot management. No custom public REST endpoints exist. Code is site-local on multisite; network-wide snippet distribution is not implemented.

PHP is arbitrary trusted code with full WordPress/PHP privileges. Syntax checking and catching `Throwable` are not a sandbox. Registered callbacks, `exit`, memory exhaustion, infinite loops and destructive side effects can escape recovery. Back up the database/files first.

Emergency recovery: add `define( 'CODESLOT_SAFE_MODE', true );` to `wp-config.php` **before** WordPress loads. All CodeSlot execution and output stops. If necessary rename the plugin directory or use `wp plugin deactivate codeslot --skip-plugins=codeslot`. Fix/deactivate the snippet before removing Safe Mode. See [SECURITY.md](SECURITY.md).

## Development

See [DEVELOPMENT.md](DEVELOPMENT.md) for Docker, fixtures, tooling and build commands; [FEATURE-MATRIX.md](FEATURE-MATRIX.md) for scope compared with the independently studied public reference documentation. No competitor code, branding or premium software is bundled. No cloud connection, telemetry, upsells or frontend assets are added.

The checked package is also committed at `packages/codeslot-1.0.0.zip` with SHA256SUMS. See [the current validation report](docs/UI-VALIDATION.md) for the rebuilt package and checks; historical CI results apply to the earlier package. Author: Malik Arslan — https://malikarslan.com/.
