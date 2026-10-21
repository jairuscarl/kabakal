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
document.querySelectorAll('[data-gallery]').forEach(function (g) {
  var track = g.querySelector('.gallery-track');
  var slides = g.querySelectorAll('.gallery-slide');
  var thumbs = g.querySelectorAll('.gallery-thumb');
  var count = g.querySelector('.gallery-count');
  var cur = 0;
  if (slides.length < 2) return;

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

  g.querySelector('.gallery-prev').addEventListener('click', function () { show(cur - 1); });
  g.querySelector('.gallery-next').addEventListener('click', function () { show(cur + 1); });
  thumbs.forEach(function (t, n) { t.addEventListener('click', function () { show(n); }); });

  g.tabIndex = 0;
  g.addEventListener('keydown', function (ev) {
    if (ev.target.tagName === 'VIDEO') return;               // let the video use its own arrow keys
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
