document.addEventListener('DOMContentLoaded', function () {
  const openButton = document.querySelector('[data-editor-main-open]');
  const form = document.querySelector('[data-editor-main-form]');
  const cancelButton = document.querySelector('[data-editor-main-cancel]');
  const message = document.querySelector('[data-editor-main-message]');

  const nameAgeNode = document.querySelector('[data-editor-main-name-age]');
  const locationNode = document.querySelector('[data-editor-main-location]');
  const goalNode = document.querySelector('[data-editor-main-goal]');
  const introNode = document.querySelector('[data-editor-main-intro]');

  const introTextarea = form ? form.querySelector('textarea[name="short_intro"]') : null;
  const introCounter = document.querySelector('[data-main-intro-counter]');

  const showMessage = function (text, isError = false) {
    if (!message) {
      return;
    }

    message.textContent = text;
    message.classList.toggle('profile-editor-main-form__message--error', isError);
  };

  const setFormVisible = function (isVisible) {
    if (!form) {
      return;
    }

    form.hidden = !isVisible;

    if (openButton) {
      openButton.hidden = isVisible;
    }
  };

  const updateIntroCounter = function () {
    if (!introTextarea || !introCounter) {
      return;
    }

    introCounter.textContent = introTextarea.value.length + ' / 160';
  };

  if (introTextarea) {
    introTextarea.addEventListener('input', updateIntroCounter);
  }

  if (openButton) {
    openButton.addEventListener('click', function () {
      setFormVisible(true);
      showMessage('');
    });
  }

  if (cancelButton) {
    cancelButton.addEventListener('click', function () {
      setFormVisible(false);
      showMessage('');
    });
  }

  if (!form) {
    return;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    formData.append('action', 'dating_update_public_profile_main');
    formData.append('nonce', LoveStoryEditProfile.mainNonce);

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Сохраняю...';
    }

    showMessage('Сохраняю...');

    fetch(LoveStoryEditProfile.ajaxUrl, {
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
            : 'Не удалось сохранить данные.';

          showMessage(errorMessage, true);
          return;
        }

        const data = result.data.data;

        if (nameAgeNode) {
          nameAgeNode.textContent = data.title;
        }

        if (locationNode) {
          locationNode.innerHTML = '<span class="profile-editor__location-icon" aria-hidden="true">⌖</span> ' + data.location;
        }

        if (goalNode) {
          goalNode.innerHTML = '<span class="profile-editor__goal-icon" aria-hidden="true">♡</span> ' + data.relationship_goal_label;
        }

        if (introNode) {
          introNode.textContent = data.short_intro || 'Добавьте короткую фразу о себе.';
        }

        showMessage(result.data.message || 'Сохранено.');
        setFormVisible(false);
      })
      .catch(function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      })
      .finally(function () {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Сохранить';
        }
      });
  });
});

