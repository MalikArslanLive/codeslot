import {test,expect,request} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encode=v=>Buffer.from(JSON.stringify(v)).toString('base64');
let original,ids=[];
test.setTimeout(180000);
test.beforeEach(async({page})=>{original=JSON.parse(wp(`$out=[];foreach(['codeslot_settings','codeslot_pixels'] as $key)$out[$key]=get_option($key,null);echo wp_json_encode($out);`));wp(`update_option('codeslot_settings',['safe_mode'=>false]);delete_option('codeslot_pixels');`);await login(page);});
test.afterEach(()=>{wp(`$data=json_decode(base64_decode('${encode(original)}'),true);foreach($data as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}foreach(json_decode(base64_decode('${encode(ids)}'),true) as $id)wp_delete_post($id,true);`);ids=[];});
async function mockVendors(page){const urls=[];await page.route(/https:\/\/(connect\.facebook\.net|www\.googletagmanager\.com|s\.pinimg\.com|analytics\.tiktok\.com|sc-static\.net)\//,route=>{urls.push(route.request().url());return route.fulfill({contentType:'application/javascript',body:'/* isolated adapter test: no provider traffic */'});});return urls;}
async function fields(page,task){return page.locator(`form:has([name=task][value="${task}"])`).evaluate(f=>Object.fromEntries(new window.FormData(f)));}
async function submitFile(page,task,name,content,extra={}){const data=await fields(page,task);delete data.json_file;const route=task==='import'?'codeslot_save':'codeslot_extra';return page.request.post('/wp-admin/admin-post.php',{multipart:{...data,...extra,action:route,json_file:{name,mimeType:'application/json',buffer:Buffer.from(content)}},maxRedirects:0});}

test('all enabled base tags initialize automatically without a cookie banner or selected events',async({page})=>{
 const config={meta:{id:'123456789012345'},google:{id:'G-TEST12345'},google_ads:{id:'AW-123456789'},gtm:{id:'GTM-TEST123'},pinterest:{id:'1234567890123'},tiktok:{id:'TESTPIXEL12345'},snapchat:{id:'12345678-abcd-1234-abcd-123456789012'}};
 for(const p of Object.values(config)){p.enabled=true;p.events=[];}wp(`update_option('codeslot_pixels',json_decode(base64_decode('${encode(config)}'),true));`);
 const errors=[];page.on('pageerror',error=>errors.push(error.message));
 await page.addInitScript(()=>{window.codeslotHeadLoads=[];const append=window.Node.prototype.appendChild;window.Node.prototype.appendChild=function(node){if(node.tagName==='SCRIPT' && /connect.facebook.net|googletagmanager.com|s.pinimg.com|sc-static.net|analytics.tiktok.com/.test(node.src))window.codeslotHeadLoads.push({head:this===document.head,bodyExists:!!document.body});return append.call(this,node);};});
 const urls=await mockVendors(page);await page.goto('/');await expect.poll(()=>urls.length).toBe(6);
 const source=await(await page.request.get('/')).text();const end=source.toLowerCase().indexOf('</head>');expect(source.indexOf('id="codeslot-pixel-config"')).toBeLessThan(end);expect(source.indexOf('id="codeslot-pixels-js"')).toBeLessThan(end);expect(source.match(/id="codeslot-pixel-config"/g)).toHaveLength(1);
 await expect(page.locator('head #codeslot-pixel-config')).toHaveCount(1);await expect(page.locator('head #codeslot-pixels-js')).toHaveCount(1);await expect(page.locator('body #codeslot-pixel-config, body #codeslot-pixels-js')).toHaveCount(0);
 expect(await page.evaluate(()=>window.codeslotHeadLoads)).toEqual(Array.from({length:6},()=>({head:true,bodyExists:false})));expect(errors).toEqual([]);
 const status=await page.evaluate(()=>window.CodeSlotPixels.getStatus());expect(status.marketingConsent).toBe(true);expect(status.consentRequired).toBe(false);expect(status.events).toEqual([]);
 expect(urls.some(u=>u.includes('/gtm.js?id=GTM-TEST123'))).toBe(true);expect(urls.some(u=>u.includes('/gtag/js?id=G-TEST12345'))).toBe(true);
 expect(await page.evaluate(()=>window.dataLayer.filter(a=>a[0]==='config').map(a=>a[1]))).toEqual(['G-TEST12345','AW-123456789']);
 expect(await page.evaluate(()=>window.dataLayer.find(a=>a[0]==='config'&&a[1]==='G-TEST12345')[2])).toEqual({send_page_view:false});expect(await page.evaluate(()=>window.dataLayer.find(a=>a[0]==='config'&&a[1]==='AW-123456789')[2])).toEqual({});
});

test('optional consent gate blocks tags until acceptance and stops new events on withdrawal',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');await page.locator('[name=require_consent]').check();await page.locator('#codeslot-pixel-meta').fill('123456789012345');await page.locator('[name="pixels[meta][enabled]"]').check();await page.locator('[name="pixels[meta][events][]"][value=page_view]').check();await page.locator('[name="pixels[meta][events][]"][value=click]').check();await page.locator('#codeslot-click-selector').fill('.test-consent');await page.getByRole('button',{name:'Save conversion pixels'}).click();
 const urls=await mockVendors(page);await page.goto('/');expect(urls).toHaveLength(0);await page.evaluate(()=>window.dispatchEvent(new window.CustomEvent('codeslot:consent',{detail:{marketing:true}})));await expect.poll(()=>urls.length).toBe(1);
 expect(await page.evaluate(()=>window.fbq.queue.filter(a=>a[1]==='PageView').length)).toBe(1);
 await page.evaluate(()=>{const b=document.createElement('button');b.className='test-consent';b.textContent='Consent fixture';b.style.cssText='position:fixed;top:80px;left:30px;z-index:999999';document.body.append(b);});await page.getByRole('button',{name:'Consent fixture'}).click();
 await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:false}));await page.getByRole('button',{name:'Consent fixture'}).click();expect(await page.evaluate(()=>window.fbq.queue.filter(a=>a[1]==='CodeSlotClick').length)).toBe(1);
});

