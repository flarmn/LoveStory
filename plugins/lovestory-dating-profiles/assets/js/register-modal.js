console.log("Script running...");
document.addEventListener('DOMContentLoaded', function () {
    const openButtons = document.querySelectorAll('[data-register-open]');
    const modal = document.querySelector('[data-register-modal]');
    const closeButtons = document.querySelectorAll('[data-register-close]');

    if (!modal) {
        return;
    }

    openButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            modal.hidden = false;
            document.body.classList.add('is-register-modal-open');
        });
    });

    closeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            modal.hidden = true;
            document.body.classList.remove('is-register-modal-open');
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) {
            modal.hidden = true;
            document.body.classList.remove('is-register-modal-open');
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
  const editButtons = document.querySelectorAll('[data-profile-edit]');

  editButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      const form = button.closest('.account-field');

      if (!form) {
        return;
      }

      const input = form.querySelector('.account-field__input');
      const saveButton = form.querySelector('.account-field__button--save');

      if (!input || !saveButton) {
        return;
      }

      input.disabled = false;
      input.focus();

      button.hidden = true;
      saveButton.hidden = false;
    });
  });
});


