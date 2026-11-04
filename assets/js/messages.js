// SafeVoice - messaging interface helpers (progressive enhancement; the form works without JS).
document.addEventListener('DOMContentLoaded', function () {
  var ta = document.getElementById('svMessage');
  var count = document.getElementById('svCount');
  var chat = document.querySelector('.sv-chat');

  if (chat) chat.scrollTop = chat.scrollHeight;
  // The inline "Message sent" confirmation fades away by itself
  var ok = document.querySelector('.sv-state-ok');
  if (ok) setTimeout(function () { ok.classList.add('hide'); setTimeout(function () { if (ok.parentNode) ok.parentNode.removeChild(ok); }, 600); }, 4000);

  if (ta) {
    var max = parseInt(ta.getAttribute('maxlength'), 10) || 3000;
    var fit = function () {
      ta.style.height = 'auto';
      ta.style.height = Math.min(ta.scrollHeight, 160) + 'px';
      if (count) count.textContent = ta.value.length + ' / ' + max;
    };
    ta.addEventListener('input', fit);
    ta.addEventListener('keydown', function (ev) {
      // Enter sends; Shift+Enter inserts a new line.
      if (ev.key === 'Enter' && !ev.shiftKey && !ev.isComposing) {
        ev.preventDefault();
        if (ta.value.trim() !== '' && ta.form) {
          if (ta.form.requestSubmit) ta.form.requestSubmit(); else ta.form.submit();
        }
      }
    });
    ta.form.addEventListener('submit', function () {
      var btn = ta.form.querySelector('button[type="submit"]');
      ta.readOnly = true;                                            // keeps the text in the POST (disabled fields are not sent)
      ta.form.classList.add('is-sending');
      if (btn) setTimeout(function () {                              // prevent double-send + show progress
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span class="ms-1">Sending…</span>';
      }, 0);
    });
    fit();
  }

  // Show "no match" when the conversation search hides every item.
  var search = document.querySelector('.sv-msgs-list [data-filter-list]');
  var none = document.getElementById('convoNoMatch');
  if (search && none) {
    search.addEventListener('input', function () {
      var items = document.querySelectorAll('#convoList .sv-filter-item');
      var visible = 0;
      items.forEach(function (el) { if (el.style.display !== 'none') visible++; });
      none.classList.toggle('d-none', visible > 0 || items.length === 0);
    });
  }
});
