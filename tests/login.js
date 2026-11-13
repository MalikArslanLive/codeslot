/** Authenticate against WordPress itself; avoid its delayed login-field autofocus. */
export async function login(page) {
  await page.goto('/wp-login.php');
  const response = await page.request.post('/wp-login.php', {form:{
    log:'codeslot-admin',
    pwd:'disposable-test-password',
    'wp-submit':'Log In',
    redirect_to:'/wp-admin/',
    testcookie:'1'
  }});
  if (!response.ok()) throw new Error(`WordPress login failed: HTTP ${response.status()}`);
  await page.goto('/wp-admin/');
  await page.waitForURL(/\/wp-admin\//);
}
