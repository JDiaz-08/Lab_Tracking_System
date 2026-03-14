// ===========================
//  NAVBAR — scroll & mobile (public)
// ===========================
const navbar     = document.querySelector('.navbar');
const hamburger  = document.querySelector('.hamburger:not(.user-hamburger)');
const mobileMenu = document.querySelector('.mobile-menu');

if (navbar) {
  window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 20);
  });
}

if (hamburger && mobileMenu) {
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
  });
  mobileMenu.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('open');
      mobileMenu.classList.remove('open');
    });
  });
}

// ===========================
//  USER NAVBAR — mobile
// ===========================
const userHamburger  = document.getElementById('userHamburger');
const userMobileMenu = document.getElementById('userMobileMenu');

if (userHamburger && userMobileMenu) {
  userHamburger.addEventListener('click', () => {
    userHamburger.classList.toggle('open');
    userMobileMenu.classList.toggle('open');
  });
}

// ===========================
//  NOTIFICATION DROPDOWN
// ===========================
const notifToggle   = document.getElementById('notifToggle');
const notifDropdown = document.getElementById('notifDropdown');

if (notifToggle && notifDropdown) {
  notifToggle.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown.classList.toggle('open');
  });

  document.addEventListener('click', (e) => {
    if (!notifDropdown.contains(e.target) && e.target !== notifToggle) {
      notifDropdown.classList.remove('open');
    }
  });
}

// ===========================
//  SCROLL REVEAL
// ===========================
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => entry.target.classList.add('visible'), i * 80);
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ===========================
//  AUTH TABS (login page)
// ===========================
const tabBtns = document.querySelectorAll('.tab-btn');
const panels  = document.querySelectorAll('.auth-panel');

function showTab(tabName) {
  tabBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabName));
  panels.forEach(p   => p.classList.toggle('active',   p.id === `panel-${tabName}`));
}

tabBtns.forEach(btn => {
  btn.addEventListener('click', () => showTab(btn.dataset.tab));
});

document.querySelectorAll('[data-switch]').forEach(link => {
  link.addEventListener('click', (e) => { e.preventDefault(); showTab(link.dataset.switch); });
});

const forgotLink = document.querySelector('.forgot-link');
if (forgotLink) {
  forgotLink.addEventListener('click', (e) => { e.preventDefault(); showTab('forgot'); });
}

// ===========================
//  PASSWORD VISIBILITY TOGGLE
// ===========================
document.querySelectorAll('.toggle-pass').forEach(icon => {
  icon.addEventListener('click', () => {
    const input = icon.previousElementSibling;
    if (!input) return;
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.textContent = input.type === 'password' ? '👁️' : '🙈';
  });
});

// ===========================
//  ACTIVE NAV LINK
// ===========================
const path = window.location.pathname.replace(/\/$/, '') || '/';
document.querySelectorAll('.nav-links a, .mobile-menu a').forEach(link => {
  const href = link.getAttribute('href');
  if (!href) return;
  const normHref = href.replace(/^(\.\.\/)+|^\.\//, '');
  const normPath = path.replace(/^\//, '');
  if (normPath === normHref || normPath.endsWith('/' + normHref) ||
      (normHref === 'index.php' && (normPath === '' || normPath.endsWith('/')))) {
    link.classList.add('active');
  }
});

// ===========================
//  ANIMATED COUNTER (stats banner)
// ===========================
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const suffix = el.dataset.suffix || '';
  let current  = 0;
  const step   = Math.ceil(target / 60);
  const timer  = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString() + suffix;
    if (current >= target) clearInterval(timer);
  }, 25);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounter(entry.target);
      counterObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('.counter').forEach(el => counterObserver.observe(el));