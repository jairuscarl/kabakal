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
    if (img && input.files[0]) { img.src = URL.createObjectURL(input.files[0]); img.hidden = false; }
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
