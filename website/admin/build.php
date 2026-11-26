<?php
/* सम्राट योद्धा फाउंडेशन — एडमिन में सहेजी जानकारी से वेबसाइट के पेज बनाना */
defined('SYF') or exit;

const HI_MONTHS = ['', 'जनवरी', 'फ़रवरी', 'मार्च', 'अप्रैल', 'मई', 'जून', 'जुलाई', 'अगस्त', 'सितंबर', 'अक्टूबर', 'नवंबर', 'दिसंबर'];
function hindi_date(string $ymd): string {
    $t = strtotime($ymd) ?: time();
    return (int)date('j', $t) . ' ' . HI_MONTHS[(int)date('n', $t)] . ' ' . date('Y', $t);
}
function site_url(array $c): string { return rtrim($c['site']['url'] ?? 'https://samratyodhafoundation.org', '/'); }
function abs_url(array $c, string $p): string {
    if (preg_match('~^https?://~i', $p)) return $p;
    return site_url($c) . '/' . ltrim($p, '/');
}
function json_ld(array $data): string {
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
}
function published_posts(array $posts): array {
    $p = array_values(array_filter($posts, fn($x) => ($x['status'] ?? '') === 'published' && !empty($x['slug'])));
    usort($p, fn($a, $b) => strcmp(($b['date'] ?? '') . ($b['id'] ?? ''), ($a['date'] ?? '') . ($a['id'] ?? '')));
    return $p;
}
function first_grapheme(string $s): string {
    return preg_match('/^\X/u', trim($s), $m) ? $m[0] : '';
}
/* दो भाषाओं वाला छोटा लिखा: English हो तो भाषा बदलने पर वही दिखे */
function bi(string $hi, string $en): string {
    $hi = h($hi); $en = trim($en);
    return $en === '' ? $hi : '<span class="l-hi">' . $hi . '</span><span class="l-en" lang="en">' . h($en) . '</span>';
}
function post_card(array $p, string $prefix, bool $h3 = true): string {
    $img = !empty($p['cover']) ? '<img src="' . h(asset_url($p['cover'], $prefix)) . '" alt="" loading="lazy">' : '<span class="bph" aria-hidden="true">✍</span>';
    $tag = $h3 ? 'h3' : 'h2';
    return '<a class="bcard" href="' . h($prefix . 'blogs/' . $p['slug'] . '/') . '"><span class="bimg">' . $img . '</span><span class="btxt"><time datetime="' . h($p['date']) . '">' . h(hindi_date($p['date'])) . '</time><' . $tag . '>' . h($p['title']) . '</' . $tag . '><span class="bsum">' . h(plain($p['summary'] ?: $p['body'], 150)) . '</span></span></a>';
}
function gallery_html(array $items): string {
    $out = '';
    foreach ($items as $g) {
        $cap = trim($g['cap'] ?? ''); $capEn = trim($g['cap_en'] ?? '');
        $fc = $cap !== '' ? '<figcaption>' . bi($cap, $capEn) . '</figcaption>' : '';
        if (($g['type'] ?? '') === 'youtube' && ($id = youtube_id($g['yt'] ?? ''))) {
            $out .= '<figure class="gitem"><div class="ytbox"><iframe src="https://www.youtube-nocookie.com/embed/' . h($id) . '" title="' . h($cap ?: 'YouTube वीडियो') . '" loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>' . $fc . '</figure>';
        } elseif (($g['type'] ?? '') === 'video' && !empty($g['src'])) {
            $out .= '<figure class="gitem"><video src="' . h($g['src']) . '" controls preload="metadata" playsinline></video>' . $fc . '</figure>';
        } elseif (!empty($g['src'])) {
            $out .= '<figure class="gitem"><a href="' . h($g['src']) . '" target="_blank" rel="noopener"><img src="' . h($g['src']) . '" alt="' . h($cap) . '" loading="lazy"></a>' . $fc . '</figure>';
        }
    }
    return $out;
}

