import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {URL} from 'node:url';
import {setTimeout} from 'node:timers';
import {login} from './login.js';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encode=value=>Buffer.from(JSON.stringify(value)).toString('base64');
let fixture,original;
test.setTimeout(180000);
test.beforeEach(async({page})=>{
 original=wp(`echo wp_json_encode(get_option('codeslot_settings',[]));`);
 fixture=JSON.parse(wp(`wp_set_current_user(1);update_option('codeslot_settings',['safe_mode'=>false]);$title='Rule Needle '.str_repeat('A lengthy product page title with spaces العربية اردو ',10);$a=wp_insert_post(['post_title'=>$title,'post_type'=>'page','post_status'=>'publish','post_content'=>'Condition search target']);$b=wp_insert_post(['post_title'=>'Different page','post_type'=>'page','post_status'=>'publish','post_content'=>'Rule Needle body only']);$c=wp_insert_post(['post_title'=>'Rule Needle Demo Item','post_type'=>'codeslot_demo','post_status'=>'publish']);$term=wp_insert_term('Rule Needle Category '.uniqid(),'category');$topic=wp_insert_term('Rule Needle Topic '.uniqid(),'codeslot_topic');add_role('rule_tester','Rule Needle Tester',['read'=>true]);echo wp_json_encode(['ids'=>[$a,$b,$c],'title'=>get_the_title($a),'url'=>get_permalink($a),'other'=>get_permalink($b),'terms'=>[$term['term_id'],$topic['term_id']]]);`));
 await login(page);await page.goto('/wp-admin/admin.php?page=codeslot-new');
});
test.afterEach(()=>{wp(`wp_set_current_user(1);$f=json_decode(base64_decode('${encode(fixture)}'),true);foreach($f['ids'] as $id)wp_delete_post($id,true);wp_delete_term($f['terms'][0],'category');wp_delete_term($f['terms'][1],'codeslot_topic');foreach(get_posts(['post_type'=>'codeslot_snippet','post_status'=>array_values(get_post_stati()),'posts_per_page'=>-1,'s'=>'Conditions UI']) as $s)wp_delete_post($s->ID,true);remove_role('rule_tester');update_option('codeslot_settings',json_decode(base64_decode('${Buffer.from(original).toString('base64')}'),true));`);});
async function rule(page,key){if(!await page.getByLabel('Condition',{exact:true}).count())await page.getByRole('button',{name:'OR group',exact:true}).first().click();await page.getByLabel('Condition',{exact:true}).first().selectOption(key);}
async function choose(page,key,query,value){await rule(page,key);const input=page.getByRole('combobox',{name:/^Search /});await input.fill(query);const option=page.locator('.codeslot-rule-results [role=option]').filter({hasText:value}).first();await expect(option).toBeVisible();await option.click();return input;}

test('friendly labels, guide and value fields follow each condition without redundant product',async({page})=>{
 await rule(page,'home');await expect(page.getByLabel('Condition',{exact:true}).locator('option:checked')).toHaveText('Blog Posts Page');await expect(page.locator('.codeslot-rule-search')).toHaveCount(0);await expect(page.locator('#codeslot-conditions input')).toHaveCount(0);
 for(const key of ['entire_site','frontend','admin','front_page','archive','search','404','logged_in','mobile','shop','cart','checkout','account']){await rule(page,key);await expect(page.locator('#codeslot-conditions input')).toHaveCount(0);}
 await expect(page.getByLabel('Condition',{exact:true}).locator('option[value=product]')).toHaveCount(0);
 const guide=page.getByRole('link',{name:/View the guide/});await expect(guide).toHaveAttribute('href','https://github.com/MalikArslanLive/codeslot/blob/main/docs/CONDITIONS.md');await expect(guide).toHaveAttribute('target','_blank');
 await rule(page,'url');const path=page.getByRole('textbox',{name:'URL path',exact:true});await expect(path).toHaveAttribute('placeholder','Example URL path: /your-page/');await expect(page.locator('.codeslot-rule-help')).toContainText('Enter only the path after your domain, for example /contact/');await path.fill('/contact/');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('/contact/');for(const width of [1440,320]){await page.setViewportSize({width,height:900});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);}await rule(page,'frontend');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('');
});

test('title search selects a real page by keyboard, saves its identity, reloads its name and limits actual output',async({page})=>{
 await rule(page,'singular');const input=page.getByRole('combobox',{name:'Search specific post or page'});await input.fill('Rule Needle');await expect(page.getByRole('option',{name:/Different page/})).toHaveCount(0);await expect(page.locator('.codeslot-rule-results [role=option]')).toHaveCount(2);
 // Choose the long page title with keyboard navigation rather than entering an ID.
 const options=await page.locator('.codeslot-rule-results [role=option]').allTextContents();const i=options.findIndex(v=>v.includes('lengthy'));for(let n=0;n<=i;n++)await input.press('ArrowDown');await input.press('Enter');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe(String(fixture.ids[0]));
 await page.locator('#codeslot-name').fill('Conditions UI page selector');await page.locator('#codeslot-source').evaluate(el=>el.codeslotEditor.setValue('<!-- CONDITIONS-UI-OUTPUT -->'));await page.locator('[name=active]').check();await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();await expect(page.getByRole('combobox',{name:'Search specific post or page'})).toHaveValue(fixture.title);
 expect(await(await page.request.get(fixture.url)).text()).toContain('CONDITIONS-UI-OUTPUT');expect(await(await page.request.get(fixture.other)).text()).not.toContain('CONDITIONS-UI-OUTPUT');
});

