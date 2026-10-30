/* assets/js/main.js - small helpers for the whole site */

// Mobile menu button
document.querySelectorAll('.nav-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var links = document.getElementById('nav-links') || document.querySelector('.admin-nav');
    var open = links.classList.toggle('open');
    btn.setAttribute('aria-expanded', open);
  });
});

// Ask "Are you sure?" on any button/form marked data-confirm="message"
document.addEventListener('submit', function (ev) {
  var msg = ev.target.getAttribute('data-confirm');
  if (msg && !confirm(msg)) ev.preventDefault();
});

// Live image preview for <input type="file" data-preview="#imgId">
document.querySelectorAll('input[data-preview]').forEach(function (input) {
  input.addEventListener('change', function () {
    var img = document.querySelector(input.getAttribute('data-preview'));
    if (img && input.files[0]) {
      img.src = URL.createObjectURL(input.files[0]); img.hidden = false;
      var letter = img.parentNode.querySelector('.avatar-letter');   // profile picture: hide the initial placeholder
      if (letter) letter.hidden = true;
    }
  });
});

// Hero video: if the browser blocks autoplay, just show the poster
document.querySelectorAll('video[autoplay]').forEach(function (v) {
  v.play && v.play().catch(function () {});
});

// Password fields: the eye button (built by pw_field() in includes/init.php)
// switches the input between hidden and visible so you can check what you typed.
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = btn.parentNode.querySelector('input');
    if (!input) return;
    var show = input.type === 'password';          // clicking again hides it
    input.type = show ? 'text' : 'password';
    btn.classList.toggle('is-visible', show);      // swaps eye <-> eye-with-slash
    btn.setAttribute('aria-pressed', show);
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.setAttribute('title', show ? 'Hide password' : 'Show password');
    input.focus();                                 // keep the caret in the field
  });
});

// Fullscreen lightbox for review photos: click a .js-lightbox thumbnail to see
// the full image, click again (or the X, or press Esc) to close. With JS off
// the link simply opens the image file.
(function () {
  var box = null;
  function close() {
    if (!box) return;
    box.parentNode.removeChild(box);
    box = null;
    document.body.classList.remove('lightbox-open');
  }
  document.querySelectorAll('a.js-lightbox').forEach(function (link) {
    link.addEventListener('click', function (ev) {
      ev.preventDefault();
      close();
      box = document.createElement('div');
      box.className = 'lightbox';
      box.setAttribute('role', 'dialog');
      box.setAttribute('aria-label', 'Full-size photo');
      var img = document.createElement('img');
      img.src = link.getAttribute('href');
      var thumb = link.querySelector('img');
      img.alt = thumb ? thumb.alt : 'Full-size review photo';
      var x = document.createElement('button');
      x.type = 'button';
      x.className = 'lightbox-close';
      x.setAttribute('aria-label', 'Close');
      x.innerHTML = '&times;';
      box.appendChild(x);
      box.appendChild(img);
      box.addEventListener('click', close);         // X, backdrop or photo all close it
      document.body.appendChild(box);
      document.body.classList.add('lightbox-open');
      x.focus();
    });
  });
  document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') close(); });
})();

