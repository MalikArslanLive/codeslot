(function () {
  'use strict';
  const container=document.getElementById('codeslot-conditions');
  const hidden=document.getElementById('codeslot-conditions-json');
  if(!container || !hidden || typeof codeslotConditions==='undefined')return;
  const config=codeslotConditions;
  let data;
  try{data=JSON.parse(hidden.value);}catch{data={};}
  data={include:data.include || [],exclude:data.exclude || []};
  let sequence=0;
  function sync(){hidden.value=JSON.stringify(data);}
  function button(text,parent,action){const el=document.createElement('button');el.type='button';el.className='button';el.textContent=text;el.addEventListener('click',action);parent.append(el);return el;}
  async function lookup(key,query,value,signal){
    const url=new window.URL(config.url);url.search=new window.URLSearchParams({action:'codeslot_conditions',nonce:config.nonce,key,q:query,value});
    const response=await window.fetch(url,{signal,credentials:'same-origin'});
    const result=await response.json();if(!response.ok || !result.success)throw new Error(result.data?.message || 'Search failed');return result.data;
  }
  function searchField(row,rule,helpId){
    const wrap=document.createElement('div');wrap.className='codeslot-rule-search';row.append(wrap);
    const input=document.createElement('input');input.type='search';input.autocomplete='off';input.placeholder='Type at least 2 characters';input.setAttribute('aria-label','Search '+config.rules[rule.key].label.toLowerCase());input.setAttribute('role','combobox');input.setAttribute('aria-autocomplete','list');input.setAttribute('aria-expanded','false');input.setAttribute('aria-describedby',helpId);input.required=true;
    const list=document.createElement('div');list.id='codeslot-results-'+(++sequence);list.className='codeslot-rule-results';list.setAttribute('role','listbox');list.setAttribute('aria-label','Matching results');list.hidden=true;input.setAttribute('aria-controls',list.id);
    const status=document.createElement('span');status.className='description codeslot-search-status';status.setAttribute('role','status');
    const full=document.createElement('span');full.className='codeslot-selected-full';
    const clear=button('Clear selection',wrap,()=>{++revision;controller?.abort();window.clearTimeout(timer);selected=null;input.readOnly=false;input.value='';rule.value='';full.textContent='';input.title='';results=[];list.replaceChildren();status.textContent='Type at least 2 characters to search by name.';input.setCustomValidity('');sync();close();input.focus();});clear.classList.add('codeslot-clear-condition');
    wrap.prepend(input);wrap.append(list,status,full);
    let selected=null,timer,controller,revision=0,results=[],index=-1;
    function close(){list.hidden=true;input.setAttribute('aria-expanded','false');input.removeAttribute('aria-activedescendant');index=-1;}
    function choose(item){selected=item;rule.value=item.value;input.value=item.label;input.title=item.label+' — '+item.detail;full.textContent=input.title;input.setCustomValidity('');status.textContent='Selected: '+item.detail;close();sync();}
    function active(){Array.from(list.children).forEach((el,i)=>el.setAttribute('aria-selected',String(i===index)));if(index>=0){input.setAttribute('aria-activedescendant',list.children[index].id);list.children[index].scrollIntoView({block:'nearest'});}else input.removeAttribute('aria-activedescendant');}
    function show(items){results=items;index=-1;list.replaceChildren();for(const [i,item] of items.entries()){
      const option=document.createElement('button');option.type='button';option.tabIndex=-1;option.id=list.id+'-'+i;option.setAttribute('role','option');option.setAttribute('aria-selected','false');option.title=item.label+' — '+item.detail;
      const title=document.createElement('span');title.className='codeslot-result-title';title.textContent=item.label;const detail=document.createElement('small');detail.textContent=item.detail;option.append(title,detail);option.addEventListener('mousedown',event=>event.preventDefault());option.addEventListener('click',()=>{choose(item);input.focus();});list.append(option);
    }list.hidden=!items.length;input.setAttribute('aria-expanded',String(!!items.length));status.textContent=items.length?'Select a result. Up to 20 matches shown; type more to narrow the search.':'No matching items. Check the name or activate the plugin that provides this content.';}
    input.addEventListener('input',()=>{
      selected=null;rule.value='';full.textContent='';input.title='';sync();window.clearTimeout(timer);controller?.abort();const current=++revision;close();results=[];list.replaceChildren();input.setCustomValidity('Select a matching result from the search list.');
      const query=input.value.trim();if(query.length<2){status.textContent='Type at least 2 characters to search by name.';return;}
      status.textContent='Searching…';timer=window.setTimeout(async()=>{controller=new window.AbortController();try{const items=await lookup(rule.key,query,'',controller.signal);if(current===revision && wrap.isConnected)show(items);}catch(error){if(error.name!=='AbortError' && current===revision)status.textContent='Could not load results. Check your connection or reload this page and try again.';}},250);
    });
    input.addEventListener('keydown',event=>{
      if(event.key==='Escape'){++revision;controller?.abort();close();return;}
      if(event.key==='Tab'){close();return;}
      if((event.key==='ArrowDown' || event.key==='ArrowUp') && results.length){event.preventDefault();list.hidden=false;input.setAttribute('aria-expanded','true');index=event.key==='ArrowDown'?(index+1)%results.length:(index<=0?results.length-1:index-1);active();}
      if(event.key==='Enter' && !list.hidden){event.preventDefault();if(index>=0)choose(results[index]);}
    });
    input.addEventListener('blur',()=>window.setTimeout(()=>{if(!wrap.contains(document.activeElement))close();},150));
    if(rule.value){const value=rule.value;const current=++revision;controller=new window.AbortController();input.value='Loading selected item…';status.textContent='Resolving saved selection…';input.readOnly=true;
      lookup(rule.key,'',value,controller.signal).then(items=>{if(current!==revision || !wrap.isConnected)return;if(items.length)choose(items[0]);else{input.value='Unavailable selection: '+value;input.title=input.value;status.textContent='Execution paused: this item/type/role is unavailable. Restore it or clear/select another and save; its stored identity is retained.';input.setCustomValidity('');}}).catch(error=>{if(current!==revision || error.name==='AbortError')return;input.value='Saved selection: '+value;status.textContent='Could not load its name. Reload to retry; its stored identity is retained.';}).finally(()=>{if(current===revision)input.readOnly=false;});
    }
    input.addEventListener('focus',()=>{if(selected)full.textContent=selected.label+' — '+selected.detail;});
  }
  function render(){
    container.replaceChildren();
    for(const kind of ['include','exclude']){
      const section=document.createElement('fieldset');const legend=document.createElement('legend');legend.textContent=kind==='include'?'Include if any group matches':'Exclude if any group matches';section.append(legend);
      data[kind].forEach((group,gi)=>{
        const box=document.createElement('div');box.className='codeslot-group';
        group.forEach((rule,ri)=>{
          const row=document.createElement('div');row.className='codeslot-rule-row';box.append(row);
          const condition=document.createElement('select');condition.setAttribute('aria-label','Condition');
          for(const [key,definition] of Object.entries(config.rules)){const option=document.createElement('option');option.value=key;option.textContent=definition.label;option.disabled=!!definition.woo && !config.commerce;if(option.disabled)option.textContent+=' — requires WooCommerce';condition.append(option);}condition.value=rule.key;
          condition.addEventListener('change',()=>{rule.key=condition.value;rule.value='';render();});row.append(condition);
          const operator=document.createElement('select');operator.setAttribute('aria-label','Operator');for(const [value,label] of [['is','is'],['not','is not']]){const option=document.createElement('option');option.value=value;option.textContent=label;operator.append(option);}operator.value=rule.op || 'is';operator.addEventListener('change',()=>{rule.op=operator.value;sync();});row.append(operator);
          const definition=config.rules[rule.key];const help=document.createElement('p');help.id='codeslot-rule-help-'+(++sequence);help.className='description codeslot-rule-help';help.textContent=definition?.help || 'Choose a condition.';
          if(definition?.kind==='search')searchField(row,rule,help.id);
          else if(definition?.kind==='path'){const input=document.createElement('input');input.setAttribute('aria-label','URL path');input.setAttribute('aria-describedby',help.id);input.placeholder='Example URL path: /your-page/';input.required=true;input.maxLength=2048;input.value=rule.value || '';input.addEventListener('input',()=>{rule.value=input.value;input.setCustomValidity(/^\/(?!\/)[^?#\s]*$/.test(input.value)?'':'Enter a path starting with /, without a domain, query string or fragment.');sync();});row.append(input);}
          else rule.value='';
          button('Remove rule',row,()=>{group.splice(ri,1);if(!group.length)data[kind].splice(gi,1);render();});box.append(help);
        });
        button('AND rule',box,()=>{group.push({key:'frontend',op:'is',value:''});render();});button('Remove group',box,()=>{data[kind].splice(gi,1);render();});section.append(box);
      });
      button('OR group',section,()=>{data[kind].push([{key:'frontend',op:'is',value:''}]);render();});container.append(section);
    }
    if(!config.commerce){const note=document.createElement('p');note.className='description codeslot-commerce-note';note.textContent='Shop, Cart, Checkout and My Account conditions require WooCommerce. Install and activate WooCommerce to use them. Any saved unavailable condition pauses the whole snippet, including Exclude and OR groups.';container.append(note);}
    sync();
  }
  render();
}());
