import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encoded=value=>Buffer.from(JSON.stringify(value)).toString('base64');
let ids=[],pages=[],original;
function create(rows){const result=JSON.parse(wp(`wp_set_current_user(1);$rows=json_decode(base64_decode('${encoded(rows)}'),true);$ids=[];foreach($rows as $row){$id=CodeSlot\\Repository::save($row);if(is_wp_error($id))throw new Exception($id->get_error_message());$ids[]=$id;}echo wp_json_encode($ids);`));ids.push(...result);return result;}
function snapshot(){return JSON.parse(wp(`$out=[];foreach(json_decode(base64_decode('${encoded(ids)}'),true) as $id)$out[$id]=CodeSlot\\Repository::get($id);echo wp_json_encode($out);`));}
test.setTimeout(240000);
test.beforeEach(async({page})=>{
 original=JSON.parse(wp(`$out=[];foreach(['codeslot_settings','codeslot_pixels','codeslot_global'] as $key)$out[$key]=get_option($key,null);foreach(['codeslot_list_preferences','managetoplevel_page_codeslotcolumnshidden'] as $key)$out[$key]=get_user_meta(1,$key,true);echo wp_json_encode($out);`));
 wp(`update_option('codeslot_settings',['safe_mode'=>false]);delete_user_meta(1,'codeslot_list_preferences');delete_user_meta(1,'managetoplevel_page_codeslotcolumnshidden');`);
 await login(page);
});
test.afterEach(()=>{
 if(!original)return;
 wp(`$data=json_decode(base64_decode('${encoded(original)}'),true);foreach(['codeslot_settings','codeslot_pixels','codeslot_global'] as $key){if(null===$data[$key])delete_option($key);else update_option($key,$data[$key]);}foreach(['codeslot_list_preferences','managetoplevel_page_codeslotcolumnshidden'] as $key){if(''===$data[$key])delete_user_meta(1,$key);else update_user_meta(1,$key,$data[$key]);}foreach(json_decode(base64_decode('${encoded([...ids,...pages])}'),true) as $id)wp_delete_post($id,true);`);
 ids=[];pages=[];
});
async function list(page,search='Review'){await page.goto('/wp-admin/admin.php?page=codeslot&s='+encodeURIComponent(search));}
function row(page,id){return page.locator('.codeslot-table tbody tr').filter({has:page.locator(`[name="ids[]"][value="${id}"]`)});}
async function bulk(page,action){await page.locator('#codeslot-select-page').check();await page.locator('[name=bulk]').selectOption(action);await page.getByRole('button',{name:'Apply',exact:true}).click();}

test('every snippet type and legacy wrappers toggle individually and in mixed bulk without source changes',async({page})=>{
 const types=[['php','frontend','/* Review safe PHP */'],['javascript','footer','window.ReviewToggle=1;'],['css','header','.review-toggle{color:green}'],['html','footer','<!-- Review HTML -->'],['text','footer','Review text'],['content','shortcode','<aside>Review content</aside>'],['shortcode','shortcode','[codeslot id="0"]']];
 const created=create(types.map(([type,location,code])=>({name:'Review types '+type,type,location,code,active:false})));
 const legacy=create([{name:'Review types legacy JS',type:'javascript',location:'footer',code:'window.ReviewLegacy=1;',active:false},{name:'Review types legacy CSS',type:'css',location:'header',code:'.review-legacy{color:red}',active:false}]);
 wp(`wp_update_post(wp_slash(['ID'=>${legacy[0]},'post_content'=>'<script>window.ReviewLegacy=1;</script>']));wp_update_post(wp_slash(['ID'=>${legacy[1]},'post_content'=>'<style>.review-legacy{color:red}</style>']));`);
 const before=snapshot();
 for(const id of [...created,...legacy]){
  await list(page,'Review types');await row(page,id).getByRole('switch').click();await expect(row(page,id).getByRole('switch')).toHaveAttribute('aria-checked','true');
  await row(page,id).getByRole('switch').click();await expect(row(page,id).getByRole('switch')).toHaveAttribute('aria-checked','false');
 }
 await bulk(page,'activate');await expect(page.locator('.notice-success')).toBeVisible();
 let after=snapshot();for(const id of ids){expect(after[id].active).toBe(true);expect(after[id].code).toBe(before[id].code);}
 await bulk(page,'deactivate');await expect(page.locator('.notice-success')).toBeVisible();
 after=snapshot();for(const id of ids){expect(after[id]).toEqual(before[id]);}
 const frontend=await(await page.request.get('/')).text();expect(frontend).not.toContain('ReviewLegacy=1');
});

