<?php
if (!lang()) set_lang('en');

function load_opportunities(): array {
  try {
    $s = db()->query('SELECT * FROM opportunities WHERE active=1 ORDER BY type, deadline IS NULL, deadline, title');
    return $s->fetchAll(PDO::FETCH_ASSOC);
  } catch (Throwable $e) { return []; }
}

page(t('rs.h1'), function () {
  $l = lang();
  echo '<h1>'.e(t('rs.h1')).'</h1><p class="lead">'.e(t('rs.lead')).'</p>';

  echo '<h2>'.e(t('rs.emerg')).'</h2><div class="card"><ul>';
  foreach (cfg('emergency') as $c) echo '<li>'.e($c[$l]).': <strong>'.e($c['num']).'</strong></li>';
  echo '</ul></div>';

  echo '<h2>'.e(t('rs.opps')).'</h2>';
  $opps = load_opportunities();
  if ($opps) {
    $types = ['job' => [], 'skill' => [], 'scholarship' => [], 'bursary' => []];
    foreach ($opps as $op) $types[$op['type']][] = $op;
    $labels = ['job' => t('rs.jobs'), 'skill' => t('rs.jobs'), 'scholarship' => t('rs.edu'), 'bursary' => t('rs.edu')];
    $shown = [];
    foreach ($types as $type => $items) {
      if (!$items) continue;
      $group = $labels[$type];
      if (!in_array($group, $shown, true)) { echo '<h3>'.e($group).'</h3>'; $shown[] = $group; }
      echo '<div class="grid">';
      foreach ($items as $op) {
        $title = ($l === 'sw' && $op['title_sw'] !== '') ? $op['title_sw'] : $op['title'];
        $desc = ($l === 'sw' && $op['description_sw'] !== '') ? $op['description_sw'] : $op['description'];
        echo '<div class="card"><h4>'.e($title).'</h4>';
        if ($desc !== '') echo '<p>'.e($desc).'</p>';
        $meta = [];
        if ($op['provider'] !== '') $meta[] = e($op['provider']);
        if ($op['location'] !== '') $meta[] = e($op['location']);
        if ($op['deadline']) $meta[] = 'Deadline: '.e($op['deadline']);
        if ($meta) echo '<p><small>'.implode(' · ', $meta).'</small></p>';
        if ($op['link'] !== '') echo '<p><a class="btn alt" href="'.e($op['link']).'" target="_blank" rel="noopener">'.($l === 'sw' ? 'Tazama' : 'View details').'</a></p>';
        echo '</div>';
      }
      echo '</div>';
    }
  } else {
    echo '<p>'.e(t('rs.opps.t')).'</p><div class="grid">';
    echo '<div class="card"><h3>'.e(t('rs.jobs')).'</h3><p>'.e(t('rs.jobs.t')).'</p></div>';
    echo '<div class="card"><h3>'.e(t('rs.edu')).'</h3><p>'.e(t('rs.edu.t')).'</p></div>';
    echo '</div>';
  }

  echo '<h2>'.e(t('rs.guides')).'</h2><div class="grid">';
  foreach (cfg('guides') as $g) echo '<div class="card"><h3>'.e($g['title'][$l]).'</h3><p>'.e($g['body'][$l]).'</p></div>';
  echo '</div>';
}, true);
