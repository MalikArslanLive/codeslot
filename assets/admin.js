(function () {
  'use strict';
  const types = ['entire_site','frontend','admin','front_page','home','singular','post_type','term','archive','search','404','logged_in','role','url','mobile','shop','product','cart','checkout','account'];
  const phpLocations = ['frontend','admin','everywhere','bootstrap','manual','once'];
  const htmlLocations = ['header','body','footer','before_content','after_content','before_paragraph','after_paragraph','shortcode','manual','woocommerce_before_single_product','woocommerce_after_single_product','woocommerce_before_shop_loop','woocommerce_after_shop_loop'];
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
    Array.from(location.options).forEach(o => { o.disabled = !allowed.includes(o.value); o.hidden = o.disabled; });
    if (!allowed.includes(location.value)) location.value = allowed[0];
    const paragraph = document.getElementById('codeslot-paragraph-field');
    if (paragraph) paragraph.hidden = !['before_paragraph','after_paragraph'].includes(location.value);
    const active = document.querySelector('[name="active"]');
    if (active) { active.disabled = location.value === 'once'; if (active.disabled) active.checked = false; }
    const modes = {php:{name:'application/x-httpd-php',startOpen:true},javascript:'text/javascript',css:'text/css',text:'text/plain'};
    editors.forEach(e => e.codemirror.setOption('mode', modes[type.value] || 'text/html'));
    const wrappers = {javascript:['<script>','</script>'],css:['<style>','</style>'],php:['<?php','']};
    const pair = wrappers[type.value] || ['', ''];
    const open = document.getElementById('codeslot-wrapper-open');
    const close = document.getElementById('codeslot-wrapper-close');
    if (open && close) { open.textContent = pair[0]; close.textContent = pair[1]; open.hidden = !pair[0]; close.hidden = !pair[1]; }
    const help = document.getElementById('codeslot-wrapper-help');
    if (help) help.textContent = pair[0] ? 'Shown tags are supplied automatically. Enter only the code inside them.' : 'HTML accepts complete embeds, including script/style tags.';
    checkWrappers();
  }
  function checkWrappers() {
    const source = document.getElementById('codeslot-source');
    const warning = document.getElementById('codeslot-wrapper-warning');
    const html = document.getElementById('codeslot-use-html');
    if (!source || !warning || !html || !type) return;
    const value = source.codeslotEditor ? source.codeslotEditor.getValue() : source.value;
    const invalid = (type.value === 'javascript' && /<\/?script\b/i.test(value)) || (type.value === 'css' && /<\/?style\b/i.test(value));
    warning.hidden = !invalid; html.hidden = !invalid;
    warning.textContent = invalid ? 'This code already contains tags. Use HTML to keep the complete embed, or remove those tags because the selected code type adds them automatically.' : '';
    source.dataset.wrapperInvalid = invalid ? '1' : '0';
  }
  const source = document.getElementById('codeslot-source');
  if (source) {
    source.addEventListener('input', checkWrappers);
    source.form.addEventListener('submit', event => { if (source.dataset.wrapperInvalid === '1') { event.preventDefault(); if(source.codeslotEditor) source.codeslotEditor.focus(); document.getElementById('codeslot-wrapper-warning').scrollIntoView({block:'center'}); } });
    if (source.codeslotEditor) source.codeslotEditor.on('change', checkWrappers);
  }
  const useHTML = document.getElementById('codeslot-use-html');
  if (useHTML) useHTML.addEventListener('click', () => { type.value = 'html'; setType(); });
  if (type) { type.addEventListener('change', setType); location.addEventListener('change', setType); setType(); }
  const container = document.getElementById('codeslot-conditions');
  const hidden = document.getElementById('codeslot-conditions-json');
  if (!container || !hidden) return;
  let data;
  try { data = JSON.parse(hidden.value); } catch { data = {}; }
  data = {include: data.include || [], exclude: data.exclude || []};
  function sync() { hidden.value = JSON.stringify(data); }
  function button(text, action, parent) {
    const b = document.createElement('button'); b.type='button'; b.className='button'; b.textContent=text;
    b.addEventListener('click', action); parent.appendChild(b);
  }
  function select(values, value, label, change, parent) {
    const el = document.createElement('select'); el.setAttribute('aria-label',label);
    values.forEach(v => { const o=document.createElement('option'); o.value=v; o.textContent=v; el.appendChild(o); });
    el.value=value; el.addEventListener('change', () => { change(el.value); sync(); }); parent.appendChild(el);
  }
  function render() {
    container.replaceChildren();
    ['include','exclude'].forEach(kind => {
      const section=document.createElement('fieldset'); const legend=document.createElement('legend');
      legend.textContent=kind === 'include' ? 'Include if any group matches' : 'Exclude if any group matches'; section.appendChild(legend);
      data[kind].forEach((group, gi) => {
        const box=document.createElement('div'); box.className='codeslot-group';
        group.forEach((rule, ri) => {
          const row=document.createElement('p');
          select(types,rule.key,'Condition',v=>rule.key=v,row);
          select(['is','not'],rule.op || 'is','Operator',v=>rule.op=v,row);
          const value=document.createElement('input'); value.value=rule.value || ''; value.setAttribute('aria-label','Value (ID, role, path or taxonomy:ID)');
          value.addEventListener('input',()=>{rule.value=value.value;sync();}); row.appendChild(value);
          button('Remove rule',()=>{group.splice(ri,1);if(!group.length)data[kind].splice(gi,1);render();},row); box.appendChild(row);
        });
        button('AND rule',()=>{group.push({key:'frontend',op:'is',value:''});render();},box);
        button('Remove group',()=>{data[kind].splice(gi,1);render();},box); section.appendChild(box);
      });
      button('OR group',()=>{data[kind].push([{key:'frontend',op:'is',value:''}]);render();},section); container.appendChild(section);
    });
    sync();
  }
  render();
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
  document.querySelectorAll('.codeslot-remove').forEach(button => button.addEventListener('click', () => {
    if (!window.confirm('Permanently delete "' + button.dataset.name + '"? This cannot be undone.')) return;
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
    if (bulk.elements.bulk.value === 'delete' && !window.confirm('Permanently delete the selected snippets and their saved versions? This cannot be undone.')) event.preventDefault();
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
    try { await window.navigator.clipboard.writeText(textarea.value); button.textContent = 'Copied'; } catch { textarea.focus(); textarea.select(); button.textContent = 'Select and copy the text'; }
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
      const events = card.querySelectorAll('input[name$="[events][]"]:checked').length;
      card.querySelector('.codeslot-pixel-state').textContent = !enabled ? 'Disabled' : (!id ? 'Add your tracking ID' : (!events ? 'Enabled · choose events' : 'Enabled · consent required'));
    }
    card.addEventListener('change', state); card.addEventListener('input', state); state();
  });
  const active = document.querySelector('.codeslot-nav a[aria-current="page"]');
  if (active && window.innerWidth < 783) active.scrollIntoView({block:'nearest',inline:'center'});
}());
