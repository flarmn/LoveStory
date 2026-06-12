document.addEventListener('DOMContentLoaded', function () {
  const chat = document.querySelector('.messages__chat');
  const form = document.querySelector('[data-message-form]');
  const body = document.querySelector('[data-messages-body]');
  const formMessage = document.querySelector('[data-message-form-message]');
  const dialogList = document.querySelector('[data-dialog-list]');
  
  const conversationId = chat ? chat.getAttribute('data-conversation-id') : '0';
  const messagesView = dialogList
    ? dialogList.getAttribute('data-messages-view') || 'active'
    : 'active';

  const messagesRoot = document.querySelector('[data-messages-root]');
  const dialogsToggle = document.querySelector('[data-dialogs-toggle]');
  const dialogsOverlay = document.querySelector('[data-dialogs-overlay]');

  const dialogsToggleBadge = document.querySelector('[data-dialogs-toggle-badge]');

  const messageActionsOverlay = document.querySelector('[data-message-actions-overlay]');
const messageActionsSheet = document.querySelector('[data-message-actions-sheet]');
const messageActionsEdit = document.querySelector('[data-message-actions-edit]');
const messageActionsDelete = document.querySelector('[data-message-actions-delete]');
const messageActionsClose = document.querySelector('[data-message-actions-close]');

const LONG_PRESS_DELAY = 1500;
const LONG_PRESS_STEP = 100;
const LONG_PRESS_MAX_GLOW = 5;

let longPressTimer = null;
let longPressInterval = null;
let longPressStartedAt = 0;
let activeActionBubble = null;

  let isFetchingMessages = false;
  let isFetchingDialogs = false;
  let lastSentAt = 0;

  const showMessage = function (text, isError = false) {
    if (!formMessage) {
      return;
    }

    formMessage.textContent = text;
    formMessage.classList.toggle('messages__form-message--error', isError);
  };

  const scrollToBottom = function () {
    if (!body) {
      return;
    }

    body.scrollTop = body.scrollHeight;
  };

  const getLastMessageId = function () {
    if (!body) {
      return 0;
    }

    const messages = body.querySelectorAll('[data-message-id]');

    if (!messages.length) {
      return 0;
    }

    const lastMessage = messages[messages.length - 1];

    return Number(lastMessage.getAttribute('data-message-id')) || 0;
  };

const createMessageNode = function (message) {
  const article = document.createElement('article');

  article.className = message.is_own
    ? 'messages__bubble messages__bubble--own'
    : 'messages__bubble messages__bubble--partner';

  if (message.is_deleted) {
    article.classList.add('messages__bubble--deleted');
  }

  article.setAttribute('data-message-id', message.id);

  if (message.is_own) {
    article.setAttribute('data-own-message', '1');
  }

  if (message.is_deleted) {
    article.setAttribute('data-message-deleted', '1');
  }

  const paragraph = document.createElement('p');
  paragraph.className = 'messages__bubble-text';
  paragraph.setAttribute('data-message-text', '');
  paragraph.textContent = message.is_deleted ? 'Сообщение удалено' : message.message_text;

  const meta = document.createElement('div');
  meta.className = 'messages__bubble-meta';

  const time = document.createElement('time');
  time.className = 'messages__bubble-time';
  time.setAttribute('datetime', message.created_at || '');
  time.textContent = message.time_label || '';

  meta.appendChild(time);

  if (message.is_edited && !message.is_deleted) {
    const edited = document.createElement('span');
    edited.className = 'messages__bubble-edited';
    edited.setAttribute('data-message-edited', '');
    edited.textContent = 'изменено';
    meta.appendChild(edited);
  }

  article.appendChild(paragraph);
  article.appendChild(meta);

  if (message.is_own && !message.is_deleted) {
    const actions = document.createElement('div');
    actions.className = 'messages__bubble-actions';
    actions.setAttribute('data-message-actions', '');

    const editButton = document.createElement('button');
    editButton.className = 'messages__bubble-action';
    editButton.type = 'button';
    editButton.setAttribute('data-message-edit-trigger', '');
    editButton.textContent = 'Изменить';

    const deleteButton = document.createElement('button');
    deleteButton.className = 'messages__bubble-action messages__bubble-action--delete';
    deleteButton.type = 'button';
    deleteButton.setAttribute('data-message-delete-trigger', '');
    deleteButton.textContent = 'Удалить';

    actions.appendChild(editButton);
    actions.appendChild(deleteButton);
    article.appendChild(actions);
  }

  return article;
};

const updateMessageNode = function (bubble, message) {
  if (!bubble || !message) {
    return;
  }

  const isDeleted = Boolean(message.is_deleted);
  const isEdited = Boolean(message.is_edited) && !isDeleted;

  bubble.classList.toggle('messages__bubble--deleted', isDeleted);

  if (isDeleted) {
    bubble.setAttribute('data-message-deleted', '1');
  } else {
    bubble.removeAttribute('data-message-deleted');
  }

  const textNode = bubble.querySelector('[data-message-text]');

  if (textNode) {
    textNode.textContent = isDeleted
      ? 'Сообщение удалено'
      : message.message_text;
  }

  const metaNode = bubble.querySelector('.messages__bubble-meta');
  let editedNode = bubble.querySelector('[data-message-edited]');

  if (isEdited) {
    if (!editedNode && metaNode) {
      editedNode = document.createElement('span');
      editedNode.className = 'messages__bubble-edited';
      editedNode.setAttribute('data-message-edited', '');
      metaNode.appendChild(editedNode);
    }

    if (editedNode) {
      editedNode.textContent = message.edited_label || 'изменено';
    }
  } else if (editedNode) {
    editedNode.remove();
  }

  if (isDeleted) {
    const actionsNode = bubble.querySelector('[data-message-actions]');

    if (actionsNode) {
      actionsNode.remove();
    }
  }
};

const syncMessage = function (message) {
  if (!body || !message || !message.id) {
    return;
  }

  const existingBubble = body.querySelector('[data-message-id="' + message.id + '"]');

  if (existingBubble) {
    updateMessageNode(existingBubble, message);
    return;
  }

  appendMessage(message);
};

  const removeEmptyText = function () {
    if (!body) {
      return;
    }

    const emptyText = body.querySelector('.messages__empty-text');

    if (emptyText) {
      emptyText.remove();
    }
  };

  const appendMessage = function (message) {
    if (!body) {
      return;
    }

    removeEmptyText();
    body.appendChild(createMessageNode(message));
  };

  const createDialogItem = function (item) {
    const listItem = document.createElement('li');
    listItem.className = 'messages__dialog-item';

    const link = document.createElement('a');
    link.className = 'messages__dialog-link';

    if (item.is_active) {
      link.classList.add('messages__dialog-link--active');
    }

    if (Number(item.unread_count) > 0) {
      link.classList.add('messages__dialog-link--unread');
    }

    link.href = item.url;

    const avatar = document.createElement('img');
    avatar.className = 'messages__dialog-avatar';
    avatar.src = item.partner_photo;
    avatar.alt = item.partner_name;

    const info = document.createElement('span');
    info.className = 'messages__dialog-info';

    const name = document.createElement('span');
    name.className = 'messages__dialog-name';
    name.textContent = item.partner_name;

    const date = document.createElement('span');
    date.className = 'messages__dialog-date';
    date.textContent = item.date_label;

    info.appendChild(name);
    info.appendChild(date);

    const preview = document.createElement('span');
preview.className = 'messages__dialog-preview';

if (item.last_message_preview) {
  preview.textContent = item.last_message_preview;
} else {
  preview.classList.add('messages__dialog-preview--empty');
  preview.textContent = 'Диалог создан';
}

info.appendChild(preview);

    if (Number(item.unread_count) > 0) {
      const unread = document.createElement('span');
      unread.className = 'messages__dialog-unread';
      unread.textContent = item.unread_count + ' новых';
      info.appendChild(unread);
    }

    link.appendChild(avatar);
    link.appendChild(info);

    const button = document.createElement('button');
    button.className = 'messages__dialog-action';
    button.type = 'button';
    button.setAttribute('data-conversation-action', item.action_type);
    button.setAttribute('data-conversation-id', item.id);
    button.textContent = item.action_label;

    listItem.appendChild(link);
    listItem.appendChild(button);

    return listItem;
  };

  const renderDialogList = function (items) {
    if (!dialogList) {
      return;
    }

    dialogList.innerHTML = '';

    if (dialogsToggleBadge) {
  const totalUnread = items.reduce(function (sum, item) {
    return sum + (Number(item.unread_count) || 0);
  }, 0);

  if (totalUnread > 0) {
    dialogsToggleBadge.hidden = false;
    dialogsToggleBadge.textContent = totalUnread;
  } else {
    dialogsToggleBadge.hidden = true;
    dialogsToggleBadge.textContent = '0';
  }
}

    if (!items.length) {
      const emptyItem = document.createElement('li');
      emptyItem.className = 'messages__dialog-empty';
      emptyItem.textContent = messagesView === 'hidden'
        ? 'Скрытых диалогов пока нет.'
        : 'У вас пока нет диалогов.';

      dialogList.appendChild(emptyItem);
      return;
    }

    items.forEach(function (item) {
      dialogList.appendChild(createDialogItem(item));
    });
  };

  const fetchConversationsList = function () {
    if (!dialogList || typeof LoveStoryMessages === 'undefined' || isFetchingDialogs) {
      return;
    }

    const formData = new FormData();

    formData.append('action', 'dating_fetch_conversations_list');
    formData.append('nonce', LoveStoryMessages.nonce);
    formData.append('view', messagesView);
    formData.append('active_conversation_id', conversationId || 0);

    isFetchingDialogs = true;

    fetch(LoveStoryMessages.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result.success) {
          return;
        }

        renderDialogList(result.data.items || []);
      })
      .catch(function () {
        // Тихо игнорируем, чтобы не мешать пользователю.
      })
      .finally(function () {
        isFetchingDialogs = false;
      });
  };

  const fetchNewMessages = function () {
    if (!body || !conversationId || isFetchingMessages) {
      return;
    }

    const lastMessageId = getLastMessageId();
    const formData = new FormData();

    formData.append('action', 'dating_fetch_messages');
    formData.append('nonce', LoveStoryMessages.nonce);
    formData.append('conversation_id', conversationId);
    formData.append('last_message_id', lastMessageId);

    isFetchingMessages = true;

    fetch(LoveStoryMessages.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (result) {
        if (!result.success) {
          return;
        }

        const messages = result.data.messages || [];

        if (!messages.length) {
          return;
        }

        const wasNearBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 120;

        messages.forEach(function (message) {
          syncMessage(message);
        });

        if (wasNearBottom) {
          scrollToBottom();
        }

        fetchConversationsList();
      })
      .catch(function () {
        // Не показываем ошибку каждые 5 секунд, чтобы не раздражать пользователя.
      })
      .finally(function () {
        isFetchingMessages = false;
      });
  };

  if (form && body && conversationId) {
    const textarea = form.querySelector('textarea[name="message_text"]');
    const submitButton = form.querySelector('button[type="submit"]');

    if (textarea && submitButton) {
      scrollToBottom();

      form.addEventListener('submit', function (event) {
        event.preventDefault();

        const text = textarea.value.trim();

        const now = Date.now();

        if (now - lastSentAt < 3000) {
          showMessage('Пожалуйста, не отправляйте сообщения так часто.', true);
          return;
        }

        if (!text) {
          showMessage('Введите сообщение.', true);
          return;
        }

        if (text.length > 1000) {
          showMessage('Сообщение не должно быть длиннее 1000 символов.', true);
          return;
        }

        const formData = new FormData();

        formData.append('action', 'dating_send_message');
        formData.append('nonce', LoveStoryMessages.nonce);
        formData.append('conversation_id', conversationId);
        formData.append('message_text', text);

        submitButton.disabled = true;
        submitButton.textContent = 'Отправляю...';
        showMessage('');

        fetch(LoveStoryMessages.ajaxUrl, {
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
                : 'Не удалось отправить сообщение.';

              showMessage(errorMessage, true);
              return;
            }

            const data = result.data.data;

            appendMessage({
              id: data.id,
              message_text: data.message_text,
              created_at: data.created_at,
              time_label: data.time_label,
              is_own: true,
            });

            textarea.value = '';
            lastSentAt = Date.now();
            showMessage('');
            scrollToBottom();
            fetchConversationsList();
          })
          .catch(function () {
            showMessage('Ошибка соединения. Попробуйте ещё раз.', true);
          })
          .finally(function () {
            submitButton.disabled = false;
            submitButton.textContent = 'Отправить';
          });
      });
    }
  }


  /* helper-функции для long press */
  const isMobileMessagesView = function () {
  return window.matchMedia('(max-width: 860px)').matches;
};

