// Opt-in live check. Sends PageView and CodeSlotClick to the supplied public Pixel ID.
// Never run this against an account without its owner's authorization.
import {chromium} from '@playwright/test';
import {readFile, writeFile} from 'node:fs/promises';
import assert from 'node:assert/strict';
import {createHash, X509Certificate} from 'node:crypto';

const pixelId = process.env.CODESLOT_META_PIXEL_ID;
assert.match(pixelId || '', /^\d{5,30}$/, 'Set CODESLOT_META_PIXEL_ID to an authorized public Meta Pixel ID.');
const base = process.env.CODESLOT_TEST_URL || 'http://localhost:8888';
const livePage = process.env.CODESLOT_META_PAGE_URL;
const reportPath = process.env.CODESLOT_META_REPORT || '/tmp/codeslot-live-meta.json';
const proxy = process.env.HTTPS_PROXY || process.env.https_proxy;
// Pin only the supplied inspection CA key in this temporary browser process.
// Do not modify a persistent trust store or ignore arbitrary TLS errors.
const caFile = process.env.CODESLOT_PROXY_CA_FILE;
const args = [];
if (caFile) {
  const certificate = new X509Certificate(await readFile(caFile));
  const key = certificate.publicKey.export({format:'der',type:'spki'});
  args.push('--ignore-certificate-errors-spki-list=' + createHash('sha256').update(key).digest('base64'));
}
const browser = await chromium.launch({
  headless:process.env.CODESLOT_HEADED !== '1',
  executablePath:process.env.CHROMIUM_PATH || '/usr/bin/chromium',
  args,
  ...(proxy ? {proxy:{server:proxy,bypass:'<-loopback>,localhost,127.0.0.1'}} : {})
});
const report = {time:new Date().toISOString(),pixelId,origin:livePage ? new URL(livePage).origin : base,pageMode:livePage ? 'configured WordPress page' : 'isolated controller fixture',browserMode:process.env.CODESLOT_HEADED === '1' ? 'headed Chromium' : 'headless Chromium',tls:caFile ? 'scoped supplied CA key; temporary browser only' : 'default certificate verification',sdk:[],tracking:[],failed:[],messages:[],requests:[],checks:{},result:'failed'};
const context = await browser.newContext();
const page = await context.newPage();
page.on('request', request => { const url=new URL(request.url()); if(url.hostname.includes('facebook')) report.requests.push({host:url.hostname,path:url.pathname}); });
page.on('console', message => { if (['error','warning'].includes(message.type())) report.messages.push({type:message.type(),text:message.text().slice(0,1500)}); });
page.on('pageerror', error => report.messages.push({type:'pageerror',text:error.message}));
page.on('response', response => {
  const url = new URL(response.url());
  if (url.hostname === 'connect.facebook.net') report.sdk.push({path:url.pathname,status:response.status()});
  if (url.hostname.endsWith('facebook.com') && url.pathname === '/tr/') {
    const parameters = new URLSearchParams(response.request().postData() || '');
    report.tracking.push({event:url.searchParams.get('ev') || parameters.get('ev'),id:url.searchParams.get('id') || parameters.get('id'),status:response.status()});
  }
});
page.on('requestfailed', request => {
  const url = new URL(request.url());
  if (url.hostname.endsWith('facebook.com') || url.hostname === 'connect.facebook.net') report.failed.push({host:url.hostname,path:url.pathname,error:request.failure()?.errorText});
});
const script = await readFile(new URL('../assets/pixels.js', import.meta.url), 'utf8');
const config = {providers:{meta:{id:pixelId,events:['page_view','click']}},site:'codeslot-live-qa',context:{},click_selector:'.qa-click'};
if (!livePage) await page.route(base + '/codeslot-live-meta-check/', route => route.fulfill({
  contentType:'text/html',
  body:'<!doctype html><html><head><title>CodeSlot authorized live pixel check</title></head><body><button type="button" class="qa-click">Test click</button><script type="application/json" id="codeslot-pixel-config">' + JSON.stringify(config) + '</script></body></html>'
}));
try {
  const navigation = await page.goto(livePage || base + '/codeslot-live-meta-check/');
  report.pageStatus = navigation.status();
  report.pageTitle = await page.title();
  assert.equal(navigation.status(),200,'The test page must be accessible with HTTP 200.');
  if (!livePage) await page.addScriptTag({content:script});
  await page.waitForFunction(()=>Boolean(window.CodeSlotPixels));
  assert.equal(await page.locator('#codeslot-pixel-config').evaluate(element=>JSON.parse(element.textContent).providers.meta.id),pixelId);
  await page.waitForTimeout(1200);
  assert.equal(report.requests.length,0); assert.equal(report.sdk.length,0); assert.equal(report.tracking.length,0);
  report.checks.noRequestsBeforeConsent = true;
  await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:true}));
  await page.waitForFunction(()=>window.CodeSlotPixels.getStatus().sdk.meta === 'loaded',null,{timeout:30000});
  await page.waitForTimeout(10000);
  assert.ok(report.tracking.some(row=>row.event==='PageView' && row.id===pixelId && row.status===200),'No HTTP 200 PageView response from Meta.');
  report.checks.pageViewHttp200 = true;
  await page.getByRole('button',{name:'Test click'}).click();
  await page.waitForTimeout(2500);
  assert.ok(report.tracking.some(row=>row.event==='CodeSlotClick' && row.id===pixelId && row.status===200),'No HTTP 200 CodeSlotClick response from Meta.');
  report.checks.clickHttp200 = true;
  const count = report.tracking.length;
  await page.evaluate(()=>window.CodeSlotPixels.setConsent({marketing:false}));
  await page.getByRole('button',{name:'Test click'}).click();
  await page.waitForTimeout(1500);
  assert.equal(report.tracking.length,count);
  report.checks.noNewCodeSlotEventsAfterWithdrawal = true;
  assert.ok(report.tracking.every(row=>['PageView','CodeSlotClick'].includes(row.event)));
  report.checks.noCommerceEventsSent = true;
  report.runtime = await page.evaluate(()=>window.CodeSlotPixels.getStatus());
  report.result = 'passed';
} catch (error) {
  report.error = error.message;
  process.exitCode = 1;
} finally {
  report.runtime = await page.evaluate(()=>window.CodeSlotPixels?.getStatus()).catch(()=>null);
  report.metaState = await page.evaluate(()=>({callMethod:typeof window.fbq?.callMethod,queue:window.fbq?.queue,state:window.fbq?.getState?.()})).catch(()=>null);
  await browser.close();
  await writeFile(reportPath,JSON.stringify(report,null,2)+'\n');
  console.log(JSON.stringify(report,null,2));
}
