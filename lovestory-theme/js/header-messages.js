document.addEventListener('DOMContentLoaded', function () {
  const link = document.querySelector('[data-header-messages-link]');
  const badge = document.querySelector('[data-header-messages-badge]');

  if (!link || !badge || typeof LoveStoryHeaderMessages === 'undefined') {
    return;
  }

  let isFetching = false;

  const updateBadge = function (count) {
    const unreadCount = Number(count) || 0;

    if (unreadCount > 0) {
      badge.hidden = false;
      badge.textContent = unreadCount;

      link.setAttribute('title', 'Новые сообщения');
      link.setAttribute('aria-label', 'Новые сообщения');
    } else {
      badge.hidden = true;
      badge.textContent = '0';

      link.setAttribute('title', 'Сообщения');
      link.setAttribute('aria-label', 'Сообщения');
    }
  };

  const fetchUnreadCount = function () {
    if (isFetching) {
      return;
    }

    const formData = new FormData();

    formData.append('action', 'dating_get_unread_messages_total');
    formData.append('nonce', LoveStoryHeaderMessages.nonce);

    isFetching = true;

    fetch(LoveStoryHeaderMessages.ajaxUrl, {
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

        updateBadge(result.data.count);
      })
      .catch(function () {
        // Тихо игнорируем, чтобы не раздражать пользователя.
      })
      .finally(function () {
        isFetching = false;
      });
  };

  fetchUnreadCount();

  setInterval(fetchUnreadCount, 15000);
});