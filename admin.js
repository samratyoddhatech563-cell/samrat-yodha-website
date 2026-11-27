const $app=document.getElementById('app'),$=id=>document.getElementById(id);
let token=sessionStorage.getItem('jt')||'',jobs=[],cfg={};
async function call(path,method='GET',body){
 const r=await fetch(SB_URL+path,{method,headers:{apikey:SB_KEY,'Content-Type':'application/json',Prefer:'return=representation',...(token&&{Authorization:'Bearer '+token})},body:body&&JSON.stringify(body)});
 const t=await r.text(),d=t?JSON.parse(t):null;
 if(r.status===401&&token){logout();throw Error('दोबारा लॉगिन करें')}
 if(!r.ok)throw Error(d?.msg||d?.error_description||d?.message||r.status);return d}
function logout(){token='';sessionStorage.removeItem('jt');login()}
const head=t=>`<header class="top"><h1>${t}</h1>${token?'<button onclick="logout()" style="font-size:14px">लॉगआउट</button>':''}</header><p class="msg" id="msg"></p>`;
const fail=e=>{$('msg').textContent='त्रुटि: '+e.message;scrollTo(0,0)};
function login(){$app.innerHTML=head('Admin Panel')+`<form id="f"><label>ईमेल</label><input id="e" type="email" required autocomplete="username"><label>पासवर्ड</label><input id="p" type="password" required autocomplete="current-password"><button class="btn">लॉगिन</button></form>`;
 $('f').onsubmit=async ev=>{ev.preventDefault();try{const d=await call('/auth/v1/token?grant_type=password','POST',{email:$('e').value,password:$('p').value});token=d.access_token;
  const a=await call('/rest/v1/admins?select=user_id');if(!a.length){token='';throw Error('इस खाते को admin अनुमति नहीं है')}
  sessionStorage.setItem('jt',token);home()}catch(e){fail(e)}}}
async function home(){try{[jobs,[cfg]]=await Promise.all([call('/rest/v1/jobs?select=*&order=sort_order.asc,created_at.desc'),call('/rest/v1/settings?select=*&id=eq.1')])}catch(e){return token&&($app.innerHTML=head('Admin Panel'),fail(e))}
 $app.innerHTML=head('Admin Panel')+`<div class="pad"><button class="btn" onclick="edit()">+ नई नौकरी जोड़ें</button><h3 style="margin:18px 0 8px">नौकरी सूची (${jobs.length})</h3>
 ${jobs.map(j=>`<div class="item"><b>${esc(j.title_hi)}</b> ${j.is_active?'':'(छिपी हुई)'}<br><small>${esc(j.location)} · क्रम ${j.sort_order}</small><br><button class="btn" onclick="edit(${j.id})">संपादित करें</button> <button class="btn gray" onclick="toggle(${j.id})">${j.is_active?'छिपाएँ':'दिखाएँ'}</button> <button class="btn red" onclick="del(${j.id})">हटाएँ</button></div>`).join('')}</div>
 <form id="s"><h3>सेटिंग</h3><label>WhatsApp नंबर (91 के साथ)</label><input id="wn" inputmode="numeric" pattern="91[0-9]{10}" required value="${esc(cfg.whatsapp)}"><label>हमारे बारे में</label><textarea id="ab" rows="6">${esc(cfg.about)}</textarea><button class="btn">सेटिंग सेव करें</button></form>`;
 $('s').onsubmit=async e=>{e.preventDefault();try{const r=await call('/rest/v1/settings?id=eq.1','PATCH',{whatsapp:$('wn').value,about:$('ab').value});if(!r.length)throw Error('सेव नहीं हुआ');$('msg').textContent='✓ सेव हो गया';scrollTo(0,0)}catch(x){fail(x)}}}
const F=[['title_hi','पद का नाम (हिंदी)',1],['title_en','पद का नाम (English)'],['location','स्थान (छोटा, जैसे लखनऊ, उ.प्र.)',1],['location_full','पूरा स्थान'],['salary','वेतन',1],['qualification','योग्यता',1],['organisation','संस्था'],['experience','अनुभव'],['duties','कार्य'],['whatsapp','इस नौकरी का WhatsApp नंबर (खाली छोड़ें तो मुख्य नंबर)'],['sort_order','सूची में क्रम (संख्या)']];
function edit(id){const j=jobs.find(x=>x.id===id)||{category:CATS[0].id,organisation:'सम्राट योद्धा फाउंडेशन',is_new:true,is_active:true,sort_order:0};
 $app.innerHTML=head(id?'नौकरी संपादित करें':'नई नौकरी जोड़ें')+`<form id="f"><label>श्रेणी</label><select id="category">${CATS.map(c=>`<option value="${c.id}" ${j.category===c.id?'selected':''}>${c.hi.replace('<br>',' ')} (${c.en})</option>`).join('')}</select>
 ${F.map(([k,l,r])=>`<label>${l}</label><input id="${k}" ${k==='sort_order'?'type="number"':''} ${r?'required':''} value="${esc(j[k])}">`).join('')}
 <label><input type="checkbox" id="is_new" ${j.is_new?'checked':''}> “नया” का निशान दिखाएँ</label><label><input type="checkbox" id="is_active" ${j.is_active?'checked':''}> वेबसाइट पर दिखाएँ</label>
 <button class="btn">सेव करें</button> <button type="button" class="btn gray" onclick="home()">रद्द करें</button></form>`;
 $('f').onsubmit=async e=>{e.preventDefault();const b={category:$('category').value,is_new:$('is_new').checked,is_active:$('is_active').checked};F.forEach(([k])=>b[k]=k==='sort_order'?parseInt($(k).value)||0:$(k).value.trim());
  let w=b.whatsapp.replace(/\D/g,'').replace(/^0+/,'');if(w.length===10)w='91'+w;
  if(b.whatsapp&&!/^91\d{10}$/.test(w))return fail(Error('WhatsApp नंबर 10 अंकों का लिखें'));b.whatsapp=w;
  try{const r=await call('/rest/v1/jobs'+(id?'?id=eq.'+id:''),id?'PATCH':'POST',b);if(!r.length)throw Error('सेव नहीं हुआ');home()}catch(x){fail(x)}}}
async function toggle(id){try{await call('/rest/v1/jobs?id=eq.'+id,'PATCH',{is_active:!jobs.find(x=>x.id===id).is_active});home()}catch(e){fail(e)}}
async function del(id){if(!confirm('क्या यह नौकरी हटानी है?'))return;try{await call('/rest/v1/jobs?id=eq.'+id,'DELETE');home()}catch(e){fail(e)}}
token?home():login();
