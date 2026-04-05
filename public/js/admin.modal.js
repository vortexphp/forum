/**
 * Admin modal host: clones <template> nodes into #adm_modal. Content-agnostic (forms, HTML, custom partials).
 *
 * Programmatic use: AdmModal.open(title, templateId); AdmModal.close();
 * Optional [data-adm-modal-autofocus] on a control inside the template for initial focus.
 */
(function () {
    'use strict';

    var lastFocus = null;

    function isModalVisuallyClosed(root) {
        if (!root) return true;
        return root.classList.contains('hidden') || root.hidden;
    }

    function onDocKey(e) {
        if (e.key !== 'Escape') return;
        var root = document.getElementById('adm_modal');
        if (isModalVisuallyClosed(root)) return;
        e.preventDefault();
        closeModal();
    }

    function initConfirmIn(container) {
        container.querySelectorAll('form[data-adm-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var msg = form.getAttribute('data-adm-confirm');
                if (msg && !window.confirm(msg)) e.preventDefault();
            });
        });
    }

    function closeModal() {
        var root = document.getElementById('adm_modal');
        if (!root || isModalVisuallyClosed(root)) return;
        root.classList.add('hidden');
        root.hidden = true;
        var body = root.querySelector('[data-adm-modal-body]');
        if (body) body.innerHTML = '';
        document.removeEventListener('keydown', onDocKey, true);
        if (lastFocus && typeof lastFocus.focus === 'function') {
            try {
                lastFocus.focus();
            } catch (err) { /* ignore */ }
        }
        lastFocus = null;
    }

    function focusModalStart(body) {
        var marked = body.querySelector('[data-adm-modal-autofocus]');
        if (marked && typeof marked.focus === 'function') {
            try {
                marked.focus();
                return;
            } catch (err) { /* ignore */ }
        }
        var focusTarget = body.querySelector(
            'input:not([type="hidden"]):not([type="submit"]), textarea, select, button:not([data-adm-modal-close]), a[href]',
        );
        if (focusTarget && typeof focusTarget.focus === 'function') {
            try {
                focusTarget.focus();
            } catch (err) { /* ignore */ }
        }
    }

    function openModal(title, templateId) {
        var tpl = document.getElementById(templateId);
        if (!tpl || tpl.tagName !== 'TEMPLATE') return;
        var root = document.getElementById('adm_modal');
        if (!root) return;

        var titleEl = root.querySelector('[data-adm-modal-title]');
        var body = root.querySelector('[data-adm-modal-body]');
        if (!body) return;
        if (titleEl) titleEl.textContent = title || '';

        body.innerHTML = '';
        body.appendChild(tpl.content.cloneNode(true));
        initConfirmIn(body);

        lastFocus = document.activeElement;
        root.classList.remove('hidden');
        root.hidden = false;
        document.addEventListener('keydown', onDocKey, true);

        focusModalStart(body);
    }

    function onClick(e) {
        var btn = e.target.closest('[data-adm-modal-open]');
        if (!btn) return;
        e.preventDefault();
        var tid = btn.getAttribute('data-adm-modal-template');
        var title = btn.getAttribute('data-adm-modal-title') || '';
        if (tid) openModal(title, tid);
    }

    function onBackdropClick(e) {
        if (e.target.matches('[data-adm-modal-backdrop]')) closeModal();
    }

    function bindShell() {
        var root = document.getElementById('adm_modal');
        if (!root) return;
        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-adm-modal-close]')) {
                e.preventDefault();
                closeModal();
            } else onBackdropClick(e);
        });
    }

    function run() {
        bindShell();
        document.addEventListener('click', onClick, false);
    }

    window.AdmModal = {
        open: openModal,
        close: closeModal,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