const resetLongPressGlow = function (bubble) {
  if (!bubble) {
    return;
  }

  bubble.classList.remove('messages__bubble--long-pressing');
  bubble.style.removeProperty('--press-glow-size');
  bubble.style.removeProperty('--press-glow-opacity');
};

const clearLongPress = function () {
  if (longPressTimer) {
    clearTimeout(longPressTimer);
    longPressTimer = null;
  }

  if (longPressInterval) {
    clearInterval(longPressInterval);
    longPressInterval = null;
  }

  if (activeActionBubble) {
    resetLongPressGlow(activeActionBubble);
  }
};

const updateLongPressGlow = function (bubble) {
  const elapsed = Date.now() - longPressStartedAt;
  const progress = Math.min(elapsed / LONG_PRESS_DELAY, 1);
  const glowSize = Math.round(progress * LONG_PRESS_MAX_GLOW);
  const glowOpacity = 0.12 + progress * 0.38;

  bubble.classList.add('messages__bubble--long-pressing');
  bubble.style.setProperty('--press-glow-size', glowSize + 'px');
  bubble.style.setProperty('--press-glow-opacity', glowOpacity.toFixed(2));
};

const openMessageActionsSheet = function (bubble) {
  if (!messageActionsOverlay || !messageActionsSheet || !bubble) {
    return;
  }

  activeActionBubble = bubble;

  messageActionsOverlay.hidden = false;
  messageActionsSheet.hidden = false;

  document.body.classList.add('is-message-actions-open');
};

