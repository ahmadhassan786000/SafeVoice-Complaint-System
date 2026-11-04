// SafeVoice - Submit Complaint helpers (progressive enhancement: the form still posts normally without JS;
// the server re-validates everything).
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('complaintForm');
  if (!form) return;

  // Bring server-side errors into view
  var errs = document.getElementById('scErrors');
  if (errs) { errs.scrollIntoView({ behavior: 'smooth', block: 'start' }); errs.focus({ preventScroll: true }); }

  // Character counter
  var desc = document.getElementById('description'), cnt = document.getElementById('descCount');
  function count() { if (desc && cnt) { cnt.textContent = desc.value.length + ' / ' + (desc.getAttribute('maxlength') || 5000); } }
  if (desc) { desc.addEventListener('input', count); count(); }

  // ---- Evidence upload list (the native file input stays the source of truth) ----
  var input = document.getElementById('evidence'), list = document.getElementById('fileList'), warn = document.getElementById('fileWarn'), zone = document.getElementById('dropzone');
  var maxBytes = (parseInt(form.dataset.maxMb, 10) || 10) * 1024 * 1024;
  var allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'txt'];
  function size(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
  function icon(name) {
    var e = name.split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp'].indexOf(e) > -1 ? 'bi-file-earmark-image' : e === 'pdf' ? 'bi-file-earmark-pdf' : e === 'txt' ? 'bi-file-earmark-text' : 'bi-file-earmark-word';
  }
  function setFiles(files) {
    if (typeof DataTransfer === 'undefined') return false;
    var dt = new DataTransfer(); files.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; return true;
  }
  function render() {
    list.innerHTML = '';
    Array.prototype.forEach.call(input.files, function (f, i) {
      var li = document.createElement('li'); li.className = 'sv-file';
      var ic = document.createElement('i'); ic.className = 'bi ' + icon(f.name); ic.setAttribute('aria-hidden', 'true');
      var meta = document.createElement('div'); meta.className = 'sv-file-meta';
      var nm = document.createElement('span'); nm.className = 'sv-file-name'; nm.textContent = f.name;
      var sz = document.createElement('span'); sz.className = 'sv-file-size'; sz.textContent = size(f.size);
      meta.appendChild(nm); meta.appendChild(sz);
      li.appendChild(ic); li.appendChild(meta);
      if (typeof DataTransfer !== 'undefined') {
        var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'sv-file-remove'; rm.setAttribute('aria-label', 'Remove ' + f.name); rm.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
        rm.addEventListener('click', function () { var arr = Array.prototype.slice.call(input.files); arr.splice(i, 1); setFiles(arr); render(); });
        li.appendChild(rm);
      }
      list.appendChild(li);
    });
    zone.classList.toggle('has-files', input.files.length > 0);
  }
  if (input) {
    input.addEventListener('change', function () {
      var ok = [], rejected = [];
      Array.prototype.forEach.call(input.files, function (f) {
        var ext = f.name.split('.').pop().toLowerCase();
        if (allowed.indexOf(ext) === -1) rejected.push(f.name + ' (file type not allowed)');
        else if (f.size > maxBytes) rejected.push(f.name + ' (larger than ' + (maxBytes / 1048576) + ' MB)');
        else ok.push(f);
      });
      if (rejected.length && setFiles(ok)) { warn.textContent = 'Not added: ' + rejected.join('; ') + '.'; warn.classList.remove('d-none'); }
      else { warn.classList.add('d-none'); }
      render();
    });
    ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('drag'); }); });
  }

  // ---- Friendly client-side check (server stays the authority) ----
  form.addEventListener('submit', function (ev) {
    var firstBad = null;
    form.querySelectorAll('[required]').forEach(function (el) {
      var bad = !el.value.trim() || (el.minLength > 0 && el.value.trim().length < el.minLength);
      el.classList.toggle('is-invalid', bad);
      if (bad && !firstBad) firstBad = el;
    });
    if (firstBad) {
      ev.preventDefault();
      firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstBad.focus({ preventScroll: true });
      return;
    }
    var btn = document.getElementById('submitBtn');   // prevent double submission + show progress
    if (btn) { setTimeout(function () { btn.disabled = true; btn.querySelector('span').textContent = 'Submitting…'; btn.querySelector('i').className = 'spinner-border spinner-border-sm me-2'; }, 0); }
  });
  form.addEventListener('input', function (ev) { if (ev.target.classList.contains('is-invalid') && ev.target.value.trim()) ev.target.classList.remove('is-invalid'); });
});