// Product gallery slider (product.php): prev/next buttons, thumbnails, arrow keys
// and swipe. A playing video is paused when you slide away from it.
// Fullscreen: clicking a photo, a video's play button or the expand icon shows the whole
// gallery fullscreen (arrows, swipe and keys still work); Esc or the X closes it.
document.querySelectorAll('[data-gallery]').forEach(function (g) {
  var stage = g.querySelector('.gallery-stage');
  var track = g.querySelector('.gallery-track');
  var slides = g.querySelectorAll('.gallery-slide');
  var thumbs = g.querySelectorAll('.gallery-thumb');
  var count = g.querySelector('.gallery-count');
  var cur = 0;

  function show(i) {
    cur = (i + slides.length) % slides.length;               // wrap around both ends
    track.style.transform = 'translateX(' + (-cur * 100) + '%)';
    slides.forEach(function (s, n) {
      s.setAttribute('aria-hidden', n !== cur);
      var v = s.querySelector('video');
      if (v && n !== cur) v.pause();
    });
    thumbs.forEach(function (t, n) { t.classList.toggle('active', n === cur); });
    if (thumbs[cur]) thumbs[cur].scrollIntoView({ block: 'nearest', inline: 'nearest' });
    if (count) count.textContent = (cur + 1) + ' / ' + slides.length;
  }

  // ---- Fullscreen ----
  // Uses the browser's real fullscreen when it has one; otherwise (e.g. iPhone) the stage is
  // simply stretched over the whole window with CSS (.is-fullscreen).
  function isOpen() { return stage.classList.contains('is-fullscreen'); }
  function setOpen(open) {
    stage.classList.toggle('is-fullscreen', open);
    document.body.classList.toggle('lightbox-open', open);  // stops the page scrolling behind it
    slides.forEach(function (s) {
      var v = s.querySelector('video');
      if (v) { v.controls = open; if (!open) v.pause(); }
    });
  }
  function enter(playVideo) {
    if (!isOpen()) {
      setOpen(true);
      var req = stage.requestFullscreen || stage.webkitRequestFullscreen;
      if (req) { try { var p = req.call(stage); if (p && p.catch) p.catch(function () {}); } catch (e) {} }
    }
    var v = slides[cur] && slides[cur].querySelector('video');
    if (playVideo && v) { var pl = v.play(); if (pl && pl.catch) pl.catch(function () {}); }
    stage.focus();
  }
  function exit() {
    if (!isOpen()) return;
    var fsEl = document.fullscreenElement || document.webkitFullscreenElement;
    if (fsEl) (document.exitFullscreen || document.webkitExitFullscreen).call(document);
    setOpen(false);
  }
  stage.tabIndex = -1;
  stage.querySelectorAll('[data-fullscreen]').forEach(function (el) {
    el.addEventListener('click', function () { enter(el.classList.contains('gallery-play')); });
  });
  stage.querySelector('.gallery-close').addEventListener('click', exit);
  // Leaving the browser's fullscreen another way (Esc key, phone back gesture) closes it here too
  ['fullscreenchange', 'webkitfullscreenchange'].forEach(function (evt) {
    document.addEventListener(evt, function () {
      if (!(document.fullscreenElement || document.webkitFullscreenElement) && isOpen()) setOpen(false);
    });
  });
  document.addEventListener('keydown', function (ev) {
    if (!isOpen()) return;
    if (ev.key === 'Escape') exit();
    else if (ev.target.tagName !== 'VIDEO' && ev.key === 'ArrowLeft') show(cur - 1);
    else if (ev.target.tagName !== 'VIDEO' && ev.key === 'ArrowRight') show(cur + 1);
  });

  if (slides.length < 2) return;

  g.querySelector('.gallery-prev').addEventListener('click', function () { show(cur - 1); });
  g.querySelector('.gallery-next').addEventListener('click', function () { show(cur + 1); });
  thumbs.forEach(function (t, n) { t.addEventListener('click', function () { show(n); }); });

  g.tabIndex = 0;
  g.addEventListener('keydown', function (ev) {
    if (isOpen() || ev.target.tagName === 'VIDEO') return;   // fullscreen keys are handled above
    if (ev.key === 'ArrowLeft') show(cur - 1);
    if (ev.key === 'ArrowRight') show(cur + 1);
  });

  var startX = null;
  track.addEventListener('touchstart', function (ev) { startX = ev.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend', function (ev) {
    if (startX === null) return;
    var dx = ev.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 40) show(dx < 0 ? cur + 1 : cur - 1);
    startX = null;
  });
});

// Inbox badge on the mail icon: check for new order updates every 30 seconds
// (notifications.php?unread=1) and pop a small notice when a new one arrives.
(function () {
  var mail = document.querySelector('[data-unread-url]');
  if (!mail || !window.fetch) return;
  var badge = mail.querySelector('.cart-badge');
  var last = parseInt(badge.textContent, 10) || 0;
  function check() {
    if (document.hidden) return;                               // don't poll from background tabs
    fetch(mail.getAttribute('data-unread-url'), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        var n = d.unread || 0;
        badge.textContent = n;
        badge.hidden = n === 0;
        mail.setAttribute('aria-label', n ? 'Inbox (' + n + ' new)' : 'Inbox');
        if (n > last) {
          var t = document.createElement('a');
          t.className = 'inbox-toast';
          t.href = mail.href;
          t.textContent = 'You have a new message or order update. Open inbox';
          document.body.appendChild(t);
          setTimeout(function () { t.remove(); }, 8000);
        }
        last = n;
      })
      .catch(function () {});
  }
  setInterval(check, 30000);
  document.addEventListener('visibilitychange', check);
})();

