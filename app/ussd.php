<?php
// USSD engine. Stateless: the gateway sends the whole path as "1*2*3", so nothing is stored between screens.
// It reads only that path. It never touches the caller's number, session id or network details.
function ussd_run(string $text): string {
  try { $out = ussd_flow($text === '' ? [] : explode('*', substr($text, 0, 600))); }
  catch (Throwable $x) { error_log('ussd '.get_class($x)); $out = 'END Connection closed'; }
  return mb_substr($out, 0, 182); // hard limit on one USSD screen
}
function ussd_flow(array $in): string {
  if (!$in) return "CON Language / Lugha\n1. English\n2. Kiswahili";
  $map = ['1' => 'en', '2' => 'sw'];
  if (!isset($map[$in[0]])) return 'END Wrong choice / Chaguo si sahihi';
  set_lang($map[$in[0]]);
  if (!isset($in[1])) return 'CON '.t('x.menu');
  return match ($in[1]) {
    '0' => 'END '.t('x.bye'), '3' => 'END '.ussd_emergency(), '4' => 'END '.t('x.safety'),
    '2' => ussd_status($in), '1' => ussd_report($in), default => 'END '.t('x.bad'),
  };
}
function ussd_emergency(): string {
  $l = lang() ?? 'en'; return implode("\n", array_map(fn($c) => $c[$l].': '.$c['num'], cfg('emergency')));
}
function ussd_status(array $in): string {
  if (!isset($in[2])) return 'CON '.t('x.ask');
  if (!throttle('ulookup', 30, 15)) return 'END '.t('locked');
  $c = find_case($in[2]);
  if (!$c) { hit('ulookup'); return 'END '.t('x.nf'); }
  return 'END '.sprintf(t('x.status'), t('st.'.$c['status']), substr($c['updated_at'], 0, 10));
}
// Shows 4 options per screen plus "5. More", so no screen ever has more than 5 options.
function pick(array $in, int &$i, array $items, string $title): array {
  $page = 0;
  while (true) {
    $slice = array_slice($items, $page * 4, 4); $more = ($page + 1) * 4 < count($items);
    if (!isset($in[$i])) {
      $s = $title; foreach ($slice as $n => $it) $s .= "\n".($n + 1).'. '.$it[1];
      return ['ask', $s.($more ? "\n".t('x.more') : '')];
    }
    $c = $in[$i++];
    if ($c === '5' && $more) { $page++; continue; }
    if (ctype_digit($c) && (int)$c >= 1 && (int)$c <= count($slice)) return ['ok', $slice[(int)$c - 1][0]];
    return ['bad', ''];
  }
}
function ussd_report(array $in): string {
  $i = 2;
  [$st, $cat] = pick($in, $i, array_map(fn($c) => [$c, t('xc.'.$c)], cfg('categories')), t('x.cat'));
  if ($st !== 'ok') return $st === 'ask' ? 'CON '.$cat : 'END '.t('x.bad');
  [$st, $con] = pick($in, $i, array_map(fn($c) => [$c, $c], array_keys(cfg('wards'))), t('x.county'));
  if ($st !== 'ok') return $st === 'ask' ? 'CON '.$con : 'END '.t('x.bad');
  if (!isset($in[$i])) return 'CON '.t('x.desc');
  $desc = trim(preg_replace('/\p{C}+/u', ' ', implode(' ', array_slice($in, $i))));
  $urg = 'moderate';
  if ($desc === '0') { $urg = 'urgent'; $desc = '[Emergency flag from USSD. No details given.]'; }
  elseif (mb_strlen($desc) < 5) return 'END '.t('x.short');
  if (!throttle('usubmit', 200, 60)) return 'END '.t('toomany');
  $code = create_case($cat, $con, '', mb_substr($desc, 0, 160), $urg);
  if (!$code) return 'END Connection closed';
  hit('usubmit');
  return 'END '.sprintf(t($urg === 'urgent' ? 'x.doneE' : 'x.done'), show_code($code));
}
