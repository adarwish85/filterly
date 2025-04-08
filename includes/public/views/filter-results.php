<?php
/**
 * Template for filter results.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/public/views
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if we have a query
if ( ! isset( $query ) || ! $query instanceof WP_Query ) {
    return;
}

// Default container ID if not set
if ( ! isset( $container_id ) ) {
    $container_id = 'filterly-results';
}

// Post type specific settings
$post_type = isset( $query->query['post_type'] ) ? $query->query['post_type'] : 'post';

// WooCommerce specific handling
$is_woocommerce = $post_type === 'product' && class_exists( 'WooCommerce' );

// Check if we have posts
if ( $query->have_posts() ) :
    
    // For WooCommerce products, use WC templates
    if ( $is_woocommerce ) :
        
        woocommerce_product_loop_start();
        
        while ( $query->have_posts() ) : $query->the_post();
            wc_get_template_part( 'content', 'product' );
        endwhile;
        
        woocommerce_product_loop_end();
        
        // Pagination
        woocommerce_pagination();
        
    // For regular posts
    else:
    ?>
        <div class="filterly-results-grid">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'filterly-item' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="filterly-item-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail( 'medium' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="filterly-item-content">
                        <header class="filterly-item-header">
                            <h2 class="filterly-item-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <?php if ( 'post' === get_post_type() ) : ?>
                                <div class="filterly-item-meta">
                                    <?php echo get_the_date(); ?>
                                </div>
                            <?php endif; ?>
                        </header>

                        <div class="filterly-item-excerpt">
                            <?php the_excerpt(); ?>
                        </div>

                        <a href="<?php the_permalink(); ?>" class="filterly-item-more">
                            <?php esc_html_e( 'Read more', 'filterly' ); ?>
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        
        <?php
        // Pagination
        the_posts_pagination( array(
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'filterly' ) . ' </span>',
        ) );
        ?>
    <?php
    endif;
    
    // Reset post data
    wp_reset_postdata();
    
else :
    // No results
    echo '<div class="filterly-no-results">';
    echo '<p>' . esc_html__( 'No results found matching your criteria.', 'filterly' ) . '</p>';
    echo '</div>';
endif;