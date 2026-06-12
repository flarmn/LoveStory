document.addEventListener('DOMContentLoaded', function () {
  const forms = document.querySelectorAll('[data-profile-field-form]');

  if (!forms.length) {
    return;
  }

  forms.forEach(function (form) {
    const input = form.querySelector('.account-field__input');
    const editButton = form.querySelector('[data-profile-edit]');
    const saveButton = form.querySelector('.account-field__button--save');
    const fieldInput = form.querySelector('input[name="dating_profile_field"]');

    if (!input || !editButton || !saveButton || !fieldInput) {
      return;
    }

    editButton.addEventListener('click', function () {
      input.disabled = false;
      input.focus();

      editButton.hidden = true;
      saveButton.hidden = false;
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const formData = new FormData();

      formData.append('action', 'dating_update_profile_field_ajax');
      formData.append('nonce', LoveStoryProfileAjax.fieldNonce);
      formData.append('field', fieldInput.value);
      formData.append('value', input.value);

      saveButton.disabled = true;
      saveButton.textContent = 'Сохраняю...';

      fetch(LoveStoryProfileAjax.ajaxUrl, {
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

          input.value = result.data.value;
          input.disabled = true;

          saveButton.hidden = true;
          editButton.hidden = false;
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

/*
   * AJAX-загрузка / удаление фото профиля
   * Загрузка фото идёт через XMLHttpRequest,
   * чтобы можно было отслеживать реальный прогресс отправки файла.
   */
   
   document.addEventListener('DOMContentLoaded', function () {
  const photoForm = document.querySelector('[data-profile-photo-form]');


  if (!photoForm) {
    return;
  }

  const photoInput = photoForm.querySelector('input[name="profile_photo"]');
  const photoPreview = document.querySelector('[data-profile-photo-preview]');
  const uploadLabel = photoForm.querySelector('[data-profile-photo-upload-label]');
  const deleteButton = document.querySelector('[data-profile-photo-delete]');
  const message = document.querySelector('[data-profile-photo-message]');

  const progress = photoForm.querySelector('[data-profile-photo-progress]');
  const progressBar = photoForm.querySelector('[data-profile-photo-progress-bar]');
  const progressText = photoForm.querySelector('[data-profile-photo-progress-text]');

  const showMessage = function (text, isError = false) {
    if (!message) {
      return;
    }

    message.textContent = text;
    message.classList.toggle('account-photo-form__message--error', isError);
    message.classList.toggle('account-photo-form__message--success', !isError);
  };

  const setLoading = function (element, isLoading) {
    if (!element) {
      return;
    }

    element.classList.toggle('is-loading', isLoading);
    element.setAttribute('aria-busy', isLoading ? 'true' : 'false');

    if (element.tagName === 'BUTTON') {
      element.disabled = isLoading;
    }

    if (photoInput) {
      photoInput.disabled = isLoading;
    }
  };

  const resetProgress = function () {
    if (progressBar) {
      progressBar.style.width = '0%';
    }

    if (progressText) {
      progressText.textContent = '0%';
    }

    if (progress) {
      progress.hidden = true;
    }
  };

  const formatKb = function (bytes) {
    return Math.round(bytes / 1024);
  };

  const updateProgress = function (percent, loadedBytes, totalBytes) {
    const safePercent = Math.max(0, Math.min(100, percent));

    if (progress) {
      progress.hidden = false;
    }

    if (progressBar) {
      progressBar.style.width = safePercent + '%';
    }

    if (progressText) {
      const loadedKb = formatKb(loadedBytes);
      const totalKb = formatKb(totalBytes);

      progressText.textContent = safePercent + '% · ' + loadedKb + ' / ' + totalKb + ' КБ';
    }
  };

  if (photoInput) {
    photoInput.addEventListener('change', function () {
      const file = photoInput.files && photoInput.files[0];

      if (!file) {
        return;
      }

      const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

      if (!allowedTypes.includes(file.type)) {
        showMessage('Можно загрузить только JPG, PNG или WEBP.', true);
        photoInput.value = '';
        return;
      }

      const maxSize = 5 * 1024 * 1024;

      if (file.size > maxSize) {
        showMessage('Размер файла не должен превышать 5 МБ.', true);
        photoInput.value = '';
        return;
      }

      const formData = new FormData();

      formData.append('action', 'dating_upload_profile_photo');
      formData.append('nonce', LoveStoryProfileAjax.photoNonce);
      formData.append('profile_photo', file);

      const xhr = new XMLHttpRequest();

      xhr.open('POST', LoveStoryProfileAjax.ajaxUrl, true);
      xhr.withCredentials = true;

      setLoading(uploadLabel, true);
      resetProgress();
      showMessage('Загружаю фото...');

      xhr.upload.addEventListener('progress', function (event) {
        if (!event.lengthComputable) {
          return;
        }

        const percent = Math.round((event.loaded / event.total) * 100);

        updateProgress(percent, event.loaded, event.total);
      });

      xhr.addEventListener('load', function () {
        let result = null;

        try {
          result = JSON.parse(xhr.responseText);
        } catch (error) {
          showMessage('Сервер вернул некорректный ответ.', true);
          return;
        }

        if (!result.success) {
          const errorMessage = result.data && result.data.message
            ? result.data.message
            : 'Не удалось загрузить фото.';

          showMessage(errorMessage, true);
          return;
        }

        if (photoPreview && result.data.photoUrl) {
          photoPreview.src = result.data.photoUrl;
        }

        updateProgress(100, file.size, file.size);
        showMessage(result.data.message || 'Фото обновлено.');
      });

      xhr.addEventListener('error', function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      });

      xhr.addEventListener('loadend', function () {
        setLoading(uploadLabel, false);
        photoInput.value = '';

        setTimeout(function () {
          resetProgress();
        }, 1200);
      });

      xhr.send(formData);
    });
  }

  if (deleteButton) {
    deleteButton.addEventListener('click', function () {
      const confirmed = window.confirm('Удалить фото профиля?');

      if (!confirmed) {
        return;
      }

      const formData = new FormData();

      formData.append('action', 'dating_delete_profile_photo');
      formData.append('nonce', LoveStoryProfileAjax.photoNonce);

      setLoading(deleteButton, true);
      showMessage('Удаляю фото...');

      fetch(LoveStoryProfileAjax.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (result) {
          if (!result.success) {
            const errorMessage = result.data && result.data.message
              ? result.data.message
              : 'Не удалось удалить фото.';

            showMessage(errorMessage, true);
            return;
          }

          if (photoPreview && result.data.photoUrl) {
            photoPreview.src = result.data.photoUrl;
          }

          resetProgress();
          showMessage(result.data.message || 'Фото удалено.');
        })
        .catch(function () {
          showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
        })
        .finally(function () {
          setLoading(deleteButton, false);
        });
    });
  }
 
}); 

/* USER SOFT DELETE */
document.addEventListener('DOMContentLoaded', function () {
  const deleteProfileForm = document.querySelector('[data-profile-delete-form]');

  if (!deleteProfileForm) {
    return;
  }

  deleteProfileForm.addEventListener('submit', function (event) {
    const confirmed = window.confirm(
      'Вы уверены, что хотите удалить профиль? Ваш профиль и анкета будут скрыты с сайта.'
    );

    if (!confirmed) {
      event.preventDefault();
    }
  });
});
