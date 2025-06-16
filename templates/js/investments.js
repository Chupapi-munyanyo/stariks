function renderTradingView(symbol){
  const infoWrapper=document.querySelector('#symbol-info .tradingview-widget-container');
  const chartWrapper=document.querySelector('#advanced-chart .tradingview-widget-container');
  if(!infoWrapper||!chartWrapper) return;
  
  infoWrapper.innerHTML='<div class="tradingview-widget-container__widget"></div>';
  chartWrapper.innerHTML='<div class="tradingview-widget-container__widget" style="width:100%;height:100%"></div>';
  
  const infoScript=document.createElement('script');
  infoScript.type='text/javascript';
  infoScript.src='https://s3.tradingview.com/external-embedding/embed-widget-symbol-info.js';
  infoScript.async=true;
  infoScript.innerHTML=JSON.stringify({symbol:symbol,width:"100%",locale:"en",colorTheme:"light",isTransparent:true});
  infoWrapper.appendChild(infoScript);
  
  const chartScript=document.createElement('script');
  chartScript.type='text/javascript';
  chartScript.src='https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
  chartScript.async=true;
  chartScript.innerHTML=JSON.stringify({autosize:true,symbol:symbol,interval:"D",timezone:"Etc/UTC",theme:"light",style:"1",locale:"en",allow_symbol_change:true,calendar:false,support_host:"https://www.tradingview.com"});
  chartWrapper.appendChild(chartScript);
}

renderTradingView('NASDAQ:AAPL');

async function list(){
  const cid=cardContext.get();
  const url=cid?`../api/index.php/investments/list?card_id=${cid}`:'../api/index.php/investments/list';
  const r=await fetch(url,{credentials:'include'});
  return (await r.json()).investments||[];
}
function fill(rows){const tb=document.querySelector('#iTable tbody');tb.innerHTML='';rows.forEach(i=>{const tr=document.createElement('tr');const cur=parseFloat(i.current_value);const prof=parseFloat(i.profit);tr.setAttribute('data-ticker',i.ticker);
    tr.innerHTML=`<td>${i.ticker}</td><td>${i.name}</td><td>${i.type}</td><td>${i.quantity}</td><td>${parseFloat(i.invested_amount).toFixed(2)}</td><td>${isNaN(cur)?i.current_value:cur.toFixed(2)}</td><td>${isNaN(prof)?i.profit:prof.toFixed(2)}</td><td><button class='btn btn-sm btn-danger' data-id='${i.id}'>Dzēst</button></td>`;tb.appendChild(tr);});}

let invChart;
let currentFrame='week';
const timeGrp=document.getElementById('timeframeGrp');
if(timeGrp){
  timeGrp.addEventListener('click',e=>{
    if(e.target.dataset.frame){
      [...timeGrp.children].forEach(b=>b.classList.remove('active'));
      e.target.classList.add('active');
      currentFrame=e.target.dataset.frame;
      refreshChart();
    }
  });
}

let allRows=[];

function drawInvestmentChart(labels, datasets, title){
  const ctx=document.getElementById('investmentsChart').getContext('2d');
  if(invChart){invChart.destroy();}
  invChart=new Chart(ctx,{
    type:'bar',
    data:{labels,datasets},
    options:{
      responsive:true,
      interaction:{mode:'index',intersect:false},
      plugins:{legend:{position:'top'},title:{display:true,text:title}},
      scales:{
        y:{type:'linear',position:'left',title:{display:true,text:'Daudzums'}},
        y1:{type:'linear',position:'right',grid:{drawOnChartArea:false},title:{display:true,text:'€'}}
      }
    }
  });
}

