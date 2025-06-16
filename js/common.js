// Common utilities across pages
// Card context helpers
window.cardContext={
  get(){return localStorage.getItem('activeCardId');},
  set(id){localStorage.setItem('activeCardId',id);} };
// Reload page when active card changes
window.addEventListener('storage',e=>{if(e.key==='activeCardId') location.reload();});

// Global color palette (teal / green theme)
window.chartPalette = {
  teal: '#025864',
  green: '#00D47E',
  lightGreen: '#5ED7A7',
  darkTeal: '#01404d',
  darkGreen: '#019e67',
  array(){return [this.green,this.teal,this.lightGreen,this.darkTeal,this.darkGreen,'#7ff0c2','#38ff9d'];}
};

// Helper for consistent Chart.js options across pages
window.buildChartOptions = function(cfg = {}) {
  return {
    plugins: {
      legend: {
        labels: { color: '#000', font: { weight: 'bold' } },
        ...(cfg.legend || {})
      },
      title: {
        display: !!cfg.title,
        text: cfg.title,
        color: '#000',
        font: { size: 16, weight: 'bold' }
      }
    },
    responsive: true,
    maintainAspectRatio: false,
    layout: { padding: 10 },
    ...(cfg.extra || {})
  };
};

// Apply global defaults if Chart is already loaded
if (window.Chart) {
  Chart.defaults.color = '#000';
  Chart.defaults.font.family = 'Roboto, sans-serif';
}

// Simple form validation helper
// rules: { fieldName: value => true/false }
window.validateForm = function(form, rules) {
  for (const [name, check] of Object.entries(rules)) {
    const el = form.elements[name];
    if (!el || !check(el.value)) {
      alert('Nepareizi aizpildīts lauks: ' + name);
      el?.focus();
      return false;
    }
  }
  return true;
};

async function updateBalance(){
  try{
    const r=await fetch('../api/index.php/cards/list',{credentials:'include'});
    const d=await r.json();
    if(d.success){
      const total=d.cards.reduce((sum,c)=>sum+parseFloat(c.balance_amount||0),0);
      document.querySelectorAll('#balanceDisplay').forEach(el=>{
        el.textContent=`Saldo: €${total.toFixed(2)}`;
      });
    }
  }catch(e){console.error('Balance fetch error',e);}

}
document.addEventListener('DOMContentLoaded',()=>{
  updateBalance();
  const logout=document.getElementById('logoutBtn');
  if(logout){
    logout.addEventListener('click',async()=>{
      try{await fetch('../api/index.php/auth/logout',{credentials:'include'});}catch(e){}
      location.href='../templates/index.html';
    });
  }
});
