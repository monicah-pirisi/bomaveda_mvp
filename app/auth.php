<?php
// Staff sign-in: password + authenticator code, session cookie, CSRF token, audit log.
const ROLES = ['admin','coordinator','liaison'];
function is_https(): bool { return ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'; }
function staff_start(): void {
  if (session_status() !== PHP_SESSION_NONE) return;
  ini_set('session.use_cookies', '1'); ini_set('session.use_only_cookies', '1'); ini_set('session.use_strict_mode', '1'); ini_set('session.gc_maxlifetime', '1800');
  session_name('bvs'); session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => is_https()]);
  session_start();
}
function staff_user(): ?array {
  staff_start();
  if (empty($_SESSION['sid'])) return null;
  if (time() - (int)($_SESSION['t'] ?? 0) > 1800) { session_destroy(); return null; } // 30 minutes idle
  $_SESSION['t'] = time();
  $s = db()->prepare('SELECT id,username,role,constituency FROM staff WHERE id=? AND active=1'); $s->execute([$_SESSION['sid']]);
  return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
function csrf(): string { staff_start(); return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_ok(): bool { staff_start(); return hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf'] ?? '')); }
function audit(?int $staff, string $action, ?int $case = null, string $note = ''): void {
  db()->prepare('INSERT INTO audit_log (staff_id,action,case_id,note) VALUES (?,?,?,?)')->execute([$staff, $action, $case, mb_substr($note, 0, 200)]);
}
function b32enc(string $bin): string {
  $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = ''; $o = '';
  foreach (str_split($bin) as $c) $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
  foreach (str_split($bits, 5) as $x) $o .= $a[bindec(str_pad($x, 5, '0'))];
  return $o;
}
function b32dec(string $s): string {
  $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits = ''; $o = '';
  foreach (str_split(strtoupper($s)) as $ch) { $p = strpos($a, $ch); if ($p !== false) $bits .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT); }
  foreach (str_split($bits, 8) as $b) if (strlen($b) === 8) $o .= chr(bindec($b));
  return $o;
}
function totp(string $secret, int $step): string {
  $h = hash_hmac('sha1', pack('J', $step), b32dec($secret), true); $o = ord($h[19]) & 15;
  $n = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
  return str_pad((string)($n % 1000000), 6, '0', STR_PAD_LEFT);
}
function totp_ok(array $u, string $code): bool { // each code works once
  $secret = dec($u['totp_enc']); $now = intdiv(time(), 30);
  foreach ([0, -1, 1] as $d) {
    $s = $now + $d;
    if ($s > (int)$u['last_totp'] && hash_equals(totp($secret, $s), $code)) { db()->prepare('UPDATE staff SET last_totp=? WHERE id=?')->execute([$s, $u['id']]); return true; }
  }
  return false;
}
// Authenticator codes are always required, except on a computer used for local testing:
// REQUIRE_2FA=0 is honoured only while DOMAIN is localhost. On any real domain it is ignored.
function two_factor_required(): bool {
  return !(getenv('REQUIRE_2FA') === '0' && in_array(getenv('DOMAIN') ?: 'localhost', ['localhost', '127.0.0.1'], true));
}
function pass_algo(): string { return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT; }
function create_staff(string $user, string $role, ?string $con, string $pass, string $secret): void {
  db()->prepare('INSERT INTO staff (username,pass_hash,role,constituency,totp_enc) VALUES (?,?,?,?,?)')->execute([$user, password_hash($pass, pass_algo()), $role, $con, enc($secret)]);
}
