<?php
page('Bomaveda', function () {
  echo '<h1>Hakuna mtandao / No connection</h1><p class="lead">Jaribu tena baadaye. Try again later.</p>';
  if (getenv('USSD_LIVE') === '1') echo '<p class="note">USSD: <strong>'.e(ussd_code()).'</strong></p>';
});
