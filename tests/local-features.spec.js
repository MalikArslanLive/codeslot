import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
import {login} from './login.js';
function wp(...args){return execFileSync('docker',['compose','run','--rm','cli',...args],{encoding:'utf8'}).trim().split('\n').at(-1);}
function save(data,id=0){const encoded=Buffer.from(JSON.stringify(data)).toString('base64');return wp('eval',`wp_set_current_user(1); echo CodeSlot\\Repository::save(json_decode(base64_decode("${encoded}"),true),${id});`);}
let ids=[];
test.setTimeout(90000);
test.beforeEach(async({page})=>{ids=[];await login(page);});
test.afterEach(()=>{wp('eval',`foreach(array(${ids.join(',')}) as $id){wp_delete_post($id,true);} delete_option("codeslot_browser_job_count");`);});
test('Run Once requires nonce and explicit confirmation, and cannot run twice',async({page})=>{
 const id=save({name:'Browser one-time task',type:'php',location:'once',code:'update_option("codeslot_browser_job_count",(int)get_option("codeslot_browser_job_count",0)+1); echo "captured output";'});ids.push(id);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 const form=page.locator('form').filter({has:page.locator('[name="task"][value="run_once"]')});
 const nonce=await form.locator('[name="_wpnonce"]').inputValue();
 const denied=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'run_once',id,confirm:'1'}});
 expect(denied.status()).toBe(403);
 const unconfirmed=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'run_once',id,_wpnonce:nonce}});
 expect(unconfirmed.status()).toBe(200);expect(await unconfirmed.text()).toContain('Confirm this one-time task explicitly');
 await form.locator('[name="confirm"]').check();
 await form.getByRole('button',{name:'Run Once',exact:true}).click();
 await expect(page.getByText('completed',{exact:true})).toBeVisible();
 await expect(page.locator('pre').filter({hasText:'captured output'})).toBeVisible();
 await expect(page.getByRole('button',{name:'Run Once',exact:true})).toHaveCount(0);
 const repeated=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'run_once',id,_wpnonce:nonce,confirm:'1'}});
 expect(repeated.status()).toBe(200);expect(await repeated.text()).toContain('already claimed');
 expect(wp('option','get','codeslot_browser_job_count')).toBe('1');
});
test('saved-version diff escapes source and remains contained on mobile',async({page})=>{
 const original={name:'Diff browser fixture',type:'html',location:'header',code:'OLD\n<img src=x onerror="window.csDiffXss=1">'};
 const id=save(original);ids.push(id);save({...original,code:'NEW\n<div>updated</div>'},id);
 await page.setViewportSize({width:320,height:900});
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 await page.getByText('Compare Previous Version with Current Version',{exact:true}).click();
 await expect(page.locator('.codeslot-revision-diff table.diff')).toBeVisible();
 await expect(page.locator('summary').filter({hasText:/^View (?:Previous )?Code$/i})).toHaveCount(0);
 await expect(page.locator('.codeslot-revision-diff')).toContainText('Previous Saved Version');
 await expect(page.locator('.codeslot-revision-diff')).toContainText('Current Version');
 await expect(page.locator('.codeslot-revision-diff')).toContainText('OLD');
 await expect(page.locator('.codeslot-revision-diff')).toContainText('NEW');
 await expect(page.locator('.codeslot-revision-diff img')).toHaveCount(0);
 expect(await page.evaluate(()=>window.csDiffXss)).toBeUndefined();
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth+1)).toBe(true);
});
test('paragraph location saves through UI and inserts once only on the selected page',async({page})=>{
 const content={post_type:'page',post_title:'Paragraph browser fixture',post_content:'<p>One</p><p>Two</p><p>Three</p>',post_status:'publish'};
 const encoded=Buffer.from(JSON.stringify(content)).toString('base64');
 const postId=wp('eval',`echo wp_insert_post(json_decode(base64_decode("${encoded}"),true));`);ids.push(postId);
 const id=save({name:'Paragraph browser snippet',type:'html',location:'header',code:'<!-- CS-PARAGRAPH-HTTP -->',conditions:{include:[[{key:'singular',value:postId}]]}});ids.push(id);
 await page.goto(`/wp-admin/admin.php?page=codeslot-new&id=${id}`);
 await page.locator('#codeslot-location').selectOption('after_paragraph');
 await expect(page.locator('#codeslot-paragraph')).toBeVisible();
 await page.locator('#codeslot-paragraph').fill('2');await page.locator('[name="active"]').check();
 await page.getByRole('button',{name:'Save Snippet',exact:true}).click();
 await expect(page.locator('.notice-success')).toContainText('saved');
 const response=await page.request.get(`/?page_id=${postId}`);expect(response.ok()).toBeTruthy();
 const body=await response.text();expect(body.split('CS-PARAGRAPH-HTTP').length-1).toBe(1);expect(body).toContain('<p>Two</p><!-- CS-PARAGRAPH-HTTP -->');
 const home=await page.request.get('/');expect(await home.text()).not.toContain('CS-PARAGRAPH-HTTP');
});
