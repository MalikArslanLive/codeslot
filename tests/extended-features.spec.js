import {test, expect, request} from '@playwright/test';
import {Buffer} from 'node:buffer';
import {URL} from 'node:url';
import {execFileSync} from 'node:child_process';
import {login} from './login.js';
function wp(...args) { return execFileSync('docker',['compose','run','--rm','cli',...args],{encoding:'utf8'}).trim().split('\n').at(-1); }
function run(code) { return wp('eval',code); }
function save(data) { return run(`wp_set_current_user(1);echo CodeSlot\\Repository::save(json_decode(base64_decode('${Buffer.from(JSON.stringify(data)).toString('base64')}'),true));`); }
function option(name,data) { wp('option','update',name,JSON.stringify(data),'--format=json'); }
let ids=[];let original={};
test.setTimeout(120000);
test.beforeEach(async({page})=>{
  for(const name of ['codeslot_files','codeslot_pixels','codeslot_settings']) original[name]=run(`echo wp_json_encode(get_option('${name}',null));`);
  option('codeslot_settings',{safe_mode:false});
  await login(page);
});
test.afterEach(()=>{
  for(const id of ids) wp('post','delete',id,'--force'); ids=[];
  for(const [name,value] of Object.entries(original)) {if(value==='null') run(`delete_option('${name}');`);else wp('option','update',name,value,'--format=json');} original={};
});
test('untitled Quick Edit preloads display name and existing metadata; toggle preserves code',async({page})=>{
 const id=save({name:'',description:'Saved description',type:'html',code:'<!-- EXT-KEEP -->',location:'footer',categories:['saved-category'],tags:['saved-tag'],active:false}); ids.push(id);
 await page.goto('/wp-admin/admin.php?page=codeslot');
 const row=page.locator('tr').filter({has:page.locator(`input[name="ids[]"][value="${id}"]`)});
 await row.getByRole('button',{name:'Quick Edit',exact:true}).click();
 await expect(page.locator('#codeslot-quick_name')).toHaveValue(`Untitled snippet #${id}`);
 await expect(page.locator('#codeslot-quick_description')).toHaveValue('Saved description');
 await expect(page.locator('#codeslot-quick_categories')).toHaveValue('saved-category');
 await expect(page.locator('#codeslot-quick_tags')).toHaveValue('saved-tag');
 await page.getByRole('button',{name:'Cancel',exact:true}).click();
 const nonce=await page.locator('#codeslot-toggle-form [name=_wpnonce]').inputValue();
 const denied=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'toggle',id,active:'1'}});expect(denied.status()).toBe(403);
 await row.getByRole('switch').click();await expect(page.locator('.notice-success')).toBeVisible();
 expect(JSON.parse(run(`echo wp_json_encode(CodeSlot\\Repository::get(${id}));`))).toMatchObject({active:true,code:'<!-- EXT-KEEP -->'});
 run(`wp_set_current_user(1);$s=CodeSlot\\Repository::get(${id});$s['locked']=true;CodeSlot\\Repository::save($s,${id});`);
 const locked=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'toggle',id,active:'0',_wpnonce:nonce}});expect(locked.status()).toBe(200);expect(await locked.text()).toContain('Unlock this snippet before changing status');
});
test('clearing revision history warns, cancels safely and preserves current code',async({page})=>{
 const id=save({name:'History fixture',type:'html',location:'header',code:'old'});ids.push(id);
 run(`wp_set_current_user(1);$s=CodeSlot\\Repository::get(${id});$s['code']='current';CodeSlot\\Repository::save($s,${id});`);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 const button=page.getByRole('button',{name:'Clear revision history',exact:true});await expect(button).toBeVisible();
 page.once('dialog',d=>d.dismiss());await button.click();await expect(button).toBeVisible();
 page.once('dialog',d=>d.accept());await button.click();await expect(page.locator('.notice-success')).toBeVisible();
 expect(run(`echo metadata_exists('post',${id},'_codeslot_history')?'yes':'no';`)).toBe('no');
 expect(run(`echo CodeSlot\\Repository::get(${id})['code'];`)).toBe('current');
});
test('virtual public files use correct MIME, obey Safe Mode and preserve physical files',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-files');
 const form=page.locator('form').filter({has:page.locator('input[name=task][value=files]')});
 const nonce=await form.locator('[name=_wpnonce]').inputValue();
 const denied=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_extra',task:'files'}});expect(denied.status()).toBe(403);
 const saved=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_extra',task:'files',_wpnonce:nonce,'files[ads.txt][enabled]':'1','files[ads.txt][code]':'example.com, 12345, DIRECT','files[service-worker.js][enabled]':'1','files[service-worker.js][code]':'/* CS-WORKER */','files[robots.txt][enabled]':'1','files[robots.txt][code]':'Disallow: /codeslot-test-private/'}});expect(saved.ok()).toBe(true);
 const ads=await page.request.get('/ads.txt');expect(ads.status()).toBe(200);expect(ads.headers()['content-type']).toContain('text/plain');expect(await ads.text()).toBe('example.com, 12345, DIRECT');
 const worker=await page.request.get('/service-worker.js');expect(worker.headers()['content-type']).toContain('application/javascript');expect(await worker.text()).toContain('CS-WORKER');
 const robots=await page.request.get('/robots.txt');expect(await robots.text()).toContain('Disallow: /codeslot-test-private/');
 const home=await page.request.get('/');expect(await home.text()).not.toContain('navigator.serviceWorker.register');
 option('codeslot_settings',{safe_mode:true});expect((await page.request.get('/ads.txt')).status()).toBe(404);
 option('codeslot_settings',{safe_mode:false});
 run(`if(file_exists(ABSPATH.'ads.txt'))throw new Exception('Physical fixture already exists');file_put_contents(ABSPATH.'ads.txt','CS-PHYSICAL-FILE');`);
 try{const physical=await page.request.get('/ads.txt');expect(await physical.text()).toBe('CS-PHYSICAL-FILE');}finally{run(`if(file_get_contents(ABSPATH.'ads.txt')==='CS-PHYSICAL-FILE')unlink(ABSPATH.'ads.txt');`);}
});
test('pixels send nothing before consent and stop CodeSlot events on withdrawal',async({page})=>{
 option('codeslot_pixels',{meta:{id:'123456789012345',enabled:true,events:['page_view','click']},click_selector:'.codeslot-test-click'});
 const requests=[];
 await page.route('https://connect.facebook.net/**',async route=>{requests.push(route.request().url());await route.fulfill({contentType:'application/javascript',body:'window.vendorEvents=window.vendorEvents||[];window.fbq.callMethod=function(){window.vendorEvents.push(Array.from(arguments));};window.fbq.queue.forEach(a=>window.fbq.callMethod.apply(null,a));'});});
 await page.goto('/');await expect(page.locator('#codeslot-pixel-config')).toHaveCount(1);
 expect(requests).toHaveLength(0);expect(await page.evaluate(()=>window.fbq)).toBeUndefined();
 await page.evaluate(()=>window.dispatchEvent(new window.CustomEvent('codeslot:consent',{detail:{marketing:true}})));
 await expect.poll(()=>requests.length).toBe(1);await expect.poll(()=>page.evaluate(()=>window.vendorEvents?.some(e=>e[1]==='PageView'))).toBe(true);
 await page.evaluate(()=>{const b=document.createElement('button');b.className='codeslot-test-click';b.textContent='Pixel test';document.body.appendChild(b);});
 await page.getByRole('button',{name:'Pixel test'}).click();
 await expect.poll(()=>page.evaluate(()=>window.vendorEvents.filter(e=>e[1]==='CodeSlotClick').length)).toBe(1);
 await page.evaluate(()=>window.dispatchEvent(new window.CustomEvent('codeslot:consent',{detail:{marketing:false}})));
 await page.getByRole('button',{name:'Pixel test'}).click();expect(await page.evaluate(()=>window.vendorEvents.filter(e=>e[1]==='CodeSlotClick').length)).toBe(1);
});
test('system info is private, readable and library search opens inactive code',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-tools');
 await expect(page.locator('#codeslot-system-info')).toContainText('SAPI: apache2handler');
 await expect(page.locator('#codeslot-system-info')).not.toContainText('/var/www/html');
 const anon=await request.newContext({baseURL:page.url()});const forbidden=await anon.get('/wp-admin/admin.php?page=codeslot-tools');expect(await forbidden.text()).not.toContain('Begin CodeSlot System Info');await anon.dispose();
 await page.goto('/wp-admin/admin.php?page=codeslot-library&library_search=Custom%20excerpt');
 await expect(page.locator('.codeslot-recipe:visible')).toHaveCount(2);
 await page.locator('.codeslot-recipe:visible').first().getByRole('link',{name:'Review and use snippet'}).click();
 await expect(page.locator('[name=active]')).not.toBeChecked();
 await expect(page.locator('#codeslot-name')).not.toHaveValue('');
});
test('early-hook PHP runs on frontend and registers login filters',async({page})=>{
 const id=save({name:'Early hook browser fixture',type:'php',location:'bootstrap',active:true,code:"add_action('wp_head',static function(){echo '<!-- CS-EARLY-HOOK -->';});add_filter('login_message',static function($message){return $message.'<p>CS-EARLY-LOGIN</p>';});"});ids.push(id);
 expect(await (await page.request.get('/')).text()).toContain('CS-EARLY-HOOK');
 expect(await (await page.request.get('/wp-login.php')).text()).toContain('CS-EARLY-LOGIN');
});