test('empty selection stays on the filtered list; server fallback returns an inline notice',async({page})=>{
 create([{name:'Review empty selection',type:'html',location:'footer',code:'<!-- keep -->',active:false}]);await list(page,'Review empty');
 await page.getByRole('button',{name:'Apply',exact:true}).click();await expect(page.locator('.codeslot-selection-error')).toContainText('Select at least one');await expect(page).toHaveURL(/page=codeslot&s=Review/);
 const form=await page.locator('#codeslot-bulk-form').evaluate(f=>Object.fromEntries(new window.FormData(f)));
 const response=await page.request.post('/wp-admin/admin-post.php',{form:{...form,bulk:'deactivate'},maxRedirects:0});expect(response.status()).toBe(302);
 await page.goto(response.headers().location);await expect(page.locator('.notice-error')).toContainText('Select 1–100');await expect(page.locator('[name=s]')).toHaveValue('Review empty');
});

test('mixed locked bulk selection is rejected before changing any eligible snippet',async({page})=>{
 const [first,locked]=create([{name:'Review locked first',type:'html',location:'footer',code:'<!-- first -->',active:false},{name:'Review locked second',type:'javascript',location:'footer',code:'void 0;',active:false,locked:true}]);
 await list(page,'Review locked');await bulk(page,'activate');await expect(page.locator('.notice-error')).toContainText('No changes were applied');
 const state=snapshot();expect(state[first].active).toBe(false);expect(state[locked].active).toBe(false);
});

test('missing Pixel ID reports inline and preserves other fields without persisting invalid settings',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');await page.locator('[name="pixels[meta][enabled]"]').check();await page.locator('#codeslot-pixel-meta').fill('');await page.locator('#codeslot-click-selector').fill('.review-click');
 await page.getByRole('button',{name:'Save conversion pixels'}).click();await expect(page.locator('.codeslot-field-error')).toContainText('public tracking ID');await expect(page).toHaveURL(/page=codeslot-pixels/);
 const form=await page.locator('#codeslot-pixel-meta').evaluate(el=>Object.fromEntries(new window.FormData(el.form)));
 const response=await page.request.post('/wp-admin/admin-post.php',{form,maxRedirects:0});expect(response.status()).toBe(302);
 await page.goto(response.headers().location);await expect(page.locator('.notice-error')).toContainText('public tracking ID');await expect(page.locator('#codeslot-click-selector')).toHaveValue('.review-click');await expect(page.locator('[name="pixels[meta][enabled]"]')).toBeChecked();
 expect(JSON.parse(wp(`echo wp_json_encode(get_option('codeslot_pixels',null));`))).toEqual(original.codeslot_pixels);
});

test('native Screen Options save column visibility and pagination preferences per user',async({page})=>{
 create(Array.from({length:5},(_,i)=>({name:'Review columns '+i,type:'html',location:'footer',code:'<!-- columns -->',active:false,categories:['Review category']})));
 await list(page,'Review columns');await expect(page.locator('.codeslot-table th').last()).toHaveText('Status');await expect(page.locator('.codeslot-table .column-author').first()).toBeVisible();
 await page.locator('#show-settings-link').click();
 const hidden=page.waitForResponse(r=>r.url().includes('admin-ajax.php') && (r.request().postData()||'').includes('hidden-columns'));
 await page.locator('#author-hide').uncheck();await hidden;
 await page.locator('[name=codeslot_per_page]').fill('3');await page.locator('[name=codeslot_sort]').selectOption('date');await page.locator('[name=codeslot_direction]').selectOption('DESC');await page.getByRole('button',{name:'Apply list preferences'}).click();
 await list(page,'Review columns');await expect(page.locator('.codeslot-table tbody [name="ids[]"]')).toHaveCount(3);await expect(page.locator('.column-author').first()).toBeHidden();
 await page.reload();await expect(page.locator('.column-author').first()).toBeHidden();await expect(page.locator('.codeslot-name').first()).not.toContainText('Author:');
});

