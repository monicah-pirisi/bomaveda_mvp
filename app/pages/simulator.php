<?php
require __DIR__.'/../ussd.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'evidence') {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  $code = preg_replace('/\D/', '', (string)($_POST['code'] ?? ''));
  if (strlen($code) !== 12) { echo json_encode(['ok' => false, 'msg' => 'Invalid case code']); return; }
  $s = db()->prepare('SELECT id FROM cases WHERE code=?'); $s->execute([$code]);
  $caseId = $s->fetchColumn();
  if (!$caseId) { echo json_encode(['ok' => false, 'msg' => 'Case not found']); return; }
  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'msg' => 'No file uploaded']); return;
  }
  $type = (string)($_POST['type'] ?? '');
  if (!in_array($type, ['photo', 'video', 'voice', 'document'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'Invalid evidence type']); return;
  }
  $allowed = [
    'photo'    => ['image/jpeg', 'image/png', 'image/webp'],
    'video'    => ['video/mp4', 'video/webm', 'video/quicktime'],
    'voice'    => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm', 'audio/mp4'],
    'document' => ['application/pdf', 'application/msword',
                   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                   'text/plain'],
  ];
  $maxSize = ['photo' => 10_000_000, 'video' => 50_000_000, 'voice' => 20_000_000, 'document' => 10_000_000];
  $file = $_FILES['file'];
  $mime = mime_content_type($file['tmp_name']) ?: '';
  if (!in_array($mime, $allowed[$type], true)) {
    echo json_encode(['ok' => false, 'msg' => 'File type not allowed for ' . $type]); return;
  }
  if ($file['size'] > $maxSize[$type]) {
    $mb = intdiv($maxSize[$type], 1_000_000);
    echo json_encode(['ok' => false, 'msg' => "File too large (max {$mb}MB)"]); return;
  }
  $ext = match ($mime) {
    'image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp',
    'video/mp4' => '.mp4', 'video/webm' => '.webm', 'video/quicktime' => '.mov',
    'audio/mpeg' => '.mp3', 'audio/ogg' => '.ogg', 'audio/wav' => '.wav', 'audio/webm' => '.weba', 'audio/mp4' => '.m4a',
    'application/pdf' => '.pdf', 'text/plain' => '.txt',
    default => '.bin',
  };
  $storedName = bin2hex(random_bytes(16)) . $ext;
  $dir = dirname(__DIR__, 2) . '/storage/evidence';
  if (!is_dir($dir)) mkdir($dir, 0750, true);
  if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $storedName)) {
    echo json_encode(['ok' => false, 'msg' => 'Upload failed']); return;
  }
  $origName = mb_substr(basename($file['name']), 0, 255);
  db()->prepare('INSERT INTO evidence (case_id,type,original_name,stored_name,mime_type,size) VALUES (?,?,?,?,?,?)')
    ->execute([(int)$caseId, $type, $origName, $storedName, $mime, $file['size']]);
  echo json_encode(['ok' => true, 'msg' => 'Evidence attached']);
  return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); echo ussd_run((string)($_POST['text'] ?? '')); return;
}
if (!lang()) set_lang('en');
$keys = ['1','2','3','4','5','6','7','8','9','*','0','#'];
page('USSD demo', function () use ($keys) {
  $code = ussd_code();
  echo '<h1>USSD Simulator</h1>';
  echo '<p class="lead">Try the Bomaveda USSD experience from your browser. Reports made here are saved as real cases.</p>';
  echo '<div class="sim"><div>';
  echo '<div class="phone">';
  echo '<div class="phbar"><span>Bomaveda USSD</span><span id="lng">EN</span></div>';
  echo '<pre id="screen" aria-live="polite">Phone is idle.'."\n\n".'Press Call to dial '.e($code).'</pre>';
  echo '<div class="dial"><input id="inp" data-code="'.e($code).'" autocomplete="off" aria-label="USSD code"><button type="button" id="del" class="key" aria-label="Delete">&#9003;</button><button type="button" id="go" class="btn call" data-call="Call" data-send="Send">Call</button></div>';
  echo '<div class="keys">';
  foreach ($keys as $k) echo '<button type="button" class="key" data-k="'.e($k).'">'.e($k).'</button>';
  echo '</div></div>';

  echo '<div id="evidence" class="ev-panel" hidden>';
  echo '<h3>Attach Supporting Evidence</h3>';
  echo '<p>Strengthen your report with evidence. All files are stored securely.</p>';
  echo '<div class="ev-grid">';
  echo '<label class="ev-opt"><input type="file" accept="image/jpeg,image/png,image/webp" data-type="photo" hidden><span class="ev-icon">&#128247;</span><span>Photo</span></label>';
  echo '<label class="ev-opt"><input type="file" accept="video/mp4,video/webm,video/quicktime" data-type="video" hidden><span class="ev-icon">&#127909;</span><span>Video</span></label>';
  echo '<label class="ev-opt"><input type="file" accept="audio/mpeg,audio/ogg,audio/wav,audio/webm,audio/mp4" data-type="voice" hidden><span class="ev-icon">&#127908;</span><span>Voice Note</span></label>';
  echo '<label class="ev-opt"><input type="file" accept=".pdf,.doc,.docx,.txt" data-type="document" hidden><span class="ev-icon">&#128196;</span><span>Document</span></label>';
  echo '</div>';
  echo '<div id="ev-status"></div>';
  echo '<button type="button" id="ev-skip" class="btn alt ev-skip">Skip &mdash; finish without evidence</button>';
  echo '</div>';

  echo '</div><div>';
  echo '<div class="card"><h2>How to use</h2><ol>';
  echo '<li>The code <strong>'.e($code).'</strong> is pre-filled. Press <strong>Call</strong> to dial.</li>';
  echo '<li>Type a number (or tap the keypad) and press <strong>Send</strong>.</li>';
  echo '<li>Follow the menu: choose a language, category, sub-county, and describe the concern.</li>';
  echo '<li>After submitting, you can attach evidence (photo, video, voice note, or document).</li>';
  echo '<li>Type <strong>0</strong> at the description step for an Emergency flag.</li>';
  echo '</ol></div>';
  echo '<div class="card"><h2>Menu options</h2><ul>';
  echo '<li><strong>1.</strong> Report a concern</li>';
  echo '<li><strong>2.</strong> Check report status</li>';
  echo '<li><strong>3.</strong> Emergency contacts</li>';
  echo '<li><strong>4.</strong> Safety information</li>';
  echo '<li><strong>0.</strong> Exit</li>';
  echo '</ul></div>';
  echo '<div class="card"><h2>Evidence types accepted</h2><ul>';
  echo '<li><strong>Photo</strong> &mdash; JPEG, PNG, WebP (max 10 MB)</li>';
  echo '<li><strong>Video</strong> &mdash; MP4, WebM, MOV (max 50 MB)</li>';
  echo '<li><strong>Voice Note</strong> &mdash; MP3, OGG, WAV, WebM (max 20 MB)</li>';
  echo '<li><strong>Document</strong> &mdash; PDF, Word, text (max 10 MB)</li>';
  echo '</ul></div>';
  echo '</div></div><script src="/assets/sim.js"></script>';
}, true);