function refreshChart(){
  const rows=allRows;
  const now=new Date();
  if(currentFrame==='week'||currentFrame==='week'){
    
    const labels=rows.map(r=>r.ticker.toUpperCase());
    const invested=rows.map(r=>parseFloat(r.invested_amount));
    const qty=rows.map(r=>parseFloat(r.quantity));
    drawInvestmentChart(labels,[
      {label:'Daudzums',data:qty,backgroundColor:'rgba(30,144,255,0.7)',yAxisID:'y'},
      {label:'Ieguldīts (€)',data:invested,backgroundColor:'rgba(34,139,34,0.7)',yAxisID:'y1'}
    ],'Bilances Vēsture pēc Ticker');
  }else if(currentFrame==='month'){
    
    const monthRows=rows.filter(r=>{
      const d=new Date(r.date);
      return d.getFullYear()===now.getFullYear()&&d.getMonth()===now.getMonth();
    });
    const byTicker={};
    monthRows.forEach(r=>{byTicker[r.ticker]=(byTicker[r.ticker]||0)+parseFloat(r.invested_amount);});
    const labels=Object.keys(byTicker);
    const invested=labels.map(l=>byTicker[l]);
    drawInvestmentChart(labels,[{label:'Ieguldīts šomēnes (€)',data:invested,backgroundColor:'rgba(34,139,34,0.7)',yAxisID:'y'}],'Šī mēneša ieguldījumi');
  }else if(currentFrame==='year'){
    const months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const sums=new Array(12).fill(0);
    rows.forEach(r=>{const d=new Date(r.date);if(d.getFullYear()===now.getFullYear()){sums[d.getMonth()]+=parseFloat(r.invested_amount);} });
    drawInvestmentChart(months,[{label:`${now.getFullYear()} ieguldīts (€)`,data:sums,backgroundColor:'rgba(34,139,34,0.7)',yAxisID:'y'}],'Ieguldījumi pa mēnešiem');
  }
}

async function load(){const cid=cardContext.get();allRows=await list();if(cid) allRows=allRows.filter(i=>String(i.card_id)===String(cid));fill(allRows);refreshChart();}
load();

const iForm=document.getElementById('iForm');

const investedInput = iForm.querySelector('input[name="invested_amount"]');
const qtyInput = iForm.querySelector('input[name="quantity"]');
const priceInput = iForm.querySelector('input[name="current_value"]');
const calcInfo = document.getElementById('calcInfo');
function updateSummary(){
  if(calcInfo){
    const qty=qtyInput.value||'?';
    const price=priceInput.value||'?';
    const inv=investedInput.value||'?';
    calcInfo.textContent=`Pirkums: ${qty} gab. × €${price} = €${inv}`;
  }
}

qtyInput?.addEventListener('input',updateSummary);

investedInput?.addEventListener('input',updateSummary);

priceInput?.addEventListener('input',updateSummary);


const tickerInput=iForm.querySelector('input[name="ticker"]');
if(tickerInput){
  tickerInput.addEventListener('blur',async ()=>{
    const t=tickerInput.value.trim().toUpperCase();
    if(!t) return;
    try{
      const res=await fetch(`https://query1.finance.yahoo.com/v7/finance/quote?symbols=${encodeURIComponent(t)}`);
      const data=await res.json();
      const q=data.quoteResponse&&data.quoteResponse.result&&data.quoteResponse.result[0];
      if(q){
        if(!iForm.name.value) iForm.name.value=q.shortName||q.longName||'';
        if(!iForm.current_value.value&&q.regularMarketPrice!=null) iForm.current_value.value=q.regularMarketPrice;
      }
    }catch(err){console.warn('Ticker lookup failed',err);}
  });
}

iForm.addEventListener('submit',async e=>{
  e.preventDefault();
  
  const price=parseFloat(priceInput.value);
  const qty=parseFloat(qtyInput.value);
  const inv=parseFloat(investedInput.value);
  if(isNaN(price)||price<=0|| ((isNaN(qty)||qty<=0)&&(isNaN(inv)||inv<=0)) ){
    alert('Ievadiet derīgas vērtības: cena un daudzums VAI ieguldījums.');
    return;
  }
  if((isNaN(inv)||inv<=0)&&qty>0){
    investedInput.value=(qty*price).toFixed(2);
  } else if((isNaN(qty)||qty<=0)&&inv>0){
    qtyInput.value=(inv/price).toFixed(4);
  }
  updateSummary();
  const fd=new FormData(iForm);
  const cid=cardContext.get();
  if(cid) fd.append('card_id',cid);
  const r=await fetch('../api/index.php/investments/create',{credentials:'include',method:'POST',body:fd});
  const d=await r.json();
  if(d.success){
    bootstrap.Modal.getInstance(document.getElementById('iModal')).hide();
    e.target.reset();
    load();
    renderTradingView(fd.get('ticker').toUpperCase());
  }else alert(d.message||'Kļūda');
});

document.getElementById('iTable').addEventListener('click',async e=>{
  const tr=e.target.closest('tr');
  if(tr&&tr.dataset.ticker){
    renderTradingView(tr.dataset.ticker.toUpperCase());
  }
  if(e.target.matches('button[data-id]')){const id=e.target.dataset.id;if(confirm('Dzēst investīciju?')){const p=new URLSearchParams({id});
      const cid=cardContext.get();
      if(cid) p.append('card_id',cid);
      await fetch('../api/index.php/investments/delete',{credentials:'include',method:'POST',body:p});load();}}});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});
