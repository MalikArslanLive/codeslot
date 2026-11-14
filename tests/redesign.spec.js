import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(...args){return execFileSync('docker',['compose','run','--rm','cli',...args],{encoding:'utf8'}).trim().split('\n').at(-1);}
function run(code){return wp('eval',code);}
function save(data){return run(`wp_set_current_user(1);echo CodeSlot\\Repository::save(json_decode(base64_decode('${Buffer.from(JSON.stringify(data)).toString('base64')}'),true));`);}
function option(key,value){wp('option','update',key,JSON.stringify(value),'--format=json');}
let ids=[],orders=[],original={};
test.setTimeout(180000);
test.beforeEach(async({page})=>{
 for(const key of ['codeslot_pixels','codeslot_settings','woocommerce_checkout_page_id'])original[key]=run(`echo wp_json_encode(get_option('${key}',null));`);
 option('codeslot_settings',{safe_mode:false});await login(page);
});
test.afterEach(()=>{
 for(const id of orders)run(`$o=wc_get_order(${id});if($o)$o->delete(true);`);orders=[];
 for(const id of ids)wp('post','delete',id,'--force');ids=[];
 for(const [key,value] of Object.entries(original)){if(value==='null')run(`delete_option('${key}');`);else wp('option','update',key,value,'--format=json');}original={};
});
async function mockMeta(page){
 await page.route('https://connect.facebook.net/**',route=>route.fulfill({contentType:'application/javascript',body:'window.metaCalls=[];window.fbq.callMethod=function(){window.metaCalls.push(Array.from(arguments));};window.fbq.queue.forEach(a=>window.fbq.callMethod.apply(null,a));'}));
}
async function metaEvent(page,name){await expect.poll(()=>page.evaluate(event=>(window.metaCalls||[]).filter(a=>a[1]===event).length,name)).toBe(1);}

test('library shows all recipes without pagination and filters/reset work live',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-library');
 await expect(page.locator('.codeslot-recipe')).toHaveCount(142);await expect(page.locator('.codeslot-recipe:visible')).toHaveCount(142);
 await expect(page.getByRole('navigation',{name:'Library pages'})).toHaveCount(0);
 await page.locator('[name=library_search]').fill('custom excerpt');await expect(page.locator('.codeslot-recipe:visible')).toHaveCount(2);
 await page.locator('[name=library_search]').fill('no-such-recipe-xyz');await expect(page.locator('#codeslot-library-empty')).toBeVisible();
 await page.getByRole('button',{name:'Clear filters',exact:true}).click();await expect(page.locator('.codeslot-recipe:visible')).toHaveCount(142);
 await page.locator('[name=library_category]').selectOption('Configuration guides');await expect(page.locator('.codeslot-recipe:visible')).toHaveCount(3);
});

test('full editor fills untitled names and shows read-only wrappers without altering source',async({page})=>{
 const id=save({name:'',type:'javascript',location:'footer',code:'window.csWrapperTest = 1;',active:false});ids.push(id);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 await expect(page.locator('#codeslot-name')).toHaveValue(`Untitled snippet #${id}`);
 await expect(page.locator('#codeslot-wrapper-open')).toHaveText('<script>');await expect(page.locator('#codeslot-wrapper-close')).toHaveText('</script>');
 expect(await page.locator('#codeslot-source').inputValue()).toBe('window.csWrapperTest = 1;');
 await page.locator('#codeslot-type').selectOption('php');await expect(page.locator('#codeslot-wrapper-open')).toHaveText('<?php');
 await page.locator('#codeslot-type').selectOption('css');await expect(page.locator('#codeslot-wrapper-open')).toHaveText('<style>');
 await page.locator('#codeslot-type').selectOption('javascript');
 await page.evaluate(()=>document.querySelector('#codeslot-source').codeslotEditor.setValue('<script>window.csWrapperTest = 2;</script>'));
 await expect(page.locator('#codeslot-wrapper-warning')).toBeVisible();
 await page.getByRole('button',{name:'Use HTML for this complete embed'}).click();
 await expect(page.locator('#codeslot-type')).toHaveValue('html');await expect(page.locator('#codeslot-wrapper-open')).toBeHidden();
 await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();
 const s=JSON.parse(run(`echo wp_json_encode(CodeSlot\\Repository::get(${id}));`));expect(s).toMatchObject({name:'',type:'html',code:'<script>window.csWrapperTest = 2;</script>',active:false});
});

test('snippet code-type tabs actually filter saved code types',async({page})=>{
 for(const [type,location,code] of [['php','frontend','return;'],['javascript','footer','void 0;'],['html','footer','<!-- type fixture -->']])ids.push(save({name:'Redesign '+type+' fixture',type,location,code,active:false}));
 await page.goto('/wp-admin/admin.php?page=codeslot&code_type=php');await expect(page.locator('.codeslot-table')).toContainText('Redesign php fixture');await expect(page.locator('.codeslot-table')).not.toContainText('Redesign javascript fixture');
 await page.getByRole('link',{name:'Javascript',exact:true}).click();await expect(page.locator('.codeslot-table')).toContainText('Redesign javascript fixture');await expect(page.locator('.codeslot-table')).not.toContainText('Redesign php fixture');
});

