document.addEventListener('DOMContentLoaded', function () {
  const loginModal = document.querySelector('[data-login-modal]');
  const loginOpenButtons = document.querySelectorAll('[data-login-open]');
  const loginCloseButtons = document.querySelectorAll('[data-login-close]');

  if (loginModal) {
    loginOpenButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        loginModal.hidden = false;
        document.body.classList.add('is-login-modal-open');
      });
    });

    loginCloseButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        loginModal.hidden = true;
        document.body.classList.remove('is-login-modal-open');
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !loginModal.hidden) {
        loginModal.hidden = true;
        document.body.classList.remove('is-login-modal-open');
      }
    });
  }
});


/* Закрыть сообщение об удалении профиля на главной */
document.addEventListener('DOMContentLoaded', function () {
  const notice = document.querySelector('[data-site-notice]');
  const closeButton = document.querySelector('[data-site-notice-close]');

  if (!notice || !closeButton) {
    return;
  }

  closeButton.addEventListener('click', function () {
    console.log("Button close clicked");
    notice.remove();

    const url = new URL(window.location.href);
    url.searchParams.delete('profile_deleted');

    window.history.replaceState({}, '', url.toString());
  });
});
