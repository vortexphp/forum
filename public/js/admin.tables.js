/**
 * Admin resource index: column visibility (localStorage) and table UX helpers.
 */
(function () {
    'use strict';

    function initColumnPicker(slug, meta, root, listEl) {
        var key = 'vortex_admin_table_cols_' + slug;
        var forced = [];
        var toggleable = [];
        for (var i = 0; i < meta.length; i++) {
            var c = meta[i];
            if (!c.toggleable) forced.push(c.name);
            else toggleable.push(c);
        }

        function defaultVisibleSet() {
            var v = forced.slice();
            for (var j = 0; j < toggleable.length; j++) {
                var t = toggleable[j];
                if (!t.startsCollapsed) v.push(t.name);
            }
            return v;
        }

        function loadVisible() {
            try {
                var raw = localStorage.getItem(key);
                if (!raw) return null;
                var parsed = JSON.parse(raw);
                if (!parsed || !Array.isArray(parsed)) return null;
                return parsed;
            } catch (e) {
                return null;
            }
        }

        function saveVisible(arr) {
            try {
                localStorage.setItem(key, JSON.stringify(arr));
            } catch (e) { /* ignore quota / private mode */ }
        }

        function validateVisible(arr) {
            var ok = {};
            for (var i = 0; i < meta.length; i++) ok[meta[i].name] = true;
            var out = [];
            for (var j = 0; j < arr.length; j++) {
                if (ok[arr[j]]) out.push(arr[j]);
            }
            return out;
        }

        var visible = loadVisible();
        if (visible) visible = validateVisible(visible);
        if (!visible || visible.length === 0) visible = defaultVisibleSet();

        var tset = {};
        for (var ti = 0; ti < toggleable.length; ti++) tset[toggleable[ti].name] = true;
        var toggledShown = false;
        for (var vi = 0; vi < visible.length; vi++) {
            if (tset[visible[vi]]) toggledShown = true;
        }
        if (!toggledShown && toggleable.length > 0) visible.push(toggleable[0].name);

        saveVisible(visible);

        function apply() {
            var show = {};
            for (var fi = 0; fi < forced.length; fi++) show[forced[fi]] = true;
            for (var vj = 0; vj < visible.length; vj++) show[visible[vj]] = true;
            root.querySelectorAll('[data-adm-col]').forEach(function (el) {
                var n = el.getAttribute('data-adm-col');
                if (n === '__actions') return;
                /* Use native hidden (UA stylesheet) — Tailwind’s .hidden is often purged when only toggled from JS. */
                el.hidden = !show[n];
            });
        }

        function syncFromChecks() {
            var v = forced.slice();
            listEl.querySelectorAll('input[type="checkbox"][data-adm-col-toggle]').forEach(function (inp) {
                if (inp.checked) v.push(inp.getAttribute('data-adm-col-toggle'));
            });
            var hasT = false;
            for (var i = 0; i < v.length; i++) if (tset[v[i]]) hasT = true;
            if (!hasT && toggleable.length > 0) v.push(toggleable[0].name);
            visible = v;
            saveVisible(visible);
            listEl.querySelectorAll('input[type="checkbox"][data-adm-col-toggle]').forEach(function (inp) {
                var n = inp.getAttribute('data-adm-col-toggle');
                inp.checked = visible.indexOf(n) !== -1;
            });
            apply();
        }

        for (var ci = 0; ci < toggleable.length; ci++) {
            var col = toggleable[ci];
            var lab = document.createElement('label');
            lab.className = 'flex cursor-pointer items-center gap-2 rounded-md px-1 py-0.5 hover:bg-zinc-900';
            var inp = document.createElement('input');
            inp.type = 'checkbox';
            inp.className = 'rounded border-zinc-600 bg-zinc-950 text-zinc-100';
            inp.setAttribute('data-adm-col-toggle', col.name);
            inp.checked = visible.indexOf(col.name) !== -1;
            inp.addEventListener('change', syncFromChecks);
            lab.appendChild(inp);
            var sp = document.createElement('span');
            sp.className = 'text-zinc-200';
            sp.textContent = col.label;
            lab.appendChild(sp);
            listEl.appendChild(lab);
        }

        apply();

        var toggle = document.getElementById('adm_col_picker_toggle');
        var panel = document.getElementById('adm_col_picker_panel');
        if (!toggle || !panel) return;

        function setPanelOpen(open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            setPanelOpen(!!panel.hidden);
        });

        document.addEventListener(
            'click',
            function (e) {
                if (panel.hidden) return;
                var t = e.target;
                if (panel.contains(t) || toggle.contains(t)) return;
                setPanelOpen(false);
            },
            true,
        );

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || panel.hidden) return;
            setPanelOpen(false);
        });
    }

    function initDeleteConfirm(scope) {
        scope.querySelectorAll('form[data-adm-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var msg = form.getAttribute('data-adm-confirm');
                if (msg && !window.confirm(msg)) e.preventDefault();
            });
        });
    }

    function run() {
        var root = document.getElementById('adm_resource_table');
        var cfgEl = document.getElementById('adm-table-config');
        if (cfgEl && root && cfgEl.getAttribute('data-enabled') === '1') {
            var slug = cfgEl.getAttribute('data-slug') || '';
            var meta = [];
            try {
                meta = JSON.parse(cfgEl.textContent || '[]');
            } catch (e) {
                meta = [];
            }
            if (!Array.isArray(meta)) meta = [];
            var listEl = document.getElementById('adm_col_picker_list');
            if (slug && listEl) initColumnPicker(slug, meta, root, listEl);
        }
        if (root) initDeleteConfirm(root);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
