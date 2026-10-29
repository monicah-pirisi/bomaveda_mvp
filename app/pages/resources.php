<?php
if (!lang()) set_lang('en');
page(t('rs.h1'), function () {
  $l = lang();
  echo '<h1>'.e(t('rs.h1')).'</h1><p class="lead">'.e(t('rs.lead')).'</p><h2>'.e(t('rs.emerg')).'</h2><div class="card"><ul>';
  foreach (cfg('emergency') as $c) echo '<li>'.e($c[$l]).': <strong>'.e($c['num']).'</strong></li>';
  echo '</ul></div><h2>'.e(t('rs.guides')).'</h2><div class="grid">';
  foreach (cfg('guides') as $g) echo '<div class="card"><h3>'.e($g['title'][$l]).'</h3><p>'.e($g['body'][$l]).'</p></div>';
  echo '</div><h2>'.e(t('rs.opps')).'</h2><p>'.e(t('rs.opps.t')).'</p><p><a class="btn" href="'.e(u('/request-support')).'">'.e(t('support')).'</a></p>';
});
