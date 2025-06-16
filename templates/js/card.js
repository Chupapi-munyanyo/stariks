(function(){
  const params=new URLSearchParams(location.search);
  const id=params.get('id');
  if(!id){location.href='cards.html';return;}

  async function load(){
    try{
      const r=await fetch(`../api/index.php/cards/view?id=${id}`,{credentials:'include'});
      const d=await r.json();
      if(!d.success) throw new Error(d.message||'Kļūda');
      const c=d.card;
      document.getElementById('bankName').textContent=c.bank_name;
      document.getElementById('last4').textContent=c.last4;
      document.getElementById('balance').textContent=c.balance_amount;
      document.getElementById('cardInfo').hidden=false;
      cardContext.set(c.id);
    }catch(err){
      const box=document.getElementById('errBox');
      box.textContent=err.message;
      box.classList.remove('d-none');
    }
  }
  load();
})();
