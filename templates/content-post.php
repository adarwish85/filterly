<?php
/**
 * Template part for displaying posts in filtered results.
 *
 * @since      1.0.0
 * @package    Filterly
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'filterly-post' ); ?>>
    <div class="filterly-post-inner">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="filterly-post-thumbnail">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'medium' ); ?>
                </a>
            </div>
        <?php endif; ?>

        <header class="filterly-post-header">
            <h2 class="filterly-post-title">
                <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
            </h2>

            <div class="filterly-post-meta">
                <span class="filterly-post-date"><?php echo get_the_date(); ?></span>
                <span class="filterly-post-author"><?php esc_html_e( 'by', 'filterly' ); ?> <?php the_author(); ?></span>
            </div>
        </header>

        <div class="filterly-post-excerpt">
            <?php the_excerpt(); ?>
        </div>

        <div class="filterly-post-footer">
            <a href="<?php the_permalink(); ?>" class="filterly-read-more">
                <?php esc_html_e( 'Read More', 'filterly' ); ?>
                <span class="screen-reader-text"><?php esc_html_e( 'about', 'filterly' ); ?> <?php the_title(); ?></span>
            </a>
        </div>
    </div>
</article>