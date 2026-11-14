(function () {
  'use strict';
  const element=document.getElementById('codeslot-selector-config');
  if(!element)return;
  const rows=JSON.parse(element.textContent);
  function insert() {
    rows.forEach(row=>{
      let targets;try{targets=document.querySelectorAll(row.selector);}catch{return;}
      targets.forEach(target=>{
        const template=document.createElement('template');template.innerHTML=row.html;
        // Scripts inserted through a template are inert; recreate them deliberately.
        template.content.querySelectorAll('script').forEach(original=>{
          const script=document.createElement('script');
          for(const attribute of original.attributes)script.setAttribute(attribute.name,attribute.value);
          script.textContent=original.textContent;original.replaceWith(script);
        });
        const fragment=template.content;
        if(row.position==='before')target.before(fragment);
        else if(row.position==='after')target.after(fragment);
        else if(row.position==='prepend')target.prepend(fragment);
        else if(row.position==='append')target.append(fragment);
        else target.replaceWith(fragment);
      });
    });
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',insert,{once:true});else insert();
}());