// Admin light / dark mode button (theme_toggle_button() in includes/init.php). The choice is kept in
// this browser only; admin_theme_script() in the page <head> applies it before the page is drawn.
document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
  function label() {
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    var text = dark ? 'Switch to light mode' : 'Switch to dark mode';
    btn.setAttribute('aria-label', text);
    btn.setAttribute('title', text);
    btn.setAttribute('aria-pressed', dark);
  }
  label();
  btn.addEventListener('click', function () {
    var dark = document.documentElement.getAttribute('data-theme') !== 'dark';
    if (dark) document.documentElement.setAttribute('data-theme', 'dark');
    else document.documentElement.removeAttribute('data-theme');
    try { localStorage.setItem('admin-theme', dark ? 'dark' : 'light'); } catch (e) {}
    label();
  });
});

// Receipt page (includes/receipt.php): "Download PNG" turns the receipt slip into a picture in the
// browser (html2canvas, saved in assets/js), "Print" prints only the slip (see @media print in style.css).
document.querySelectorAll('[data-receipt-png]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var slip = document.getElementById('receipt-slip');
    if (!slip || !window.html2canvas) { alert('Sorry, the picture could not be made. Please use Download PDF.'); return; }
    btn.disabled = true;
    window.html2canvas(slip, {
      scale: 3, backgroundColor: '#ffffff', useCORS: true,
      // html2canvas draws boxed monospace text a few px lower than the browser: lift "PAID" in its copy only
      onclone: function (doc) { var paid = doc.querySelector('.r-paid'); if (paid) paid.style.lineHeight = '12px'; }
    }).then(function (canvas) {
      var a = document.createElement('a');
      a.download = btn.getAttribute('data-receipt-png');
      a.href = canvas.toDataURL('image/png');
      document.body.appendChild(a); a.click(); a.remove();
    }).catch(function () {
      alert('Sorry, the picture could not be made. Please use Download PDF.');
    }).then(function () { btn.disabled = false; });
  });
});
document.querySelectorAll('[data-print]').forEach(function (btn) {
  btn.addEventListener('click', function () { window.print(); });
});

// One-time messages ("You have been logged out." etc.) fade out after 5 seconds, or at once via their X
document.querySelectorAll('.flash').forEach(function (box) {
  var hide = function () {
    if (box.classList.contains('flash-hide')) return;
    box.classList.add('flash-hide');
    setTimeout(function () { box.remove(); }, 500);
  };
  setTimeout(hide, 5000);
  var x = box.querySelector('.alert-close');
  if (x) x.addEventListener('click', hide);
});

// Animations: anything marked data-reveal fades up when it scrolls into view.
// includes/header.php only turns this on (html.reveal) when the browser has IntersectionObserver.
if (document.documentElement.classList.contains('reveal')) {
  var revealer = new IntersectionObserver(function (entries) {
    entries.forEach(function (en) {
      if (en.isIntersecting) { en.target.classList.add('in'); revealer.unobserve(en.target); }
    });
  }, { rootMargin: '0px 0px -10% 0px' });
  document.querySelectorAll('[data-reveal]').forEach(function (section) {
    // number the cards so CSS can stagger them (60 ms apart, capped so long grids do not lag)
    section.querySelectorAll('.grid > *, .video-grid > *').forEach(function (card, i) { card.style.setProperty('--i', Math.min(i, 8)); });
    revealer.observe(section);
  });
}

// ---- Phase 2 animations: shopping (docs/animation-plan.md). Each one only delays the normal
// form submit / link by a fraction of a second; with reduced motion they are all skipped. ----
var reduceMotion = (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)
                   || document.body.classList.contains('admin-theme');   // the admin panel stays calm
var cartIcon = document.querySelector('.site-header .nav-cart:not(.nav-mail)');
// Run fn when the animation ends - or after ms anyway, in case the browser never reports the end
// (e.g. a background tab), so the cart form / link always goes through.
function afterAnimation(anim, ms, fn) {
  var done = false, run = function () { if (!done) { done = true; fn(); } };
  anim.onfinish = run;
  setTimeout(run, ms + 150);
}