test('all five pixel adapters initialize only after consent and queue selected events',async({page})=>{
 const ids={meta:'123456789012345',google:'AW-123456789',pinterest:'1234567890123',tiktok:'TESTPIXEL12345',snapchat:'12345678-abcd-1234-abcd-123456789012'};
 const providers=Object.fromEntries(Object.entries(ids).map(([key,id])=>[key,{id,enabled:true,events:['page_view','click']}]));
 option('codeslot_pixels',{...providers,click_selector:'.all-adapters-test'});
 const urls=[];
 await page.route(/https:\/\/(connect\.facebook\.net|www\.googletagmanager\.com|s\.pinimg\.com|analytics\.tiktok\.com|sc-static\.net)\//,async route=>{urls.push(route.request().url());await route.fulfill({contentType:'application/javascript',body:'/* mocked vendor SDK: inspect adapter queues without sending analytics */'});});
 await page.goto('/');expect(urls).toHaveLength(0);
 await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:true}));await expect.poll(()=>urls.length).toBe(5);
 await page.evaluate(()=>{const b=document.createElement('button');b.className='all-adapters-test';b.textContent='All adapters click';document.body.appendChild(b);});
 await page.getByRole('button',{name:'All adapters click'}).click();
 const queues=await page.evaluate(()=>({meta:window.fbq.queue,google:window.dataLayer.map(a=>Array.from(a)),pinterest:window.pintrk.queue,tiktok:Array.from(window.ttq),snapchat:window.snaptr.queue}));
 expect(queues.meta.some(a=>a[1]==='CodeSlotClick')).toBe(true);expect(queues.google.some(a=>a[1]==='codeslot_click')).toBe(true);
 expect(queues.pinterest.some(a=>a[0]==='track'&&a[1]==='custom')).toBe(true);expect(queues.tiktok.some(a=>a[1]==='ClickButton')).toBe(true);expect(queues.snapchat.some(a=>a[1]==='CUSTOM_EVENT_1')).toBe(true);
});

