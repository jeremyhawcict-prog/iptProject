/**
 * MediQueue — Client-Side Utility Module
 * Loaded on every page via footer.php
 *
 * Usage:  utils.apiGet('/mediqueue/api/doctors/list.php', (err, data) => { ... });
 *         utils.showToast('Saved!', 'success');
 */

const utils = (() => {
  'use strict';

  /* =========================================================
     CSRF TOKEN HELPER
     ========================================================= */

  /**
   * Read the CSRF token from the <meta name="csrf-token"> tag.
   * @returns {string}
   */
  function getCsrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }


  /* =========================================================
     API — GET
     ========================================================= */

  /**
   * Fetch GET request to a PHP API endpoint.
   * @param {string}            url       Full or relative URL to the API file
   * @param {object|Function}   params    Optional query parameters object, or callback
   * @param {Function}          callback  (error, data) — error is null on success
   */
  function apiGet(url, params, callback) {
    // Allow calling as apiGet(url, callback) without params
    if (typeof params === 'function') {
      callback = params;
      params = null;
    }
    // Build query string from params object
    if (params && typeof params === 'object') {
      var qs = Object.keys(params).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
      }).join('&');
      if (qs) url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
    }
    fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin'
    })
    .then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try {
          data = text ? JSON.parse(text) : {};
        } catch (e) {
          throw new Error('Invalid JSON response (' + res.status + ')');
        }
        data._httpStatus = res.status;
        data._ok = res.ok;
        return data;
      });
    })
    .then(function (data) {
      callback(null, data);
    })
    .catch(function (err) {
      console.error('[utils.apiGet]', err);
      callback(err, null);
    });
  }


  /* =========================================================
     API — POST (with automatic CSRF token)
     ========================================================= */

  /**
   * Fetch POST request. Accepts FormData or a plain object.
   * Automatically attaches the CSRF token from the meta tag.
   *
   * @param {string}            url               API endpoint URL
   * @param {FormData|object}   formDataOrObject   Payload
   * @param {Function}          callback           (error, data)
   */
  function apiPost(url, formDataOrObject, callback) {
    var csrfToken = getCsrfToken();
    var options = {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': csrfToken
      }
    };

    if (formDataOrObject instanceof FormData) {
      // Let browser set Content-Type with boundary
      options.body = formDataOrObject;
    } else {
      options.headers['Content-Type'] = 'application/json; charset=utf-8';
      options.body = JSON.stringify(formDataOrObject);
    }

    fetch(url, options)
    .then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try {
          data = text ? JSON.parse(text) : {};
        } catch (e) {
          throw new Error('Invalid JSON response (' + res.status + ')');
        }
        data._httpStatus = res.status;
        data._ok = res.ok;
        return data;
      });
    })
    .then(function (data) {
      callback(null, data);
    })
    .catch(function (err) {
      console.error('[utils.apiPost]', err);
      callback(err, null);
    });
  }


  /* =========================================================
     TOAST NOTIFICATIONS
     ========================================================= */

  /**
   * Show a floating toast notification (bottom-right).
   * @param {string} message   Toast text
   * @param {string} type      'success' | 'error' | 'info' | 'warning'
   * @param {number} duration  Auto-dismiss in ms (default 3500)
   */
  function showToast(message, type, duration) {
    type     = type || 'info';
    duration = duration || 3500;

    // Ensure container exists
    var container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    var icons = {
      success: 'fa-circle-check',
      error:   'fa-circle-xmark',
      warning: 'fa-triangle-exclamation',
      info:    'fa-circle-info'
    };

    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML =
      '<div class="toast-icon"><i class="fa-solid ' + (icons[type] || icons.info) + '"></i></div>' +
      '<div class="toast-body">' + _escapeHtml(message) + '</div>' +
      '<button class="toast-close" aria-label="Close">&times;</button>';

    // Wire close button
    toast.querySelector('.toast-close').addEventListener('click', function () {
      _dismissToast(toast);
    });

    container.appendChild(toast);

    // Trigger entrance animation
    requestAnimationFrame(function () {
      toast.classList.add('toast--visible');
    });

    // Auto-dismiss
    var timer = setTimeout(function () {
      _dismissToast(toast);
    }, duration);

    // Pause on hover
    toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
    toast.addEventListener('mouseleave', function () {
      timer = setTimeout(function () { _dismissToast(toast); }, 1500);
    });
  }

  function _dismissToast(el) {
    if (!el || el._dismissing) return;
    el._dismissing = true;
    el.classList.remove('toast--visible');
    el.classList.add('toast--exit');
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 350);
  }


  /* =========================================================
     ALERTS (legacy — kept for backward compatibility)
     ========================================================= */

  /**
   * Inject an alert into the page.
   * @param {string} message       Alert text
   * @param {string} type          'success' | 'danger' | 'warning' | 'info'
   * @param {string} containerId   ID of the container element (default: 'alert-container')
   */
  function showAlert(message, type, containerId) {
    type        = type || 'info';
    containerId = containerId || 'alert-container';

    var container = document.getElementById(containerId);
    if (!container) return;

    var icons = {
      success: 'fa-circle-check',
      danger:  'fa-circle-xmark',
      warning: 'fa-triangle-exclamation',
      info:    'fa-circle-info'
    };

    var alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.innerHTML =
      '<span class="alert-message">' +
        '<i class="fa-solid ' + (icons[type] || icons.info) + '" style="margin-right:8px"></i>' +
        _escapeHtml(message) +
      '</span>' +
      '<button class="alert-close" aria-label="Close">&times;</button>';

    alert.querySelector('.alert-close').addEventListener('click', function () {
      _dismissEl(alert);
    });

    container.prepend(alert);

    // Auto-dismiss after 4 seconds
    setTimeout(function () { _dismissEl(alert); }, 4000);
  }


  /* =========================================================
     LOADING BUTTON
     ========================================================= */

  /**
   * Toggle a button between normal and loading state.
   * @param {string|HTMLElement} btn       Button element or its ID
   * @param {boolean}           isLoading true to set loading, false to restore
   */
  function loadingButton(btn, isLoading) {
    if (typeof btn === 'string') btn = document.getElementById(btn);
    if (!btn) return;

    if (isLoading) {
      btn.setAttribute('data-original-html', btn.innerHTML);
      btn.disabled = true;
      btn.classList.add('btn--loading');
      btn.innerHTML =
        '<span class="spinner" style="width:16px;height:16px;border-width:2px;margin-right:6px"></span>' +
        'Loading\u2026';
    } else {
      btn.disabled = false;
      btn.classList.remove('btn--loading');
      var original = btn.getAttribute('data-original-html');
      if (original) btn.innerHTML = original;
    }
  }


  /* =========================================================
     EMPTY STATE RENDERER
     ========================================================= */

  /**
   * Render a styled empty state inside a container.
   * @param {string|HTMLElement} container  Container element or its ID
   * @param {string}             message    Main message text
   * @param {string}             icon       Font Awesome icon class (default: 'fa-inbox')
   * @param {string}             ctaText    Optional call-to-action button text
   * @param {string}             ctaHref    Optional CTA link URL
   */
  function renderEmptyState(container, message, icon, ctaText, ctaHref) {
    if (typeof container === 'string') container = document.getElementById(container);
    if (!container) return;

    icon = icon || 'fa-inbox';

    var html =
      '<div class="empty-state fade-in">' +
        '<i class="fa-solid ' + icon + '"></i>' +
        '<h4>' + _escapeHtml(message) + '</h4>' +
        '<p>There\'s nothing here yet.</p>';

    if (ctaText && ctaHref) {
      html += '<a href="' + _escapeHtml(ctaHref) + '" class="btn btn-primary">' +
                '<i class="fa-solid fa-plus" style="margin-right:6px"></i>' +
                _escapeHtml(ctaText) +
              '</a>';
    }

    html += '</div>';
    container.innerHTML = html;
  }


  /* =========================================================
     MODAL
     ========================================================= */

  /**
   * Show a modal with the given HTML content.
   * @param {string} htmlContent  HTML to place inside the modal body
   * @param {string} title        Optional modal header title
   */
  function showModal(htmlContent, title) {
    title = title || '';

    var overlay = document.querySelector('.modal-overlay');
    var modal, header, body;

    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'modal-overlay';

      modal = document.createElement('div');
      modal.className = 'modal';

      header = document.createElement('div');
      header.className = 'modal-header';

      body = document.createElement('div');
      body.className = 'modal-body';

      modal.appendChild(header);
      modal.appendChild(body);
      overlay.appendChild(modal);
      document.body.appendChild(overlay);
    } else {
      modal  = overlay.querySelector('.modal');
      header = overlay.querySelector('.modal-header');
      body   = overlay.querySelector('.modal-body');
    }

    header.innerHTML =
      '<h3>' + _escapeHtml(title) + '</h3>' +
      '<button class="modal-close" aria-label="Close modal">&times;</button>';

    body.innerHTML = htmlContent;

    overlay.classList.add('modal--open');
    document.body.style.overflow = 'hidden';

    header.querySelector('.modal-close').addEventListener('click', closeModal);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeModal();
    });
  }

  function closeModal() {
    var overlay = document.querySelector('.modal-overlay');
    if (!overlay) return;
    overlay.classList.remove('modal--open');
    document.body.style.overflow = '';

    setTimeout(function () {
      var body = overlay.querySelector('.modal-body');
      if (body) body.innerHTML = '';
    }, 300);
  }


  /* =========================================================
     CONFIRM ACTION (custom styled modal)
     ========================================================= */

  /**
   * Show a styled confirmation dialog with OK and Cancel buttons.
   * @param {string}   message    The question/message to display
   * @param {Function} onConfirm  Called if the user clicks OK
   */
  function confirmAction(message, onConfirm) {
    var id = 'mq-confirm-' + Date.now();
    var overlay = document.createElement('div');
    overlay.id = id;
    overlay.className = 'modal-overlay modal--open';
    overlay.innerHTML =
      '<div class="modal" style="max-width:440px">' +
        '<div class="modal-header">' +
          '<h3><i class="fa-solid fa-triangle-exclamation" style="color:var(--clr-warning);margin-right:8px"></i>Confirm Action</h3>' +
          '<button class="modal-close" data-action="cancel" aria-label="Close">&times;</button>' +
        '</div>' +
        '<div class="modal-body">' +
          '<p style="margin:0;color:var(--clr-text);line-height:1.6">' + _escapeHtml(message) + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
          '<button class="btn btn-ghost" data-action="cancel">Cancel</button>' +
          '<button class="btn btn-primary" data-action="confirm"><i class="fa-solid fa-check" style="margin-right:6px"></i>OK</button>' +
        '</div>' +
      '</div>';

    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';

    function cleanup() {
      overlay.remove();
      document.body.style.overflow = '';
    }

    overlay.querySelectorAll('[data-action="cancel"]').forEach(function (btn) {
      btn.addEventListener('click', cleanup);
    });

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) cleanup();
    });

    overlay.querySelector('[data-action="confirm"]').addEventListener('click', function () {
      cleanup();
      if (typeof onConfirm === 'function') onConfirm();
    });
  }


  /* =========================================================
     SPINNER (full-page loading overlay)
     ========================================================= */

  function showSpinner() {
    if (document.getElementById('mq-spinner-overlay')) return;
    var overlay = document.createElement('div');
    overlay.id = 'mq-spinner-overlay';
    overlay.className = 'spinner-overlay';
    overlay.innerHTML = '<div class="spinner spinner-lg"></div>';
    document.body.appendChild(overlay);
  }

  function hideSpinner() {
    var overlay = document.getElementById('mq-spinner-overlay');
    if (overlay) overlay.remove();
  }


  /* =========================================================
     DATE / TIME FORMATTING
     ========================================================= */

  function formatDate(dateStr) {
    if (!dateStr) return '';
    var d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', {
      year:  'numeric',
      month: 'long',
      day:   'numeric'
    });
  }

  function formatTime(timeStr) {
    if (!timeStr) return '';
    var parts = timeStr.split(':');
    var date  = new Date();
    date.setHours(parseInt(parts[0], 10), parseInt(parts[1], 10), 0);
    return date.toLocaleTimeString('en-US', {
      hour:    'numeric',
      minute:  '2-digit',
      hour12:  true
    });
  }


  /* =========================================================
     DEBOUNCE
     ========================================================= */

  function debounce(fn, delay) {
    var timer;
    return function () {
      var context = this;
      var args    = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }


  /* =========================================================
     STAR RATING
     ========================================================= */

  function renderStars(rating) {
    rating = Math.max(0, Math.min(5, Math.round(rating)));
    var html = '<span class="star-rating">';
    for (var i = 1; i <= 5; i++) {
      html += '<i class="fa-solid fa-star ' + (i <= rating ? 'filled' : '') + '"></i>';
    }
    html += '</span>';
    return html;
  }


  /* =========================================================
     EXPORT TABLE TO CSV
     ========================================================= */

  function exportTableToCSV(tableId, filename) {
    var table = document.getElementById(tableId);
    if (!table) {
      console.error('[utils.exportTableToCSV] Table not found:', tableId);
      return;
    }

    var csv = [];
    var rows = table.querySelectorAll('tr');

    rows.forEach(function (row) {
      var cols = row.querySelectorAll('td, th');
      var rowData = [];
      cols.forEach(function (col) {
        var text = col.innerText.replace(/(\r\n|\n|\r)/gm, ' ').trim();
        text = '"' + text.replace(/"/g, '""') + '"';
        rowData.push(text);
      });
      csv.push(rowData.join(','));
    });

    var csvContent = csv.join('\n');
    var blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });

    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();

    setTimeout(function () {
      document.body.removeChild(link);
      URL.revokeObjectURL(link.href);
    }, 100);

    showToast('CSV exported successfully!', 'success');
  }


  /* =========================================================
     INTERNAL HELPERS
     ========================================================= */

  function _escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function _dismissEl(el) {
    if (!el || !el.parentNode) return;
    el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(-10px)';
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 300);
  }


  /* =========================================================
     FORM HELPERS
     ========================================================= */

  function serializeForm(form) {
    var data     = {};
    var formData = new FormData(form);
    formData.forEach(function (value, key) {
      if (data[key] !== undefined) {
        if (!Array.isArray(data[key])) data[key] = [data[key]];
        data[key].push(value);
      } else {
        data[key] = value;
      }
    });
    return data;
  }

  /**
   * @deprecated Use loadingButton() instead
   */
  function btnLoading(btn, loading) {
    loadingButton(btn, loading);
  }

  function showFieldError(fieldId, message) {
    clearFieldError(fieldId);
    var field = document.getElementById(fieldId);
    if (!field) return;
    field.classList.add('is-invalid');
    var span = document.createElement('span');
    span.className = 'form-error';
    span.setAttribute('data-error-for', fieldId);
    span.textContent = message;
    field.parentNode.appendChild(span);
  }

  function clearFieldError(fieldId) {
    var field = document.getElementById(fieldId);
    if (!field) return;
    field.classList.remove('is-invalid');
    var err = field.parentNode.querySelector('[data-error-for="' + fieldId + '"]');
    if (err) err.remove();
  }

  function clearAllErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    form.querySelectorAll('.form-error').forEach(function (el) { el.remove(); });
  }


  /* =========================================================
     SIDEBAR TOGGLE (for dashboard pages)
     ========================================================= */

  /**
   * Initialize sidebar toggle behavior on dashboard pages.
   * Call once after DOM is ready on pages with .sidebar.
   */
  function initSidebar() {
    var sidebar = document.querySelector('.sidebar');
    var toggle  = document.querySelector('.sidebar-toggle');
    var overlay = document.querySelector('.sidebar-overlay');

    if (!sidebar || !toggle) return;

    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('sidebar--open');
      if (overlay) overlay.classList.toggle('active');
      document.body.style.overflow = sidebar.classList.contains('sidebar--open') ? 'hidden' : '';
    });

    if (overlay) {
      overlay.addEventListener('click', function () {
        sidebar.classList.remove('sidebar--open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    }
  }


  /* =========================================================
     URL HELPERS
     ========================================================= */

  var BASE_URL = (function() {
    var meta = document.querySelector('meta[name="base-url"]');
    return meta ? meta.getAttribute('content') : '/Mediqueue';
  })();

  function apiUrl(path) {
    return BASE_URL + '/api/' + path;
  }

  function pageUrl(path) {
    return BASE_URL + '/pages/' + path;
  }


  /* =========================================================
     API — UPLOAD (multipart/form-data)
     ========================================================= */

  function apiUpload(url, formData, callback) {
    formData.append('csrf_token', getCsrfToken());
    fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': getCsrfToken()
      },
      body: formData,
      credentials: 'same-origin'
    })
    .then(function (res) {
      return res.text().then(function (text) {
        var data = null;
        try {
          data = text ? JSON.parse(text) : {};
        } catch (e) {
          throw new Error('Invalid JSON response (' + res.status + ')');
        }
        data._httpStatus = res.status;
        data._ok = res.ok;
        return data;
      });
    })
    .then(function (data) {
      if (callback) callback(null, data);
    })
    .catch(function (err) {
      console.error('[utils.apiUpload]', err);
      if (callback) callback(err, null);
    });
  }


  /* =========================================================
     PUBLIC API
     ========================================================= */
  return {
    // Core API
    apiGet:          apiGet,
    apiPost:         apiPost,
    apiUpload:       apiUpload,
    getCsrfToken:    getCsrfToken,

    // URL helpers
    apiUrl:          apiUrl,
    pageUrl:         pageUrl,
    BASE_URL:        BASE_URL,

    // UI — Toasts (primary)
    showToast:       showToast,

    // UI — Alerts (legacy)
    showAlert:       showAlert,

    // UI — Modal
    showModal:       showModal,
    closeModal:      closeModal,
    confirmAction:   confirmAction,

    // UI — Loading
    showSpinner:     showSpinner,
    hideSpinner:     hideSpinner,
    loadingButton:   loadingButton,

    // UI — Empty state
    renderEmptyState: renderEmptyState,

    // UI — Sidebar
    initSidebar:     initSidebar,

    // Formatting
    formatDate:      formatDate,
    formatTime:      formatTime,
    renderStars:     renderStars,

    // Utilities
    debounce:        debounce,
    exportTableToCSV:exportTableToCSV,
    serializeForm:   serializeForm,
    getUrlParam:     function(name) {
      var params = new URLSearchParams(window.location.search);
      return params.get(name);
    },

    // Form helpers
    btnLoading:      btnLoading,
    loadingBtn:      loadingButton,
    showFieldError:  showFieldError,
    clearFieldError: clearFieldError,
    clearAllErrors:  clearAllErrors,
    _escapeHtml:     _escapeHtml,
    escapeHtml:      _escapeHtml
  };

})();

/* =============================================================
   Global Modal Helpers (used by onclick attributes in pages)
   ============================================================= */
function openModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) { modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) { modal.classList.remove('active'); document.body.style.overflow = ''; }
}

/* Auto-init: close modals on overlay click & Escape key */
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === this) { this.classList.remove('active'); document.body.style.overflow = ''; }
    });
  });
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function(m) {
        m.classList.remove('active');
      });
      document.body.style.overflow = '';
    }
  });
});
