(function () {
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
  var qe = document.getElementById('qe');
  function leave(e) { if (e) e.preventDefault(); try { sessionStorage.clear(); localStorage.clear(); } catch (x) {} location.replace(qe.href); }
  if (qe) qe.addEventListener('click', leave);
  var last = 0;
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var n = Date.now(); if (n - last < 600) leave(); last = n; } });

  [].forEach.call(document.forms, function (f) {
    f.addEventListener('submit', function (e) {
      if (f.dataset.busy) { e.preventDefault(); return; }
      f.dataset.busy = '1';
      var b = e.submitter || f.querySelector('button[type=submit]'); if (b) { b.classList.add('busy'); b.setAttribute('aria-disabled', 'true'); }
    });
  });
  window.addEventListener('pageshow', function (e) { if (e.persisted) [].forEach.call(document.forms, function (f) { delete f.dataset.busy; [].forEach.call(f.querySelectorAll('.busy'), function (b) { b.classList.remove('busy'); b.removeAttribute('aria-disabled'); }); }); });

  var al = document.querySelector('[role=alert]'); if (al) { al.setAttribute('tabindex', '-1'); al.focus({ preventScroll: false }); }

  var catSel = document.querySelector('select[name=category]');
  if (catSel) {
    catSel.addEventListener('change', function () {
      var details = document.querySelectorAll('.cat-detail');
      for (var d = 0; d < details.length; d++) details[d].hidden = details[d].dataset.cat !== catSel.value;
    });
  }

  // Scroll-reveal: Intersection Observer for .reveal, .reveal-left, .reveal-scale
  if ('IntersectionObserver' in window) {
    var revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-scale');
    if (revealEls.length) {
      var revealObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            revealObs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      [].forEach.call(revealEls, function (el) { revealObs.observe(el); });
    }
  } else {
    [].forEach.call(document.querySelectorAll('.reveal, .reveal-left, .reveal-scale'), function (el) {
      el.classList.add('visible');
    });
  }

  // Auto-reveal: cards inside .grid and .how get staggered reveal
  if ('IntersectionObserver' in window) {
    var grids = document.querySelectorAll('.grid, .how, .stats-row');
    [].forEach.call(grids, function (grid) {
      var cards = grid.children;
      if (!cards.length) return;
      var gridObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            [].forEach.call(cards, function (card, idx) {
              card.style.transitionDelay = (idx * 0.1) + 's';
              card.style.opacity = '0';
              card.style.transform = 'translateY(20px)';
              card.style.transition = 'opacity .6s cubic-bezier(.23,1,.32,1), transform .6s cubic-bezier(.23,1,.32,1)';
              setTimeout(function () {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
              }, 60 + idx * 100);
            });
            gridObs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.08 });
      gridObs.observe(grid);
    });
  }

  // Animated counters
  var counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    var counterObs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var el = entry.target;
        counterObs.unobserve(el);
        var target = parseInt(el.dataset.count, 10);
        var prefix = el.dataset.prefix || '';
        var suffix = el.dataset.suffix || '';
        var duration = 1600;
        var start = 0;
        var startTime = null;
        function step(ts) {
          if (!startTime) startTime = ts;
          var progress = Math.min((ts - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = Math.round(start + (target - start) * eased);
          el.textContent = prefix + current.toLocaleString() + suffix;
          if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
      });
    }, { threshold: 0.3 });
    [].forEach.call(counters, function (el) { counterObs.observe(el); });
  }

  // Parallax hero on scroll
  var hero = document.querySelector('.hero');
  if (hero) {
    var heroScheduled = false;
    window.addEventListener('scroll', function () {
      if (!heroScheduled) {
        heroScheduled = true;
        requestAnimationFrame(function () {
          var y = window.pageYOffset;
          if (y < hero.offsetHeight + 200) {
            hero.style.backgroundPositionY = (y * 0.3) + 'px';
          }
          heroScheduled = false;
        });
      }
    }, { passive: true });
  }

  // Active nav link highlight on scroll (for home page sections)
  var bands = document.querySelectorAll('.band[id]');
  if (bands.length > 1) {
    var navLinks = document.querySelectorAll('.bar nav a');
    window.addEventListener('scroll', function () {
      var scrollPos = window.pageYOffset + 120;
      [].forEach.call(bands, function (band) {
        if (band.offsetTop <= scrollPos && band.offsetTop + band.offsetHeight > scrollPos) {
          var id = band.id;
          [].forEach.call(navLinks, function (link) {
            if (link.getAttribute('href') === '#' + id) link.classList.add('active');
            else link.classList.remove('active');
          });
        }
      });
    }, { passive: true });
  }

  // Multi-step form wizard
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
