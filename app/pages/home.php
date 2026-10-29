<?php
if (!lang()) {
  page('Bomaveda', function () {
    echo '<section class="band"><div class="wrap chooser"><h1 lang="sw">Chagua lugha</h1><p class="lead" lang="en">Choose a language</p><p class="actions"><a class="btn" lang="sw" hreflang="sw" href="/?l=sw">Kiswahili</a> <a class="btn alt" lang="en" hreflang="en" href="/?l=en">English</a></p></div></section>';
  }, false, false, 'home');
  return;
}
page('Bomaveda', function () {
  $x = fn(string $k) => e(t($k));
  echo '<section class="band"><div class="wrap"><p class="kick">'.$x('hm.kick').'</p><h1>'.$x('hm.h1').'</h1><p class="lead">'.$x('hm.lead').'</p>';
  echo '<p class="actions"><a class="btn" href="'.e(u('/submit-concern')).'">'.$x('share').'</a> <a class="btn alt" href="'.e(u('/request-support')).'">'.$x('support').'</a></p>';
  echo '<p class="trust">'.$x('hm.badges').'</p><p class="note">'.$x('emerg').'</p></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.$x('hm.how').'</h2><p>'.$x('hm.how.sub').'</p><div class="grid three how">';
  foreach ([1, 2, 3] as $n) echo '<div class="card"><h3>'.$x("hm.s{$n}t").'</h3><p>'.$x("hm.s$n").'</p></div>';
  echo '</div></div></section>';
  echo '<section class="band"><div class="wrap split"><div><h2>'.$x('hm.after').'</h2><p>'.$x('hm.after.t').'</p></div><ol class="stat">';
  foreach (cfg('status') as $s) echo '<li>'.e(t('st.'.$s)).'</li>';
  echo '</ol></div></section>';
  echo '<section class="band white"><div class="wrap"><h2>'.$x('hm.pill').'</h2><div class="grid four pillars">';
  foreach ([1, 2, 3, 4] as $n) echo '<div class="card"><h3>'.$x("hm.p{$n}t").'</h3><p>'.$x("hm.p$n").'</p></div>';
  echo '</div></div></section>';
  echo '<section class="band"><div class="wrap split"><div class="card"><h2>'.$x('hm.priv').'</h2><ul class="ticks">';
  foreach ([1, 2, 3, 4, 5] as $n) echo '<li>'.$x("hm.v$n").'</li>';
  echo '</ul></div><div><h2>'.$x('hm.partners').'</h2><ul class="partners">';
  foreach (cfg('partners') as $p) echo '<li>'.e($p).'</li>';
  echo '</ul></div></div></section>';
}, false, false, 'home');
