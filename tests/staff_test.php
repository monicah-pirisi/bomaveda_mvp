<?php
// Run inside the web container:  docker compose exec web php -d allow_url_fopen=1 tests/staff_test.php
// Makes temporary staff and cases, then removes them. Never run against real data.
require __DIR__.'/../app/lib.php';
$base = rtrim($argv[1] ?? 'http://127.0.0.1', '/'); $fail = 0;
function ok(string $n, bool $c): void { global $fail; echo ($c ? 'PASS ' : 'FAIL ').$n."\n"; if (!$c) $fail++; }
function req(string $m, string $p, array $post = [], string $ck = '', string $ip = '198.51.100.50'): array {
  global $base;
  $o = ['http' => ['method' => $m, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10, 'content' => http_build_query($post),
    'header' => "Content-Type: application/x-www-form-urlencoded\r\nX-Forwarded-For: $ip".($ck ? "\r\nCookie: $ck" : '')]];
  $b = (string)@file_get_contents($base.$p, false, stream_context_create($o)); $h = $http_response_header ?? [];
  $code = isset($h[0]) && preg_match('/\s(\d{3})\s/', $h[0], $x) ? (int)$x[1] : 0; $hs = implode("\n", $h);
  return [$code, $b, $hs, preg_match('/Set-Cookie: (bvs=[^;]+)/i', $hs, $y) ? $y[1] : $ck];
}
function csrf_of(string $b): string { return preg_match('/name="csrf" value="([a-f0-9]+)"/', $b, $m) ? $m[1] : ''; }
function login(string $u, string $pw, string $secret, string $ip, ?string $code = null): array {
  [$c, $b, $h, $ck] = req('GET', '/auth', [], '', $ip);
  return req('POST', '/auth', ['csrf' => csrf_of($b), 'username' => $u, 'password' => $pw, 'code' => $code ?? totp($secret, intdiv(time(), 30))], $ck, $ip);
}
foreach ([['0','localhost',false],['0','example.org',true],['0','demo.bomaveda.org',true],['1','localhost',true],['','localhost',true]] as [$r,$d,$want]) {
  putenv("REQUIRE_2FA=$r"); putenv("DOMAIN=$d"); ok("authenticator required? REQUIRE_2FA='$r' DOMAIN=$d -> ".($want ? 'yes' : 'no'), two_factor_required() === $want);
}
putenv('REQUIRE_2FA'); putenv('DOMAIN');
$pw = 'Correct-Horse-1234'; $tag = 't'.bin2hex(random_bytes(3)); $staff = [];
foreach ([['admin','admin',null],['coord','coordinator',null],['lia','liaison','Kilifi North'],['lib','liaison','Malindi'],['adm2','admin',null]] as [$n,$r,$cn]) {
  $staff[$n] = ['u' => "$tag$n", 's' => b32enc(random_bytes(20))]; create_staff($staff[$n]['u'], $r, $cn, $pw, $staff[$n]['s']);
}
$mark = 'DESC'.bin2hex(random_bytes(4)); $startC = (int)db()->query('SELECT COALESCE(MAX(id),0) FROM cases')->fetchColumn();
$kn = create_case('sudden_isolation', 'Kilifi North', 'Tezo', "Case in KN $mark", 'moderate', 'phone', '0711222333', true);
$ml = create_case('emotional_distress', 'Malindi', 'Shella', "Case in Malindi $mark", 'urgent');
$idKn = (int)db()->query("SELECT id FROM cases WHERE code='$kn'")->fetchColumn(); $idMl = (int)db()->query("SELECT id FROM cases WHERE code='$ml'")->fetchColumn();
$h = (string)db()->query("SELECT pass_hash FROM staff WHERE username='{$staff['lia']['u']}'")->fetchColumn();
ok('passwords are stored as hashes', preg_match('/^\$(argon2id|2y)\$/', $h) === 1 && strpos($h, $pw) === false);
[$c,,$hd] = req('GET', '/admin'); ok('admin page sends signed-out visitors to /auth', $c === 302 && stripos($hd, 'location: /auth') !== false);
[$c,$b] = login($staff['lia']['u'], 'wrong-password', $staff['lia']['s'], '192.0.2.10'); ok('wrong password is refused, no detail given', $c === 200 && strpos($b, 'Sign-in failed') !== false);
[$c,$b] = login($staff['lia']['u'], $pw, $staff['lia']['s'], '192.0.2.11', '000000'); ok('wrong authenticator code is refused', $c === 200 && strpos($b, 'Sign-in failed') !== false);
$code = totp($staff['lia']['s'], intdiv(time(), 30));
[$c,$b,$hd,$L] = login($staff['lia']['u'], $pw, $staff['lia']['s'], '192.0.2.12', $code);
ok('correct password and code sign in', $c === 302 && stripos($hd, 'location: /admin') !== false);
ok('session cookie is HttpOnly and SameSite=Strict', stripos($hd, 'httponly') !== false && stripos($hd, 'samesite=strict') !== false);
[$c,$b] = login($staff['lia']['u'], $pw, $staff['lia']['s'], '192.0.2.13', $code); ok('an authenticator code cannot be used twice', $c === 200 && strpos($b, 'Sign-in failed') !== false);
[$c,$b] = req('GET', "/admin?v=case&id=$idKn", [], $L); ok('liaison opens a case in own sub-county', $c === 200 && strpos($b, $mark) !== false);
$csrf = csrf_of($b);
[$c,$bl,$hl] = req('GET', '/admin?v=cases', [], $L);
ok("inbox shows the new request for the liaison's own sub-county only", $c === 200 && strpos($bl, show_code($kn)) !== false && strpos($bl, show_code($ml)) === false);
ok('inbox reloads itself every minute', stripos($hl, 'refresh: 60') !== false);
ok('menu shows how many requests are new', preg_match('/\d+ new/', $bl) === 1);
[$c,$bc] = req('GET', '/admin?v=cases&f=closed', [], $L); ok('closed filter does not list a new request', strpos($bc, show_code($kn)) === false);
[$c,$b] = req('GET', "/admin?v=case&id=$idMl", [], $L); ok('liaison cannot open another sub-county', $c === 403 && strpos($b, $mark) === false);
[$c] = req('GET', '/admin?v=audit', [], $L); ok('liaison cannot open the audit log', $c === 403);
[$c] = req('GET', '/admin?v=mentors', [], $L); ok('liaison cannot open the mentor pool', $c === 403);
[$c] = req('POST', '/admin', ['act' => 'status', 'id' => $idKn, 'status' => 'resolved'], $L); 
ok('request without CSRF token is refused and changes nothing', $c === 403 && db()->query("SELECT status FROM cases WHERE id=$idKn")->fetchColumn() === 'submitted');
[$c] = req('POST', '/admin', ['act' => 'status', 'id' => $idKn, 'status' => 'verified', 'csrf' => $csrf], $L);
ok('status update with token works', $c === 302 && db()->query("SELECT status FROM cases WHERE id=$idKn")->fetchColumn() === 'verified');
[$c,$b] = req('POST', '/admin', ['act' => 'reveal', 'id' => $idKn, 'csrf' => $csrf], $L);
ok('contact stays hidden until revealed, then shows and is logged', $c === 200 && strpos($b, '0711222333') !== false && (int)db()->query("SELECT COUNT(*) FROM audit_log WHERE action='reveal_contact' AND case_id=$idKn")->fetchColumn() === 1);
[$c] = req('POST', '/admin', ['act' => 'msg', 'id' => $idMl, 'body' => 'hello', 'csrf' => $csrf], $L); ok('liaison cannot message a case outside their sub-county', $c === 403);
[$c] = req('POST', '/admin', ['act' => 'esc', 'id' => $idKn, 'agency' => 'County social services', 'reason' => 'Needs school support from the county', 'csrf' => $csrf], $L);
$eid = (int)db()->query('SELECT MAX(id) FROM escalations')->fetchColumn();
ok('liaison can request a referral', $c === 302 && db()->query("SELECT status FROM escalations WHERE id=$eid")->fetchColumn() === 'requested');
[$c] = req('POST', '/admin', ['act' => 'decide', 'id' => $eid, 'd' => 'approved', 'csrf' => $csrf], $L); ok('liaison cannot approve referrals', $c === 403 && db()->query("SELECT status FROM escalations WHERE id=$eid")->fetchColumn() === 'requested');
[, , , $A] = login($staff['admin']['u'], $pw, $staff['admin']['s'], '192.0.2.20');
[$c,$b] = req('GET', "/admin?v=case&id=$idMl", [], $A); $ca = csrf_of($b); ok('admin can open any case', $c === 200 && strpos($b, $mark) !== false);
req('POST', '/admin', ['act' => 'esc', 'id' => $idMl, 'agency' => 'Child protection services', 'reason' => 'Urgent home situation needs a visit', 'csrf' => $ca], $A);
$own = (int)db()->query('SELECT MAX(id) FROM escalations')->fetchColumn();
req('POST', '/admin', ['act' => 'decide', 'id' => $own, 'd' => 'approved', 'csrf' => $ca], $A);
ok('nobody can approve their own referral request', db()->query("SELECT status FROM escalations WHERE id=$own")->fetchColumn() === 'requested');
[, , , $B] = login($staff['adm2']['u'], $pw, $staff['adm2']['s'], '192.0.2.21');
[, $b2] = req('GET', '/admin?v=esc', [], $B); req('POST', '/admin', ['act' => 'decide', 'id' => $own, 'd' => 'approved', 'csrf' => csrf_of($b2)], $B);
ok('a second person can approve it', db()->query("SELECT status FROM escalations WHERE id=$own")->fetchColumn() === 'approved');
[, $b3] = req('GET', '/admin?v=esc', [], $A); req('POST', '/admin', ['act' => 'ref', 'id' => $own, 'reference' => 'Ref 001', 'csrf' => csrf_of($b3)], $A);
ok('admin records the referral', db()->query("SELECT status FROM escalations WHERE id=$own")->fetchColumn() === 'referred');
[, , , $K] = login($staff['coord']['u'], $pw, $staff['coord']['s'], '192.0.2.30');
[$c,$b] = req('GET', "/admin?v=case&id=$idKn", [], $K); ok('coordinator cannot read report text', $c === 403 && strpos($b, $mark) === false);
[$c] = req('GET', '/admin?v=cases', [], $K); ok('coordinator cannot open the request inbox', $c === 403);
[$c,$b] = req('GET', '/admin?v=stats', [], $K); ok('coordinator sees overview counts', $c === 200 && strpos($b, 'Overview') !== false && strpos($b, $mark) === false);
[$c,$b] = req('GET', '/admin?v=esc', [], $K); ok('referral list shows reasons but not report text', $c === 200 && strpos($b, $mark) === false && strpos($b, 'Urgent home situation') !== false);
[$c,$b] = req('GET', '/admin?v=audit', [], $A); ok('admin sees the audit log', $c === 200 && strpos($b, 'reveal_contact') !== false);
[$c,, $hd2] = req('POST', '/admin', ['act' => 'logout', 'csrf' => csrf_of($b)], $A); [$c2] = req('GET', '/admin', [], $A);
ok('sign-out ends the session', $c === 302 && $c2 === 302);
$bad = 0; for ($i = 0; $i < 5; $i++) { [$c,$b] = login($staff['lib']['u'], 'nope-nope-nope', $staff['lib']['s'], "192.0.2.".(40 + $i)); if (strpos($b, 'Sign-in failed') !== false) $bad++; }
[$c,$b] = login($staff['lib']['u'], $pw, $staff['lib']['s'], '192.0.2.99'); ok('account locks after 5 wrong tries, even from a new address', strpos($b, 'Too many tries') !== false && $c === 200);
$ids = implode(',', array_map(fn($x) => (int)$x, db()->query("SELECT id FROM staff WHERE username LIKE '$tag%'")->fetchAll(PDO::FETCH_COLUMN)));
db()->exec("DELETE FROM audit_log WHERE staff_id IN ($ids)"); db()->exec("DELETE FROM staff WHERE id IN ($ids)"); db()->exec("DELETE FROM cases WHERE id > $startC"); db()->exec('DELETE FROM throttle');
echo $fail ? "\n$fail FAILED\n" : "\nAll passed\n"; exit($fail ? 1 : 0);
