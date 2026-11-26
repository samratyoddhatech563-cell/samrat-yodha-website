<?php
/* सम्राट योद्धा फाउंडेशन — एडमिन पैनल */
define('SYF', 1);
require __DIR__ . '/lib.php';
require __DIR__ . '/build.php';
session_boot();
security_headers();

$page = preg_replace('~[^a-z-]~', '', (string)($_GET['p'] ?? 'home')) ?: 'home';
$auth = auth_load();
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

function flash(string $t, bool $ok = true): void { $_SESSION['flash'] = ['t' => $t, 'ok' => $ok]; }
function go(string $p, string $extra = ''): void { header('Location: ./?p=' . $p . $extra); exit; }
function rebuild_or_flash(string $okMsg): void {
    try { build_site(); flash($okMsg . ' वेबसाइट पर बदलाव लग गया।'); }
    catch (Throwable $e) { flash('जानकारी सहेज ली, पर वेबसाइट बनाते समय दिक्कत आई: ' . $e->getMessage(), false); }
}
function post_str(string $k, int $max = 5000): string { $v = $_POST[$k] ?? ''; return is_string($v) ? mb_substr(trim(str_replace("\r", '', $v)), 0, $max) : ''; }
function plain_in($v, int $max = 2000): string { return is_string($v) ? mb_substr(trim(strip_tags(str_replace("\r", '', $v))), 0, $max) : ''; }
function media_ok($p): string {
    $p = is_string($p) ? trim($p) : '';
    return preg_match('~^(/uploads/\d{4}/\d{2}/[A-Za-z0-9._-]+|images/[A-Za-z0-9._-]+)$~', $p) ? $p : '';
}
function rows_in(string $k): array { $v = $_POST[$k] ?? []; return is_array($v) ? array_values(array_filter($v, 'is_array')) : []; }
function lines_in($v): array { return array_values(array_filter(array_map(fn($x) => plain_in($x, 300), explode("\n", is_string($v) ? $v : '')), fn($x) => $x !== '')); }

/* ---------- अपलोड (पेज के अंदर से, JSON जवाब) ---------- */
if ($page === 'upload' || $page === 'mediajson') {
    header('Content-Type: application/json; charset=utf-8');
    if (!logged_in()) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'दोबारा लॉगिन करें']); exit; }
    if ($page === 'mediajson') { echo json_encode(['ok' => true, 'files' => media_files()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
    if (!$isPost || !csrf_ok()) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'पेज पुराना हो गया है, उसे रिफ़्रेश करें']); exit; }
    $kind = in_array($_POST['kind'] ?? '', ['image', 'video', 'any'], true) ? $_POST['kind'] : 'image';
    $r = isset($_FILES['file']) ? save_upload($_FILES['file'], $kind) : ['ok' => false, 'error' => 'फ़ाइल बहुत बड़ी है या पहुँची नहीं। ज़्यादा से ज़्यादा ' . upload_limit_mb() . ' MB।'];
    echo json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit;
}

/* ---------- पहली बार: पासवर्ड बनाना ---------- */
if (empty($auth['hash'])) {
    $codeFile = DATA . '/setup-code.php';
    if (!is_file($codeFile)) {
        $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $code = '';
        for ($i = 0; $i < 8; $i++) $code .= $abc[random_int(0, strlen($abc) - 1)];
        write_atomic($codeFile, GUARD . "SETUP CODE (सेटअप कोड): " . substr($code, 0, 4) . '-' . substr($code, 4) . "\n");
    }
    preg_match('~:\s*([A-Z0-9]{4})-([A-Z0-9]{4})~', (string)file_get_contents($codeFile), $cm);
    $real = ($cm[1] ?? '') . ($cm[2] ?? '');
    $err = '';
    if ($isPost) {
        $given = strtoupper(preg_replace('~[^A-Za-z0-9]~', '', (string)($_POST['code'] ?? '')));
        $p1 = (string)($_POST['pass1'] ?? ''); $p2 = (string)($_POST['pass2'] ?? '');
        if (!csrf_ok()) $err = 'पेज पुराना हो गया है। रिफ़्रेश करके दोबारा भरें।';
        elseif ($m = login_blocked()) $err = "बहुत बार गलत कोड डाला गया। $m मिनट बाद कोशिश करें।";
        elseif ($real === '' || !hash_equals($real, $given)) { login_failed(); $err = 'सेटअप कोड गलत है।'; }
        elseif (mb_strlen($p1) < 8) $err = 'पासवर्ड कम से कम 8 अक्षर का रखें।';
        elseif ($p1 !== $p2) $err = 'दोनों पासवर्ड एक जैसे नहीं हैं।';
        else {
            $auth = ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'created' => time()];
            data_save('auth.php', $auth);
            @unlink($codeFile);
            login_as($auth); login_ok_reset();
            try { build_site(); } catch (Throwable $e) {}
            flash('आपका एडमिन पासवर्ड बन गया। अब आप वेबसाइट बदल सकते हैं।');
            go('home');
        }
    }
    page_top('पहली बार सेटअप', false);
    echo '<div class="card narrow"><h1>एडमिन पैनल: पहली बार सेटअप</h1>
<p>सुरक्षा के लिए पहले एक सेटअप कोड चाहिए, जो सिर्फ़ Hostinger खाते वाले ही देख सकते हैं:</p>
<ol class="steps"><li>Hostinger के File Manager में <b>public_html → data</b> फ़ोल्डर खोलें।</li><li><b>setup-code.php</b> फ़ाइल खोलें। उसमें <b>SETUP CODE</b> के आगे 8 अक्षर का कोड लिखा है।</li><li>वह कोड नीचे डालें और अपना नया पासवर्ड बनाएँ।</li></ol>'
        . ($err ? '<p class="alert bad">' . h($err) . '</p>' : '') . '
