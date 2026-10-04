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
