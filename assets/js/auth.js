// SafeVoice - signup form: ONE validation message at a time, in priority order.
// Validation only runs on submit (never on focus/click). Server-side validation stays authoritative.
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('registerForm');
  if (!form) return;

  var rules = [
    ['full_name', function (v) {
      v = v.trim();
      if (!v) return 'Please enter your full name.';
      if (v.length < 2 || v.length > 100) return 'Full name must be between 2 and 100 characters.';
      if (!/^[\p{L}\s.'-]+$/u.test(v)) return 'Full name can only contain letters, spaces, dots, hyphens and apostrophes.';
      return '';
    }],
    ['email', function (v) {
      v = v.trim();
      if (!v) return 'Please enter your email address.';
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) return 'Please enter a valid email address.';
      return '';
    }],
    ['phone', function (v) {
      v = v.trim();
      if (!v) return 'Please enter your phone number.';
      if (!/^[0-9+\-\s()]{7,20}$/.test(v) || (v.match(/[0-9]/g) || []).length < 7) return 'Please enter a valid phone number (at least 7 digits).';
      return '';
    }],
    ['password', function (v) {
      if (!v) return 'Please enter a password.';
      if (v.length < 8) return 'Password must be at least 8 characters long.';
      return '';
    }],
    ['confirm_password', function (v) {
      if (!v) return 'Please confirm your password.';
      if (v !== document.getElementById('password').value) return 'Passwords do not match.';
      return '';
    }]
  ];

  function clearAll() {
    form.querySelectorAll('.sv-field-error').forEach(function (e) { e.classList.remove('show'); e.textContent = ''; });
    form.querySelectorAll('.sv-input').forEach(function (g) { g.classList.remove('is-error'); });
  }
  function showError(name, msg) {
    clearAll();
    var input = document.getElementById(name);
    var box = form.querySelector('[data-error-for="' + name + '"]');
    if (box) { box.textContent = msg; box.classList.add('show'); }
    if (input) { input.closest('.sv-input').classList.add('is-error'); input.focus(); }
  }

  form.addEventListener('submit', function (ev) {
    for (var i = 0; i < rules.length; i++) {
      var el = document.getElementById(rules[i][0]);
      var msg = rules[i][1](el ? el.value : '');
      if (msg) { ev.preventDefault(); showError(rules[i][0], msg); return; }   // stop at the first error
    }
  });

  // While correcting the field that has the error, remove the message as soon as it becomes valid.
  form.addEventListener('input', function (ev) {
    var id = ev.target.id, box = form.querySelector('[data-error-for="' + id + '"]');
    if (!box || !box.classList.contains('show')) return;
    for (var i = 0; i < rules.length; i++) if (rules[i][0] === id && !rules[i][1](ev.target.value)) {
      box.classList.remove('show'); box.textContent = ''; ev.target.closest('.sv-input').classList.remove('is-error');
    }
  });
});