<form method="post" autocomplete="off">' . csrf_field() . '
<label>सेटअप कोड<input name="code" required placeholder="जैसे ABCD-2345" autocapitalize="characters"></label>
<label>नया पासवर्ड (कम से कम 8 अक्षर)<input type="password" name="pass1" required minlength="8" autocomplete="new-password"></label>
<label>पासवर्ड दोबारा<input type="password" name="pass2" required minlength="8" autocomplete="new-password"></label>
<button class="btn">पासवर्ड बनाएँ</button></form>
<p class="muted">पासवर्ड कहीं सुरक्षित लिख लें। यह सिर्फ़ आपके पास रहेगा।</p></div>';
    page_bottom(); exit;
}

/* ---------- लॉगिन ---------- */
if ($page === 'logout') {
    if ($isPost && csrf_ok()) { $_SESSION = []; session_regenerate_id(true); }
    go('login');
}
if (!logged_in()) {
    $err = '';
    if ($isPost) {
        if (!csrf_ok()) $err = 'पेज पुराना हो गया है। दोबारा कोशिश करें।';
        elseif ($m = login_blocked()) $err = "बहुत बार गलत पासवर्ड डाला गया। $m मिनट बाद कोशिश करें।";
        elseif (password_verify((string)($_POST['password'] ?? ''), $auth['hash'])) {
            if (password_needs_rehash($auth['hash'], PASSWORD_DEFAULT)) { $auth['hash'] = password_hash((string)$_POST['password'], PASSWORD_DEFAULT); data_save('auth.php', $auth); }
            login_as($auth); login_ok_reset(); go('home');
        } else { login_failed(); $err = 'पासवर्ड गलत है।'; usleep(400000); }
    }
    page_top('लॉगिन', false);
    echo '<div class="card narrow login"><img src="../images/logo.webp" alt="" width="84" height="84"><h1>एडमिन लॉगिन</h1>'
        . ($err ? '<p class="alert bad">' . h($err) . '</p>' : '') . '
<form method="post">' . csrf_field() . '<label>पासवर्ड<input type="password" name="password" required autofocus autocomplete="current-password"></label><button class="btn">लॉगिन करें</button></form>
<p class="muted"><a href="../">← वेबसाइट पर जाएँ</a></p></div>';
    page_bottom(); exit;
}