/* Секция "О себе" и "Кого ищу"  */
document.addEventListener('DOMContentLoaded', function () {
  const textSections = document.querySelectorAll('[data-editor-text-section]');

  if (!textSections.length) {
    return;
  }

  textSections.forEach(function (section) {
    const sectionName = section.getAttribute('data-editor-text-section');
    const openButton = section.querySelector('[data-editor-text-open]');
    const form = section.querySelector('[data-editor-text-form]');
    const cancelButton = section.querySelector('[data-editor-text-cancel]');
    const textarea = section.querySelector('textarea[name="text"]');
    const view = section.querySelector('[data-editor-text-view]');
    const message = section.querySelector('[data-editor-text-message]');
    const counter = section.querySelector('[data-editor-text-counter]');
    const staticCounter = section.querySelector('[data-editor-text-static-counter]');

    if (!sectionName || !openButton || !form || !textarea || !view) {
      return;
    }

    const fallbackTexts = {
      about_me: 'Расскажите немного о себе.',
      looking_for: 'Опишите, какого человека вы хотите встретить.',
    };

    const showMessage = function (text, isError = false) {
      if (!message) {
        return;
      }

      message.textContent = text;
      message.classList.toggle('profile-editor-text-form__message--error', isError);
    };

    const updateCounter = function () {
      const length = textarea.value.length;

      if (counter) {
        counter.textContent = length + ' / 512';
      }
    };

    const setEditing = function (isEditing) {
      form.hidden = !isEditing;
      openButton.hidden = isEditing;

      if (isEditing) {
        textarea.focus();
      }
    };

    textarea.addEventListener('input', updateCounter);

    openButton.addEventListener('click', function () {
      showMessage('');
      updateCounter();
      setEditing(true);
    });

    if (cancelButton) {
      cancelButton.addEventListener('click', function () {
        showMessage('');
        setEditing(false);
      });
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const submitButton = form.querySelector('button[type="submit"]');
      const formData = new FormData();

      formData.append('action', 'dating_update_public_profile_text_section');
      formData.append('nonce', LoveStoryEditProfile.textSectionNonce);
      formData.append('section', sectionName);
      formData.append('text', textarea.value);

      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Сохраняю...';
      }

      showMessage('Сохраняю...');

      fetch(LoveStoryEditProfile.ajaxUrl, {
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
              : 'Не удалось сохранить раздел.';

            showMessage(errorMessage, true);
            return;
          }

          const data = result.data.data;
          const newText = data.text || fallbackTexts[sectionName] || '';

          view.textContent = newText;

          if (staticCounter) {
            staticCounter.textContent = data.length + ' / 512';
          }

          if (counter) {
            counter.textContent = data.length + ' / 512';
          }

          showMessage(result.data.message || 'Сохранено.');
          setEditing(false);
        })
        .catch(function () {
          showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
        })
        .finally(function () {
          if (submitButton) {
            submitButton.disabled = false;
            submitButton.textContent = 'Сохранить';
          }
        });
    });
  });
});

/* Интересы */