const closeMessageActionsSheet = function () {
  if (messageActionsOverlay) {
    messageActionsOverlay.hidden = true;
  }

  if (messageActionsSheet) {
    messageActionsSheet.hidden = true;
  }

  document.body.classList.remove('is-message-actions-open');

  if (activeActionBubble) {
    resetLongPressGlow(activeActionBubble);
  }

  activeActionBubble = null;
};


/* сам long press */
document.addEventListener('pointerdown', function (event) {
  if (!isMobileMessagesView()) {
    return;
  }

  const bubble = event.target.closest('[data-own-message="1"]');

  if (!bubble || bubble.getAttribute('data-message-deleted') === '1') {
    return;
  }

  //event.preventDefault();

  activeActionBubble = bubble;
  longPressStartedAt = Date.now();

  updateLongPressGlow(bubble);

  longPressInterval = setInterval(function () {
    updateLongPressGlow(bubble);
  }, LONG_PRESS_STEP);

  longPressTimer = setTimeout(function () {
    clearLongPress();

    openMessageActionsSheet(bubble);
  }, LONG_PRESS_DELAY);
});

document.addEventListener('pointerup', clearLongPress);
document.addEventListener('pointercancel', clearLongPress);

document.addEventListener('pointermove', function (event) {
  if (!activeActionBubble || !isMobileMessagesView()) {
    return;
  }

  if (event.pointerType === 'touch') {
    clearLongPress();
  }
});


