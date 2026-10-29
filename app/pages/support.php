<?php
$err = null; $old = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $type = $_POST['type'] ?? ''; [$con, $ward] = array_pad(explode('|', (string)($_POST['ward'] ?? ''), 2), 2, '');
  $old = $msg = trim((string)($_POST['message'] ?? '')); $cv = trim((string)($_POST['cvalue'] ?? '')); $cm = $_POST['cmethod'] ?? 'phone';
  if (!empty($_POST['website'])) { header('Location: /'); exit; }
  if (!is_string($type) || !is_string($cm) || !in_array($type, cfg('support_types'), true) || !isset(cfg('wards')[$con]) || !in_array($ward, cfg('wards')[$con], true)
      || mb_strlen($msg) > 1000 || ($cv !== '' && (!in_array($cm, ['phone','email'], true) || mb_strlen($cv) > 120))) {
    $err = t('invalid');
  } elseif (!throttle('submit', 10, 60)) { $err = t('toomany'); }
  elseif (!($code = create_case($type, $con, $ward, $msg === '' ? '-' : $msg, 'moderate', $cm, $cv, !empty($_POST['safe']), 'support'))) { $err = t('invalid'); }
  else {
    hit('submit');
    page(t('d0'), function () use ($code) {
      echo '<h1>'.e(t('d0')).'</h1><p class="code">'.e(show_code($code)).'</p><p>'.e(t('d1')).'</p><p class="note">'.e(t('d2')).'</p><p>'.e(t('d3')).'</p>';
      echo '<p><a class="btn" href="'.e(u('/case')).'">'.e(t('check')).'</a></p>';
    });
    return;
  }
}
page(t('sp.h1'), function () use ($err, $old) {
  echo '<h1>'.e(t('sp.h1')).'</h1><p>'.e(t('sp.lead')).'</p><p id="prog" data-t="'.e(t('prog')).'"></p>'; if ($err) echo '<p class="alert alert-error" role="alert">'.e($err).'</p>';
  echo '<form id="intake" method="post" action="/request-support"><input type="hidden" name="l" value="'.e(lang() ?? 'en').'"><fieldset class="step"><legend>'.e(t('sp.s1')).'</legend>';
  foreach (cfg('support_types') as $c) echo '<label class="opt"><input type="radio" name="type" value="'.$c.'" required> '.e(t('sp.'.$c)).'</label>';
  echo '</fieldset><fieldset class="step"><legend>'.e(t('s2')).'</legend><select name="ward" required aria-label="'.e(t('ward')).'"><option value="">'.e(t('ward')).'</option>';
  foreach (cfg('wards') as $con => $ws) { echo '<optgroup label="'.e($con).'">'; foreach ($ws as $w) echo '<option value="'.e($con.'|'.$w).'">'.e($w).'</option>'; echo '</optgroup>'; }
  echo '</select></fieldset><fieldset class="step"><legend>'.e(t('sp.s3')).'</legend><p>'.e(t('sp.s3h')).'</p><textarea name="message" rows="5" maxlength="1000">'.e($old).'</textarea></fieldset>';
  echo '<fieldset class="step"><legend>'.e(t('s5')).'</legend><p>'.e(t('s5h')).'</p><select name="cmethod" aria-label="'.e(t('cvalue')).'"><option value="phone">'.e(t('cphone')).'</option><option value="email">'.e(t('cemail')).'</option></select>';
  echo '<input name="cvalue" maxlength="120" autocomplete="off" aria-label="'.e(t('cvalue')).'" placeholder="'.e(t('cvalue')).'"><label class="opt"><input type="checkbox" name="safe" value="1"> '.e(t('csafe')).'</label>';
  echo '<input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></fieldset>';
  echo '<p class="row"><button type="button" class="btn alt" id="back">'.e(t('back')).'</button> <button type="button" class="btn" id="next">'.e(t('next')).'</button> <button type="submit" class="btn" id="send">'.e(t('send')).'</button></p></form>';
});
