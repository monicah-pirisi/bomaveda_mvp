<?php
if (!lang()) set_lang('en');
page('Bomaveda', function () {
  $x = fn(string $k) => e(t($k));
  $l = lang();

  // Hero
  echo '<section class="hero"><div class="hero-in"><p class="kick hero-kick">'.$x('hm.kick').'</p><h1>'.$x('hm.h1').'</h1><p class="lead">'.$x('hm.lead').'</p>';
  echo '<p class="actions"><a class="btn" href="'.e(u('/submit-concern')).'">'.$x('share').'</a> <a class="btn alt" href="'.e(u('/request-support')).'">'.$x('support').'</a></p>';
  echo '<p class="trust"><span class="live-dot"></span>'.$x('hm.badges').'</p>';

  // Stats row in hero
  $stats = $l === 'sw'
    ? [['7','Kaunti ndogo',''],['24/7','Ripoti wakati wowote',''],['100','Siri kamili','%'],['3','Hatua rahisi','']]
    : [['7','Sub-counties covered',''],['24/7','Report anytime',''],['100','Fully anonymous','%'],['3','Simple steps','']];
  echo '<div class="stats-row">';
  foreach ($stats as $s) {
    $val = $s[0]; $suf = $s[2];
    echo '<div class="stat-item"><div class="stat-num" data-count="'.e($val === '24/7' ? '' : preg_replace('/\D/', '', $val)).'"'.($val === '24/7' ? '' : ' data-suffix="'.e($suf).'"').'>'.$val.$suf.'</div><div class="stat-label">'.e($s[1]).'</div></div>';
  }
  echo '</div>';
  echo '<p class="note">'.$x('emerg').'</p></div></section>';

  // How it works
  $links = ['/submit-concern','/request-support','/resources'];
  echo '<section class="band white"><div class="wrap"><h2 class="reveal">'.$x('hm.how').'</h2><div class="grid three how">';
  foreach ([1, 2, 3] as $n) echo '<a class="card how-link" href="'.e(u($links[$n-1])).'"><div class="feat-icon">'.['&#128227;','&#129309;','&#128218;'][$n-1].'</div><h3>'.$x("hm.s{$n}t").'</h3><p>'.$x("hm.s$n").'</p></a>';
  echo '</div><p class="section-note reveal">'.$x('hm.after').'</p></div></section>';

  // CTA
  echo '<section class="band cta-band"><div class="wrap chooser reveal"><h2>'.$x('hm.cta').'</h2><p>'.$x('hm.cta.t').'</p>';
  echo '<p class="actions"><a class="btn" href="'.e(u('/about')).'">'.$x('about').'</a> <a class="btn" href="'.e(u('/simulator')).'" style="background:transparent;border-color:#fff;color:#fff">'.$x('simlink').'</a></p></div></section>';
}, false, false, 'home');