test('bulk checkboxes and Tools controls remain compact at desktop and mobile widths',async({page})=>{
 create([{name:'Review layout',type:'html',location:'footer',code:'<!-- layout -->',active:false}]);
 for(const width of [1440,390,320]){
  await page.setViewportSize({width,height:1000});await list(page,'Review layout');await page.locator('[name=bulk]').selectOption('edit');
  for(const name of ['bulk_priority_enabled','bulk_categories_enabled','bulk_tags_enabled']){const box=await page.locator(`[name=${name}]`).boundingBox();expect(box.width).toBeLessThanOrEqual(20);expect(box.height).toBeGreaterThanOrEqual(16);}
  expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);
  await page.goto('/wp-admin/admin.php?page=codeslot-tools');await expect(page.getByRole('heading',{name:'Local templates'})).toHaveCount(0);await expect(page.getByRole('button',{name:'Refresh report'})).toBeVisible();expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);
 }
});

test('device and schedule rules filter real HTTP output without changing saved activation',async({page})=>{
 const now=Math.floor(Date.now()/1000);
 create([{name:'Review mobile',type:'html',location:'footer',code:'<!-- REVIEW-MOBILE -->',active:true,device:'mobile'},{name:'Review desktop',type:'html',location:'footer',code:'<!-- REVIEW-DESKTOP -->',active:true,device:'desktop'},{name:'Review future',type:'html',location:'footer',code:'<!-- REVIEW-FUTURE -->',active:true,starts:now+3600},{name:'Review expired',type:'html',location:'footer',code:'<!-- REVIEW-EXPIRED -->',active:true,ends:now-3600},{name:'Review live',type:'html',location:'footer',code:'<!-- REVIEW-LIVE -->',active:true,starts:now-3600,ends:now+3600}]);
 const mobile=await(await page.request.get('/',{headers:{'User-Agent':'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile','Sec-CH-UA-Mobile':'?1'}})).text();const desktop=await(await page.request.get('/',{headers:{'User-Agent':'Mozilla/5.0 (X11; Linux x86_64)','Sec-CH-UA-Mobile':'?0'}})).text();
 expect(mobile).toContain('REVIEW-MOBILE');expect(mobile).not.toContain('REVIEW-DESKTOP');expect(desktop).toContain('REVIEW-DESKTOP');expect(desktop).not.toContain('REVIEW-MOBILE');
 for(const source of [mobile,desktop]){expect(source).not.toContain('REVIEW-FUTURE');expect(source).not.toContain('REVIEW-EXPIRED');expect(source).toContain('REVIEW-LIVE');}
 expect(Object.values(snapshot()).every(s=>s.active)).toBe(true);
});

