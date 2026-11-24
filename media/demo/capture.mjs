/** Real UI captures from an isolated demo; no DOM replacement or image compositing. */
import {chromium} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {mkdirSync,writeFileSync} from 'node:fs';
import {resolve} from 'node:path';
const site=process.env.CODESLOT_GALLERY_URL || 'http://localhost:8888/gallery';
const wpPath=process.env.CODESLOT_GALLERY_PATH || '/var/www/html/gallery';
const password=process.env.CODESLOT_GALLERY_PASSWORD;
if(!password)throw new Error('Set CODESLOT_GALLERY_PASSWORD for the disposable demo user.');
const fixture=JSON.parse(execFileSync('docker',['compose','run','--rm','cli','--path='+wpPath,'option','get','codeslot_gallery_fixture','--format=json'],{encoding:'utf8',stdio:['ignore','pipe','pipe']}));
const version=execFileSync('docker',['compose','run','--rm','cli','--path='+wpPath,'core','version'],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);
const out=resolve('media/gallery');mkdirSync(out,{recursive:true});
const browser=await chromium.launch({executablePath:process.env.CODESLOT_CHROMIUM || '/usr/bin/chromium',headless:true});
const context=await browser.newContext({viewport:{width:1600,height:1200},deviceScaleFactor:1,locale:'en-US',reducedMotion:'reduce'});
await context.route('**/*',route=>{const url=new URL(route.request().url());return url.origin===new URL(site).origin?route.continue():route.abort();});
const page=await context.newPage();const errors=[];page.on('pageerror',error=>errors.push(error.message));
async function visit(path,height=1200){await page.setViewportSize({width:1600,height});await page.goto(site+path,{waitUntil:'networkidle'});await page.evaluate(()=>document.fonts.ready);await page.mouse.move(1590,1190);}
async function shot(number){await page.screenshot({path:out+'/screenshot-'+number+'.png',animations:'disabled'});console.log('Captured screenshot-'+number+'.png');}
try{
 await page.goto(site+'/wp-login.php');await page.request.post(site+'/wp-login.php',{form:{log:process.env.CODESLOT_GALLERY_USER || 'gallery-admin',pwd:password,'wp-submit':'Log In',redirect_to:site+'/wp-admin/admin.php?page=codeslot',testcookie:'1'}});
 await visit('/wp-admin/admin.php?page=codeslot',1350);await page.locator('.codeslot-table').waitFor();await shot(1);
 await visit('/wp-admin/admin.php?page=codeslot-new&id='+fixture.editor,1470);await page.locator('.CodeMirror').waitFor();await shot(2);
 await visit('/wp-admin/admin.php?page=codeslot-new&id='+fixture.conditions,1050);await page.waitForFunction(()=>!Array.from(document.querySelectorAll('.codeslot-search-status')).some(el=>el.textContent.includes('Resolving')));await page.locator('#codeslot-conditions').evaluate(el=>window.scrollTo(0,el.getBoundingClientRect().top+window.scrollY-170));await shot(3);
 await visit('/wp-admin/admin.php?page=codeslot-global',1540);await shot(4);
 await visit('/wp-admin/post.php?post='+fixture.contact+'&action=edit',1900);await page.locator('#codeslot-slots').waitFor();await shot(5);
 await visit('/wp-admin/admin.php?page=codeslot-pixels',1500);await shot(6);
 await visit('/wp-admin/admin.php?page=codeslot-library',1200);await shot(7);
 await visit('/wp-admin/admin.php?page=codeslot-transfer',1500);const restore=page.locator('form:has([name=task][value=backup_import])');const json=execFileSync('docker',['compose','run','--rm','cli','--path='+wpPath,'eval','wp_set_current_user(1);echo wp_json_encode(CodeSlot\\Backup::export());'],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);await restore.locator('[name=json_file]').setInputFiles({name:'codeslot-demo-backup.json',mimeType:'application/json',buffer:Buffer.from(json)});for(const scope of ['snippets','global','individual'])await restore.locator('[name="scopes[]"][value='+scope+']').check();await restore.locator('[name="restore_existing[global]"]').selectOption('skip');await restore.locator('[name="restore_existing[individual]"]').selectOption('skip');await restore.evaluate(el=>window.scrollTo(0,el.getBoundingClientRect().top+window.scrollY-310));await shot(8);
 if(errors.length)throw new Error('Browser errors: '+errors.join('; '));
 writeFileSync(resolve('work/gallery-capture-results.json'),JSON.stringify({captured:new Date().toISOString(),site,wordpress:version,screenshots:8,notes:'Real UI; generic isolated demo data; external requests blocked; no restore submitted; no external trackers enabled.',pageErrors:errors},null,2));
}finally{await browser.close();}
