<?php
// Front controller: Apache (FallbackResource) and PHP's dev server send every non-file URL here.
require __DIR__.'/../app/lib.php';
$path = '/'.trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$routes = ['/'=>'home','/submit-concern'=>'submit','/request-support'=>'support','/resources'=>'resources','/case'=>'case',
           '/auth'=>'auth','/admin'=>'admin','/simulator'=>'simulator','/offline'=>'offline','/about'=>'about'];
if (!isset($routes[$path])) {
  http_response_code(404);
  page('Not found', function () { echo '<h1>404</h1><p><a href="/">Bomaveda</a></p>'; });
  exit;
}
require __DIR__.'/../app/pages/'.$routes[$path].'.php';
