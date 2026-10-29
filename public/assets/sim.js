(function () {
  var inp = document.getElementById('inp'), scr = document.getElementById('screen'), go = document.getElementById('go'), lng = document.getElementById('lng');
  var code = inp.dataset.code, inputs = null;
  function idle() { inputs = null; go.textContent = go.dataset.call; inp.value = code; lng.textContent = 'EN'; }
  function send(text) {
    fetch('/simulator', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'text=' + encodeURIComponent(text) })
      .then(function (r) { return r.text(); })
      .then(function (t) {
        if (t.indexOf('CON ') === 0) { scr.textContent = t.slice(4); inp.value = ''; go.textContent = go.dataset.send; inp.focus(); }
        else { scr.textContent = t.replace(/^END /, ''); var l = lng.textContent; idle(); lng.textContent = l; }
      })
      .catch(function () { scr.textContent = 'Connection closed'; idle(); });
  }
  go.addEventListener('click', function () {
    if (inputs === null) { if (inp.value.trim() !== code) { scr.textContent = 'Invalid code.'; return; } inputs = []; send(''); return; }
    var v = inp.value.replace(/[*#]/g, '').trim(); if (v === '') return;
    inputs.push(v); lng.textContent = inputs[0] === '2' ? 'SW' : 'EN'; send(inputs.join('*'));
  });
  document.getElementById('del').addEventListener('click', function () { inp.value = inp.value.slice(0, -1); });
  [].forEach.call(document.querySelectorAll('[data-k]'), function (b) { b.addEventListener('click', function () { inp.value += b.dataset.k; inp.focus(); }); });
  inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); go.click(); } });
  idle();
})();
