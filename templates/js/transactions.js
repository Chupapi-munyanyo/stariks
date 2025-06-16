async function fetchCategories(){const r=await fetch('../api/index.php/categories/list',{credentials:'include'});return (await r.json()).categories||[];}async function fetchTx(){
  const cid=cardContext.get();
  const url=cid?`../api/index.php/transactions/list?card_id=${cid}`:'../api/index.php/transactions/list';
  const r=await fetch(url,{credentials:'include'});
  return (await r.json()).transactions||[];
}
function fillTable(rows){const tb=document.querySelector('#txTable tbody');tb.innerHTML='';rows.forEach(r=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${r.happened_on}</td><td>${r.label}</td><td>${r.type}</td><td>${r.amount}</td><td><button class='btn btn-sm btn-danger' data-id='${r.id}'>Dzēst</button></td>`;tb.appendChild(tr);});}
let expensesChart,incomeChart;
function drawPie(ctx, labels, data) {
  const colors = chartPalette.array();
  return new Chart(ctx, {
    type: 'pie',
    data: {
      labels,
      datasets: [{ data, backgroundColor: colors.slice(0, labels.length) }]
    },
    options: buildChartOptions({ legend: { position: 'bottom' } })
  });
}
function updateCharts(_unused,transactions){const expMap=new Map(),incMap=new Map();transactions.forEach(t=>{const m=(t.type==='expense'?expMap:incMap);m.set(t.label,(m.get(t.label)||0)+parseFloat(t.amount));});
  const expLabels=[...expMap.keys()],expData=[...expMap.values()],incLabels=[...incMap.keys()],incData=[...incMap.values()];
  
  if(expLabels.length){
    if(!expensesChart) expensesChart = drawPie(document.getElementById('expensesChart'), expLabels, expData);
    else if (expData.length) {expensesChart.data.labels = expLabels;
      expensesChart.data.datasets[0].data = expData;
      expensesChart.data.datasets[0].backgroundColor = chartPalette.array().slice(0, expLabels.length);
      expensesChart.update();}
  }
  if(incLabels.length){
    if(!incomeChart) incomeChart = drawPie(document.getElementById('incomeChart'), incLabels, incData);
    else if (incData.length) {
      incomeChart.data.labels = incLabels;
      incomeChart.data.datasets[0].data = incData;
      incomeChart.data.datasets[0].backgroundColor = chartPalette.array().slice(0, incLabels.length);
      incomeChart.update();
    }
    else {
      incomeChart.data.labels = incLabels;
      incomeChart.data.datasets[0].data = incData;
      incomeChart.data.datasets[0].backgroundColor = chartPalette.array().slice(0, incLabels.length);
      incomeChart.update();
    }
  }}

async function load(){const cid=cardContext.get();const [cats,allTx]=await Promise.all([fetchCategories(),fetchTx()]);
  const tx = cid ? allTx.filter(t=>String(t.card_id)===String(cid)) : allTx;
  const sel=document.querySelector('select[name=category_id]');sel.innerHTML=cats.map(c=>`<option value='${c.id}'>${c.type==='expense'?'🟥':'🟩'} ${c.label}</option>`).join('');fillTable(tx);updateCharts(cats,tx);}load();

const txForm=document.getElementById('txForm');

txForm.addEventListener('submit',async e=>{
  e.preventDefault();
  if(!validateForm(txForm, {
    happened_on: v=>!!v,
    category_id: v=>!!v,
    amount: v=>parseFloat(v)>0
  })) return;
  const fd=new FormData(txForm);
  const cid=cardContext.get();
  if(cid) fd.append('card_id',cid);const r=await fetch('../api/index.php/transactions/create',{method:'POST',body:fd,credentials:'include'});const d=await r.json();if(d.success){bootstrap.Modal.getInstance(document.getElementById('txModal')).hide();load();}else alert(d.message||'Kļūda');});

document.getElementById('txTable').addEventListener('click',async e=>{if(e.target.matches('button[data-id]')){const id=e.target.dataset.id;if(confirm('Dzēst?')){await fetch('../api/index.php/transactions/delete',{method:'POST',body:new URLSearchParams({id}),credentials:'include'});load();}}});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});
