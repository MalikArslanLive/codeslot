import { test, expect } from '@playwright/test';
test('Elementor preview retains individual page slots',async({page})=>{
 test.setTimeout(60000);
 await page.goto('/wp-login.php');
 await page.locator('#user_login').fill('codeslot-admin');
 await page.locator('#user_pass').fill('disposable-test-password');
 await page.locator('#wp-submit').click();
 await page.waitForURL(/\/wp-admin\//);
 for(let attempt=0;attempt<5;attempt++) {
   await page.goto('/wp-admin/edit.php?post_type=page');
   if(await page.locator('.row-title').filter({hasText:/^Page A$/}).count()) break;
 }
 const href=await page.locator('.row-title').filter({hasText:/^Page A$/}).getAttribute('href');
 const id=href.match(/[?&]post=(\d+)/)[1];
 await page.goto('/wp-admin/post.php?post='+id+'&action=elementor');
 const iframe=page.locator('#elementor-preview-iframe');
 await expect(iframe).toBeAttached({timeout:45000});
 const handle=await iframe.elementHandle();
 const frame=await handle.contentFrame();
 await expect.poll(async()=> (await frame.content()).includes('CS-PAGE-A'),{timeout:15000}).toBeTruthy();
 const source=await frame.content();
 expect(source.split('CS-PAGE-A').length-1).toBe(1);
 expect(source).not.toContain('CS-PAGE-B');
 expect(source).toContain('CS-GLOBAL-HEADER');
});