test('event cards show provider-specific supported choices and exact Click custom labels',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-pixels');await expect(page.locator('[name="pixels[google_ads][events][]"]')).toHaveCount(1);await expect(page.locator('[name="pixels[google_ads][events][]"]')).toHaveValue('purchase');
 await expect(page.locator('[name="pixels[pinterest][events][]"]')).toHaveCount(3);await expect(page.locator('[name="pixels[pinterest][events][]"][value=begin_checkout]')).toHaveCount(0);
 await expect(page.getByText('Click button (ClickButton)',{exact:true})).toBeVisible();await expect(page.locator('[name=require_consent]')).not.toBeChecked();
});

test('snippet JSON file uploads preserve exact source and import inactive; no paste field remains',async({page})=>{
 const name='File import '+Date.now();const source='const x = "<tag>";\nwindow.fileImport = x;';
 const json=JSON.parse(wp(`wp_set_current_user(1);$id=CodeSlot\\Repository::save(['name'=>'${name}','type'=>'javascript','location'=>'footer','code'=>base64_decode('${Buffer.from(source).toString('base64')}'),'active'=>false]);echo wp_json_encode(['id'=>$id,'export'=>CodeSlot\\Repository::export()]);`));ids.push(json.id);json.export.snippets=json.export.snippets.filter(s=>s.name===name);
 await page.goto('/wp-admin/admin.php?page=codeslot-transfer');await expect(page.locator('textarea[name=json]')).toHaveCount(0);
 const form=page.locator('form:has([name=task][value=import])');await form.locator('[type=file]').setInputFiles({name:'snippets.json',mimeType:'application/json',buffer:Buffer.from(JSON.stringify(json.export))});await form.getByRole('button',{name:'Import inactive copies'}).click();await expect(page.locator('.notice-success')).toBeVisible();
 const imported=JSON.parse(wp(`$out=[];foreach(get_posts(['post_type'=>'codeslot_snippet','post_status'=>'any','numberposts'=>-1]) as $p){if($p->post_title==='${name}')$out[]=CodeSlot\\Repository::get($p->ID);}echo wp_json_encode($out);`));
 ids.push(...imported.map(s=>s.id));expect(imported.length).toBe(1);for(const s of imported){expect(s.code).toBe(source);expect(s.active).toBe(false);}
});