document.addEventListener('DOMContentLoaded', function () {
  const section = document.querySelector('[data-editor-interests-section]');

  if (!section) {
    return;
  }

  const openButtons = section.querySelectorAll('[data-editor-interests-open], [data-editor-interests-open-secondary]');
  const form = section.querySelector('[data-editor-interests-form]');
  const cancelButton = section.querySelector('[data-editor-interests-cancel]');
  const view = section.querySelector('[data-editor-interests-view]');
  const message = section.querySelector('[data-editor-interests-message]');
  const customInput = section.querySelector('[data-editor-custom-interest-input]');
  const addCustomButton = section.querySelector('[data-editor-custom-interest-add]');
  const secondaryAddButton = section.querySelector('[data-editor-interests-open-secondary]');

  const maxInterests = 12;

  if (!form || !view) {
    return;
  }

  const showMessage = function (text, isError = false) {
    if (!message) {
      return;
    }

    message.textContent = text;
    message.classList.toggle('profile-editor-interests-form__message--error', isError);
  };

  const setEditing = function (isEditing) {
    form.hidden = !isEditing;

    openButtons.forEach(function (button) {
      button.hidden = isEditing;
    });

    if (secondaryAddButton) {
      secondaryAddButton.hidden = isEditing;
    }

    if (isEditing && customInput) {
      customInput.focus();
    }
  };

  const getSelectedInterests = function () {
    const checkedInputs = form.querySelectorAll('input[name="interests[]"]:checked');

    return Array.from(checkedInputs).map(function (input) {
      return input.value.trim();
    }).filter(Boolean);
  };

  const renderView = function (interests) {
    if (!interests.length) {
      view.innerHTML = '<p class="profile-editor__hint">Интересы пока не добавлены.</p>';
      return;
    }

    const list = document.createElement('ul');
    list.className = 'profile-editor__tag-list';

    interests.forEach(function (interest) {
      const item = document.createElement('li');
      item.className = 'profile-editor__tag';
      item.textContent = interest;
      list.appendChild(item);
    });

    view.innerHTML = '';
    view.appendChild(list);
  };

  const createInterestChip = function (interest) {
    const label = document.createElement('label');
    label.className = 'profile-editor-interests-form__chip';

    const checkbox = document.createElement('input');
    checkbox.className = 'profile-editor-interests-form__checkbox';
    checkbox.type = 'checkbox';
    checkbox.name = 'interests[]';
    checkbox.value = interest;
    checkbox.checked = true;

    const text = document.createElement('span');
    text.className = 'profile-editor-interests-form__chip-text';
    text.textContent = interest;

    label.appendChild(checkbox);
    label.appendChild(text);

    return label;
  };

  openButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      showMessage('');
      setEditing(true);
    });
  });

  if (cancelButton) {
    cancelButton.addEventListener('click', function () {
      showMessage('');
      setEditing(false);
    });
  }

  if (addCustomButton && customInput) {
    addCustomButton.addEventListener('click', function () {
      const interest = customInput.value.trim();

      if (!interest) {
        showMessage('Введите название интереса.', true);
        return;
      }

      if (interest.length > 30) {
        showMessage('Интерес не должен быть длиннее 30 символов.', true);
        return;
      }

      const currentInterests = getSelectedInterests();

      if (currentInterests.length >= maxInterests) {
        showMessage('Можно выбрать не больше 12 интересов.', true);
        return;
      }

      const exists = Array.from(form.querySelectorAll('input[name="interests[]"]')).some(function (input) {
        return input.value.toLowerCase() === interest.toLowerCase();
      });

      if (exists) {
        showMessage('Такой интерес уже есть в списке.', true);
        return;
      }

      const grid = form.querySelector('.profile-editor-interests-form__grid');

      if (!grid) {
        return;
      }

      grid.appendChild(createInterestChip(interest));
      customInput.value = '';
      showMessage('');
    });
  }

  form.addEventListener('change', function (event) {
    if (!event.target.matches('input[name="interests[]"]')) {
      return;
    }

    const selected = getSelectedInterests();

    if (selected.length > maxInterests) {
      event.target.checked = false;
      showMessage('Можно выбрать не больше 12 интересов.', true);
    } else {
      showMessage('');
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    const submitButton = form.querySelector('button[type="submit"]');
    const selectedInterests = getSelectedInterests();
    const formData = new FormData();

    formData.append('action', 'dating_update_public_profile_interests');
    formData.append('nonce', LoveStoryEditProfile.interestsNonce);

    selectedInterests.forEach(function (interest) {
      formData.append('interests[]', interest);
    });

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'Сохраняю...';
    }

    showMessage('Сохраняю...');

    fetch(LoveStoryEditProfile.ajaxUrl, {
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
            : 'Не удалось сохранить интересы.';

          showMessage(errorMessage, true);
          return;
        }

        const interests = result.data.data.interests || [];

        renderView(interests);
        showMessage(result.data.message || 'Интересы сохранены.');
        setEditing(false);
      })
      .catch(function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      })
      .finally(function () {
        if (submitButton) {
          submitButton.disabled = false;
          submitButton.textContent = 'Сохранить';
        }
      });
  });
});

/* Загрузка главного фото в редакторе анкет */

