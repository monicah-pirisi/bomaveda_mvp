<?php
declare(strict_types=1);
require __DIR__.'/../app/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

function api_error(int $code, string $msg): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function api_ok(array $data = []): never {
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = '/'.trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$endpoint = str_replace('/api/', '', $path);

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- POST /api/concerns --- Submit a community concern
if ($endpoint === 'concerns' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) api_error(400, 'Invalid JSON body');

    $cat = (string)($input['category'] ?? '');
    $urg = (string)($input['urgency'] ?? 'moderate');
    $con = (string)($input['constituency'] ?? '');
    $ward = (string)($input['ward'] ?? '');
    $desc = trim((string)($input['description'] ?? ''));
    $village = trim((string)($input['village'] ?? ''));
    $landmark = trim((string)($input['landmark'] ?? ''));
    $cm = (string)($input['contact_method'] ?? '');
    $cv = trim((string)($input['contact_value'] ?? ''));
    $safe = !empty($input['safe_to_call']);

    if ($village !== '' || $landmark !== '') {
        $desc = implode(' | ', array_filter([$village, $landmark])) . "\n\n" . $desc;
    }

    if (!in_array($cat, cfg('categories'), true))
        api_error(422, 'Invalid category. Allowed: ' . implode(', ', cfg('categories')));
    if (!in_array($urg, cfg('urgency'), true))
        api_error(422, 'Invalid urgency. Allowed: ' . implode(', ', cfg('urgency')));
    if (!isset(cfg('wards')[$con]))
        api_error(422, 'Invalid constituency. Allowed: ' . implode(', ', array_keys(cfg('wards'))));
    if (!in_array($ward, cfg('wards')[$con], true))
        api_error(422, 'Invalid ward for constituency ' . $con);
    if (mb_strlen($desc) < 10 || mb_strlen($desc) > 2000)
        api_error(422, 'Description must be 10-2000 characters');
    if ($cv !== '' && (!in_array($cm, ['phone', 'email'], true) || mb_strlen($cv) > 120))
        api_error(422, 'Contact requires a valid method (phone/email) and value (max 120 chars)');

    if (!throttle('submit', 10, 60))
        api_error(429, 'Too many submissions. Please wait before trying again.');

    $code = create_case($cat, $con, $ward, $desc, $urg, $cm, $cv, $safe);
    if (!$code) api_error(500, 'Could not create case. Please try again.');

    hit('submit');
    http_response_code(201);
    api_ok([
        'case_code' => show_code($code),
        'message'   => 'Concern submitted successfully. Save your case code.'
    ]);
}

// --- POST /api/support-requests --- Request mentorship or counselling support
if ($endpoint === 'support-requests' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) api_error(400, 'Invalid JSON body');

    $type = (string)($input['type'] ?? '');
    $con = (string)($input['constituency'] ?? '');
    $ward = (string)($input['ward'] ?? '');
    $msg = trim((string)($input['message'] ?? ''));
    $cm = (string)($input['contact_method'] ?? '');
    $cv = trim((string)($input['contact_value'] ?? ''));
    $safe = !empty($input['safe_to_call']);

    if (!in_array($type, cfg('support_types'), true))
        api_error(422, 'Invalid support type. Allowed: ' . implode(', ', cfg('support_types')));
    if (!isset(cfg('wards')[$con]))
        api_error(422, 'Invalid constituency');
    if (!in_array($ward, cfg('wards')[$con], true))
        api_error(422, 'Invalid ward for constituency ' . $con);
    if (mb_strlen($msg) > 1000)
        api_error(422, 'Message must be under 1000 characters');
    if ($cv !== '' && (!in_array($cm, ['phone', 'email'], true) || mb_strlen($cv) > 120))
        api_error(422, 'Contact requires a valid method (phone/email) and value (max 120 chars)');

    if (!throttle('submit', 10, 60))
        api_error(429, 'Too many submissions. Please wait before trying again.');

    $code = create_case($type, $con, $ward, $msg === '' ? '-' : $msg, 'moderate', $cm, $cv, $safe, 'support');
    if (!$code) api_error(500, 'Could not create request. Please try again.');

    hit('submit');
    http_response_code(201);
    api_ok([
        'case_code' => show_code($code),
        'message'   => 'Support request submitted successfully. Save your case code.'
    ]);
}

// --- POST /api/cases/lookup --- Check a case by its code
if ($endpoint === 'cases/lookup' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) api_error(400, 'Invalid JSON body');

    $raw = (string)($input['code'] ?? '');
    if (!throttle('lookup', 5, 15))
        api_error(429, 'Too many lookups. Please wait.');

    $res = find_case($raw);
    if (!$res) { hit('lookup'); api_error(404, 'Case not found'); }

    $notes = [];
    $s = db()->prepare("SELECT body_enc, created_at FROM messages WHERE case_id=? AND author='staff' ORDER BY created_at");
    $s->execute([$res['id']]);
    foreach ($s as $m) $notes[] = ['message' => dec($m['body_enc']), 'date' => $m['created_at']];

    $statuses = cfg('status');
    $currentIdx = array_search($res['status'], $statuses, true);

    api_ok([
        'status'      => $res['status'],
        'status_label' => t('st.' . $res['status']),
        'progress'    => ($currentIdx !== false ? $currentIdx + 1 : 0) . '/' . count($statuses),
        'updated_at'  => $res['updated_at'],
        'messages'    => $notes,
    ]);
}

