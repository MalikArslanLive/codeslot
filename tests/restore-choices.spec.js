import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encode=v=>Buffer.from(JSON.stringify(v)).toString('base64');
let original,ids=[];
test.setTimeout(180000);
test.beforeEach(async({page})=>{original=JSON.parse(wp(`$out=[];foreach(['codeslot_settings','codeslot_global','codeslot_pixels','codeslot_files'] as $key)$out[$key]=get_option($key,null);echo wp_json_encode($out);`));wp(`update_option('codeslot_settings',['safe_mode'=>false]);`);await login(page);});
test.afterEach(()=>{wp(`$data=json_decode(base64_decode('${encode(original)}'),true);foreach($data as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}foreach(json_decode(base64_decode('${encode(ids)}'),true) as $id)wp_delete_post($id,true);delete_transient('codeslot_restore_1');delete_transient('codeslot_import_1');`);ids=[];});
function snippet(data){const id=Number(wp(`wp_set_current_user(1);echo CodeSlot\\Repository::save(json_decode(base64_decode('${encode(data)}'),true));`));ids.push(id);return id;}
function state(id){return JSON.parse(wp(`echo wp_json_encode(CodeSlot\\Repository::get(${id}));`));}
async function formFor(page,task,document){await page.goto('/wp-admin/admin.php?page=codeslot-transfer');const form=page.locator(`form:has([name=task][value=${task}])`);await form.locator('[name=json_file]').setInputFiles({name:'backup.json',mimeType:'application/json',buffer:Buffer.from(JSON.stringify(document))});return form;}
function backup(individual=[]){return JSON.parse(wp(`wp_set_current_user(1);$b=CodeSlot\\Backup::export();$b['individual']=json_decode(base64_decode('${encode(individual)}'),true);echo wp_json_encode($b);`));}

test('short restore replacement requires confirmation, preserves the selected existing state and reports untouched sections',async({page})=>{
 const id=snippet({name:'Restore choice UI',type:'html',location:'footer',code:'<!-- ORIGINAL -->',active:true});const incoming={...state(id),code:'<!-- REPLACED -->'};let form=await formFor(page,'import',{schema:'codeslot/v1',snippets:[incoming]});await form.locator('[name=snippet_conflicts]').selectOption('replace');await form.locator('[name=restore_state]').selectOption('preserve');await expect(form.locator('[name=confirm_replace]')).toHaveJSProperty('required',true);await expect(form.locator('.codeslot-restore-preview')).toContainText('will be replaced');await expect(form.locator('.codeslot-import-warning')).toContainText('Download');
 const fields=await form.evaluate(f=>Object.fromEntries(new window.FormData(f)));delete fields.json_file;delete fields.confirm_replace;
 const rejected=await page.request.post('/wp-admin/admin-post.php',{multipart:{...fields,json_file:{name:'snippets.json',mimeType:'application/json',buffer:Buffer.from(JSON.stringify({schema:'codeslot/v1',snippets:[incoming]}))}},maxRedirects:0});expect(rejected.status()).toBe(302);expect(state(id).code).toBe('<!-- ORIGINAL -->');
 await page.goto(rejected.headers().location);await expect(page.locator('.notice-error')).toContainText('Confirm replacing');form=await formFor(page,'import',{schema:'codeslot/v1',snippets:[incoming]});await form.locator('[name=snippet_conflicts]').selectOption('replace');await form.locator('[name=restore_state]').selectOption('preserve');await form.locator('[name=confirm_replace]').check();await form.getByRole('button',{name:'Replace matching snippets'}).click();
 await expect(page.locator('.notice-success')).toContainText('Existing snippets replaced: 1');await expect(page.locator('.notice-success')).toContainText('1 enabled, 0 disabled');await expect(page.locator('.notice-success')).toContainText('Global code, individual fields, settings, virtual files and pixels were not replaced or updated');expect(state(id)).toMatchObject({code:'<!-- REPLACED -->',active:true});
});

