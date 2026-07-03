/* Global UI Alert Modal (Admin)
 * Replaces blocking browser `alert()` dialogs with a themed modal.
 * Theme-safe: uses existing CSS variables (no new palette colors).
 */

(function () {
  if (window.__uiAlertInstalled) return;
  window.__uiAlertInstalled = true;

  const nativeAlert = window.alert;
  window.nativeAlert = nativeAlert;

  function ensureAlertDom() {
    let backdrop = document.getElementById('uiAlertBackdrop');
    if (backdrop) return backdrop;

    backdrop = document.createElement('div');
    backdrop.id = 'uiAlertBackdrop';
    backdrop.className = 'ui-alert-backdrop';
    backdrop.setAttribute('aria-hidden', 'true');
    backdrop.innerHTML = `
      <div class="ui-alert-modal" role="alertdialog" aria-modal="true" aria-labelledby="uiAlertTitle" aria-describedby="uiAlertMessage">
        <div class="ui-alert-head">
          <div class="ui-alert-title" id="uiAlertTitle">Notice</div>
          <button type="button" class="ui-alert-x" aria-label="Close">×</button>
        </div>
        <div class="ui-alert-body">
          <div class="ui-alert-message" id="uiAlertMessage"></div>
        </div>
        <div class="ui-alert-actions">
          <button type="button" class="btn btn-primary ui-alert-ok">OK</button>
        </div>
      </div>
    `;

    document.body.appendChild(backdrop);
    return backdrop;
  }

  function setOpen(backdrop, open) {
    if (!backdrop) return;
    backdrop.classList.toggle('is-open', open);
    backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
  }

  function uiAlert(message, options = {}) {
    const backdrop = ensureAlertDom();
    const dialog = backdrop.querySelector('.ui-alert-modal');
    const titleEl = backdrop.querySelector('#uiAlertTitle');
    const msgEl = backdrop.querySelector('#uiAlertMessage');
    const okBtn = backdrop.querySelector('.ui-alert-ok');
    const xBtn = backdrop.querySelector('.ui-alert-x');

    titleEl.textContent = (options && options.title) ? String(options.title) : 'Notice';
    msgEl.textContent = message == null ? '' : String(message);

    let lastActive = document.activeElement;

    const close = () => {
      setOpen(backdrop, false);
      document.removeEventListener('keydown', onKeyDown, true);
      backdrop.removeEventListener('click', onBackdropClick, true);
      okBtn.removeEventListener('click', onOk, true);
      xBtn.removeEventListener('click', onOk, true);
      if (lastActive && typeof lastActive.focus === 'function') {
        try { lastActive.focus(); } catch {}
      }
    };

    const onOk = (e) => {
      e.preventDefault();
      close();
    };

    const onBackdropClick = (e) => {
      if (e.target === backdrop) close();
    };

    const onKeyDown = (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        close();
        return;
      }
      if (e.key === 'Enter') {
        // Avoid submitting forms behind the modal
        e.preventDefault();
        close();
      }
      // Minimal focus trap
      if (e.key === 'Tab' && dialog) {
        const focusables = dialog.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };

    okBtn.addEventListener('click', onOk, true);
    xBtn.addEventListener('click', onOk, true);
    backdrop.addEventListener('click', onBackdropClick, true);
    document.addEventListener('keydown', onKeyDown, true);

    setOpen(backdrop, true);
    setTimeout(() => {
      try { okBtn.focus(); } catch {}
    }, 0);
  }

  window.uiAlert = uiAlert;
  window.alert = uiAlert;
})();

