<?php
$current_page = isset($_GET['page']) ? max(1, absint($_GET['page'])) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

$q = new WP_Query([
    'posts_per_page' => 10,
    'paged'          => $current_page,
    's'              => $search_query,
]);

$total_pages = max(1, (int) $q->max_num_pages);
?>

<header class="search-header">
    <h1>
        Page <?= esc_html($current_page); ?> of <?= esc_html($total_pages); ?>
        <?php if ($search_query !== '') : ?>
            : Search for <?= esc_html($search_query); ?>
        <?php endif; ?>
    </h1>
</header>

<?php if ($q->have_posts()) : ?>
    <div class="search-results">
        <?php
        while ($q->have_posts()) :
            $q->the_post();

            $content = get_the_content();
            $ad_index = 0;
            $content = preg_replace_callback('/\[ad\]/', function () use (&$ad_index) {
                $ad_index++;
                return sprintf('<div class="ad ad-%d"></div>', $ad_index);
            }, $content);
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h2><?= esc_html(get_the_title()); ?></h2>
                <div class="entry-content">
                    <?= wp_kses_post(wpautop($content)); ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
    <?php wp_reset_postdata(); ?>
<?php else : ?>
    <p class="no-results">No posts found matching your criteria.</p>
<?php endif; ?>
