<?php
/**
 * Footer template.
 */
?>
<footer class="footer" id="contacts">
    <div class="container footer__grid">
      <section class="footer__column footer__column--brand" aria-labelledby="footer-brand-title">
        <h2 class="visually-hidden" id="footer-brand-title">О LoveStory</h2>
        <a class="logo footer__logo" href="<?php echo esc_url(home_url('/')); ?>" aria-label="LoveStory — на главную">
          <span class="logo__icon" aria-hidden="true"></span>
          <span class="logo__text">LoveStory</span>
        </a>
        <p class="footer__text">Место, где рождаются чувства и создаются счастливые истории.</p>

        <ul class="social footer__social" aria-label="Социальные сети">
          <li class="social__item"><a class="social__link social__link--instagram" href="#" aria-label="Instagram"></a></li>
          <li class="social__item"><a class="social__link social__link--vk" href="#" aria-label="VK"></a></li>
          <li class="social__item"><a class="social__link social__link--facebook" href="#" aria-label="Facebook"></a></li>
          <li class="social__item"><a class="social__link social__link--telegram" href="#" aria-label="Telegram"></a></li>
        </ul>
      </section>

      <nav class="footer__column footer-nav" aria-labelledby="footer-nav-title">
        <h2 class="footer__title" id="footer-nav-title">Навигация</h2>
        <ul class="footer-nav__list">
          <li class="footer-nav__item"><a class="footer-nav__link" href="#hero">Главная</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#about">О нас</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#features">Возможности</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#stories">Истории</a></li>
        </ul>
      </nav>

      <nav class="footer__column footer-nav" aria-labelledby="footer-support-title">
        <h2 class="footer__title" id="footer-support-title">Поддержка</h2>
        <ul class="footer-nav__list">
          <li class="footer-nav__item"><a class="footer-nav__link" href="#">Помощь</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#">Безопасность</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#">Условия использования</a></li>
          <li class="footer-nav__item"><a class="footer-nav__link" href="#">Политика конфиденциальности</a></li>
        </ul>
      </nav>

      <address class="footer__column contacts" aria-labelledby="contacts-title">
        <h2 class="footer__title" id="contacts-title">Свяжитесь с нами</h2>
        <ul class="contacts__list">
          <li class="contacts__item contacts__item--email"><a class="contacts__link" href="mailto:hello@lovestory.com">hello@lovestory.com</a></li>
          <li class="contacts__item contacts__item--phone"><a class="contacts__link" href="tel:+78001234567">+7 (800) 123-45-67</a></li>
          <li class="contacts__item contacts__item--location">Москва, Россия</li>
        </ul>
      </address>
    </div>

    <p class="footer__copy">© <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. Все права защищены.</p>
  </footer>
<?php wp_footer(); ?>
</body>
</html>