/* ---------- मुख्य पेज (index.html) ---------- */
function render_index(array $c, array $posts, string $V): string {
    $tpl = file_get_contents(__DIR__ . '/tpl/index.tpl.html');
    $t = $c['texts'];
    $f = array_values(array_filter($c['people'] ?? [], fn($p) => trim($p['name'] ?? '') !== ''))[0] ?? ['name' => '', 'role' => ''];
    $t['founder.name'] = ['hi' => h($f['name'] ?? ''), 'en' => h($f['name_en'] ?? '')];
    $t['founder.role'] = ['hi' => h($f['role'] ?? ''), 'en' => h($f['role_en'] ?? '')];
    $t['founder.av'] = ['hi' => h(first_grapheme($f['name'] ?? '')), 'en' => h(first_grapheme($f['name_en'] ?? ''))];
    $gallery = gallery_html($c['gallery'] ?? []);
    $pub = published_posts($posts);
    $flags = ['gallery' => $gallery !== '', 'posts' => count($pub) > 0];
    $tpl = preg_replace_callback('/<!--IF:(\w+)-->(.*?)<!--\/IF:\1-->/s', fn($m) => !empty($flags[$m[1]]) ? $m[2] : '', $tpl);
    $url = site_url($c);
    $sameAs = array_values(array_filter([$c['config']['facebook'] ?? '']));
    $ld = json_ld(['@context' => 'https://schema.org', '@type' => 'NGO', 'name' => 'सम्राट योद्धा फाउंडेशन', 'alternateName' => 'Samrat Yodha Foundation',
        'url' => $url . '/', 'logo' => $url . '/images/logo.webp', 'image' => abs_url($c, $c['config']['ogImage'] ?? 'images/share.jpg'),
        'description' => plain($t['meta.desc']['hi'] ?? ''), 'slogan' => 'शिक्षित बनो, संगठित रहो, आगे बढ़ो',
        'founder' => ['@type' => 'Person', 'name' => plain($f['name'] ?? '')], 'email' => $c['config']['email'] ?? '', 'sameAs' => $sameAs, 'areaServed' => 'IN']);
    $gsv = trim($c['site']['gsv'] ?? '');
    $cards = implode('', array_map(fn($p) => post_card($p, ''), array_slice($pub, 0, 3)));
    $vals = ['V' => $V, 'SITE_URL' => h($url), 'OG_IMAGE' => h(abs_url($c, $c['config']['ogImage'] ?? 'images/share.jpg')),
        'GSV' => $gsv !== '' ? '<meta name="google-site-verification" content="' . h($gsv) . "\">\n" : '', 'JSONLD' => $ld,
        'GALLERY_ITEMS' => $gallery, 'BLOG_CARDS' => $cards];
    return preg_replace_callback('/\{\{(?:(t|a):)?([A-Za-z0-9_.]+)\}\}/', function ($m) use ($t, $vals) {
        if ($m[1] === 't') return $t[$m[2]]['hi'] ?? '';
        if ($m[1] === 'a') return h(plain($t[$m[2]]['hi'] ?? ''));
        return $vals[$m[2]] ?? $m[0];
    }, $tpl);
}

/* ---------- वेबसाइट का डेटा (js/site-data.js) ---------- */
function render_site_data(array $c): string {
    $j = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_PRETTY_PRINT);
    $cfg = $c['config']; $cfg['stats'] = $c['stats']; $cfg['siteUrl'] = site_url($c); $cfg['sampleData'] = false;
    $en = [];
    foreach ($c['texts'] as $k => $v) if (trim($v['en'] ?? '') !== '') $en[$k] = $v['en'];
    $f = array_values(array_filter($c['people'] ?? [], fn($p) => trim($p['name'] ?? '') !== ''))[0] ?? null;
    if ($f) {
        if (trim($f['name_en'] ?? '') !== '') { $en['founder.name'] = h($f['name_en']); $en['founder.av'] = h(first_grapheme($f['name_en'])); }
        if (trim($f['role_en'] ?? '') !== '') $en['founder.role'] = h($f['role_en']);
    }
    return "/* यह फ़ाइल एडमिन पैनल (samratyodhafoundation.org/admin) अपने आप बनाता है। यहाँ सीधे बदलाव न करें। */\n"
        . 'const CONFIG = ' . $j($cfg) . ";\n"
        . 'const PATHSHALAS = ' . $j(array_values($c['pathshalas'] ?? [])) . ";\n"
        . 'const COURSE_TAGS = ' . $j((object)($c['courseTags'] ?? [])) . ";\n"
        . 'const COURSES = ' . $j(array_values($c['courses'] ?? [])) . ";\n"
        . 'const PEOPLE = ' . $j(array_values($c['people'] ?? [])) . ";\n"
        . 'const SITE_EN = ' . $j((object)$en) . ";\n";
}

