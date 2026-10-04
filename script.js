/* ==========================================================================
   DYT - DESIGN YOUR TEXTILE | shared behaviour
   Mobile nav, scroll reveals, active-link highlighting, marquee build,
   and the enquiry form (client-side validation + async POST).
   ========================================================================== */
(function () {
  'use strict';

  /* ---------- Mobile navigation ---------- */
  var toggle = document.getElementById('menuToggle');
  var links = document.getElementById('navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---------- Active link based on current file ---------- */
  try {
    var path = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.nav-links a').forEach(function (a) {
      var href = a.getAttribute('href') || '';
      if (href === path || (path === '' && href === 'index.html')) {
        a.classList.add('active');
      }
    });
  } catch (e) { /* no-op */ }

  /* ---------- Scroll reveal ---------- */
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) {
          en.target.classList.add('in');
          io.unobserve(en.target);
        }
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Enquiry form ---------- */
  var form = document.getElementById('dytEnquiryForm');
  if (!form) { return; }

  var statusEl = document.getElementById('dytStatus');
  var submitBtn = document.getElementById('dytSubmit');
  var startedEl = document.getElementById('dytFormStarted');
  var tokenEl = document.getElementById('dytFormToken');
  var fileInput = document.getElementById('dytFile');
  var fileNameEl = document.getElementById('dytFileName');
  var fileDrop = document.getElementById('dytFileDrop');
  var MAX_FILE = 10 * 1024 * 1024;

  if (startedEl) { startedEl.value = String(Date.now()); }
  if (tokenEl) { tokenEl.value = (Date.now().toString(36)) + '-' + Math.random().toString(36).slice(2); }

  var fields = {
    name: { el: document.getElementById('dytName'), required: true, min: 2 },
    email: { el: document.getElementById('dytEmail'), required: true, email: true },
    phone: { el: document.getElementById('dytPhone'), required: true, min: 6 },
    company: { el: document.getElementById('dytCompany'), required: false },
    project_type: { el: document.getElementById('dytProjectType'), required: true, select: true },
    message: { el: document.getElementById('dytMessage'), required: true, min: 10 }
  };

  function setError(key, msg) {
    var f = fields[key];
    if (!f || !f.el) { return; }
    var err = document.querySelector('.error[data-for="' + key + '"]');
    if (msg) {
      f.el.classList.add('invalid');
      if (err) { err.textContent = msg; }
    } else {
      f.el.classList.remove('invalid');
      if (err) { err.textContent = ''; }
    }
  }

  function validateField(key) {
    var f = fields[key];
    if (!f || !f.el) { return true; }
    var v = (f.el.value || '').trim();
    if (f.required && !v) { setError(key, 'This field is required.'); return false; }
    if (v && f.min && v.length < f.min) { setError(key, 'Please enter at least ' + f.min + ' characters.'); return false; }
    if (v && f.email) {
      var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!re.test(v)) { setError(key, 'Please enter a valid email address.'); return false; }
    }
    setError(key, '');
    return true;
  }

  function clearFileError() {
    if (fileDrop) { fileDrop.classList.remove('invalid'); }
    var err = document.querySelector('.error[data-for="attachment"]');
    if (err) { err.textContent = ''; }
  }

  function validateFile() {
    if (!fileInput) { return true; }
    var file = fileInput.files && fileInput.files[0];
    if (!file) { clearFileError(); return true; }
    if (file.size > MAX_FILE) {
      var err = document.querySelector('.error[data-for="attachment"]');
      if (err) { err.textContent = 'File is too large. Maximum size is 10 MB.'; }
      if (fileDrop) { fileDrop.classList.add('invalid'); }
      return false;
    }
    clearFileError();
    return true;
  }

  if (fileInput && fileNameEl) {
    fileInput.addEventListener('change', function () {
      validateFile();
      var file = fileInput.files && fileInput.files[0];
      fileNameEl.textContent = file ? file.name : '';
    });
  }

  Object.keys(fields).forEach(function (key) {
    var f = fields[key];
    if (!f.el) { return; }
    f.el.addEventListener('input', function () {
      if (f.el.classList.contains('invalid')) { validateField(key); }
    });
    f.el.addEventListener('blur', function () {
      if ((f.el.value || '').trim() !== '') { validateField(key); }
    });
  });

  function validateAll() {
    var ok = true, firstInvalid = null;
    Object.keys(fields).forEach(function (key) {
      if (!validateField(key)) { ok = false; if (!firstInvalid) { firstInvalid = fields[key].el; } }
    });
    if (!validateFile()) { ok = false; if (!firstInvalid && fileInput) { firstInvalid = fileInput; } }
    if (firstInvalid && typeof firstInvalid.focus === 'function') { firstInvalid.focus(); }
    return ok;
  }

  function showStatus(type, title, body) {
    if (!statusEl) { return; }
    statusEl.className = 'status show ' + (type === 'success' ? 'success' : 'error');
    statusEl.innerHTML = '';
    var strong = document.createElement('strong'); strong.textContent = title; statusEl.appendChild(strong);
    var span = document.createElement('span'); span.textContent = body; statusEl.appendChild(span);
    if (typeof statusEl.scrollIntoView === 'function') { statusEl.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
  }

  function setLoading(on) {
    if (!submitBtn) { return; }
    if (on) { submitBtn.classList.add('loading'); submitBtn.setAttribute('aria-busy', 'true'); submitBtn.disabled = true; }
    else { submitBtn.classList.remove('loading'); submitBtn.removeAttribute('aria-busy'); submitBtn.disabled = false; }
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    if (!validateAll()) {
      showStatus('error', 'Please check the form', 'Some required details are missing or invalid. Fix the highlighted fields and try again.');
      return;
    }
    setLoading(true);
    if (statusEl) { statusEl.className = 'status'; statusEl.innerHTML = ''; }
    if (typeof window.fetch !== 'function') {
      setLoading(false);
      showStatus('error', 'Browser not supported', 'Please email admin@designyourtextile.com directly.');
      return;
    }
    var data = new FormData(form);
    window.fetch(form.getAttribute('action') || 'contact.php', {
      method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (resp) {
        return resp.text().then(function (text) {
          var json = null; try { json = JSON.parse(text); } catch (e) { json = null; }
          return { ok: resp.ok, status: resp.status, json: json };
        });
      })
      .then(function (result) {
        setLoading(false);
        if (result.json && result.json.success) {
          form.reset();
          if (fileNameEl) { fileNameEl.textContent = ''; }
          if (startedEl) { startedEl.value = String(Date.now()); }
          if (tokenEl) { tokenEl.value = (Date.now().toString(36)) + '-' + Math.random().toString(36).slice(2); }
          clearFileError();
          Object.keys(fields).forEach(function (key) { setError(key, ''); });
          showStatus('success', 'Thank you - your enquiry has been sent.', result.json.message || 'We will review your project details and reply within one business day.');
        } else {
          var msg = (result.json && result.json.message) ? result.json.message : 'Something went wrong while sending your enquiry. Please try again, or email admin@designyourtextile.com directly.';
          showStatus('error', 'Your enquiry could not be sent', msg);
        }
      })
      .catch(function () {
        setLoading(false);
        showStatus('error', 'Network error', 'We could not reach the server. Please check your connection and try again, or email admin@designyourtextile.com directly.');
      });
  });
})();
