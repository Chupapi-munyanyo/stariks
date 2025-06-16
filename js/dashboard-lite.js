document.addEventListener('DOMContentLoaded',()=>{
 const cid=cardContext.get();
 fetch(`../api/index.php/dashboard/summary${cid?`?card_id=${cid}`:''}`,{credentials:'include'})
  .then(r=>r.json()).then(draw).catch(err=>{console.error('Dashboard load error',err);alert('Kļūda ielādējot datus');});
 function draw(res){
   if(!res.success){alert(res.message);return;}
   const d=res.data;
   // Calculate totals and last month for % change
   let totalIncome = 0, totalExpense = 0, lastIncome = 0, lastExpense = 0;
   const months = (d.monthlyTotals||[]).map(row => row.month);
   (d.monthlyTotals||[]).forEach((row,i,arr) => {
     if(i === arr.length-1) { lastIncome = Number(row.income)||0; lastExpense = Number(row.expense)||0; }
     totalIncome += Number(row.income)||0;
     totalExpense += Number(row.expense)||0;
   });
   const balance = parseFloat(res.data.cardsBalance ?? 0);
   const lastBalance = balance;
   // Calculate % changes
   const incomeChange = lastIncome ? ((totalIncome-lastIncome)/lastIncome*100).toFixed(1) : 0;
   const expenseChange = lastExpense ? ((totalExpense-lastExpense)/lastExpense*100).toFixed(1) : 0;
   const balanceChange = lastBalance ? ((balance-lastBalance)/Math.abs(lastBalance)*100).toFixed(1) : 0;
   // Fill summary boxes
   document.getElementById('balanceValue').textContent = `€${balance.toFixed(2)}`;
   document.getElementById('incomeValue').textContent = `€${totalIncome.toFixed(2)}`;
   document.getElementById('expenseValue').textContent = `€${totalExpense.toFixed(2)}`;
   document.getElementById('balanceSub').textContent = `${balanceChange >= 0 ? '+' : ''}${balanceChange}% no pagājušā mēneša`;
   document.getElementById('balanceSub').className = 'summary-sub ' + (balanceChange >= 0 ? 'summary-lime' : 'summary-red');
   document.getElementById('incomeSub').textContent = `${incomeChange >= 0 ? '+' : ''}${incomeChange}% no pagājušā mēneša`;
   document.getElementById('incomeSub').className = 'summary-sub ' + (incomeChange >= 0 ? 'summary-lime' : 'summary-red');
   document.getElementById('expenseSub').textContent = `${expenseChange >= 0 ? '+' : ''}${expenseChange}% no pagājušā mēneša`;
   document.getElementById('expenseSub').className = 'summary-sub ' + (expenseChange >= 0 ? 'summary-lime' : 'summary-red');
   // Draw charts
   bar(d.monthlyTotals);
   pie('expensesPie',d.expenseByCategory,'Izdevumi');
   pie('incomePie',d.incomeByCategory,'Ienākumi');
   table(d.latest);
 }
 function pie(id,rows,label){
    if(!rows || !rows.length) return; // nothing to draw
    new Chart(document.getElementById(id),{
     type:'pie',
     data:{
       labels:rows.map(r=>r.label),
       datasets:[{
         data:rows.map(r=>r.total),
         backgroundColor: [
            '#00D47E', '#025864', '#5ed7a7','#02837a','#7ff0c2','#01404d','#38ff9d'
          ],
          borderColor: '#ffffff',
         borderWidth: 2
       }]
     },
     options:{
       plugins:{
         legend:{labels:{color:'#000',font:{weight:'bold'}}},
         title:{display:true,text:label,color:'#000',font:{size:16,weight:'bold'}}
       },
       layout:{padding:10},
       responsive: true,
       maintainAspectRatio: false,
       backgroundColor:'#fff'
     }
   });
 }
 function bar(rows){
    if(!rows || !rows.length) return;
    const ctx = document.getElementById('monthlyBar').getContext('2d');
   // Create green gradient for bars
   const incomeColor = '#025864'; // teal
    const expenseColor = '#00D47E'; // bright green
   new Chart(ctx,{
     type:'bar',
     data:{
       labels:rows.map(r=>r.month).reverse(),
       datasets:[
         {
           label:'Ienākumi',
           data:rows.map(r=>r.income).reverse(),
           backgroundColor: incomeColor,
           borderColor:'#013f4d',
           borderWidth:2
         },
         {
           label:'Izdevumi',
           data:rows.map(r=>r.expense).reverse(),
           backgroundColor: expenseColor,
           borderColor:'#019e67',
           borderWidth:2
         }
       ]
     },
     options:{
       plugins:{
         legend:{labels:{color:'#000',font:{weight:'bold'}}},
         title:{display:true,text:'Ienākumi un Izdevumi',color:'#000',font:{size:16,weight:'bold'}}
       },
       scales:{
         x:{ticks:{color:'#000'}},
         y:{ticks:{color:'#000'}},
         },
       backgroundColor:'#fff',
       layout:{padding:10}
     }
   });
 }
 function table(rows){const tb=document.querySelector('#latestTable tbody');tb.innerHTML='';rows.forEach(r=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${r.happened_on}</td><td>${r.category}</td><td>${r.type}</td><td>${r.amount}</td>`;tb.appendChild(tr);});}
});
