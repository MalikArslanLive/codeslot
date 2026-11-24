/** Read-only browser traffic, with all vendor SDKs mocked, on a disposable WordPress site. */
import {chromium} from '@playwright/test';
import assert from 'node:assert/strict';
import {execFileSync} from 'node:child_process';
const site=process.env.CODESLOT_TEST_URL || 'http://localhost:8888/';
const path=process.env.CODESLOT_TEST_PATH || '/var/www/html';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','--path='+path,'eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const before=wp(`echo wp_json_encode(['pixels'=>get_option('codeslot_pixels',[]),'settings'=>get_option('codeslot_settings',[])]);`);
const providers={meta:{id:'123456789012345'},google:{id:'G-HEADTEST123'},google_ads:{id:'AW-123456789'},gtm:{id:'GTM-TEST123'},pinterest:{id:'1234567890123'},tiktok:{id:'HEADPIXEL12345'},snapchat:{id:'12345678-abcd-1234-abcd-123456789012'}};
for(const config of Object.values(providers)){config.enabled=true;config.events=[];}
let browser;
try{
 wp(`update_option('codeslot_settings',['safe_mode'=>false]);update_option('codeslot_pixels',json_decode(base64_decode('${Buffer.from(JSON.stringify(providers)).toString('base64')}'),true));`);
 browser=await chromium.launch({executablePath:'/usr/bin/chromium',headless:true});const page=await browser.newPage();const errors=[],requests=[];page.on('pageerror',error=>errors.push(error.message));
 await page.route(/https:\/\/(connect\.facebook\.net|www\.googletagmanager\.com|s\.pinimg\.com|analytics\.tiktok\.com|sc-static\.net)\//,route=>{requests.push(route.request().url());return route.fulfill({contentType:'application/javascript',body:'/* isolated SDK fixture */'});});
 await page.addInitScript(()=>{window.headLoads=[];const append=window.Node.prototype.appendChild;window.Node.prototype.appendChild=function(node){if(node.tagName==='SCRIPT' && /connect.facebook.net|googletagmanager.com|s.pinimg.com|sc-static.net|analytics.tiktok.com/.test(node.src))window.headLoads.push({head:this===document.head,body:!!document.body});return append.call(this,node);};});
 await page.goto(site);await page.waitForFunction(()=>window.CodeSlotPixels && window.headLoads.length===6);
 const html=await(await page.request.get(site)).text();const end=html.toLowerCase().indexOf('</head>');assert(end>0);for(const id of ['codeslot-pixel-config','codeslot-pixels-js']){const index=html.indexOf('id="'+id+'"');assert(index>0 && index<end,id+' must precede </head>');assert.equal(await page.locator('head #'+id).count(),1);assert.equal(await page.locator('body #'+id).count(),0);}
 assert.deepEqual(await page.evaluate(()=>window.headLoads),Array.from({length:6},()=>({head:true,body:false})));assert.equal(requests.length,6);assert.deepEqual(errors,[]);assert.deepEqual((await page.evaluate(()=>window.CodeSlotPixels.getStatus())).events,[]);
 await page.evaluate(()=>document.body.dispatchEvent(new window.CustomEvent('wc-blocks_added_to_cart')));assert.deepEqual(errors,[]);
 console.log('PASS: clean-installed ZIP prints one configuration/loader in head; all six SDK requests begin before body parsing; no body-access errors or unselected events.');
}finally{await browser?.close();wp(`$old=json_decode(base64_decode('${Buffer.from(before).toString('base64')}'),true);update_option('codeslot_pixels',$old['pixels']);update_option('codeslot_settings',$old['settings']);`);}
