<?php
declare(strict_types=1);
require __DIR__.'/i18n.php';
const LANGS = ['en','sw'];
function cfg(string $k): array { static $d; $d ??= require __DIR__.'/data.php'; return $d[$k]; }
function ussd_code(): string { return getenv('USSD_CODE') ?: '*519*41#'; }
function set_lang(string $l): void { $GLOBALS['LANG'] = $l; }
function lang(): ?string {
  if (!empty($GLOBALS['LANG'])) return $GLOBALS['LANG'];
  $l = $_GET['l'] ?? $_POST['l'] ?? ''; return is_string($l) && in_array($l, LANGS, true) ? $l : null;
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function t(string $k): string { global $T; return $T[lang() ?? 'en'][$k] ?? $T['en'][$k] ?? $k; }
function u(string $p): string { return $p.'?l='.(lang() ?? 'en'); }
function db(): PDO {
  static $p; return $p ??= new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('MARIADB_DATABASE').';charset=utf8mb4',
    getenv('MARIADB_USER'), getenv('MARIADB_PASSWORD'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
}
function app_key(): string { return base64_decode((string)getenv('APP_KEY')); }
function enc(string $s): string { $n = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES); return base64_encode($n.sodium_crypto_secretbox($s, $n, app_key())); }
function dec(string $s): string { $r = base64_decode($s); $k = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
  return (string)sodium_crypto_secretbox_open(substr($r, $k), substr($r, 0, $k), app_key()); }
function new_code(): string { $c = ''; for ($i = 0; $i < 12; $i++) $c .= random_int(0, 9); return $c; }
function show_code(string $c): string { return implode('-', str_split($c, 4)); }
// Behind our own proxy (private Docker address), use the visitor address it forwards. Direct public clients are never trusted.
function client_ip(): string {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
  }
  return $ip;
}
function iph(): string { return hash_hmac('sha256', client_ip(), app_key().date('Y-m-d')); }
function throttle(string $b, int $max, int $mins, ?string $who = null): bool {
  db()->exec('DELETE FROM throttle WHERE at < NOW() - INTERVAL 60 MINUTE');
  $s = db()->prepare('SELECT COUNT(*) FROM throttle WHERE h=? AND b=? AND at > NOW() - INTERVAL '.(int)$mins.' MINUTE');
  $s->execute([$who ?? iph(), $b]); return (int)$s->fetchColumn() < $max;
}
function hit(string $b, ?string $who = null): void { db()->prepare('INSERT INTO throttle (h,b) VALUES (?,?)')->execute([$who ?? iph(), $b]); }
// One place that saves a report, used by the web form and by USSD. Returns the case code, or null.
function create_case(string $cat, string $con, string $ward, string $desc, string $urg, string $cm = '', string $cv = '', bool $safe = false, string $kind = 'concern'): ?string {
  $pdo = db(); $pdo->beginTransaction(); $code = null;
  for ($i = 0; $i < 5 && !$code; $i++) {
    $try = new_code();
    try {
      $pdo->prepare('INSERT INTO cases (code,category,constituency,ward,description,urgency,kind) VALUES (?,?,?,?,?,?,?)')->execute([$try, $cat, $con, $ward, enc($desc), $urg, $kind]);
      $code = $try;
    } catch (PDOException $x) { if ($x->getCode() !== '23000') throw $x; }
  }
  if (!$code) { $pdo->rollBack(); return null; }
  if ($cv !== '') $pdo->prepare('INSERT INTO contacts (case_id,method,value_enc,safe_to_call) VALUES (?,?,?,?)')->execute([$pdo->lastInsertId(), $cm, enc($cv), $safe ? 1 : 0]);
  $pdo->commit(); return $code;
}
function find_case(string $raw): ?array {
  $code = preg_replace('/\D/', '', $raw);
  if (strlen($code) !== 12) return null;
  $s = db()->prepare('SELECT id,status,updated_at FROM cases WHERE code=?'); $s->execute([$code]);
  return $s->fetch(PDO::FETCH_ASSOC) ?: null;
}
function page(string $title, callable $body, bool $wide = false, bool $staff = false, string $layout = ''): void {
  header('Cache-Control: no-store'); header('Referrer-Policy: no-referrer'); header('X-Content-Type-Options: nosniff');
  header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
  $l = lang(); $o = $l === 'sw' ? 'en' : 'sw'; $path = '/'.trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'); $pub = $l && !$staff;
  $main = trim(($layout !== '' ? $layout : ($staff ? 'staff' : ($wide ? 'wide' : ''))));
  echo '<!doctype html><html lang="'.($l ?? 'en').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex"><meta name="theme-color" content="#0077A8"><link rel="manifest" href="/manifest.webmanifest"><link rel="icon" type="image/png" href="/assets/icon-192.png"><link rel="apple-touch-icon" href="/assets/icon-192.png"><title>'.e($title).'</title><link rel="stylesheet" href="/assets/style.css"></head><body>';
  echo '<a class="skip" href="#main">'.e(t('skip')).'</a><a id="qe" class="qe" href="https://www.google.com">'.e(t('quick')).'</a>';
  echo '<header class="bar"><div class="bar-in"><a class="brand" href="'.e($pub ? u('/') : '/').'"><img src="/assets/icon-192.png" alt="" width="32" height="32"><span>Bomaveda</span></a>';
  if ($pub) {
    echo '<nav aria-label="'.e(t('navlabel')).'">';
    foreach (['/submit-concern' => 'share', '/request-support' => 'support', '/resources' => 'res', '/case' => 'check', '/about' => 'about'] as $href => $key)
      echo '<a href="'.e(u($href)).'"'.($path === $href ? ' aria-current="page"' : '').'>'.e(t($key)).'</a>';
    echo '<a class="lang" lang="'.$o.'" hreflang="'.$o.'" href="'.e($path.'?l='.$o).'">'.e(t('other')).'</a></nav>';
  }
  echo '</div></header><main id="main" tabindex="-1"'.($main !== '' ? ' class="'.e($main).'"' : '').'>'; $body(); echo '</main>';
  if ($l || $staff) echo '<footer class="foot"><div class="foot-in"><div><p><strong>Bomaveda</strong> '.e(t('ft.tag')).'</p><p>'.e(t('ft.line')).'</p></div><nav aria-label="'.e(t('footlabel')).'"><a href="'.e(u('/about')).'">'.e(t('about')).'</a> <a href="'.e(u('/resources')).'">'.e(t('res')).'</a> <a href="/auth">Coordinator workspace</a>'.(getenv('SIM_ENABLED') === '1' ? ' <a href="'.e(u('/simulator')).'">'.e(t('simlink')).'</a>' : '').'</nav></div></footer>';
  echo '<script src="/assets/app.js"></script></body></html>';
}
require __DIR__.'/auth.php';
