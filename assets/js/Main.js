// ===========================
//  NAVBAR — scroll & mobile
// ===========================
const navbar    = document.querySelector('.navbar');
const hamburger = document.querySelector('.hamburger');
const mobileMenu = document.querySelector('.mobile-menu');

window.addEventListener('scroll', () => {
  if (window.scrollY > 20) {
    navbar.classList.add('scrolled');
  } else {
    navbar.classList.remove('scrolled');
  }
});

if (hamburger) {
  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('open');
    mobileMenu.classList.toggle('open');
  });
}

// Close mobile menu when a link is clicked
document.querySelectorAll('.mobile-menu a').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('open');
    mobileMenu.classList.remove('open');
  });
});

// ===========================
//  SCROLL REVEAL
// ===========================
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => {
        entry.target.classList.add('visible');
      }, i * 80);
      revealObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ===========================
//  AUTH TABS
// ===========================
const tabBtns  = document.querySelectorAll('.tab-btn');
const panels   = document.querySelectorAll('.auth-panel');

function showTab(tabName) {
  tabBtns.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tabName);
  });
  panels.forEach(panel => {
    panel.classList.toggle('active', panel.id === `panel-${tabName}`);
  });
}

tabBtns.forEach(btn => {
  btn.addEventListener('click', () => showTab(btn.dataset.tab));
});

// Switch links inside panels
document.querySelectorAll('[data-switch]').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    showTab(link.dataset.switch);
  });
});

// ===========================
//  FORGOT PASSWORD PANEL
// ===========================
const forgotLink = document.querySelector('.forgot-link');
if (forgotLink) {
  forgotLink.addEventListener('click', (e) => {
    e.preventDefault();
    showTab('forgot');
  });
}

// ===========================
//  PASSWORD VISIBILITY TOGGLE
// ===========================
document.querySelectorAll('.toggle-pass').forEach(icon => {
  icon.addEventListener('click', () => {
    const input = icon.previousElementSibling;
    if (input.type === 'password') {
      input.type = 'text';
      icon.textContent = '🙈';
    } else {
      input.type = 'password';
      icon.textContent = '👁️';
    }
  });
});

// ===========================
//  ACTIVE NAV LINK
// ===========================
const currentPage = window.location.pathname.split('/').pop();
document.querySelectorAll('.nav-links a, .mobile-menu a').forEach(link => {
  const href = link.getAttribute('href');
  if (href === currentPage || (currentPage === '' && href === 'index.php')) {
    link.classList.add('active');
  }
});

// ===========================
//  ANIMATED COUNTER (stats banner)
// ===========================
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const suffix = el.dataset.suffix || '';
  let current = 0;
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