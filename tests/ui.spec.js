import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
const pages = ['codeslot', 'codeslot-new', 'codeslot-global', 'codeslot-transfer', 'codeslot-tools', 'codeslot-settings'];
test.beforeEach(async ({ page }) => {
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill('codeslot-admin');
  await page.locator('#user_pass').fill('disposable-test-password');
  await page.locator('#wp-submit').click();
  await page.waitForURL(/\/wp-admin\//);
});
for (const width of [1440, 768, 390, 320]) {
  test(`all admin pages fit at ${width}px`, async ({ page }) => {
    await page.setViewportSize({ width, height: 1000 });
    for (const slug of pages) {
      await page.goto(`/wp-admin/admin.php?page=${slug}`);
      await expect(page.locator('.codeslot-nav [aria-current="page"]')).toHaveCount(1);
      await expect(page.locator('.codeslot-content')).toBeVisible();
      if (slug === 'codeslot' && width <= 782) {
        const priority = page.locator('.codeslot-table td[data-label="Priority"]').first();
        await expect(priority).toBeVisible();
        expect(await priority.evaluate(el => window.getComputedStyle(el, '::before').content)).toContain('Priority');
      }
      expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), slug).toBe(true);
      for (const button of await page.locator('.codeslot-wrap .button:visible').all()) {
        const box = await button.boundingBox();
        expect(box.x, `${slug}: button left edge`).toBeGreaterThanOrEqual(0);
        expect(box.x + box.width, `${slug}: button right edge`).toBeLessThanOrEqual(width + 1);
      }
      if (width === 1440 || width === 390) {
        await page.screenshot({ path: `test-results/ui-${slug}-${width}.png`, fullPage: true });
      }
    }
  });
}
test('unnamed legacy snippet remains visible and editable; name is required for new UI saves', async ({ page }) => {
  const id = execFileSync('docker', ['compose', 'run', '--rm', 'cli', 'eval', 'wp_set_current_user(1); echo CodeSlot\\Repository::save(["type"=>"text","code"=>"Legacy fixture","location"=>"footer","active"=>false]);'], {encoding:'utf8'}).trim().match(/(\d+)$/)[1];
  await page.goto('/wp-admin/admin.php?page=codeslot');
  const row = page.locator('.codeslot-table tr').filter({ hasText: `Untitled snippet #${id}` }).first();
  await expect(row).toBeVisible();
  await row.getByRole('link', { name: /^Edit Untitled snippet/ }).click();
  await expect(page.locator('h2')).toContainText('Edit Snippet');
  await expect(page.locator('#codeslot-name')).toHaveAttribute('required', '');
  await page.locator('#codeslot-name').fill('Renamed legacy snippet');
  await page.getByRole('button', { name: 'Save Snippet', exact: true }).click();
  await expect(page.locator('.notice-success')).toContainText('saved');
  await page.goto('/wp-admin/admin.php?page=codeslot');
  await expect(page.getByRole('link', { name: 'Renamed legacy snippet', exact: true }).first()).toBeVisible();
  execFileSync('docker', ['compose', 'run', '--rm', 'cli', 'post', 'delete', id, '--force']);
});
test('filter controls retain their selection and reset clears filters', async ({ page }) => {
  await page.goto('/wp-admin/admin.php?page=codeslot');
  await page.locator('[name="status"]').selectOption('draft');
  await page.locator('[name="sort"]').selectOption('title');
  await page.locator('[name="category"]').fill('missing-category');
  await page.getByRole('button', { name: 'Filter', exact: true }).click();
  await expect(page.locator('[name="status"]')).toHaveValue('draft');
  await expect(page.locator('[name="sort"]')).toHaveValue('title');
  await expect(page.locator('[name="category"]')).toHaveValue('missing-category');
  await expect(page.getByRole('heading', { name: 'No snippets found' })).toBeVisible();
  await page.getByRole('link', { name: 'Reset', exact: true }).click();
  await expect(page.locator('[name="category"]')).toHaveValue('');
});
test('conditions remain usable on a narrow viewport', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 900 });
  await page.goto('/wp-admin/admin.php?page=codeslot-new');
  await page.getByRole('button', { name: 'OR group', exact: true }).first().click();
  await page.locator('[aria-label="Condition"]').selectOption('singular');
  await page.locator('[aria-label="Value (ID, role, path or taxonomy:ID)"]').fill('123');
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
  await page.getByRole('button', { name: 'AND rule', exact: true }).click();
  await expect(page.locator('[aria-label="Condition"]')).toHaveCount(2);
  await page.getByRole('button', { name: 'Remove group', exact: true }).click();
  await expect(page.locator('[aria-label="Condition"]')).toHaveCount(0);
});
