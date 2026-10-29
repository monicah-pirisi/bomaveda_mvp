<?php
// Gateway callback. Set the gateway URL to https://YOUR-DOMAIN/ussd.php?k=YOUR_USSD_TOKEN
// Disabled (404) until USSD_TOKEN is set. Only the "text" field is read.
require __DIR__.'/../app/lib.php'; require __DIR__.'/../app/ussd.php';
$tok = (string)getenv('USSD_TOKEN'); $k = $_GET['k'] ?? '';
if ($tok === '' || !is_string($k) || !hash_equals($tok, $k)) { http_response_code(404); exit; }
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store');
echo ussd_run((string)($_POST['text'] ?? ''));