test('plugin post types, public taxonomy terms and plugin roles are searched by name and store correct values',async({page})=>{
 await choose(page,'post_type','Demo','Demo Items');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('codeslot_demo');
 await choose(page,'term','Rule Needle Topic','codeslot_topic');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('codeslot_topic:'+fixture.terms[1]);
 await choose(page,'role','Rule Needle','Rule Needle Tester');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('rule_tester');
 await page.getByRole('button',{name:'Clear selection',exact:true}).click();expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('');
});

test('long multilingual titles stay contained at desktop and mobile widths with full selection on focus',async({page})=>{
 for(const width of [1440,768,390,320]){await page.setViewportSize({width,height:1000});await page.goto('/wp-admin/admin.php?page=codeslot-new');await rule(page,'singular');const input=page.getByRole('combobox',{name:'Search specific post or page'});await input.fill('Rule Needle');const result=page.locator('.codeslot-rule-results [role=option]').filter({hasText:'lengthy'});await expect(result).toBeVisible();expect(await result.locator('.codeslot-result-title').evaluate(el=>el.offsetHeight)).toBeLessThan(60);expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);await result.click();await input.focus();await expect(page.locator('.codeslot-selected-full')).toContainText(fixture.title);expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);await page.screenshot({path:`/tmp/codeslot-conditions-${width}.png`,fullPage:true});}
});

test('search endpoint rejects anonymous, missing/invalid nonce, unknown keys and oversized queries',async({page,playwright})=>{
 const config=await page.evaluate(()=>window.codeslotConditions);const anonymous=await playwright.request.newContext();const params={action:'codeslot_conditions',nonce:config.nonce,key:'singular',q:'Rule Needle'};
 expect((await anonymous.get(config.url,{params})).status()).toBe(400);await anonymous.dispose();
 for(const nonce of ['', 'invalid'])expect((await page.request.get(config.url,{params:{...params,nonce}})).status()).toBe(403);
 expect((await page.request.get(config.url,{params:{...params,key:'users'}})).status()).toBe(400);expect((await page.request.get(config.url,{params:{...params,q:'x'.repeat(201)}})).status()).toBe(400);
 expect((await(await page.request.get(config.url,{params:{...params,q:'R'}})).json()).data).toEqual([]);
 const response=await page.request.get(config.url,{params});expect(response.status()).toBe(200);expect((await response.json()).data.every(item=>!item.label.includes('Different page'))).toBe(true);
});

test('WooCommerce-only rules remain visible disabled without WooCommerce, and return after activation',async({page})=>{
 try{wp(`deactivate_plugins('woocommerce/woocommerce.php');`);await page.reload();await rule(page,'frontend');for(const key of ['shop','cart','checkout','account'])await expect(page.getByLabel('Condition',{exact:true}).locator(`option[value=${key}]`)).toBeDisabled();await expect(page.locator('.codeslot-commerce-note')).toContainText('Install and activate WooCommerce');}
 finally{wp(`activate_plugin('woocommerce/woocommerce.php');`);}
 await page.reload();await rule(page,'shop');await expect(page.getByLabel('Condition',{exact:true}).locator('option[value=shop]')).toBeEnabled();
});

test('search failures recover, stale queries cannot overwrite the latest results and unselected text cannot be saved',async({page})=>{
 await rule(page,'singular');let fail=true;await page.route('**/admin-ajax.php?**',async route=>{const url=new URL(route.request().url());if(url.searchParams.get('action')!=='codeslot_conditions')return route.continue();if(fail)return route.fulfill({status:500,contentType:'application/json',body:'{"success":false}'});if(url.searchParams.get('value'))await new Promise(resolve=>setTimeout(resolve,500));if(url.searchParams.get('q')==='Old query'){await new Promise(resolve=>setTimeout(resolve,500));return route.fulfill({json:{success:true,data:[{value:'123',label:'Old result',detail:'Page'}]}});}return route.fulfill({json:{success:true,data:[{value:String(fixture.ids[0]),label:'Latest safe <img src=x onerror=alert(1)>',detail:'Page · ID '+fixture.ids[0]}]}});});
 const input=page.getByRole('combobox',{name:'Search specific post or page'});await input.fill('First query');await expect(page.getByRole('status')).toContainText('Could not load results');expect(await input.evaluate(el=>el.checkValidity())).toBe(false);fail=false;await input.fill('Old query');await page.waitForTimeout(300);await input.fill('New query');await expect(page.locator('.codeslot-rule-results')).toContainText('Latest safe');await page.waitForTimeout(600);await expect(page.locator('.codeslot-rule-results')).not.toContainText('Old result');await expect(page.locator('.codeslot-rule-results img')).toHaveCount(0);await page.locator('.codeslot-rule-results [role=option]').click();expect(await input.evaluate(el=>el.checkValidity())).toBe(true);
 await input.fill('Old query');await input.press('ArrowDown');await expect(input).toHaveAttribute('aria-expanded','false');await page.waitForTimeout(300);await page.getByRole('button',{name:'Clear selection',exact:true}).click();await page.waitForTimeout(600);await expect(input).toHaveValue('');await expect(page.locator('.codeslot-rule-results')).toBeHidden();
 const saved=Number(wp(`wp_set_current_user(1);echo CodeSlot\\Repository::save(['name'=>'Conditions UI delayed selection','type'=>'html','location'=>'header','code'=>'<!-- delayed -->','conditions'=>['include'=>[[['key'=>'singular','value'=>'${fixture.ids[0]}']]]]]);`));await page.goto('/wp-admin/admin.php?page=codeslot-new&id='+saved);await page.getByRole('button',{name:'Clear selection',exact:true}).click();await page.waitForTimeout(650);await expect(page.getByRole('combobox',{name:'Search specific post or page'})).toHaveValue('');expect(JSON.parse(await page.locator('#codeslot-conditions-json').inputValue()).include[0][0].value).toBe('');
});
