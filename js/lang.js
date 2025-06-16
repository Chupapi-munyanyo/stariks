// Simple i18n toggle between Latvian (lv) and English (en)
(function(){
  const dict={
    en:{dashboard:'Dashboard',transactions:'Transactions',budgets:'Budgets',investments:'Investments',cards:'Cards',receipts:'Reports',logout:'Logout'},
    lv:{dashboard:'Panelis',transactions:'Transakcijas',budgets:'Budžeti',investments:'Investīcijas',cards:'Kartes',receipts:'Atskaites',logout:'Izrakstīties'}
  };
  const btn=document.getElementById('langBtn');
  if(!btn) return;
  const apply=(lang)=>{
    document.documentElement.setAttribute('data-lang',lang);
    localStorage.setItem('lang',lang);
    const map=dict[lang]||{};
    document.querySelectorAll('[data-i18n]').forEach(el=>{
       const key=el.getAttribute('data-i18n');
       if(map[key]) el.textContent=map[key];
    });
    btn.textContent=lang.toUpperCase();
  };
  btn.addEventListener('click',()=>{
    const current=localStorage.getItem('lang')||'lv';
    apply(current==='lv'?'en':'lv');
  });
  // init
  apply(localStorage.getItem('lang')||'lv');
})();
