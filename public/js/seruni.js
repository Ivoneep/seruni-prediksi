/* Interaksi umum: navbar, menu mobile, animasi muncul, modal */
document.addEventListener('DOMContentLoaded', function () {
  var navbar = document.getElementById('navbar');
  var hamburger = document.getElementById('hamburger');
  var mobileMenu = document.getElementById('mobileMenu');

  function toggleMenu() {
    var open = hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('active', open);
    hamburger.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }
  hamburger.addEventListener('click', toggleMenu);
  mobileMenu.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { if (hamburger.classList.contains('active')) toggleMenu(); });
  });

  // Navbar transparan hanya di halaman dengan hero hitam (Beranda)
  if (navbar.dataset.hero === '1') {
    var onScroll = function () { navbar.classList.toggle('scrolled', window.scrollY > 50); };
    window.addEventListener('scroll', onScroll); onScroll();
  }

  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('active'); io.unobserve(e.target); } });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('active'); });
  }

  // ---- Modal ----
  var modal = document.getElementById('modal');
  var titleEl = document.getElementById('modal-title');
  var bodyEl = document.getElementById('modal-body');
  var closeBtn = modal.querySelector('.x');
  var lastFocus = null;

  window.SeruniModal = {
    open: function (title, html) {
      lastFocus = document.activeElement;
      titleEl.textContent = title;
      bodyEl.innerHTML = html;
      modal.classList.add('open');
      closeBtn.focus();
    },
    close: function () {
      modal.classList.remove('open');
      if (lastFocus && lastFocus.focus) lastFocus.focus();
    }
  };
  closeBtn.addEventListener('click', SeruniModal.close);
  modal.addEventListener('click', function (e) { if (e.target === modal) SeruniModal.close(); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && modal.classList.contains('open')) SeruniModal.close(); });

  // Tombol dengan data-modal="idTemplate" membuka isi <template id="idTemplate">
  document.querySelectorAll('[data-modal]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var t = document.getElementById(btn.dataset.modal);
      if (t) SeruniModal.open(btn.dataset.title || btn.textContent.trim(), t.innerHTML);
    });
  });
});
