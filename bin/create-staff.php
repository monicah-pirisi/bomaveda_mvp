<?php
// Usage: docker compose exec web php bin/create-staff.php <username> <admin|coordinator|liaison> [sub-county, for liaisons]
if (PHP_SAPI !== 'cli') exit;
require __DIR__.'/../app/lib.php';
[$u, $role, $con] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? null];
if (!preg_match('/^[a-z0-9._-]{3,40}$/', $u) || !in_array($role, ROLES, true) || ($role === 'liaison' && !isset(cfg('wards')[$con ?? '']))) {
  fwrite(STDERR, "Usage: php bin/create-staff.php <username> <admin|coordinator|liaison> [sub-county for liaison, e.g. \"Kilifi North\"]\n"); exit(1);
}
$a = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789'; $pw = ''; for ($i = 0; $i < 16; $i++) $pw .= $a[random_int(0, strlen($a) - 1)];
$secret = b32enc(random_bytes(20));
create_staff($u, $role, $role === 'liaison' ? $con : null, $pw, $secret);
echo "Created $role '$u'. These are shown once. Store them safely:\n  Password: $pw\n  Authenticator key: $secret\n  Or open in an authenticator app: otpauth://totp/Bomaveda:$u?secret=$secret&issuer=Bomaveda\nChange the password after your first sign-in.\n";
