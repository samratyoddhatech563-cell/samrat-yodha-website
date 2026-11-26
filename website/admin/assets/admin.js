/* सम्राट योद्धा फाउंडेशन — एडमिन पैनल का कोड */
(function () {
  'use strict';
  const csrf = (document.querySelector('meta[name=csrf]') || {}).content || '';
  const upmax = +((document.querySelector('meta[name=upmax]') || {}).content || 0);
  const src = p => (!p || /^(https?:|\/|data:)/.test(p)) ? p : '../' + p;
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  /* ---------- मेनू: बड़ी स्क्रीन पर हमेशा खुला, फ़ोन पर बटन से ---------- */
  const menu = document.querySelector('details.menu');
  if (menu) {
    const wide = matchMedia('(min-width:1100px)');
    const sync = () => { menu.open = wide.matches; };
    sync(); wide.addEventListener('change', sync);
    document.addEventListener('click', e => { if (!wide.matches && menu.open && !menu.contains(e.target)) menu.open = false; });
  }

  /* ---------- फ़ाइल अपलोड ---------- */
  function upload(file, kind) {
    if (upmax && file.size > upmax * 1048576) return Promise.reject(new Error('फ़ाइल बहुत बड़ी है। ज़्यादा से ज़्यादा ' + upmax + ' MB की फ़ाइल डालें। बड़ा वीडियो हो तो YouTube पर डालकर लिंक लगाएँ।'));
    const fd = new FormData();
    fd.append('file', file); fd.append('kind', kind); fd.append('csrf', csrf);
    return fetch('./?p=upload', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json().catch(() => ({ ok: false, error: 'अपलोड नहीं हुआ (फ़ाइल शायद बहुत बड़ी है)।' })))
      .then(j => { if (!j.ok) throw new Error(j.error || 'अपलोड नहीं हुआ'); return j; });
  }
  function busy(btn, on, label) {
    if (on) { btn.dataset.l = btn.textContent; btn.textContent = label || 'अपलोड हो रहा है…'; btn.disabled = true; }
    else { btn.textContent = btn.dataset.l || btn.textContent; btn.disabled = false; }
  }

  /* ---------- पहले से डाली फ़ाइलों में से चुनना ---------- */
  let libDlg = null;
  function pickFromLibrary(kind) {
    return new Promise(resolve => {
      if (!libDlg) {
        libDlg = document.createElement('dialog');
        libDlg.className = 'lib';
        libDlg.innerHTML = '<div class="libhead"><b>फ़ाइल चुनें</b><button type="button" class="btn sm ghost lclose">बंद करें</button></div><div class="libgrid"></div>';
        document.body.appendChild(libDlg);
        libDlg.querySelector('.lclose').addEventListener('click', () => libDlg.close(''));
      }
      const grid = libDlg.querySelector('.libgrid');
      grid.innerHTML = '<p class="muted">लोड हो रहा है…</p>';
      libDlg.returnValue = '';
      libDlg.showModal();
      fetch('./?p=mediajson', { credentials: 'same-origin' }).then(r => r.json()).then(j => {
        const files = (j.files || []).filter(f => kind === 'any' || (kind === 'video') === f.video);
        grid.innerHTML = files.length ? '' : '<p class="muted">अभी कोई फ़ाइल नहीं है।</p>';
        files.forEach(f => {
          const b = document.createElement('button');
          b.type = 'button'; b.className = 'libitem';
          b.innerHTML = f.video ? '<video src="' + esc(f.path) + '" preload="metadata" muted></video>' : '<img src="' + esc(f.path) + '" alt="" loading="lazy">';
          b.addEventListener('click', () => libDlg.close(f.path));
          grid.appendChild(b);
        });
      });
      libDlg.addEventListener('close', function once() { libDlg.removeEventListener('close', once); resolve(libDlg.returnValue || ''); });
    });
  }

  /* ---------- फ़ोटो/वीडियो वाला डिब्बा ---------- */
  function initMedia(box) {
    if (box.dataset.ready) return; box.dataset.ready = 1;
    const input = box.querySelector('input[type=hidden]'), prev = box.querySelector('.mprev'), file = box.querySelector('.mfile');
    const kind = box.dataset.kind || 'image';
    const show = () => {
      const v = input.value;
      prev.innerHTML = !v ? '<span class="muted">कोई फ़ाइल नहीं</span>' : /\.(mp4|webm)$/i.test(v) ? '<video src="' + esc(src(v)) + '" controls preload="metadata"></video>' : '<img src="' + esc(src(v)) + '" alt="">';
      box.querySelector('.mclr').hidden = !v;
    };
    show();
    box.querySelector('.mup').addEventListener('click', () => file.click());
    file.addEventListener('change', () => {
      const f = file.files[0]; if (!f) return;
      const b = box.querySelector('.mup'); busy(b, true);
      upload(f, kind).then(j => { input.value = j.path; show(); if (input.name === '__lib') location.reload(); })
        .catch(e => alert(e.message)).finally(() => { busy(b, false); file.value = ''; });
    });
    box.querySelector('.mlib').addEventListener('click', () => pickFromLibrary(kind).then(p => { if (p) { input.value = p; show(); } }));
    box.querySelector('.mclr').addEventListener('click', () => { input.value = ''; show(); });
  }
  document.querySelectorAll('.media').forEach(initMedia);

  /* ---------- जोड़ने-हटाने वाली सूचियाँ ---------- */
  document.querySelectorAll('.rep-form').forEach(form => {
    const list = form.querySelector('.rep'), tpl = form.querySelector('.rowtpl');
    const renumber = () => list.querySelectorAll(':scope > .row').forEach((r, i) => { r.querySelector('.num').textContent = '#' + (i + 1); });
    const wire = row => {
      row.querySelectorAll('.media').forEach(initMedia);
      row.querySelectorAll('.gtype').forEach(gtype);
      row.querySelectorAll('input.st').forEach(stateField);
    };
    list.querySelectorAll(':scope > .row').forEach(wire);
    renumber();
    form.querySelector('.add').addEventListener('click', () => {
      const html = tpl.innerHTML.replace(/__N__/g, 'n' + Date.now());
      const tmp = document.createElement('div'); tmp.innerHTML = html;
      const row = tmp.firstElementChild; list.appendChild(row); wire(row); renumber();
      const f = row.querySelector('input:not([type=hidden]),textarea,select'); if (f) f.focus();
    });
    list.addEventListener('click', e => {
      const b = e.target.closest('button.ico'); if (!b) return;
      const row = b.closest('.row');
      if (b.classList.contains('del')) { if (confirm('यह हटाना है? सहेजें दबाने पर पक्का होगा।')) row.remove(); }
      else if (b.classList.contains('up') && row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
      else if (b.classList.contains('down') && row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
      renumber();
    });
  });

  /* गैलरी: फ़ोटो/वीडियो या YouTube के हिसाब से सही डिब्बा दिखे */
  function gtype(sel) {
    const row = sel.closest('.row');
    const upd = () => { const yt = sel.value === 'youtube'; row.querySelector('.g-file').hidden = yt; row.querySelector('.g-yt').hidden = !yt; };
    sel.addEventListener('change', upd); upd();
  }

  /* पाठशालाएँ: राज्य चुनने पर उसके ज़िलों की सूची */
  const states = (typeof STATES !== 'undefined') ? STATES.map(s => ({ n: s.n.split('|')[0], d: String(s.d || '').split(',').map(x => x.split('|')[0].trim()).filter(Boolean) })) : [];
  const dl = document.getElementById('states');
  if (dl && states.length) dl.innerHTML = states.map(s => '<option value="' + esc(s.n) + '">').join('');
  function stateField(inp) {
    if (!states.length) return;
    const row = inp.closest('.row'), d = row.querySelector('input.dt');
    const id = 'dl' + Math.random().toString(36).slice(2);
    const list = document.createElement('datalist'); list.id = id; row.appendChild(list); d.setAttribute('list', id);
    const upd = () => { const s = states.find(x => x.n === inp.value.trim()); list.innerHTML = s ? s.d.map(x => '<option value="' + esc(x) + '">').join('') : ''; };
    inp.addEventListener('input', upd); upd();
  }

  /* ---------- पक्का करें? ---------- */
  document.querySelectorAll('form.confirm').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  /* ---------- अक्षर गिनती ---------- */
  document.querySelectorAll('[data-count]').forEach(t => {
    const s = document.createElement('small'); s.className = 'count'; t.after(s);
    const upd = () => { s.textContent = t.value.length + ' अक्षर (Google में लगभग ' + t.dataset.count + ' तक दिखते हैं)'; };
    t.addEventListener('input', upd); upd();
  });

  /* ---------- ब्लॉग लिखने का डिब्बा ---------- */
  document.querySelectorAll('.rte').forEach(rte => {
    const area = rte.querySelector('.rtarea'), ta = rte.querySelector('textarea'), file = rte.querySelector('.rtfile');
    const form = rte.closest('form');
    let range = null;
    const save = () => { const s = getSelection(); if (s.rangeCount && area.contains(s.anchorNode)) range = s.getRangeAt(0).cloneRange(); };
    const restore = () => { area.focus(); if (range) { const s = getSelection(); s.removeAllRanges(); s.addRange(range); } };
    area.addEventListener('keyup', save); area.addEventListener('mouseup', save); area.addEventListener('input', save);
    if (!area.innerHTML.trim()) area.innerHTML = '<p><br></p>';
    document.execCommand('defaultParagraphSeparator', false, 'p');
    const insert = html => { restore(); document.execCommand('insertHTML', false, html); save(); };
    let fileKind = 'image';
    rte.querySelector('.rtbar').addEventListener('mousedown', e => { if (e.target.closest('button')) e.preventDefault(); });
    rte.querySelector('.rtbar').addEventListener('click', e => {
      const b = e.target.closest('button[data-cmd]'); if (!b) return;
      const c = b.dataset.cmd;
      restore();
      if (c === 'h2' || c === 'h3' || c === 'p') document.execCommand('formatBlock', false, c);
      else if (c === 'quote') document.execCommand('formatBlock', false, 'blockquote');
      else if (c === 'bold' || c === 'italic') document.execCommand(c);
      else if (c === 'ul') document.execCommand('insertUnorderedList');
      else if (c === 'ol') document.execCommand('insertOrderedList');
      else if (c === 'hr') insert('<hr><p><br></p>');
      else if (c === 'link') {
        const u = prompt('लिंक का पता डालें (https:// से शुरू):', 'https://');
        if (u && /^(https?:\/\/|mailto:|tel:|\/)/.test(u.trim())) document.execCommand('createLink', false, u.trim());
      } else if (c === 'yt') {
        const u = prompt('YouTube वीडियो का लिंक चिपकाएँ:');
        const m = u && u.match(/(?:youtu\.be\/|v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{11})/);
        if (m) insert('<iframe src="https://www.youtube-nocookie.com/embed/' + m[1] + '" title="YouTube वीडियो" allowfullscreen></iframe><p><br></p>');
        else if (u) alert('यह YouTube लिंक नहीं पहचाना गया।');
      } else if (c === 'img' || c === 'video') {
        fileKind = c === 'img' ? 'image' : 'video';
        file.accept = c === 'img' ? 'image/jpeg,image/png,image/webp,image/gif' : 'video/mp4,video/webm';
        file.click();
      }
      save();
    });
    file.addEventListener('change', () => {
      const f = file.files[0]; if (!f) return;
      const b = rte.querySelector('[data-cmd=' + (fileKind === 'image' ? 'img' : 'video') + ']'); busy(b, true, '…');
      upload(f, fileKind).then(j => {
        if (j.type === 'video') insert('<video src="' + esc(j.path) + '" controls></video><p><br></p>');
        else {
          const cap = prompt('फ़ोटो के नीचे छोटा कैप्शन लिखें (न चाहें तो खाली छोड़ें):', '') || '';
          insert('<figure><img src="' + esc(j.path) + '" alt="' + esc(cap) + '">' + (cap ? '<figcaption>' + esc(cap) + '</figcaption>' : '') + '</figure><p><br></p>');
        }
      }).catch(e => alert(e.message)).finally(() => { busy(b, false); file.value = ''; });
    });
    /* बाहर से चिपकाया लिखा सादा बनकर आए (WhatsApp/Word की फ़ॉर्मेटिंग न आए) */
    area.addEventListener('paste', e => {
      const t = (e.clipboardData || window.clipboardData).getData('text/plain');
      if (!t) return;
      e.preventDefault();
      const html = t.replace(/\r/g, '').split(/\n{2,}/).map(p => '<p>' + esc(p).replace(/\n/g, '<br>') + '</p>').join('');
      document.execCommand('insertHTML', false, html);
    });
    form.addEventListener('submit', () => { ta.value = area.innerHTML; });
  });

  /* बिना सहेजे पेज छोड़ने पर चेतावनी */
  document.querySelectorAll('main form:not(.confirm)').forEach(f => {
    let dirty = false;
    f.addEventListener('input', () => { dirty = true; });
    f.addEventListener('click', e => { if (e.target.closest('.add,.ico,.mclr')) dirty = true; });
    f.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', e => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });
})();
