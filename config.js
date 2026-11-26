// publishable key: browser में रखने के लिए ही बनी है; सुरक्षा database के RLS नियमों से है
const SB_URL='https://dbwqzgpkffkqgajfymot.supabase.co';
const SB_KEY='sb_publishable_OymizDuwPGJt-TOmNYoMyw_7OgNrPUX';
const CATS=[
 {id:'teaching',hi:'शिक्षण कार्य',en:'Teaching',img:'c1',ic:'cap',c:'#4a1d96'},
 {id:'computer',hi:'कंप्यूटर एवं<br>आईटी कार्य',en:'Computer & IT',img:'c2',ic:'laptop',c:'#1d4ed8'},
 {id:'office',hi:'ऑफिस एवं<br>प्रशासन',en:'Office & Admin',img:'c3',ic:'bag',c:'#7c2d92'},
 {id:'marketing',hi:'मार्केटिंग<br>एवं सेल्स',en:'Marketing & Sales',img:'c4',ic:'chart',c:'#15803d'},
 {id:'field',hi:'फील्ड कार्य',en:'Field Work',img:'c5',ic:'pin',c:'#b45309'},
 {id:'technical',hi:'तकनीकी कार्य',en:'Technical',img:'c6',ic:'tool',c:'#0e7490'}];
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
