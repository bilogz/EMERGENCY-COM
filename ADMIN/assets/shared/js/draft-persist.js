/* Draft Persist (Cookie-first, localStorage-backed)
 *
 * Opt-in by adding `data-draft-key="some-key"` to a <form>.
 * Optionally add `data-draft-status="#selector"` to show "Draft saved".
 *
 * Saves values on input/change (debounced) so users don't lose work on refresh/crash.
 */
(function () {
    'use strict';

    const COOKIE_DAYS = 7;
    const COOKIE_PREFIX = 'ec_draft_';
    const LS_PREFIX = 'ec_draft_ls:';

    function nowIso() {
        try { return new Date().toISOString(); } catch { return ''; }
    }

    function fnv1a(str) {
        // 32-bit FNV-1a
        let hash = 0x811c9dc5;
        for (let i = 0; i < str.length; i++) {
            hash ^= str.charCodeAt(i);
            hash = (hash >>> 0) * 0x01000193;
        }
        return (hash >>> 0).toString(16);
    }

    function cookieNameForKey(key) {
        const h = fnv1a(String(key || ''));
        return COOKIE_PREFIX + h;
    }

    function setCookie(name, value, days) {
        const maxAge = Math.max(0, Math.floor((days || COOKIE_DAYS) * 86400));
        const secure = (location && location.protocol === 'https:') ? '; Secure' : '';
        document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;
    }

    function getCookie(name) {
        const m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[$()*+./?[\\\]^{|}-]/g, '\\$&') + '=([^;]*)'));
        return m ? m[1] : null;
    }

    function deleteCookie(name) {
        setCookie(name, '', -1);
    }

    function safeJsonParse(text) {
        try { return JSON.parse(text); } catch { return null; }
    }

    function encodePayload(payload) {
        return encodeURIComponent(JSON.stringify(payload));
    }

    function decodePayload(encoded) {
        try {
            const decoded = decodeURIComponent(encoded);
            return safeJsonParse(decoded);
        } catch {
            return null;
        }
    }

    function getDraft(key) {
        const cookieName = cookieNameForKey(key);
        const fromLs = (() => {
            try { return localStorage.getItem(LS_PREFIX + key); } catch { return null; }
        })();
        const fromCookie = getCookie(cookieName);

        const lsDraft = fromLs ? safeJsonParse(fromLs) : null;
        if (lsDraft && typeof lsDraft === 'object') return lsDraft;

        const cookieDraft = fromCookie ? decodePayload(fromCookie) : null;
        if (cookieDraft && typeof cookieDraft === 'object') return cookieDraft;

        return null;
    }

    function setDraft(key, payload) {
        const cookieName = cookieNameForKey(key);

        // Store full payload in localStorage (best-effort)
        try { localStorage.setItem(LS_PREFIX + key, JSON.stringify(payload)); } catch {}

        // Store cookie (size-limited). If too big, still keep a slim version.
        let encoded = encodePayload(payload);
        if (encoded.length > 3600) {
            const slim = { ...payload, values: { ...(payload.values || {}) } };
            // Trim any very large text fields (cookie limit safety)
            Object.keys(slim.values).forEach(k => {
                const v = slim.values[k];
                if (typeof v === 'string' && v.length > 1200) slim.values[k] = v.slice(0, 1200);
                if (Array.isArray(v) && v.length > 50) slim.values[k] = v.slice(0, 50);
            });
            encoded = encodePayload(slim);
            if (encoded.length > 3600) {
                // Last resort: store only a marker + timestamp
                encoded = encodePayload({ key, ts: payload.ts || nowIso(), values: {} });
            }
        }

        setCookie(cookieName, encoded, COOKIE_DAYS);
    }

    function clearDraft(key) {
        const cookieName = cookieNameForKey(key);
        deleteCookie(cookieName);
        try { localStorage.removeItem(LS_PREFIX + key); } catch {}
    }

    function fieldKey(el) {
        return (el.getAttribute('name') || el.id || '').trim();
    }

    function shouldSkip(el) {
        if (!el || el.disabled) return true;
        if (el.closest && el.closest('[data-draft-ignore]')) return true;
        const type = (el.getAttribute('type') || '').toLowerCase();
        if (type === 'password' || type === 'file') return true;
        const name = (el.getAttribute('name') || '').toLowerCase();
        if (name.includes('password')) return true;
        return false;
    }

    function collectFormValues(form) {
        const values = {};
        const els = form.querySelectorAll('input, textarea, select');
        els.forEach(el => {
            if (shouldSkip(el)) return;
            const key = fieldKey(el);
            if (!key) return;

            const tag = el.tagName.toLowerCase();
            const type = (el.getAttribute('type') || '').toLowerCase();

            if (type === 'checkbox') {
                if (!values[key]) values[key] = [];
                if (el.checked) values[key].push(el.value || 'on');
                return;
            }

            if (type === 'radio') {
                if (el.checked) values[key] = el.value;
                return;
            }

            if (tag === 'select' && el.multiple) {
                values[key] = Array.from(el.selectedOptions).map(o => o.value);
                return;
            }

            values[key] = el.value;
        });
        return values;
    }

    function applyValue(el, value) {
        const tag = el.tagName.toLowerCase();
        const type = (el.getAttribute('type') || '').toLowerCase();

        if (type === 'checkbox') {
            const list = Array.isArray(value) ? value : [value];
            el.checked = list.includes(el.value || 'on');
            return;
        }

        if (type === 'radio') {
            el.checked = String(value) === String(el.value);
            return;
        }

        if (tag === 'select' && el.multiple && Array.isArray(value)) {
            Array.from(el.options).forEach(o => { o.selected = value.includes(o.value); });
            return;
        }

        el.value = value == null ? '' : String(value);
    }

    function restoreForm(form) {
        if (!form) return false;
        const key = form.getAttribute('data-draft-key');
        if (!key) return false;

        const draft = getDraft(key);
        if (!draft || !draft.values) return false;

        const els = form.querySelectorAll('input, textarea, select');
        els.forEach(el => {
            if (shouldSkip(el)) return;
            const k = fieldKey(el);
            if (!k) return;
            if (!(k in draft.values)) return;

            applyValue(el, draft.values[k]);
        });

        // Re-sync Select2 (if present)
        try {
            if (window.jQuery) {
                window.jQuery(form).find('select.select2-hidden-accessible').each(function () {
                    window.jQuery(this).trigger('change');
                });
            }
        } catch {}

        // Update any UI states bound to change/input handlers
        try {
            form.querySelectorAll('input, textarea, select').forEach(el => {
                el.dispatchEvent(new Event('change', { bubbles: true }));
                el.dispatchEvent(new Event('input', { bubbles: true }));
            });
        } catch {}

        return true;
    }

    function attachForm(form) {
        const key = form.getAttribute('data-draft-key');
        if (!key) return;

        let saveTimer = null;
        const statusSel = form.getAttribute('data-draft-status');
        const statusEl = statusSel ? document.querySelector(statusSel) : null;

        function showStatus(text) {
            if (!statusEl) return;
            statusEl.textContent = text;
            statusEl.classList.add('is-visible');
            window.clearTimeout(statusEl.__dp_t);
            statusEl.__dp_t = window.setTimeout(() => {
                statusEl.classList.remove('is-visible');
            }, 1500);
        }

        function saveNow() {
            const payload = { key, ts: nowIso(), values: collectFormValues(form) };
            setDraft(key, payload);
            showStatus('Draft saved');
        }

        function scheduleSave() {
            window.clearTimeout(saveTimer);
            saveTimer = window.setTimeout(saveNow, 450);
        }

        form.addEventListener('input', scheduleSave, true);
        form.addEventListener('change', scheduleSave, true);
        window.addEventListener('beforeunload', saveNow);

        // First restore pass
        restoreForm(form);
    }

    function init() {
        document.querySelectorAll('form[data-draft-key]').forEach(attachForm);
    }

    window.DraftPersist = {
        restoreForm,
        clearDraft,
        getDraft,
        setDraft
    };

    document.addEventListener('DOMContentLoaded', init);
})();

