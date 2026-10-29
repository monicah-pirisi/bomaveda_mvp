<?php
require __DIR__.'/../app/lib.php';
$err = null; $old = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cat = $_POST['category'] ?? ''; $urg = $_POST['urgency'] ?? '';
  [$con, $ward] = array_pad(explode('|', $_POST['ward'] ?? '', 2), 2, '');
  $old = $desc = trim($_POST['description'] ?? '');
  $cv = trim($_POST['cvalue'] ?? ''); $cm = $_POST['cmethod'] ?? 'phone';
  if (!empty($_POST['website'])) { header('Location: /'); exit; } // honeypot: bots only
  if (!in_array($cat, CATS, true) || !in_array($urg, URG, true) || !isset(WARDS[$con]) || !in_array($ward, WARDS[$con], true)
      || mb_strlen($desc) < 10 || mb_strlen($desc) > 2000 || ($cv !== '' && (!in_array($cm, ['phone','email'], true) || mb_strlen($cv) > 120))) {
    $err = t('invalid');
  } elseif (!throttle('submit', 10, 60)) {
    $err = t('toomany');
  } else {
    $pdo = db(); $pdo->beginTransaction(); $code = null;
    for ($i = 0; $i < 5 && !$code; $i++) {
      $try = new_code();
      try {
        $pdo->prepare('INSERT INTO cases (code,category,constituency,ward,description,urgency) VALUES (?,?,?,?,?,?)')->execute([$try, $cat, $con, $ward, enc($desc), $urg]);
        $code = $try;
      } catch (PDOException $x) { if ($x->getCode() !== '23000') throw $x; }
    }
    if ($code && $cv !== '') {
      $pdo->prepare('INSERT INTO contacts (case_id,method,value_enc,safe_to_call) VALUES (?,?,?,?)')->execute([$pdo->lastInsertId(), $cm, enc($cv), empty($_POST['safe']) ? 0 : 1]);
    }
    $pdo->commit(); hit('submit');
    page(t('d0'), function () use ($code) {
      echo '<h1>'.e(t('d0')).'</h1><p class="code">'.e(show_code($code)).'</p><p>'.e(t('d1')).'</p><p class="note">'.e(t('d2')).'</p><p>'.e(t('d3')).'</p>';
      echo '<p><a class="btn" href="'.e(u('/case.php')).'">'.e(t('check')).'</a></p>';
    });
    exit;
  }
}
page(t('share'), function () use ($err, $old) {
  echo '<p id="prog" data-t="'.e(t('prog')).'"></p>'; if ($err) echo '<p class="note" role="alert">'.e($err).'</p>';
  echo '<form id="intake" method="post" action="/submit.php"><input type="hidden" name="l" value="'.e(lang() ?? 'en').'">';
  echo '<fieldset class="step"><legend>'.e(t('s1')).'</legend>';
  foreach (CATS as $c) echo '<label class="opt"><input type="radio" name="category" value="'.$c.'" required> '.e(t('cat.'.$c)).'</label>';
  echo '</fieldset><fieldset class="step"><legend>'.e(t('s2')).'</legend><select name="ward" required aria-label="'.e(t('ward')).'"><option value="">'.e(t('ward')).'</option>';
  foreach (WARDS as $con => $ws) { echo '<optgroup label="'.e($con).'">'; foreach ($ws as $w) echo '<option value="'.e($con.'|'.$w).'">'.e($w).'</option>'; echo '</optgroup>'; }
  echo '</select></fieldset><fieldset class="step"><legend>'.e(t('s3')).'</legend><p>'.e(t('s3h')).'</p><textarea name="description" rows="6" minlength="10" maxlength="2000" required>'.e($old).'</textarea></fieldset>';
  echo '<fieldset class="step"><legend>'.e(t('s4')).'</legend>';
  foreach (URG as $x) echo '<label class="opt"><input type="radio" name="urgency" value="'.$x.'" required'.($x === 'moderate' ? ' checked' : '').'> '.e(t('u.'.$x)).'</label>';
  echo '</fieldset><fieldset class="step"><legend>'.e(t('s5')).'</legend><p>'.e(t('s5h')).'</p><select name="cmethod" aria-label="'.e(t('cvalue')).'"><option value="phone">'.e(t('cphone')).'</option><option value="email">'.e(t('cemail')).'</option></select>';
  echo '<input name="cvalue" maxlength="120" autocomplete="off" aria-label="'.e(t('cvalue')).'" placeholder="'.e(t('cvalue')).'"><label class="opt"><input type="checkbox" name="safe" value="1"> '.e(t('csafe')).'</label>';
  echo '<input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"></fieldset>';
  echo '<p class="row"><button type="button" class="btn alt" id="back">'.e(t('back')).'</button> <button type="button" class="btn" id="next">'.e(t('next')).'</button> <button type="submit" class="btn" id="send">'.e(t('send')).'</button></p></form>';
});
