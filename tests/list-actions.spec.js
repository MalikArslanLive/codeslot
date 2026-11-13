import { login } from './login.js';
import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
function wp(...args) {
  return execFileSync('docker', ['compose', 'run', '--rm', 'cli', ...args], {encoding:'utf8'}).trim().split('\n').at(-1);
}
function snippet(id) { return JSON.parse(wp('eval', `echo wp_json_encode(CodeSlot\\Repository::get(${id}));`)); }
let id;
test.setTimeout(90000);
test.beforeEach(async ({page}) => {
  id = wp('eval', 'wp_set_current_user(1); echo CodeSlot\\Repository::save(["name"=>"List action fixture","type"=>"html","code"=>"<!-- CS-KEEP-CODE -->","location"=>"footer","active"=>false]);');
  await login(page);
  await page.goto('/wp-admin/admin.php?page=codeslot');
});
test.afterEach(() => { if (snippet(id)) wp('post','delete',id,'--force'); });
test('Quick Edit and Bulk Edit preserve executable code and update selected metadata', async ({page}) => {
  await page.setViewportSize({width:390,height:950});
  const row=page.locator('.codeslot-table tr').filter({has:page.locator(`input[value="${id}"][name="ids[]"]`)});
  await row.getByRole('button',{name:'Quick Edit',exact:true}).click();
  await page.locator('#codeslot-quick_name').fill('Quick Updated');
  await page.locator('#codeslot-quick_priority').fill('37');
  await page.locator('#codeslot-quick_tags').fill('ui, verified');
  await page.getByRole('button',{name:'Update Snippet',exact:true}).click();
  await expect(page.locator('.notice-success')).toContainText('saved');
  expect(snippet(id)).toMatchObject({name:'Quick Updated',priority:37,code:'<!-- CS-KEEP-CODE -->',tags:['ui','verified']});
  await page.locator(`input[value="${id}"][name="ids[]"]`).check();
  await page.locator('[name="bulk"]').selectOption('edit');
  await page.locator('[name="bulk_priority_enabled"]').check();
  await page.locator('[name="bulk_priority"]').fill('48');
  await page.locator('[name="bulk_status"]').selectOption('active');
  await expect(page.locator('#codeslot-bulk-form [name="confirm"]')).toHaveCount(0);
  await page.getByRole('button',{name:'Apply',exact:true}).click();
  await expect(page.locator('.notice-success')).toContainText('saved');
  expect(snippet(id)).toMatchObject({name:'Quick Updated',priority:48,active:true,code:'<!-- CS-KEEP-CODE -->',tags:['ui','verified']});
});
test('Delete requires confirmation and cancels without removing data', async ({page}) => {
  const row=page.locator('.codeslot-table tr').filter({has:page.locator(`input[value="${id}"][name="ids[]"]`)});
  page.once('dialog',dialog=>dialog.dismiss());
  await row.getByRole('button',{name:'Delete',exact:true}).click();
  expect(snippet(id)).not.toBeNull();
  page.once('dialog',dialog=>dialog.accept());
  await row.getByRole('button',{name:'Delete',exact:true}).click();
  await expect(page.locator('.notice-success')).toContainText('saved');
  expect(snippet(id)).toBeNull();
});
test('Quick Edit rejects missing nonces and locked snippets', async ({page}) => {
  const denied=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'quick_edit',id,quick_name:'Forbidden'}});
  expect(denied.status()).toBe(403);
  expect(snippet(id).name).toBe('List action fixture');
  const nonce=await page.locator('#codeslot-quick-form [name="_wpnonce"]').inputValue();
  wp('eval',`wp_set_current_user(1); $s=CodeSlot\\Repository::get(${id}); $s["locked"]=true; CodeSlot\\Repository::save($s,${id});`);
  const locked=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'quick_edit',id,_wpnonce:nonce,quick_name:'Forbidden'}});
  expect(locked.status()).toBe(400);
  expect(snippet(id).name).toBe('List action fixture');
});

test('pagination preserves search and mobile Select page selects the visible rows', async ({page}) => {
  const ids = JSON.parse(wp('eval', 'wp_set_current_user(1); $ids=[]; for($i=0;$i<31;$i++) { $ids[]=CodeSlot\\Repository::save(["name"=>"UI pagination fixture ".$i,"type"=>"text","code"=>"page fixture","location"=>"footer","active"=>false]); } echo wp_json_encode($ids);'));
  try {
    await page.setViewportSize({width:390,height:950});
    await page.goto('/wp-admin/admin.php?page=codeslot&s=UI+pagination+fixture');
    await expect(page.locator('.codeslot-table [name="ids[]"]')).toHaveCount(30);
    await page.locator('#codeslot-select-page').check();
    await expect(page.locator('.codeslot-table [name="ids[]"]:checked')).toHaveCount(30);
    await page.getByRole('link',{name:'Next page',exact:true}).click();
    await expect(page.locator('[name="s"]')).toHaveValue('UI pagination fixture');
    await expect(page.locator('.codeslot-table [name="ids[]"]')).toHaveCount(1);
    await expect(page.getByRole('link',{name:'Previous page',exact:true})).toBeVisible();
  } finally {
    wp('eval', `foreach (${JSON.stringify(ids).replace('[','array(').replace(']',')')} as $id) { wp_delete_post($id,true); }`);
  }
});
