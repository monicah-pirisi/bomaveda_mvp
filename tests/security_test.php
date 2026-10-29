<?php
// Run inside the web container:  docker compose exec web php -d allow_url_fopen=1 tests/security_test.php
// Creates test cases, then removes them. Never run against a database holding real reports.
require __DIR__.'/../app/lib.php'; require __DIR__.'/../app/ussd.php';
$base = rtrim($argv[1] ?? 'http://127.0.0.1', '/'); $fail = 0;
function ok(string $n, bool $c, string $x = ''): void { global $fail; echo ($c ? 'PASS ' : 'FAIL ').$n.(!$c && $x ? " ($x)" : '')."\n"; if (!$c) $fail++; }
function http(string $m, string $p, array $post = [], string $ip = '198.51.100.1'): array {
  global $base;
  $o = ['http' => ['method' => $m, 'ignore_errors' => true, 'timeout' => 10, 'header' => "Content-Type: application/x-www-form-urlencoded\r\nX-Forwarded-For: $ip", 'content' => http_build_query($post)]];
  $b = @file_get_contents($base.$p, false, stream_context_create($o)); $h = $http_response_header ?? [];
  return [isset($h[0]) && preg_match('/\s(\d{3})\s/', $h[0], $x) ? (int)$x[1] : 0, (string)$b, implode("\n", $h)];
}
$start = (int)db()->query('SELECT COALESCE(MAX(id),0) FROM cases')->fetchColumn();
$valid = ['l'=>'en','category'=>'sudden_isolation','ward'=>'Kilifi North|Tezo','description'=>'A valid test description','urgency'=>'moderate'];
foreach (['/?l=en','/submit-concern?l=en','/request-support?l=sw','/resources?l=en','/case?l=sw','/auth','/simulator'] as $r) { [$c] = http('GET', $r); ok("page loads: $r", $c === 200); }
[$c] = http('GET', '/nope'); ok('unknown page is 404', $c === 404);
[$c,$b,$h] = http('GET', '/?l=en');
[$c2,$ab] = http('GET', '/about?l=en'); $plain = strip_tags($ab);
$gone = ['ward-level', 'Vetted', '(FUND)', '(CARRY)', '(CONNECT)', 'Haki Africa and KECOSCE', 'left on a shared handset', 'designed around', 'Managers see', 'Trained community liaisons'];
$left = array_filter($gone, fn($x) => stripos($plain, $x) !== false);
ok('About page has none of the removed claims', $left === [], implode(', ', $left));
[$c2,$bs] = http('GET', '/about?l=sw'); ok('Kiswahili About page has no "terrorist groups" wording', stripos($bs, 'kigaidi') === false);
[$c2,$off] = http('GET', '/offline');
if (getenv('USSD_LIVE') === '1') ok('USSD code is shown when USSD is live', strpos($ab, ussd_code()) !== false && strpos($off, ussd_code()) !== false);
else ok('USSD code is hidden until USSD is live', strpos($ab, ussd_code()) === false && strpos($off, ussd_code()) === false);
foreach (cfg('partners') as $pn) ok("partner listed only as 'looking to work alongside': $pn", stripos($plain, $pn) !== false && stripos($plain, 'partner organisations') === false);

