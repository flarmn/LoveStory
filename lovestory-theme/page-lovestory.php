<?php
/**
 * Template Name: LoveStory — контентная страница
 * Template Post Type: page
 */

get_header();

$page_id = get_the_ID();

$lovestory_page_intro = get_post_meta($page_id, 'lovestory_page_intro', true);
$lovestory_page_label = get_post_meta($page_id, 'lovestory_page_label', true);

if (!$lovestory_page_label) {
    $lovestory_page_label = 'LoveStory';
}
?>

<main class="lovestory-page">
  <section class="lovestory-content" aria-labelledby="lovestory-page-title">
    <div class="lovestory-content__container">

      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>

          <nav class="lovestory-content__breadcrumbs" aria-label="Хлебные крошки">
            <a class="lovestory-content__breadcrumb-link" href="<?php echo esc_url(home_url('/')); ?>">
              Главная
            </a>

            <span class="lovestory-content__breadcrumb-separator" aria-hidden="true">›</span>

            <span class="lovestory-content__breadcrumb-current">
              <?php the_title(); ?>
            </span>
          </nav>

          <header class="lovestory-content__header">
            <p class="lovestory-content__eyebrow">
              <?php echo esc_html($lovestory_page_label); ?>
            </p>

            <h1 class="lovestory-content__title" id="lovestory-page-title">
              <?php the_title(); ?>
            </h1>

            <?php if ($lovestory_page_intro) : ?>
              <p class="lovestory-content__intro">
                <?php echo esc_html($lovestory_page_intro); ?>
              </p>
            <?php endif; ?>
          </header>

          <article class="lovestory-content__card">
            <div class="lovestory-content__body">
              <?php the_content(); ?>
            </div>
          </article>

        <?php endwhile; ?>
      <?php else : ?>

        <article class="lovestory-content__card">
          <div class="lovestory-content__body">
            <h1 class="lovestory-content__title">
              Страница не найдена
            </h1>

            <p>
              К сожалению, эта страница недоступна.
            </p>
          </div>
        </article>

      <?php endif; ?>

    </div>
  </section>
</main>

<?php
get_footer();