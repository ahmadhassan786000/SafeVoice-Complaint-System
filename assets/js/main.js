// SafeVoice - shared vanilla JavaScript
document.addEventListener('DOMContentLoaded', function () {
  // Show/hide password buttons
  document.querySelectorAll('.js-toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      var icon = btn.querySelector('i');
      if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
  });
});

// Panel sidebar toggle (admin / officer, mobile)
document.addEventListener('DOMContentLoaded', function () {
  var sb = document.getElementById('svSidebar'), bd = document.getElementById('svBackdrop'), tg = document.getElementById('svSidebarToggle');
  if (!sb || !tg) return;
  function toggle(open) {
    sb.classList.toggle('open', open);
    bd.classList.toggle('show', open);
    tg.setAttribute('aria-expanded', open ? 'true' : 'false');
  }
  tg.addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
  bd.addEventListener('click', function () { toggle(false); });
  sb.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', function () { toggle(false); });
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape' && sb.classList.contains('open')) {
      toggle(false);
      tg.focus();
    }
  });
});

// Confirm dialogs: <form data-confirm="Are you sure?">
document.addEventListener('submit', function (ev) {
  var f = ev.target;
  if (f.dataset && f.dataset.confirm && !window.confirm(f.dataset.confirm)) ev.preventDefault();
});

// Auto-scroll chat boxes to the newest message
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.sv-chat').forEach(function (c) { c.scrollTop = c.scrollHeight; });
});

// Searchable lists: <input data-filter-list="#listId"> filters children with class .sv-filter-item
document.addEventListener('input', function (ev) {
  var t = ev.target;
  if (!t.dataset || !t.dataset.filterList) return;
  var q = t.value.toLowerCase();
  document.querySelectorAll(t.dataset.filterList + ' .sv-filter-item').forEach(function (el) {
    el.style.display = el.textContent.toLowerCase().indexOf(q) === -1 ? 'none' : '';
  });
});

// Chart.js helper (admin/officer reports)
window.svChart = function (id, type, rows, label) {
  var el = document.getElementById(id);
  if (!el || !window.Chart) return;
  var colors = ['#1f8a4c', '#0b3d2e', '#6fbf8f', '#f0ad4e', '#5a32a3', '#d9534f', '#14573f', '#9bd1b0', '#3b82c4', '#b5651d'];
  var round = type === 'doughnut' || type === 'pie';
  new Chart(el, {
    type: type,
    data: { labels: rows.map(function (r) { return r.label; }),
            datasets: [{ label: label || 'Complaints', data: rows.map(function (r) { return +r.n; }),
                         backgroundColor: round ? colors : '#1f8a4c', borderColor: '#1f8a4c', tension: .3, fill: type === 'line' ? false : true }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: round } }, scales: round ? {} : { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });
};

// Edit-category modal
document.addEventListener('click', function (ev) {
  var b = ev.target.closest('[data-edit-category]');
  if (!b) return;
  document.getElementById('ec_id').value = b.dataset.id;
  document.getElementById('ec_name').value = b.dataset.name;
  document.getElementById('ec_desc').value = b.dataset.desc;
});

// Flash messages: stay ~3 seconds, fade out smoothly, then are removed from the page
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.sv-flash').forEach(function (el) {
    setTimeout(function () {
      el.classList.add('sv-flash-hide');
      setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 600);
    }, 3000);
  });
});
