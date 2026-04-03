(function () {
  const root = document.querySelector('[data-header-search]');
  if (!root) return;

  const input = root.querySelector('[data-search-input]');
  const panel = root.querySelector('[data-search-panel]');
  const list = root.querySelector('[data-search-results]');
  const empty = root.querySelector('[data-search-empty]');
  const endpoint = root.getAttribute('data-endpoint') || '/search/suggest';
  const labelThread = root.getAttribute('data-label-thread') || 'Thread';
  const labelCategory = root.getAttribute('data-label-category') || 'Category';
  const labelUser = root.getAttribute('data-label-user') || 'User';
  const labelNoResults = root.getAttribute('data-label-empty') || 'No results';

  if (!input || !panel || !list || !empty) return;

  let timer = null;
  let activeIndex = -1;
  let links = [];
  let lastQuery = '';

  const esc = (str) =>
    String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');

  const showPanel = () => panel.classList.remove('hidden');
  const hidePanel = () => {
    panel.classList.add('hidden');
    activeIndex = -1;
  };

  const typeLabel = (type) => {
    if (type === 'thread') return labelThread;
    if (type === 'category') return labelCategory;
    if (type === 'user') return labelUser;
    return '';
  };

  const updateActive = () => {
    links.forEach((link, idx) => {
      if (idx === activeIndex) {
        link.classList.add('bg-emerald-50', 'dark:bg-emerald-900/20');
      } else {
        link.classList.remove('bg-emerald-50', 'dark:bg-emerald-900/20');
      }
    });
  };

  const render = (items) => {
    list.innerHTML = '';
    links = [];
    activeIndex = -1;

    if (!Array.isArray(items) || items.length === 0) {
      empty.textContent = labelNoResults;
      empty.classList.remove('hidden');
      return;
    }

    empty.classList.add('hidden');
    const html = items
      .map((item) => {
        const title = esc(item.title || '');
        const meta = esc(item.meta || '');
        const extra = esc(item.extra || '');
        const href = esc(item.url || '#');
        const kind = esc(typeLabel(item.type || ''));
        return (
          '<a href="' +
          href +
          '" class="block rounded-lg px-3 py-2 transition hover:bg-zinc-100 dark:hover:bg-zinc-800/70" data-search-link>' +
          '<p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">' +
          kind +
          '</p>' +
          '<p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">' +
          title +
          '</p>' +
          '<p class="truncate text-xs text-zinc-500 dark:text-zinc-400">' +
          [meta, extra].filter(Boolean).join(' · ') +
          '</p>' +
          '</a>'
        );
      })
      .join('');

    list.innerHTML = html;
    links = Array.from(list.querySelectorAll('[data-search-link]'));
  };

  const fetchResults = async (query) => {
    const url = endpoint + '?q=' + encodeURIComponent(query);
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    const payload = await res.json();
    return Array.isArray(payload.items) ? payload.items : [];
  };

  const searchNow = async () => {
    const query = input.value.trim();
    lastQuery = query;
    if (query.length < 2) {
      hidePanel();
      return;
    }

    showPanel();
    const items = await fetchResults(query);
    if (input.value.trim() !== lastQuery) return;
    render(items);
  };

  input.addEventListener('input', function () {
    if (timer) clearTimeout(timer);
    timer = setTimeout(searchNow, 180);
  });

  input.addEventListener('focus', function () {
    if (input.value.trim().length >= 2) showPanel();
  });

  input.addEventListener('keydown', function (e) {
    if (panel.classList.contains('hidden')) return;
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (links.length === 0) return;
      activeIndex = (activeIndex + 1) % links.length;
      updateActive();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      if (links.length === 0) return;
      activeIndex = activeIndex <= 0 ? links.length - 1 : activeIndex - 1;
      updateActive();
    } else if (e.key === 'Enter') {
      if (activeIndex >= 0 && links[activeIndex]) {
        e.preventDefault();
        links[activeIndex].click();
      }
    } else if (e.key === 'Escape') {
      hidePanel();
    }
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) hidePanel();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === '/' && document.activeElement !== input) {
      const target = e.target;
      const tag = target && target.tagName ? target.tagName.toLowerCase() : '';
      if (tag === 'input' || tag === 'textarea' || tag === 'select' || target?.isContentEditable) {
        return;
      }
      e.preventDefault();
      input.focus();
      input.select();
    }
  });
})();