test('pixel ID examples and GTM save validate container IDs; selected events enter dataLayer after consent',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');await expect(page.locator('#codeslot-pixel-meta')).toHaveAttribute('placeholder','123456789012345');
 await expect(page.locator('#codeslot-pixel-gtm')).toHaveAttribute('placeholder','GTM-ABC1234');
 await page.locator('#codeslot-pixel-gtm').fill('GTM-TEST123');await page.locator('[name="pixels[gtm][enabled]"]').check();
 await page.locator('[name="pixels[gtm][events][]"][value=page_view]').check();await page.locator('[name="pixels[gtm][events][]"][value=click]').check();await page.locator('#codeslot-click-selector').fill('.gtm-click');
 await page.getByRole('button',{name:'Save conversion pixels'}).click();await expect(page.locator('.notice-success')).toBeVisible();
 const urls=[];await page.route('https://www.googletagmanager.com/**',route=>{urls.push(route.request().url());return route.fulfill({contentType:'application/javascript',body:'/* GTM adapter isolated from live account */'});});
 await page.goto('/');expect(urls).toHaveLength(0);
 await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:true}));await expect.poll(()=>urls.length).toBe(1);expect(urls[0]).toContain('/gtm.js?id=GTM-TEST123');
 expect(await page.evaluate(()=>window.dataLayer.filter(e=>e.event==='codeslot_page_view').length)).toBe(1);
 await page.evaluate(()=>{const b=document.createElement('button');b.className='gtm-click';b.textContent='GTM click fixture';document.body.appendChild(b);});await page.getByRole('button',{name:'GTM click fixture'}).click();
 expect(await page.evaluate(()=>window.dataLayer.filter(e=>e.event==='codeslot_click').length)).toBe(1);
 await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:false}));await page.getByRole('button',{name:'GTM click fixture'}).click();expect(await page.evaluate(()=>window.dataLayer.filter(e=>e.event==='codeslot_click').length)).toBe(1);
});

test('Meta selected ecommerce events fire on real product/cart/checkout paths and authorized purchase fixture',async({page})=>{
 const f=JSON.parse(run(`$p=new WC_Product_Simple();$p->set_name('CodeSlot redesign pixel fixture');$p->set_regular_price('25');$p->set_price('25');$p->set_status('publish');$p->save();$checkout=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'CodeSlot test checkout','post_content'=>'[woocommerce_checkout]'));update_option('woocommerce_checkout_page_id',$checkout);echo wp_json_encode(array('id'=>$p->get_id(),'url'=>get_permalink($p->get_id()),'checkout'=>$checkout,'checkout_url'=>get_permalink($checkout)));`));ids.push(String(f.id),String(f.checkout));
 option('codeslot_pixels',{meta:{id:'123456789012345',enabled:true,events:['page_view','view_content','add_to_cart','begin_checkout','purchase','click']},click_selector:'.meta-click'});
 await mockMeta(page);await page.addInitScript(()=>{window.CodeSlotConsent={marketing:true};});
 await page.goto(f.url);await metaEvent(page,'PageView');await metaEvent(page,'ViewContent');
 await page.locator('.single_add_to_cart_button').click();await metaEvent(page,'AddToCart');
 const cart=await page.evaluate(()=>window.metaCalls.find(a=>a[1]==='AddToCart')[2]);expect(cart.content_ids).toContain(String(f.id));expect(cart.value).toBe(25);
 await page.goto(f.checkout_url);await metaEvent(page,'InitiateCheckout');
 const o=JSON.parse(run(`$o=wc_create_order(array('customer_id'=>0));$o->add_product(wc_get_product(${f.id}),1);$o->calculate_totals();$o->update_status('completed');echo wp_json_encode(array('id'=>$o->get_id(),'url'=>$o->get_checkout_order_received_url()));`));orders.push(o.id);
 await page.goto(o.url);await metaEvent(page,'Purchase');expect(await page.evaluate(()=>window.metaCalls.find(a=>a[1]==='Purchase')[2].value)).toBe(25);
 await page.evaluate(()=>{const b=document.createElement('button');b.type='button';b.className='meta-click';b.style.cssText='position:fixed;top:90px;right:20px;z-index:999999';b.textContent='Meta click fixture';document.body.appendChild(b);});await page.getByRole('button',{name:'Meta click fixture'}).click();await metaEvent(page,'CodeSlotClick');
 await page.reload();expect(await page.evaluate(()=>(window.metaCalls||[]).filter(a=>a[1]==='Purchase').length)).toBe(0);
});

test('Meta unticked view-content event stays off on a product page',async({page})=>{
 const f=JSON.parse(run(`$p=new WC_Product_Simple();$p->set_name('Checkbox event fixture');$p->set_regular_price('5');$p->set_status('publish');$p->save();echo wp_json_encode(array('id'=>$p->get_id(),'url'=>get_permalink($p->get_id())));`));ids.push(String(f.id));
 option('codeslot_pixels',{meta:{id:'123456789012345',enabled:true,events:['page_view']}});await mockMeta(page);await page.addInitScript(()=>{window.CodeSlotConsent={marketing:true};});
 await page.goto(f.url);await metaEvent(page,'PageView');expect(await page.evaluate(()=>window.metaCalls.some(a=>a[1]==='ViewContent'))).toBe(false);
});