// Add to cart: the product photo flies to the cart icon, then the form is sent as usual.
// The cart count bounces on the next page (flag kept in sessionStorage).
document.querySelectorAll('form.buy-row').forEach(function (form) {
  form.addEventListener('submit', function (ev) {
    var btn = ev.submitter;
    if (!btn || btn.value !== 'add') return;
    try { sessionStorage.setItem('kb-cart-bump', '1'); } catch (e) {}
    var card = form.closest('.card');
    var img = card ? card.querySelector('img') : document.querySelector('.gallery-slide[aria-hidden="false"] img, .detail img');
    if (reduceMotion || !cartIcon || !img || !img.animate) return;
    ev.preventDefault();
    var a = img.getBoundingClientRect(), b = cartIcon.getBoundingClientRect();
    var fly = img.cloneNode();
    fly.className = 'fly-img';
    fly.removeAttribute('loading');
    fly.style.cssText = 'left:' + a.left + 'px;top:' + a.top + 'px;width:' + a.width + 'px;height:' + a.height + 'px';
    document.body.appendChild(fly);
    var dx = b.left + b.width / 2 - (a.left + a.width / 2), dy = b.top + b.height / 2 - (a.top + a.height / 2);
    afterAnimation(fly.animate([
      { transform: 'none', opacity: 1 },
      { transform: 'translate(' + dx * 0.45 + 'px,' + (dy * 0.45 - 70) + 'px) scale(.5)', opacity: 1, offset: 0.45 },   // arc upwards
      { transform: 'translate(' + dx + 'px,' + dy + 'px) scale(.06)', opacity: 0.5 }
    ], { duration: 650, easing: 'cubic-bezier(.45,0,.55,1)', fill: 'forwards' }), 650, function () {
      var action = document.createElement('input');
      action.type = 'hidden'; action.name = 'action'; action.value = 'add';   // form.submit() does not send the clicked button
      form.appendChild(action);
      form.submit();
    });
  });
});
(function () {
  var bump = null;
  try { bump = sessionStorage.getItem('kb-cart-bump'); sessionStorage.removeItem('kb-cart-bump'); } catch (e) {}
  var badge = cartIcon && cartIcon.querySelector('.cart-badge');
  if (bump && badge && !reduceMotion) badge.classList.add('bump');
})();

// Category chips: the orange fill slides from the current chip to the clicked one, then the page changes.
document.querySelectorAll('.chips').forEach(function (row) {
  row.addEventListener('click', function (ev) {
    var to = ev.target.closest('.chip'), from = row.querySelector('.chip.active:not(.chip-toggle)');
    if (reduceMotion || !to || !from || to === from || to.classList.contains('chip-toggle') || !document.body.animate
        || ev.ctrlKey || ev.metaKey || ev.shiftKey || ev.button) return;
    ev.preventDefault();
    var r = row.getBoundingClientRect(), a = from.getBoundingClientRect(), b = to.getBoundingClientRect();
    var pill = document.createElement('span');
    pill.className = 'chip-slide';
    pill.style.cssText = 'left:' + (a.left - r.left) + 'px;top:' + (a.top - r.top) + 'px;width:' + a.width + 'px;height:' + a.height + 'px';
    row.appendChild(pill);
    from.classList.remove('active');
    afterAnimation(pill.animate([{ transform: 'none' }, { transform: 'translate(' + (b.left - a.left) + 'px,' + (b.top - a.top) + 'px) scaleX(' + b.width / a.width + ')' }],
                                { duration: 250, easing: 'cubic-bezier(.22,1,.36,1)', fill: 'forwards' }), 250, function () {
      to.classList.add('active');
      location.href = to.href;
    });
  });
});

// Cart page: a removed row slides out, then the form is sent.
document.querySelectorAll('form.inline-form input[name="action"][value="remove"]').forEach(function (field) {
  var form = field.form, row = form.closest('tr');
  form.addEventListener('submit', function (ev) {
    if (reduceMotion || !row) return;
    ev.preventDefault();
    row.classList.add('row-out');
    setTimeout(function () { form.submit(); }, 260);
  });
});

// Cart page: the total counts from the last total this visitor saw to the new one.
(function () {
  var el = document.querySelector('[data-count-peso]');
  if (!el) return;
  var now = parseFloat(el.getAttribute('data-count-peso')), before = NaN;
  try { before = parseFloat(sessionStorage.getItem('kb-cart-total')); sessionStorage.setItem('kb-cart-total', now); } catch (e) {}
  if (reduceMotion || isNaN(before) || before === now) return;
  var fmt = function (v) { return '₱' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var start = null;
  el.textContent = fmt(before);
  requestAnimationFrame(function step(t) {
    start = start || t;
    var k = Math.min((t - start) / 500, 1);
    el.textContent = fmt(before + (now - before) * (1 - Math.pow(1 - k, 3)));
    if (k < 1) requestAnimationFrame(step);
  });
})();
