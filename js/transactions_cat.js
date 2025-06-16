(function(){
 const form=document.getElementById('catForm');
 if(!form) return;
 form.addEventListener('submit',async e=>{
   e.preventDefault();
   const fd=new FormData(form);
   const cid=cardContext.get();
   if(cid) fd.append('card_id',cid);
   try{
     const r=await fetch('../api/index.php/categories/create',{method:'POST',body:fd,credentials:'include'});
     const d=await r.json();
     if(d.success){
      
       const cats=await (await fetch('../api/index.php/categories/list'+(cid?`?card_id=${cid}`:''),{credentials:'include'})).json();
       const sel=document.querySelector('#txForm select[name=category_id]');
       sel.innerHTML=cats.categories.map(c=>`<option value='${c.id}'>${c.type==='expense'?'🟥':'🟩'} ${c.label}</option>`).join('');
       
       sel.value=d.id||'';
       bootstrap.Modal.getInstance(document.getElementById('catModal')).hide();
       form.reset();
     }else alert(d.message||'Kļūda');
   }catch(err){alert('Kļūda saglabājot kategoriju');console.error(err);}  
 });
})();
