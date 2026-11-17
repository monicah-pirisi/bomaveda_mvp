<?php
$err = null; $old = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cat = $_POST['category'] ?? ''; $urg = $_POST['urgency'] ?? '';
  [$con, $ward] = array_pad(explode('|', (string)($_POST['ward'] ?? ''), 2), 2, '');
  $village = trim((string)($_POST['village'] ?? ''));
  $landmark = trim((string)($_POST['landmark'] ?? ''));
  $old = $desc = trim((string)($_POST['description'] ?? ''));
  $location_parts = array_filter([$village, $landmark]);
  if ($location_parts) $desc = implode(' | ', $location_parts) . "\n\n" . $desc;
  $cv = trim((string)($_POST['cvalue'] ?? '')); $cm = $_POST['cmethod'] ?? 'phone';
  if (!empty($_POST['website'])) { header('Location: /'); exit; }
  if (!is_string($cat) || !is_string($urg) || !is_string($cm) || !in_array($cat, cfg('categories'), true) || !in_array($urg, cfg('urgency'), true)
      || !isset(cfg('wards')[$con]) || !in_array($ward, cfg('wards')[$con], true)
      || mb_strlen($old) < 10 || mb_strlen($old) > 2000 || ($cv !== '' && (!in_array($cm, ['phone','email'], true) || mb_strlen($cv) > 120))) {
    $err = t('invalid');
  } elseif (!throttle('submit', 10, 60)) {
    $err = t('toomany');
  } elseif (!($code = create_case($cat, $con, $ward, $desc, $urg, $cm, $cv, !empty($_POST['safe'])))) {
    $err = t('invalid');
  } else {
    hit('submit');
    page(t('d0'), function () use ($code) {
      echo '<h1>'.e(t('d0')).'</h1><p class="code">'.e(show_code($code)).'</p><p>'.e(t('d1')).'</p><p class="note">'.e(t('d2')).'</p><p>'.e(t('d3')).'</p>';
      echo '<p><a class="btn" href="'.e(u('/case')).'">'.e(t('check')).'</a></p>';
    });
    exit;
  }
}
page(t('share'), function () use ($err, $old) {
  echo '<h1>'.e(t('share')).'</h1><p id="prog" data-t="'.e(t('prog')).'"></p>'; if ($err) echo '<p class="alert alert-error" role="alert">'.e($err).'</p>';
  echo '<form id="intake" method="post" action="/submit-concern"><input type="hidden" name="l" value="'.e(lang() ?? 'en').'">';

  echo '<fieldset class="step"><legend>'.e(t('s1')).'</legend>';
  echo '<label>'.e(t('ward')).'</label><select name="ward" required aria-label="'.e(t('ward')).'"><option value="">'.e(t('ward')).'</option>';
  foreach (cfg('wards') as $con => $ws) { echo '<optgroup label="'.e($con).'">'; foreach ($ws as $w) echo '<option value="'.e($con.'|'.$w).'">'.e($w).'</option>'; echo '</optgroup>'; }
  echo '</select>';
  echo '<label>'.e(t('village')).'</label><input name="village" maxlength="200" placeholder="'.e(t('village')).'">';
  echo '<label>'.e(t('landmark')).'</label><input name="landmark" maxlength="200" placeholder="'.e(t('landmark')).'">';
  echo '</fieldset>';

  echo '<fieldset class="step"><legend>'.e(t('s2')).'</legend>';
  echo '<select name="category" required aria-label="'.e(t('s2')).'"><option value="">—</option>';
  foreach (cfg('categories') as $c) echo '<option value="'.$c.'">'.e(t('cat.'.$c)).'</option>';
  echo '</select>';
  echo '<p class="cat-desc help" id="cat-desc"></p>';
  echo '<div class="cat-details">';
  foreach (cfg('categories') as $c) echo '<p class="cat-detail" data-cat="'.$c.'" hidden>'.e(t('cat.'.$c.'.d')).'</p>';
  echo '</div></fieldset>';

  echo '<fieldset class="step"><legend>'.e(t('s3')).'</legend><p class="help">'.e(t('s3h')).'</p><textarea name="description" rows="6" minlength="10" maxlength="2000" required>'.e($old).'</textarea></fieldset>';

  echo '<fieldset class="step"><legend>'.e(t('s4')).'</legend>';
  echo '<select name="urgency" required aria-label="'.e(t('s4')).'"><option value="">—</option>';
  foreach (cfg('urgency') as $x) echo '<option value="'.$x.'"'.($x === 'moderate' ? ' selected' : '').'>'.e(t('u.'.$x)).'</option>';
  echo '</select></fieldset>';

  echo '<fieldset class="step"><legend>'.e(t('s5')).'</legend><p class="help">'.e(t('s5h')).'</p>';
  echo '<select name="cmethod" aria-label="'.e(t('cvalue')).'"><option value="phone">'.e(t('cphone')).'</option><option value="email">'.e(t('cemail')).'</option></select>';
  echo '<input name="cvalue" maxlength="120" autocomplete="off" aria-label="'.e(t('cvalue')).'" placeholder="'.e(t('cvalue')).'"><label class="opt"><input type="checkbox" name="safe" value="1"> '.e(t('csafe')).'</label>';
  echo '<input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></fieldset>';
  echo '<p class="row"><button type="button" class="btn alt" id="back">'.e(t('back')).'</button> <button type="button" class="btn" id="next">'.e(t('next')).'</button> <button type="submit" class="btn" id="send">'.e(t('send')).'</button></p></form>';
});
