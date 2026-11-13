(function () {
  'use strict';
  const types = ['entire_site','frontend','admin','front_page','home','singular','post_type','term','archive','search','404','logged_in','role','url','mobile','shop','product','cart','checkout','account'];
  const phpLocations = ['frontend','admin','everywhere','manual','once'];
  const htmlLocations = ['header','body','footer','before_content','after_content','before_paragraph','after_paragraph','shortcode','manual','woocommerce_before_single_product','woocommerce_after_single_product','woocommerce_before_shop_loop','woocommerce_after_shop_loop'];
  const editors = [];
  document.querySelectorAll('.codeslot-code').forEach(function (el) {
    if (window.wp && window.wp.codeEditor) {
      const editor = window.wp.codeEditor.initialize(el, {codemirror: {mode: el.dataset.codeslotMode, lineNumbers:true, indentUnit:4}});
      editors.push(editor);
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
    const modes = {php:'text/x-php',javascript:'text/javascript',css:'text/css',text:'text/plain'};
    editors.forEach(e => e.codemirror.setOption('mode', modes[type.value] || 'text/html'));
  }
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
