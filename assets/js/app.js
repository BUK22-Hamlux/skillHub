/**
 * SkillHub — app.js
 * All client-side features:
 *   - Responsive nav toggle
 *   - Form validation (register, login, service create/edit)
 *   - Password visibility toggle
 *   - Confirm-delete with styled modal
 *   - Live search/filter on service & order lists
 *   - Image upload preview
 *   - Flash message auto-dismiss
 *   - Smooth stat counter animation
 *   - Stagger animations on card grids
 */

'use strict';

/* ── Utility ──────────────────────────────────────────────── */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

/* ── 1. NAV TOGGLE (mobile hamburger) ─────────────────────── */
function initNav() {
  const toggle = $('#navToggle');
  const nav    = $('#mainNav');
  if (!toggle || !nav) return;

  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('nav-open');
    toggle.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open);
    document.body.style.overflow = open ? 'hidden' : '';
  });

  // Close on outside click
  document.addEventListener('click', e => {
    if (nav.classList.contains('nav-open') &&
        !nav.contains(e.target) && !toggle.contains(e.target)) {
      nav.classList.remove('nav-open');
      toggle.classList.remove('open');
      toggle.setAttribute('aria-expanded', false);
      document.body.style.overflow = '';
    }
  });

  // Close on resize to desktop
  window.addEventListener('resize', () => {
    if (window.innerWidth > 720) {
      nav.classList.remove('nav-open');
      toggle.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
}

/* ── 2. PASSWORD VISIBILITY TOGGLE ────────────────────────── */
function initPasswordToggles() {
  $$('.input-password-wrap').forEach(wrap => {
    const input = $('input[type="password"], input[type="text"]', wrap);
    if (!input) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn-eye';
    btn.setAttribute('aria-label', 'Toggle password visibility');
    btn.innerHTML = eyeIcon(false);
    wrap.appendChild(btn);

    btn.addEventListener('click', () => {
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.innerHTML = eyeIcon(isPass);
      input.focus();
    });
  });
}

function eyeIcon(visible) {
  return visible
    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`
    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
}

/* ── 3. FORM VALIDATION ───────────────────────────────────── */
function initFormValidation() {

  // ── Login form
  const loginForm = $('#loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', e => {
      let valid = true;

      const email = $('#loginEmail');
      const pass  = $('#loginPassword');

      clearErrors(loginForm);

      if (!email.value.trim() || !isValidEmail(email.value)) {
        showError(email, 'Please enter a valid email address.');
        valid = false;
      }
      if (!pass.value) {
        showError(pass, 'Password is required.');
        valid = false;
      }

      if (!valid) {
        e.preventDefault();
        shakeForm(loginForm);
      } else {
        setLoading(loginForm.querySelector('[type="submit"]'));
      }
    });

    // Real-time feedback
    onBlurValidate($('#loginEmail'), v => isValidEmail(v) ? '' : 'Invalid email address.');
  }

  // ── Register form
  const regForm = $('#registerForm');
  if (regForm) {
    regForm.addEventListener('submit', e => {
      let valid = true;
      clearErrors(regForm);

      const name  = $('#regName');
      const email = $('#regEmail');
      const pass  = $('#regPassword');
      const conf  = $('#regConfirm');

      if (!name.value.trim() || name.value.trim().length < 2) {
        showError(name, 'Full name must be at least 2 characters.'); valid = false;
      }
      if (!isValidEmail(email.value)) {
        showError(email, 'Please enter a valid email address.'); valid = false;
      }
      if (pass.value.length < 8) {
        showError(pass, 'Password must be at least 8 characters.'); valid = false;
      } else if (!/[A-Z]/.test(pass.value) || !/[0-9]/.test(pass.value)) {
        showError(pass, 'Include at least one uppercase letter and one number.'); valid = false;
      }
      if (conf.value !== pass.value) {
        showError(conf, 'Passwords do not match.'); valid = false;
      }

      if (!valid) {
        e.preventDefault();
        shakeForm(regForm);
      } else {
        setLoading(regForm.querySelector('[type="submit"]'));
      }
    });

    // Real-time: password strength
    const passInput = $('#regPassword');
    const confInput = $('#regConfirm');
    if (passInput) {
      passInput.addEventListener('input', () => updatePasswordStrength(passInput));
      passInput.addEventListener('blur',  () => {
        if (passInput.value.length > 0 && passInput.value.length < 8)
          showError(passInput, 'Password must be at least 8 characters.');
        else clearError(passInput);
      });
    }
    if (confInput) {
      confInput.addEventListener('blur', () => {
        if (confInput.value && confInput.value !== passInput.value)
          showError(confInput, 'Passwords do not match.');
        else clearError(confInput);
      });
    }
    onBlurValidate($('#regEmail'), v => isValidEmail(v) ? '' : 'Invalid email address.');
    onBlurValidate($('#regName'),  v => v.trim().length >= 2 ? '' : 'Name too short.');
  }

  // ── Service create/edit form
  const svcForm = $('#serviceForm');
  if (svcForm) {
    svcForm.addEventListener('submit', e => {
      let valid = true;
      clearErrors(svcForm);

      const title = $('#svcTitle');
      const desc  = $('#description');
      const price = $('#price');
      const days  = $('#delivery_days');

      if (!title || title.value.trim().length < 5) {
        showError(title, 'Title must be at least 5 characters.'); valid = false;
      }
      if (!desc || desc.value.trim().length < 20) {
        showError(desc, 'Description must be at least 20 characters.'); valid = false;
      }
      const p = parseFloat(price?.value);
      if (!price || isNaN(p) || p < 1 || p > 99999) {
        showError(price, 'Enter a valid price between $1 and $99,999.'); valid = false;
      }
      const d = parseInt(days?.value);
      if (!days || isNaN(d) || d < 1 || d > 365) {
        showError(days, 'Delivery days must be between 1 and 365.'); valid = false;
      }

      if (!valid) {
        e.preventDefault();
        shakeForm(svcForm);
        // Scroll to first error
        const firstErr = svcForm.querySelector('.has-error');
        if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else {
        setLoading(svcForm.querySelector('[type="submit"]'));
      }
    });

    // Live character counter for description
    const desc = $('#description');
    if (desc) {
      const hint = desc.nextElementSibling;
      desc.addEventListener('input', () => {
        const len = desc.value.length;
        if (hint && hint.classList.contains('field-hint')) {
          hint.textContent = `${len} chars (min 20)`;
          hint.style.color = len < 20 ? 'var(--red)' : 'var(--mist)';
        }
      });
    }

    // Live price formatter
    const priceInput = $('#price');
    if (priceInput) {
      priceInput.addEventListener('blur', () => {
        const v = parseFloat(priceInput.value);
        if (!isNaN(v)) priceInput.value = v.toFixed(2);
      });
    }
  }
}

/* ── Password strength meter ──────────────────────────────── */
function updatePasswordStrength(input) {
  let meter = input.parentElement.querySelector('.pwd-strength');
  if (!meter) {
    meter = document.createElement('div');
    meter.className = 'pwd-strength';
    meter.innerHTML = `<div class="pwd-strength__bar"><div class="pwd-strength__fill"></div></div><span class="pwd-strength__label"></span>`;
    input.parentElement.insertAdjacentElement('afterend', meter);
  }
  const v = input.value;
  let score = 0;
  if (v.length >= 8)              score++;
  if (v.length >= 12)             score++;
  if (/[A-Z]/.test(v))            score++;
  if (/[0-9]/.test(v))            score++;
  if (/[^A-Za-z0-9]/.test(v))    score++;

  const levels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Excellent'];
  const colors = ['', 'var(--red)', 'var(--amber)', 'var(--amber)', 'var(--green)', 'var(--green)'];

  const fill  = meter.querySelector('.pwd-strength__fill');
  const label = meter.querySelector('.pwd-strength__label');
  fill.style.width = (score / 5 * 100) + '%';
  fill.style.background = colors[score] || 'var(--fog)';
  label.textContent = levels[score] || '';
  label.style.color = colors[score] || 'var(--mist)';
}

/* ── 4. CONFIRM DELETE MODAL ──────────────────────────────── */
function initConfirmDelete() {
  // Create modal once
  const modal = document.createElement('div');
  modal.id = 'confirmModal';
  modal.className = 'confirm-modal';
  modal.innerHTML = `
    <div class="confirm-modal__backdrop"></div>
    <div class="confirm-modal__box" role="dialog" aria-modal="true">
      <div class="confirm-modal__icon">🗑️</div>
      <h3 class="confirm-modal__title">Delete service?</h3>
      <p class="confirm-modal__msg">This action cannot be undone.</p>
      <div class="confirm-modal__actions">
        <button class="btn btn--outline" id="confirmCancel">Cancel</button>
        <button class="btn btn--danger" id="confirmOk">Delete</button>
      </div>
    </div>`;
  document.body.appendChild(modal);

  let pendingForm = null;

  // Intercept all delete forms
  document.addEventListener('submit', e => {
    const form = e.target.closest('form[data-confirm]');
    if (!form) return;
    e.preventDefault();
    pendingForm = form;

    const title = form.dataset.confirmTitle || 'this item';
    modal.querySelector('.confirm-modal__title').textContent = `Delete "${title}"?`;
    modal.querySelector('.confirm-modal__msg').textContent =
      form.dataset.confirmMsg || 'This action cannot be undone.';

    modal.classList.add('open');
    $('#confirmOk').focus();
  });

  $('#confirmOk').addEventListener('click', () => {
    if (pendingForm) {
      modal.classList.remove('open');
      // Remove data-confirm temporarily to avoid re-intercept
      pendingForm.removeAttribute('data-confirm');
      pendingForm.submit();
    }
  });

  const closeModal = () => {
    modal.classList.remove('open');
    pendingForm = null;
  };
  $('#confirmCancel').addEventListener('click', closeModal);
  modal.querySelector('.confirm-modal__backdrop').addEventListener('click', closeModal);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
}

/* ── 5. LIVE SEARCH / FILTER ──────────────────────────────── */
function initLiveSearch() {
  // Service list page search
  const serviceSearch = $('#serviceListSearch');
  if (serviceSearch) {
    const cards = $$('.service-card');
    serviceSearch.addEventListener('input', () => {
      const q = serviceSearch.value.toLowerCase().trim();
      let visible = 0;
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const show = !q || text.includes(q);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      updateSearchCount(serviceSearch, visible, cards.length);
    });
  }

  // Order list page search
  const orderSearch = $('#orderListSearch');
  if (orderSearch) {
    const rows = $$('.order-row');
    orderSearch.addEventListener('input', () => {
      const q = orderSearch.value.toLowerCase().trim();
      let visible = 0;
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const show = !q || text.includes(q);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      updateSearchCount(orderSearch, visible, rows.length);
    });
  }

  // Marketplace live search (client-side highlight on results already rendered)
  const mpSearch = $('#mpLiveSearch');
  if (mpSearch) {
    const cards = $$('.mp-card');
    mpSearch.addEventListener('input', () => {
      const q = mpSearch.value.toLowerCase().trim();
      let visible = 0;
      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const show = !q || text.includes(q);
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      const counter = $('#mpResultCount');
      if (counter) counter.textContent = q ? `${visible} result${visible !== 1 ? 's' : ''} for "${mpSearch.value}"` : `${cards.length} services`;
    });
  }
}

function updateSearchCount(input, visible, total) {
  let counter = input.parentElement.querySelector('.search-count');
  if (!counter) {
    counter = document.createElement('small');
    counter.className = 'search-count';
    input.parentElement.appendChild(counter);
  }
  counter.textContent = input.value ? `${visible} of ${total}` : '';
}

/* ── 6. IMAGE UPLOAD PREVIEW ──────────────────────────────── */
function initImageUpload() {
  $$('.upload-zone').forEach(zone => {
    const input   = $('input[type="file"]', zone);
    const preview = $('.upload-zone__preview', zone);
    const img     = preview ? $('img', preview) : null;
    const label   = $('.upload-zone__label', zone);
    const clearBtn= $('.upload-zone__preview button', zone);

    if (!input) return;

    function showPreview(file) {
      if (!file || !img) return;
      const reader = new FileReader();
      reader.onload = ev => {
        img.src = ev.target.result;
        if (label)   label.hidden   = true;
        if (preview) preview.hidden = false;
      };
      reader.readAsDataURL(file);
    }

    input.addEventListener('change', () => showPreview(input.files[0]));

    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        input.value = '';
        if (img)     img.src = '';
        if (label)   label.hidden   = false;
        if (preview) preview.hidden = true;
        const removeCb = document.getElementById('removeImage');
        if (removeCb) removeCb.checked = false;
      });
    }

    // Drag & drop
    zone.addEventListener('dragover',  e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault();
      zone.classList.remove('drag-over');
      const file = e.dataTransfer.files[0];
      if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showPreview(file);
      }
    });
  });
}

/* ── 7. FLASH AUTO-DISMISS ────────────────────────────────── */
function initFlashMessages() {
  $$('.flash').forEach((flash, i) => {
    // Auto-dismiss after 5s
    setTimeout(() => dismissFlash(flash), 5000 + i * 300);

    // Click to dismiss
    flash.style.cursor = 'pointer';
    flash.title = 'Click to dismiss';
    flash.addEventListener('click', () => dismissFlash(flash));
  });
}

function dismissFlash(el) {
  el.style.transition = 'opacity .3s, transform .3s, max-height .4s .1s, padding .4s .1s, margin .4s .1s';
  el.style.opacity    = '0';
  el.style.transform  = 'translateY(-6px)';
  el.style.maxHeight  = '0';
  el.style.padding    = '0';
  el.style.marginBottom = '0';
  setTimeout(() => el.remove(), 520);
}

/* ── 8. STAT COUNTER ANIMATION ────────────────────────────── */
function initStatCounters() {
  const cards = $$('.stat-card__number');
  if (!cards.length) return;

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el  = entry.target;
      const raw = el.textContent.trim();

      // Handle currency
      const isCurrency = raw.startsWith('$');
      const num = parseFloat(raw.replace(/[$,]/g, ''));
      if (isNaN(num) || num === 0) return;

      observer.unobserve(el);
      animateCount(el, num, isCurrency);
    });
  }, { threshold: 0.5 });

  cards.forEach(c => observer.observe(c));
}

function animateCount(el, target, isCurrency) {
  const dur   = 900;
  const start = performance.now();
  const fmt   = n => isCurrency
    ? '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    : Math.round(n).toLocaleString('en-US');

  function step(now) {
    const t = Math.min((now - start) / dur, 1);
    const ease = 1 - Math.pow(1 - t, 4); // ease-out-quart
    el.textContent = fmt(ease * target);
    if (t < 1) requestAnimationFrame(step);
    else el.textContent = fmt(target);
  }
  requestAnimationFrame(step);
}

/* ── 9. STAGGER CARD ANIMATIONS ───────────────────────────── */
function initStaggerAnimations() {
  const grids = $$('.mp-grid, .service-grid, .order-list, .stats-grid');
  grids.forEach(grid => {
    const children = [...grid.children];
    children.forEach((child, i) => {
      child.style.animationDelay = `${i * 0.055}s`;
    });
  });
}

/* ── 10. REQUIREMENTS CHAR COUNTER ───────────────────────── */
function initCharCounters() {
  $$('textarea[maxlength]').forEach(ta => {
    const max     = parseInt(ta.getAttribute('maxlength'));
    const counter = ta.nextElementSibling?.id === 'reqCount'
      ? ta.nextElementSibling
      : null;
    if (!counter) return;
    const update = () => {
      const remaining = max - ta.value.length;
      counter.textContent = `${ta.value.length} / ${max}`;
      counter.style.color = remaining < 100 ? 'var(--red)' : 'var(--mist)';
    };
    ta.addEventListener('input', update);
    update();
  });
}

/* ── Validation helpers ───────────────────────────────────── */
function isValidEmail(v) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
}

function showError(input, msg) {
  if (!input) return;
  const group = input.closest('.form-group');
  if (!group) return;
  group.classList.add('has-error');
  group.classList.remove('is-valid');

  let err = group.querySelector('.field-error');
  if (!err) {
    err = document.createElement('span');
    err.className = 'field-error';
    input.after(err);
  }
  err.textContent = msg;
}

function clearError(input) {
  if (!input) return;
  const group = input.closest('.form-group');
  if (!group) return;
  group.classList.remove('has-error');
  const err = group.querySelector('.field-error');
  if (err) err.remove();
  if (input.value.trim()) group.classList.add('is-valid');
}

function clearErrors(form) {
  $$('.has-error', form).forEach(g => {
    g.classList.remove('has-error');
    g.classList.remove('is-valid');
  });
  $$('.field-error', form).forEach(e => e.remove());
  $$('.pwd-strength', form.parentElement || document).forEach(m => m.remove());
}

function onBlurValidate(input, validator) {
  if (!input) return;
  input.addEventListener('blur', () => {
    const msg = validator(input.value);
    if (msg) showError(input, msg);
    else clearError(input);
  });
}

function shakeForm(form) {
  form.classList.remove('shake');
  void form.offsetWidth; // reflow
  form.classList.add('shake');
  setTimeout(() => form.classList.remove('shake'), 600);
}

function setLoading(btn) {
  if (!btn) return;
  btn.classList.add('loading');
  btn.disabled = true;
  const orig = btn.textContent;
  btn.dataset.orig = orig;
  btn.textContent = 'Please wait…';
}

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initPasswordToggles();
  initFormValidation();
  initConfirmDelete();
  initLiveSearch();
  initImageUpload();
  initFlashMessages();
  initStatCounters();
  initStaggerAnimations();
  initCharCounters();
});
