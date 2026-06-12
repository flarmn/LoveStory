document.addEventListener('DOMContentLoaded', function () {
  const menus = document.querySelectorAll('[data-mobile-menu]');

  menus.forEach(function (menu) {
    const toggle = menu.querySelector('[data-mobile-menu-toggle]');

    if (!toggle) {
      return;
    }

    const closeMenu = function () {
      menu.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
      toggle.setAttribute('aria-label', 'Открыть меню');
    };

    toggle.addEventListener('click', function (event) {
      event.stopPropagation();

      const isOpen = menu.classList.toggle('is-open');

      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      toggle.setAttribute('aria-label', isOpen ? 'Закрыть меню' : 'Открыть меню');
    });

    document.addEventListener('click', function (event) {
      if (!menu.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMenu();
      }
    });

    const links = menu.querySelectorAll('a');

    links.forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });
  });
});