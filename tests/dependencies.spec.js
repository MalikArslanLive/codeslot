import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encode=v=>Buffer.from(JSON.stringify(v)).toString('base64');
let ids=[],original,woo;
const plugin='codeslot-dependency-browser/fixture.php';
const source="<?php\n/* Plugin Name: CodeSlot Dependency Browser Fixture */\nfunction codeslot_dependency_browser_api(){return 'DEPENDENCY-API-OK';}\n";
test.setTimeout(180000);
test.beforeEach(async({page})=>{original=wp(`echo wp_json_encode(get_option('codeslot_settings',[]));`);woo=wp(`echo is_plugin_active('woocommerce/woocommerce.php')?'yes':'no';`)==='yes';wp(`update_option('codeslot_settings',['safe_mode'=>false]);$dir=WP_PLUGIN_DIR.'/codeslot-dependency-browser';wp_mkdir_p($dir);file_put_contents($dir.'/fixture.php',base64_decode('${Buffer.from(source).toString('base64')}'));wp_clean_plugins_cache();activate_plugin('${plugin}');`);await login(page);});
test.afterEach(()=>{wp(`wp_set_current_user(1);foreach(json_decode(base64_decode('${encode(ids)}'),true) as $id)wp_delete_post($id,true);deactivate_plugins('${plugin}');$file=WP_PLUGIN_DIR.'/${plugin}';if(file_exists($file))unlink($file);$dir=dirname($file);if(is_dir($dir))rmdir($dir);wp_clean_plugins_cache();update_option('codeslot_settings',json_decode(base64_decode('${Buffer.from(original).toString('base64')}'),true));`);if(woo)wp(`activate_plugin('woocommerce/woocommerce.php');`);ids=[];});
function create(data){const id=Number(wp(`wp_set_current_user(1);echo CodeSlot\\Repository::save(json_decode(base64_decode('${encode(data)}'),true));`));ids.push(id);return id;}
function saved(id){return JSON.parse(wp(`echo wp_json_encode(CodeSlot\\Repository::get(${id}));`));}
async function editor(page,id){await page.goto('/wp-admin/admin.php?page=codeslot-new&id='+id);}

test('WooCommerce locations stay visible disabled, retain a saved unavailable location on save, and resume after activation',async({page})=>{
 const helper=create({name:'Dependency hook dispatcher',type:'php',location:'frontend',active:true,code:"add_action('wp_footer',static function(){do_action('woocommerce_after_single_product');});"});expect(helper).toBeGreaterThan(0);
 const id=create({name:'Dependency Woo location',type:'html',location:'woocommerce_after_single_product',active:true,code:'<!-- DEP-WOO-LOCATION -->'});const before=saved(id);
 const combined=create({name:'Dependency Woo combined',type:'html',location:'woocommerce_after_single_product',active:true,code:'<!-- DEP-WOO-COMBINED -->',conditions:{include:[[{key:'cart',op:'not'}]]}});expect(combined).toBeGreaterThan(0);
 expect(await(await page.request.get('/')).text()).toContain('DEP-WOO-COMBINED');expect(await(await page.request.get('/')).text()).toContain('DEP-WOO-LOCATION');wp(`deactivate_plugins('woocommerce/woocommerce.php');`);await editor(page,id);
 await expect(page.locator('.codeslot-dependency-notice')).toContainText('Execution paused');await expect(page.locator('#codeslot-location')).toHaveValue('woocommerce_after_single_product');
 await page.getByLabel('Choose insertion location').click();await page.getByRole('searchbox',{name:'Search locations'}).fill('woocommerce');await expect(page.locator('.codeslot-location-option')).toHaveCount(4);for(const name of ['Before product','After product','Before shop loop','After shop loop'])await expect(page.locator('.codeslot-location-option').filter({hasText:name}).first()).toBeDisabled();await page.getByLabel('Choose insertion location').click();
 await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();expect(saved(id)).toEqual(before);expect(await(await page.request.get('/')).text()).not.toContain('DEP-WOO-LOCATION');expect(await(await page.request.get('/')).text()).not.toContain('DEP-WOO-COMBINED');
 await page.goto('/wp-admin/admin.php?page=codeslot&s=Dependency+Woo+location');await expect(page.locator('tbody .column-status')).toContainText('Paused (saved Active)');
 wp(`activate_plugin('woocommerce/woocommerce.php');`);await editor(page,id);await expect(page.locator('.codeslot-dependency-notice')).toHaveCount(0);expect(saved(id)).toEqual(before);expect(await(await page.request.get('/')).text()).toContain('DEP-WOO-LOCATION');expect(await(await page.request.get('/')).text()).toContain('DEP-WOO-COMBINED');
});

