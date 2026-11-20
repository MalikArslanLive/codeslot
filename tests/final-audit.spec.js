import {test,expect} from '@playwright/test';
import {login} from './login.js';
test('oversized global code stays in the editor and does not submit',async({page})=>{
 await login(page);const rejected=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_extra',task:'backup_import'}});expect(rejected.status()).toBe(403);await page.goto('/wp-admin/admin.php?page=codeslot-global');
 const field=page.locator('textarea[data-codeslot-max-bytes]').first();await expect(field).toHaveCount(1);
 await field.evaluate(el=>{const value='é'.repeat(524289);if(el.codeslotEditor)el.codeslotEditor.setValue(value);else el.value=value;});
 let alerted=false;page.once('dialog',async dialog=>{alerted=true;expect(dialog.message()).toContain('Maximum 1 MiB');await dialog.accept();});
 await page.getByRole('button',{name:'Save Changes',exact:true}).click();expect(alerted).toBe(true);await expect(page).toHaveURL(/page=codeslot-global/);expect(await field.evaluate(el=>(el.codeslotEditor?el.codeslotEditor.getValue():el.value).length)).toBe(524289);
 await field.evaluate(el=>{if(el.codeslotEditor)el.codeslotEditor.setValue('');else{el.value='';el.dispatchEvent(new Event('input',{bubbles:true}));}});
});


test('malformed condition POST values return a validation notice instead of saving unrestricted code',async({page})=>{
 await login(page);await page.goto('/wp-admin/admin.php?page=codeslot-new');const fields=await page.locator('form:has([name=task][value=snippet])').evaluate(form=>Object.fromEntries(new window.FormData(form)));
 for(const value of ['{',null]){const data={...fields,name:'Malformed conditions audit',type:'html',location:'header',code:'<!-- MUST NOT SAVE -->'};if(value===null){delete data.conditions;data['conditions[]']='bad';}else data.conditions=value;const response=await page.request.post('/wp-admin/admin-post.php',{form:data,maxRedirects:0});expect(response.status()).toBe(302);await page.goto(response.headers().location);await expect(page.locator('.notice-error')).toContainText('Invalid condition expression');await expect(page.locator('.notice-error')).toContainText('Nothing was saved.');}
});
