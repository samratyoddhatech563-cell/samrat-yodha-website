<?php
/* सिर्फ़ कमांड लाइन से: php admin/cli-build.php */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('SYF', 1);
require __DIR__ . '/lib.php';
require __DIR__ . '/build.php';
foreach (build_site() as $f) echo $f, "\n";
