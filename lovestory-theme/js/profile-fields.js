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

  const forms = document.querySelectorAll('.account-field');

  forms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const input = form.querySelector('.account-field__input');
      const editButton = form.querySelector('[data-profile-edit]');
      const saveButton = form.querySelector('.account-field__button--save');
      const fieldInput = form.querySelector('input[name="dating_profile_field"]');

      if (!input || !editButton || !saveButton || !fieldInput) {
        return;
      }

      const formData = new FormData();

      formData.append('action', 'dating_update_profile_field');
      formData.append('nonce', DatingProfileAjax.nonce);
      formData.append('field', fieldInput.value);
      formData.append('value', input.value);

      saveButton.disabled = true;
      saveButton.textContent = 'Сохраняю...';

      fetch(DatingProfileAjax.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (result) {
          if (!result.success) {
            const message = result.data && result.data.message
              ? result.data.message
              : 'Не удалось сохранить поле.';

            alert(message);
            return;
          }

          input.disabled = true;

          saveButton.hidden = true;
          editButton.hidden = false;

          saveButton.textContent = 'Сохранить';
        })
        .catch(function () {
          alert('Ошибка соединения. Попробуйте ещё раз.');
        })
        .finally(function () {
          saveButton.disabled = false;
          saveButton.textContent = 'Сохранить';
        });
    });
  });
});
