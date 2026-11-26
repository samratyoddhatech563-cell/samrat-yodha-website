<?php
/* सम्राट योद्धा फाउंडेशन — एडमिन पैनल: साझा काम (लॉगिन, फ़ाइलें, सफ़ाई, अपलोड) */
defined('SYF') or exit;

define('ROOT', dirname(__DIR__));
define('DATA', ROOT . '/data');
define('UPLOADS', ROOT . '/uploads');
define('GUARD', "<?php http_response_code(404); exit; ?>\n");

/* ---------- फ़ाइलें पढ़ना-लिखना ---------- */
function write_atomic(string $file, string $body): void {
    $dir = dirname($file);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('फ़ोल्डर नहीं बन सका: ' . $dir);
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $body, LOCK_EX) === false) throw new RuntimeException('फ़ाइल नहीं लिखी जा सकी: ' . basename($file));
    if (!rename($tmp, $file)) { @unlink($tmp); throw new RuntimeException('फ़ाइल नहीं बदली जा सकी: ' . basename($file)); }
    @chmod($file, 0644);
}
function data_load(string $name, $default = []) {
    $f = DATA . '/' . $name;
    if (!is_file($f)) return $default;
    $raw = file_get_contents($f);
    if (strncmp($raw, '<?php', 5) === 0) $raw = substr($raw, strpos($raw, "\n") + 1);
    $v = json_decode($raw, true);
    return is_array($v) ? $v : $default;
}
function data_save(string $name, $value): void {
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) throw new RuntimeException('डेटा सहेजा नहीं जा सका');
    write_atomic(DATA . '/' . $name, (substr($name, -4) === '.php' ? GUARD : '') . $json);
}
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/* ---------- सत्र, लॉगिन, सुरक्षा ---------- */
function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
}
function session_boot(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('syf_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Strict']);
    ini_set('session.use_strict_mode', '1');
    session_start();
    $now = time();
    if (!empty($_SESSION['uid']) && ($now - ($_SESSION['seen'] ?? 0)) > 4 * 3600) { $_SESSION = []; session_regenerate_id(true); }
    $_SESSION['seen'] = $now;
}
function security_headers(): void {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://i.ytimg.com; media-src 'self'; frame-src https://www.youtube-nocookie.com; style-src 'self' 'unsafe-inline'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_ok(): bool {
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF'] ?? '');
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}
function auth_load(): array { return data_load('auth.php', []); }
function logged_in(): bool {
    $a = auth_load();
    return !empty($_SESSION['uid']) && !empty($a['hash']) && hash_equals(substr(hash('sha256', $a['hash']), 0, 16), (string)$_SESSION['uid']);
}
function login_as(array $auth): void {
    session_regenerate_id(true);
    $_SESSION['uid'] = substr(hash('sha256', $auth['hash']), 0, 16);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function client_key(): string {
    return substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')), 0, 20);
}
/* 5 गलत कोशिशों के बाद 15 मिनट की रोक; और सब मिलाकर एक घंटे में 30 गलत कोशिशों पर भी रोक */
function login_blocked(): int {
    $a = data_load('attempts.php', []);
    $now = time();
    $mine = $a['ip'][client_key()] ?? null;
    if ($mine && ($mine['until'] ?? 0) > $now) return (int)ceil(($mine['until'] - $now) / 60);
    if (($a['all']['until'] ?? 0) > $now) return (int)ceil(($a['all']['until'] - $now) / 60);
    return 0;
}
function login_failed(): void {
    $a = data_load('attempts.php', []);
    $now = time(); $k = client_key();
    foreach ($a['ip'] ?? [] as $ik => $v) if (($v['last'] ?? 0) < $now - 86400) unset($a['ip'][$ik]);
    $m = $a['ip'][$k] ?? ['n' => 0, 'until' => 0, 'last' => 0];
    if ($m['last'] < $now - 900) $m['n'] = 0;
    $m['n']++; $m['last'] = $now;
    if ($m['n'] >= 5) { $m['until'] = $now + 900; $m['n'] = 0; }
    $a['ip'][$k] = $m;
    $all = $a['all'] ?? ['n' => 0, 'since' => $now, 'until' => 0];
    if ($all['since'] < $now - 3600) $all = ['n' => 0, 'since' => $now, 'until' => 0];
    $all['n']++;
    if ($all['n'] >= 30) { $all['until'] = $now + 900; $all['n'] = 0; $all['since'] = $now; }
    $a['all'] = $all;
    data_save('attempts.php', $a);
}
function login_ok_reset(): void {
    $a = data_load('attempts.php', []);
    unset($a['ip'][client_key()]);
    data_save('attempts.php', $a);
}

/* ---------- लिखावट की सफ़ाई (सिर्फ़ सुरक्षित टैग रहें) ---------- */
function safe_url(string $u, bool $allowHash = true): ?string {
    $u = trim($u);
    if ($u === '') return null;
    if ($allowHash && $u[0] === '#') return $u;
    if (preg_match('~^(https?:|mailto:|tel:)~i', $u)) return $u;
    if (preg_match('~^[a-z]+:~i', $u)) return null;          // javascript: वगैरह नहीं
    if (strpos($u, '//') === 0) return null;
    return $u;                                                 // सापेक्ष पता
}
function clean_html(string $html, array $allow): string {
    $html = trim(str_replace("\r", '', $html));
    if ($html === '') return '';
    if (strpos($html, '<') === false) return htmlspecialchars($html, ENT_NOQUOTES, 'UTF-8', false);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><!DOCTYPE html><html><body><div id="__r">' . $html . '</div></body></html>', LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors(); libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__r');
    if (!$root) return htmlspecialchars(strip_tags($html), ENT_NOQUOTES, 'UTF-8');
    clean_node($root, $allow, $doc);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}