document.addEventListener('DOMContentLoaded', function () {
  const openButton = document.querySelector('[data-editor-main-photo-open]');
  const fileInput = document.querySelector('[data-editor-main-photo-input]');
  const preview = document.querySelector('[data-editor-main-photo-preview]');
  const message = document.querySelector('[data-editor-main-photo-message]');
  const photoBox = document.querySelector('.profile-editor__main-photo');

  if (!openButton || !fileInput || !preview) {
    return;
  }

  const showMessage = function (text, isError = false) {
    if (!message) {
      return;
    }

    message.textContent = text;
    message.classList.toggle('profile-editor__photo-message--error', isError);
  };

  const setLoading = function (isLoading) {
    openButton.classList.toggle('is-loading', isLoading);
    openButton.disabled = isLoading;

    if (photoBox) {
      photoBox.classList.toggle('is-loading', isLoading);
    }

    fileInput.disabled = isLoading;
  };

  openButton.addEventListener('click', function () {
    fileInput.click();
  });

  fileInput.addEventListener('change', function () {
    const file = fileInput.files && fileInput.files[0];

    if (!file) {
      return;
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!allowedTypes.includes(file.type)) {
      showMessage('Можно загрузить только JPG, PNG или WEBP.', true);
      fileInput.value = '';
      return;
    }

    const maxSize = 5 * 1024 * 1024;

    if (file.size > maxSize) {
      showMessage('Размер файла не должен превышать 5 МБ.', true);
      fileInput.value = '';
      return;
    }

    const formData = new FormData();

    formData.append('action', 'dating_upload_profile_photo');
    formData.append('nonce', LoveStoryEditProfile.mainPhotoNonce);
    formData.append('profile_photo', file);

    setLoading(true);
    showMessage('Загружаю главное фото...');

    fetch(LoveStoryEditProfile.ajaxUrl, {
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
            : 'Не удалось загрузить фото.';

          showMessage(errorMessage, true);
          return;
        }

        if (result.data.photoUrl) {
          preview.src = result.data.photoUrl;
        }

        showMessage(result.data.message || 'Главное фото обновлено.');
      })
      .catch(function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      })
      .finally(function () {
        setLoading(false);
        fileInput.value = '';
      });
  });
});

