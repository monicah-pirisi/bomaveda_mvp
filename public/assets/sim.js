(function () {
  var inp = document.getElementById('inp'), scr = document.getElementById('screen'), go = document.getElementById('go'), lng = document.getElementById('lng');
  var evPanel = document.getElementById('evidence'), evStatus = document.getElementById('ev-status'), evSkip = document.getElementById('ev-skip');
  var code = inp.dataset.code, inputs = null, lastCode = null;

  function idle() { inputs = null; lastCode = null; go.textContent = go.dataset.call; inp.value = code; lng.textContent = 'EN'; evPanel.hidden = true; evStatus.textContent = ''; }

  function showEvidence(caseCode) {
    lastCode = caseCode;
    evPanel.hidden = false;
    evStatus.textContent = '';
    evPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  function send(text) {
    fetch('/simulator', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'text=' + encodeURIComponent(text) })
      .then(function (r) { return r.text(); })
      .then(function (t) {
        if (t.indexOf('CON ') === 0) { scr.textContent = t.slice(4); inp.value = ''; go.textContent = go.dataset.send; inp.focus(); }
        else {
          var body = t.replace(/^END /, '');
          scr.textContent = body;
          var m = body.match(/(\d{4})-(\d{4})-(\d{4})/);
          var l = lng.textContent;
          if (m) {
            var caseCode = m[1] + m[2] + m[3];
            go.textContent = go.dataset.call;
            inp.value = code;
            lng.textContent = l;
            inputs = null;
            showEvidence(caseCode);
          } else {
            idle();
            lng.textContent = l;
          }
        }
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

  [].forEach.call(evPanel.querySelectorAll('input[type=file]'), function (fi) {
    fi.addEventListener('change', function () {
      if (!fi.files.length || !lastCode) return;
      var type = fi.dataset.type;
      var fd = new FormData();
      fd.append('action', 'evidence');
      fd.append('code', lastCode);
      fd.append('type', type);
      fd.append('file', fi.files[0]);
      evStatus.textContent = 'Uploading ' + type + '...';
      evStatus.className = 'ev-msg';
      fetch('/simulator', { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (j.ok) {
            evStatus.textContent = 'Attached: ' + fi.files[0].name;
            evStatus.className = 'ev-msg ev-ok';
          } else {
            evStatus.textContent = j.msg || 'Upload failed';
            evStatus.className = 'ev-msg ev-err';
          }
          fi.value = '';
        })
        .catch(function () {
          evStatus.textContent = 'Connection error';
          evStatus.className = 'ev-msg ev-err';
          fi.value = '';
        });
    });
  });

  evSkip.addEventListener('click', function () { evPanel.hidden = true; idle(); });

  idle();
})();