/* ---------- ब्लॉग पेजों का ढाँचा ---------- */
function blog_shell(array $c, array $o): string {
    $p = $o['prefix']; $V = $o['V']; $url = site_url($c);
    $gsv = trim($c['site']['gsv'] ?? '');
    $nav = '<a href="' . $p . '#about">हमारे बारे में</a><a href="' . $p . '#courses">पाठ्यक्रम</a><a href="' . $p . '#schools">पाठशालाएँ</a><a href="' . $p . 'blogs/" aria-current="' . ($o['list'] ? 'page' : 'false') . '">ब्लॉग</a><a href="' . $p . '#join">जुड़ें</a><a href="' . $p . '#contact">संपर्क</a>';
    return '<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . h($o['title']) . '</title>
<meta name="description" content="' . h($o['desc']) . '">
<meta name="theme-color" content="#5e0f0f">
<link rel="canonical" href="' . h($o['canonical']) . '">
<meta property="og:type" content="' . ($o['list'] ? 'website' : 'article') . '">
<meta property="og:title" content="' . h($o['ogtitle']) . '">
<meta property="og:description" content="' . h($o['desc']) . '">
<meta property="og:url" content="' . h($o['canonical']) . '">
<meta property="og:image" content="' . h($o['image']) . '">
<meta property="og:locale" content="hi_IN">
<meta property="og:site_name" content="सम्राट योद्धा फाउंडेशन">
<meta name="twitter:card" content="summary_large_image">
' . ($gsv !== '' ? '<meta name="google-site-verification" content="' . h($gsv) . "\">\n" : '') . $o['ld'] . '<link rel="icon" type="image/png" href="' . $p . 'images/favicon.png">
<link rel="apple-touch-icon" href="' . $p . 'images/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Mukta:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="' . $p . 'css/style.css?v=' . $V . '">
</head>
<body class="blogpage">
<header class="nav">
  <a class="brand" href="' . $p . '"><img src="' . $p . 'images/logo.webp" alt="सम्राट योद्धा फाउंडेशन का लोगो"><span>सम्राट योद्धा<small>फाउंडेशन</small></span></a>
  <a class="btn gold" href="' . $p . '#donate">दान करें</a>
  <div class="bar2"><nav class="links" aria-label="मुख्य मेनू">' . $nav . '</nav></div>
</header>
<main>
' . $o['body'] . '
</main>
<footer class="band">
  <div class="wrap">
    <div class="fg">
      <div>
        <div class="fb"><img src="' . $p . 'images/logo.webp" alt=""><div><b>सम्राट योद्धा फाउंडेशन</b><span>' . h(plain($c['texts']['foot.by']['hi'] ?? '')) . '</span></div></div>
        <p class="slogan">शिक्षित बनो <b>★</b> संगठित रहो <b>★</b> आगे बढ़ो</p>
        <p>शिक्षा ही शक्ति है।</p>
      </div>
      <nav aria-label="फ़ुटर लिंक"><h3>वेबसाइट</h3><a href="' . $p . '">मुख्य पेज</a><a href="' . $p . '#about">हमारे बारे में</a><a href="' . $p . 'blogs/">ब्लॉग</a></nav>
      <nav aria-label="जुड़ें"><h3>साथ आइए</h3><a href="' . $p . '#join">ज्वाइन सम्राट योद्धा फाउंडेशन</a><a href="' . $p . '#donate">दान करें</a><a href="' . $p . '#contact">संपर्क</a></nav>
    </div>
    <p class="copy">© ' . date('Y') . ' सम्राट योद्धा फाउंडेशन</p>
  </div>
