(function () {
  'use strict';
  const phpLocations = ['frontend','admin','everywhere','bootstrap','manual','once'];
  const htmlLocations = ['header','body','footer','admin_header','admin_footer','selector_before','selector_after','selector_prepend','selector_append','selector_replace','before_content','after_content','before_paragraph','after_paragraph','shortcode','manual','woocommerce_before_single_product','woocommerce_after_single_product','woocommerce_before_shop_loop','woocommerce_after_shop_loop'];
  const editors = [];
  document.querySelectorAll('.codeslot-code').forEach(function (el) {
    if (window.wp && window.wp.codeEditor) {
      const editor = window.wp.codeEditor.initialize(el, {codemirror: {mode: el.dataset.codeslotMode, lineNumbers:true, indentUnit:4}});
      editors.push(editor);
      el.codeslotEditor = editor.codemirror;
    }
  });
  const type = document.getElementById('codeslot-type');
  const location = document.getElementById('codeslot-location');
  function setType() {
    if (!type || !location) return;
    const allowed = type.value === 'php' ? phpLocations : htmlLocations;
    Array.from(location.options).forEach(o => { o.hidden = !allowed.includes(o.value); o.disabled = o.hidden || !!o.dataset.unavailable; });
    if (!allowed.includes(location.value)) location.value = allowed[0];
    const preserved=document.getElementById('codeslot-location-preserve');if(preserved){preserved.disabled=!location.selectedOptions[0]?.dataset.unavailable;preserved.value=location.value;}
    const paragraph = document.getElementById('codeslot-paragraph-field');
    if (paragraph) paragraph.hidden = !['before_paragraph','after_paragraph'].includes(location.value);
    const selector=document.getElementById('codeslot-selector-field');
    if(selector){selector.hidden=!location.value.startsWith('selector_');selector.querySelector('input').required=!selector.hidden;}
    for(const [key,supported] of [['load_file',['javascript','css']],['compact',['javascript']]]){
      const control=document.querySelector('[name="'+key+'"]');
      if(control){control.disabled=!supported.includes(type.value);if(control.disabled)control.checked=false;}
    }
    document.querySelectorAll('[name="insertion_method"]').forEach(radio=>{
      radio.disabled=type.value==='php' && radio.value==='shortcode';
      radio.checked=radio.value===(['manual','shortcode'].includes(location.value)?location.value:'auto');
    });
    const mode=['manual','shortcode'].includes(location.value)?location.value:'auto';
    const field=document.getElementById('codeslot-location-field');
    if(field)field.hidden=mode!=='auto';
    updateLocationPicker();
    const usage=document.getElementById('codeslot-insert-usage');
    if(usage){
      usage.replaceChildren();usage.hidden=mode==='auto';
      if(mode==='shortcode'){
        if(Number(usage.dataset.id)>0){const code=document.createElement('code');code.textContent='[codeslot id="'+usage.dataset.id+'"]';const copy=document.createElement('button');copy.type='button';copy.className='button codeslot-copy-shortcode';copy.dataset.copy=code.textContent;copy.textContent='Copy';usage.append(code,copy);}
        else usage.textContent='Save this snippet to generate its shortcode.';
      }else if(mode==='manual'){
        const name=document.querySelector('[name="name"]')?.value || '';
        const code=document.createElement('code');code.textContent="do_action( 'codeslot_manual', '"+name.replace(/\\/g,'\\\\').replace(/'/g,"\\'")+"' );";usage.append('Run using the exact saved snippet name: ',code);
      }
    }
    const help=document.getElementById('codeslot-location-help');
    const descriptions={before_content:'Before the main content of an individual post, page, product or supported content item; not archive listings.',after_content:'After the main content of an individual post, page, product or supported content item; not archive listings.',before_paragraph:'Before the selected paragraph in individual main content; not archive listings.',after_paragraph:'After the selected paragraph in individual main content; not archive listings.',header:'Printed inside the frontend <head>.',body:'Printed after the opening <body> via wp_body_open.',footer:'Printed near the closing frontend </body>.',admin_header:'Printed in the wp-admin head.',admin_footer:'Printed in the wp-admin footer.',frontend:'PHP runs after frontend query setup.',admin:'PHP runs during admin_init.',everywhere:'PHP runs on the frontend and in wp-admin after setup. AJAX, REST, cron and CLI are excluded; use Early hooks for those requests.',bootstrap:'PHP runs at init priority 20 on frontend, admin, login, AJAX, REST, cron and CLI. Query conditions are not supported.',once:'Runs only through its confirmed Run Once action.',manual:'Runs through do_action("codeslot_manual", "Exact snippet name") after query/admin setup.',shortcode:'Insert the displayed [codeslot id="…"] shortcode where this snippet should appear.'};
    if(help)help.textContent=location.selectedOptions[0]?.dataset.unavailable ? location.selectedOptions[0].dataset.unavailable+' Saved location is retained; execution is paused until it becomes available or you explicitly choose another location.' : descriptions[location.value] || 'Runs at the selected location. Use conditions below to limit pages, products, archives or visitors.';
    const active = document.querySelector('[name="active"]');
    if (active) { active.disabled = location.value === 'once'; if (active.disabled) active.checked = false; }
    const modes = {php:{name:'application/x-httpd-php',startOpen:true},javascript:'text/javascript',css:'text/css',text:'text/plain'};
    editors.forEach(e => e.codemirror.setOption('mode', modes[type.value] || 'text/html'));
    const wrappers = {javascript:['<script>','</script>'],css:['<style>','</style>'],php:['<?php','']};
    const pair = wrappers[type.value] || ['', ''];
    const open = document.getElementById('codeslot-wrapper-open');
    const close = document.getElementById('codeslot-wrapper-close');
    if (open && close) { open.textContent = pair[0]; close.textContent = pair[1]; open.hidden = !pair[0]; close.hidden = !pair[1]; }
    const wrapperHelp = document.getElementById('codeslot-wrapper-help');
    if (wrapperHelp) wrapperHelp.textContent = pair[0] ? 'Shown tags are supplied automatically. Enter only the code inside them.' : 'HTML accepts complete embeds, including script/style tags.';
    checkWrappers();
  }
  function checkWrappers() {
    const source = document.getElementById('codeslot-source');
    const warning = document.getElementById('codeslot-wrapper-warning');
    const html = document.getElementById('codeslot-use-html');
    if (!source || !warning || !html || !type) return;
    const value = source.codeslotEditor ? source.codeslotEditor.getValue() : source.value;
    const wrapped=['javascript','css'].includes(type.value) && /^\s*<(script|style)\b/i.test(value);
    const invalid=wrapped;
    warning.hidden = !wrapped; html.hidden = !wrapped;
    warning.textContent = wrapped ? 'This code already contains tags. Use HTML to keep the complete embed, or remove those tags because the selected code type adds them automatically.' : '';
    if(wrapped){document.getElementById('codeslot-wrapper-open').hidden=true;document.getElementById('codeslot-wrapper-close').hidden=true;}
    source.dataset.wrapperInvalid = invalid ? '1' : '0';
  }
  const source = document.getElementById('codeslot-source');
  if (source) {
    source.addEventListener('input', checkWrappers);
    source.form.addEventListener('submit', event => { if (source.dataset.wrapperInvalid === '1') { event.preventDefault(); if(source.codeslotEditor) source.codeslotEditor.focus(); document.getElementById('codeslot-wrapper-warning').scrollIntoView({block:'center'}); } });
    if (source.codeslotEditor) source.codeslotEditor.on('change', checkWrappers);
    const selector=document.getElementById('codeslot-selector');
    if(selector){selector.addEventListener('input',()=>selector.setCustomValidity(''));source.form.addEventListener('submit',event=>{if(!selector.closest('[hidden]') && selector.value){try{document.querySelector(selector.value);}catch{event.preventDefault();selector.setCustomValidity('Enter a valid CSS selector.');selector.reportValidity();}}});}
  }
  let locationPicker;
  function updateLocationPicker(){
    if(!locationPicker)return;
    const selected=location.selectedOptions[0];locationPicker.querySelector('summary').textContent=selected?selected.textContent+(selected.dataset.unavailable?' — unavailable':''):'Choose location';
    const search=locationPicker.querySelector('input');const result=locationPicker.querySelector('.codeslot-location-options');result.replaceChildren();
    for(const group of location.querySelectorAll('optgroup')){
      const choices=Array.from(group.children).filter(option=>!option.hidden && !['shortcode','manual'].includes(option.value) && [option.textContent,option.value,group.label,option.dataset.unavailable || ''].join(' ').toLowerCase().includes(search.value.toLowerCase()));
      if(!choices.length)continue;
      const title=document.createElement('strong');title.textContent=group.label;result.append(title);
      for(const option of choices){const button=document.createElement('button');button.type='button';button.className='codeslot-location-option';button.textContent=option.textContent+(option.dataset.unavailable?' — '+option.dataset.unavailable:'');button.disabled=option.disabled;button.setAttribute('aria-pressed',String(option.selected));button.addEventListener('click',()=>{location.value=option.value;locationPicker.open=false;search.value='';setType();locationPicker.querySelector('summary').focus();});result.append(button);}
    }
    if(!result.children.length)result.textContent='No matching locations.';
  }
  if(location){
    location.hidden=true;locationPicker=document.createElement('details');locationPicker.className='codeslot-location-picker';
    const summary=document.createElement('summary');summary.setAttribute('aria-label','Choose insertion location');
    const popup=document.createElement('div');popup.className='codeslot-location-popup';
    const search=document.createElement('input');search.type='search';search.placeholder='Search locations';search.setAttribute('aria-label','Search locations');search.addEventListener('input',updateLocationPicker);
    const options=document.createElement('div');options.className='codeslot-location-options';popup.append(search,options);locationPicker.append(summary,popup);location.after(locationPicker);
    locationPicker.addEventListener('toggle',()=>{if(locationPicker.open)search.focus();});
    locationPicker.addEventListener('keydown',event=>{if(event.key==='Escape'){locationPicker.open=false;summary.focus();}});
    document.addEventListener('click',event=>{if(!locationPicker.contains(event.target))locationPicker.open=false;});
  }
  const useHTML = document.getElementById('codeslot-use-html');
  if (useHTML) useHTML.addEventListener('click', () => { type.value = 'html'; setType(); });
  if (type) { type.addEventListener('change', setType); location.addEventListener('change', setType); setType(); }
  document.querySelectorAll('[name="insertion_method"]').forEach(radio=>radio.addEventListener('change',()=>{location.value=radio.value==='auto'?(type.value==='php'?'frontend':'header'):radio.value;setType();}));
  document.querySelector('[name="name"]')?.addEventListener('input',()=>{if(location?.value==='manual')setType();});
}());

(function () {
  'use strict';
  const panel = document.getElementById('codeslot-quick-edit');
  const quickForm = document.getElementById('codeslot-quick-form');
  if (!panel || !quickForm) return;
  let trigger;
  function closeQuick() {
    panel.hidden = true;
    if (trigger) { trigger.setAttribute('aria-expanded', 'false'); trigger.focus(); }
  }
  document.querySelectorAll('.codeslot-quick').forEach(button => button.addEventListener('click', () => {
    const snippet = JSON.parse(button.dataset.snippet);
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
    trigger = button;
    quickForm.elements.id.value = snippet.id;
    ['name','description','priority','categories','tags'].forEach(key => {
      quickForm.elements['quick_' + key].value = Array.isArray(snippet[key]) ? snippet[key].join(', ') : snippet[key];
    });
    quickForm.elements.quick_active.disabled = snippet.location === 'once';
    quickForm.elements.quick_active.checked = snippet.location !== 'once' && snippet.active;
    panel.hidden = false;
    button.setAttribute('aria-expanded', 'true');
    quickForm.elements.quick_name.focus();
    panel.scrollIntoView({block:'center', behavior: 'instant'});
  }));
  document.querySelector('.codeslot-cancel-quick').addEventListener('click', closeQuick);
  panel.addEventListener('keydown', event => { if (event.key === 'Escape') closeQuick(); });
  const bulkForm = document.getElementById('codeslot-bulk-form');
  const bulkEditor = bulkForm.querySelector('.codeslot-bulk-editor');
  bulkForm.elements.bulk.addEventListener('change', () => {
    const editing = bulkForm.elements.bulk.value === 'edit';
    bulkEditor.hidden = !editing;
    bulkEditor.disabled = !editing;
  });
  const all = [document.getElementById('codeslot-select-all'), document.getElementById('codeslot-select-page')];
  const checkboxes = Array.from(bulkForm.querySelectorAll('[name="ids[]"]'));
  all.forEach(control => control.addEventListener('change', () => {
    checkboxes.forEach(box => { box.checked = control.checked; });
    all.forEach(item => { item.checked = control.checked; item.indeterminate = false; });
  }));
  checkboxes.forEach(box => box.addEventListener('change', () => {
    all.forEach(control => {
      control.checked = checkboxes.length > 0 && checkboxes.every(item => item.checked);
      control.indeterminate = checkboxes.some(item => item.checked) && !control.checked;
    });
  }));
  const deleteForm = document.getElementById('codeslot-delete-form');
  const emptyTrash=document.getElementById('codeslot-empty-trash');
  if(emptyTrash)emptyTrash.addEventListener('click',()=>{if(!window.confirm('Permanently delete every snippet in Trash? This cannot be undone.'))return;const form=document.getElementById('codeslot-empty-trash-form');form.elements.confirm.value='1';form.requestSubmit();});
  document.querySelectorAll('.codeslot-remove').forEach(button => button.addEventListener('click', () => {
    const task=button.dataset.task || 'trash';
    if (task==='delete' && !window.confirm('Permanently delete "' + button.dataset.name + '"? This cannot be undone.')) return;
    deleteForm.elements.task.value=task;
    deleteForm.elements.id.value = button.dataset.id;
    deleteForm.elements.confirm.value = '1';
    deleteForm.requestSubmit();
  }));
}());

(function () {
  'use strict';
  document.querySelectorAll('.codeslot-clear-history').forEach(button => button.closest('form').addEventListener('submit', event => {
    if (!window.confirm('Permanently delete all saved versions? They cannot be recovered. Current code will be retained.')) event.preventDefault();
  }));
  const bulk = document.getElementById('codeslot-bulk-form');
  if (bulk) bulk.addEventListener('submit', event => {
    if(!bulk.querySelector('[name="ids[]"]:checked')){
      event.preventDefault();
      let notice=bulk.querySelector('.codeslot-selection-error');
      if(!notice){notice=document.createElement('p');notice.className='notice notice-error codeslot-selection-error';notice.setAttribute('role','alert');bulk.querySelector('.codeslot-bulk').after(notice);}
      notice.textContent='Select at least one snippet before applying an action.';
      return;
    }
    bulk.elements.confirm.value='0';
    if (bulk.elements.bulk.value === 'delete') {
      if(!window.confirm('Permanently delete the selected trashed snippets and their saved versions? This cannot be undone.'))event.preventDefault();
      else bulk.elements.confirm.value='1';
    }
  });
}());

(function () {
  'use strict';
  document.querySelectorAll('.codeslot-status-toggle').forEach(button => button.addEventListener('click', () => {
    const form = document.getElementById('codeslot-toggle-form');
    form.elements.id.value = button.dataset.id;
    form.elements.active.value = button.getAttribute('aria-checked') === 'true' ? '0' : '1';
    button.disabled = true; form.requestSubmit();
  }));
  document.querySelectorAll('.codeslot-copy-info').forEach(button => button.addEventListener('click', async () => {
    const textarea = document.getElementById('codeslot-system-info');
    try { await window.window.navigator.clipboard.writeText(textarea.value); button.textContent = 'Copied'; } catch { textarea.focus(); textarea.select(); button.textContent = 'Select and copy the text'; }
  }));
  document.querySelectorAll('.codeslot-clear-logs').forEach(button => button.closest('form').addEventListener('submit', event => {
    if (!window.confirm('Permanently clear all CodeSlot log entries? They cannot be recovered.')) event.preventDefault();
  }));
}());

(function () {
  'use strict';
  document.querySelectorAll('.codeslot-download').forEach(button => button.addEventListener('click', () => {
    const value = document.getElementById(button.dataset.target).value;
    const url = window.URL.createObjectURL(new window.Blob([value], {type:'text/plain;charset=utf-8'}));
    const link = document.createElement('a'); link.href = url; link.download = button.dataset.filename; link.click(); window.URL.revokeObjectURL(url);
  }));
}());

(function () {
  'use strict';
  const form = document.getElementById('codeslot-library-filter');
  if (!form) return;
  const search = form.elements.library_search;
  const category = form.elements.library_category;
  const cards = Array.from(document.querySelectorAll('.codeslot-recipe'));
  function filter() {
    const term = search.value.trim().toLocaleLowerCase();
    let count = 0;
    cards.forEach(card => {
      const categories = JSON.parse(card.dataset.libraryCategories);
      card.hidden = !(card.dataset.librarySearch.toLocaleLowerCase().includes(term) && (!category.value || categories.includes(category.value)));
      if (!card.hidden) count++;
    });
    document.getElementById('codeslot-library-count').textContent = count + ' recipes shown';
    document.getElementById('codeslot-library-empty').hidden = count > 0;
  }
  search.addEventListener('input', filter); category.addEventListener('change', filter);
  form.addEventListener('submit', event => { event.preventDefault(); filter(); });
  document.getElementById('codeslot-library-reset').addEventListener('click', () => { search.value = ''; category.value = ''; filter(); search.focus(); });
  filter();
}());

(function () {
  'use strict';
  document.querySelectorAll('.codeslot-pixel-card').forEach(card => {
    function state() {
      const enabled = card.querySelector('input[name$="[enabled]"]').checked;
      const id = card.querySelector('input[id^="codeslot-pixel-"]').value.trim();
      const requireConsent=card.closest('form').querySelector('[name="require_consent"]')?.checked;
      card.querySelector('.codeslot-pixel-state').textContent = !enabled ? 'Disabled' : (!id ? 'Add your tracking ID' : (requireConsent ? 'Enabled · consent required' : 'Enabled · automatic base code'));
    }
    card.closest('form').querySelector('[name="require_consent"]')?.addEventListener('change',state);
    card.addEventListener('change', state); card.addEventListener('input', state); state();
    card.closest('form').addEventListener('submit',event=>{
      const enabled=card.querySelector('input[name$="[enabled]"]');
      const input=card.querySelector('input[id^="codeslot-pixel-"]');
      let message=card.querySelector('.codeslot-field-error');
      if(enabled.checked && !input.value.trim()){
        event.preventDefault();
        if(!message){message=document.createElement('p');message.className='codeslot-field-error';message.setAttribute('role','alert');input.after(message);}
        message.textContent='Enter the public tracking ID before enabling this provider.';input.setAttribute('aria-invalid','true');input.focus();
      }else{input.removeAttribute('aria-invalid');if(message)message.remove();}
    });
  });
  const active = document.querySelector('.codeslot-nav a[aria-current="page"]');
  if (active && window.innerWidth < 783) active.scrollIntoView({block:'nearest',inline:'center'});
}());

(function(){
  'use strict';
  document.addEventListener('click',async event=>{
    const button=event.target.closest('.codeslot-copy-shortcode');if(!button)return;
    try{
      if(window.navigator.clipboard && window.isSecureContext)await window.navigator.clipboard.writeText(button.dataset.copy);
      else {const input=document.createElement('textarea');input.value=button.dataset.copy;input.style.position='fixed';input.style.opacity='0';document.body.append(input);input.select();const copied=document.execCommand('copy');input.remove();if(!copied)throw new Error('Copy unavailable');}
      button.textContent='Copied';window.setTimeout(()=>{button.textContent='Copy';},1800);
    }catch{button.textContent='Select and copy the shortcode';}
  });
}());

(function(){
  'use strict';
  const header=document.querySelector('.codeslot-header');if(!header)return;
  function moveNotices(){
    let anchor=header;
    header.querySelectorAll('.notice,.updated,.error').forEach(notice=>{anchor.after(notice);anchor=notice;});
  }
  new window.MutationObserver(moveNotices).observe(header,{childList:true,subtree:true});moveNotices();
}());

(function(){
  'use strict';
  document.querySelectorAll('form').forEach(form=>{
    const policy=form.querySelector('[name="snippet_conflicts"]');if(!policy)return;
    const state=form.querySelector('[name="restore_state"]');
    const confirm=form.querySelector('[name="confirm_replace"]');
    const preview=form.querySelector('.codeslot-restore-preview');
    function update(){
      const scopes=Array.from(form.querySelectorAll('[name="scopes[]"]'));
      const selected=!scopes.length || scopes.some(box=>box.value==='snippets' && box.checked);policy.disabled=!selected;
      const replacing=selected && policy.value==='replace';confirm.required=replacing;confirm.disabled=!replacing;confirm.closest('.codeslot-replace-confirm').hidden=!replacing;
      preview.replaceChildren();const title=document.createElement('strong');title.textContent='What this restore will change';preview.append(title);const list=document.createElement('ul');preview.append(list);
      const summary=state.value==='preserve'?'Existing enabled/disabled states will be preserved for replacements; changed enabled code may execute immediately.':'Restored/replaced entries will be disabled.';
      function line(text){const item=document.createElement('li');item.textContent=text;list.append(item);}
      const snippetText=policy.value==='skip'?'Different versions with the same name will be skipped; original code and state will not be updated.':(policy.value==='copy'?'Both versions will be kept; new copies are disabled and originals are not replaced or updated.':'One matching unlocked snippet will be replaced; locked or ambiguous matches will remain unchanged. '+summary);
      if(!scopes.length){const submit=form.querySelector('[type="submit"]');if(submit){if(submit.tagName==='INPUT')submit.value=replacing?'Replace matching snippets':'Import inactive copies';else submit.textContent=replacing?'Replace matching snippets':'Import inactive copies';}line(snippetText);line('Exact duplicates are skipped unchanged. New snippets always start disabled.');return;}
      if(!scopes.some(box=>box.checked))line('No sections selected. Select at least one section to restore. Nothing will be restored until you select a section.');
      scopes.forEach(box=>{
        const choice=form.querySelector('[name="restore_existing['+box.value+']"]');if(choice)choice.disabled=!box.checked;
        if(!box.checked){line(box.value+': Not selected — existing code/settings will not be replaced or updated.');return;}
        if(box.value==='snippets'){line('snippets: '+snippetText+' Exact duplicates remain unchanged.');return;}
        if(choice.value==='skip'){line(box.value+(box.value==='settings'?': Existing preferences will not be replaced or updated.':': Existing entries will remain unchanged; only missing/empty entries can receive backup data, disabled.'));return;}
        line(box.value+': Selected existing content will be replaced by backup content. '+(box.value==='settings'?'Safe Mode remains unchanged; uninstall deletion will be off.':summary));
      });
      line('No new WordPress posts/pages or physical server files will be created or overwritten.');
    }
    form.addEventListener('change',update);update();
  });
}());

(function(){'use strict';document.querySelectorAll('.codeslot-remove-requirement').forEach(button=>button.addEventListener('click',()=>{const row=button.closest('.codeslot-plugin-requirement');row.querySelector('input[type=hidden]').remove();row.querySelector('input[type=checkbox]').checked=false;button.remove();}));}());
(function(){
  'use strict';
  document.querySelectorAll('[data-codeslot-max-bytes]').forEach(field=>{
    function valid(){const value=field.codeslotEditor?field.codeslotEditor.getValue():field.value;return new window.TextEncoder().encode(value).length<=Number(field.dataset.codeslotMaxBytes);}
    function check(){if(!field.codeslotEditor)field.setCustomValidity(valid()?'':'Maximum 1 MiB per code field. Reduce the code before saving.');}
    field.addEventListener('input',check);field.codeslotEditor?.on('change',check);
    field.form?.addEventListener('submit',event=>{if(valid())return;event.preventDefault();check();field.codeslotEditor?.focus();window.alert('Maximum 1 MiB per Header, Body or Footer field. Your edits have not been submitted.');});
  });
}());
