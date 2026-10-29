<?php
$res = null; $msg = null; $notes = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // POST keeps the code out of the URL and browser history
  $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
  if (!throttle('lookup', 5, 15)) { $msg = t('locked'); }
  else {
    $res = find_case($code);
    if (!$res) { hit('lookup'); $msg = t('nf'); }
    else {
      $s = db()->prepare("SELECT body_enc,created_at FROM messages WHERE case_id=? AND author='staff' ORDER BY created_at"); $s->execute([$res['id']]);
      foreach ($s as $m) $notes[] = [dec($m['body_enc']), $m['created_at']];
    }
  }
}
page(t('ltitle'), function () use ($res, $msg, $notes) {
  echo '<h1>'.e(t('ltitle')).'</h1>'; if ($msg) echo '<p class="alert alert-error" role="alert">'.e($msg).'</p>';
  echo '<form class="lookup" method="post" action="/case"><input type="hidden" name="l" value="'.e(lang() ?? 'en').'"><label for="code">'.e(t('llabel')).'</label>';
  echo '<input id="code" name="code" inputmode="numeric" autocomplete="off" required> <button class="btn" type="submit">'.e(t('lgo')).'</button></form>';
  if ($res) {
    echo '<ol class="stat">'; foreach (cfg('status') as $s) echo '<li'.($s === $res['status'] ? ' aria-current="step"' : '').'>'.e(t('st.'.$s)).'</li>'; echo '</ol>';
    echo '<p>'.e(t('upd')).': '.e(substr($res['updated_at'], 0, 10)).'</p><h2>'.e(t('msgs')).'</h2>';
    if (!$notes) echo '<p>'.e(t('nomsg')).'</p>'; foreach ($notes as $n) echo '<div class="card">'.nl2br(e($n[0])).'</div>';
  }
});
