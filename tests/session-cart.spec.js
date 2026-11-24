import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {Buffer} from 'node:buffer';
function wp(code){return execFileSync('docker',['compose','run','--rm','cli','eval',code],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim().split('\n').at(-1);}
const encode=v=>Buffer.from(JSON.stringify(v)).toString('base64');
test('asynchronous cart events use the session product and quantity, not the currently viewed product',async({browser})=>{
 test.setTimeout(180000);
 const original=JSON.parse(wp(`$out=[];foreach(['codeslot_pixels','codeslot_settings'] as $key)$out[$key]=get_option($key,null);echo wp_json_encode($out);`));let products=[];let context;
 try{
  const fixture=JSON.parse(wp(`$out=[];foreach([5,12] as $price){$p=new WC_Product_Simple();$p->set_name('Session cart fixture '.$price);$p->set_status('publish');$p->set_regular_price((string)$price);$p->set_price((string)$price);$p->save();$out[]=['id'=>$p->get_id(),'url'=>get_permalink($p->get_id())];}update_option('codeslot_settings',['safe_mode'=>false]);update_option('codeslot_pixels',['meta'=>['id'=>'123456789012345','enabled'=>true,'events'=>['add_to_cart']],'pinterest'=>['id'=>'1234567890123','enabled'=>true,'events'=>['page_view']]]);echo wp_json_encode($out);`));products=fixture.map(p=>p.id);
  context=await browser.newContext({baseURL:'http://localhost:8888'});const page=await context.newPage();await page.route('https://connect.facebook.net/**',route=>route.fulfill({contentType:'application/javascript',body:'/* isolated Meta queue */'}));await page.route('https://s.pinimg.com/**',route=>route.fulfill({contentType:'application/javascript',body:'/* isolated Pinterest queue */'}));await page.goto(fixture[0].url);
  const visit=await page.evaluate(()=>window.pintrk.queue.find(a=>a[0]==='track'&&a[1]==='pagevisit')[2]);expect(visit.line_items[0]).toMatchObject({product_id:String(fixture[0].id),product_price:5,product_quantity:1});
  const added=await page.request.post('/?wc-ajax=add_to_cart',{form:{product_id:String(fixture[1].id),quantity:'2'}});expect(added.ok()).toBe(true);expect((await added.json()).error).not.toBe(true);
  expect(await page.locator('#codeslot-pixel-config').evaluate(el=>JSON.parse(el.textContent).cart_url)).toContain('wc-ajax=codeslot_cart_event');await page.evaluate(()=>document.body.dispatchEvent(new window.CustomEvent('wc-blocks_added_to_cart')));await expect.poll(()=>page.evaluate(()=>window.fbq.queue.filter(a=>a[1]==='AddToCart').length)).toBe(1);
  const data=await page.evaluate(()=>window.fbq.queue.find(a=>a[1]==='AddToCart')[2]);expect(data.content_ids).toEqual([String(fixture[1].id)]);expect(data.value).toBe(24);expect(data).not.toHaveProperty('email');
  await page.evaluate(()=>document.body.dispatchEvent(new window.CustomEvent('wc-blocks_added_to_cart')));await page.waitForTimeout(500);expect(await page.evaluate(()=>window.fbq.queue.filter(a=>a[1]==='AddToCart').length)).toBe(1);
  const anonymous=await browser.newContext({baseURL:'http://localhost:8888'});const outsider=await anonymous.request.get('/?wc-ajax=codeslot_cart_event');expect(await outsider.json()).toEqual([]);await anonymous.close();
 }finally{if(context)await context.close();wp(`$data=json_decode(base64_decode('${encode(original)}'),true);foreach($data as $key=>$value){if(null===$value)delete_option($key);else update_option($key,$value);}foreach(json_decode(base64_decode('${encode(products)}'),true) as $id)wp_delete_post($id,true);`);}
});