function clean_node(DOMNode $node, array $allow, DOMDocument $doc): void {
    for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
        $c = $node->childNodes->item($i);
        if ($c instanceof DOMComment || $c instanceof DOMProcessingInstruction) { $node->removeChild($c); continue; }
        if (!($c instanceof DOMElement)) continue;
        $tag = strtolower($c->nodeName);
        if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'base'], true)) { $node->removeChild($c); continue; }
        if (!isset($allow[$tag])) {                       // अनजान टैग: अंदर का लिखा रखें, टैग हटाएँ
            clean_node($c, $allow, $doc);
            while ($c->firstChild) $node->insertBefore($c->firstChild, $c);
            $node->removeChild($c);
            continue;
        }
        $keep = $allow[$tag];
        $attrs = [];
        foreach (iterator_to_array($c->attributes) as $a) $attrs[strtolower($a->name)] = $a->value;
        foreach (array_keys($attrs) as $an) $c->removeAttribute($an);
        foreach ($keep as $an) {
            if (!isset($attrs[$an])) continue;
            $v = $attrs[$an];
            if (in_array($an, ['href', 'src'], true)) { $v = safe_url($v, $an === 'href'); if ($v === null) continue; }
            $c->setAttribute($an, $v);
        }
        if ($tag === 'iframe') {
            $src = $c->getAttribute('src');
            if (!preg_match('~^https://www\.youtube-nocookie\.com/embed/[A-Za-z0-9_-]{11}$~', $src)) { $node->removeChild($c); continue; }
            foreach (['loading' => 'lazy', 'allowfullscreen' => 'allowfullscreen', 'allow' => 'accelerometer; encrypted-media; gyroscope; picture-in-picture', 'referrerpolicy' => 'strict-origin-when-cross-origin'] as $k => $v) $c->setAttribute($k, $v);
            if (!$c->getAttribute('title')) $c->setAttribute('title', 'YouTube वीडियो');
            while ($c->firstChild) $c->removeChild($c->firstChild);
            continue;
        }
        if ($tag === 'img') {
            if (!$c->getAttribute('src')) { $node->removeChild($c); continue; }
            $c->setAttribute('loading', 'lazy');
            if (!$c->hasAttribute('alt')) $c->setAttribute('alt', '');
        }
        if ($tag === 'video') {
            if (!$c->getAttribute('src')) { $node->removeChild($c); continue; }
            $c->setAttribute('controls', 'controls'); $c->setAttribute('preload', 'metadata'); $c->setAttribute('playsinline', 'playsinline');
        }
        if ($tag === 'a' && preg_match('~^https?://~i', $c->getAttribute('href')) && !preg_match('~^https?://(www\.)?samratyodhafoundation\.org~i', $c->getAttribute('href'))) {
            $c->setAttribute('target', '_blank'); $c->setAttribute('rel', 'noopener');
        }
        clean_node($c, $allow, $doc);
    }
}
const INLINE_TAGS = ['b' => [], 'strong' => [], 'i' => [], 'em' => [], 'span' => [], 'small' => [], 'br' => [], 'a' => ['href']];
const RICH_TAGS = ['p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [],
    'ul' => [], 'ol' => [], 'li' => [], 'a' => ['href'], 'blockquote' => [], 'hr' => [], 'figure' => [], 'figcaption' => [],
    'img' => ['src', 'alt'], 'video' => ['src'], 'iframe' => ['src', 'title'], 'div' => [], 'span' => []];
