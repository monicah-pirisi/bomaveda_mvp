<?php
// Coordinator sign-in. Staff pages are English only.
set_lang('en'); staff_start();
if (staff_user()) { header('Location: /admin'); exit; }
$err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = strtolower(trim((string)($_POST['username'] ?? ''))); $pass = (string)($_POST['password'] ?? ''); $code = preg_replace('/\D/', '', (string)($_POST['code'] ?? ''));
  $who = hash_hmac('sha256', 'u:'.$user, app_key());
  $need = two_factor_required();
  if (!csrf_ok()) { $err = 'Please try again.'; }
  elseif (!throttle('login', 5, 15) || !throttle('login', 5, 15, $who)) { $err = 'Too many tries. Wait 15 minutes.'; }
  else {
    $s = db()->prepare('SELECT * FROM staff WHERE username=? AND active=1'); $s->execute([$user]); $row = $s->fetch(PDO::FETCH_ASSOC);
    static $dummy; $dummy ??= password_hash('not-a-real-password', pass_algo());
    $pass_ok = password_verify($pass, $row['pass_hash'] ?? $dummy) && $row;
    if ($pass_ok && (!$need || totp_ok($row, $code))) {
      session_regenerate_id(true); $_SESSION['sid'] = (int)$row['id']; $_SESSION['t'] = time(); $_SESSION['csrf'] = bin2hex(random_bytes(16));
      audit((int)$row['id'], 'login'); header('Location: /admin'); exit;
    }
    hit('login'); hit('login', $who); $err = 'Sign-in failed. Check your details and code.';
  }
}
page('Coordinator sign-in', function () use ($err) {
  echo '<h1>Coordinator workspace</h1><p class="lead">For authorised Bomaveda staff only.</p>'; if (!two_factor_required()) echo '<p class="alert" role="status">Local test mode: the authenticator code is switched off on this computer.</p>'; if ($err) echo '<p class="alert alert-error" role="alert">'.e($err).'</p>';
  echo '<form method="post" action="/auth"><input type="hidden" name="csrf" value="'.e(csrf()).'">';
  echo '<label for="u">Username</label><input id="u" name="username" autocomplete="username" required>';
  echo '<label for="p">Password</label><input id="p" name="password" type="password" autocomplete="current-password" required>';
  if (two_factor_required()) echo '<label for="c">Authenticator code (6 digits)</label><input id="c" name="code" inputmode="numeric" autocomplete="one-time-code" required>';
  echo '<p><button class="btn" type="submit">Sign in</button></p></form>';
}, false, true);