</footer>
</body>
</html>
';
}
function render_blog_list(array $c, array $posts, string $V): string {
    $pub = published_posts($posts);
    $cards = implode('', array_map(fn($p) => post_card($p, '../', false), $pub));
    $body = '<section class="band blogtop"><div class="wrap"><nav class="crumbs" aria-label="आप यहाँ हैं"><a href="../">मुख्य पेज</a> › <span>ब्लॉग</span></nav><h1>सम्राट योद्धा फाउंडेशन का ब्लॉग</h1><p class="lead">शिक्षा, संगठन, संविधान और युवाओं के भविष्य पर हमारे विचार, अनुभव और गाँव-गाँव की पाठशालाओं की खबरें।</p></div></section>
<section class="band"><div class="wrap">' . ($cards !== '' ? '<div class="bgrid">' . $cards . '</div>' : '<p class="lead">जल्द ही यहाँ नए ब्लॉग आएँगे।</p>') . '</div></section>';
    $url = site_url($c) . '/blogs/';
    $ld = json_ld(['@context' => 'https://schema.org', '@type' => 'Blog', 'name' => 'सम्राट योद्धा फाउंडेशन का ब्लॉग', 'url' => $url, 'inLanguage' => 'hi',
        'publisher' => ['@type' => 'NGO', 'name' => 'सम्राट योद्धा फाउंडेशन', 'url' => site_url($c) . '/'],
        'blogPost' => array_map(fn($p) => ['@type' => 'BlogPosting', 'headline' => $p['title'], 'url' => $url . $p['slug'] . '/', 'datePublished' => $p['date']], array_slice($pub, 0, 20))]);
    return blog_shell($c, ['prefix' => '../', 'V' => $V, 'list' => true, 'title' => 'ब्लॉग | सम्राट योद्धा फाउंडेशन', 'ogtitle' => 'सम्राट योद्धा फाउंडेशन का ब्लॉग',
        'desc' => 'सम्राट योद्धा फाउंडेशन के ब्लॉग: शिक्षा, संगठन, संविधान और युवाओं के भविष्य पर विचार और गाँव-गाँव की पाठशालाओं की खबरें।',
        'canonical' => $url, 'image' => abs_url($c, $c['config']['ogImage'] ?? 'images/share.jpg'), 'ld' => $ld, 'body' => $body]);
}
function render_post(array $c, array $p, array $pub, string $V): string {
    $pre = '../../';
    $body = preg_replace('~(\s(?:src|href)=")(?!https?:|/|#|mailto:|tel:)([^"]+)~', '$1' . $pre . '$2', $p['body'] ?? '');
    $others = array_slice(array_values(array_filter($pub, fn($x) => $x['slug'] !== $p['slug'])), 0, 3);
    $url = site_url($c) . '/blogs/' . $p['slug'] . '/';
    $img = !empty($p['cover']) ? abs_url($c, $p['cover']) : abs_url($c, $c['config']['ogImage'] ?? 'images/share.jpg');
    $desc = plain($p['summary'] ?: $p['body'], 160);
    $wa = 'https://wa.me/' . rawurlencode((string)($c['config']['whatsapp'] ?? '')) . '?text=' . rawurlencode('नमस्ते, मैंने आपका ब्लॉग "' . $p['title'] . '" पढ़ा। मैं सम्राट योद्धा फाउंडेशन से जुड़ना चाहता/चाहती हूँ।');
    $html = '<article class="band post"><div class="wrap narrow">
<nav class="crumbs" aria-label="आप यहाँ हैं"><a href="' . $pre . '">मुख्य पेज</a> › <a href="../">ब्लॉग</a></nav>
<h1>' . h($p['title']) . '</h1>
<p class="postmeta"><time datetime="' . h($p['date']) . '">' . h(hindi_date($p['date'])) . '</time> · सम्राट योद्धा फाउंडेशन</p>'
        . (!empty($p['cover']) ? '<img class="cover" src="' . h(asset_url($p['cover'], $pre)) . '" alt="' . h($p['title']) . '">' : '')
        . (trim($p['summary'] ?? '') !== '' ? '<p class="lead postsum">' . h($p['summary']) . '</p>' : '')
        . '<div class="prose">' . $body . '</div>
<aside class="cta"><b>सम्राट योद्धा फाउंडेशन से जुड़ें</b><span>शिक्षक, स्वयंसेवक या सदस्य बनकर गाँव-गाँव शिक्षा पहुँचाने में साथ दें।</span><a class="btn wa" href="' . h($wa) . '" target="_blank" rel="noopener">WhatsApp पर बात करें</a><a class="btn ghost" href="' . $pre . '#join">जुड़ने का फ़ॉर्म</a></aside>
</div></article>'
        . ($others ? '<section class="band blogt"><div class="wrap"><div class="bhead"><h2>और ब्लॉग पढ़ें</h2><a class="more" href="../">सभी ब्लॉग →</a></div><div class="bgrid">' . implode('', array_map(fn($x) => post_card($x, $pre), $others)) . '</div></div></section>' : '');
    $ld = json_ld(['@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $p['title'], 'description' => $desc, 'image' => [$img],
        'datePublished' => $p['date'], 'dateModified' => date('c', (int)($p['updated'] ?? time())), 'inLanguage' => 'hi', 'mainEntityOfPage' => $url,
        'author' => ['@type' => 'Organization', 'name' => 'सम्राट योद्धा फाउंडेशन', 'url' => site_url($c) . '/'],
        'publisher' => ['@type' => 'Organization', 'name' => 'सम्राट योद्धा फाउंडेशन', 'logo' => ['@type' => 'ImageObject', 'url' => site_url($c) . '/images/logo.webp']]]);
    return blog_shell($c, ['prefix' => $pre, 'V' => $V, 'list' => false, 'title' => $p['title'] . ' | सम्राट योद्धा फाउंडेशन', 'ogtitle' => $p['title'],
        'desc' => $desc, 'canonical' => $url, 'image' => $img, 'ld' => $ld, 'body' => $html]);
}
function render_sitemap(array $c, array $pub): string {
    $u = site_url($c);
    $rows = ['<url><loc>' . h($u . '/') . '</loc><lastmod>' . date('Y-m-d') . '</lastmod><priority>1.0</priority></url>',
        '<url><loc>' . h($u . '/blogs/') . '</loc><lastmod>' . h($pub[0]['date'] ?? date('Y-m-d')) . '</lastmod><priority>0.8</priority></url>'];
    foreach ($pub as $p) $rows[] = '<url><loc>' . h($u . '/blogs/' . $p['slug'] . '/') . '</loc><lastmod>' . date('Y-m-d', (int)($p['updated'] ?? strtotime($p['date']))) . '</lastmod><priority>0.7</priority></url>';
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n" . implode("\n", $rows) . "\n</urlset>\n";
}

