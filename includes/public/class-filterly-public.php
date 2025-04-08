<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The public-facing functionality of the plugin.
 */
class Filterly_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The query handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Filterly_Query    $query    The query handler instance.
     */
    private $query;

    /**
     * The URL handler instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Filterly_URL_Handler    $url_handler    The URL handler instance.
     */
    private $url_handler;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Load dependencies
        require_once FILTERLY_PLUGIN_DIR . 'includes/public/class-filterly-query.php';
        require_once FILTERLY_PLUGIN_DIR . 'includes/public/class-filterly-url-handler.php';
        
        // Initialize handlers
        $this->query = new Filterly_Query();
        $this->url_handler = new Filterly_URL_Handler();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, FILTERLY_PLUGIN_URL . 'assets/css/public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, FILTERLY_PLUGIN_URL . 'assets/js/public.js', array( 'jquery' ), $this->version, true );
        
        // Add localized data for the script
        wp_localize_script( $this->plugin_name, 'filterly', array(
            'ajax_url'        => admin_url( 'admin-ajax.php' ),
            'nonce'           => wp_create_nonce( 'filterly_nonce' ),
            'loading_text'    => __( 'Loading...', 'filterly' ),
            'view_more_text'  => __( 'View More', 'filterly' ),
            'view_less_text'  => __( 'View Less', 'filterly' ),
        ) );
    }

    /**
     * Process AJAX filter requests.
     *
     * @since    1.0.0
     */
    public function process_ajax_filter() {
        // Check nonce
        if ( ! check_ajax_referer( 'filterly_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'filterly' ) ) );
        }

        // Get filter data
        $filter_data = isset( $_POST['filter_data'] ) ? $_POST['filter_data'] : array();
        
        if ( ! is_array( $filter_data ) ) {
            $filter_data = array();
        }
        
        // Sanitize filter data
        $sanitized_filter_data = array();
        foreach ( $filter_data as $key => $values ) {
            $key = sanitize_key( $key );
            if ( is_array( $values ) ) {
                $sanitized_values = array_map( 'sanitize_text_field', $values );
                $sanitized_filter_data[ $key ] = $sanitized_values;
            } else {
                $sanitized_filter_data[ $key ] = sanitize_text_field( $values );
            }
        }
        
        // Get target settings
        $target_post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : 'post';
        $posts_per_page = isset( $_POST['posts_per_page'] ) ? absint( $_POST['posts_per_page'] ) : get_option( 'posts_per_page' );
        $paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        $target_container = isset( $_POST['container'] ) ? sanitize_text_field( $_POST['container'] ) : '';
        
        // Build the query
        $query_args = $this->query->build_query( $sanitized_filter_data, array(
            'post_type'      => $target_post_type,
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        ) );
        
        // Execute the query
        $query = new WP_Query( $query_args );
        
        // Get the results HTML
        ob_start();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // Use template based on post type
                $template = 'content-' . $target_post_type . '.php';
                
                // Check if the template exists in the theme
                if ( locate_template( $template ) ) {
                    get_template_part( 'content', $target_post_type );
                } else {
                    // Fall back to default template
                    include FILTERLY_PLUGIN_DIR . 'templates/results-container.php';
                }
            }
            
            // Pagination
            $this->render_pagination( $query );
            
        } else {
            echo '<div class="filterly-no-results">';
            echo esc_html__( 'No results found.', 'filterly' );
            echo '</div>';
        }
        
        $results_html = ob_get_clean();
        wp_reset_postdata();
        
        // Generate filter state URL
        $filter_url = $this->url_handler->generate_filter_url( $sanitized_filter_data );
        
        // Return the response
        wp_send_json_success( array(
            'html'        => $results_html,
            'filter_url'  => $filter_url,
            'found_posts' => $query->found_posts,
            'max_pages'   => $query->max_num_pages,
        ) );
    }

    /**
     * Render pagination HTML.
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The query object.
     */
    private function render_pagination( $query ) {
        if ( $query->max_num_pages <= 1 ) {
            return;
        }
        
        echo '<nav class="filterly-pagination">';
        
        $big = 999999999;
        echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, $query->get( 'paged' ) ),
            'total'     => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ) );
        
        echo '</nav>';
    }

    /**
     * Shortcode handler for [filterly].
     *
     * @since    1.0.0
     * @param    array     $atts        Shortcode attributes.
     * @param    string    $content     Shortcode content.
     * @return   string    The shortcode output.
     */
    public function shortcode_handler( $atts, $content = null ) {
        $atts = shortcode_atts( array(
            'post_type'       => 'post',
            'filters'         => '',  // comma-separated list of filters to include
            'filters_exclude' => '',  // comma-separated list of filters to exclude
            'posts_per_page'  => get_option( 'posts_per_page' ),
            'show_count'      => 'true',
            'show_reset'      => 'true',
            'ajax_enabled'    => 'true',
            'pretty_urls'     => 'true',
            'auto_submit'     => 'true',
            'layout'          => 'standard', // standard, horizontal, sidebar
            'template'        => '',
        ), $atts, 'filterly' );
        
        // Generate a unique ID for this filter instance
        $filter_id = 'filterly-' . wp_rand( 1000, 9999 );
        
        // Initialize filters
        $filters = $this->get_filters_for_post_type( $atts['post_type'], $atts['filters'], $atts['filters_exclude'] );
        
        if ( empty( $filters ) ) {
            return '<div class="filterly-error">' . esc_html__( 'No filters available for this post type.', 'filterly' ) . '</div>';
        }
        
        // Get current filter values from URL
        $current_filter_values = $this->url_handler->get_current_filter_values();
        
        // Start output buffer
        ob_start();
        
        // Filter form
        echo '<div id="' . esc_attr( $filter_id ) . '" class="filterly-container filterly-layout-' . esc_attr( $atts['layout'] ) . '">';
        
        // Filter form
        include FILTERLY_PLUGIN_DIR . 'templates/filter-form.php';
        
        // Results container
        echo '<div class="filterly-results-container">';
        
        // Run the initial query
        $query_args = $this->query->build_query( $current_filter_values, array(
            'post_type'      => $atts['post_type'],
            'posts_per_page' => absint( $atts['posts_per_page'] ),
        ) );
        
        $query = new WP_Query( $query_args );
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // Use custom template if provided
                if ( ! empty( $atts['template'] ) && locate_template( $atts['template'] ) ) {
                    get_template_part( str_replace( '.php', '', $atts['template'] ) );
                } 
                // Use template based on post type
                elseif ( locate_template( 'content-' . $atts['post_type'] . '.php' ) ) {
                    get_template_part( 'content', $atts['post_type'] );
                } 
                // Fall back to default template
                else {
                    include FILTERLY_PLUGIN_DIR . 'templates/results-container.php';
                }
            }
            
            // Pagination
            $this->render_pagination( $query );
            
        } else {
            echo '<div class="filterly-no-results">';
            echo esc_html__( 'No results found.', 'filterly' );
            echo '</div>';
        }
        
        wp_reset_postdata();
        
        echo '</div>'; // .filterly-results-container
        echo '</div>'; // .filterly-container
        
        // Initialize the filter with JavaScript
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (typeof $.filterly === 'function') {
                    $('#<?php echo esc_js( $filter_id ); ?>').filterly({
                        ajaxEnabled: <?php echo $atts['ajax_enabled'] === 'true' ? 'true' : 'false'; ?>,
                        prettyUrls: <?php echo $atts['pretty_urls'] === 'true' ? 'true' : 'false'; ?>,
                        autoSubmit: <?php echo $atts['auto_submit'] === 'true' ? 'true' : 'false'; ?>,
                        postType: '<?php echo esc_js( $atts['post_type'] ); ?>',
                        postsPerPage: <?php echo esc_js( absint( $atts['posts_per_page'] ) ); ?>,
                        container: '.filterly-results-container'
                    });
                }
            });
        </script>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Get the available filters for a post type.
     *
     * @since    1.0.0
     * @param    string    $post_type         The post type.
     * @param    string    $include_filters   Comma-separated list of filters to include.
     * @param    string    $exclude_filters   Comma-separated list of filters to exclude.
     * @return   array     The available filters.
     */
    private function get_filters_for_post_type( $post_type, $include_filters = '', $exclude_filters = '' ) {
        $filters = array();
        
        // Get taxonomies for this post type
        $taxonomies = get_object_taxonomies( $post_type, 'objects' );
        
        if ( ! empty( $taxonomies ) ) {
            foreach ( $taxonomies as $taxonomy ) {
                // Skip internal taxonomies
                if ( ! $taxonomy->public ) {
                    continue;
                }
                
                $filters[ $taxonomy->name ] = new Filterly_Taxonomy_Filter( $taxonomy->name );
            }
        }
        
        // For WooCommerce products, add special filters
        if ( $post_type === 'product' && class_exists( 'WooCommerce' ) ) {
            // Add price filter
            $filters['price'] = new Filterly_Meta_Filter( '_price', __( 'Price', 'filterly' ), array(
                'display_type' => 'range',
                'data_type'    => 'numeric',
            ) );
            
            // Add product attributes
            $attributes = wc_get_attribute_taxonomies();
            
            if ( ! empty( $attributes ) ) {
                foreach ( $attributes as $attribute ) {
                    $filters[ 'pa_' . $attribute->attribute_name ] = new Filterly_Attribute_Filter( $attribute->attribute_name );
                }
            }
        }
        
        // Allow custom filters to be added by other plugins
        $filters = apply_filters( 'filterly_filters_for_post_type', $filters, $post_type );
        
        // Include only specific filters if requested
        if ( ! empty( $include_filters ) ) {
            $include_list = array_map( 'trim', explode( ',', $include_filters ) );
            $filters = array_intersect_key( $filters, array_flip( $include_list ) );
        }
        
        // Exclude specific filters if requested
        if ( ! empty( $exclude_filters ) ) {
            $exclude_list = array_map( 'trim', explode( ',', $exclude_filters ) );
            $filters = array_diff_key( $filters, array_flip( $exclude_list ) );
        }
        
        return $filters;
    }
}