test('missing WooCommerce exclusions and alternative include groups pause header code instead of broadening output',async({page})=>{
 const id=create({name:'Dependency Woo exclusion',type:'html',location:'header',active:true,code:'<!-- DEP-EXCLUSION -->',conditions:{include:[[{key:'entire_site'}]],exclude:[[{key:'cart',op:'is'}]]}});
 expect(await(await page.request.get('/')).text()).toContain('DEP-EXCLUSION');wp(`deactivate_plugins('woocommerce/woocommerce.php');`);expect(await(await page.request.get('/')).text()).not.toContain('DEP-EXCLUSION');await editor(page,id);await expect(page.locator('.codeslot-dependency-notice')).toContainText('Requires active WooCommerce');await expect(page.locator('[name=active]')).toBeChecked();
 await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();expect(saved(id).conditions.exclude[0][0].key).toBe('cart');wp(`activate_plugin('woocommerce/woocommerce.php');`);expect(await(await page.request.get('/')).text()).toContain('DEP-EXCLUSION');
});

test('custom required plugins preserve requirements through deactivation, invalid saves, deletion and explicit removal',async({page})=>{
 const id=create({name:'Dependency custom plugin',type:'html',location:'footer',active:true,code:'<!-- DEP-CUSTOM -->'});await editor(page,id);await page.getByText('Choose required plugins',{exact:true}).click();await page.locator('[type=checkbox][name="requires[]"][value="'+plugin+'"]').check();await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();expect(saved(id).requires).toEqual([plugin]);expect(await(await page.request.get('/')).text()).toContain('DEP-CUSTOM');
 wp(`deactivate_plugins('${plugin}');`);await editor(page,id);await expect(page.locator('.codeslot-dependency-notice')).toContainText(plugin);expect(await(await page.request.get('/')).text()).not.toContain('DEP-CUSTOM');
 await page.locator('#codeslot-type').selectOption('php');await page.locator('#codeslot-source').evaluate(el=>el.codeslotEditor.setValue('if ('));await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-error')).toBeVisible();expect(saved(id).type).toBe('html');expect(saved(id).requires).toEqual([plugin]);await expect(page.locator('[type=hidden][name="requires[]"][value="'+plugin+'"]')).toHaveCount(1);await page.locator('#codeslot-type').selectOption('html'); await page.locator('#codeslot-source').evaluate(el=>el.codeslotEditor.setValue('<!-- DEP-CUSTOM-EDITED -->'));await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();expect(saved(id).requires).toEqual([plugin]);
 wp(`unlink(WP_PLUGIN_DIR.'/${plugin}');wp_clean_plugins_cache();`);await editor(page,id);await page.getByText('Choose required plugins',{exact:true}).click();await expect(page.locator('[type=checkbox][name="requires[]"][value="'+plugin+'"]').first()).toBeDisabled();await expect(page.locator('.codeslot-dependency-notice')).toContainText('Execution paused');await page.getByRole('button',{name:'Save Snippet',exact:true}).click();expect(saved(id).requires).toEqual([plugin]);
 await page.getByText('Choose required plugins',{exact:true}).click();await page.getByRole('button',{name:'Remove requirement',exact:true}).click();await page.getByRole('button',{name:'Save Snippet',exact:true}).click();expect(saved(id).requires).toEqual([]);expect(await(await page.request.get('/')).text()).toContain('DEP-CUSTOM-EDITED');
});

test('general PHP requiring a removed plugin skips safely and an inactive snippet stays inactive after restoration',async({page})=>{
 const id=create({name:'Dependency custom PHP',type:'php',location:'frontend',active:true,requires:[plugin],code:"add_action('wp_footer',static function(){echo '<!-- '.codeslot_dependency_browser_api().' -->';});"});
 expect(await(await page.request.get('/')).text()).toContain('DEPENDENCY-API-OK');wp(`deactivate_plugins('${plugin}');unlink(WP_PLUGIN_DIR.'/${plugin}');wp_clean_plugins_cache();`);const body=await(await page.request.get('/')).text();expect(body).not.toContain('DEPENDENCY-API-OK');expect(body).not.toContain('Fatal error');expect(saved(id).active).toBe(true);
 wp(`wp_set_current_user(1);CodeSlot\\Repository::status(${id},false);file_put_contents(WP_PLUGIN_DIR.'/${plugin}',base64_decode('${Buffer.from(source).toString('base64')}'));wp_clean_plugins_cache();activate_plugin('${plugin}');`);expect(saved(id).active).toBe(false);expect(await(await page.request.get('/')).text()).not.toContain('DEPENDENCY-API-OK');
});

test('paused setup and required-plugin controls remain contained on mobile',async({page})=>{
 const id=create({name:'Dependency mobile',type:'html',location:'woocommerce_after_single_product',active:true,code:'<!-- mobile -->',requires:[plugin]});wp(`deactivate_plugins('woocommerce/woocommerce.php');deactivate_plugins('${plugin}');`);
 for(const width of [1440,390,320]){await page.setViewportSize({width,height:1000});await editor(page,id);await page.getByText('Choose required plugins',{exact:true}).click();await page.getByLabel('Choose insertion location').click();await expect(page.locator('.codeslot-dependency-notice')).toBeVisible();expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);await page.screenshot({path:`/tmp/codeslot-dependency-${width}.png`,fullPage:true});}
});
