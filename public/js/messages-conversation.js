(function () {
  const root = document.querySelector('[data-conversation-root]');
  if (!root) return;

  const list = root.querySelector('[data-message-list]');
  const form = root.querySelector('[data-message-form]');
  const textarea = form ? form.querySelector('textarea[name="body"]') : null;
  const errorBox = root.querySelector('[data-message-form-error]');
  const statusBox = root.querySelector('[data-message-form-status]');
  const loadingInitialEl = root.querySelector('[data-loading-initial]');
  const loadingOlderEl = root.querySelector('[data-loading-older]');
  const sendButton = root.querySelector('[data-send-button]');
  const sendLabel = root.querySelector('[data-send-label]');
  const sendLoading = root.querySelector('[data-send-loading]');

  const feedUrl = root.getAttribute('data-feed-url') || '';
  const sendUrl = root.getAttribute('data-send-url') || '';
  const profileUrlBase = root.getAttribute('data-profile-url-base') || '/users/';
  const currentUserId = parseInt(root.getAttribute('data-current-user-id') || '0', 10);
  const youLabel = root.getAttribute('data-you-label') || 'You';
  const agoTemplate = root.getAttribute('data-ago-template') || ':time ago';

  if (!list || !form || !textarea || !feedUrl || !sendUrl || currentUserId <= 0) return;

  const esc = (str) =>
    String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  const avatarPalette = [
    '#0ea5e9',
    '#14b8a6',
    '#22c55e',
    '#84cc16',
    '#eab308',
    '#f97316',
    '#ef4444',
    '#ec4899',
    '#a855f7',
    '#6366f1',
    '#3b82f6',
    '#10b981',
  ];
  const avatarColor = (name) => {
    const normalized = String(name || '').trim();
    const first = (normalized.slice(0, 1) || 'U').toUpperCase();
    const code = first.charCodeAt(0) || 0;
    return avatarPalette[code % avatarPalette.length];
  };

  const ago = (value) => agoTemplate.replace(':time', String(value || '1m'));
  const isNearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 120;
  const feedUrlForPage = (page) => feedUrl + '?page=' + encodeURIComponent(String(page));
  const profileHref = (userId) => profileUrlBase + encodeURIComponent(String(userId));

  let lastSignature = '';
  let sending = false;
  let loadingOlder = false;
  let polling = false;
  let highestLoadedPage = 0;
  let hasMoreOlder = true;
  const messagesById = new Map();

  const render = (items, options) => {
    const opts = options || {};
    const signature = JSON.stringify(
      (items || []).map((x) => [x.id || 0, x.created_at || '', x.body || ''])
    );
    if (signature === lastSignature) return;
    lastSignature = signature;

    const stickBottom = !!opts.forceBottom || (!opts.preserveTop && isNearBottom());
    const beforeHeight = list.scrollHeight;
    const beforeTop = list.scrollTop;
    const html = (items || [])
      .map((message) => {
        const senderId = Number(message.sender_id || 0);
        const own = senderId === currentUserId;
        const senderName = own ? youLabel : message.sender_name || 'User';
        const avatarSeedName = own ? message.sender_name || youLabel : senderName;
        const avatar = message.sender_avatar || '';
        const initial = esc(String(avatarSeedName).slice(0, 1).toUpperCase() || 'U');
        const avatarStyle = ' style="background-color: ' + esc(avatarColor(avatarSeedName)) + '"';
        const profileHrefValue = esc(profileHref(senderId > 0 ? senderId : currentUserId));
        const avatarHtml = avatar
          ? '<a href="' +
            profileHrefValue +
            '" class="shrink-0"><img src="' +
            esc(avatar.startsWith('/') ? avatar : '/'+avatar) +
            '" alt="' +
            esc(senderName) +
            '" class="h-10 w-10 rounded-full object-cover ring-1 ring-zinc-200 transition hover:ring-emerald-400 dark:ring-zinc-700 dark:hover:ring-emerald-500" width="40" height="40"></a>'
          : '<a href="' +
            profileHrefValue +
            '" class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-semibold text-white"' +
            avatarStyle +
            '>' +
            initial +
            '</a>';
        const bubbleClass = own
          ? 'border-emerald-300 bg-emerald-50/80 dark:border-emerald-900/40 dark:bg-emerald-900/15'
          : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900/60';
        const nameClass = own
          ? 'text-emerald-800 dark:text-emerald-300'
          : 'text-zinc-900 dark:text-white';
        return (
          '<div class="flex ' +
          (own ? 'justify-end' : 'justify-start') +
          '">' +
          '<article class="w-full max-w-[95%] sm:max-w-[86%]">' +
          '<div class="flex items-end gap-2 ' +
          (own ? 'flex-row-reverse' : '') +
          '">' +
          avatarHtml +
          '<div class="min-w-0 flex-1 rounded-2xl border px-5 py-4 ' +
          bubbleClass +
          '">' +
          '<div class="flex items-center justify-between gap-3">' +
          '<p class="inline-flex items-center gap-1.5 text-base font-semibold ' +
          nameClass +
          '">' +
          '<svg class="h-4 w-4 ' +
          (own ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400 dark:text-zinc-500') +
          '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>' +
          esc(senderName) +
          '</p>' +
          '<span class="text-sm text-zinc-500 dark:text-zinc-500">' +
          esc(ago(message.created_ago || '1m')) +
          '</span>' +
          '</div>' +
          '<p class="mt-2 whitespace-pre-wrap break-words text-base text-zinc-700 dark:text-zinc-300">' +
          esc(message.body || '') +
          '</p>' +
          '</div></div></article></div>'
        );
      })
      .join('');

    list.innerHTML = html;
    if (opts.preserveTop) {
      const delta = list.scrollHeight - beforeHeight;
      list.scrollTop = beforeTop + delta;
    } else if (stickBottom) {
      list.scrollTop = list.scrollHeight;
    }
  };

  const mergeMessages = (items) => {
    (items || []).forEach((item) => {
      const id = Number(item.id || 0);
      if (id <= 0) return;
      messagesById.set(id, item);
    });
    return Array.from(messagesById.values()).sort(
      (a, b) => Number(a.id || 0) - Number(b.id || 0)
    );
  };

  const fetchPage = async (page) => {
    const res = await fetch(feedUrlForPage(page), { headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const payload = await res.json();
    if (!payload || !Array.isArray(payload.items)) return;
    return payload;
  };

  const loadOlder = async () => {
    if (loadingOlder || !hasMoreOlder) return;
    const nextPage = highestLoadedPage + 1;
    loadingOlder = true;
    setOlderLoading(true);
    const payload = await fetchPage(nextPage);
    loadingOlder = false;
    setOlderLoading(false);
    if (!payload) return;

    highestLoadedPage = nextPage;
    hasMoreOlder = Boolean(payload.has_more);
    const merged = mergeMessages(payload.items);
    render(merged, { preserveTop: true });
  };

  const loadLatest = async (forceBottom) => {
    if (polling) return;
    if (highestLoadedPage === 0) setInitialLoading(true);
    polling = true;
    const payload = await fetchPage(1);
    polling = false;
    if (highestLoadedPage === 0) setInitialLoading(false);
    if (!payload) return;

    if (highestLoadedPage === 0) {
      highestLoadedPage = 1;
      hasMoreOlder = Boolean(payload.has_more);
    }
    const merged = mergeMessages(payload.items);
    render(merged, { forceBottom: !!forceBottom });
  };

  const showError = (msg) => {
    if (!errorBox) return;
    errorBox.textContent = msg;
    errorBox.classList.remove('hidden');
  };
  const hideError = () => {
    if (!errorBox) return;
    errorBox.textContent = '';
    errorBox.classList.add('hidden');
  };
  const showStatus = (msg) => {
    if (!statusBox) return;
    statusBox.textContent = msg;
    statusBox.classList.remove('hidden');
    setTimeout(() => statusBox.classList.add('hidden'), 2500);
  };

  const setInitialLoading = (on) => {
    if (!loadingInitialEl) return;
    loadingInitialEl.classList.toggle('hidden', !on);
  };

  const setOlderLoading = (on) => {
    if (!loadingOlderEl) return;
    loadingOlderEl.classList.toggle('hidden', !on);
  };

  const setSendingState = (on) => {
    if (sendButton) {
      sendButton.disabled = on;
      sendButton.classList.toggle('opacity-70', on);
      sendButton.classList.toggle('cursor-not-allowed', on);
    }
    if (sendLabel) sendLabel.classList.toggle('hidden', on);
    if (sendLoading) {
      sendLoading.classList.toggle('hidden', !on);
      sendLoading.classList.toggle('inline-flex', on);
    }
  };

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (sending) return;
    hideError();

    const body = textarea.value.trim();
    if (body === '') return;

    sending = true;
    setSendingState(true);
    const fd = new FormData(form);
    const res = await fetch(sendUrl, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json' },
    });
    const payload = await res.json().catch(() => ({}));
    sending = false;
    setSendingState(false);

    if (!res.ok || !payload.ok) {
      const err =
        (payload.errors && payload.errors.body) ||
        payload.message ||
        'Could not send message.';
      showError(String(err));
      return;
    }

    textarea.value = '';
    showStatus(payload.message || 'Sent');
    await loadLatest(true);
    list.scrollTop = list.scrollHeight;
  });

  textarea.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    if (e.shiftKey) return;
    if (e.isComposing) return;
    e.preventDefault();
    form.requestSubmit();
  });

  list.addEventListener('scroll', function () {
    if (list.scrollTop > 60) return;
    loadOlder();
  });

  setInitialLoading(true);
  loadLatest(true);
  setInterval(function () {
    loadLatest(false);
  }, 5000);
})();
