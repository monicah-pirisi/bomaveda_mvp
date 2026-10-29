<?php
// Help for whoever runs the server. Run inside the web container:
//   php bin/staff-tools.php list            show accounts, and whether each authenticator secret can still be read
//   php bin/staff-tools.php unlock          clear sign-in lockouts
//   php bin/staff-tools.php reset <user>    give an existing account a new password and authenticator key (shown once)
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../app/lib.php';
$cmd = $argv[1] ?? '';
if ($cmd === 'list') {
  foreach (db()->query('SELECT username,role,constituency,active,totp_enc FROM staff ORDER BY id') as $r) {
    try { $ok = dec($r['totp_enc']) !== '' ? 'authenticator secret OK' : 'AUTHENTICATOR SECRET UNREADABLE: run reset'; }
    catch (Throwable $x) { $ok = 'AUTHENTICATOR SECRET UNREADABLE: run reset'; }
    printf("%-14s %-12s %-14s %-9s %s\n", $r['username'], $r['role'], $r['constituency'] ?? '-', $r['active'] ? 'active' : 'DISABLED', $ok);
  }
} elseif ($cmd === 'unlock') {
  db()->exec('DELETE FROM throttle'); echo "Sign-in lockouts cleared.\n";
} elseif ($cmd === 'reset' && ($u = (string)($argv[2] ?? '')) !== '') {
  $a = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'; $pw = ''; for ($i = 0; $i < 16; $i++) $pw .= $a[random_int(0, strlen($a) - 1)];
  $secret = b32enc(random_bytes(20));
  $s = db()->prepare('UPDATE staff SET pass_hash=?, totp_enc=?, last_totp=0, active=1 WHERE username=?');
  $s->execute([password_hash($pw, pass_algo()), enc($secret), $u]);
  if (!$s->rowCount()) { fwrite(STDERR, "No account called '$u'.\n"); exit(1); }
  echo "New sign-in details for '$u'. Shown once. Store them safely:\n  Password: $pw\n  Authenticator key: $secret\nRemove the old entry for this account from the authenticator app and add the new key.\n";
} else { fwrite(STDERR, "Usage: php bin/staff-tools.php list | unlock | reset <username>\n"); exit(1); }