/* ---------- लॉगिन के बाद: सहेजना ---------- */
$c = data_load('content.json');
$posts = data_load('posts.json', []);
if ($isPost) {
    if (!csrf_ok()) { flash('पेज बहुत देर खुला रहा। जानकारी सहेजी नहीं गई, दोबारा भरें।', false); go($page); }
    try {
        switch ($page) {
            case 'texts':
                $in = $_POST['t'] ?? [];
                foreach ($c['texts'] as $k => $v) {
                    if (!isset($in[$k]) || !is_array($in[$k])) continue;
                    $hi = clean_inline((string)($in[$k]['hi'] ?? ''));
                    if ($hi !== '') $c['texts'][$k]['hi'] = $hi;
                    $c['texts'][$k]['en'] = clean_inline((string)($in[$k]['en'] ?? ''));
                }
                data_save('content.json', $c); rebuild_or_flash('लिखावट सहेज ली।'); go('texts');
            case 'contact':
                $err = [];
                $wa = preg_replace('~\D~', '', post_str('whatsapp'));
                if (strlen($wa) === 10) $wa = '91' . $wa;
                if (!preg_match('~^91[6-9]\d{9}$~', $wa)) $err[] = 'WhatsApp नंबर 10 अंकों का सही मोबाइल नंबर होना चाहिए।';
                $fb = post_str('facebook', 300);
                if ($fb !== '' && !preg_match('~^https://~', $fb)) $err[] = 'Facebook लिंक https:// से शुरू होना चाहिए।';
                $em = post_str('email', 200);
                if ($em !== '' && !filter_var($em, FILTER_VALIDATE_EMAIL)) $err[] = 'ईमेल पता सही नहीं है।';
                $upi = post_str('upi', 100);
                if ($upi !== '' && !preg_match('~^[A-Za-z0-9._-]{2,}@[A-Za-z0-9.-]{2,}$~', $upi)) $err[] = 'UPI ID सही नहीं है (जैसे naam@okhdfcbank)।';
                $url = rtrim(post_str('url', 200), '/');
                if (!preg_match('~^https://[a-z0-9.-]+$~i', $url)) $err[] = 'वेबसाइट का पता https://… होना चाहिए।';
                $gsv = post_str('gsv', 400);
                if (preg_match('~content=["\']([^"\']+)~', $gsv, $gm)) $gsv = $gm[1];
                $gsv = preg_replace('~[^A-Za-z0-9_-]~', '', $gsv);
                if ($err) { flash(implode(' ', $err), false); go('contact'); }
                $c['config'] = array_merge($c['config'], ['whatsapp' => $wa, 'facebook' => $fb, 'email' => $em, 'upi' => $upi,
                    'upiName' => plain_in($_POST['upiName'] ?? '', 80) ?: 'Samrat Yodha Foundation', 'qrImage' => media_ok($_POST['qrImage'] ?? ''), 'ogImage' => media_ok($_POST['ogImage'] ?? '') ?: 'images/share.jpg']);
                foreach (['pathshalas', 'children', 'districts', 'centres'] as $k) $c['stats'][$k] = max(0, min(10000000, (int)($_POST['stats'][$k] ?? 0)));
                $c['site']['url'] = $url; $c['site']['gsv'] = $gsv;
                data_save('content.json', $c); rebuild_or_flash('संपर्क और अंक सहेज लिए।'); go('contact');
            case 'people':
                $out = [];
                foreach (rows_in('items') as $r) {
                    $p = ['name' => plain_in($r['name'] ?? '', 120), 'name_en' => plain_in($r['name_en'] ?? '', 120), 'role' => plain_in($r['role'] ?? '', 160), 'role_en' => plain_in($r['role_en'] ?? '', 160),
                        'about' => plain_in($r['about'] ?? '', 1500), 'about_en' => plain_in($r['about_en'] ?? '', 1500), 'photo' => media_ok($r['photo'] ?? '')];
                    if (implode('', $p) !== '') $out[] = $p;
                }
                $c['people'] = $out; data_save('content.json', $c); rebuild_or_flash('स्लाइडर के लोग सहेज लिए।'); go('people');
            case 'courses':
                $out = [];
                foreach (rows_in('items') as $r) {
                    $x = ['n' => plain_in($r['n'] ?? '', 150), 'w' => plain_in($r['w'] ?? '', 200), 'p' => lines_in($r['p'] ?? ''),
                        'n_en' => plain_in($r['n_en'] ?? '', 150), 'w_en' => plain_in($r['w_en'] ?? '', 200), 'p_en' => lines_in($r['p_en'] ?? '')];
                    if ($x['n'] !== '') $out[] = $x;
                }
                $c['courses'] = $out; data_save('content.json', $c); rebuild_or_flash('पाठ्यक्रम सहेज लिए।'); go('courses');
            case 'pathshalas':
                $out = [];
                foreach (rows_in('items') as $r) {
                    $x = ['type' => ($r['type'] ?? '') === 'सेंटर' ? 'सेंटर' : 'पाठशाला'];
                    foreach (['s', 'd', 'b', 'b_en', 'v', 'v_en', 't', 't_en', 'time', 'time_en', 'since'] as $k) $x[$k] = plain_in($r[$k] ?? '', 120);
                    $x['boys'] = max(0, (int)($r['boys'] ?? 0)); $x['girls'] = max(0, (int)($r['girls'] ?? 0));
                    $x['c'] = array_values(array_filter(array_map('trim', explode(',', plain_in($r['c'] ?? '', 300)))));
                    if ($x['s'] !== '' && $x['d'] !== '') $out[] = $x;
                }
                $c['pathshalas'] = $out; data_save('content.json', $c); rebuild_or_flash('पाठशालाओं की सूची सहेज ली।'); go('pathshalas');
            case 'gallery':
                $out = [];
                foreach (rows_in('items') as $r) {
                    $type = in_array($r['type'] ?? '', ['image', 'video', 'youtube'], true) ? $r['type'] : 'image';
                    $g = ['type' => $type, 'src' => media_ok($r['src'] ?? ''), 'yt' => '', 'cap' => plain_in($r['cap'] ?? '', 200), 'cap_en' => plain_in($r['cap_en'] ?? '', 200)];
                    if ($type === 'youtube') { $g['yt'] = youtube_id((string)($r['yt'] ?? '')) ?? ''; $g['src'] = ''; if ($g['yt'] === '') continue; }
                    elseif ($g['src'] === '') continue;
                    $out[] = $g;
                }
                $c['gallery'] = $out; data_save('content.json', $c); rebuild_or_flash('गैलरी सहेज ली।'); go('gallery');
            case 'post':
                $id = preg_replace('~[^a-f0-9]~', '', (string)($_POST['id'] ?? ''));
                $title = plain_in($_POST['title'] ?? '', 200);
                if ($title === '') { flash('ब्लॉग का शीर्षक लिखें।', false); go('post', $id ? '&id=' . $id : ''); }
                $slug = slugify(post_str('slug', 120) ?: $title);
                $taken = array_column(array_filter($posts, fn($p) => $p['id'] !== $id), 'slug');
                $base = $slug; $n = 2;
                while (in_array($slug, $taken, true)) $slug = $base . '-' . $n++;
                $date = post_str('date', 10);
                if (!preg_match('~^\d{4}-\d{2}-\d{2}$~', $date) || !strtotime($date)) $date = date('Y-m-d');
                $p = ['id' => $id ?: bin2hex(random_bytes(6)), 'title' => $title, 'slug' => $slug, 'date' => $date,
                    'status' => ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published', 'cover' => media_ok($_POST['cover'] ?? ''),
                    'summary' => plain_in($_POST['summary'] ?? '', 300), 'body' => clean_rich((string)($_POST['body'] ?? '')), 'updated' => time()];
                $found = false;
                foreach ($posts as $i => $old) if ($old['id'] === $p['id']) { $posts[$i] = $p; $found = true; }
                if (!$found) $posts[] = $p;
                data_save('posts.json', $posts);
                rebuild_or_flash($p['status'] === 'published' ? 'ब्लॉग प्रकाशित हो गया।' : 'ब्लॉग ड्राफ़्ट में सहेज लिया (वेबसाइट पर अभी नहीं दिखेगा)।');
                go('post', '&id=' . $p['id']);
            case 'post-delete':
                $id = (string)($_POST['id'] ?? '');
                $posts = array_values(array_filter($posts, fn($p) => $p['id'] !== $id));
                data_save('posts.json', $posts); rebuild_or_flash('ब्लॉग हटा दिया।'); go('posts');
            case 'media-delete':
                $p = media_ok($_POST['path'] ?? '');
                $full = $p && strpos($p, '/uploads/') === 0 ? realpath(ROOT . $p) : false;
                if ($full && strpos($full, realpath(UPLOADS) . DIRECTORY_SEPARATOR) === 0 && is_file($full)) { unlink($full); flash('फ़ाइल हटा दी।'); }
                else flash('यह फ़ाइल हटाई नहीं जा सकती।', false);
                go('media');
            case 'password':
                $cur = (string)($_POST['current'] ?? ''); $p1 = (string)($_POST['pass1'] ?? ''); $p2 = (string)($_POST['pass2'] ?? '');
                if (!password_verify($cur, $auth['hash'])) flash('अभी वाला पासवर्ड गलत है।', false);
                elseif (mb_strlen($p1) < 8) flash('नया पासवर्ड कम से कम 8 अक्षर का रखें।', false);
                elseif ($p1 !== $p2) flash('दोनों नए पासवर्ड एक जैसे नहीं हैं।', false);
                else { $auth = ['hash' => password_hash($p1, PASSWORD_DEFAULT), 'created' => time()]; data_save('auth.php', $auth); login_as($auth); flash('पासवर्ड बदल गया।'); }
                go('password');
            case 'rebuild':
                rebuild_or_flash('वेबसाइट दोबारा बना दी।'); go('home');
        }
    } catch (Throwable $e) { flash('दिक्कत आई: ' . $e->getMessage(), false); go($page); }
    go('home');
}