test('static JS file and JavaScript compaction preserve source and execute template/regex semantics',async({page})=>{
 const code='/* removable comment */\nconst reviewWord = `hello ${2 + 3}`;\nwindow.ReviewCompact = {word:reviewWord,match:/a\\/b/.test("a/b")};';
 const [id]=create([{name:'Review file',type:'javascript',location:'footer',code,active:true,load_file:true,compact:true},{name:'Review CSS file',type:'css',location:'header',code:'.review-file-target{color:rgb(1,2,3)!important}',active:true,load_file:true}]);
 try{
  await page.goto('/');await expect.poll(()=>page.evaluate(()=>window.ReviewCompact)).toEqual({word:'hello 5',match:true});
  await page.evaluate(()=>{const target=document.createElement('div');target.className='review-file-target';target.textContent='CSS file fixture';document.body.append(target);});await expect(page.locator('.review-file-target')).toHaveCSS('color','rgb(1, 2, 3)');
  const css=await page.locator('link[href*="/codeslot-assets/"]').first().getAttribute('href');expect((await page.request.get(css)).status()).toBe(200);
  const url=await page.locator('script[src*="/codeslot-assets/"]').first().getAttribute('src');const response=await page.request.get(url);expect(response.status()).toBe(200);const compiled=await response.text();expect(compiled).not.toContain('removable comment');expect(compiled.length).toBeLessThan(code.length);expect(snapshot()[id].code).toBe(code);
  await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);await expect(page.locator('[name=load_file]')).toBeChecked();await expect(page.locator('[name=compact]')).toBeChecked();
  wp(`$u=wp_upload_dir();foreach(array_merge(glob($u['basedir'].'/codeslot-assets/codeslot-*.js'),glob($u['basedir'].'/codeslot-assets/codeslot-*.css')) as $file){$content=file_get_contents($file);if(false!==strpos($content,'ReviewCompact')||false!==strpos($content,'review-file-target'))wp_delete_file($file);}`);
 }finally{wp(`wp_set_current_user(1);CodeSlot\\Repository::status(${id},false);`);}
});

test('CSS-selector insertion supports all five positions and executes an inserted script once',async({page})=>{
 const html=['before','after','prepend','append','replace','script'].map(value=>`<div id="review-${value}">Original ${value}</div>`).join('');
 const fixture=JSON.parse(wp(`wp_set_current_user(1);$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Review selector page','post_content'=>base64_decode('${Buffer.from(html).toString('base64')}')]);echo wp_json_encode(['id'=>$id,'url'=>get_permalink($id)]);`));pages.push(fixture.id);
 create(['before','after','prepend','append','replace'].map(position=>({name:'Review selector '+position,type:'html',location:'selector_'+position,selector:'#review-'+position,code:`<span id="inserted-${position}">${position}</span>`,active:true,conditions:{include:[[{key:'singular',value:String(fixture.id)}]],exclude:[]}})));
 create([{name:'Review selector script',type:'javascript',location:'selector_append',selector:'#review-script',code:'window.ReviewSelector=(window.ReviewSelector||0)+1;',active:true,conditions:{include:[[{key:'singular',value:String(fixture.id)}]],exclude:[]}}]);
 await page.goto(fixture.url);for(const position of ['before','after','prepend','append','replace'])await expect(page.locator('#inserted-'+position)).toBeVisible();
 await expect(page.locator('#review-replace')).toHaveCount(0);await expect.poll(()=>page.evaluate(()=>window.ReviewSelector)).toBe(1);
 expect(await page.locator('#review-prepend').innerHTML()).toMatch(/^<span/);expect(await page.locator('#review-append').innerHTML()).toMatch(/<\/span>$/);
 await page.goto('/');await expect(page.locator('[id^="inserted-"]')).toHaveCount(0);
});

