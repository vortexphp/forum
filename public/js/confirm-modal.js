(function () {
  const modal = document.querySelector('[data-confirm-modal]');
  if (!modal) return;

  const messageEl = modal.querySelector('[data-confirm-modal-message]');
  const cancelBtn = modal.querySelector('[data-confirm-modal-cancel]');
  const acceptBtn = modal.querySelector('[data-confirm-modal-accept]');
  if (!messageEl || !cancelBtn || !acceptBtn) return;

  let pendingForm = null;
  let pendingSubmitter = null;

  const closeModal = () => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
    pendingForm = null;
    pendingSubmitter = null;
  };

  const openModal = (form, submitter) => {
    pendingForm = form;
    pendingSubmitter = submitter || null;
    const msg =
      form.getAttribute('data-confirm-message') ||
      'Are you sure you want to continue?';
    messageEl.textContent = msg;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    acceptBtn.focus();
  };

  document.addEventListener(
    'submit',
    function (e) {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      if (!form.hasAttribute('data-confirm-message')) return;
      if (form.dataset.confirmed === '1') {
        form.dataset.confirmed = '';
        return;
      }
      e.preventDefault();
      openModal(form, e.submitter || null);
    },
    true
  );

  cancelBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', function (e) {
    if (e.target === modal) closeModal();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });

  acceptBtn.addEventListener('click', function () {
    if (!(pendingForm instanceof HTMLFormElement)) {
      closeModal();
      return;
    }

    const form = pendingForm;
    const submitter = pendingSubmitter;
    closeModal();
    form.dataset.confirmed = '1';
    if (submitter && typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter);
      return;
    }
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
      return;
    }
    form.submit();
  });
})();
