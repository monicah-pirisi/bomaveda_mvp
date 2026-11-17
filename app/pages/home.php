<?php
if (!lang()) set_lang('en');
page('Bomaveda', function () {
  $x = fn(string $k) => e(t($k));
  echo '<section class="hero"><div class="hero-in"><p class="kick hero-kick">'.$x('hm.kick').'</p><h1>'.$x('hm.h1').'</h1><p class="lead">'.$x('hm.lead').'</p>';
  echo '<p class="actions"><a class="btn" href="'.e(u('/submit-concern')).'">'.$x('share').'</a> <a class="btn alt" href="'.e(u('/request-support')).'">'.$x('support').'</a></p>';
  echo '<p class="trust">'.$x('hm.badges').'</p><p class="note">'.$x('emerg').'</p></div></section>';

  $links = ['/submit-concern','/request-support','/resources'];
  echo '<section class="band white"><div class="wrap"><h2>'.$x('hm.how').'</h2><div class="grid three how">';
  foreach ([1, 2, 3] as $n) echo '<a class="card how-link" href="'.e(u($links[$n-1])).'"><h3>'.$x("hm.s{$n}t").'</h3><p>'.$x("hm.s$n").'</p></a>';
  echo '</div><p class="section-note">'.$x('hm.after').'</p></div></section>';

  echo '<section class="band"><div class="wrap chooser"><h2>'.$x('hm.cta').'</h2><p>'.$x('hm.cta.t').'</p>';
  echo '<p class="actions"><a class="btn" href="'.e(u('/about')).'">'.$x('about').'</a></p></div></section>';
}, false, false, 'home');
