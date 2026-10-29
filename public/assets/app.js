(function () {
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
  var qe = document.getElementById('qe');
  function leave(e) { if (e) e.preventDefault(); try { sessionStorage.clear(); localStorage.clear(); } catch (x) {} location.replace(qe.href); }
  if (qe) qe.addEventListener('click', leave);
  var last = 0;
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var n = Date.now(); if (n - last < 600) leave(); last = n; } });

  // Stop double submits. A flag is used instead of disabling the button, so named buttons (Approve/Reject) still send their value.
  [].forEach.call(document.forms, function (f) {
    f.addEventListener('submit', function (e) {
      if (f.dataset.busy) { e.preventDefault(); return; }
      f.dataset.busy = '1';
      var b = e.submitter || f.querySelector('button[type=submit]'); if (b) { b.classList.add('busy'); b.setAttribute('aria-disabled', 'true'); }
    });
  });
  window.addEventListener('pageshow', function (e) { if (e.persisted) [].forEach.call(document.forms, function (f) { delete f.dataset.busy; [].forEach.call(f.querySelectorAll('.busy'), function (b) { b.classList.remove('busy'); b.removeAttribute('aria-disabled'); }); }); });

  var al = document.querySelector('[role=alert]'); if (al) { al.setAttribute('tabindex', '-1'); al.focus({ preventScroll: false }); }

  var f = document.getElementById('intake'); if (!f) return;
  document.documentElement.classList.add('js');
  var s = [].slice.call(f.querySelectorAll('.step')), i = 0, p = document.getElementById('prog');
  var back = document.getElementById('back'), next = document.getElementById('next'), send = document.getElementById('send');
  var bar = document.createElement('progress'); bar.max = s.length; bar.setAttribute('aria-hidden', 'true'); p.parentNode.insertBefore(bar, p.nextSibling);
  function show(n, move) {
    i = n; s.forEach(function (x, k) { x.classList.toggle('on', k === n); });
    p.textContent = p.dataset.t.replace('{n}', n + 1).replace('{t}', s.length); bar.value = n + 1;
    back.hidden = n === 0; next.hidden = n === s.length - 1; send.hidden = n !== s.length - 1; window.scrollTo(0, 0);
    if (move) { var lg = s[n].querySelector('legend'); if (lg) { lg.setAttribute('tabindex', '-1'); lg.focus(); } }
  }
  function ok() { var r = s[i].querySelectorAll('input,select,textarea'); for (var k = 0; k < r.length; k++) if (!r[k].checkValidity()) { r[k].reportValidity(); return false; } return true; }
  next.addEventListener('click', function () { if (ok()) show(i + 1, true); });
  back.addEventListener('click', function () { show(i - 1, true); });
  show(0);
})();
