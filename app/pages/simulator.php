<?php
// Phone simulator for demos (layout follows the approved reference). Turn off with SIM_ENABLED=0.
require __DIR__.'/../ussd.php';
if (getenv('SIM_ENABLED') !== '1') { http_response_code(404); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); echo ussd_run((string)($_POST['text'] ?? '')); return;
}
if (!lang()) set_lang('en');
$keys = ['1','2','3','4','5','6','7','8','9','*','0','#'];
page('USSD demo', function () use ($keys) {
  $code = ussd_code();
  echo '<h1>USSD demo</h1><p class="note">Demo only. Reports made here are saved as test cases.</p><div class="sim"><div class="phone">';
  echo '<div class="phbar"><span>Bomaveda</span><span id="lng">EN</span></div><pre id="screen" aria-live="polite">Phone is idle.'."\n\n".'Enter a USSD code below and press Call to begin.</pre>';
  echo '<div class="dial"><input id="inp" data-code="'.e($code).'" autocomplete="off" aria-label="USSD code"><button type="button" id="del" class="key" aria-label="Delete">&#9003;</button><button type="button" id="go" class="btn call" data-call="Call" data-send="Send">Call</button></div><div class="keys">';
  foreach ($keys as $k) echo '<button type="button" class="key" data-k="'.e($k).'">'.e($k).'</button>';
  echo '</div></div><div><div class="card"><h2>How to use</h2><ol><li>The code <strong>'.e($code).'</strong> is already entered. Press Call to dial it.</li><li>Type a number (or tap the keypad) and press Send. Use &#9003; to correct a mistake.</li>';
  echo '<li>Follow the on-screen menu: choose a language, then a category, sub-county, and describe your concern.</li><li>Type 0 at the description step to trigger the Emergency flag instead of a normal report.</li></ol></div>';
  echo '<div class="card"><h2>Main menu options</h2><ul><li><strong>1.</strong> Report a concern</li><li><strong>2.</strong> Check report status (use a case number from a previous session)</li><li><strong>3.</strong> Emergency contacts</li><li><strong>4.</strong> Safety information</li><li><strong>0.</strong> Exit</li></ul></div></div></div><script src="/assets/sim.js"></script>';
}, true);