/* Галлереия фото */
document.addEventListener('DOMContentLoaded', function () {
  const gallery = document.querySelector('.profile-editor__gallery');
  const message = document.querySelector('[data-gallery-message]');

  if (!gallery) {
    return;
  }

  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
  const maxSize = 5 * 1024 * 1024;

  const showMessage = function (text, isError = false) {
    if (!message) {
      return;
    }

    message.textContent = text;
    message.classList.toggle('profile-editor-gallery-message--error', isError);
  };

  const setLoading = function (slot, isLoading) {
    if (!slot) {
      return;
    }

    slot.classList.toggle('is-loading', isLoading);

    const buttons = slot.querySelectorAll('button');
    const inputs = slot.querySelectorAll('input');

    buttons.forEach(function (button) {
      button.disabled = isLoading;
    });

    inputs.forEach(function (input) {
      input.disabled = isLoading;
    });
  };

  const validateFile = function (file) {
    if (!allowedTypes.includes(file.type)) {
      return 'Можно загрузить только JPG, PNG или WEBP.';
    }

    if (file.size > maxSize) {
      return 'Размер файла не должен превышать 5 МБ.';
    }

    return '';
  };

  const renderFilledSlot = function (slot, photoUrl, slotIndex, attachmentId) {
  slot.classList.remove('profile-editor__gallery-slot--empty');
  slot.classList.add('profile-editor__gallery-slot--filled');
  slot.setAttribute('data-slot-index', slotIndex);

  if (attachmentId) {
    slot.setAttribute('data-attachment-id', attachmentId);
  }

  slot.innerHTML =
    '<input class="profile-editor__gallery-input" type="file" accept="image/jpeg,image/png,image/webp" data-gallery-input hidden>' +
    '<button class="profile-editor__drag-handle" type="button" aria-label="Переместить фото">⋮⋮</button>' +
    '<img class="profile-editor__gallery-image" src="' + photoUrl + '" alt="Дополнительное фото ' + (Number(slotIndex) + 1) + '" data-gallery-image>' +
    '<div class="profile-editor__slot-actions">' +
      '<button class="profile-editor__slot-button" type="button" data-gallery-replace>Заменить</button>' +
      '<button class="profile-editor__slot-button profile-editor__slot-button--danger" type="button" data-gallery-delete>Удалить</button>' +
    '</div>';
};

  const renderEmptySlot = function (slot, slotIndex) {
  slot.classList.remove('profile-editor__gallery-slot--filled');
  slot.classList.add('profile-editor__gallery-slot--empty');
  slot.setAttribute('data-slot-index', slotIndex);
  slot.removeAttribute('data-attachment-id');

  slot.innerHTML =
    '<input class="profile-editor__gallery-input" type="file" accept="image/jpeg,image/png,image/webp" data-gallery-input hidden>' +
    '<button class="profile-editor__upload-slot" type="button" data-gallery-upload>' +
      '<span class="profile-editor__upload-icon" aria-hidden="true">⇧</span>' +
      '<span>Загрузить фото</span>' +
    '</button>';
};

  const uploadFileToSlot = function (slot, file) {
    const validationError = validateFile(file);

    if (validationError) {
      showMessage(validationError, true);
      return;
    }

    const slotIndex = slot.getAttribute('data-slot-index');
    const formData = new FormData();

    formData.append('action', 'dating_upload_public_profile_gallery_photo');
    formData.append('nonce', LoveStoryEditProfile.galleryNonce);
    formData.append('slot_index', slotIndex);
    formData.append('gallery_photo', file);

    setLoading(slot, true);
    showMessage('Загружаю фото...');

    fetch(LoveStoryEditProfile.ajaxUrl, {
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
            : 'Не удалось загрузить фото.';

          showMessage(errorMessage, true);
          return;
        }

        renderFilledSlot(
  slot,
  result.data.photoUrl,
  result.data.slotIndex,
  result.data.attachmentId
);
        showMessage(result.data.message || 'Фото сохранено.');
      })
      .catch(function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      })
      .finally(function () {
        setLoading(slot, false);

        const input = slot.querySelector('[data-gallery-input]');

        if (input) {
          input.value = '';
        }
      });
  };

  gallery.addEventListener('click', function (event) {
    const uploadButton = event.target.closest('[data-gallery-upload]');
    const replaceButton = event.target.closest('[data-gallery-replace]');
    const deleteButton = event.target.closest('[data-gallery-delete]');

    if (uploadButton || replaceButton) {
      const slot = event.target.closest('[data-gallery-slot]');

      if (!slot) {
        return;
      }

      const input = slot.querySelector('[data-gallery-input]');

      if (input) {
        input.click();
      }

      return;
    }

    if (deleteButton) {
      const slot = event.target.closest('[data-gallery-slot]');

      if (!slot) {
        return;
      }

      const confirmed = window.confirm('Удалить это фото из галереи?');

      if (!confirmed) {
        return;
      }

      const slotIndex = slot.getAttribute('data-slot-index');
      const formData = new FormData();

      formData.append('action', 'dating_delete_public_profile_gallery_photo');
      formData.append('nonce', LoveStoryEditProfile.galleryNonce);
      formData.append('slot_index', slotIndex);

      setLoading(slot, true);
      showMessage('Удаляю фото...');

      fetch(LoveStoryEditProfile.ajaxUrl, {
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

          renderEmptySlot(slot, result.data.slotIndex);
          showMessage(result.data.message || 'Фото удалено.');
        })
        .catch(function () {
          showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
        })
        .finally(function () {
          setLoading(slot, false);
        });
    }
  });

  gallery.addEventListener('change', function (event) {
    const input = event.target.closest('[data-gallery-input]');

    if (!input) {
      return;
    }

    const slot = input.closest('[data-gallery-slot]');
    const file = input.files && input.files[0];

    if (!slot || !file) {
      return;
    }

    uploadFileToSlot(slot, file);
  });
  
  
  /* Перетаскивание -  Drag&Drop*/
  const orderButton = gallery.querySelector('[data-gallery-order-open]');
const saveOrderButton = gallery.querySelector('.profile-editor__save-order');
const cancelOrderButton = gallery.querySelector('.profile-editor__cancel-order');
const galleryGrid = gallery.querySelector('.profile-editor__gallery-grid');

let isOrderMode = false;
let draggedSlot = null;
let originalOrderHtml = '';

const getFilledSlots = function () {
  return Array.from(gallery.querySelectorAll('[data-gallery-slot][data-attachment-id]'));
};

