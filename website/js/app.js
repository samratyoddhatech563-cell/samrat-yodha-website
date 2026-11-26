/* सम्राट योद्धा फाउंडेशन — वेबसाइट का काम करने वाला कोड।
   इसमें बदलाव की ज़रूरत नहीं है। सारी जानकारी, फ़ोटो और ब्लॉग एडमिन पैनल
   (samratyodhafoundation.org/admin) से बदलें। राज्य-ज़िले js/districts.js में हैं। */
(function () {
  'use strict';
  const $ = id => document.getElementById(id);
  const fmt = n => Number(n).toLocaleString('en-IN');
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
  const wa = text => 'https://wa.me/' + CONFIG.whatsapp + '?text=' + encodeURIComponent(text);
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  /* एडमिन पैनल में लिखी English (js/site-data.js) सबसे ऊपर मानी जाए */
  if (typeof SITE_EN === 'object' && SITE_EN) Object.assign(LANG.en, SITE_EN);
  const P = PATHSHALAS, C = COURSES;
  const kids = p => (Number(p.boys) || 0) + (Number(p.girls) || 0);
  const isC = p => p.type === 'सेंटर';
  const uniq = a => [...new Set(a)];

  /* ---------- राज्य और ज़िले ---------- */
  const ST = STATES.map(s => ({
    n: s.n, en: s.en, ut: !!s.ut,
    d: s.d.split(',').map(x => { const [h, e] = x.trim().split('|'); return { n: h.trim(), en: (e || h).trim() }; })
  }));
  P.forEach(p => {   // data.js में कोई ऐसा राज्य/ज़िला हो जो सूची में न हो, तो उसे जोड़ दें
    let st = ST.find(s => s.n === p.s);
    if (!st) { st = { n: p.s, en: p.s_en || p.s, d: [] }; ST.push(st); }
    if (!st.d.some(d => d.n === p.d)) st.d.push({ n: p.d, en: p.d_en || p.d });
  });
  const stateOf = n => ST.find(s => s.n === n);

  /* ---------- भाषा ---------- */
  const KEY = 'syf-lang';
  let lang = 'hi';
  try { if (localStorage.getItem(KEY) === 'en') lang = 'en'; } catch (e) { /* सेव न हो सके तो हिंदी */ }
  const T = k => LANG.ui[lang][k];
  const en = () => lang === 'en';
  const nm = o => (en() && o.en) ? o.en : o.n;                                // राज्य / ज़िले का नाम
  const L = (o, f) => (en() && o[f + '_en']) ? o[f + '_en'] : o[f];           // पाठशाला की जानकारी
  const sName = s => { const st = stateOf(s); return st ? nm(st) : s; };
  const dName = (s, d) => { const st = stateOf(s), o = st && st.d.find(x => x.n === d); return o ? nm(o) : d; };
  const tag = c => (en() && COURSE_TAGS[c]) ? COURSE_TAGS[c] : c;
  const typeLabel = p => isC(p) ? T('typeC') : T('typeP');
  const cmp = (a, b) => a.localeCompare(b, lang === 'en' ? 'en' : 'hi');
  const HI_TITLE = document.title;

  const ATTRS = [['data-i', 'innerHTML'], ['data-i-ph', 'placeholder'], ['data-i-alt', 'alt'], ['data-i-aria', 'aria-label']];
  const orig = new Map();
  ATTRS.forEach(([attr, prop]) => document.querySelectorAll('[' + attr + ']').forEach(el => {
    if (!orig.has(el)) orig.set(el, {});
    orig.get(el)[prop] = prop === 'innerHTML' ? el.innerHTML : (prop === 'aria-label' ? el.getAttribute('aria-label') : el[prop]);
  }));
  function applyStatic() {
    ATTRS.forEach(([attr, prop]) => document.querySelectorAll('[' + attr + ']').forEach(el => {
      const k = el.getAttribute(attr);
      const v = en() && LANG.en[k] !== undefined ? LANG.en[k] : orig.get(el)[prop];
      if (prop === 'innerHTML') el.innerHTML = v;
      else if (prop === 'aria-label') el.setAttribute('aria-label', v);
      else el[prop] = v;
    }));
    document.documentElement.lang = lang;
    document.title = en() ? LANG.en.title : HI_TITLE;
    document.querySelectorAll('.lang button').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.lang === lang)));
  }

  if (!CONFIG.sampleData) document.querySelectorAll('.sample').forEach(el => el.remove());

  /* पहले हिस्से की किरणें */
  const R = 170;
  $('rays').innerHTML = Array.from({ length: 24 }, (_, i) => {
    const a = (i * 15) * Math.PI / 180, b = (i * 15 + 7.5) * Math.PI / 180;
    return `<path d="M0 0L${(R * Math.cos(a)).toFixed(1)} ${(R * Math.sin(a)).toFixed(1)}L${(R * Math.cos(b)).toFixed(1)} ${(R * Math.sin(b)).toFixed(1)}Z"/>`;
  }).join('');

  /* ---------- सबसे ऊपर का स्लाइडर: लोगो, फिर संस्थापक और टीम ---------- */
  const slidesEl = $('slides');
  const people = () => (typeof PEOPLE !== 'undefined' ? PEOPLE : []).filter(p => String(p.name || '').trim());
  const initial = n => {
    try { return [...new Intl.Segmenter('hi', { granularity: 'grapheme' }).segment(n)][0].segment; }
    catch (e) { const m = n.match(/^.[ऀ-ःऺ-ॏॕ-ॗ]*/); return m ? m[0] : n.charAt(0); }
  };
  let slideIdx = 0;
  const slideCount = () => slidesEl.children.length;
  function goTo(i, instant) {
    const n = slideCount(); slideIdx = (i + n) % n;
    slidesEl.scrollTo({ left: slideIdx * slidesEl.clientWidth, behavior: instant || reduced ? 'auto' : 'smooth' });
    markDots();
  }
  function markDots() { [...$('dots').children].forEach((d, i) => d.setAttribute('aria-current', String(i === slideIdx))); }
  function renderPeople() {
    const keep = slideIdx;
    slidesEl.querySelectorAll('.slide.person').forEach(n => n.remove());
    people().forEach(p => {
      const name = L(p, 'name'), role = L(p, 'role'), about = L(p, 'about');
      const el = document.createElement('div');
      el.className = 'slide person'; el.setAttribute('role', 'group'); el.setAttribute('aria-roledescription', 'slide');
      el.innerHTML = `<div class="band"><div class="wrap pgrid"><div class="pphoto">${p.photo ? `<img src="${esc(p.photo)}" alt="${esc(name)}">` : `<span class="ini" aria-hidden="true">${esc(initial(name))}</span>`}</div><div class="ptext">${role ? `<p class="prole">${esc(role)}</p>` : ''}<h2>${esc(name)}</h2>${about ? `<p class="pabout">${esc(about)}</p>` : ''}</div></div></div>`;
      slidesEl.appendChild(el);
    });
    const n = slideCount();
    [...slidesEl.children].forEach((sl, i) => sl.setAttribute('aria-label', `${i + 1} / ${n}`));
    $('dots').innerHTML = Array.from({ length: n }, (_, i) => `<button class="dot" type="button" data-s="${i}" aria-label="${esc(T('slideN')(i + 1))}"></button>`).join('');
    $('slnav').hidden = n < 2;
    goTo(Math.min(keep, n - 1), true);
  }
  let scrollRaf = 0;
  slidesEl.addEventListener('scroll', () => {
    cancelAnimationFrame(scrollRaf);
    scrollRaf = requestAnimationFrame(() => { const i = Math.round(slidesEl.scrollLeft / Math.max(1, slidesEl.clientWidth)); if (i !== slideIdx) { slideIdx = i; markDots(); } });
  }, { passive: true });
  $('slPrev').addEventListener('click', () => goTo(slideIdx - 1));
  $('slNext').addEventListener('click', () => goTo(slideIdx + 1));
  $('dots').addEventListener('click', e => { const d = e.target.closest('.dot'); if (d) goTo(+d.dataset.s); });
  window.addEventListener('resize', () => goTo(slideIdx, true));
  /* संस्थापक की फ़ोटो हो, तो "हमारे बारे में" वाले हिस्से में भी दिखे */
  function founderPhoto() {
    const f = people()[0], av = document.querySelector('.founder .av');
    if (f && f.photo && av) av.innerHTML = `<img src="${esc(f.photo)}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`;
  }

  /* ---------- गिनती ---------- */

  function renderStats() {
    const K = CONFIG.stats, num = v => Math.max(0, Math.round(Number(v) || 0));
    const S = K ? [   // data.js के CONFIG.stats से: गाँव = पाठशालाएँ, शिक्षक = पाठशालाएँ × 1.5
      [num(K.pathshalas), T('sSchools')],
      ...(num(K.centres) ? [[num(K.centres), T('sCenters')]] : []),
      [num(K.children), T('sKids')],
      [num(K.districts), T('sDist')],
      [num(K.pathshalas), T('sVill')],
      [num(K.pathshalas * 1.5), T('sTeach')]
    ] : [
      [P.filter(x => !isC(x)).length, T('sSchools')],
      [P.filter(isC).length, T('sCenters')],
      [P.reduce((s, p) => s + kids(p), 0), T('sKids')],
      [uniq(P.map(p => p.s + '|' + p.d)).length, T('sDist')],
      [uniq(P.map(p => p.s + '|' + p.d + '|' + p.v)).length, T('sVill')],
      [uniq(P.map(p => p.t)).length, T('sTeach')]
    ];
    $('sgrid').innerHTML = S.map(([n, l]) => `<div class="stat"><b data-n="${n}">${fmt(n)}</b><span>${l}</span></div>`).join('');
  }

  /* ---------- पाठ्यक्रम ---------- */
  function renderCourses() {
    $('cgrid').innerHTML = C.map(c => {
      const name = L(c, 'n'), pts = (en() && c.p_en) ? c.p_en : c.p;
      return `<article class="course"><h3>${esc(name)}</h3><p class="who">${T('who')}${esc(L(c, 'w'))}</p><ul>${pts.map(x => `<li>${esc(x)}</li>`).join('')}</ul><a href="${wa(T('admitMsg')(name))}" target="_blank" rel="noopener">${T('admit')}</a></article>`;
    }).join('');
  }

  /* ---------- ज़िलेवार कोचिंग सेंटर: राज्य या ज़िला खोजें ---------- */
  const norm = s => String(s == null ? '' : s).normalize('NFD').replace(/[़̀-ͯ​-‍]/g, '').replace(/ँ/g, 'ं').toLowerCase();
  const flash = (el, block) => { if (!el) return; el.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: block || 'start' }); el.classList.remove('flash'); void el.offsetWidth; el.classList.add('flash'); };
  const centres = (s, d) => P.filter(p => isC(p) && p.s === s && (!d || p.d === d)).length;
  const listReady = P.length > 0;   // सूची खाली हो, तो गिनती की जगह WhatsApp पर पूछने का बटन
  const fs = $('fs'), fd = $('fd');
  let sel = { s: '', d: '' };
  function fillPick() {
    fs.innerHTML = `<option value="">${esc(T('chooseS'))}</option>` + ST.slice().sort((a, b) => cmp(nm(a), nm(b))).map(st => `<option value="${esc(st.n)}">${esc(nm(st))}</option>`).join('');
    fs.value = sel.s;
    const st = stateOf(sel.s);
    fd.innerHTML = `<option value="">${esc(st ? T('chooseD') : T('chooseSFirst'))}</option>` + (st ? st.d.slice().sort((a, b) => cmp(nm(a), nm(b))).map(d => `<option value="${esc(d.n)}">${esc(nm(d))}</option>`).join('') : '');
    fd.disabled = !st;
    fd.value = sel.d;
  }
  function renderResult() {
    const box = $('ares'), st = stateOf(sel.s), d = st && sel.d && st.d.find(x => x.n === sel.d);
    if (!st) { box.className = 'ares hint'; box.textContent = T(listReady ? 'pickHint' : 'pickHintAsk'); return; }
    if (!d) { box.className = 'ares hint'; box.textContent = T('pickDist')(nm(st)); return; }
    if (!listReady) {
      box.className = 'ares ask';
      box.innerHTML = `<div><b>${esc(nm(d))}, ${esc(nm(st))}</b><span>${T('resAsk')}</span><a class="btn wa" href="${wa(T('askMsg')(nm(d), nm(st)))}" target="_blank" rel="noopener">${T('askBtn')}</a></div>`;
      return;
    }
    const c = centres(st.n, d.n);
    box.className = 'ares' + (c ? '' : ' zero');
    box.innerHTML = `<div class="rnum">${c}</div><div><b>${esc(nm(d))}, ${esc(nm(st))}</b><span>${c ? T('resC')(c) : T('resNone')}</span></div>`;
  }
  function selectArea(s, d, scroll) {
    sel = { s: s || '', d: d || '' };
    fillPick();
    renderResult();
    if (scroll) flash(sel.d ? $('ares') : $('schools'), sel.d ? 'center' : 'start');
  }
  fs.addEventListener('change', () => selectArea(fs.value, ''));
  fd.addEventListener('change', () => selectArea(fs.value, fd.value));

  /* ---------- जुड़ने का फ़ॉर्म: संदेश WhatsApp पर जाता है ---------- */
  $('jform').addEventListener('submit', e => {
    e.preventDefault();
    const n = $('jn').value.trim();
    let m = $('jm').value.replace(/\D/g, '');
    if (m.length === 12 && m.startsWith('91')) m = m.slice(2);
    if (m.length === 11 && m.startsWith('0')) m = m.slice(1);
    const fail = (msg, el) => { $('jerr').textContent = msg; $('jres').hidden = true; el.focus(); };
    if (!n) return fail(T('errName'), $('jn'));
    if (!/^[6-9]\d{9}$/.test(m)) return fail(T('errMobile'), $('jm'));
    $('jerr').textContent = '';
    const jt = $('jt'), jw = $('jw');
    $('jlink').href = wa(T('joinMsg')({ n, m, d: $('jd').value.trim() || '-', v: $('jv').value.trim() || '-', t: jt.options[jt.selectedIndex].text, w: jw.value ? jw.options[jw.selectedIndex].text : '' }));
    $('jres').hidden = false;
  });

  /* ---------- दान: बच्चों के लिए सामान चुनें, फिर WhatsApp पर टीम से संपर्क ---------- */
  const hasUpi = !!String(CONFIG.upi || '').trim();
  const upiUrl = () => 'upi://pay?pa=' + encodeURIComponent(CONFIG.upi.trim()) + '&pn=' + encodeURIComponent(CONFIG.upiName || '') + '&cu=INR&tn=' + encodeURIComponent('Daan');
  if (hasUpi) {   // data.js में UPI ID डालने पर ही दिखेगा
    $('upiBox').hidden = false;
    $('upiId').textContent = CONFIG.upi.trim();
    $('dupi').href = upiUrl();
    if (CONFIG.qrImage) $('qr').innerHTML = `<img src="${esc(CONFIG.qrImage)}" alt="UPI QR">`;
    else { try { $('qr').innerHTML = qrSVG(upiUrl()); } catch (err) { $('qr').innerHTML = ''; } }
  }
  $('upiCopy').addEventListener('click', () => {
    const done = () => { $('upiCopy').textContent = T('copied'); setTimeout(() => { $('upiCopy').textContent = T('copy'); }, 2000); };
    if (navigator.clipboard) navigator.clipboard.writeText(CONFIG.upi.trim()).then(done, () => {});
  });
  const itemBtns = [...document.querySelectorAll('#items .item')];
  function updateDonate() {
    const chosen = itemBtns.filter(b => b.getAttribute('aria-pressed') === 'true');
    const otherBtn = chosen.find(b => b.dataset.k === 'other');
    $('otherBox').hidden = !otherBtn;
    const list = chosen.filter(b => b !== otherBtn).map(b => b.textContent.trim());
    if (otherBtn) { const t = $('dother').value.trim(); list.push(otherBtn.textContent.trim() + (t ? ': ' + t : '')); }
    $('dbtn').href = wa(T('donItems')(list));
  }
  $('items').addEventListener('click', e => {
    const b = e.target.closest('.item'); if (!b) return;
    b.setAttribute('aria-pressed', String(b.getAttribute('aria-pressed') !== 'true'));
    updateDonate();
    if (b.dataset.k === 'other' && b.getAttribute('aria-pressed') === 'true') $('dother').focus();
  });
  $('dother').addEventListener('input', updateDonate);
  function donateText() {
    $('upiCopy').textContent = T('copy');
    updateDonate();
  }

  /* ---------- संपर्क ---------- */
  $('cWa').href = 'https://wa.me/' + String(CONFIG.whatsapp);   // नंबर दिखता नहीं, सीधे चैट खुलती है
  $('cFb').href = CONFIG.facebook;
  $('fFb').href = CONFIG.facebook;
  $('cEmailText').textContent = CONFIG.email;
  $('cEmail').href = 'mailto:' + CONFIG.email;
  $('year').textContent = new Date().getFullYear();

  /* ---------- सर्च: "सम्राट योद्धा फाउंडेशन के बारे में जानें" ---------- */
  const sModal = $('sModal'), sq = $('sq'), sres = $('sres');
  let index = [], shown = [], act = 0;
  const txt = el => el ? el.textContent.replace(/\s+/g, ' ').trim() : '';

  function buildIndex() {
    const I = [];
    const add = (o) => I.push(Object.assign({ s: '', k: '', w: 1 }, o));
    T('faq').forEach(f => add({ kind: T('kFaq'), t: f.q, s: f.a.replace('{email}', CONFIG.email), k: f.k, w: 3, el: () => document.querySelector(f.go) }));
    document.querySelectorAll('main section[id], body > section[id]').forEach(sec => {
      const h = sec.querySelector('h2, h1'); if (!h) return;
      add({ kind: T('kPage'), t: txt(h), s: txt(sec.querySelector('.lead')), k: T('secK')[sec.id] || '', w: 2, el: () => sec });
    });
    document.querySelectorAll('.plan li, .ways li').forEach(li => { const b = li.querySelector('b'); add({ kind: T('kPage'), t: txt(b), s: txt(li).replace(txt(b), '').trim(), el: () => li }); });
    document.querySelectorAll('.course').forEach(c => add({ kind: T('kPage'), t: txt(c.querySelector('h3')), s: txt(c.querySelector('.who')) + ' · ' + [...c.querySelectorAll('li')].map(txt).join(', '), el: () => c }));
    document.querySelectorAll('.insp').forEach(x => { const h = x.querySelector('h3, b'); add({ kind: T('kPage'), t: txt(h), s: txt(x).replace(txt(h), '').trim(), el: () => x }); });
    document.querySelectorAll('.ci').forEach(x => add({ kind: T('kPage'), t: txt(x.querySelector('b')), s: txt(x.querySelector('em')), k: 'contact sampark संपर्क', run: () => { flash($('contact')); } }));
    people().forEach((p, i) => add({ kind: T('kPage'), t: L(p, 'name'), s: [L(p, 'role'), L(p, 'about')].filter(Boolean).join(' · '), k: [p.name, p.name_en, p.role, p.role_en].join(' '), w: 2, run: () => { flash($('top')); goTo(i + 1); } }));
    P.forEach(p => {
      const st = stateOf(p.s) || {}, dd = (st.d || []).find(x => x.n === p.d) || {};
      const a = [p.v, p.v_en, p.b, p.b_en, p.t, p.t_en, p.d, p.s, dd.en, st.en, typeLabel(p)].join(' ');
      add({ kind: T('kSchool'), t: typeLabel(p) + ' ' + L(p, 'v'), s: T('place')(L(p, 'v'), L(p, 'b'), dName(p.s, p.d)) + ' · ' + sName(p.s) + ' · ' + kids(p) + ' ' + T('kids'), k: a, w: 2, run: () => selectArea(p.s, p.d, true) });
    });
    ST.forEach(st => {
      add({ kind: T('kState'), t: nm(st), s: listReady ? T('areaSum')(centres(st.n)) : '', names: st.n + ' ' + st.en, run: () => selectArea(st.n, '', true) });
      st.d.forEach(d => {
        add({ kind: T('kDist'), t: nm(d), s: nm(st) + (listReady ? ' · ' + T('areaSum')(centres(st.n, d.n)) : ''), names: d.n + ' ' + d.en, run: () => selectArea(st.n, d.n, true) });
      });
    });
    I.forEach(it => { it.nt = norm(it.t); it.hay = it.names ? norm(it.names) : norm(it.t + ' ' + it.s + ' ' + it.k); if (it.names) it.words = it.hay.split(/[\s\-–.,()]+/).filter(Boolean); });
    return I;
  }

  function runSearch() {
    const qn = norm(sq.value.trim());
    const STOP = ['जिला', 'जिले', 'जिलों', 'राज्य', 'में', 'का', 'की', 'के', 'है', 'district', 'zila', 'jila', 'state', 'in', 'of', 'the', 'is', 'a'];
    let toks = qn.split(/\s+/).filter(Boolean);
    const core = toks.filter(t => !STOP.includes(t));
    if (core.length) toks = core;
    if (!toks.length) {
      shown = index.filter(it => it.kind === T('kFaq'));
      $('sTitle').textContent = T('sHint');
    } else {
      const scored = [];
      index.forEach(it => {
        if (it.words ? !toks.every(t => it.words.some(wd => wd.startsWith(t))) : !toks.every(t => it.hay.includes(t))) return;
        let sc = it.w + toks.filter(t => it.nt.includes(t)).length * 2;
        if (it.nt.startsWith(toks[0])) sc += 3;
        if (it.nt === qn) sc += 6;
        scored.push([sc, it]);
      });
      scored.sort((a, b) => b[0] - a[0]);
      let places = 0;
      shown = [];
      for (const [, it] of scored) {
        if (it.names) { if (places >= 8) continue; places++; }
        shown.push(it);
        if (shown.length >= 15) break;
      }
      $('sTitle').textContent = shown.length ? T('sFound')(shown.length) : '';
    }
    act = 0;
    sres.innerHTML = shown.length ? shown.map((it, i) => `<button type="button" class="sitem${i === 0 ? ' act' : ''}" data-x="${i}"><em>${esc(it.kind)}</em><b>${esc(it.t)}</b>${it.s ? `<span>${esc(it.s)}</span>` : ''}</button>`).join('')
      : `<p class="snone">${esc(T('sNone'))}</p>`;
  }
  function openSearch() {
    index = buildIndex();
    sModal.hidden = false;
    document.body.style.overflow = 'hidden';
    sq.value = '';
    runSearch();
    sq.focus();
  }
  function closeSearch() {
    sModal.hidden = true;
    document.body.style.overflow = '';
    $('sOpen').focus({ preventScroll: true });
  }
  function choose(it) {
    if (!it) return;
    closeSearch();
    if (it.run) it.run(); else flash(it.el && it.el());
  }
  $('sOpen').addEventListener('click', openSearch);
  $('sClose').addEventListener('click', closeSearch);
  sModal.addEventListener('click', e => { if (e.target === sModal) closeSearch(); });
  sq.addEventListener('input', runSearch);
  sres.addEventListener('click', e => { const b = e.target.closest('.sitem'); if (b) choose(shown[+b.dataset.x]); });
  document.addEventListener('keydown', e => {
    if (sModal.hidden) return;
    if (e.key === 'Escape') { e.preventDefault(); closeSearch(); }
    else if (e.key === 'Enter' && document.activeElement === sq) { e.preventDefault(); choose(shown[act]); }
    else if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && shown.length) {
      e.preventDefault();
      act = (act + (e.key === 'ArrowDown' ? 1 : shown.length - 1)) % shown.length;
      sres.querySelectorAll('.sitem').forEach((b, i) => b.classList.toggle('act', i === act));
      const cur = sres.querySelector('.sitem.act'); if (cur) cur.scrollIntoView({ block: 'nearest' });
    }
  });

  /* ---------- पूरी वेबसाइट चुनी गई भाषा में दिखाएँ ---------- */
  /* मेनू की पट्टी: आगे और लिंक हों तो दाईं ओर धुंधलापन */
  const linksEl = document.querySelector('.links');
  const fadeLinks = () => linksEl.classList.toggle('more', linksEl.scrollWidth - linksEl.clientWidth - linksEl.scrollLeft > 4);
  linksEl.addEventListener('scroll', fadeLinks, { passive: true });
  window.addEventListener('resize', fadeLinks);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fadeLinks);
  /* फ़ोन पर नीचे स्क्रॉल करते समय ऊपर की पट्टी छिपे, ऊपर स्क्रॉल करते ही वापस आए */
  const navEl = document.querySelector('.nav');
  let lastY = window.scrollY, navRaf = 0;
  window.addEventListener('scroll', () => {
    if (navRaf) return;
    navRaf = requestAnimationFrame(() => {
      navRaf = 0;
      const y = window.scrollY, dy = y - lastY;
      if (Math.abs(dy) < 8) return;
      navEl.classList.toggle('nav-up', dy > 0 && y > 160 && !navEl.contains(document.activeElement));
      lastY = y;
    });
  }, { passive: true });

  function applyLang() {
    applyStatic();
    founderPhoto();
    renderPeople();
    renderStats();
    renderCourses();
    selectArea(sel.s, sel.d);
    donateText();
    $('jerr').textContent = '';
    $('jres').hidden = true;
    if (!sModal.hidden) { index = buildIndex(); runSearch(); }
    fadeLinks();
  }
  document.querySelector('.lang').addEventListener('click', e => {
    const b = e.target.closest('button[data-lang]');
    if (!b || b.dataset.lang === lang) return;
    lang = b.dataset.lang;
    try { localStorage.setItem(KEY, lang); } catch (err) { /* सेव न हो सके तो भी इस बार भाषा बदल जाएगी */ }
    applyLang();
  });

  applyLang();
  document.documentElement.classList.remove('en-wait');

  /* पहली बार खुलने पर अंक 0 से गिनते हुए ऊपर जाएँ */
  if (!reduced) {
    const els = [...document.querySelectorAll('.stat b')];
    let t0 = null;
    const step = t => {
      if (t0 === null) t0 = t;
      const k = Math.min(1, (t - t0) / 1100), e = 1 - Math.pow(1 - k, 3);
      els.forEach(el => { el.textContent = fmt(Math.round(el.dataset.n * e)); });
      if (k < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }
})();
