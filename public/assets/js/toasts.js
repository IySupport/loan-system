/*!
 * Toast.js — lightweight, dependency-free toast notifications
 * Usage:
 *   Toast.success('Saved successfully');
 *   Toast.error('Something went wrong');
 *   Toast.warning('Please check the form');
 *   Toast.info('Heads up, new update available');
 *
 * Options (2nd argument, all optional):
 *   Toast.success('Saved!', { duration: 5000, title: 'Nice one' });
 *
 * PHP MVC integration: see the bottom of this file for the
 * recommended pattern (flash messages via session -> data attribute -> auto-fire).
 */
(function (window, document) {
  'use strict';

  var CONFIG = {
    position: 'top-right',   // top-right | top-left | bottom-right | bottom-left | top-center | bottom-center
    duration: 4500,           // ms, 0 = stays until closed manually
    gap: 10
  };

  var ICONS = {
    success: '<svg viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    error: '<svg viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    info: '<svg viewBox="0 0 24 24" fill="none"><path d="M12 16v-4m0-4h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  };

  var TITLES = {
    success: 'Success',
    error: 'Error',
    warning: 'Warning',
    info: 'Notice'
  };

  var injected = false;

  function injectStyles() {
    if (injected) return;
    injected = true;

    var css = ''
      + ':root{'
      + '  --toast-bg:#1C1F26;'
      + '  --toast-border:rgba(255,255,255,0.08);'
      + '  --toast-text:#F5F7FA;'
      + '  --toast-text-muted:#9CA3AF;'
      + '  --toast-success:#34D399;'
      + '  --toast-error:#FB7185;'
      + '  --toast-warning:#FBBF24;'
      + '  --toast-info:#60A5FA;'
      + '}'
      + '.toast-viewport{position:fixed;z-index:99999;display:flex;flex-direction:column;padding:16px;pointer-events:none;max-width:calc(100vw - 32px);}'
      + '.toast-viewport[data-pos="top-right"]{top:0;right:0;align-items:flex-end;}'
      + '.toast-viewport[data-pos="top-left"]{top:0;left:0;align-items:flex-start;}'
      + '.toast-viewport[data-pos="bottom-right"]{bottom:0;right:0;align-items:flex-end;flex-direction:column-reverse;}'
      + '.toast-viewport[data-pos="bottom-left"]{bottom:0;left:0;align-items:flex-start;flex-direction:column-reverse;}'
      + '.toast-viewport[data-pos="top-center"]{top:0;left:50%;transform:translateX(-50%);align-items:center;}'
      + '.toast-viewport[data-pos="bottom-center"]{bottom:0;left:50%;transform:translateX(-50%);align-items:center;flex-direction:column-reverse;}'
      + '.toast-card{pointer-events:auto;position:relative;overflow:hidden;display:flex;align-items:flex-start;gap:12px;width:340px;max-width:100%;background:var(--toast-bg);color:var(--toast-text);border:1px solid var(--toast-border);border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.28),0 2px 6px rgba(0,0,0,0.2);padding:14px 14px 14px 14px;margin-top:10px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;transition:transform .25s ease, opacity .25s ease;}'
      + '.toast-viewport[data-pos^="bottom"] .toast-card{margin-top:0;margin-bottom:10px;}'
      + '.toast-card[data-state="enter"]{opacity:0;transform:translateY(-8px) scale(.98);}'
      + '.toast-viewport[data-pos^="bottom"] .toast-card[data-state="enter"]{transform:translateY(8px) scale(.98);}'
      + '.toast-card[data-state="show"]{opacity:1;transform:translateY(0) scale(1);}'
      + '.toast-card[data-state="leave"]{opacity:0;transform:translateX(24px) scale(.98);}'
      + '.toast-accent{position:absolute;left:0;top:0;bottom:0;width:4px;border-radius:12px 0 0 12px;}'
      + '.toast-card[data-type="success"] .toast-accent{background:var(--toast-success);}'
      + '.toast-card[data-type="error"] .toast-accent{background:var(--toast-error);}'
      + '.toast-card[data-type="warning"] .toast-accent{background:var(--toast-warning);}'
      + '.toast-card[data-type="info"] .toast-accent{background:var(--toast-info);}'
      + '.toast-icon{flex:0 0 auto;width:20px;height:20px;margin-top:1px;}'
      + '.toast-card[data-type="success"] .toast-icon{color:var(--toast-success);}'
      + '.toast-card[data-type="error"] .toast-icon{color:var(--toast-error);}'
      + '.toast-card[data-type="warning"] .toast-icon{color:var(--toast-warning);}'
      + '.toast-card[data-type="info"] .toast-icon{color:var(--toast-info);}'
      + '.toast-icon svg{width:100%;height:100%;}'
      + '.toast-body{flex:1 1 auto;min-width:0;}'
      + '.toast-title{font-size:13.5px;font-weight:600;line-height:1.3;margin:0 0 2px;}'
      + '.toast-msg{font-size:13px;line-height:1.4;color:var(--toast-text-muted);margin:0;word-wrap:break-word;}'
      + '.toast-close{flex:0 0 auto;background:transparent;border:0;color:var(--toast-text-muted);cursor:pointer;padding:2px;line-height:0;border-radius:6px;transition:color .15s ease, background .15s ease;}'
      + '.toast-close:hover{color:var(--toast-text);background:rgba(255,255,255,0.06);}'
      + '.toast-close svg{width:15px;height:15px;}'
      + '.toast-progress{position:absolute;left:0;bottom:0;height:2.5px;width:100%;background:rgba(255,255,255,0.08);}'
      + '.toast-progress i{display:block;height:100%;width:100%;transform-origin:left;background:currentColor;}'
      + '.toast-card[data-type="success"] .toast-progress i{color:var(--toast-success);}'
      + '.toast-card[data-type="error"] .toast-progress i{color:var(--toast-error);}'
      + '.toast-card[data-type="warning"] .toast-progress i{color:var(--toast-warning);}'
      + '.toast-card[data-type="info"] .toast-progress i{color:var(--toast-info);}'
      + '@keyframes toast-shrink{from{transform:scaleX(1);}to{transform:scaleX(0);}}'
      + '@media (max-width:480px){.toast-card{width:calc(100vw - 32px);}}'
      + '@media (prefers-reduced-motion: reduce){.toast-card{transition:opacity .15s linear;}.toast-progress i{animation:none !important;}}';

    var style = document.createElement('style');
    style.setAttribute('data-toastjs', '');
    style.textContent = css;
    document.head.appendChild(style);
  }

  function getViewport(position) {
    var pos = position || CONFIG.position;
    var selector = '.toast-viewport[data-pos="' + pos + '"]';
    var vp = document.querySelector(selector);
    if (!vp) {
      vp = document.createElement('div');
      vp.className = 'toast-viewport';
      vp.setAttribute('data-pos', pos);
      document.body.appendChild(vp);
    }
    return vp;
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
  }

  function show(type, message, options) {
    injectStyles();
    options = options || {};

    var duration = options.duration !== undefined ? options.duration : CONFIG.duration;
    var title = options.title !== undefined ? options.title : TITLES[type];
    var position = options.position || CONFIG.position;
    var vp = getViewport(position);

    var card = document.createElement('div');
    card.className = 'toast-card';
    card.setAttribute('data-type', type);
    card.setAttribute('data-state', 'enter');
    card.setAttribute('role', type === 'error' ? 'alert' : 'status');

    var html = '<div class="toast-accent"></div>'
      + '<div class="toast-icon">' + (ICONS[type] || ICONS.info) + '</div>'
      + '<div class="toast-body">'
      + (title ? '<p class="toast-title">' + escapeHtml(title) + '</p>' : '')
      + '<p class="toast-msg">' + escapeHtml(message) + '</p>'
      + '</div>'
      + '<button type="button" class="toast-close" aria-label="Dismiss">'
      + '<svg viewBox="0 0 24 24" fill="none"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
      + '</button>';

    if (duration > 0) {
      html += '<div class="toast-progress"><i style="animation:toast-shrink ' + duration + 'ms linear forwards;"></i></div>';
    }

    card.innerHTML = html;
    vp.appendChild(card);

    // force reflow so the enter -> show transition fires
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        card.setAttribute('data-state', 'show');
      });
    });

    var timer = null;
    function dismiss() {
      if (card.getAttribute('data-state') === 'leave') return;
      card.setAttribute('data-state', 'leave');
      if (timer) clearTimeout(timer);
      card.addEventListener('transitionend', function handler() {
        card.removeEventListener('transitionend', handler);
        card.remove();
      });
      // fallback in case transitionend doesn't fire
      setTimeout(function () { if (card.parentNode) card.remove(); }, 400);
    }

    card.querySelector('.toast-close').addEventListener('click', dismiss);

    if (duration > 0) {
      timer = setTimeout(dismiss, duration);
      card.addEventListener('mouseenter', function () {
        var bar = card.querySelector('.toast-progress i');
        if (bar) bar.style.animationPlayState = 'paused';
        if (timer) clearTimeout(timer);
      });
      card.addEventListener('mouseleave', function () {
        var bar = card.querySelector('.toast-progress i');
        if (bar) bar.style.animationPlayState = 'running';
        timer = setTimeout(dismiss, duration);
      });
    }

    return { dismiss: dismiss, element: card };
  }

  var Toast = {
    success: function (message, options) { return show('success', message, options); },
    error: function (message, options) { return show('error', message, options); },
    warning: function (message, options) { return show('warning', message, options); },
    info: function (message, options) { return show('info', message, options); },
    show: show,
    configure: function (opts) { Object.assign(CONFIG, opts || {}); }
  };

  window.Toast = Toast;

  // ---------------------------------------------------------------------
  // Auto-fire from PHP flash messages.
  // In your layout, render a hidden element like:
  //   <div id="toast-flash" data-type="success" data-message="Saved!"></div>
  // (only render it when a flash message actually exists in the session)
  // This script will pick it up on load and fire the matching toast.
  // ---------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    var flash = document.getElementById('toast-flash');
    if (flash) {
      var type = flash.getAttribute('data-type') || 'info';
      var message = flash.getAttribute('data-message') || '';
      var title = flash.getAttribute('data-title') || undefined;
      if (message) show(type, message, { title: title });
      flash.remove();
    }
  });

})(window, document);