/* ---------- सब कुछ बनाना ---------- */
function build_site(): array {
    $c = data_load('content.json');
    $posts = data_load('posts.json', []);
    if (empty($c['texts'])) throw new RuntimeException('content.json नहीं मिली');
    $V = date('YmdHis');
    $pub = published_posts($posts);
    $files = [];
    write_atomic(ROOT . '/index.html', render_index($c, $posts, $V)); $files[] = 'index.html';
    write_atomic(ROOT . '/js/site-data.js', render_site_data($c)); $files[] = 'js/site-data.js';
    write_atomic(ROOT . '/blogs/index.html', render_blog_list($c, $posts, $V)); $files[] = 'blogs/index.html';
    $made = [];
    foreach ($pub as $p) {
        if (!preg_match('~^[a-z0-9-]{1,80}$~', $p['slug'])) continue;
        write_atomic(ROOT . '/blogs/' . $p['slug'] . '/index.html', render_post($c, $p, $pub, $V));
        $made[] = $p['slug'];
    }
    foreach (data_load('built-posts.json', []) as $old) {          // हटाए या ड्राफ़्ट किए ब्लॉग के पुराने पेज हटाएँ
        if (in_array($old, $made, true) || !preg_match('~^[a-z0-9-]{1,80}$~', $old)) continue;
        $d = ROOT . '/blogs/' . $old;
        if (is_file($d . '/index.html')) @unlink($d . '/index.html');
        if (is_dir($d) && count(scandir($d)) === 2) @rmdir($d);
    }
    data_save('built-posts.json', $made);
    write_atomic(ROOT . '/sitemap.xml', render_sitemap($c, $pub)); $files[] = 'sitemap.xml';
    write_atomic(ROOT . '/robots.txt', "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /data/\n\nSitemap: " . site_url($c) . "/sitemap.xml\n"); $files[] = 'robots.txt';
    data_save('build.json', ['time' => time(), 'posts' => count($made)]);
    return array_merge($files, array_map(fn($s) => "blogs/$s/index.html", $made));
}