/* виртуальный клик */
if (messageActionsEdit) {
  messageActionsEdit.addEventListener('click', function () {
    if (!activeActionBubble) {
      return;
    }

    const editButton = activeActionBubble.querySelector('[data-message-edit-trigger]');

    closeMessageActionsSheet();

    if (editButton) {
      editButton.click();
    }
  });
}

if (messageActionsDelete) {
  messageActionsDelete.addEventListener('click', function () {
    if (!activeActionBubble) {
      return;
    }

    const deleteButton = activeActionBubble.querySelector('[data-message-delete-trigger]');

    closeMessageActionsSheet();

    if (deleteButton) {
      deleteButton.click();
    }
  });
}

if (messageActionsClose) {
  messageActionsClose.addEventListener('click', closeMessageActionsSheet);
}

if (messageActionsOverlay) {
  messageActionsOverlay.addEventListener('click', closeMessageActionsSheet);
}


  document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-conversation-action]');

    if (!button) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const actionConversationId = button.getAttribute('data-conversation-id');
    const actionType = button.getAttribute('data-conversation-action');

    if (!actionConversationId || !actionType || typeof LoveStoryMessages === 'undefined') {
      return;
    }

    const isRestore = actionType === 'restore';

    if (!isRestore) {
      const confirmed = window.confirm('Скрыть этот диалог из списка?');

      if (!confirmed) {
        return;
      }
    }

    const formData = new FormData();

    formData.append(
      'action',
      isRestore ? 'dating_restore_conversation' : 'dating_hide_conversation'
    );
    formData.append('nonce', LoveStoryMessages.nonce);
    formData.append('conversation_id', actionConversationId);

    button.disabled = true;
    button.textContent = isRestore ? 'Возвращаю...' : 'Скрываю...';

    fetch(LoveStoryMessages.ajaxUrl, {
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
            : 'Не удалось выполнить действие.';

          alert(errorMessage);
          return;
        }

        const item = button.closest('.messages__dialog-item');

        if (item) {
          item.remove();
        }

        if (chat && chat.getAttribute('data-conversation-id') === actionConversationId) {
          window.location.href = LoveStoryMessages.messagesUrl;
          return;
        }

        fetchConversationsList();
      })
      .catch(function () {
        alert('Ошибка соединения. Попробуйте ещё раз.');
      })
      .finally(function () {
        button.disabled = false;
        button.textContent = isRestore ? 'Вернуть' : 'Скрыть';
      });
  }, true);

  /* Обработчик блокировки / разблокировки пользователя */