test('repeated unchanged saves create no history; each actual field change creates one snapshot',async({page})=>{
 const [id,legacy]=create([{name:'Review revisions',type:'javascript',location:'footer',code:'window.ReviewRevision=1;',active:false},{name:'',type:'javascript',location:'footer',code:'void 0;',active:false}]);
 wp(`wp_update_post(wp_slash(['ID'=>${legacy},'post_content'=>'<script>window.ReviewLegacyHistory=1;</script>']));`);
 const result=JSON.parse(wp(`wp_set_current_user(1);$id=${id};$native=count(wp_get_post_revisions($id));$s=CodeSlot\\Repository::get($id);for($i=0;$i<10;$i++){CodeSlot\\Repository::save($s,$id);}echo wp_json_encode(['history'=>get_post_meta($id,'_codeslot_history',true),'native_before'=>$native,'native_after'=>count(wp_get_post_revisions($id))]);`));
 expect(result.history).toBe('');expect(result.native_after).toBe(result.native_before);
 for(const current of [id,legacy]){
  await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${current}`);
  for(let i=0;i<3;i++){await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();}
  expect(wp(`echo wp_json_encode(get_post_meta(${current},'_codeslot_history',true));`)).toBe('""');
 }
 const changed=JSON.parse(wp(`wp_set_current_user(1);$id=${id};$changes=[['name'=>'Review changed'],['description'=>'Changed description'],['priority'=>14],['categories'=>['Changed category']],['tags'=>['Changed tag']],['code'=>'window.ReviewRevision=2;'],['location'=>'header'],['conditions'=>['include'=>[[['key'=>'frontend']]],'exclude'=>[]]],['device'=>'mobile'],['starts'=>time()+3600],['ends'=>time()+7200],['load_file'=>true],['compact'=>true],['type'=>'html','load_file'=>false,'compact'=>false]];$counts=[];foreach($changes as $change){$s=array_merge(CodeSlot\\Repository::get($id),$change);$r=CodeSlot\\Repository::save($s,$id);if(is_wp_error($r))throw new Exception($r->get_error_message());$counts[]=count(get_post_meta($id,'_codeslot_history',true));CodeSlot\\Repository::save(CodeSlot\\Repository::get($id),$id);}echo wp_json_encode($counts);`));
 expect(changed).toEqual(Array.from({length:14},(_,i)=>i+1));
});

test('editor insertion method, device and schedule controls save and reload consistently',async({page})=>{
 const [id]=create([{name:'Review editor controls',type:'javascript',location:'footer',code:'window.ReviewControls=1;',active:false}]);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 await page.locator('[name=insertion_method][value=shortcode]').check();await expect(page.locator('#codeslot-location')).toHaveValue('shortcode');
 await page.locator('[name=device][value=mobile]').check();await page.locator('#codeslot-starts').fill('2030-01-01T10:00');await page.locator('#codeslot-ends').fill('2030-01-02T10:00');await page.locator('[name=load_file]').check();await page.locator('[name=compact]').check();
 await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('.notice-success')).toBeVisible();await expect(page.locator('[name=device][value=mobile]')).toBeChecked();await expect(page.locator('#codeslot-starts')).toHaveValue('2030-01-01T10:00');await expect(page.locator('[name=load_file]')).toBeChecked();
 await page.locator('#codeslot-type').selectOption('php');await expect(page.locator('[name=insertion_method][value=shortcode]')).toBeDisabled();await expect(page.locator('[name=load_file]')).toBeDisabled();await page.locator('#codeslot-location').selectOption('everywhere');await expect(page.locator('#codeslot-location-help')).toContainText('AJAX, REST, cron and CLI are excluded');
});

test('Settings, slot, lock, Tools and pixel checkboxes have branded states and working persistence',async({page})=>{
 const brand=async locator=>{await expect(locator).toBeChecked();expect(await locator.evaluate(el=>window.getComputedStyle(el).backgroundColor)).toBe('rgb(184, 232, 78)');};
 await page.goto('/wp-admin/admin.php?page=codeslot-settings');
 await page.locator('[name=safe_mode]').check();await page.locator('[name=delete_data]').check();await page.getByRole('button',{name:'Save Changes',exact:true}).click();await brand(page.locator('[name=safe_mode]'));await brand(page.locator('[name=delete_data]'));
 await page.locator('[name=safe_mode]').focus();await page.keyboard.press('Space');await expect(page.locator('[name=safe_mode]')).not.toBeChecked();await page.getByRole('button',{name:'Save Changes',exact:true}).click();await expect(page.locator('[name=safe_mode]')).not.toBeChecked();
 await page.goto('/wp-admin/admin.php?page=codeslot-global');await page.locator('[name="slots[header][enabled]"]').check();await page.locator('#submit').click();await brand(page.locator('[name="slots[header][enabled]"]'));
 const [id]=create([{name:'Review checkbox lock',type:'html',location:'footer',code:'<!-- lock -->',active:false}]);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);await page.locator('[name=locked]').check();await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await brand(page.locator('[name=locked]'));await page.locator('[name=unlock]').check();await page.locator('[name=locked]').uncheck();await page.getByRole('button',{name:'Save Snippet',exact:true}).click();await expect(page.locator('[name=locked]')).not.toBeChecked();
 await page.goto('/wp-admin/admin.php?page=codeslot-tools');await page.locator('[name=system_details]').check();await page.getByRole('button',{name:'Refresh report'}).click();await brand(page.locator('[name=system_details]'));
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');await page.locator('#codeslot-pixel-meta').fill('123456789012345');await page.locator('[name="pixels[meta][enabled]"]').check();await page.locator('[name="pixels[meta][events][]"][value=page_view]').check();await page.getByRole('button',{name:'Save conversion pixels'}).click();await brand(page.locator('[name="pixels[meta][events][]"][value=page_view]'));await expect(page.locator('[name="pixels[meta][enabled]"]')).toBeChecked();
});

test('WordPress Trash views, Undo, inactive restore, bulk lifecycle and permanent-delete guard work',async({page})=>{
 const created=create([{name:'Review lifecycle JS',type:'javascript',location:'footer',code:'window.ReviewTrash=1;',active:true},{name:'Review lifecycle HTML',type:'html',location:'footer',code:'<!-- REVIEW-TRASH -->',active:false}]);
 await list(page,'Review lifecycle');await expect(page.locator('.codeslot-status-views')).toContainText('Active');await expect(page.locator('.codeslot-status-views')).not.toContainText('Outdated');
 await row(page,created[0]).getByRole('button',{name:'Trash',exact:true}).click();expect(snapshot()[created[0]]).toMatchObject({trashed:true,active:false});
 await page.getByRole('button',{name:'Undo (restore inactive)',exact:true}).click();expect(snapshot()[created[0]]).toMatchObject({trashed:false,active:false});
 await list(page,'Review lifecycle');await bulk(page,'trash');for(const s of Object.values(snapshot()))expect(s.trashed).toBe(true);
 await page.goto('/');expect(await page.content()).not.toContain('REVIEW-TRASH');
 await page.goto('/wp-admin/admin.php?page=codeslot&status=trash&s=Review+lifecycle');await expect(page.locator('.codeslot-table [role=switch]')).toHaveCount(0);await bulk(page,'restore');for(const s of Object.values(snapshot()))expect(s).toMatchObject({trashed:false,active:false});
 const nonce=await page.locator('#codeslot-delete-form [name=_wpnonce]').inputValue();const refused=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'delete',id:String(created[0]),confirm:'1',_wpnonce:nonce}});expect(await refused.text()).toContain('Only trashed snippets');expect(snapshot()[created[0]]).not.toBeNull();
 await list(page,'Review lifecycle');await bulk(page,'trash');await page.goto('/wp-admin/admin.php?page=codeslot&status=trash&s=Review+lifecycle');page.once('dialog',dialog=>dialog.accept());await bulk(page,'delete');for(const s of Object.values(snapshot()))expect(s).toBeNull();
});

test('GA4 and Google Ads save independently and send to distinct destinations through one SDK',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');
 await expect(page.getByRole('group',{name:'Google Analytics 4',exact:true})).toBeVisible();await expect(page.getByRole('group',{name:'Google Ads',exact:true})).toBeVisible();
 for(const [provider,id] of [['google','G-REVIEW12345'],['google_ads','AW-1234567890']]){
  await page.locator(`#codeslot-pixel-${provider}`).fill(id);await page.locator(`[name="pixels[${provider}][enabled]"]`).check();for(const event of ['page_view','click'])await page.locator(`[name="pixels[${provider}][events][]"][value=${event}]`).check();
 }
 await page.locator('[name="pixels[google_ads][label]"]').fill('ReviewLabel123');await page.locator('#codeslot-click-selector').fill('.review-google-click');await page.getByRole('button',{name:'Save conversion pixels'}).click();await expect(page.locator('.notice-success')).toBeVisible();await expect(page.locator('#codeslot-pixel-google')).toHaveValue('G-REVIEW12345');await expect(page.locator('#codeslot-pixel-google_ads')).toHaveValue('AW-1234567890');
 const urls=[];await page.route('https://www.googletagmanager.com/gtag/js?**',route=>{urls.push(route.request().url());return route.fulfill({contentType:'application/javascript',body:'/* isolated Google SDK fixture */'});});
 await page.goto('/');expect(urls).toHaveLength(0);await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:true}));await expect.poll(()=>urls.length).toBe(1);
 await page.evaluate(()=>{const button=document.createElement('button');button.className='review-google-click';button.textContent='Review Google click';button.style.cssText='position:fixed;top:90px;right:20px;z-index:999999';document.body.append(button);});await page.getByRole('button',{name:'Review Google click'}).click();
 const calls=await page.evaluate(()=>window.dataLayer.map(args=>Array.from(args)));for(const id of ['G-REVIEW12345','AW-1234567890']){expect(calls.some(args=>args[0]==='config'&&args[1]===id)).toBe(true);expect(calls.some(args=>args[1]==='codeslot_click'&&args[2].send_to===id)).toBe(true);}
 const purchaseConfig={providers:{google:{id:'G-REVIEW12345',events:['purchase']},google_ads:{id:'AW-1234567890',label:'ReviewLabel123',events:['purchase']}},site:'review-google-fixture',context:{purchase:{event_id:'review-order-1',content_ids:['review-product'],value:25,currency:'USD'}},click_selector:''};
 await page.route('http://localhost:8888/review-google-purchase/',route=>route.fulfill({contentType:'text/html',body:'<!doctype html><html><head><title>Isolated Google purchase adapter fixture</title></head><body><script>window.CodeSlotConsent={marketing:true};</script><script id="codeslot-pixel-config" type="application/json">'+JSON.stringify(purchaseConfig)+'</script><script src="/wp-content/plugins/codeslot/assets/pixels.js"></script></body></html>'}));
 urls.length=0;await page.goto('/review-google-purchase/');await expect.poll(()=>urls.length).toBe(1);
 const purchases=await page.evaluate(()=>window.dataLayer.map(args=>Array.from(args)));
 expect(purchases.filter(args=>args[1]==='purchase'&&args[2].send_to==='G-REVIEW12345')).toHaveLength(1);
 expect(purchases.filter(args=>args[1]==='conversion'&&args[2].send_to==='AW-1234567890/ReviewLabel123'&&args[2].value===25)).toHaveLength(1);
 await page.reload();const repeated=await page.evaluate(()=>window.dataLayer.map(args=>Array.from(args)));expect(repeated.filter(args=>['purchase','conversion'].includes(args[1]))).toHaveLength(0);
 const migrated=JSON.parse(wp(`echo wp_json_encode(CodeSlot\\Pixels::validate(['google'=>['id'=>'AW-99887766','enabled'=>true,'events'=>['page_view'],'label'=>'OldLabel']]));`));expect(migrated.google_ads).toMatchObject({id:'AW-99887766',enabled:true,label:'OldLabel'});expect(migrated.google.enabled).toBe(false);
});

