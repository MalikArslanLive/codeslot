# Missing plugins and saved setup

CodeSlot keeps saved configuration and pauses execution when a known requirement is unavailable. It does not move code to another location, erase source, clear conditions or change your saved Active switch merely because another plugin was deactivated/deleted.

## What happens?

| Setup | While available | After removal/deactivation | When restored |
|---|---|---|---|
| WooCommerce insertion location | Runs at the selected WooCommerce hook. | Location stays visible but disabled. The saved selection is retained and its snippet is paused, even if another component invokes that hook. | The same saved active setup can run again at that hook. |
| WooCommerce Shop/Cart/Checkout/My Account condition | Uses WooCommerce's page predicates. | The condition stays visible/disabled; **the whole snippet pauses** if a saved rule requires it. | Matching resumes when WooCommerce is active. |
| Public post type or selected item | Uses the registered type and stable item ID. | Missing/non-public type, deleted item or item in Trash pauses the whole snippet. | The same identity must be restored, or you must explicitly select another. |
| Category/tag/public taxonomy term | Uses taxonomy plus term ID. | Unregistered/non-public taxonomy or deleted term pauses the whole snippet. | Registering the same taxonomy can restore an existing term. A newly created term with the same name is not automatically substituted. |
| Visitor role | Uses its registered role identifier. | Removing the role pauses the whole snippet. | Re-registering that role restores matching. |
| Declared **Required plugins** | Code can run when every selected plugin is active and valid. | Any inactive/deleted/unavailable plugin pauses the whole snippet. WordPress recovery-paused plugins are unavailable according to WordPress's active-plugin list. | It can resume if the saved Active switch is still on and other execution restrictions allow it. |

**Include and Exclude are both protected.** One unavailable saved reference pauses the entire snippet, including alternative OR groups and negated “is not” rules. Ignoring a missing exclusion could otherwise broaden execution unexpectedly. To change this behavior, explicitly edit/remove that rule and save; CodeSlot does not silently reinterpret it.

## Reading the status

- **Active** is your saved activation preference; **Paused (saved Active)** means that preference is on but a dependency currently prevents execution.
- **Inactive** remains inactive even if its required plugin returns. Restoring a plugin does not activate snippets that you deliberately disabled.
- The full editor shows **Execution paused — dependency unavailable** with specific reasons. All Snippets shows the paused state/reason too. You can still edit, save, deactivate or trash an unlocked snippet.
- A saved unavailable location remains selected when saving unrelated edits. It is not changed to Header/Footer. Choose another available location yourself if you want a different setup.
- Safe Mode, schedules, device restrictions, ordinary conditions and insertion-method requirements still apply. An available dependency does not guarantee execution on every request.

After changing dependencies, purge page/CDN caches. Previously cached output or downloaded browser assets cannot be withdrawn by changing server-side execution rules.

## Declare dependencies for custom code

CodeSlot cannot infer every function, class, shortcode, JavaScript API or hook used inside arbitrary code. If your snippet needs a plugin:

1. Install that plugin through **Plugins → Add New** and activate it through **Plugins → Installed Plugins**.
2. Edit the snippet and open **Required plugins (optional) → Choose required plugins**.
3. Check its name and save. Select every plugin the code needs.
4. Test on the intended page. Deactivate the dependency on a disposable site to confirm the paused notice and missing output.

Inactive installed plugins appear disabled in this chooser. A previously selected plugin remains recorded after deletion, with its plugin-file identity shown if its name is no longer available. Use **Remove requirement**, then save, only when the edited code genuinely no longer needs that plugin. Otherwise restore the plugin.

Requirements apply to automatic PHP, manual/on-demand snippets, shortcodes, content/selector locations and Run Once tasks. A blocked Run Once task is not claimed/executed, so it remains available for an explicitly confirmed run after fixing the requirement.

Requirements are retained by revisions, duplication, snippet JSON import/export and full-backup restore. Imports still start new snippets disabled; missing dependencies are retained as paused requirements rather than being dropped.

## Plugin identifiers and changing providers

A required plugin is identified by its WordPress plugin-file path, such as `woocommerce/woocommerce.php`. Restoring a differently named fork is not treated as the same requirement automatically. Review the code and update the requirement explicitly.

A public type/taxonomy can be registered by any plugin or a theme. WordPress does not universally record its owner, and roles may remain registered in the database after a plugin is removed. If a setup must depend on a **particular provider**, declare that provider under Required plugins as well as choosing the type/term/role rule. Renaming/recreating content by title never retargets a saved numeric ID.

The bundled local recipes use core WordPress features and are examples to review/customize. They do not claim automatic detection of every third-party integration. External browser tracking tags such as GA4/Meta/GTM do not require another WordPress plugin; their basic events remain usable without WooCommerce. Only WooCommerce commerce triggers require WooCommerce.

## Scope

These checks cover explicit plugin requirements, known WooCommerce locations/rules, and structured item/type/taxonomy/role references. Raw global/individual HTML or arbitrary undeclared code may depend on external scripts or plugin APIs that cannot be inferred reliably. Add the appropriate requirement to managed snippets and keep availability guards in custom PHP where needed. Plugin upgrades can change APIs even while a plugin remains active; review and test those integrations separately.

## Before deleting another plugin

Back up first. Another plugin’s uninstall routine can remove its own products, posts, terms or settings, and WordPress removes attached metadata when content is permanently deleted. CodeSlot cannot recover content or individual slot metadata deleted by that routine. Standalone CodeSlot snippets retain their code and references; references to removed content pause rather than matching a different item. A full CodeSlot backup contains slot mappings, not the WordPress posts/products themselves, so restore the content separately before restoring its mapped slots.
