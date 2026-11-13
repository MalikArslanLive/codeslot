(function () {
  'use strict';
  const types = ['entire_site','frontend','admin','front_page','home','singular','post_type','term','archive','search','404','logged_in','role','url','mobile','shop','product','cart','checkout','account'];
  const phpLocations = ['frontend','admin','everywhere','manual'];
  const htmlLocations = ['header','body','footer','before_content','after_content','shortcode','manual','woocommerce_before_single_product','woocommerce_after_single_product','woocommerce_before_shop_loop','woocommerce_after_shop_loop'];
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
    const modes = {php:'text/x-php',javascript:'text/javascript',css:'text/css',text:'text/plain'};
    editors.forEach(e => e.codemirror.setOption('mode', modes[type.value] || 'text/html'));
  }
  if (type) { type.addEventListener('change', setType); setType(); }
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