function clean_inline(string $s): string { return clean_html($s, INLINE_TAGS); }
function clean_rich(string $s): string {
    $out = clean_html($s, RICH_TAGS);
    $out = preg_replace('~<(p|div)>\s*(<br>)?\s*</\1>~', '', $out);   // खाली पैराग्राफ़ हटाएँ
    return trim($out);
}
function plain(string $s, int $max = 0): string {
    $t = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>', '</h2>', '</h3>'], ' ', $s)), ENT_QUOTES, 'UTF-8')));
    if ($max && mb_strlen($t) > $max) $t = rtrim(mb_substr($t, 0, $max - 1)) . '…';
    return $t;
}

/* ---------- YouTube और स्लग ---------- */
function youtube_id(string $u): ?string {
    $u = trim($u);
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $u)) return $u;
    if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/))([A-Za-z0-9_-]{11})~', $u, $m)) return $m[1];
    return null;
}
function translit(string $s): string {
    static $map = null;
    if ($map === null) {
        $cons = ['क'=>'k','ख'=>'kh','ग'=>'g','घ'=>'gh','ङ'=>'n','च'=>'ch','छ'=>'chh','ज'=>'j','झ'=>'jh','ञ'=>'n','ट'=>'t','ठ'=>'th','ड'=>'d','ढ'=>'dh','ण'=>'n','त'=>'t','थ'=>'th','द'=>'d','ध'=>'dh','न'=>'n','प'=>'p','फ'=>'ph','ब'=>'b','भ'=>'bh','म'=>'m','य'=>'y','र'=>'r','ल'=>'l','व'=>'v','श'=>'sh','ष'=>'sh','स'=>'s','ह'=>'h','क़'=>'q','ख़'=>'kh','ग़'=>'g','ज़'=>'z','ड़'=>'d','ढ़'=>'dh','फ़'=>'f','ळ'=>'l'];
        $vow = ['अ'=>'a','आ'=>'aa','इ'=>'i','ई'=>'ee','उ'=>'u','ऊ'=>'oo','ऋ'=>'ri','ए'=>'e','ऐ'=>'ai','ओ'=>'o','औ'=>'au','ऑ'=>'o'];
        $mat = ['ा'=>'a','ि'=>'i','ी'=>'ee','ु'=>'u','ू'=>'oo','ृ'=>'ri','े'=>'e','ै'=>'ai','ो'=>'o','ौ'=>'au','ॉ'=>'o','ं'=>'n','ँ'=>'n','ः'=>'h','्'=>'','़'=>''];
        $map = ['c' => $cons, 'v' => $vow, 'm' => $mat];
    }
    if (class_exists('Normalizer')) $s = Normalizer::normalize($s, Normalizer::FORM_C) ?: $s;
    $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
    $out = ''; $n = count($chars);
    for ($i = 0; $i < $n; $i++) {
        $ch = $chars[$i]; $next = $chars[$i + 1] ?? '';
        if ($next === '़' && isset($map['c'][$ch . '़'])) { $ch .= '़'; $i++; $next = $chars[$i + 1] ?? ''; }
        if (isset($map['c'][$ch])) {
            $out .= $map['c'][$ch];
            if (!isset($map['m'][$next]) && $next !== '' && !preg_match('/\s|[-,.!?।]/u', $next)) $out .= 'a';
        } elseif (isset($map['v'][$ch])) $out .= $map['v'][$ch];
        elseif (isset($map['m'][$ch])) $out .= $map['m'][$ch];
        else $out .= $ch;
    }
    return $out;
}
function slugify(string $s): string {
    $s = mb_strtolower(translit($s));
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim(preg_replace('~-+~', '-', $s), '-');
    return substr($s, 0, 70) ?: 'post';
}

