<?php
// Autograder cv05: 10 kontrol × 10 bodov. Spúšťa sa v GitHub Actions (.github/workflows/classroom.yml) alebo lokálne: php tests/test.php
declare(strict_types=1);

$root = dirname(__DIR__);
$prefix = '/student/test.user/it/cv05';
$port = 8765;
$score = 0;
$results = [];

function check(string $name, bool $ok, string $hint = ''): void
{
    global $score, $results;
    $score += $ok ? 10 : 0;
    $results[] = sprintf('%s %s%s', $ok ? '✔' : '✘', $name, $ok || $hint === '' ? '' : "  → $hint");
}

/** HTTP požiadavka na php -S; vráti [stavový kód, telo, hlavičky]. Presmerovanie nesleduje. */
function req(string $method, string $path, array $data = []): array
{
    global $prefix, $port;
    $opts = ['method' => $method, 'ignore_errors' => true, 'timeout' => 10, 'follow_location' => 0];
    if ($method === 'POST') {
        $opts['header']  = "Content-Type: application/x-www-form-urlencoded\r\n";
        $opts['content'] = http_build_query($data);
    }
    $ctx = stream_context_create(['http' => $opts]);
    $body = @file_get_contents("http://127.0.0.1:$port$prefix$path", false, $ctx);
    $status = 0;
    $headers = [];
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
            $status = (int) $m[1];
        } elseif (str_contains($h, ':')) {
            [$k, $v] = explode(':', $h, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
    }
    return [$status, (string) $body, $headers];
}

function get(string $path): array
{
    return req('GET', $path);
}

/** Z HTML formulára vyberie polia a hodnoty na odoslanie: text → $text, number → 1, select → prvá option, textarea → $text. */
function formFields(string $html, string $text): array
{
    $fields = [];
    preg_match_all('/<input\b[^>]*>/i', $html, $inputs);
    foreach ($inputs[0] as $tag) {
        if (!preg_match('/name=["\']([^"\']+)["\']/i', $tag, $n)) {
            continue;
        }
        $type = preg_match('/type=["\']([^"\']+)["\']/i', $tag, $t) ? strtolower($t[1]) : 'text';
        if (in_array($type, ['submit', 'button', 'checkbox', 'radio', 'file'], true)) {
            continue;
        }
        $fields[$n[1]] = $type === 'number' ? '1' : ($type === 'hidden' ? (preg_match('/value=["\']([^"\']*)["\']/i', $tag, $v) ? $v[1] : '') : $text);
    }
    preg_match_all('/<select\b[^>]*name=["\']([^"\']+)["\'][^>]*>(.*?)<\/select>/is', $html, $selects, PREG_SET_ORDER);
    foreach ($selects as $s) {
        $fields[$s[1]] = preg_match('/<option\b[^>]*value=["\']([^"\']*)["\']/i', $s[2], $o) ? $o[1] : '1';
    }
    preg_match_all('/<textarea\b[^>]*name=["\']([^"\']+)["\']/i', $html, $tas);
    foreach ($tas[1] as $name) {
        $fields[$name] = $text;
    }
    return $fields;
}

// --- statické kontroly súborov -------------------------------------------------
$cj = @file_get_contents("$root/composer.json") ?: '';
check('composer.json má twig/twig a existuje composer.lock',
    str_contains($cj, '"twig/twig"') && is_file("$root/composer.lock"),
    'composer require twig/twig v Kubeflow; composer.lock ide na GitHub (úloha 6.1)');

$twigs = glob("$root/views/*.twig") ?: [];
$phps  = glob("$root/views/*.php") ?: [];
$extendsOk = true;
foreach ($twigs as $f) {
    if (basename($f) !== 'layout.twig' && !preg_match('/\{%\s*extends\s+["\']layout\.twig["\']\s*%\}/', file_get_contents($f))) {
        $extendsOk = false;
    }
}
$layout = @file_get_contents("$root/views/layout.twig") ?: '';
check('views/: len .twig súbory (aspoň 6), každý s {% extends "layout.twig" %}; layout.twig má {% block content %}',
    count($twigs) >= 6 && count($phps) === 0 && $extendsOk && preg_match('/\{%\s*block\s+content\s*%\}/', $layout),
    count($phps) ? 'vo views/ sú ešte .php súbory: ' . implode(', ', array_map('basename', $phps)) . ' (úloha 6.2)' : 'views/*.twig z twig.zip + about.twig, form.twig (úlohy 6.2, 6.5)');

$hp = @file_get_contents("$root/src/helpers.php") ?: '';
check('src/helpers.php: view() cez Twig\\Environment s funkciou url',
    str_contains($hp, 'Twig\\Environment') && str_contains($hp, 'FilesystemLoader') && preg_match('/TwigFunction\(\s*[\'"]url[\'"]/', $hp) && str_contains($hp, '->render('),
    'src/helpers.php z twig.zip');

