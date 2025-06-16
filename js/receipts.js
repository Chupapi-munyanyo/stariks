const periodSel=document.getElementById('periodSel');
const budgetsWrap=document.getElementById('budgetsWrap');
const typeSel=document.getElementById('typeSel');
const budHeader=document.getElementById('budHeader');
const invHeader=document.getElementById('invHeader');
const invTBody=document.querySelector('#invTable tbody');
const transTBody=document.querySelector('#transTable tbody');
const transHeader=document.getElementById('transHeader');


async function load(){
  const cid = cardContext.get();
  const chosen=typeSel.value;
  const p=periodSel.value;

  const res=await fetch(`../api/index.php/receipts/data?period=${p}${cid?`&card_id=${cid}`:''}`,{credentials:'include'});
  const data=await res.json();
  if(!data.success){alert(data.message||'Kļūda');return;}
  // visibility helpers
  const showBud = (chosen==='budgets'||chosen==='all');
  const showInv = (chosen==='investments'||chosen==='all');
  const showTx  = (chosen==='transactions'||chosen==='all');

  // Budgets
  if(showBud){
    renderBudgets(data.budgets);
    budHeader.style.display='';
    budgetsWrap.style.display='';
  }else{
    budHeader.style.display='none';
    budgetsWrap.style.display='none';
  }
  // Investments
  if(showInv){
    renderInvestments(data.investments);
    invHeader.style.display='';
    document.getElementById('invTable').style.display='';
  }else{
    invHeader.style.display='none';
    document.getElementById('invTable').style.display='none';
  }
  // Transactions
  if(showTx){
    renderTransactions(data.transactions);
    transHeader.style.display='';
    document.getElementById('transTable').style.display='';
  }else{
    transHeader.style.display='none';
    document.getElementById('transTable').style.display='none';
  }
  
  

  }

function renderBudgets(buds){
  budgetsWrap.innerHTML='';
  buds.forEach(b=>{
    const card=document.createElement('div');card.className='card mb-3';
    const over=b.remaining<0;
    card.innerHTML=`<div class="card-header d-flex justify-content-between"><strong>${b.label}</strong><span>${(b.remaining).toFixed(2)} €</span></div>`;
    const body=document.createElement('div');body.className='card-body p-0';
    const tbl=document.createElement('table');tbl.className='table mb-0';
    tbl.innerHTML='<thead><tr><th>Datums</th><th>Apraksts</th><th>Summa (€)</th></tr></thead>';
    const tb=document.createElement('tbody');
    b.transactions.forEach(t=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${t.happened_on}</td><td>${t.description||''}</td><td>${t.amount}</td>`;
      tb.appendChild(tr);
    });
    tbl.appendChild(tb);body.appendChild(tbl);card.appendChild(body);
    if(over) card.classList.add('border-danger');
    budgetsWrap.appendChild(card);
  });
}

function renderTransactions(list){
  transTBody.innerHTML='';
  list.forEach(t=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`<td>${t.happened_on}</td><td>${t.category}</td><td>${t.amount}</td><td>${t.note||''}</td>`;
    transTBody.appendChild(tr);
  });
}

function renderInvestments(inv){
  invTBody.innerHTML='';
  inv.forEach(i=>{
    const tr=document.createElement('tr');
    const diff=parseFloat(i.diff);
    tr.innerHTML=`<td>${i.ticker}</td><td>${i.name}</td><td>${i.quantity}</td><td>${i.invested_amount}</td><td>${i.current_value}</td><td class="${diff<0?'text-danger':'text-success'}">${diff.toFixed(2)}</td>`;
    invTBody.appendChild(tr);
  });
}

periodSel.addEventListener('change',load);
typeSel.addEventListener('change',load);
document.getElementById('refreshBtn').addEventListener('click',load);

document.getElementById('exportXls').addEventListener('click',()=>{const cid=cardContext.get();
window.open(`../api/index.php/receipts/export?period=${periodSel.value}&type=xlsx&report=${typeSel.value}${cid?`&card_id=${cid}`:''}`, '_blank');});
document.getElementById('exportDoc').addEventListener('click',()=>{const cid=cardContext.get();
window.open(`../api/index.php/receipts/export?period=${periodSel.value}&type=doc&report=${typeSel.value}${cid?`&card_id=${cid}`:''}`, '_blank');});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});