/* ---------- अपलोड ---------- */
function upload_limit_mb(): int {
    $f = function ($v) { $v = trim((string)$v); $n = (float)$v; $u = strtolower(substr($v, -1));
        return $u === 'g' ? $n * 1024 : ($u === 'm' ? $n : ($u === 'k' ? $n / 1024 : $n / 1048576)); };
    return (int)floor(min($f(ini_get('upload_max_filesize')), $f(ini_get('post_max_size'))));
}
function save_upload(array $file, string $kind): array {
    if (!isset($file['error']) || is_array($file['error'])) return ['ok' => false, 'error' => 'फ़ाइल नहीं मिली'];
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) return ['ok' => false, 'error' => 'फ़ाइल बहुत बड़ी है। ज़्यादा से ज़्यादा ' . upload_limit_mb() . ' MB की फ़ाइल डाल सकते हैं।'];
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) return ['ok' => false, 'error' => 'अपलोड पूरा नहीं हुआ, दोबारा कोशिश करें।'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $dir = UPLOADS . '/' . date('Y') . '/' . date('m');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return ['ok' => false, 'error' => 'uploads फ़ोल्डर नहीं बन सका'];
    $base = date('d') . '-' . bin2hex(random_bytes(5));
    if ($kind !== 'video' && strpos($mime, 'image/') === 0) {
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true)) return ['ok' => false, 'error' => 'सिर्फ़ JPG, PNG, WEBP या GIF फ़ोटो डालें।'];
        if ($info[0] * $info[1] > 40000000) return ['ok' => false, 'error' => 'फ़ोटो बहुत बड़ी है।'];
        $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$img) return ['ok' => false, 'error' => 'यह फ़ोटो खुल नहीं रही।'];
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $o = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
            $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
            if ($rot) { $r = imagerotate($img, $rot, 0); if ($r) { imagedestroy($img); $img = $r; } }
        }
        $w = imagesx($img); $hh = imagesy($img); $max = 1800;
        if ($w > $max || $hh > $max) {
            $sc = $max / max($w, $hh); $nw = (int)round($w * $sc); $nh = (int)round($hh * $sc);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false); imagesavealpha($dst, true);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $hh);
            imagedestroy($img); $img = $dst;
        }
        $png = in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_GIF], true);
        $name = $base . ($png ? '.png' : '.jpg');
        $path = $dir . '/' . $name;
        if ($png) { imagesavealpha($img, true); $ok = imagepng($img, $path, 7); }
        else {
            $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
            imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
            imageinterlace($bg, true);
            $ok = imagejpeg($bg, $path, 82); imagedestroy($bg);
        }
        imagedestroy($img);
        if (!$ok) return ['ok' => false, 'error' => 'फ़ोटो सहेजी नहीं जा सकी'];
        @chmod($path, 0644);
        return ['ok' => true, 'path' => '/uploads/' . date('Y') . '/' . date('m') . '/' . $name, 'type' => 'image'];
    }
    if ($kind !== 'image' && in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime', 'application/mp4'], true)) {
        $ext = $mime === 'video/webm' ? '.webm' : '.mp4';
        $path = $dir . '/' . $base . $ext;
        if (!move_uploaded_file($file['tmp_name'], $path)) return ['ok' => false, 'error' => 'वीडियो सहेजा नहीं जा सका'];
        @chmod($path, 0644);
        return ['ok' => true, 'path' => '/uploads/' . date('Y') . '/' . date('m') . '/' . $base . $ext, 'type' => 'video'];
    }
    return ['ok' => false, 'error' => $kind === 'image' ? 'सिर्फ़ फ़ोटो (JPG, PNG, WEBP) डालें।' : ($kind === 'video' ? 'सिर्फ़ MP4 या WEBM वीडियो डालें।' : 'यह फ़ाइल नहीं चलेगी। फ़ोटो (JPG/PNG) या वीडियो (MP4) डालें।')];
}
function media_files(): array {
    $out = [];
    if (!is_dir(UPLOADS)) return $out;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOADS, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || $f->getFilename()[0] === '.') continue;
        $ext = strtolower($f->getExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'], true)) continue;
        $rel = '/uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen(UPLOADS) + 1));
        $out[] = ['path' => $rel, 'size' => $f->getSize(), 'time' => $f->getMTime(), 'video' => in_array($ext, ['mp4', 'webm'], true)];
    }
    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}
/* किसी फ़ाइल का रास्ता उस पेज के हिसाब से, जिस पर वह दिखेगी */
function asset_url(string $p, string $prefix): string {
    if ($p === '' || preg_match('~^(https?:)?//|^/|^data:~i', $p)) return $p;
    return $prefix . $p;
}