test('full restore keep choice leaves existing fields intact, adds only missing fields disabled and reports every unselected section',async({page})=>{
 wp(`update_option('codeslot_global',['header'=>['code'=>'<!-- KEEP -->','enabled'=>true]]);`);const b=backup();b.global={header:{code:'<!-- OVERWRITE -->',enabled:true},body:{code:'<!-- NEW -->',enabled:true}};
 const form=await formFor(page,'backup_import',b);await form.locator('[name="scopes[]"][value=global]').check();await form.locator('[name="restore_existing[global]"]').selectOption('skip');await form.locator('[name=restore_state]').selectOption('preserve');await expect(form.locator('.codeslot-restore-preview')).toContainText('global: Existing entries will remain unchanged');await expect(form.locator('.codeslot-restore-preview')).toContainText('pixels: Not selected');await form.locator('[name=confirm]').check();await form.getByRole('button',{name:'Restore selected sections',exact:true}).click();
 const notice=page.locator('.codeslot-restore-result');await expect(notice.locator('[data-restore-section=global]')).toContainText('1 entries restored/replaced (0 enabled, 1 disabled); 1 existing entries skipped');for(const scope of ['snippets','individual','settings','files','pixels'])await expect(notice.locator(`[data-restore-section=${scope}]`)).toContainText('Not selected — existing data was not replaced or updated');
 const global=JSON.parse(wp(`echo wp_json_encode(get_option('codeslot_global'));`));expect(global.header).toEqual({code:'<!-- KEEP -->',enabled:true});expect(global.body).toEqual({code:'<!-- NEW -->',enabled:false});
});

test('full replace with preserve updates individual code and keeps each field current state; notice accurately counts enabled and disabled',async({page})=>{
 const post=Number(wp(`$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Restore choice post','post_name'=>'restore-choice-post']);update_post_meta($id,'_codeslot_slots',['header'=>['code'=>'<!-- OLD HEAD -->','enabled'=>true],'footer'=>['code'=>'<!-- OLD FOOT -->','enabled'=>false]]);echo $id;`));ids.push(post);
 const b=backup([{kind:'post',type:'page',path:'restore-choice-post',slots:{header:{code:'<!-- NEW HEAD -->',enabled:false},footer:{code:'<!-- NEW FOOT -->',enabled:true}}}]);const form=await formFor(page,'backup_import',b);await form.locator('[name="scopes[]"][value=individual]').check();await form.locator('[name=restore_state]').selectOption('preserve');await form.locator('[name=confirm]').check();await form.getByRole('button',{name:'Restore selected sections',exact:true}).click();
 await expect(page.locator('[data-restore-section=individual]')).toContainText('2 entries restored/replaced (1 enabled, 1 disabled)');const slots=JSON.parse(wp(`echo wp_json_encode(get_post_meta(${post},'_codeslot_slots',true));`));expect(slots.header).toEqual({code:'<!-- NEW HEAD -->',enabled:true});expect(slots.footer).toEqual({code:'<!-- NEW FOOT -->',enabled:false});
});

test('restore warnings, previews and confirmation controls fit narrow screens and do not affect export forms',async({page})=>{
 for(const width of [1440,390,320]){
  await page.setViewportSize({width,height:1000});await page.goto('/wp-admin/admin.php?page=codeslot-transfer');const form=page.locator('form:has([name=task][value=backup_import])');await form.locator('[name="scopes[]"][value=global]').check();await form.locator('[name="scopes[]"][value=snippets]').check();await form.locator('[name=snippet_conflicts]').selectOption('replace');await expect(form.locator('[name=confirm_replace]')).toBeVisible();await expect(form.locator('.codeslot-restore-preview')).toContainText('Selected existing content will be replaced');expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);
  await expect(page.locator('form:has([name=task][value=export]) [name=confirm_replace]')).toHaveCount(0);await page.screenshot({path:`/tmp/codeslot-restore-choices-${width}.png`,fullPage:true});
 }
});

test('full restore without a selected section explains the next step and changes nothing',async({page})=>{
 const b=backup();const form=await formFor(page,'backup_import',b);await expect(form.locator('.codeslot-restore-preview')).toContainText('No sections selected. Select at least one section to restore.');await form.locator('[name=confirm]').check();await form.getByRole('button',{name:'Restore selected sections',exact:true}).click();await expect(page.locator('.notice-error')).toContainText('Select at least one section to restore');await expect(page.locator('.notice-error')).toContainText('Nothing was restored.');expect(backup()).toEqual(b);await expect(page.locator('.codeslot-restore-result')).toHaveCount(0);
});
