// publishable key: browser में रखने के लिए ही बनी है; सुरक्षा database के RLS नियमों से है
const SB_URL='https://dbwqzgpkffkqgajfymot.supabase.co';
const SB_KEY='sb_publishable_OymizDuwPGJt-TOmNYoMyw_7OgNrPUX';
const CATS=[
 {id:'computer',hi:'कंप्यूटर एवं<br>आईटी कार्य',en:'Computer & IT',img:'it',pos:'50% 6%',ic:'laptop',c:'#1d4ed8'},
 {id:'office',hi:'ऑफिस एवं<br>प्रशासन',en:'Office & Admin',img:'c3',ic:'bag',c:'#7c2d92'},
 {id:'marketing',hi:'मार्केटिंग<br>एवं सेल्स',en:'Marketing & Sales',img:'c4',ic:'chart',c:'#15803d'},
 {id:'field',hi:'फील्ड कार्य',en:'Field Work',img:'c5',ic:'pin',c:'#b45309'},
 {id:'payment',img:'digital',pos:'50% 25%',hi:'पेटीएम /<br>गूगल पे',en:'Paytm / Google Pay',ic:'money',c:'#1d4ed8',note:1},
 {id:'blinkit',img:'digital',pos:'50% 25%',hi:'ब्लिंकिट<br>कार्य',en:'Blinkit',ic:'box',c:'#a16207',note:1},
 {id:'security',img:'security',pos:'50% 0',hi:'सिक्योरिटी<br>कार्य',en:'Security',ic:'shield',c:'#7c2d92'}];
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