/* ---------- पेज दिखाना ---------- */
function page_top(string $title, bool $menu = true, string $active = ''): void {
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
    echo '<!DOCTYPE html><html lang="hi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">'
        . '<meta name="csrf" content="' . h(csrf_token()) . '"><meta name="upmax" content="' . upload_limit_mb() . '">'
        . '<title>' . h($title) . ' | एडमिन | सम्राट योद्धा फाउंडेशन</title><link rel="icon" href="../images/favicon.png"><link rel="stylesheet" href="assets/admin.css?v=3"></head><body>';
    if ($menu) {
        $items = ['home' => 'डैशबोर्ड', 'texts' => 'लिखावट', 'contact' => 'संपर्क व अंक', 'people' => 'स्लाइडर के लोग', 'courses' => 'पाठ्यक्रम', 'pathshalas' => 'पाठशालाएँ', 'gallery' => 'फ़ोटो-वीडियो गैलरी', 'posts' => 'ब्लॉग', 'media' => 'अपलोड फ़ाइलें', 'password' => 'पासवर्ड'];
        echo '<header class="top"><a class="logo" href="./"><img src="../images/logo.webp" alt="" width="40" height="40"><span>एडमिन पैनल</span></a><details class="menu"><summary>मेनू</summary><nav>';
        foreach ($items as $k => $v) echo '<a href="./?p=' . $k . '"' . ($k === $active ? ' aria-current="page"' : '') . '>' . h($v) . '</a>';
        echo '<a href="../" target="_blank" rel="noopener">वेबसाइट देखें ↗</a><form method="post" action="./?p=logout">' . csrf_field() . '<button>लॉगआउट</button></form></nav></details></header>';
    }
    echo '<main class="main">';
    if ($f) echo '<p class="alert ' . ($f['ok'] ? 'good' : 'bad') . '" role="status">' . h($f['t']) . '</p>';
}
function page_bottom(): void { echo '</main><script src="assets/admin.js?v=3"></script></body></html>'; }
function media_field(string $name, string $value, string $kind = 'image', string $label = 'फ़ोटो'): string {
    return '<div class="media" data-kind="' . $kind . '"><span class="lbl">' . h($label) . '</span><input type="hidden" name="' . h($name) . '" value="' . h($value) . '"><div class="mprev"></div>'
        . '<div class="mbtns"><button type="button" class="btn sm mup">' . ($kind === 'video' ? 'वीडियो अपलोड करें' : ($kind === 'any' ? 'फ़ोटो/वीडियो अपलोड करें' : 'फ़ोटो अपलोड करें')) . '</button><button type="button" class="btn sm ghost mlib">पहले से डाली फ़ाइल चुनें</button><button type="button" class="btn sm ghost mclr">हटाएँ</button></div>'
        . '<input type="file" class="mfile" hidden accept="' . ($kind === 'video' ? 'video/mp4,video/webm' : ($kind === 'any' ? 'image/*,video/mp4,video/webm' : 'image/jpeg,image/png,image/webp,image/gif')) . '"></div>';
}
function field(string $label, string $name, string $value, string $type = 'text', string $extra = ''): string {
    if ($type === 'textarea') return '<label>' . h($label) . '<textarea name="' . h($name) . '" rows="3"' . $extra . '>' . h($value) . '</textarea></label>';
    return '<label>' . h($label) . '<input type="' . $type . '" name="' . h($name) . '" value="' . h($value) . '"' . $extra . '></label>';
}
function repeater(string $title, string $hint, array $rows, callable $render, string $addLabel): void {
    echo '<form method="post" class="rep-form">' . csrf_field() . '<h1>' . h($title) . '</h1><p class="muted">' . $hint . '</p><div class="rep">';
    foreach ($rows as $i => $r) echo '<fieldset class="row"><div class="rowbar"><b class="num"></b><span><button type="button" class="ico up" title="ऊपर करें">↑</button><button type="button" class="ico down" title="नीचे करें">↓</button><button type="button" class="ico del" title="हटाएँ">✕</button></span></div>' . $render('items[' . $i . ']', $r) . '</fieldset>';
    echo '</div><template class="rowtpl"><fieldset class="row"><div class="rowbar"><b class="num"></b><span><button type="button" class="ico up" title="ऊपर करें">↑</button><button type="button" class="ico down" title="नीचे करें">↓</button><button type="button" class="ico del" title="हटाएँ">✕</button></span></div>' . $render('items[__N__]', []) . '</fieldset></template>'
        . '<button type="button" class="btn ghost add">+ ' . h($addLabel) . '</button><div class="savebar"><button class="btn">सहेजें और वेबसाइट पर लगाएँ</button></div></form>';
}

