(function () {
  const root = document.querySelector('[data-conversation-root]');
  if (!root) return;

  const list = root.querySelector('[data-message-list]');
  const form = root.querySelector('[data-message-form]');
  const textarea = form ? form.querySelector('textarea[name="body"]') : null;
  const errorBox = root.querySelector('[data-message-form-error]');
  const statusBox = root.querySelector('[data-message-form-status]');

  const feedUrl = root.getAttribute('data-feed-url') || '';
  const sendUrl = root.getAttribute('data-send-url') || '';
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

  const ago = (value) => agoTemplate.replace(':time', String(value || '1m'));
  const isNearBottom = () => list.scrollHeight - list.scrollTop - list.clientHeight < 120;

  let lastSignature = '';
  let sending = false;

  const render = (items) => {
    const signature = JSON.stringify(
      (items || []).map((x) => [x.id || 0, x.created_at || '', x.body || ''])
    );
    if (signature === lastSignature) return;
    lastSignature = signature;

    const stickBottom = isNearBottom();
    const html = (items || [])
      .map((message) => {
        const own = Number(message.sender_id || 0) === currentUserId;
        const senderName = own ? youLabel : message.sender_name || 'User';
        const avatar = message.sender_avatar || '';
        const initial = esc(String(senderName).slice(0, 1).toUpperCase() || 'U');
        const avatarHtml = avatar
          ? '<img src="' +
            esc(avatar.startsWith('/') ? avatar : '/'+avatar) +
            '" alt="' +
            esc(senderName) +
            '" class="h-8 w-8 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" width="32" height="32">'
          : '<span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 text-[11px] font-semibold text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">' +
            initial +
            '</span>';
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
          '<article class="w-full max-w-[90%] sm:max-w-[78%]">' +
          '<div class="flex items-end gap-2 ' +
          (own ? 'flex-row-reverse' : '') +
          '">' +
          avatarHtml +
          '<div class="min-w-0 flex-1 rounded-2xl border px-4 py-3 ' +
          bubbleClass +
          '">' +
          '<div class="flex items-center justify-between gap-3">' +
          '<p class="text-sm font-semibold ' +
          nameClass +
          '">' +
          esc(senderName) +
          '</p>' +
          '<span class="text-xs text-zinc-500 dark:text-zinc-500">' +
          esc(ago(message.created_ago || '1m')) +
          '</span>' +
          '</div>' +
          '<p class="mt-2 whitespace-pre-wrap break-words text-sm text-zinc-700 dark:text-zinc-300">' +
          esc(message.body || '') +
          '</p>' +
          '</div></div></article></div>'
        );
      })
      .join('');

    list.innerHTML = html;
    if (stickBottom) {
      list.scrollTop = list.scrollHeight;
    }
  };

  const loadFeed = async () => {
    const res = await fetch(feedUrl, { headers: { Accept: 'application/json' } });
    if (!res.ok) return;
    const payload = await res.json();
    if (!payload || !Array.isArray(payload.items)) return;
    render(payload.items);
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

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (sending) return;
    hideError();

    const body = textarea.value.trim();
    if (body === '') return;

    sending = true;
    const fd = new FormData(form);
    const res = await fetch(sendUrl, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json' },
    });
    const payload = await res.json().catch(() => ({}));
    sending = false;

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
    await loadFeed();
    list.scrollTop = list.scrollHeight;
  });

  textarea.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    if (e.shiftKey) return;
    if (e.isComposing) return;
    e.preventDefault();
    form.requestSubmit();
  });

  loadFeed();
  setInterval(loadFeed, 5000);
})();
