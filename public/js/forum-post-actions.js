(function () {
  const root = document.querySelector('#comments');
  if (!root) return;
  const replyForm = document.querySelector('[data-reply-form]');

  const likedClasses = [
    'border-emerald-500',
    'text-emerald-700',
    'dark:border-emerald-500',
    'dark:text-emerald-300',
  ];
  const unlikedClasses = [
    'border-zinc-300',
    'text-zinc-700',
    'dark:border-zinc-700',
    'dark:text-zinc-300',
  ];
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
  const esc = (str) =>
    String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  const avatarColor = (name) => {
    const normalized = String(name || '').trim();
    const first = (normalized.slice(0, 1) || 'U').toUpperCase();
    const code = first.charCodeAt(0) || 0;
    return avatarPalette[code % avatarPalette.length];
  };

  const setButtonState = (button, liked, likesCount) => {
    const label = button.getAttribute('data-label-like') || 'Like';
    button.setAttribute('data-liked', liked ? '1' : '0');
    button.innerHTML =
      '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10v10"></path><path d="M12 10v10"></path><path d="M17 10v10"></path><path d="M5 10h14"></path><path d="M10 10V6a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v4"></path></svg>' +
      '<span>' +
      esc(label) +
      ' (' +
      String(likesCount) +
      ')</span>';

    button.classList.remove(...likedClasses, ...unlikedClasses);
    if (liked) {
      button.classList.add(...likedClasses);
      return;
    }

    button.classList.add(...unlikedClasses);
  };

  root.addEventListener('submit', async function (e) {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.matches('[data-like-form]')) return;

    e.preventDefault();
    const button = form.querySelector('[data-like-button]');
    if (!(button instanceof HTMLButtonElement)) return;
    if (button.disabled) return;

    button.disabled = true;
    button.classList.add('opacity-70', 'cursor-not-allowed');

    const res = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const payload = await res.json().catch(function () {
      return null;
    });

    button.disabled = false;
    button.classList.remove('opacity-70', 'cursor-not-allowed');

    if (!res.ok || !payload || payload.ok !== true) {
      return;
    }

    const liked = Boolean(payload.liked);
    const likesCount = Number(payload.likes_count || 0);
    setButtonState(button, liked, likesCount);
  });

  if (!replyForm) return;

  const replyTextarea = replyForm.querySelector('textarea[name="body"]');
  const replyError = replyForm.querySelector('[data-reply-form-error]');
  const replyStatus = replyForm.querySelector('[data-reply-form-status]');
  const replySubmit = replyForm.querySelector('[data-reply-submit-button]');
  const labelLike = replyForm.getAttribute('data-label-like') || 'Like';
  const labelFlagPost = replyForm.getAttribute('data-label-flag-post') || 'Flag post';
  const labelEditPost = replyForm.getAttribute('data-label-edit-post') || 'Edit';
  const labelDeletePost = replyForm.getAttribute('data-label-delete-post') || 'Delete post';
  const labelModerationBadge = replyForm.getAttribute('data-label-moderation-badge') || 'Moderator';
  const labelReplyRequired = replyForm.getAttribute('data-label-reply-required') || 'Reply is required.';
  const csrfToken = replyForm.getAttribute('data-csrf-token') || '';
  let sendingReply = false;

  const showReplyError = (msg) => {
    if (!replyError) return;
    replyError.textContent = String(msg || '');
    replyError.classList.remove('hidden');
  };
  const hideReplyError = () => {
    if (!replyError) return;
    replyError.textContent = '';
    replyError.classList.add('hidden');
  };
  const showReplyStatus = (msg) => {
    if (!replyStatus) return;
    replyStatus.textContent = String(msg || '');
    replyStatus.classList.remove('hidden');
    setTimeout(function () {
      replyStatus.classList.add('hidden');
    }, 2200);
  };
  const setReplySending = (on) => {
    if (!(replySubmit instanceof HTMLButtonElement)) return;
    replySubmit.disabled = on;
    replySubmit.classList.toggle('opacity-70', on);
    replySubmit.classList.toggle('cursor-not-allowed', on);
  };
  const bumpReplyCount = () => {
    const heading = root.querySelector('h2');
    if (!heading) return;
    const txt = heading.textContent || '';
    const match = txt.match(/\((\d+)\)/);
    if (!match) return;
    const current = parseInt(match[1], 10);
    if (!Number.isFinite(current)) return;
    heading.textContent = txt.replace(/\(\d+\)/, '(' + String(current + 1) + ')');
  };
  const removeEmptyState = () => {
    const empty = root.querySelector('div.rounded-xl.border');
    if (!empty) return;
    if ((empty.textContent || '').trim() !== '') {
      empty.remove();
    }
  };
  const appendPostHtml = (post) => {
    const authorName = esc(post.author_name || 'User');
    const authorRole = String(post.author_role || 'member');
    const primaryBadge = post.author_primary_badge ? String(post.author_primary_badge) : '';
    const primaryBadgeLabel = post.author_primary_badge_label ? String(post.author_primary_badge_label) : '';
    const createdAt = esc(post.created_at || '');
    const profileUrl = esc(post.profile_url || '#');
    const avatar = String(post.author_avatar || '').trim();
    const likeUrl = esc(post.like_url || '#');
    const flagUrl = esc(post.flag_url || '#');
    const editUrl = esc(post.edit_url || '#');
    const deleteUrl = esc(post.delete_url || '#');
    const bodyHtml = String(post.body_html || '');
    const canEdit = Boolean(post.can_edit);
    const canDelete = Boolean(post.can_delete);
    const likeClass = post.liked_by_auth_user
      ? 'border-emerald-500 text-emerald-700 dark:border-emerald-500 dark:text-emerald-300'
      : 'border-zinc-300 text-zinc-700 dark:border-zinc-700 dark:text-zinc-300';
    const avatarHtml = avatar
      ? '<a href="' +
        profileUrl +
        '"><img src="' +
        esc(avatar.startsWith('/') ? avatar : '/' + avatar) +
        '" alt="' +
        authorName +
        '" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" width="36" height="36"></a>'
      : '<a href="' +
        profileUrl +
        '" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white" style="background-color: ' +
        esc(avatarColor(authorName)) +
        '">' +
        esc(String(authorName).slice(0, 1).toUpperCase()) +
        '</a>';
    const badgeHtml = primaryBadge
      ? '<span class="ml-1 rounded bg-sky-100 px-1.5 py-0.5 text-[11px] font-semibold text-sky-800 dark:bg-sky-900/35 dark:text-sky-300">' +
        esc(primaryBadgeLabel || primaryBadge) +
        '</span>'
      : authorRole === 'moderator'
      ? '<span class="ml-1 rounded bg-violet-100 px-1.5 py-0.5 text-[11px] font-semibold text-violet-800 dark:bg-violet-900/35 dark:text-violet-300">' +
        esc(labelModerationBadge) +
        '</span>'
      : '';
    const editHtml = canEdit
      ? '<a href="' +
        editUrl +
        '" class="inline-flex items-center gap-1.5 rounded border border-zinc-300 px-2 py-1 text-zinc-700 hover:border-emerald-500 hover:text-emerald-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-emerald-500 dark:hover:text-emerald-400">' +
        '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="m16.5 3.5 4 4L8 20l-5 1 1-5Z"></path></svg>' +
        esc(labelEditPost) +
        '</a>'
      : '';
    const deleteHtml = canDelete
      ? '<form method="post" action="' +
        deleteUrl +
        '"><input type="hidden" name="_csrf" value="' +
        esc(csrfToken) +
        '"><button type="submit" class="rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50 dark:border-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/20">' +
        esc(labelDeletePost) +
        '</button></form>'
      : '';
    const html =
      '<article class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900/60">' +
      '<div class="flex items-start justify-between gap-3">' +
      '<div class="min-w-0 flex items-start gap-3">' +
      avatarHtml +
      '<div><p class="text-sm font-semibold text-zinc-900 dark:text-white"><a href="' +
      profileUrl +
      '" class="hover:text-emerald-600 dark:hover:text-emerald-400">' +
      authorName +
      '</a>' +
      badgeHtml +
      '</p><p class="text-xs text-zinc-500 dark:text-zinc-500">' +
      createdAt +
      '</p></div></div>' +
      '<div class="flex flex-wrap items-center gap-2 text-xs">' +
      '<form method="post" action="' +
      likeUrl +
      '" data-like-form><input type="hidden" name="_csrf" value="' +
      esc(csrfToken) +
      '"><button type="submit" data-like-button data-liked="0" data-label-like="' +
      esc(labelLike) +
      '" class="inline-flex items-center gap-1.5 rounded border px-2 py-1 ' +
      likeClass +
      '">' +
      '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 10v10"></path><path d="M12 10v10"></path><path d="M17 10v10"></path><path d="M5 10h14"></path><path d="M10 10V6a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v4"></path></svg>' +
      '<span>' +
      esc(labelLike) +
      ' (0)</span></button></form>' +
      '<form method="post" action="' +
      flagUrl +
      '"><input type="hidden" name="_csrf" value="' +
      esc(csrfToken) +
      '"><input type="hidden" name="reason" value="post"><button type="submit" class="inline-flex items-center gap-1.5 rounded border border-zinc-300 px-2 py-1 text-zinc-700 hover:border-amber-500 hover:text-amber-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-amber-500 dark:hover:text-amber-300">' +
      '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3v18"></path><path d="M8 4h11l-2.5 4 2.5 4H8"></path></svg>' +
      esc(labelFlagPost) +
      '</button></form>' +
      editHtml +
      deleteHtml +
      '</div></div><div class="prose prose-zinc mt-3 max-w-none text-sm dark:prose-invert">' +
      bodyHtml +
      '</div></article>';
    root.insertAdjacentHTML('beforeend', html);
  };

  replyForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (sendingReply) return;
    if (!(replyTextarea instanceof HTMLTextAreaElement)) return;

    hideReplyError();
    const body = replyTextarea.value.trim();
    if (body === '') {
      showReplyError(labelReplyRequired);
      return;
    }

    sendingReply = true;
    setReplySending(true);
    const res = await fetch(replyForm.action, {
      method: 'POST',
      body: new FormData(replyForm),
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });
    const payload = await res.json().catch(function () {
      return null;
    });
    sendingReply = false;
    setReplySending(false);

    if (!res.ok || !payload || payload.ok !== true || !payload.post) {
      const err =
        (payload && payload.errors && payload.errors.body) ||
        (payload && payload.message) ||
        'Could not post reply.';
      showReplyError(String(err));
      return;
    }

    removeEmptyState();
    appendPostHtml(payload.post);
    bumpReplyCount();
    replyTextarea.value = '';
    replyTextarea.dispatchEvent(new Event('input', { bubbles: true }));
    showReplyStatus(payload.message || 'Reply posted.');
    const inserted = root.lastElementChild;
    if (inserted instanceof HTMLElement) {
      inserted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  });
})();