// --- POST /api/evidence --- Upload evidence for a case (multipart/form-data)
if ($endpoint === 'evidence' && $method === 'POST') {
    $code = preg_replace('/\D/', '', (string)($_POST['code'] ?? ''));
    if (strlen($code) !== 12) api_error(422, 'Invalid case code');

    $s = db()->prepare('SELECT id FROM cases WHERE code=?');
    $s->execute([$code]);
    $caseId = $s->fetchColumn();
    if (!$caseId) api_error(404, 'Case not found');

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK)
        api_error(400, 'No file uploaded or upload error');

    $type = (string)($_POST['type'] ?? '');
    if (!in_array($type, ['photo', 'video', 'voice', 'document'], true))
        api_error(422, 'Invalid evidence type. Allowed: photo, video, voice, document');

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
    if (!in_array($mime, $allowed[$type], true))
        api_error(415, 'File type not allowed for ' . $type . '. Detected: ' . $mime);
    if ($file['size'] > $maxSize[$type])
        api_error(413, 'File too large. Max ' . intdiv($maxSize[$type], 1_000_000) . 'MB for ' . $type);

    $ext = match ($mime) {
        'image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp',
        'video/mp4' => '.mp4', 'video/webm' => '.webm', 'video/quicktime' => '.mov',
        'audio/mpeg' => '.mp3', 'audio/ogg' => '.ogg', 'audio/wav' => '.wav', 'audio/webm' => '.weba', 'audio/mp4' => '.m4a',
        'application/pdf' => '.pdf', 'text/plain' => '.txt',
        default => '.bin',
    };

    $storedName = bin2hex(random_bytes(16)) . $ext;
    $dir = dirname(__DIR__) . '/storage/evidence';
    if (!is_dir($dir)) mkdir($dir, 0750, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $storedName))
        api_error(500, 'Upload failed');

    $origName = mb_substr(basename($file['name']), 0, 255);
    db()->prepare('INSERT INTO evidence (case_id, type, original_name, stored_name, mime_type, size) VALUES (?,?,?,?,?,?)')
        ->execute([(int)$caseId, $type, $origName, $storedName, $mime, $file['size']]);

    http_response_code(201);
    api_ok(['message' => 'Evidence attached successfully']);
}

// --- GET /api/resources --- List safety guides and emergency contacts
if ($endpoint === 'resources' && $method === 'GET') {
    $l = ($_GET['lang'] ?? 'en');
    if (!in_array($l, LANGS, true)) $l = 'en';

    $guides = [];
    foreach (cfg('guides') as $g) {
        $guides[] = ['title' => $g['title'][$l], 'body' => $g['body'][$l]];
    }

    $emergency = [];
    foreach (cfg('emergency') as $e) {
        $emergency[] = ['number' => $e['num'], 'label' => $e[$l]];
    }

    api_ok([
        'language'  => $l,
        'guides'    => $guides,
        'emergency' => $emergency,
    ]);
}

// --- GET /api/opportunities --- List active opportunities
if ($endpoint === 'opportunities' && $method === 'GET') {
    $l = ($_GET['lang'] ?? 'en');
    if (!in_array($l, LANGS, true)) $l = 'en';

    $type = (string)($_GET['type'] ?? '');
    $where = 'active = 1';
    $args = [];
    if (in_array($type, ['job', 'skill', 'scholarship', 'bursary'], true)) {
        $where .= ' AND type = ?';
        $args[] = $type;
    }

    $s = db()->prepare("SELECT id, type, title, title_sw, description, description_sw, provider, location, deadline, link FROM opportunities WHERE $where ORDER BY created_at DESC LIMIT 50");
    $s->execute($args);

    $rows = [];
    foreach ($s as $r) {
        $rows[] = [
            'id'          => (int)$r['id'],
            'type'        => $r['type'],
            'title'       => $l === 'sw' && $r['title_sw'] !== '' ? $r['title_sw'] : $r['title'],
            'description' => $l === 'sw' && $r['description_sw'] !== '' ? $r['description_sw'] : $r['description'],
            'provider'    => $r['provider'],
            'location'    => $r['location'],
            'deadline'    => $r['deadline'],
            'link'        => $r['link'],
        ];
    }

    api_ok(['opportunities' => $rows]);
}

// --- GET /api/wards --- List constituencies and wards
if ($endpoint === 'wards' && $method === 'GET') {
    api_ok(['constituencies' => cfg('wards')]);
}

// --- GET /api/categories --- List concern categories
if ($endpoint === 'categories' && $method === 'GET') {
    $l = ($_GET['lang'] ?? 'en');
    if (!in_array($l, LANGS, true)) $l = 'en';
    set_lang($l);

    $cats = [];
    foreach (cfg('categories') as $c) {
        $cats[] = ['key' => $c, 'label' => t('cat.' . $c)];
    }

    api_ok(['categories' => $cats]);
}

// --- GET /api/health --- Health check
if ($endpoint === 'health' && $method === 'GET') {
    try {
        db()->query('SELECT 1');
        api_ok(['status' => 'healthy', 'database' => 'connected', 'timestamp' => date('c')]);
    } catch (Throwable $e) {
        api_error(503, 'Database unreachable');
    }
}

api_error(404, 'Endpoint not found. See /docs/api for documentation.');
