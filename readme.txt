=== CodeSlot – Header, Body & Footer Code + Snippet Manager ===
Tags: code snippets, header, footer, custom code, scripts
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Global and individual Header, Body and Footer code with privileged local snippets, conditions and recovery controls.

== Description ==
By Malik Arslan: https://malikarslan.com/
Plugin: https://wordpress.org/plugins/codeslot/
Source and documentation: https://github.com/MalikArslanLive/codeslot

CodeSlot manages global code and individual page, post, public custom post type, WooCommerce product and term-archive code. Global and individual settings coexist. Output order is global, matching snippets, individual code.

A separate local snippet manager supports PHP, JavaScript, CSS, HTML, plain text, reusable content and shortcode content, priorities, conditions, import/export, locks, local templates and the last 30 saved versions.

Executable code is privileged functionality. Access requires manage_options, unfiltered_html and edit_plugins; multisite additionally requires super-admin. PHP is not sandboxed. Review every snippet and keep backups and filesystem access. No cloud account is required.

This is a release candidate; see the repository compatibility and test reports for actual tested combinations. A minimum version expresses a target, not proof of every combination.

== Installation ==
1. Upload the codeslot directory to wp-content/plugins, or upload the generated plugin ZIP.
2. Activate CodeSlot.
3. Open Settings -> CodeSlot for global slots; use CodeSlot -> All Snippets for snippets.
4. Use the CodeSlot meta box in an item's native WordPress editor, or the fields on term editing screens.

== Configuration ==
Use CodeSlot -> Header, Body & Footer for global slots. Open an item in its native WordPress editor for individual slots. Create snippets through CodeSlot -> Add New, give each a name, choose its type and location, review conditions and save. Only activate code you trust. Edit existing snippets using their name or the Edit link in All Snippets. Unnamed legacy entries have an ID-based fallback title.

== Frequently Asked Questions ==

= Can PHP break my site? =
Yes. PHP has full server privileges. Syntax validation and best-effort exception recovery cannot prevent every failure or reverse destructive side effects.

= How do I recover? =
Add define( 'CODESLOT_SAFE_MODE', true ); to wp-config.php before WordPress loads. This disables all CodeSlot output and execution. If necessary rename the plugin directory or deactivate with WP-CLI using --skip-plugins=codeslot. Fix the snippet before leaving Safe Mode.

= What if my theme omits wp_body_open? =
Body code does not run. After a frontend request with wp_footer, CodeSlot records a warning. It never silently moves body code to a different hook.

= Do previews execute unsaved code? =
No. Authorized previews use the item's saved metadata. Autosaves, revisions and unsaved editor changes do not alter individual code.

= Does it work with Elementor and WooCommerce? =
Individual code belongs to the underlying WordPress content item. Use the native WordPress editor to change its fields. See the compatibility report for tested versions and editor limitations.

= Does uninstall delete code? =
Data is retained by default. Enable the explicit deletion option in CodeSlot Settings before uninstalling to remove site-local CodeSlot data. Back up first.

== Changelog ==

= 1.0.0 =
* Local snippet manager, global and individual slots, conditions and recovery controls.
* Responsive admin screens, visible Edit actions, fallback snippet titles and retained list filters.
