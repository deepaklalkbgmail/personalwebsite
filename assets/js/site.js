/* =============================================================================
   Site behaviour. Everything here is progressive enhancement: the page is fully
   readable and navigable if this file fails to load or throws.
   ========================================================================== */
(function () {
  'use strict';

  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* --- loader ------------------------------------------------------------
     Dismissed on `load`, but never held longer than HARD_LIMIT. CSS carries an
     independent fail-safe, so a JS error here still cannot trap the page. */
  (function loader() {
    var el = $('#loader');
    if (!el) { return; }

    var MIN_MS = 700;        // let the boot sequence read as intentional
    var HARD_LIMIT = 2600;   // absolute ceiling, whatever the network is doing
    var start = Date.now();
    var done = false;

    function dismiss() {
      if (done) { return; }
      done = true;
      el.classList.add('is-done');
      document.body.classList.remove('is-loading');
      window.setTimeout(function () {
        if (el.parentNode) { el.parentNode.removeChild(el); }
      }, 450);
    }

    function finish() {
      var waited = Date.now() - start;
      window.setTimeout(dismiss, Math.max(0, MIN_MS - waited));
    }

    if (document.readyState === 'complete') { finish(); }
    else { window.addEventListener('load', finish); }

    window.setTimeout(dismiss, HARD_LIMIT);
    // returning via the back/forward cache must never restore the overlay
    window.addEventListener('pageshow', dismiss);
  }());

  /* --- theme ------------------------------------------------------------- */
  (function theme() {
    var root = document.documentElement;
    var btn = $('#themeToggle');
    var btnMobile = $('#themeToggleMobile');

    function apply(t) {
      root.setAttribute('data-theme', t);
      try { localStorage.setItem('theme', t); } catch (e) { /* private mode */ }
      $$('[data-theme-label]').forEach(function (n) { n.textContent = t === 'dark' ? 'Light mode' : 'Dark mode'; });
    }

    function toggle() {
      apply(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    }

    if (btn) { btn.addEventListener('click', toggle); }
    if (btnMobile) { btnMobile.addEventListener('click', toggle); }
    apply(root.getAttribute('data-theme') || 'dark');
  }());

  /* --- mobile drawer ----------------------------------------------------- */
  (function drawer() {
    var toggle = $('#menuToggle');
    var close = $('#menuClose');
    var panel = $('#drawer');
    if (!toggle || !panel) { return; }

    function setOpen(open) {
      document.body.classList.toggle('nav-open', open);
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) { var first = panel.querySelector('a'); if (first) { first.focus(); } }
    }

    toggle.addEventListener('click', function () { setOpen(!document.body.classList.contains('nav-open')); });
    if (close) { close.addEventListener('click', function () { setOpen(false); toggle.focus(); }); }
    $$('a', panel).forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && document.body.classList.contains('nav-open')) { setOpen(false); toggle.focus(); }
    });
    // a resize into desktop layout must not leave the body scroll-locked
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 900 && document.body.classList.contains('nav-open')) { setOpen(false); }
    });
  }());

  /* --- timeline filter (panels are server-rendered) ---------------------- */
  (function timeline() {
    var buttons = $$('[data-tl-filter]');
    var panels = $$('[data-tl-panel]');
    if (!buttons.length || !panels.length) { return; }

    function select(name) {
      buttons.forEach(function (b) { b.setAttribute('aria-selected', String(b.dataset.tlFilter === name)); });
      panels.forEach(function (p) { p.hidden = p.dataset.tlPanel !== name; });
      reveal();
    }

    buttons.forEach(function (b) {
      b.addEventListener('click', function () { select(b.dataset.tlFilter); });
    });
    select(buttons[0].dataset.tlFilter);
  }());

  /* --- reveal on scroll --------------------------------------------------
     Content is only ever hidden by the animation, so it always gets shown:
     by the observer when scrolled into view, or by the fallback timer. */
  function reveal() {
    var items = $$('.tl-item:not(.is-visible)');
    function showAll() { items.forEach(function (i) { i.classList.add('is-visible'); }); }

    if (!('IntersectionObserver' in window)) { showAll(); return; }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px' });
    items.forEach(function (i) { io.observe(i); });

    // safety net: nothing stays invisible for longer than this, ever
    window.setTimeout(showAll, 2500);
  }
  reveal();

  /* --- article table of contents highlighting ---------------------------- */
  (function scrollspy() {
    var links = $$('.toc a');
    if (!links.length || !('IntersectionObserver' in window)) { return; }

    var map = {};
    var targets = links.map(function (a) {
      var t = document.getElementById(a.getAttribute('href').slice(1));
      if (t) { map[t.id] = a; }
      return t;
    }).filter(Boolean);

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) { return; }
        links.forEach(function (a) { a.style.color = ''; });
        var active = map[e.target.id];
        if (active) { active.style.color = 'var(--accent)'; }
      });
    }, { rootMargin: '-80px 0px -70% 0px' });

    targets.forEach(function (t) { io.observe(t); });
  }());

  /* --- footer year ------------------------------------------------------- */
  $$('[data-year]').forEach(function (n) { n.textContent = String(new Date().getFullYear()); });
}());
