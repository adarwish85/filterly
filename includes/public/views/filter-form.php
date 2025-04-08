<?php
/**
 * Template for filter form.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/public/views
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Default container ID if not provided
$container_id = ! empty( $atts['container_id'] ) ? esc_attr( $atts['container_id'] ) : 'filterly-results';

// Determine if AJAX is enabled
$use_ajax = isset( $atts['ajax'] ) ? filter_var( $atts['ajax'], FILTER_VALIDATE_BOOLEAN ) : true;

// Whether to show count and reset button
$show_count = isset( $atts['show_count'] ) ? filter_var( $atts['show_count'], FILTER_VALIDATE_BOOLEAN ) : true;
$show_reset = isset( $atts['show_reset'] ) ? filter_var( $atts['show_reset'], FILTER_VALIDATE_BOOLEAN ) : true;

// Target post type
$post_type = isset( $atts['post_type'] ) ? esc_attr( $atts['post_type'] ) : 'post';

?>
<div class="filterly-container" data-post-type="<?php echo esc_attr( $post_type ); ?>" data-use-ajax="<?php echo $use_ajax ? 'true' : 'false'; ?>">
    <?php if ( ! empty( $this->filters ) ) : ?>
        <div class="filterly-sidebar">
            <form class="filterly-form" method="get" action="<?php echo esc_url( $this->generate_filtered_url( array(), $post_type ) ); ?>" data-container-id="<?php echo esc_attr( $container_id ); ?>">
                <?php 
                // Add post type as hidden input
                echo '<input type="hidden" name="post_type" value="' . esc_attr( $post_type ) . '">';
                
                // Render each filter
                foreach ( $this->filters as $filter ) :
                    // Skip if limiting to specific filters and this one is not included
                    if ( ! empty( $filter_ids ) && ! in_array( $filter->get_id(), $filter_ids ) ) {
                        continue;
                    }
                    
                    // Get current value for this filter
                    $current_value = isset( $filter_values[ $filter->get_id() ] ) ? $filter_values[ $filter->get_id() ] : '';
                    
                    // Render filter
                    echo $filter->render( $current_value );
                endforeach; 
                ?>
                
                <div class="filterly-actions">
                    <button type="submit" class="filterly-apply-button">
                        <?php echo esc_html__( 'Apply Filters', 'filterly' ); ?>
                    </button>
                    
                    <?php if ( $show_reset ) : ?>
                        <a href="<?php echo esc_url( $this->generate_filtered_url( array(), $post_type ) ); ?>" class="filterly-reset-button">
                            <?php echo esc_html__( 'Reset', 'filterly' ); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ( $show_count && isset( $query ) ) : ?>
                        <div class="filterly-count">
                            <?php 
                            printf(
                                esc_html__( 'Found %d results', 'filterly' ),
                                $query->found_posts
                            ); 
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <div class="filterly-results-container" id="<?php echo esc_attr( $container_id ); ?>">
        <?php include FILTERLY_PLUGIN_DIR . 'includes/public/views/filter-results.php'; ?>
    </div>
    
    <div class="filterly-loading">
        <div class="filterly-spinner"></div>
        <span><?php echo esc_html__( 'Loading...', 'filterly' ); ?></span>
    </div>
</div>