ok('home loads', $c === 200);
ok('CSP header', stripos($h, "content-security-policy: default-src 'self'") !== false);
ok('no-store cache header', stripos($h, 'cache-control: no-store') !== false);
ok('nosniff header', stripos($h, 'x-content-type-options: nosniff') !== false);
ok('no PHP version leaked', stripos($h, 'x-powered-by') === false);
[$c,$b] = http('GET', '/?l='.urlencode('"><script>alert(1)</script>'));
ok('language parameter is not reflected', strpos($b, '<script>alert(1)') === false);
[$c,$b] = http('POST', '/case', ['l'=>'en','code'=>'"><script>alert(1)</script>'], '198.51.100.2');
ok('lookup input is not reflected', strpos($b, '<script>alert(1)') === false);
[$c,$b] = http('POST', '/case', ['l'=>'en','code'=>"' OR '1'='1"], '198.51.100.3');
ok('SQL injection in lookup finds nothing', $c === 200 && stripos($b, 'cannot find') !== false);
for ($i = 0; $i < 5; $i++) http('POST', '/case', ['l'=>'en','code'=>'000000000000'], '203.0.113.7');
[$c,$b] = http('POST', '/case', ['l'=>'en','code'=>'000000000000'], '203.0.113.7');
ok('lookup locks after 5 wrong tries', stripos($b, 'wait 15') !== false);
[$c,$b] = http('POST', '/case', ['l'=>'en','code'=>'000000000000'], '203.0.113.8');
ok('a different visitor is not locked out', stripos($b, 'cannot find') !== false);
$mark = 'MARK'.bin2hex(random_bytes(6));
[$c,$b] = http('POST', '/submit-concern', array_merge($valid, ['description'=>"Test $mark <script>x</script> ' OR 1=1 --",'cmethod'=>'phone','cvalue'=>'0700123456']), '198.51.100.20');
$got = preg_match('/(\d{4})-(\d{4})-(\d{4})/', $b, $m); $code = $got ? $m[1].$m[2].$m[3] : '';
ok('report accepted and 12-digit code shown', $got === 1);
$raw = (string)db()->query('SELECT description FROM cases ORDER BY id DESC LIMIT 1')->fetchColumn();
ok('report text is encrypted in the database', $raw !== '' && strpos($raw, $mark) === false);
$cv = (string)db()->query('SELECT value_enc FROM contacts ORDER BY case_id DESC LIMIT 1')->fetchColumn();
ok('contact is encrypted in the database', $cv !== '' && strpos($cv, '0700123456') === false);
[$c,$b] = http('POST', '/submit-concern', ['l'=>'en','category'=>'evil','ward'=>'x|y','description'=>'short','urgency'=>'x'], '198.51.100.21');
ok('invalid report is rejected', stripos($b, 'check your answers') !== false);
[$c,$b] = http('POST', '/submit-concern', array_merge($valid, ['website'=>'http://spam.example']), '198.51.100.22');
ok('honeypot blocks bots', stripos($b, 'Your case number') === false);
[$c] = http('POST', '/ussd.php', ['text'=>'']); ok('USSD gateway hidden without token', $c === 404);
[$c] = http('POST', '/ussd.php?k=wrong', ['text'=>'']); ok('USSD gateway hidden with wrong token', $c === 404);
$tok = (string)getenv('USSD_TOKEN');
if ($tok !== '') { [$c,$b] = http('POST', '/ussd.php?k='.urlencode($tok), ['text'=>'','phoneNumber'=>'+254700000000','sessionId'=>'S1']); ok('USSD gateway works with token', $c === 200 && strpos($b, 'CON ') === 0); }
else echo "SKIP USSD token test (USSD_TOKEN not set)\n";
if (getenv('SIM_ENABLED') === '1') { [$c,$b] = http('POST', '/simulator', ['text'=>'']); ok('simulator answers', $c === 200 && strpos($b, 'CON ') === 0); }
$ok = true; $paths = ['', '9', '1', '1*1', '1*1*1', '1*1*5', '1*1*1*1', '1*1*1*5', '1*1*1*1*Boys are leaving school', '1*1*1*1*0', '2*1*2*1*5*1*Wavulana wanaacha shule', '1*3', '1*4', '2*3', '1*0', '1*2', '1*2*123412341234', '1*1*7', '1*1*1*9'];
if ($code) $paths[] = '1*2*'.$code;
foreach ($paths as $p) {
  $o = ussd_run($p); $n = preg_match_all('/^\d\. /m', $o);
  if (!preg_match('/^(CON|END) /', $o) || mb_strlen($o) > 182 || $n > 5) { $ok = false; echo "  bad screen for '$p': ".mb_strlen($o)." chars, $n options\n"; }
}
ok('every USSD screen is under 182 characters with at most 5 options', $ok);
ok('USSD report path creates a case', strpos(ussd_run('1*1*1*1*Boys are leaving school'), 'END ') === 0 && stripos(ussd_run('1*1*1*1*0'), 'EMERGENCY') !== false);
if ($code) ok('USSD status check finds a real case', stripos(ussd_run('1*2*'.$code), 'Status') !== false);
$out = ussd_run("1*1*1*1*x'; DROP TABLE cases;--");
ok('SQL injection through USSD is harmless', (int)db()->query('SELECT COUNT(*) FROM cases')->fetchColumn() > 0);
$src = file_get_contents(__DIR__.'/../public/ussd.php').file_get_contents(__DIR__.'/../app/ussd.php').file_get_contents(__DIR__.'/../app/pages/simulator.php');
ok('USSD code never reads caller number or session id', !preg_match('/\$_(POST|GET|REQUEST|SERVER)\[\s*[\'"](phoneNumber|sessionId|msisdn|serviceCode)/i', $src));
$dump = json_encode(db()->query('SELECT * FROM throttle')->fetchAll(PDO::FETCH_ASSOC));
ok('rate-limit table holds hashes, never raw IPs', strpos($dump, '203.0.113') === false && strpos($dump, '198.51.100') === false);
foreach (['/.env', '/app/lib.php', '/bin/create-staff.php', '/bin/staff-tools.php', '/db/schema.sql', '/Dockerfile', '/tests/security_test.php'] as $pth) {
  [$c,$b] = http('GET', $pth); ok("not downloadable: $pth", strpos($b, '<?php') === false && strpos($b, 'APP_KEY') === false && strpos($b, 'MARIADB_') === false);
}
db()->exec("DELETE FROM cases WHERE id > $start"); db()->exec('DELETE FROM throttle');
echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n"; exit($fail ? 1 : 0);