document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-user-block-action]');

  if (!button) {
    return;
  }

  event.preventDefault();

  if (typeof LoveStoryMessages === 'undefined') {
    alert('Система сообщений временно недоступна.');
    return;
  }

  const actionType = button.getAttribute('data-user-block-action');
  const blockedUserId = button.getAttribute('data-blocked-user-id');

  if (!actionType || !blockedUserId) {
    alert('Не удалось определить пользователя.');
    return;
  }

  const isUnblock = actionType === 'unblock';

  const confirmed = window.confirm(
    isUnblock
      ? 'Разблокировать этого пользователя?'
      : 'Заблокировать этого пользователя? После блокировки обмен сообщениями будет недоступен.'
  );

  if (!confirmed) {
    return;
  }

  const formData = new FormData();

  formData.append(
    'action',
    isUnblock ? 'dating_unblock_user' : 'dating_block_user'
  );

  formData.append('nonce', LoveStoryMessages.nonce);
  formData.append('blocked_user_id', blockedUserId);

  button.disabled = true;
  button.textContent = isUnblock ? 'Разблокирую...' : 'Блокирую...';

  fetch(LoveStoryMessages.ajaxUrl, {
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
          : 'Не удалось выполнить действие.';

        alert(errorMessage);
        return;
      }

      window.location.reload();
    })
    .catch(function () {
      alert('Ошибка соединения. Попробуйте ещё раз.');
    })
    .finally(function () {
      button.disabled = false;
      button.textContent = isUnblock ? 'Разблокировать' : 'Заблокировать';
    });
});


  if (messagesRoot && dialogsToggle && dialogsOverlay) {
  const openDialogsPanel = function () {
    messagesRoot.classList.add('is-dialogs-panel-open');
    dialogsToggle.setAttribute('aria-expanded', 'true');
    dialogsToggle.setAttribute('aria-label', 'Закрыть диалоги');
  };

  const closeDialogsPanel = function () {
    messagesRoot.classList.remove('is-dialogs-panel-open');
    dialogsToggle.setAttribute('aria-expanded', 'false');
    dialogsToggle.setAttribute('aria-label', 'Открыть диалоги');
  };

  dialogsToggle.addEventListener('click', function () {
    if (messagesRoot.classList.contains('is-dialogs-panel-open')) {
      closeDialogsPanel();
    } else {
      openDialogsPanel();
    }
  });

  dialogsOverlay.addEventListener('click', closeDialogsPanel);

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      closeDialogsPanel();
    }
  });

  if (dialogList) {
    dialogList.addEventListener('click', function (event) {
      const dialogLink = event.target.closest('.messages__dialog-link');

      if (dialogLink) {
        closeDialogsPanel();
      }
    });
  }
}


