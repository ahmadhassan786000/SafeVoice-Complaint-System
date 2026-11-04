// SafeVoice public homepage: scroll-reveal, active-section highlight, mobile menu close.
document.addEventListener('DOMContentLoaded', function () {
  // Scroll reveal (falls back to showing everything when IntersectionObserver is unavailable)
  var items = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    items.forEach(function (el) { io.observe(el); });
  } else { items.forEach(function (el) { el.classList.add('in'); }); }

  // Highlight the nav link of the section currently in view
  var links = document.querySelectorAll('#pubNav [data-spy]');
  var map = {};
  links.forEach(function (a) { map[a.dataset.spy] = a; });
  if ('IntersectionObserver' in window) {
    var spy = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting && map[en.target.id]) {
          links.forEach(function (a) { a.classList.remove('active'); });
          map[en.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    Object.keys(map).forEach(function (id) { var s = document.getElementById(id); if (s) spy.observe(s); });
  }

  // Close the mobile menu after choosing a section
  var menu = document.getElementById('publicNav');
  document.querySelectorAll('#pubNav .nav-link').forEach(function (a) {
    a.addEventListener('click', function () {
      if (menu && menu.classList.contains('show') && window.bootstrap) bootstrap.Collapse.getOrCreateInstance(menu).hide();
    });
  });
});
