/**
 * FILE: assets/js/toast.js
 * Lightweight custom toast notification system.
 * Usage:
 *   Toast.success('Message here');
 *   Toast.error('Something went wrong');
 *   Toast.warning('Watch out');
 *   Toast.info('Just so you know');
 */

(function () {
  'use strict';

  /* ── Inject styles once ── */
  const STYLE_ID = '__toast_styles__';
  if (!document.getElementById(STYLE_ID)) {
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent = `
      #toast-container {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 99999;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        pointer-events: none;
        max-width: 360px;
        width: calc(100vw - 2.5rem);
      }

      .toast {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.875rem 1rem 0.875rem 1rem;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(15,40,84,0.18), 0 2px 8px rgba(0,0,0,0.10);
        background: #fff;
        border: 1px solid rgba(0,0,0,0.07);
        pointer-events: all;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        animation: toastIn 0.32s cubic-bezier(0.34,1.56,0.64,1) both;
        font-family: 'Outfit', sans-serif;
        min-width: 260px;
      }

      .toast.removing {
        animation: toastOut 0.28s ease forwards;
      }

      @keyframes toastIn {
        from { opacity: 0; transform: translateX(110%) scale(0.92); }
        to   { opacity: 1; transform: translateX(0)   scale(1); }
      }
      @keyframes toastOut {
        from { opacity: 1; transform: translateX(0)    scale(1);    max-height: 120px; margin-bottom: 0; }
        to   { opacity: 0; transform: translateX(110%) scale(0.88); max-height: 0;     margin-bottom: -0.6rem; }
      }

      /* Progress bar */
      .toast::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0;
        height: 3px;
        border-radius: 0 0 10px 10px;
        animation: toastProgress linear forwards;
        transform-origin: left;
      }

      @keyframes toastProgress {
        from { width: 100%; }
        to   { width: 0%; }
      }

      /* Icon wrapper */
      .toast-icon {
        width: 36px; height: 36px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0; margin-top: 1px;
      }

      /* Text */
      .toast-body { flex: 1; min-width: 0; }
      .toast-title {
        font-size: 0.82rem; font-weight: 700;
        color: #0f172a; margin-bottom: 2px; line-height: 1.3;
      }
      .toast-msg {
        font-size: 0.80rem; color: #475569;
        line-height: 1.5; word-break: break-word;
      }

      /* Close button */
      .toast-close {
        background: none; border: none; cursor: pointer;
        padding: 2px; color: #94a3b8;
        font-size: 0.75rem; line-height: 1;
        display: flex; align-items: center; justify-content: center;
        border-radius: 4px; transition: color 0.15s, background 0.15s;
        flex-shrink: 0; margin-top: 1px;
        width: 20px; height: 20px;
      }
      .toast-close:hover { color: #1e293b; background: #f1f5f9; }

      /* Variants */
      .toast-success .toast-icon { background: rgba(22,163,74,0.10);  color: #16a34a; }
      .toast-success::after      { background: #16a34a; }

      .toast-error .toast-icon   { background: rgba(220,38,38,0.09);  color: #dc2626; }
      .toast-error::after        { background: #dc2626; }

      .toast-warning .toast-icon { background: rgba(217,119,6,0.10);  color: #d97706; }
      .toast-warning::after      { background: #d97706; }

      .toast-info .toast-icon    { background: rgba(37,99,235,0.09);  color: #2563EB; }
      .toast-info::after         { background: #2563EB; }

      @media (max-width: 480px) {
        #toast-container { top: auto; bottom: 1rem; right: 1rem; left: 1rem; max-width: 100%; width: auto; }
        @keyframes toastIn {
          from { opacity: 0; transform: translateY(40px) scale(0.94); }
          to   { opacity: 1; transform: translateY(0)    scale(1); }
        }
        @keyframes toastOut {
          from { opacity: 1; transform: translateY(0)    scale(1); max-height: 120px; }
          to   { opacity: 0; transform: translateY(40px) scale(0.9); max-height: 0; }
        }
      }
    `;
    document.head.appendChild(style);
  }

  /* ── Container ── */
  function getContainer() {
    let c = document.getElementById('toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  /* ── Icons (Bootstrap Icons SVG inline) ── */
  const ICONS = {
    success: '<i class="bi bi-check-circle-fill"></i>',
    error:   '<i class="bi bi-x-circle-fill"></i>',
    warning: '<i class="bi bi-exclamation-triangle-fill"></i>',
    info:    '<i class="bi bi-info-circle-fill"></i>',
  };

  const TITLES = {
    success: 'Success',
    error:   'Error',
    warning: 'Warning',
    info:    'Notice',
  };

  /* ── Show ── */
  function show(type, message, options) {
    const opts = Object.assign({ duration: 4000, title: TITLES[type] }, options || {});
    const container = getContainer();

    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.style.setProperty('--duration', opts.duration + 'ms');
    el.innerHTML = `
      <div class="toast-icon">${ICONS[type]}</div>
      <div class="toast-body">
        <div class="toast-title">${opts.title}</div>
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" aria-label="Dismiss">
        <i class="bi bi-x-lg"></i>
      </button>
    `;

    /* Progress bar duration */
    el.style.setProperty('--d', opts.duration + 'ms');
    const styleOverride = document.createElement('style');
    styleOverride.textContent = `
      .toast:nth-child(${container.children.length + 1})::after {
        animation-duration: ${opts.duration}ms;
      }
    `;
    document.head.appendChild(styleOverride);

    /* Also set directly on the element */
    el.style.cssText += `--progress-dur: ${opts.duration}ms`;

    /* Apply progress bar animation duration via a unique class */
    const uid = 'toast-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
    el.classList.add(uid);
    const ps = document.createElement('style');
    ps.textContent = `.${uid}::after { animation-duration: ${opts.duration}ms; }`;
    document.head.appendChild(ps);

    function dismiss() {
      el.classList.add('removing');
      el.addEventListener('animationend', () => {
        el.remove();
        ps.remove();
        styleOverride.remove();
      }, { once: true });
    }

    el.querySelector('.toast-close').addEventListener('click', dismiss);
    el.addEventListener('click', dismiss);

    container.appendChild(el);
    const timer = setTimeout(dismiss, opts.duration);

    /* Pause progress on hover */
    el.addEventListener('mouseenter', () => {
      clearTimeout(timer);
      el.style.animationPlayState = 'paused';
      el.querySelectorAll('*').forEach(c => c.style.animationPlayState = 'paused');
    });
    el.addEventListener('mouseleave', () => {
      dismiss();
    });

    return el;
  }

  /* ── Public API ── */
  window.Toast = {
    success: (msg, opts) => show('success', msg, opts),
    error:   (msg, opts) => show('error',   msg, opts),
    warning: (msg, opts) => show('warning', msg, opts),
    info:    (msg, opts) => show('info',    msg, opts),
  };

})();