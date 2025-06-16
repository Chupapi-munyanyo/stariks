async function list(){const r=await fetch('../api/index.php/cards/list',{credentials:'include'});return (await r.json()).cards||[];}
function fill(rows){
  const tb=document.querySelector('#cTable tbody');
  tb.innerHTML='';
  rows.forEach(c=>{
        const activeId=cardContext.get();
    const badge = c.id==activeId ? " <span class='badge bg-success'>Aktīva</span>" : '';
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><a href='card.html?id=${c.id}'>${c.bank_name}</a>${badge}</td><td>${c.last4}</td><td>${c.balance_amount}</td><td><button class='btn btn-sm btn-secondary' data-change='${c.id}'>Mainīt</button> <button class='btn btn-sm btn-danger' data-id='${c.id}'>Dzēst</button></td>`;
    tb.appendChild(tr);
  });
  const sel=document.querySelector('#lForm select[name=card_id]');
  if(sel){
    sel.innerHTML = rows.map(c=>`<option value='${c.id}'>${c.bank_name} ••••${c.last4}</option>`).join('');
  }
}

async function listLoans(){const r=await fetch('../api/index.php/cardloans/list',{credentials:'include'});return (await r.json()).loans||[];}
let loanCharts=new Map();
function makeLoanCanvas(id){const c=document.createElement('canvas');c.style.width='300px';c.style.height='220px';c.id='loanChart_'+id;return c;}
function drawLoanChart(l){
  const ctx = document.getElementById('loanChart_'+l.id).getContext('2d');
  const paid   = parseFloat(l.paid_off_amount) || 0;
  const total  = parseFloat(l.amount) || 0;
  const remain = Math.max(total - paid, 0);
  return new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Aizdevums'],
      datasets: [
        { label: 'Samaksāts', data: [paid], backgroundColor: chartPalette.teal },
        { label: 'Atlikums',  data: [remain], backgroundColor: chartPalette.green }
      ]
    },
    options: {
      indexAxis: 'y',
      plugins: { legend: { display: false } },
      scales: {
        x: { stacked: true, max: total },
        y: { stacked: true }
      }
    }
  });
}
function fillLoans(rows){const tb=document.querySelector('#lTable tbody');const chartsRow=document.getElementById('loanChartsRow');tb.innerHTML='';chartsRow.innerHTML='';loanCharts.forEach(c=>c.destroy());loanCharts.clear();rows.forEach(l=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${l.bank_name} ••••${l.last4}</td><td>${l.description}</td><td>${l.amount}</td><td>${l.monthly_payment}</td><td>${l.paid_off_amount}</td><td>${l.start_date} – ${l.end_date}</td><td><button class='btn btn-sm btn-danger' data-id='${l.id}'>Dzēst</button></td>`;tb.appendChild(tr);
  const col=document.createElement('div');col.className='col';const card=document.createElement('div');card.className='p-3 bg-white shadow-sm border rounded';card.innerHTML=`<h6 class='text-center mb-2'>${l.description}</h6>`;const canvas=makeLoanCanvas(l.id);card.appendChild(canvas);col.appendChild(card);chartsRow.appendChild(col);const chart=drawLoanChart(l);loanCharts.set(l.id,chart);});}
async function load(){const [cards,loans]=await Promise.all([list(),listLoans()]);fill(cards);fillLoans(loans);}load();

document.getElementById('cForm').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);const r=await fetch('../api/index.php/cards/create',{credentials:'include',method:'POST',body:fd});const d=await r.json();if(d.success){bootstrap.Modal.getInstance(document.getElementById('cModal')).hide();e.target.reset();load();}else alert(d.message||'Kļūda');});

document.getElementById('cTable').addEventListener('click',async e=>{
  if(e.target.matches('button[data-change]')){
    const id=e.target.dataset.change;
    location.href=`card.html?id=${id}`;
  }
  if(e.target.matches('button[data-id]')){const id=e.target.dataset.id;if(confirm('Dzēst karti?')){await fetch('../api/index.php/cards/delete',{credentials:'include',method:'POST',body:new URLSearchParams({id})});load();}}});

document.getElementById('lForm').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);
  ['amount','monthly_payment','paid_off_amount'].forEach(k=>{
    const v=parseFloat(fd.get(k)||0).toFixed(2);
    fd.set(k,v);
  });
  const r=await fetch('../api/index.php/cardloans/create',{credentials:'include',method:'POST',body:fd});const d=await r.json();if(d.success){bootstrap.Modal.getInstance(document.getElementById('lModal')).hide();e.target.reset();load();}else alert(d.message||'Kļūda');});

document.getElementById('lTable').addEventListener('click',async e=>{if(e.target.matches('button[data-id]')){const id=e.target.dataset.id;if(confirm('Dzēst aizdevumu?')){await fetch('../api/index.php/cardloans/delete',{credentials:'include',method:'POST',body:new URLSearchParams({id})});load();}}});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});

const importForm=document.getElementById('importForm');
if(importForm){
  importForm.addEventListener('submit',async e=>{
    e.preventDefault();
    const fileInput=importForm.querySelector('input[type=file]');
    if(!fileInput.files.length){alert('Izvēlieties Excel failu');return;}
    const fd=new FormData();
    fd.append('file',fileInput.files[0]);
    const cid=cardContext.get();
    if(cid) fd.append('card_id',cid);
    const r=await fetch('../api/index.php/import/excel',{method:'POST',body:fd,credentials:'include'});
    const d=await r.json();
    if(d.success){bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();location.reload();}
    else alert(d.message||'Kļūda importējot');
  });
}
