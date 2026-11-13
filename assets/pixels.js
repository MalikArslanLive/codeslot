(function () {
  'use strict';
  if (window.CodeSlotPixels) return;
  const element = document.getElementById('codeslot-pixel-config');
  if (!element) return;
  const config = JSON.parse(element.textContent);
  let consent = false;
  let started = false;
  const queues = {};
  const sent = new Set();
  function load(url) {
    const script = document.createElement('script'); script.async = true; script.src = url; document.head.appendChild(script);
  }
  function queue(name, method) {
    if (!window[name]) {
      const fn = function () { if (fn.callMethod) fn.callMethod.apply(fn, arguments); else if (fn.handleRequest) fn.handleRequest.apply(fn, arguments); else fn[method].push(Array.from(arguments)); };
      fn[method] = []; window[name] = fn;
    }
    return window[name];
  }
  function initialize() {
    Object.entries(config.providers).forEach(([provider, settings]) => {
      if (provider === 'meta') {
        const fn = queue('fbq', 'queue'); fn.loaded = true; fn.version = '2.0'; window._fbq = window._fbq || fn;
        load('https://connect.facebook.net/en_US/fbevents.js'); fn('init', settings.id); queues.meta = fn;
      } else if (provider === 'google') {
        window.dataLayer = window.dataLayer || []; window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        window.gtag('js', new Date()); window.gtag('config', settings.id, {send_page_view: false});
        load('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(settings.id)); queues.google = window.gtag;
      } else if (provider === 'pinterest') {
        const fn = queue('pintrk', 'queue'); fn.version = '3.0'; load('https://s.pinimg.com/ct/core.js'); fn('load', settings.id); queues.pinterest = fn;
      } else if (provider === 'snapchat') {
        const fn = queue('snaptr', 'queue'); load('https://sc-static.net/scevent.min.js'); fn('init', settings.id); queues.snapchat = fn;
      } else if (provider === 'tiktok') {
        const ttq = window.ttq = window.ttq || []; window.TiktokAnalyticsObject = 'ttq';
        ['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie','holdConsent','revokeConsent','grantConsent'].forEach(method => { ttq[method] = ttq[method] || function () { ttq.push([method].concat(Array.from(arguments))); }; });
        ttq._i = ttq._i || {}; ttq._i[settings.id] = []; ttq._i[settings.id]._u = 'https://analytics.tiktok.com/i18n/pixel/events.js';
        ttq._t = ttq._t || {}; ttq._t[settings.id] = Date.now(); ttq._o = ttq._o || {}; ttq._o[settings.id] = {};
        load('https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=' + encodeURIComponent(settings.id) + '&lib=ttq'); queues.tiktok = ttq;
      }
    });
  }
  const mapping = {
    meta: {page_view:'PageView',view_content:'ViewContent',add_to_cart:'AddToCart',begin_checkout:'InitiateCheckout',purchase:'Purchase',click:'CodeSlotClick'},
    pinterest: {page_view:'pagevisit',view_content:'pagevisit',add_to_cart:'addtocart',begin_checkout:'custom',purchase:'checkout',click:'custom'},
    tiktok: {page_view:'Pageview',view_content:'ViewContent',add_to_cart:'AddToCart',begin_checkout:'InitiateCheckout',purchase:'CompletePayment',click:'ClickButton'},
    snapchat: {page_view:'PAGE_VIEW',view_content:'VIEW_CONTENT',add_to_cart:'ADD_CART',begin_checkout:'START_CHECKOUT',purchase:'PURCHASE',click:'CUSTOM_EVENT_1'}
  };
  function track(event, details) {
    if (!consent || !started) return;
    details = details || {};
    Object.entries(config.providers).forEach(([provider, settings]) => {
      if (!settings.events.includes(event)) return;
      const purchaseKey = 'codeslot:' + config.site + ':' + provider + ':' + details.event_id;
      if (event === 'purchase') {
        try { if (window.sessionStorage.getItem(purchaseKey)) return; } catch { /* Storage can be disabled. */ }
        if (sent.has(purchaseKey)) return;
      }
      const data = Object.assign({}, details);
      if (provider === 'meta') queues.meta('track' + (event === 'click' ? 'Custom' : ''), mapping.meta[event], data, details.event_id ? {eventID:details.event_id} : {});
      else if (provider === 'google') {
        if (details.event_id) data.transaction_id = details.event_id;
        if (event === 'purchase' && settings.id.startsWith('AW-') && settings.label) { data.send_to = settings.id + '/' + settings.label; queues.google('event', 'conversion', data); }
        else { data.send_to = settings.id; queues.google('event', event === 'click' ? 'codeslot_click' : event, data); }
      } else if (provider === 'pinterest') {
        if (event === 'page_view') queues.pinterest('page'); else queues.pinterest('track', mapping.pinterest[event], data);
      } else if (provider === 'tiktok') {
        if (event === 'page_view') queues.tiktok.page(); else queues.tiktok.track(mapping.tiktok[event], data, details.event_id ? {event_id:details.event_id} : {});
      } else if (provider === 'snapchat') queues.snapchat('track', mapping.snapchat[event], data);
      if (event === 'purchase') { sent.add(purchaseKey); try { window.sessionStorage.setItem(purchaseKey, '1'); } catch { /* No persistence available. */ } }
    });
  }
  function updateConsent(value) {
    consent = value && value.marketing === true;
    if (!consent || started) return;
    started = true; initialize(); track('page_view');
    Object.entries(config.context).forEach(([event, data]) => track(event, data));
  }
  window.CodeSlotPixels = {setConsent:updateConsent};
  window.addEventListener('codeslot:consent', event => updateConsent(event.detail));
  if (window.jQuery) window.jQuery(document.body).on('added_to_cart', function (event, fragments, hash, button) {
    const id = button && button.data('product_id'); track('add_to_cart', id ? {content_ids:[String(id)]} : {});
  });
  document.body.addEventListener('wc-blocks_added_to_cart', () => track('add_to_cart'));
  document.addEventListener('click', event => {
    if (!config.click_selector || !(event.target instanceof window.Element)) return;
    try { if (event.target.closest(config.click_selector)) track('click'); } catch { /* Invalid administrator-provided selector. */ }
  });
  updateConsent(window.CodeSlotConsent);
}());
