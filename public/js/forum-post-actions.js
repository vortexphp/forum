(function () {
  const root = document.querySelector('#comments');
  if (!root) return;

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

  const setButtonState = (button, liked, likesCount) => {
    const label = button.getAttribute('data-label-like') || 'Like';
    button.setAttribute('data-liked', liked ? '1' : '0');
    button.textContent = label + ' (' + String(likesCount) + ')';

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
})();