test('WooCommerce purchase requires the order key, omits customer data and deduplicates per browser session',async({page})=>{
 const raw=run(`$p=new WC_Product_Simple();$p->set_name('CodeSlot pixel purchase fixture');$p->set_regular_price('15');$p->set_status('publish');$p->save();$o=wc_create_order(array('customer_id'=>0));$o->add_product($p,1);$o->set_billing_email('pixel-fixture@example.org');$o->calculate_totals();$o->update_status('completed');echo wp_json_encode(array('product'=>$p->get_id(),'order'=>$o->get_id(),'url'=>$o->get_checkout_order_received_url()));`);
 const fixture=JSON.parse(raw);ids.push(String(fixture.product));
 try{
  option('codeslot_pixels',{google:{id:'AW-123456789',label:'TEST_LABEL',enabled:true,events:['page_view','purchase']},click_selector:''});
  const wrong=new URL(fixture.url);wrong.searchParams.set('key','invalid-order-key');
  const wrongBody=await(await page.request.get(wrong.toString())).text();expect(wrongBody).not.toContain('"purchase":{"value"');
  await page.route('https://www.googletagmanager.com/**',route=>route.fulfill({contentType:'application/javascript',body:'/* isolated Google SDK test */'}));
  await page.addInitScript(()=>{window.CodeSlotConsent={marketing:true};});
  await page.goto(fixture.url);
  const config=JSON.parse(await page.locator('#codeslot-pixel-config').textContent());expect(config.context.purchase.value).toBe(15);
  expect(JSON.stringify(config)).not.toContain('pixel-fixture@example.org');expect(config.context.purchase.event_id).toMatch(/^[a-f0-9]{64}$/);
  await expect.poll(()=>page.evaluate(()=>window.dataLayer?.filter(a=>a[0]==='event'&&a[1]==='conversion').length)).toBe(1);
  await page.reload();expect(await page.evaluate(()=>window.dataLayer.filter(a=>a[0]==='event'&&a[1]==='conversion').length)).toBe(0);
 }finally{run(`$o=wc_get_order(${fixture.order});if($o)$o->delete(true);`);}
});