test('invalid extension, malformed/oversize JSON and missing uploads fail inline without importing',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-transfer');
 for(const [name,content] of [['bad.php','<?php echo 1;'],['bad.json','{invalid'],['oversize.json',' '.repeat(5242881)]]){const response=await submitFile(page,'import',name,content);expect(response.status()).toBe(302);await page.goto(response.headers().location);await expect(page.locator('.notice-error')).toBeVisible();}
 const form=await fields(page,'import');delete form.json_file;const missing=await page.request.post('/wp-admin/admin-post.php',{form,maxRedirects:0});expect(missing.status()).toBe(302);await page.goto(missing.headers().location);await expect(page.locator('.notice-error')).toContainText('Choose a JSON file');
 const outsider=await request.newContext({baseURL:'http://localhost:8888'});const blocked=await outsider.post('/wp-admin/admin-post.php',{multipart:{action:'codeslot_save',task:'import',json_file:{name:'x.json',mimeType:'application/json',buffer:Buffer.from('{}')}},maxRedirects:0});expect(blocked.status()).toBeGreaterThanOrEqual(400);await outsider.dispose();
});

test('full backup JSON restores selected settings and rejects missing nonce without writes',async({page})=>{
 const backup=JSON.parse(wp(`wp_set_current_user(1);echo wp_json_encode(CodeSlot\\Backup::export());`));await page.goto('/wp-admin/admin.php?page=codeslot-transfer');
 const invalid=await fields(page,'backup_import');delete invalid._wpnonce;delete invalid.json_file;const denied=await page.request.post('/wp-admin/admin-post.php',{multipart:{...invalid,confirm:'1','scopes[]':'settings',json_file:{name:'backup.json',mimeType:'application/json',buffer:Buffer.from(JSON.stringify(backup))}},maxRedirects:0});expect(denied.status()).toBe(403);
 const response=await submitFile(page,'backup_import','backup.json',JSON.stringify(backup),{confirm:'1','scopes[]':'settings'});expect(response.status()).toBe(302);await page.goto(response.headers().location);await expect(page.locator('.codeslot-restore-result')).toContainText('Backup restore complete');expect(JSON.parse(wp(`echo wp_json_encode(get_option('codeslot_settings'));`)).safe_mode).toBe(false);
});

test('shortcode appears only in its editor mode and list column, with copy and integrated location search',async({page})=>{
 const id=Number(wp(`wp_set_current_user(1);echo CodeSlot\\Repository::save(['name'=>'Shortcode presentation fixture','type'=>'html','location'=>'shortcode','code'=>'<b>Fixture</b>','active'=>false]);`));ids.push(id);
 await page.goto('/wp-admin/admin.php?page=codeslot-new&id='+id);await expect(page.locator('#codeslot-insert-usage')).toContainText(`[codeslot id="${id}"]`);await expect(page.locator('#codeslot-insert-usage .codeslot-copy-shortcode')).toBeVisible();await expect(page.locator('#codeslot-location-field')).toBeHidden();await expect(page.getByText('Usage:',{exact:false})).toHaveCount(0);await expect(page.getByText('This is not a sandbox.',{exact:false})).toHaveCount(0);
 await page.locator('[name=insertion_method][value=auto]').check();await expect(page.locator('#codeslot-insert-usage')).toBeHidden();await page.locator('.codeslot-location-picker summary').click();await page.getByRole('searchbox',{name:'Search locations'}).fill('footer');await page.locator('.codeslot-location-option').filter({hasText:'Site-wide Footer'}).click();await expect(page.locator('#codeslot-location')).toHaveValue('footer');await expect(page.locator('.codeslot-location-picker')).not.toHaveAttribute('open');
 await page.goto('/wp-admin/admin.php?page=codeslot&s=Shortcode+presentation');await page.locator('#show-settings-link').click();await page.locator('#shortcode-hide').check();await expect(page.locator('.column-shortcode code')).toContainText(`[codeslot id="${id}"]`);await expect(page.locator('.column-shortcode .codeslot-copy-shortcode')).toBeVisible();
 await page.goto('/wp-admin/admin.php?page=codeslot-new');await page.locator('[name=insertion_method][value=shortcode]').check();await expect(page.locator('#codeslot-insert-usage')).toHaveText('Save this snippet to generate its shortcode.');
});
