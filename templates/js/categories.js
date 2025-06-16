async function load(){const r=await fetch('../api/index.php/categories/list',{credentials:'include'});const d=await r.json();const tb=document.querySelector('#catTable tbody');tb.innerHTML=d.categories.map(c=>`<tr><td>${c.type==='expense'?'Izdevumi':'Ienākumi'}</td><td>${c.label}</td></tr>`).join('');}
load();

document.getElementById('catForm').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);const r=await fetch('../api/index.php/categories/create',{credentials:'include',method:'POST',body:fd});const d=await r.json();if(d.success){e.target.reset();load();}else alert(d.message||'Kļūda');});

document.getElementById('logoutBtn').addEventListener('click',async()=>{await fetch('../api/index.php/auth/logout',{credentials:'include'});location.href='../login.html';});