$rt = @file_get_contents("$root/src/Router.php") ?: '';
$rq = @file_get_contents("$root/src/Http/Request.php") ?: '';
$rs = @file_get_contents("$root/src/Http/Response.php") ?: '';
check('Router::post(), Request::post() a Response::redirect() existujú',
    str_contains($rt, 'function post(') && str_contains($rq, 'function post(') && str_contains($rq, '$_POST') && str_contains($rs, 'function redirect('),
    'src/Router.php, src/Http/Request.php, src/Http/Response.php z twig.zip');

$ix = @file_get_contents("$root/index.php") ?: '';
$pair = '\[\s*\\\\?[A-Za-z0-9_\\\\]+::class\s*,\s*[\'"][a-zA-Z_][a-zA-Z0-9_]*[\'"]\s*\]';
preg_match_all('/->post\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*' . $pair . '/', $ix, $pm);
preg_match_all('/->get\(\s*[\'"]([^\'"]+\/new)[\'"]\s*,\s*' . $pair . '/', $ix, $nm);
$postPath = $pm[1][0] ?? null;
$newPath  = $nm[1][0] ?? null;
check('index.php: route POST /entita a route GET /entita/new ako [Controller::class, \'metóda\']',
    $postPath !== null && $newPath !== null && str_starts_with($newPath, $postPath . '/'),
    'dve route: $router->get(\'/entita/new\', [..., \'create\']) a $router->post(\'/entita\', [..., \'store\']) (úloha 6.4)');

$insertOk = false;
foreach (glob("$root/src/Model/*Model.php") ?: [] as $f) {
    $c = file_get_contents($f);
    if (str_contains($c, 'function insert(') && preg_match('/INSERT\s+INTO/i', $c) && str_contains($c, 'lastInsertId()')) {
        $insertOk = true;
    }
}
check('model hlavnej entity: insert() s INSERT INTO a lastInsertId()', $insertOk, 'metóda insert(array $data): int v modeli (úloha 6.3)');

// --- HTTP kontroly cez php -S ----------------------------------------------------
$cmd = sprintf('php -S 127.0.0.1:%d %s > /dev/null 2>&1 & echo $!', $port, escapeshellarg("$root/tests/router.php"));
$pid = (int) trim((string) shell_exec($cmd));
usleep(700000);

[$s, $b] = get('/');
[$s2, $b2] = get('/debug?q=tofu');
[$s3, $b3] = get('/toto-neexistuje-' . random_int(1000, 9999));
check('GET / → 200 s menu; GET /debug?q=tofu → 200 s /debug a tofu; neexistujúca adresa → 404',
    $s === 200 && str_contains($b, '<nav') && $s2 === 200 && preg_match('#<td>/debug</td>#', $b2) && preg_match('#<td>tofu</td>#', $b2) && $s3 === 404,
    "stavové kódy: / $s, /debug $s2, neexistujúca $s3; debug() v HomeController má šablóne poslať server a request (úloha 6.2)");

$formHtml = '';
$ok = false; $why = 'chýba route GET /entita/new';
if ($newPath) {
    [$s, $formHtml] = get($newPath);
    $ok = $s === 200 && preg_match('/<form\b[^>]*method=["\']post["\']/i', $formHtml) && stripos($formHtml, '<select') !== false;
    $why = "GET $newPath → stavový kód $s; stránka má mať <form method=\"post\"> so <select> z číselníka (úloha 6.5)";
}
check('GET /entita/new → 200 a formulár method="post" so <select>', $ok, $ok ? '' : $why);

$ok = false; $why = 'formulár sa nedal načítať';
if ($formHtml !== '' && $postPath) {
    $text = 'Autograder ' . random_int(10000, 99999);
    $fields = formFields($formHtml, $text);
    [$s, $b, $h] = req('POST', $postPath, $fields);
    $loc = $h['location'] ?? '';
    $ok = $s === 302 && preg_match('#' . preg_quote($prefix . $postPath, '#') . '/\d+$#', $loc);
    $why = "POST $postPath → stavový kód $s, Location: " . ($loc ?: '(žiadna)') . "; store() má vrátiť Response::redirect('/entita/' . \$id)";
    if ($ok) {
        [$s2, $b2] = get(substr($loc, strlen($prefix)));
        $ok = $s2 === 200 && str_contains($b2, $text);
        $why = "po presmerovaní GET " . substr($loc, strlen($prefix)) . " → stavový kód $s2; detail má obsahovať uložený názov";
    }
}
check('POST /entita s vyplneným formulárom → 302 na /entita/{id}; detail → 200 s uloženým názvom', $ok, $ok ? '' : $why);

$ok = false; $why = 'formulár sa nedal načítať';
if ($formHtml !== '' && $postPath) {
    $fields = formFields($formHtml, '');
    [$s, $b] = req('POST', $postPath, $fields);
    $ok = $s === 200 && preg_match('/<form\b[^>]*method=["\']post["\']/i', $b);
    $why = "POST $postPath s prázdnym názvom → stavový kód $s; store() má vrátiť formulár s hláškou, nie 302 ani 500";
}
check('POST /entita s prázdnym názvom → 200 a formulár znova (kontrola v store())', $ok, $ok ? '' : $why);

if ($pid) {
    exec("kill $pid 2>/dev/null");
}

echo implode("\n", $results), "\n\n", "Body: $score / 100\n";
exit($score === 100 ? 0 : 1);