/* обработчик удаления сообщения */

document.addEventListener('click', function (event) {
  const deleteButton = event.target.closest('[data-message-delete-trigger]');

  if (!deleteButton) {
    return;
  }

  event.preventDefault();

  const bubble = deleteButton.closest('[data-message-id]');

  if (!bubble) {
    return;
  }

  const messageId = bubble.getAttribute('data-message-id');

  if (!messageId) {
    return;
  }

  const confirmed = window.confirm('Удалить это сообщение?');

  if (!confirmed) {
    return;
  }

  const formData = new FormData();

  formData.append('action', 'dating_delete_message');
  formData.append('nonce', LoveStoryMessages.nonce);
  formData.append('message_id', messageId);

  deleteButton.disabled = true;
  deleteButton.textContent = 'Удаляю...';

  fetch(LoveStoryMessages.ajaxUrl, {
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
          : 'Не удалось удалить сообщение.';

        alert(errorMessage);
        return;
      }

      const textNode = bubble.querySelector('[data-message-text]');
      const actionsNode = bubble.querySelector('[data-message-actions]');
      const editedNode = bubble.querySelector('[data-message-edited]');

      bubble.classList.add('messages__bubble--deleted');
      bubble.setAttribute('data-message-deleted', '1');

      if (textNode) {
        textNode.textContent = result.data.data.deleted_text || 'Сообщение удалено';
      }

      if (actionsNode) {
        actionsNode.remove();
      }

      if (editedNode) {
        editedNode.remove();
      }

      fetchConversationsList();
    })
    .catch(function () {
      alert('Ошибка соединения. Попробуйте ещё раз.');
    })
    .finally(function () {
      deleteButton.disabled = false;
      deleteButton.textContent = 'Удалить';
    });
});


/* обработчик редактирования */

document.addEventListener('click', function (event) {
  const editButton = event.target.closest('[data-message-edit-trigger]');

  if (!editButton) {
    return;
  }

  event.preventDefault();

  const bubble = editButton.closest('[data-message-id]');

  if (!bubble || bubble.getAttribute('data-message-deleted') === '1') {
    return;
  }

  const messageId = bubble.getAttribute('data-message-id');
  const textNode = bubble.querySelector('[data-message-text]');

  if (!messageId || !textNode) {
    return;
  }

  const currentText = textNode.textContent.trim();

  const newText = window.prompt('Изменить сообщение:', currentText);

  if (newText === null) {
    return;
  }

  const preparedText = newText.trim();

  if (!preparedText) {
    alert('Сообщение не может быть пустым.');
    return;
  }

  if (preparedText.length > 1000) {
    alert('Сообщение не должно быть длиннее 1000 символов.');
    return;
  }

  if (preparedText === currentText) {
    return;
  }

  const formData = new FormData();

  formData.append('action', 'dating_edit_message');
  formData.append('nonce', LoveStoryMessages.nonce);
  formData.append('message_id', messageId);
  formData.append('message_text', preparedText);

  editButton.disabled = true;
  editButton.textContent = 'Сохраняю...';

  fetch(LoveStoryMessages.ajaxUrl, {
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
          : 'Не удалось изменить сообщение.';

        alert(errorMessage);
        return;
      }

      textNode.textContent = result.data.data.message_text;

      let editedNode = bubble.querySelector('[data-message-edited]');
      const metaNode = bubble.querySelector('.messages__bubble-meta');

      if (!editedNode && metaNode) {
        editedNode = document.createElement('span');
        editedNode.className = 'messages__bubble-edited';
        editedNode.setAttribute('data-message-edited', '');
        metaNode.appendChild(editedNode);
      }

      if (editedNode) {
        editedNode.textContent = result.data.data.edited_label || 'изменено';
      }

      fetchConversationsList();
    })
    .catch(function () {
      alert('Ошибка соединения. Попробуйте ещё раз.');
    })
    .finally(function () {
      editButton.disabled = false;
      editButton.textContent = 'Изменить';
    });
});

  fetchConversationsList();
  setInterval(fetchNewMessages, 5000);
  setInterval(fetchConversationsList, 15000);
});
