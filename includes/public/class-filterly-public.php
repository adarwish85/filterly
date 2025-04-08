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
     * The active filters collection.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $filters    The active filter objects.
     */
    private $filters = array();

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
        
        // Initialize the default filters
        $this->initialize_filters();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            FILTERLY_PLUGIN_URL . 'assets/css/public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            FILTERLY_PLUGIN_URL . 'assets/js/public.js',
            array( 'jquery', 'jquery-ui-slider' ),
            $this->version,
            true
        );
        
        // Localize the script with data needed for AJAX
        wp_localize_script(
            $this->plugin_name,
            'filterly_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'filterly_filter' ),
            )
        );
    }

    /**
     * Initialize the filters collection.
     *
     * @since    1.0.0
     */
    private function initialize_filters() {
        // Get filter settings from options
        $filter_settings = get_option( 'filterly_filters', array() );
        
        if ( empty( $filter_settings ) ) {
            // If no settings, create some default filters for demonstration
            $this->create_default_filters();
            return;
        }
        
        // Create filter objects based on settings
        foreach ( $filter_settings as $filter_data ) {
            $filter = $this->create_filter_from_data( $filter_data );
            
            if ( $filter ) {
                $this->filters[$filter->get_id()] = $filter;
            }
        }
    }

    /**
     * Create default filters for demonstration.
     *
     * @since    1.0.0
     */
    private function create_default_filters() {
        // Add default category filter
        if ( taxonomy_exists( 'category' ) ) {
            $this->filters['category'] = new Filterly_Taxonomy_Filter( 'category', __( 'Categories', 'filterly' ) );
        }
        
        // Add default tag filter
        if ( taxonomy_exists( 'post_tag' ) ) {
            $this->filters['post_tag'] = new Filterly_Taxonomy_Filter( 'post_tag', __( 'Tags', 'filterly' ) );
        }
        
        // Add WooCommerce product category filter
        if ( taxonomy_exists( 'product_cat' ) ) {
            $this->filters['product_cat'] = new Filterly_Taxonomy_Filter(
                'product_cat',
                __( 'Product Categories', 'filterly' ),
                array( 'hierarchical' => true )
            );
        }
        
        // Add WooCommerce product attribute filters
        if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            
            if ( ! empty( $attribute_taxonomies ) ) {
                foreach ( $attribute_taxonomies as $taxonomy ) {
                    $this->filters['pa_' . $taxonomy->attribute_name] = new Filterly_Attribute_Filter(
                        $taxonomy->attribute_name,
                        $taxonomy->attribute_label
                    );
                }
            }
        }
    }

    /**
     * Create a filter object from filter data.
     *
     * @since    1.0.0
     * @param    array    $filter_data    The filter configuration data.
     * @return   Filterly_Filter_Base|null    The created filter object or null.
     */
    private function create_filter_from_data( $filter_data ) {
        if ( empty( $filter_data['type'] ) ) {
            return null;
        }
        
        switch ( $filter_data['type'] ) {
            case 'taxonomy':
                return new Filterly_Taxonomy_Filter(
                    $filter_data['taxonomy'],
                    $filter_data['label'],
                    isset( $filter_data['options'] ) ? $filter_data['options'] : array()
                );
                
            case 'meta':
                return new Filterly_Meta_Filter(
                    $filter_data['meta_key'],
                    $filter_data['label'],
                    isset( $filter_data['options'] ) ? $filter_data['options'] : array()
                );
                
            case 'attribute':
                if ( class_exists( 'WooCommerce' ) ) {
                    return new Filterly_Attribute_Filter(
                        $filter_data['attribute'],
                        $filter_data['label'],
                        isset( $filter_data['options'] ) ? $filter_data['options'] : array()
                    );
                }
                break;
                
            case 'variation':
                if ( class_exists( 'WooCommerce' ) && class_exists( 'Filterly_Variation_Filter' ) ) {
                    return new Filterly_Variation_Filter(
                        $filter_data['variation_key'],
                        $filter_data['label'],
                        isset( $filter_data['options'] ) ? $filter_data['options'] : array()
                    );
                }
                break;
        }
        
        return null;
    }

    /**
     * Handle the filterly shortcode.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Rendered shortcode HTML.
     */
    public function shortcode_handler( $atts ) {
        $attributes = shortcode_atts(
            array(
                'post_type'    => 'post',
                'filters'      => '',
                'target'       => '',
                'per_page'     => get_option( 'posts_per_page' ),
                'columns'      => 3,
                'show_count'   => 'yes',
                'ajax'         => 'yes',
                'reset_button' => 'yes',
            ),
            $atts
        );
        
        // Get the selected filters
        $filter_ids = array();
        if ( ! empty( $attributes['filters'] ) ) {
            $filter_ids = array_map( 'trim', explode( ',', $attributes['filters'] ) );
        }
        
        // If no specific filters selected, use all available filters
        if ( empty( $filter_ids ) ) {
            $filter_ids = array_keys( $this->filters );
        }
        
        // Get the currently selected filter values from URL parameters
        $selected_values = $this->get_selected_filter_values();
        
        // Start output buffering
        ob_start();
        
        // Render the filter form
        $this->render_filter_form( $filter_ids, $selected_values, $attributes );
        
        // If a target is specified, we don't display results here
        if ( empty( $attributes['target'] ) ) {
            $this->render_filtered_results( $attributes, $selected_values );
        }
        
        return ob_get_clean();
    }

    /**
     * Render the filter form.
     *
     * @since    1.0.0
     * @param    array     $filter_ids       IDs of filters to display.
     * @param    array     $selected_values  Currently selected filter values.
     * @param    array     $attributes       Shortcode attributes.
     */
    private function render_filter_form( $filter_ids, $selected_values, $attributes ) {
        $target_id = ! empty( $attributes['target'] ) ? $attributes['target'] : 'filterly-results-' . uniqid();
        $ajax = ( $attributes['ajax'] === 'yes' );
        
        // Add form attributes
        $form_atts = array(
            'class'            => 'filterly-filter-form',
            'data-target'      => $target_id,
            'data-post-type'   => $attributes['post_type'],
            'data-per-page'    => $attributes['per_page'],
            'data-columns'     => $attributes['columns'],
            'data-ajax'        => $ajax ? 'true' : 'false',
        );
        
        include FILTERLY_PLUGIN_DIR . 'templates/filter-form.php';
    }

    /**
     * Render the filtered results.
     *
     * @since    1.0.0
     * @param    array     $attributes       Shortcode attributes.
     * @param    array     $selected_values  Currently selected filter values.
     */
    private function render_filtered_results( $attributes, $selected_values ) {
        $target_id = 'filterly-results-' . uniqid();
        
        echo '<div id="' . esc_attr( $target_id ) . '" class="filterly-results" data-post-type="' . esc_attr( $attributes['post_type'] ) . '">';
        
        // Create a WP_Query with the filter values
        $query_args = array(
            'post_type'      => $attributes['post_type'],
            'posts_per_page' => $attributes['per_page'],
            'post_status'    => 'publish',
        );
        
        // Apply filters to the query
        $query = $this->apply_filters_to_query( new WP_Query(), $selected_values, $query_args );
        
        // Execute the query
        $query->query( $query_args );
        
        // Display results based on post type
        if ( $attributes['post_type'] === 'product' && function_exists( 'woocommerce_product_loop_start' ) ) {
            // WooCommerce product display
            woocommerce_product_loop_start();
            
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
            } else {
                echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'filterly' ) . '</p>';
            }
            
            woocommerce_product_loop_end();
            
            // Pagination
            woocommerce_pagination();
            
        } else {
            // Standard WordPress post display
            if ( $query->have_posts() ) {
                echo '<div class="filterly-posts columns-' . esc_attr( $attributes['columns'] ) . '">';
                
                while ( $query->have_posts() ) {
                    $query->the_post();
                    include FILTERLY_PLUGIN_DIR . 'templates/content-post.php';
                }
                
                echo '</div>';
                
                // Pagination
                $this->render_pagination( $query );
                
            } else {
                echo '<p>' . esc_html__( 'No posts found', 'filterly' ) . '</p>';
            }
        }
        
        wp_reset_postdata();
        
        echo '</div>'; // .filterly-results
    }

    /**
     * Handle AJAX filter requests.
     *
     * @since    1.0.0
     */
    public function process_ajax_filter() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly_filter' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
            exit;
        }
        
        // Get filter values from POST data
        $filter_values = isset( $_POST['filters'] ) ? $this->sanitize_filter_values( $_POST['filters'] ) : array();
        
        // Get query parameters
        $post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( $_POST['post_type'] ) : 'post';
        $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : get_option( 'posts_per_page' );
        $paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
        $columns = isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3;
        
        // Create query args
        $query_args = array(
            'post_type'      => $post_type,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_status'    => 'publish',
        );
        
        // Apply filters to query
        $query = $this->apply_filters_to_query( new WP_Query(), $filter_values, $query_args );
        
        // Execute query
        $query->query( $query_args );
        
        // Start output buffering to capture the HTML
        ob_start();
        
        // Display results based on post type
        if ( $post_type === 'product' && function_exists( 'woocommerce_product_loop_start' ) ) {
            // WooCommerce product display
            woocommerce_product_loop_start();
            
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    wc_get_template_part( 'content', 'product' );
                }
            } else {
                echo '<p class="woocommerce-info">' . esc_html__( 'No products found', 'filterly' ) . '</p>';
            }
            
            woocommerce_product_loop_end();
            
            // Pagination
            woocommerce_pagination();
            
        } else {
            // Standard WordPress post display
            if ( $query->have_posts() ) {
                echo '<div class="filterly-posts columns-' . esc_attr( $columns ) . '">';
                
                while ( $query->have_posts() ) {
                    $query->the_post();
                    include FILTERLY_PLUGIN_DIR . 'templates/content-post.php';
                }
                
                echo '</div>';
                
                // Pagination
                $this->render_pagination( $query );
                
            } else {
                echo '<p>' . esc_html__( 'No posts found', 'filterly' ) . '</p>';
            }
        }
        
        wp_reset_postdata();
        
        // Get the buffered content
        $content = ob_get_clean();
        
        // Send the response
        wp_send_json_success( array(
            'content' => $content,
            'found_posts' => $query->found_posts,
            'max_num_pages' => $query->max_num_pages,
            'current_page' => $paged,
        ) );
        
        exit;
    }

    /**
     * Get selected filter values from URL parameters.
     *
     * @since    1.0.0
     * @return   array    Array of filter values keyed by filter ID.
     */
    private function get_selected_filter_values() {
        $values = array();
        
        // Get filter values from query string
        foreach ( $_GET as $key => $value ) {
            // If the filter exists in our collection
            if ( isset( $this->filters[$key] ) ) {
                // Ensure the value is an array
                if ( ! is_array( $value ) ) {
                    $value = array( $value );
                }
                
                // Sanitize the values
                $clean_values = array();
                foreach ( $value as $val ) {
                    $clean_values[] = sanitize_text_field( $val );
                }
                
                $values[$key] = $clean_values;
            }
        }
        
        return $values;
    }

    /**
     * Sanitize filter values from POST data.
     *
     * @since    1.0.0
     * @param    array    $dirty_values    The unsanitized filter values.
     * @return   array    Sanitized filter values.
     */
    private function sanitize_filter_values( $dirty_values ) {
        $clean_values = array();
        
        if ( ! is_array( $dirty_values ) ) {
            return $clean_values;
        }
        
        foreach ( $dirty_values as $filter_id => $values ) {
            $filter_id = sanitize_key( $filter_id );
            
            if ( ! isset( $this->filters[$filter_id] ) ) {
                continue;
            }
            
            // Ensure values is an array
            if ( ! is_array( $values ) ) {
                $values = array( $values );
            }
            
            // Sanitize each value
            $clean_filter_values = array();
            foreach ( $values as $value ) {
                $clean_filter_values[] = sanitize_text_field( $value );
            }
            
            $clean_values[$filter_id] = $clean_filter_values;
        }
        
        return $clean_values;
    }

    /**
     * Apply filters to a WP_Query object.
     *
     * @since    1.0.0
     * @param    WP_Query    $query           The query to modify.
     * @param    array       $filter_values   The selected filter values.
     * @param    array       $query_args      Additional query args to set.
     * @return   WP_Query    The modified query.
     */
    public function apply_filters_to_query( $query, $filter_values, $query_args = array() ) {
        // Apply any additional query args first
        foreach ( $query_args as $key => $value ) {
            $query->set( $key, $value );
        }
        
        // Apply each active filter to the query
        foreach ( $filter_values as $filter_id => $values ) {
            if ( isset( $this->filters[$filter_id] ) && ! empty( $values ) ) {
                $query = $this->filters[$filter_id]->apply_to_query( $query, $values );
            }
        }
        
        return $query;
    }

    /**
     * Render pagination for query results.
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The query object.
     */
    private function render_pagination( $query ) {
        $big = 999999999; // need an unlikely integer
        
        echo '<nav class="filterly-pagination">';
        echo paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, get_query_var( 'paged' ) ),
            'total'     => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ) );
        echo '</nav>';
    }
}