test('invalid editor saves preserve private draft values without changing or executing saved source',async({page})=>{
 const [id,other]=create([{name:'Review invalid editor',type:'php',location:'frontend',code:'/* original valid source */',active:false},{name:'Review other editor',type:'html',location:'footer',code:'<!-- other source -->',active:false}]);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 await page.locator('#codeslot-name').fill('Review unsaved draft title');
 await page.evaluate(()=>{const editor=document.querySelector('#codeslot-source').codeslotEditor;editor.setValue('if ( /* deliberately unfinished */');editor.save();});
 const form=await page.locator('#codeslot-snippet-form').evaluate(f=>Object.fromEntries(new window.FormData(f)));
 const response=await page.request.post('/wp-admin/admin-post.php',{form,maxRedirects:0});expect(response.status()).toBe(302);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${other}`);await expect(page.locator('#codeslot-name')).toHaveValue('Review other editor');await expect(page.locator('#codeslot-source')).toHaveValue('<!-- other source -->');
 await page.goto(response.headers().location);await expect(page.locator('.notice-error')).toBeVisible();await expect(page.locator('#codeslot-name')).toHaveValue('Review unsaved draft title');await expect(page.locator('#codeslot-source')).toHaveValue('if ( /* deliberately unfinished */');
 expect(snapshot()[id]).toMatchObject({name:'Review invalid editor',code:'/* original valid source */',active:false});
});
