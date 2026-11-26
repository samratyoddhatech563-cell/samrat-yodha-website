const P={menu:'M3 6h18M3 12h18M3 18h18',back:'M20 12H4m7-7-7 7 7 7',next:'M9 5l7 7-7 7',search:'M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zm5 12 5 5',
home:'M3 11 12 3l9 8M5 10v10h5v-6h4v6h5V10',gridi:'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',bag:'M3 8h18v12H3zM9 8V4h6v4M3 13h18',
phone:'M5 4h4l2 5-2.500 1.500a12 12 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',star:'m12 3 2.700 5.600 6.100.8-4.500 4.300 1.100 6.100L12 16.900l-5.400 2.900 1.100-6.100-4.500-4.300 6.100-.8z',
info:'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm0 8v6m0-10v.1',pin:'M12 21s-7-6.500-7-12a7 7 0 0 1 14 0c0 5.500-7 12-7 12zm0-14.500a2.500 2.500 0 1 0 0 5 2.500 2.500 0 0 0 0-5z',
money:'M7 4h11M7 9h11M7 4c9 0 9 10 0 10l8 7',bank:'M3 10l9-6 9 6zM5 10v8m5-8v8m4-8v8m5-8v8M3 21h18',cap:'M2 9l10-5 10 5-10 5zM6 11v5c3 3 9 3 12 0v-5M22 9v6',
user:'M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8zM4 21c0-8 16-8 16 0',tasks:'M9 6h12M9 12h12M9 18h12M3 6h2M3 12h2M3 18h2',laptop:'M5 5h14v11H5zM2 20h20',
chart:'M5 21v-6m7 6V10m7 11V4',tool:'M14.500 6a4.500 4.500 0 0 0 5.500 5.500L10 21l-4-4 9.500-9.500M14.500 6 18 3',check:'M4 13l5 5L20 6',
wa:'M3 21l1.700-5A9 9 0 1 1 8 19.300zM9 8c-1 3 3 8 7 7l.5-2-2.500-1-1 1c-1.500-.5-2.500-1.500-3-3l1-1-1-2.500z'};
const I=n=>`<svg viewBox="0 0 24 24" aria-hidden="true"><path d="${P[n]}"/></svg>`;
const $app=document.getElementById('app');
let jobs=[],cfg={whatsapp:'',about:''},state='loading',q='';
const api=p=>fetch(SB_URL+'/rest/v1/'+p,{headers:{apikey:SB_KEY}}).then(r=>{if(!r.ok)throw r.status;return r.json()});
const cat=id=>CATS.find(c=>c.id===id)||CATS[0];
const brand=()=>`<header class="brand"><img src="img/logo.webp" alt="सम्राट योद्धा फाउंडेशन"><div>सम्राट योद्धा फाउंडेशन<b>Job Portal</b></div><button aria-label="मेन्यू" onclick="document.querySelector('.menu').classList.add('open')">${I('menu')}</button></header>
<div class="menu" onclick="this.classList.remove('open')"><nav><a href="#home">होम</a><a href="#categories">नौकरी श्रेणियां</a><a href="#jobs">सभी नौकरियां</a><a href="#latest">नवीनतम नौकरियां</a><a href="#about">हमारे बारे में</a><a href="#contact">संपर्क</a></nav></div>`;
const bar=(t,back,right='')=>`<header class="top"><a href="${back}" aria-label="वापस">${I('back')}</a><h1>${t}</h1>${right}</header>`;
const nav=on=>`<nav class="nav">${[['home','home','होम'],['categories','gridi','श्रेणियां'],['jobs','bag','नौकरियां'],['contact','phone','संपर्क']].map(([h,i,t])=>`<a href="#${h}" class="${on===h?'on':''}">${I(i)}${t}</a>`).join('')}</nav>`;
const title=(j,tag)=>`<${tag}>${esc(j.title_hi)}${j.title_en?`<small>(${esc(j.title_en)})</small>`:''}</${tag}>`;
const card=j=>`<a class="job" href="#job/${j.id}"><div class="jh"><span class="ic" style="color:${cat(j.category).c}">${I(cat(j.category).ic)}</span>${title(j,'h3')}</div>${j.is_new?'<span class="new">नया</span>':''}
<div class="row loc">${I('pin')}${esc(j.location)}</div><div class="row sal">${I('money')}${esc(j.salary)}</div><div class="row">${I('bank')}<span><b>योग्यता:</b> ${esc(j.qualification)}</span></div><span class="arrow">${I('next')}</span></a>`;
const status=t=>state==='loading'?'<p class="empty">लोड हो रहा है…</p>':state==='error'?'<p class="empty">जानकारी लोड नहीं हो सकी। इंटरनेट जाँचकर पेज दोबारा खोलें।</p>':`<p class="empty">${t}</p>`;
const match=j=>!q||[j.title_hi,j.title_en,j.location,j.location_full,j.qualification,cat(j.category).en].join(' ').toLowerCase().includes(q.toLowerCase());
const listHtml=l=>{l=l.filter(match);return state==='ok'&&l.length?l.map(card).join(''):status('अभी कोई नौकरी उपलब्ध नहीं है।')};
const waLink=text=>`https://wa.me/${cfg.whatsapp.replace(/\D/g,'')}?text=${encodeURIComponent(text)}`;
const message=j=>`नमस्कार,

मैं "${j.title_hi}${j.title_en?` (${j.title_en})`:''}" पद के लिए आवेदन/रुचि रखता/रखती हूँ।
कृपया आगे की जानकारी दें।

नाम : ____________
जिला : ____________

धन्यवाद।
सम्राट योद्धा फाउंडेशन
Job Portal`;
const waBtn=(text,sent)=>cfg.whatsapp?`<a class="wa" href="${esc(waLink(text))}" target="_blank" rel="noopener" ${sent?`onclick="setTimeout(()=>location.hash='sent',400)"`:''}>${I('wa')}WhatsApp पर संपर्क करें</a>`:'';
function render(){
 const [page,arg]=(location.hash.slice(1)||'home').split('/');let h='';
 if(page==='categories') h=bar('नौकरी की श्रेणियां','#home')+`<div class="grid">${CATS.map(c=>`<a class="cat" href="#category/${c.id}"><img src="img/${c.img}.jpg" alt=""><span>${c.hi}<small>(${c.en})</small></span></a>`).join('')}</div>`+nav('categories');
 else if(['jobs','latest','category'].includes(page)){
  let l=jobs;if(page==='category')l=jobs.filter(j=>j.category===arg);if(page==='latest')l=[...jobs].sort((a,b)=>b.created_at.localeCompare(a.created_at)||a.sort_order-b.sort_order).slice(0,20);
  const name=page==='latest'?'नवीनतम नौकरियां':'उपलब्ध नौकरियां';
  h=bar(`${name}${state==='ok'?` (${l.length})`:''}`,page==='category'?'#categories':'#home',`<button aria-label="खोजें" id="sbtn">${I('search')}</button>`)+
  `<label class="search" id="sbox" style="margin:10px;border:1px solid #ccc;${q?'':'display:none'}">${I('search')}<input id="q" placeholder="यहाँ नौकरी खोजें..." value="${esc(q)}"></label><div class="list" id="list">${listHtml(l)}</div>`+nav('jobs');
  $app.innerHTML=h;
  document.getElementById('sbtn').onclick=()=>{const b=document.getElementById('sbox');b.style.display='';document.getElementById('q').focus()};
  document.getElementById('q').oninput=e=>{q=e.target.value;document.getElementById('list').innerHTML=listHtml(l)};return}
 else if(page==='job'){
  const j=jobs.find(x=>String(x.id)===arg);
  h=bar('नौकरी का पूरा विवरण','#jobs');
  if(!j)h+=status('यह नौकरी अब उपलब्ध नहीं है।');else{
   const rows=[['bank','संस्था',j.organisation],['pin','स्थान',j.location_full||j.location],['money','वेतन',j.salary],['cap','योग्यता',j.qualification],['user','अनुभव',j.experience],['tasks','कार्य',j.duties]].filter(r=>r[2]);
   h+=`<img class="banner" src="img/${j.category==='teaching'?'teacher':cat(j.category).img}.jpg" alt=""><div class="dtitle"><span class="ic">${I(cat(j.category).ic)}</span>${title(j,'h2')}</div><div class="sec">${I('bank')}नौकरी की जानकारी</div>
   <table class="info">${rows.map(r=>`<tr><td>${I(r[0])}</td><td>${r[1]}</td><td>:</td><td>${esc(r[2])}</td></tr>`).join('')}</table>${waBtn(message(j),true)}`}
 }
 else if(page==='sent') h=brand()+`<div class="done"><div class="tick">${I('check')}</div><h2>संपर्क अनुरोध<br>भेज दिया गया!</h2><div class="box"><p>आपका संदेश WhatsApp<br>पर तैयार है।</p><p>अब “Send” बटन दबाकर<br>संदेश भेज दें।</p></div><a class="more" href="#jobs">${I('back')}अन्य नौकरियां देखें</a></div>`;
 else if(page==='about') h=bar('हमारे बारे में','#home')+`<div class="about">${esc(cfg.about)}</div>`+nav('');
 else if(page==='contact') h=bar('संपर्क','#home')+`<div class="done"><h2>सम्राट योद्धा फाउंडेशन</h2><div class="box"><p>नौकरी से जुड़ी जानकारी के लिए<br>WhatsApp पर संपर्क करें।</p></div>${waBtn('नमस्कार, मुझे Job Portal के बारे में जानकारी चाहिए।')}</div>`+nav('contact');
 else h=brand()+`<div class="home"><section class="hero"><img src="img/hero.jpg" alt=""><div><h2>रोजगार के<br>बेहतरीन<br>अवसर</h2><p>योग्यता के अनुसार<br>अपने लिए सही<br>नौकरी खोजें</p></div></section>
  <form class="search" id="sf">${I('search')}<input id="hq" placeholder="यहाँ नौकरी खोजें..." aria-label="नौकरी खोजें"></form>
  <div class="tiles"><a class="tile t1" href="#jobs">${I('bag')}सभी नौकरियां</a><a class="tile t2" href="#categories">${I('gridi')}नौकरी श्रेणियां</a><a class="tile t3" href="#latest">${I('star')}नवीनतम<br>नौकरियां</a><a class="tile t4" href="#about">${I('info')}हमारे बारे में</a></div></div>`+nav('home');
 $app.innerHTML=h;
 const f=document.getElementById('sf');if(f)f.onsubmit=e=>{e.preventDefault();q=document.getElementById('hq').value.trim();keep=true;location.hash='jobs'};
}
let keep=false;
addEventListener('hashchange',()=>{if(!keep)q='';keep=false;render();scrollTo(0,0)});
render();
Promise.all([api('jobs?select=*&is_active=eq.true&order=sort_order.asc,created_at.desc'),api('settings?select=*&id=eq.1')])
 .then(([j,s])=>{jobs=j;cfg=s[0]||cfg;state='ok';render()}).catch(()=>{state='error';render()});
