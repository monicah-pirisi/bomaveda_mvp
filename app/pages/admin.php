<?php
// Staff workspace. Roles: liaison (cases in own sub-county), coordinator (counts, mentors, referral decisions), admin (everything).
set_lang('en'); staff_start(); $me = staff_user();
if (!$me) { header('Location: /auth'); exit; }
$role = $me['role'];
function deny(): void { http_response_code(403); page('Not allowed', function () { echo '<h1>Not allowed</h1><p>Your role cannot open this.</p><p><a href="/admin">Back</a></p>'; }, false, true); exit; }
function load_case(int $id): ?array { $s = db()->prepare('SELECT * FROM cases WHERE id=?'); $s->execute([$id]); return $s->fetch(PDO::FETCH_ASSOC) ?: null; }
function may_open(array $me, ?array $c): bool { return $c && ($me['role'] === 'admin' || ($me['role'] === 'liaison' && $me['constituency'] === $c['constituency'])); }
function go(string $to, string $flash = ''): void { if ($flash !== '') $_SESSION['flash'] = $flash; header('Location: '.$to); exit; }
$views = ['cases','case','esc','stats','mentors','audit','pw'];
$map = ['admin' => $views, 'liaison' => ['cases','case','pw'], 'coordinator' => ['stats','mentors','esc','pw']];
$v = in_array($_GET['v'] ?? '', $views, true) ? $_GET['v'] : ($role === 'coordinator' ? 'stats' : 'cases');
if (!in_array($v, $map[$role], true)) deny();
$reveal = false; $pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_ok()) { http_response_code(403); exit('Invalid request'); }
  $act = (string)($_POST['act'] ?? ''); $id = (int)($_POST['id'] ?? 0); $uid = (int)$me['id'];
  if ($act === 'logout') { audit($uid, 'logout'); session_destroy(); header('Location: /auth'); exit; }
  if (in_array($act, ['status','msg','esc','reveal'], true)) {
    $c = load_case($id); if (!may_open($me, $c)) deny();
    if ($act === 'status' && in_array($_POST['status'] ?? '', cfg('status'), true)) { $pdo->prepare('UPDATE cases SET status=? WHERE id=?')->execute([$_POST['status'], $id]); audit($uid, 'status', $id, $_POST['status']); go("/admin?v=case&id=$id", 'Status updated.'); }
    if ($act === 'msg') { $b = trim((string)($_POST['body'] ?? '')); if ($b !== '' && mb_strlen($b) <= 1000) { $pdo->prepare("INSERT INTO messages (case_id,author,body_enc) VALUES (?,'staff',?)")->execute([$id, enc($b)]); audit($uid, 'message', $id); go("/admin?v=case&id=$id", 'Message sent.'); } go("/admin?v=case&id=$id", 'Message was empty or too long.'); }
    if ($act === 'esc') { $r = trim((string)($_POST['reason'] ?? '')); if (in_array($_POST['agency'] ?? '', cfg('agencies'), true) && mb_strlen($r) >= 10 && mb_strlen($r) <= 500) { $pdo->prepare('INSERT INTO escalations (case_id,agency,reason_enc,requested_by) VALUES (?,?,?,?)')->execute([$id, $_POST['agency'], enc($r), $uid]); audit($uid, 'esc_request', $id, $_POST['agency']); go("/admin?v=case&id=$id", 'Referral requested. A different person must approve it.'); } go("/admin?v=case&id=$id", 'Choose an agency and give a reason (10 to 500 characters).'); }
    if ($act === 'reveal') { $reveal = true; audit($uid, 'reveal_contact', $id); $_GET['id'] = (string)$id; $v = 'case'; }
    if ($act === 'status') go("/admin?v=case&id=$id", 'Choose a valid status.');
  } elseif ($act === 'decide' && in_array($role, ['admin','coordinator'], true)) {
    $s = $pdo->prepare('SELECT * FROM escalations WHERE id=?'); $s->execute([$id]); $x = $s->fetch(PDO::FETCH_ASSOC);
    if ($x && $x['status'] === 'requested' && (int)$x['requested_by'] !== $uid && in_array($_POST['d'] ?? '', ['approved','rejected'], true)) {
      $pdo->prepare('UPDATE escalations SET status=?, decided_by=? WHERE id=?')->execute([$_POST['d'], $uid, $id]); audit($uid, 'esc_'.$_POST['d'], (int)$x['case_id']); go('/admin?v=esc', 'Decision saved.');
    }
    go('/admin?v=esc', 'Not allowed. A different person must decide.');
  } elseif ($act === 'ref' && $role === 'admin') {
    $r = trim((string)($_POST['reference'] ?? ''));
    $s = $pdo->prepare("UPDATE escalations SET status='referred', reference=? WHERE id=? AND status='approved'");
    if ($r !== '' && mb_strlen($r) <= 80) { $s->execute([$r, $id]); audit($uid, 'esc_referred', null, 'esc '.$id); go('/admin?v=esc', 'Referral recorded.'); }
    go('/admin?v=esc', 'Add a reference.');
  } elseif ($act === 'mentor' && in_array($role, ['admin','coordinator'], true)) {
    $n = trim((string)($_POST['name'] ?? '')); $f = trim((string)($_POST['focus'] ?? '')); $k = (string)($_POST['con'] ?? ''); $ct = trim((string)($_POST['contact'] ?? ''));
    if ($n !== '' && $f !== '' && mb_strlen($n) <= 80 && mb_strlen($f) <= 80 && (isset(cfg('wards')[$k]) || $k === 'All')) { $pdo->prepare('INSERT INTO mentors (name,focus,constituency,contact_enc) VALUES (?,?,?,?)')->execute([$n, $f, $k, $ct === '' ? null : enc($ct)]); audit($uid, 'mentor_add'); go('/admin?v=mentors', 'Mentor added.'); }
    go('/admin?v=mentors', 'Name, focus and area are needed.');
  } elseif ($act === 'mtoggle' && in_array($role, ['admin','coordinator'], true)) {
    $pdo->prepare('UPDATE mentors SET active = 1 - active WHERE id=?')->execute([$id]); audit($uid, 'mentor_toggle'); go('/admin?v=mentors');
  } elseif ($act === 'pw') {
    $s = $pdo->prepare('SELECT pass_hash FROM staff WHERE id=?'); $s->execute([$uid]); $h = (string)$s->fetchColumn(); $new = (string)($_POST['new'] ?? '');
    if (password_verify((string)($_POST['cur'] ?? ''), $h) && strlen($new) >= 12 && hash_equals($new, (string)($_POST['again'] ?? ''))) {
      $pdo->prepare('UPDATE staff SET pass_hash=? WHERE id=?')->execute([password_hash($new, pass_algo()), $uid]); session_regenerate_id(true); audit($uid, 'password_change'); go('/admin?v=pw', 'Password changed.');
    }
    go('/admin?v=pw', 'Not changed. Check the current password; the new one needs 12+ characters and must match.');
  } else deny();
}
function badge(string $kind, string $val, string $label): string { return '<span class="badge '.e($kind.'-'.$val).'">'.e($label).'</span>'; }
function count_fmt(int $n, bool $hide): string { return $hide && $n > 0 && $n < 3 ? '<3' : (string)$n; }
function tpl_form(string $act, string $inner, array $hidden = []): string {
  $h = '<input type="hidden" name="csrf" value="'.e(csrf()).'"><input type="hidden" name="act" value="'.e($act).'">';
  foreach ($hidden as $k => $x) $h .= '<input type="hidden" name="'.e($k).'" value="'.e((string)$x).'">';
  return '<form method="post" action="/admin">'.$h.$inner.'</form>';
}
// Inbox: "new" means a request that is still at the first status, "submitted".
$newN = 0; $f = 'all';
if (in_array($role, ['admin','liaison'], true)) {
  $q = $pdo->prepare("SELECT COUNT(*) FROM cases WHERE status='submitted'".($role === 'liaison' ? ' AND constituency=?' : ''));
  $q->execute($role === 'liaison' ? [$me['constituency']] : []); $newN = (int)$q->fetchColumn();
  $f = in_array($_GET['f'] ?? '', ['new','open','closed','all'], true) ? $_GET['f'] : ($newN > 0 ? 'new' : 'open');
  if ($v === 'cases' && $_SERVER['REQUEST_METHOD'] === 'GET') header('Refresh: 60'); // the list reloads itself every minute
}
page('Coordinator workspace', function () use ($me, $role, $v, $map, $reveal, $pdo, $newN, $f) {
  $nav = ['cases'=>'Requests','esc'=>'Referrals','stats'=>'Overview','mentors'=>'Mentors','audit'=>'Audit log','pw'=>'Password'];
  $cur = $v === 'case' ? 'cases' : $v;
  echo '<nav class="staffbar" aria-label="Workspace"><span class="who"><strong>'.e($me['username']).'</strong><span class="badge role">'.e($role).($me['constituency'] ? ', '.e($me['constituency']) : '').'</span></span>';
  foreach ($nav as $k => $lab) if (in_array($k, $map[$role], true)) echo '<a href="/admin?v='.$k.'"'.($k === $cur ? ' aria-current="page"' : '').'>'.e($lab).($k === 'cases' && $newN > 0 ? ' '.badge('st', 'submitted', $newN.' new') : '').'</a>';
  echo '<span class="out">'.tpl_form('logout', '<button class="btn alt" type="submit">Sign out</button>').'</span></nav>';
  if (!empty($_SESSION['flash'])) { echo '<p class="alert" role="status">'.e($_SESSION['flash']).'</p>'; unset($_SESSION['flash']); }
  if ($v === 'cases') {
    $where = []; $args = [];
    if ($role === 'liaison') { $where[] = 'constituency=?'; $args[] = $me['constituency']; }
    $where[] = ['new' => "status='submitted'", 'open' => "status NOT IN ('submitted','resolved')", 'closed' => "status='resolved'", 'all' => '1=1'][$f];
    $s = $pdo->prepare('SELECT id,code,kind,category,constituency,urgency,status,created_at FROM cases WHERE '.implode(' AND ', $where)." ORDER BY (urgency = 'urgent') DESC, created_at DESC LIMIT 100");
    $s->execute($args);
    echo '<h1>Requests</h1><p>Every concern and support request sent on the web or by USSD arrives here'.($role === 'liaison' ? ' for '.e($me['constituency']) : '').'. This page reloads every minute.</p><p>';
    foreach (['new' => 'New', 'open' => 'In progress', 'closed' => 'Closed', 'all' => 'All'] as $k => $lab) echo '<a class="btn'.($k === $f ? '' : ' alt').'" href="/admin?v=cases&amp;f='.$k.'"'.($k === $f ? ' aria-current="true"' : '').'>'.e($lab).($k === 'new' && $newN > 0 ? ' ('.$newN.')' : '').'</a> ';
    echo '</p>'; $rows = '';
    foreach ($s as $c) $rows .= '<tr><th scope="row"><a href="/admin?v=case&amp;id='.(int)$c['id'].'">'.e(show_code($c['code'])).'</a></th><td>'.e($c['kind']).'</td><td>'.e($c['kind'] === 'support' ? t('sp.'.$c['category']) : t('cat.'.$c['category'])).'</td><td>'.e($c['constituency']).'</td><td>'.badge('u', $c['urgency'], $c['urgency']).'</td><td>'.badge('st', $c['status'], t('st.'.$c['status'])).'</td><td>'.e(substr($c['created_at'], 0, 16)).'</td></tr>';
    if ($rows === '') echo '<p class="alert">Nothing here right now. New requests show up on this page within a minute.</p>';
    else echo '<div class="scroll" role="region" tabindex="0" aria-label="Requests"><table class="tbl"><thead><tr><th scope="col">Case</th><th scope="col">Type</th><th scope="col">Topic</th><th scope="col">Sub-county</th><th scope="col">Urgency</th><th scope="col">Status</th><th scope="col">Received</th></tr></thead><tbody>'.$rows.'</tbody></table></div>';
  } elseif ($v === 'case') {
    $c = load_case((int)($_GET['id'] ?? 0)); if (!may_open($me, $c)) deny();
    audit((int)$me['id'], 'view_case', (int)$c['id']);
    echo '<h1>Case '.e(show_code($c['code'])).'</h1><div class="card"><p class="meta"><strong>'.e($c['kind']).'</strong> <span>'.e($c['kind'] === 'support' ? t('sp.'.$c['category']) : t('cat.'.$c['category'])).'</span> <span>'.e($c['constituency']).($c['ward'] !== '' ? ', '.e($c['ward']) : '').'</span> '.badge('u', $c['urgency'], 'urgency '.$c['urgency']).' '.badge('st', $c['status'], t('st.'.$c['status'])).'</p><p>'.nl2br(e(dec($c['description']))).'</p></div>';
    $s = $pdo->prepare('SELECT method,value_enc,safe_to_call FROM contacts WHERE case_id=?'); $s->execute([$c['id']]); $ct = $s->fetch(PDO::FETCH_ASSOC);
    if ($ct) echo '<div class="card"><h2>Contact (optional, given by the reporter)</h2>'.($reveal ? '<p>'.e($ct['method']).': <strong>'.e(dec($ct['value_enc'])).'</strong> ('.($ct['safe_to_call'] ? 'safe to call' : 'do not call').')</p>' : tpl_form('reveal', '<p>Hidden. Showing it is recorded in the audit log.</p><button class="btn alt" type="submit">Show contact</button>', ['id' => $c['id']])).'</div>';
    $opts = ''; foreach (cfg('status') as $st) $opts .= '<option value="'.e($st).'"'.($st === $c['status'] ? ' selected' : '').'>'.e(t('st.'.$st)).'</option>';
    echo '<div class="card"><h2>Status</h2>'.tpl_form('status', '<select name="status" aria-label="Status">'.$opts.'</select><button class="btn" type="submit">Update</button>', ['id' => $c['id']]).'</div>';
    echo '<div class="card"><h2>Messages to the reporter</h2>';
    $s = $pdo->prepare('SELECT author,body_enc,created_at FROM messages WHERE case_id=? ORDER BY id'); $s->execute([$c['id']]);
    foreach ($s as $m) echo '<div class="msg"><small>'.e($m['author']).', '.e(substr($m['created_at'], 0, 16)).'</small><p>'.nl2br(e(dec($m['body_enc']))).'</p></div>';
    echo tpl_form('msg', '<textarea name="body" rows="3" maxlength="1000" aria-label="Message" required></textarea><p class="note">The reporter reads this with their case number. Do not include names or anything that could identify anyone.</p><button class="btn" type="submit">Send</button>', ['id' => $c['id']]).'</div>';
    $ag = ''; foreach (cfg('agencies') as $a) $ag .= '<option>'.e($a).'</option>';
    echo '<div class="card"><h2>Request a referral</h2>'.tpl_form('esc', '<select name="agency" aria-label="Agency">'.$ag.'</select><textarea name="reason" rows="3" minlength="10" maxlength="500" aria-label="Reason" placeholder="Why, and what needs to be shared. No names." required></textarea><button class="btn" type="submit">Request</button>', ['id' => $c['id']]).'<p><small>A different person must approve it before anything is shared.</small></p></div>';
  } elseif ($v === 'esc') {
    echo '<h1>Referrals</h1><p>Two people are needed: one asks, another approves. Only the reason and topic are shown here, never the report text.</p>';
    $s = $pdo->query('SELECT e.*, c.code, c.category, c.kind, c.constituency, s.username AS req FROM escalations e JOIN cases c ON c.id=e.case_id JOIN staff s ON s.id=e.requested_by ORDER BY e.id DESC LIMIT 100');
    foreach ($s as $x) {
      echo '<div class="card"><p><strong>'.e(show_code($x['code'])).'</strong>, '.e($x['constituency']).', '.e($x['kind'] === 'support' ? t('sp.'.$x['category']) : t('cat.'.$x['category'])).'<br>To: '.e($x['agency']).'<br>Asked by '.e($x['req']).'. Status: '.badge('es', $x['status'], $x['status']).($x['reference'] ? ', reference '.e($x['reference']) : '').'</p><p>'.nl2br(e(dec($x['reason_enc']))).'</p>';
      if ($x['status'] === 'requested' && (int)$x['requested_by'] !== (int)$me['id']) echo tpl_form('decide', '<span class="actions"><button class="btn" name="d" value="approved" type="submit">Approve</button> <button class="btn danger" name="d" value="rejected" type="submit">Reject</button></span>', ['id' => $x['id']]);
      if ($x['status'] === 'approved' && $role === 'admin') echo tpl_form('ref', '<input name="reference" maxlength="80" placeholder="Reference or who it was passed to" aria-label="Reference" required><button class="btn" type="submit">Record referral</button>', ['id' => $x['id']]);
      echo '</div>';
    }
  } elseif ($v === 'stats') {
    $hide = $role !== 'admin'; $st = cfg('status'); $grid = [];
    foreach ($pdo->query('SELECT constituency, status, COUNT(*) n FROM cases GROUP BY constituency, status') as $r) $grid[$r['constituency']][$r['status']] = (int)$r['n'];
    echo '<h1>Overview</h1>'.($role === 'coordinator' ? '<p class="alert">Your role shows counts only. To open and handle requests, ask an admin for a liaison account for your sub-county.</p>' : '').'<p>Counts only. Small numbers show as &lt;3 so nobody can be picked out.</p><div class="scroll" role="region" tabindex="0" aria-label="Cases by sub-county and status"><table class="tbl"><thead><tr><th scope="col">Sub-county</th>';
    foreach ($st as $s2) echo '<th scope="col">'.e(t('st.'.$s2)).'</th>'; echo '</tr></thead><tbody>';
    foreach (array_keys(cfg('wards')) as $k) { echo '<tr><th scope="row">'.e($k).'</th>'; foreach ($st as $s2) echo '<td>'.e(count_fmt($grid[$k][$s2] ?? 0, $hide)).'</td>'; echo '</tr>'; }
    echo '</tbody></table></div><h2>By topic</h2><div class="scroll" role="region" tabindex="0" aria-label="Cases by topic"><table class="tbl"><thead><tr><th scope="col">Topic</th><th scope="col">Cases</th></tr></thead><tbody>';
    foreach ($pdo->query('SELECT kind, category, COUNT(*) n FROM cases GROUP BY kind, category ORDER BY n DESC') as $r) echo '<tr><th scope="row">'.e($r['kind'] === 'support' ? t('sp.'.$r['category']) : t('cat.'.$r['category'])).'</th><td>'.e(count_fmt((int)$r['n'], $hide)).'</td></tr>';
    echo '</tbody></table></div>';
  } elseif ($v === 'mentors') {
    echo '<h1>Mentor pool</h1><div class="scroll" role="region" tabindex="0" aria-label="Mentor pool"><table class="tbl"><thead><tr><th scope="col">Name</th><th scope="col">Focus</th><th scope="col">Area</th><th scope="col">Contact</th><th scope="col">Active</th></tr></thead><tbody>';
    foreach ($pdo->query('SELECT * FROM mentors ORDER BY active DESC, name') as $m) echo '<tr><td>'.e($m['name']).'</td><td>'.e($m['focus']).'</td><td>'.e($m['constituency']).'</td><td>'.e($m['contact_enc'] ? dec($m['contact_enc']) : '').'</td><td>'.tpl_form('mtoggle', '<button class="btn alt" type="submit">'.($m['active'] ? 'Yes, turn off' : 'No, turn on').'</button>', ['id' => $m['id']]).'</td></tr>';
    $o = '<option>All</option>'; foreach (array_keys(cfg('wards')) as $k) $o .= '<option>'.e($k).'</option>';
    echo '</tbody></table></div><div class="card"><h2>Add a mentor</h2>'.tpl_form('mentor', '<input name="name" maxlength="80" placeholder="Name" aria-label="Name" required><input name="focus" maxlength="80" placeholder="Focus, for example counselling" aria-label="Focus" required><select name="con" aria-label="Area">'.$o.'</select><input name="contact" maxlength="120" placeholder="Contact (optional)" aria-label="Contact"><button class="btn" type="submit">Add</button>').'</div>';
  } elseif ($v === 'audit') {
    echo '<h1>Audit log</h1><div class="scroll" role="region" tabindex="0" aria-label="Audit log"><table class="tbl"><thead><tr><th scope="col">When</th><th scope="col">Who</th><th scope="col">Action</th><th scope="col">Case</th><th scope="col">Note</th></tr></thead><tbody>';
    foreach ($pdo->query('SELECT a.*, s.username FROM audit_log a LEFT JOIN staff s ON s.id=a.staff_id ORDER BY a.id DESC LIMIT 100') as $a) echo '<tr><td>'.e($a['at']).'</td><td>'.e((string)$a['username']).'</td><td>'.e($a['action']).'</td><td>'.e((string)$a['case_id']).'</td><td>'.e((string)$a['note']).'</td></tr>';
    echo '</tbody></table></div>';
  } else {
    echo '<h1>Change password</h1>'.tpl_form('pw', '<label for="cur">Current password</label><input id="cur" name="cur" type="password" autocomplete="current-password" required><label for="nw">New password (12+ characters)</label><input id="nw" name="new" type="password" autocomplete="new-password" minlength="12" required><label for="ag">New password again</label><input id="ag" name="again" type="password" autocomplete="new-password" minlength="12" required><button class="btn" type="submit">Change</button>');
  }
}, true, true);