const updateSlotIndexes = function () {
  const slots = gallery.querySelectorAll('[data-gallery-slot]');

  slots.forEach(function (slot, index) {
    slot.setAttribute('data-slot-index', index);
  });
};

const setOrderMode = function (isEnabled) {
  isOrderMode = isEnabled;

  gallery.classList.toggle('is-order-mode', isEnabled);

  if (orderButton) {
    orderButton.hidden = isEnabled;
  }

  if (saveOrderButton) {
    saveOrderButton.hidden = !isEnabled;
  }

  if (cancelOrderButton) {
    cancelOrderButton.hidden = !isEnabled;
  }

  getFilledSlots().forEach(function (slot) {
    slot.draggable = isEnabled;
  });

  if (isEnabled && galleryGrid) {
    originalOrderHtml = galleryGrid.innerHTML;
    showMessage('Перетащите фотографии в нужном порядке.');
  } else {
    showMessage('');
  }
};

if (saveOrderButton) {
  saveOrderButton.hidden = true;
}

if (cancelOrderButton) {
  cancelOrderButton.hidden = true;
}

if (orderButton) {
  orderButton.addEventListener('click', function () {
    setOrderMode(true);
  });
}

if (cancelOrderButton) {
  cancelOrderButton.addEventListener('click', function () {
    if (galleryGrid && originalOrderHtml) {
      galleryGrid.innerHTML = originalOrderHtml;
    }

    updateSlotIndexes();
    setOrderMode(false);
  });
}

if (galleryGrid) {
  galleryGrid.addEventListener('dragstart', function (event) {
    if (!isOrderMode) {
      event.preventDefault();
      return;
    }

    const slot = event.target.closest('[data-gallery-slot][data-attachment-id]');

    if (!slot) {
      event.preventDefault();
      return;
    }

    draggedSlot = slot;
    slot.classList.add('is-dragging');

    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', slot.getAttribute('data-attachment-id'));
  });

  galleryGrid.addEventListener('dragend', function () {
    if (draggedSlot) {
      draggedSlot.classList.remove('is-dragging');
    }

    draggedSlot = null;
    updateSlotIndexes();
  });

  galleryGrid.addEventListener('dragover', function (event) {
    if (!isOrderMode || !draggedSlot) {
      return;
    }

    event.preventDefault();

    const targetSlot = event.target.closest('[data-gallery-slot][data-attachment-id]');

    if (!targetSlot || targetSlot === draggedSlot) {
      return;
    }

    const gridItems = getFilledSlots();
    const draggedIndex = gridItems.indexOf(draggedSlot);
    const targetIndex = gridItems.indexOf(targetSlot);

    if (draggedIndex < targetIndex) {
      targetSlot.after(draggedSlot);
    } else {
      targetSlot.before(draggedSlot);
    }
  });
}

if (saveOrderButton) {
  saveOrderButton.addEventListener('click', function () {
    const orderedIds = getFilledSlots().map(function (slot) {
      return slot.getAttribute('data-attachment-id');
    });

    if (!orderedIds.length) {
      showMessage('Нет фотографий для сортировки.', true);
      return;
    }

    const formData = new FormData();

    formData.append('action', 'dating_reorder_public_profile_gallery');
    formData.append('nonce', LoveStoryEditProfile.galleryNonce);

    orderedIds.forEach(function (attachmentId) {
      formData.append('order[]', attachmentId);
    });

    saveOrderButton.disabled = true;
    saveOrderButton.textContent = 'Сохраняю...';
    showMessage('Сохраняю порядок...');

    fetch(LoveStoryEditProfile.ajaxUrl, {
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
            : 'Не удалось сохранить порядок.';

          showMessage(errorMessage, true);
          return;
        }

        updateSlotIndexes();
        setOrderMode(false);
        showMessage(result.data.message || 'Порядок сохранён.');
      })
      .catch(function () {
        showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
      })
      .finally(function () {
        saveOrderButton.disabled = false;
        saveOrderButton.textContent = '✓ Сохранить порядок';
      });
  });
}
});