$labels = ['home' => 'डैशबोर्ड'];
switch ($page) {
case 'texts':
    page_top('लिखावट', true, 'texts');
    $groups = [
        'Google और ब्राउज़र टैब' => ['title', 'meta.'], 'ऊपर की पट्टी और मेनू' => ['brand', 'logo.', 'nav.', 'donate', 'search.'],
        'सबसे ऊपर का हिस्सा' => ['sl.', 'hero.', 'slogan', 'join.cta'], 'उपलब्धियाँ' => ['stats.'], 'हमारे बारे में और कार्य-योजना' => ['about.', 'insp.', 'plan.'],
        'पाठ्यक्रम' => ['courses.'], 'गैलरी' => ['gal.'], 'पाठशालाएँ खोजें' => ['schools.', 'f.'], 'ब्लॉग (मुख्य पेज पर)' => ['blogt.'],
        'जुड़ें' => ['join.', 'j.', 'way.'], 'दान' => ['don.', 'd.', 'use.'], 'संपर्क' => ['contact.', 'c.'], 'फ़ुटर (सबसे नीचे)' => ['foot.']];
    $done = [];
    echo '<form method="post">' . csrf_field() . '<h1>वेबसाइट की लिखावट</h1><p class="muted">हर हिस्से का हिंदी और English लिखा यहाँ बदलें। English खाली छोड़ेंगे, तो English में भी हिंदी ही दिखेगी। <code>&lt;b&gt;</code>, <code>&lt;span&gt;</code> जैसे निशान हों, तो उन्हें न हटाएँ।</p>';
    foreach ($groups as $g => $prefixes) {
        $keys = array_filter(array_keys($c['texts']), function ($k) use ($prefixes, &$done) {
            if (isset($done[$k])) return false;
            foreach ($prefixes as $p) if ($k === $p || strpos($k, $p) === 0) return true;
            return false;
        });
        if (!$keys) continue;
        echo '<details class="grp"><summary>' . h($g) . ' <small>(' . count($keys) . ')</small></summary>';
        foreach ($keys as $k) {
            $done[$k] = 1; $v = $c['texts'][$k];
            $rows = max(1, min(6, (int)ceil(mb_strlen($v['hi']) / 60)));
            echo '<div class="tx"><code>' . h($k) . '</code><label>हिंदी<textarea name="t[' . h($k) . '][hi]" rows="' . $rows . '">' . h($v['hi']) . '</textarea></label><label>English<textarea name="t[' . h($k) . '][en]" rows="' . $rows . '">' . h($v['en']) . '</textarea></label></div>';
        }
        echo '</details>';
    }
    $rest = array_diff(array_keys($c['texts']), array_keys($done));
    if ($rest) { echo '<details class="grp"><summary>बाकी</summary>'; foreach ($rest as $k) { $v = $c['texts'][$k]; echo '<div class="tx"><code>' . h($k) . '</code><label>हिंदी<textarea name="t[' . h($k) . '][hi]">' . h($v['hi']) . '</textarea></label><label>English<textarea name="t[' . h($k) . '][en]">' . h($v['en']) . '</textarea></label></div>'; } echo '</details>'; }
    echo '<div class="savebar"><button class="btn">सहेजें और वेबसाइट पर लगाएँ</button></div></form>';
    break;

case 'contact':
    page_top('संपर्क व अंक', true, 'contact');
    $cf = $c['config']; $st = $c['stats'];
    echo '<form method="post">' . csrf_field() . '<h1>संपर्क, अंक और Google</h1>
<section class="card"><h2>संपर्क</h2><p class="muted">WhatsApp के सारे बटन (प्रवेश, जुड़ें, दान, ज़िला, संपर्क) इसी एक नंबर पर जाते हैं। नंबर वेबसाइट पर लिखा नहीं दिखता।</p>'
        . field('WhatsApp नंबर (10 अंक)', 'whatsapp', substr($cf['whatsapp'] ?? '', -10), 'tel', ' inputmode="numeric" required')
        . field('Facebook पेज का लिंक', 'facebook', $cf['facebook'] ?? '', 'url')
        . field('ईमेल', 'email', $cf['email'] ?? '', 'email') . '</section>
<section class="card"><h2>उपलब्धियों के अंक</h2><p class="muted">गाँव अपने आप पाठशालाओं के बराबर, और शिक्षक पाठशालाओं के 1.5 गुना दिखते हैं। कोचिंग सेंटर 0 रखेंगे, तो वह डिब्बा नहीं दिखेगा।</p><div class="g2">'
        . field('पाठशालाएँ', 'stats[pathshalas]', (string)$st['pathshalas'], 'number', ' min="0"') . field('बच्चे पढ़ रहे हैं', 'stats[children]', (string)$st['children'], 'number', ' min="0"')
        . field('ज़िले', 'stats[districts]', (string)$st['districts'], 'number', ' min="0"') . field('कोचिंग सेंटर', 'stats[centres]', (string)($st['centres'] ?? 0), 'number', ' min="0"') . '</div></section>
<section class="card"><h2>दान (UPI)</h2><p class="muted">UPI ID खाली रहेगी, तो दान वाले हिस्से में UPI और QR नहीं दिखेंगे, सिर्फ़ WhatsApp बटन रहेगा।</p>'
        . field('UPI ID', 'upi', $cf['upi'] ?? '', 'text', ' placeholder="जैसे naam@okhdfcbank"') . field('UPI ऐप में दिखने वाला नाम (अंग्रेज़ी अक्षरों में)', 'upiName', $cf['upiName'] ?? '')
        . media_field('qrImage', $cf['qrImage'] ?? '', 'image', 'अपना QR कोड (न डालें तो अपने आप बनेगा)') . '</section>
<section class="card"><h2>Google सर्च और शेयर</h2>'
        . field('वेबसाइट का पता', 'url', $c['site']['url'] ?? '', 'url')
        . field('Google Search Console का verification कोड (वहाँ से मिला पूरा meta टैग भी चिपका सकते हैं)', 'gsv', $c['site']['gsv'] ?? '', 'text', ' placeholder="<meta name=&quot;google-site-verification&quot; …>"')
        . media_field('ogImage', $cf['ogImage'] ?? 'images/share.jpg', 'image', 'WhatsApp/Facebook पर लिंक भेजने पर दिखने वाली फ़ोटो') . '</section>
<div class="savebar"><button class="btn">सहेजें और वेबसाइट पर लगाएँ</button></div></form>';
    break;

case 'people':
    page_top('स्लाइडर के लोग', true, 'people');
    $rows = $c['people'];
    repeater('स्लाइडर के लोग', 'सबसे ऊपर के स्लाइडर में लोगो के बाद ये लोग दिखते हैं। <b>पहला व्यक्ति संस्थापक है</b>, उसका नाम और फ़ोटो "हमारे बारे में" में भी दिखती है। नाम खाली रहेगा, तो वह स्लाइड नहीं दिखेगी।', $rows, function ($n, $r) {
        return '<div class="g2">' . field('नाम', $n . '[name]', $r['name'] ?? '') . field('नाम (English)', $n . '[name_en]', $r['name_en'] ?? '')
            . field('पद', $n . '[role]', $r['role'] ?? '') . field('पद (English)', $n . '[role_en]', $r['role_en'] ?? '') . '</div>'
            . field('परिचय', $n . '[about]', $r['about'] ?? '', 'textarea') . field('परिचय (English)', $n . '[about_en]', $r['about_en'] ?? '', 'textarea')
            . media_field($n . '[photo]', $r['photo'] ?? '', 'image', 'फ़ोटो');
    }, 'नया व्यक्ति जोड़ें');
    break;

case 'courses':
    page_top('पाठ्यक्रम', true, 'courses');
    repeater('पाठ्यक्रम', 'हर पाठ्यक्रम का कार्ड "पाठ्यक्रम" वाले हिस्से में दिखता है। "क्या सिखाते हैं" में हर बात नई लाइन में लिखें।', $c['courses'], function ($n, $r) {
        return '<div class="g2">' . field('पाठ्यक्रम का नाम', $n . '[n]', $r['n'] ?? '') . field('नाम (English)', $n . '[n_en]', $r['n_en'] ?? '')
            . field('किसके लिए', $n . '[w]', $r['w'] ?? '') . field('किसके लिए (English)', $n . '[w_en]', $r['w_en'] ?? '') . '</div><div class="g2">'
            . field('क्या सिखाते हैं (हर बात नई लाइन में)', $n . '[p]', implode("\n", $r['p'] ?? []), 'textarea') . field('English में', $n . '[p_en]', implode("\n", $r['p_en'] ?? []), 'textarea') . '</div>';
    }, 'नया पाठ्यक्रम जोड़ें');
    break;

case 'pathshalas':
    page_top('पाठशालाएँ', true, 'pathshalas');
    echo '<datalist id="states"></datalist>';
    repeater('पाठशालाएँ और कोचिंग सेंटर', 'राज्य और ज़िले का नाम सूची में से चुनें, ताकि वेबसाइट पर ज़िला चुनने पर सही गिनती आए। ज़िला चुनने पर सिर्फ़ "कोचिंग सेंटर" वाले गिने जाते हैं। सूची खाली रहेगी, तो लोगों को WhatsApp पर पूछने का बटन दिखेगा।', $c['pathshalas'], function ($n, $r) {
        $t = $r['type'] ?? 'पाठशाला';
        return '<div class="g2"><label>किस तरह का<select name="' . $n . '[type]"><option' . ($t === 'पाठशाला' ? ' selected' : '') . '>पाठशाला</option><option' . ($t === 'सेंटर' ? ' selected' : '') . '>सेंटर</option></select></label>'
            . field('किस साल से', $n . '[since]', $r['since'] ?? '', 'text', ' inputmode="numeric"')
            . field('राज्य', $n . '[s]', $r['s'] ?? '', 'text', ' list="states" class="st"') . field('ज़िला', $n . '[d]', $r['d'] ?? '', 'text', ' class="dt"')
            . field('ब्लॉक / तहसील', $n . '[b]', $r['b'] ?? '') . field('ब्लॉक (English)', $n . '[b_en]', $r['b_en'] ?? '')
            . field('गाँव', $n . '[v]', $r['v'] ?? '') . field('गाँव (English)', $n . '[v_en]', $r['v_en'] ?? '')
            . field('शिक्षक', $n . '[t]', $r['t'] ?? '') . field('शिक्षक (English)', $n . '[t_en]', $r['t_en'] ?? '')
            . field('पढ़ाई का समय', $n . '[time]', $r['time'] ?? '') . field('समय (English)', $n . '[time_en]', $r['time_en'] ?? '')
            . field('लड़के', $n . '[boys]', (string)($r['boys'] ?? ''), 'number', ' min="0"') . field('लड़कियाँ', $n . '[girls]', (string)($r['girls'] ?? ''), 'number', ' min="0"') . '</div>'
            . field('पाठ्यक्रम (कॉमा लगाकर)', $n . '[c]', implode(', ', $r['c'] ?? []));
    }, 'नई पाठशाला / सेंटर जोड़ें');
    echo '<script src="../js/districts.js"></script>';
    break;

case 'gallery':
    page_top('गैलरी', true, 'gallery');
    repeater('फ़ोटो और वीडियो गैलरी', 'ये फ़ोटो और वीडियो मुख्य पेज के "झलकियाँ" हिस्से में दिखते हैं। गैलरी खाली रहेगी, तो वह हिस्सा नहीं दिखेगा। बड़े वीडियो YouTube पर डालकर उसका लिंक लगाना बेहतर है, इससे वेबसाइट तेज़ चलती है।', $c['gallery'], function ($n, $r) {
        $t = $r['type'] ?? 'image';
        return '<label>क्या डालना है<select name="' . $n . '[type]" class="gtype"><option value="image"' . ($t === 'image' ? ' selected' : '') . '>फ़ोटो</option><option value="video"' . ($t === 'video' ? ' selected' : '') . '>वीडियो (अपलोड)</option><option value="youtube"' . ($t === 'youtube' ? ' selected' : '') . '>YouTube वीडियो (लिंक)</option></select></label>'
            . '<div class="g-file">' . media_field($n . '[src]', $r['src'] ?? '', 'any', 'फ़ाइल') . '</div>'
            . '<div class="g-yt">' . field('YouTube वीडियो का लिंक', $n . '[yt]', !empty($r['yt']) ? 'https://youtu.be/' . $r['yt'] : '', 'url', ' placeholder="https://youtu.be/…"') . '</div>'
            . '<div class="g2">' . field('कैप्शन (एक लाइन)', $n . '[cap]', $r['cap'] ?? '') . field('कैप्शन (English)', $n . '[cap_en]', $r['cap_en'] ?? '') . '</div>';
    }, 'नई फ़ोटो / वीडियो जोड़ें');
    break;

case 'posts':
    page_top('ब्लॉग', true, 'posts');
    $list = $posts; usort($list, fn($a, $b) => strcmp($b['date'] . $b['id'], $a['date'] . $a['id']));
    echo '<div class="headrow"><h1>ब्लॉग</h1><a class="btn" href="./?p=post">+ नया ब्लॉग लिखें</a></div>';
    if (!$list) echo '<p class="card">अभी कोई ब्लॉग नहीं है। "नया ब्लॉग लिखें" दबाकर शुरू करें।</p>';
    echo '<div class="plist">';
    foreach ($list as $p) {
        echo '<div class="pitem"><div><b>' . h($p['title']) . '</b><span class="muted">' . h(hindi_date($p['date'])) . ' · ' . ($p['status'] === 'published' ? '<span class="tag ok">प्रकाशित</span>' : '<span class="tag">ड्राफ़्ट</span>') . '</span></div><div class="pact"><a class="btn sm" href="./?p=post&amp;id=' . h($p['id']) . '">बदलें</a>'
            . ($p['status'] === 'published' ? '<a class="btn sm ghost" href="../blogs/' . h($p['slug']) . '/" target="_blank" rel="noopener">देखें ↗</a>' : '')
            . '<form method="post" action="./?p=post-delete" class="confirm" data-confirm="यह ब्लॉग हमेशा के लिए हटाना है?">' . csrf_field() . '<input type="hidden" name="id" value="' . h($p['id']) . '"><button class="btn sm danger">हटाएँ</button></form></div></div>';
    }
    echo '</div>';
    break;

case 'post':
    $id = (string)($_GET['id'] ?? '');
    $p = ['id' => '', 'title' => '', 'slug' => '', 'date' => date('Y-m-d'), 'status' => 'published', 'cover' => '', 'summary' => '', 'body' => ''];
    foreach ($posts as $x) if ($x['id'] === $id) $p = $x;
    page_top($p['id'] ? 'ब्लॉग बदलें' : 'नया ब्लॉग', true, 'posts');
    echo '<form method="post" class="postform">' . csrf_field() . '<input type="hidden" name="id" value="' . h($p['id']) . '">
<div class="headrow"><h1>' . ($p['id'] ? 'ब्लॉग बदलें' : 'नया ब्लॉग') . '</h1>' . ($p['id'] && $p['status'] === 'published' ? '<a class="btn ghost sm" href="../blogs/' . h($p['slug']) . '/" target="_blank" rel="noopener">वेबसाइट पर देखें ↗</a>' : '') . '</div>
<section class="card">' . field('शीर्षक (Google में यही दिखेगा)', 'title', $p['title'], 'text', ' required maxlength="200"')
        . '<div class="g2">' . field('तारीख़', 'date', $p['date'], 'date') . '<label>स्थिति<select name="status"><option value="published"' . ($p['status'] === 'published' ? ' selected' : '') . '>प्रकाशित (वेबसाइट पर दिखे)</option><option value="draft"' . ($p['status'] === 'draft' ? ' selected' : '') . '>ड्राफ़्ट (अभी छिपा रहे)</option></select></label></div>'
        . field('छोटा सार: 1–2 लाइन, Google और ब्लॉग कार्ड पर दिखेगा', 'summary', $p['summary'], 'textarea', ' maxlength="300" data-count="160"')
        . media_field('cover', $p['cover'], 'image', 'ऊपर की बड़ी फ़ोटो')
        . '<details class="adv"><summary>वेब पता (अपने आप बनता है)</summary>' . field('पता: samratyodhafoundation.org/blogs/…/', 'slug', $p['slug'], 'text', ' pattern="[a-z0-9-]*" placeholder="अंग्रेज़ी छोटे अक्षर और -"') . '</details></section>
<section class="card"><span class="lbl">ब्लॉग का लेख</span><div class="rte"><div class="rtbar" role="toolbar" aria-label="लिखने के औज़ार">
<button type="button" data-cmd="h2">बड़ा शीर्षक</button><button type="button" data-cmd="h3">छोटा शीर्षक</button><button type="button" data-cmd="p">साधारण</button><button type="button" data-cmd="bold"><b>B</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="ul">• सूची</button><button type="button" data-cmd="ol">1. सूची</button><button type="button" data-cmd="quote">❝ उद्धरण</button><button type="button" data-cmd="link">🔗 लिंक</button><button type="button" data-cmd="img">🖼 फ़ोटो</button><button type="button" data-cmd="video">🎬 वीडियो</button><button type="button" data-cmd="yt">▶ YouTube</button><button type="button" data-cmd="hr">― लाइन</button>
</div><div class="rtarea" contenteditable="true" lang="hi" data-ph="यहाँ अपना ब्लॉग लिखें…">' . $p['body'] . '</div><textarea name="body" hidden>' . h($p['body']) . '</textarea><input type="file" class="rtfile" hidden></div></section>
<div class="savebar"><button class="btn">सहेजें</button></div></form>';
    break;

case 'media':
    page_top('अपलोड फ़ाइलें', true, 'media');
    $files = media_files();
    echo '<div class="headrow"><h1>अपलोड की हुई फ़ाइलें</h1></div><section class="card">' . media_field('__lib', '', 'any', 'नई फ़ाइल डालें (फ़ोटो या MP4 वीडियो, ज़्यादा से ज़्यादा ' . upload_limit_mb() . ' MB)') . '</section><div class="mgrid">';
    foreach ($files as $f) {
        echo '<div class="mcell">' . ($f['video'] ? '<video src="' . h($f['path']) . '" preload="metadata" muted></video>' : '<img src="' . h($f['path']) . '" alt="" loading="lazy">')
            . '<span class="muted">' . round($f['size'] / 1024) . ' KB · ' . date('d-m-Y', $f['time']) . '</span>'
            . '<form method="post" action="./?p=media-delete" class="confirm" data-confirm="यह फ़ाइल हटानी है? जहाँ यह लगी है, वहाँ से भी गायब हो जाएगी।">' . csrf_field() . '<input type="hidden" name="path" value="' . h($f['path']) . '"><button class="btn sm danger">हटाएँ</button></form></div>';
    }
    if (!$files) echo '<p class="muted">अभी कोई फ़ाइल नहीं है।</p>';
    echo '</div>';
    break;

case 'password':
    page_top('पासवर्ड', true, 'password');
    echo '<form method="post" class="card narrow">' . csrf_field() . '<h1>पासवर्ड बदलें</h1>'
        . field('अभी वाला पासवर्ड', 'current', '', 'password', ' required autocomplete="current-password"')
        . field('नया पासवर्ड (कम से कम 8 अक्षर)', 'pass1', '', 'password', ' required minlength="8" autocomplete="new-password"')
        . field('नया पासवर्ड दोबारा', 'pass2', '', 'password', ' required minlength="8" autocomplete="new-password"')
        . '<button class="btn">पासवर्ड बदलें</button><p class="muted">पासवर्ड भूल जाएँ, तो Hostinger के File Manager में public_html/data/auth.php फ़ाइल हटा दें। फिर यह पेज खोलने पर नया सेटअप कोड बनेगा।</p></form>';
    break;

default:
    page_top('डैशबोर्ड', true, 'home');
    $b = data_load('build.json', []);
    $pub = count(published_posts($posts));
    $cards = ['texts' => ['लिखावट', 'हर हिस्से का हिंदी और English लिखा'], 'contact' => ['संपर्क व अंक', 'WhatsApp, ईमेल, Facebook, उपलब्धियाँ, UPI, Google'],
        'people' => ['स्लाइडर के लोग', count(array_filter($c['people'], fn($p) => trim($p['name'] ?? '') !== '')) . ' लोग, फ़ोटो के साथ'], 'courses' => ['पाठ्यक्रम', count($c['courses']) . ' पाठ्यक्रम'],
        'pathshalas' => ['पाठशालाएँ', count($c['pathshalas']) . ' पाठशालाएँ/सेंटर'], 'gallery' => ['फ़ोटो-वीडियो गैलरी', count($c['gallery']) . ' फ़ोटो/वीडियो'],
        'posts' => ['ब्लॉग', $pub . ' प्रकाशित, ' . (count($posts) - $pub) . ' ड्राफ़्ट'], 'media' => ['अपलोड फ़ाइलें', count(media_files()) . ' फ़ाइलें']];
    echo '<div class="headrow"><h1>नमस्ते! 🙏</h1><a class="btn" href="./?p=post">+ नया ब्लॉग लिखें</a></div><div class="dash">';
    foreach ($cards as $k => $v) echo '<a class="dcard" href="./?p=' . $k . '"><b>' . h($v[0]) . '</b><span>' . h($v[1]) . '</span></a>';
    echo '</div><section class="card"><h2>Google सर्च में वेबसाइट लाने के लिए</h2><ol class="steps">
<li>हर हफ़्ते 1–2 ब्लॉग लिखें। हर ब्लॉग का अलग पेज बनता है और वह अपने आप sitemap में जुड़ जाता है।</li>
<li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a> में samratyodhafoundation.org जोड़ें। "HTML tag" वाला तरीका चुनें और वहाँ मिला कोड <a href="./?p=contact">संपर्क व अंक</a> में डालें।</li>
<li>Search Console में Sitemaps में <code>sitemap.xml</code> लिखकर Submit करें।</li></ol></section>
<section class="card"><p class="muted">वेबसाइट आख़िरी बार बनी: ' . (!empty($b['time']) ? h(date('d-m-Y H:i', $b['time'])) : '—') . '</p><form method="post" action="./?p=rebuild">' . csrf_field() . '<button class="btn ghost">वेबसाइट दोबारा बनाएँ</button></form></section>';
}
page_bottom();
