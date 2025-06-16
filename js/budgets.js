async function getCats(){const r=await fetch('../api/index.php/categories/list',{credentials:'include'});return (await r.json()).categories||[];}
async function getBudgets(){
  const cid = cardContext.get();
  const url = cid ? `../api/index.php/budgets/list?card_id=${cid}` : '../api/index.php/budgets/list';
  const r=await fetch(url,{credentials:'include'});
  return (await r.json()).budgets||[];
}
let budgetCharts=new Map();
function makeChartCanvas(id){
  const c=document.createElement('canvas');
  c.className = 'w-100'; 
  c.id = 'budChart_' + id;
  return c;
}
function drawBudgetChart(b){const ctx=document.getElementById('budChart_'+b.id).getContext('2d');const spent=parseFloat(b.spent)||0,limit=parseFloat(b.limit_amount)||0;const remaining=Math.max(limit-spent,0);return new Chart(ctx,{
    type:'doughnut',
    data:{
      labels:['Izlietots','Atlikums'],
      datasets:[{
        data:[spent,remaining],
        backgroundColor:[chartPalette.teal, chartPalette.green]
      }]
    },
    options: buildChartOptions({
      legend:{display:false},
      extra:{cutout:'60%',aspectRatio:1}
    })
  });}
function fillTable(rows){const tb=document.querySelector('#bTable tbody');const chartsRow=document.getElementById('budChartsRow');tb.innerHTML='';chartsRow.innerHTML='';budgetCharts.forEach(ch=>ch.destroy());budgetCharts.clear();
  rows.forEach(r=>{ 
    const tr=document.createElement('tr');tr.innerHTML=`<td>${r.label}</td><td>${r.limit_amount}</td><td>${r.spent}</td><td>${r.remaining}</td><td><button class='btn btn-sm btn-danger' data-id='${r.id}'>Dzēst</button></td>`;tb.appendChild(tr);
    const col=document.createElement('div');col.className='col';
    const card=document.createElement('div');card.className='chart-box p-3 bg-white shadow-sm border rounded text-center';
    card.innerHTML=`<h6 class='mb-3'>${r.label}</h6>`;
    const canvas=makeChartCanvas(r.id);card.appendChild(canvas);col.appendChild(card);chartsRow.appendChild(col);
    const chart=drawBudgetChart(r);budgetCharts.set(r.id,chart);
  });}
async function load(){const active=cardContext.get();const [cats,allBuds]=await Promise.all([getCats(),getBudgets()]);
  const buds = active ? allBuds.filter(b=>String(b.card_id)===String(active)) : allBuds;const sel=document.querySelector('#bForm select[name=category_id]');sel.innerHTML=cats.filter(c=>c.type==='expense').map(c=>`<option value='${c.id}'>${c.label}</option>`).join('');fillTable(buds);}load();

// set default month when opening modal
const bModalEl=document.getElementById('bModal');
bModalEl.addEventListener('show.bs.modal',()=>{
  const monthInput=document.querySelector('#bForm input[name=period]');
  if(monthInput && !monthInput.value){monthInput.value=new Date().toISOString().slice(0,7);} 
});

document.getElementById('bForm').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);
  const cid=cardContext.get();
  if(cid) fd.append('card_id',cid);
  const r=await fetch('../api/index.php/budgets/create',{credentials:'include',method:'POST',body:fd});const d=await r.json();if(d.success){bootstrap.Modal.getInstance(document.getElementById('bModal')).hide();load();}else alert(d.message||'Kļūda');});

document.getElementById('bTable').addEventListener('click',async e=>{if(e.target.matches('button[data-id]')){const id=e.target.dataset.id;if(confirm('Dzēst budžetu?')){const p=new URLSearchParams({id});
    const cid=cardContext.get();
    if(cid) p.append('card_id',cid);
    await fetch('../api/index.php/budgets/delete',{credentials:'include',method:'POST',body:p});load();}}});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});
