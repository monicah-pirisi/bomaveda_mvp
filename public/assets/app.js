(function () {
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js');
  var qe = document.getElementById('qe');
  function leave(e) { if (e) e.preventDefault(); try { sessionStorage.clear(); localStorage.clear(); } catch (x) {} location.replace(qe.href); }
  if (qe) qe.addEventListener('click', leave);
  var last = 0;
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { var n = Date.now(); if (n - last < 600) leave(); last = n; } });

  var navToggle = document.getElementById('nav-toggle');
  var mainNav = document.getElementById('main-nav');
  if (navToggle && mainNav) {
    function closeNav() {
      mainNav.classList.remove('open');
      navToggle.setAttribute('aria-expanded', 'false');
      navToggle.textContent = '☰';
    }
    navToggle.addEventListener('click', function () {
      var isOpen = mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      navToggle.textContent = isOpen ? '✕' : '☰';
    });
    [].forEach.call(mainNav.querySelectorAll('a'), function (a) {
      a.addEventListener('click', closeNav);
    });
    document.addEventListener('click', function (e) {
      if (mainNav.classList.contains('open') && !mainNav.contains(e.target) && e.target !== navToggle) closeNav();
    });
  }

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

  // --- INTERACTIVITY ENGINE ---

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

  // Auto-reveal: children inside .grid, .how, .stats-row get staggered entrance
  if ('IntersectionObserver' in window) {
    var grids = document.querySelectorAll('.grid, .how, .stats-row');
    [].forEach.call(grids, function (grid) {
      var cards = grid.children;
      if (!cards.length) return;
      [].forEach.call(cards, function (c) { c.style.opacity = '0'; c.style.transform = 'translateY(24px)'; });
      var gridObs = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            [].forEach.call(cards, function (card, idx) {
              setTimeout(function () {
                card.style.transition = 'opacity .6s cubic-bezier(.23,1,.32,1), transform .6s cubic-bezier(.23,1,.32,1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
              }, 80 + idx * 120);
            });
            gridObs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.06 });
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
        if (isNaN(target)) return;
        var prefix = el.dataset.prefix || '';
        var suffix = el.dataset.suffix || '';
        var duration = 1800;
        var startTime = null;
        function step(ts) {
          if (!startTime) startTime = ts;
          var progress = Math.min((ts - startTime) / duration, 1);
          var eased = 1 - Math.pow(1 - progress, 3);
          var current = Math.round(target * eased);
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
            hero.style.backgroundPositionY = (y * 0.35) + 'px';
          }
          heroScheduled = false;
        });
      }
    }, { passive: true });
  }

  // Scroll-to-top button
  var topBtn = document.createElement('button');
  topBtn.className = 'scroll-top';
  topBtn.innerHTML = '↑';
  topBtn.setAttribute('aria-label', 'Scroll to top');
  topBtn.type = 'button';
  document.body.appendChild(topBtn);
  var topShown = false;
  window.addEventListener('scroll', function () {
    var show = window.pageYOffset > 400;
    if (show !== topShown) { topShown = show; topBtn.classList.toggle('visible', show); }
  }, { passive: true });
  topBtn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });

  // Click ripple on buttons
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn');
    if (!btn || btn.hasAttribute('disabled') || btn.getAttribute('aria-disabled') === 'true') return;
    var ripple = document.createElement('span');
    ripple.className = 'ripple';
    var rect = btn.getBoundingClientRect();
    ripple.style.left = (e.clientX - rect.left) + 'px';
    ripple.style.top = (e.clientY - rect.top) + 'px';
    btn.appendChild(ripple);
    setTimeout(function () { ripple.remove(); }, 700);
  });

  // Header shrink on scroll
  var bar = document.querySelector('.bar');
  if (bar) {
    var barShrunk = false;
    window.addEventListener('scroll', function () {
      var shrink = window.pageYOffset > 60;
      if (shrink !== barShrunk) {
        barShrunk = shrink;
        bar.style.boxShadow = shrink ? '0 2px 16px rgba(31,41,55,.1)' : '';
        bar.style.transition = 'box-shadow .3s ease';
      }
    }, { passive: true });
  }

  // Tilt effect on cards
  if (window.matchMedia('(hover: hover)').matches) {
    document.addEventListener('mousemove', function (e) {
      var card = e.target.closest('.grid .card, .how .card, .stat-item');
      if (!card) return;
      var rect = card.getBoundingClientRect();
      var x = (e.clientX - rect.left) / rect.width - 0.5;
      var y = (e.clientY - rect.top) / rect.height - 0.5;
      card.style.transform = 'translateY(-3px) perspective(600px) rotateX(' + (-y * 4) + 'deg) rotateY(' + (x * 4) + 'deg)';
    });
    document.addEventListener('mouseleave', function (e) {
      var card = e.target.closest('.grid .card, .how .card, .stat-item');
      if (card) card.style.transform = '';
    }, true);
  }

  // Multi-step form wizard
  var f = document.getElementById('intake'); if (!f) return;
  document.documentElement.classList.add('js');
  var s = [].slice.call(f.querySelectorAll('.step')), i = 0, p = document.getElementById('prog');
  var back = document.getElementById('back'), next = document.getElementById('next'), send = document.getElementById('send');
  var bar2 = document.createElement('progress'); bar2.max = s.length; bar2.setAttribute('aria-hidden', 'true'); p.parentNode.insertBefore(bar2, p.nextSibling);
  function show(n, move) {
    i = n; s.forEach(function (x, k) { x.classList.toggle('on', k === n); });
    p.textContent = p.dataset.t.replace('{n}', n + 1).replace('{t}', s.length); bar2.value = n + 1;
    back.hidden = n === 0; next.hidden = n === s.length - 1; send.hidden = n !== s.length - 1; window.scrollTo(0, 0);
    if (move) { var lg = s[n].querySelector('legend'); if (lg) { lg.setAttribute('tabindex', '-1'); lg.focus(); } }
  }
  function ok() { var r = s[i].querySelectorAll('input,select,textarea'); for (var k = 0; k < r.length; k++) if (!r[k].checkValidity()) { r[k].reportValidity(); return false; } return true; }
  next.addEventListener('click', function () { if (ok()) show(i + 1, true); });
  back.addEventListener('click', function () { show(i - 1, true); });
  show(0);
})();
