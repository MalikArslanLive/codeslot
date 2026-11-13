import { test, expect } from '@playwright/test';
test.beforeEach(async ({page})=>{
 await page.goto('/wp-login.php');
 await page.locator('#user_login').fill('codeslot-admin');
 await page.locator('#user_pass').fill('disposable-test-password');
 await page.locator('#wp-submit').click();
 await expect(page.locator('#wpadminbar')).toBeVisible();
});
test('global editors and settings save',async({page})=>{
 await page.goto('/wp-admin/options-general.php?page=codeslot-global');
 await expect(page.locator('.CodeMirror')).toHaveCount(3);
 await page.locator('#submit').click();
 await expect(page.locator('.notice-success')).toContainText('saved');
});
test('create snippet, conditions builder, activation and frontend',async({page})=>{
 await page.goto('/wp-admin/admin.php?page=codeslot-new');
 await page.locator('#codeslot-name').fill('Browser snippet');
 await page.locator('#codeslot-type').selectOption('text');
 await page.locator('#codeslot-location').selectOption('footer');
 await page.locator('.CodeMirror').click();
 await page.keyboard.type('CS-BROWSER-SNIPPET');
 await page.getByRole('button',{name:'OR group'}).first().click();
 await page.locator('[aria-label="Condition"]').selectOption('frontend');
 await page.locator('[name="active"]').check();
 await page.locator('#submit').click();
 await expect(page.locator('.notice-success')).toContainText('saved');
 await page.goto('/');
 await expect(page.locator('body')).toContainText('CS-BROWSER-SNIPPET');
});
test('classic content meta box',async({page})=>{
 await page.goto('/wp-admin/post-new.php?post_type=page&classic-editor');
 await expect(page.locator('#codeslot-slots')).toBeVisible();
 await expect(page.locator('#codeslot-slots textarea')).toHaveCount(3);
});
test('Gutenberg native meta box integration',async({page})=>{
 await page.goto('/wp-admin/post-new.php?post_type=page&classic-editor__forget');
 await expect(page.locator('#editor')).toBeVisible({timeout:30000});
 const dialog=page.getByRole('dialog');
 if(await dialog.count()) { await dialog.getByRole('button',{name:/close/i}).click(); }
 const meta=page.getByRole('button',{name:'Meta Boxes',exact:true});
 if(await meta.count()) { await meta.click(); }
 await expect(page.locator('#codeslot-slots')).toBeVisible();
 await expect(page.locator('#codeslot-slots textarea')).toHaveCount(3);
});
test('WooCommerce product code fields',async({page})=>{
 await page.goto('/wp-admin/post-new.php?post_type=product');
 await expect(page.locator('#codeslot-slots')).toBeAttached({timeout:30000});
 await expect(page.locator('#codeslot-slots textarea')).toHaveCount(3);
});
test('admin mutation rejects missing nonce',async({page})=>{
 const response=await page.request.post('/wp-admin/admin-post.php',{form:{action:'codeslot_save',task:'global'}});
 expect(response.status()).toBe(403);
});
