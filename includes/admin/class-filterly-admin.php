<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 */
class Filterly_Admin {

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
     * The settings instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Filterly_Settings    $settings    The settings instance.
     */
    private $settings;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Load settings class
        require_once FILTERLY_PLUGIN_DIR . 'includes/admin/class-filterly-settings.php';
        $this->settings = new Filterly_Settings( $plugin_name, $version );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if ( $screen && strpos( $screen->id, 'filterly' ) !== false ) {
            wp_enqueue_style(
                $this->plugin_name . '-admin',
                FILTERLY_PLUGIN_URL . 'includes/admin/css/filterly-admin.css',
                array(),
                $this->version,
                'all'
            );
            
            // WordPress color picker
            wp_enqueue_style( 'wp-color-picker' );
            
            // jQuery UI styles for sliders, tabs, etc.
            wp_enqueue_style(
                'jquery-ui-styles',
                'https://code.jquery.com/ui/1.13.1/themes/smoothness/jquery-ui.css',
                array(),
                '1.13.1',
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if ( $screen && strpos( $screen->id, 'filterly' ) !== false ) {
            // WordPress scripts
            wp_enqueue_script( 'jquery-ui-tabs' );
            wp_enqueue_script( 'jquery-ui-sortable' );
            wp_enqueue_script( 'jquery-ui-slider' );
            wp_enqueue_script( 'wp-color-picker' );
            
            // Our admin script
            wp_enqueue_script(
                $this->plugin_name . '-admin',
                FILTERLY_PLUGIN_URL . 'includes/admin/js/filterly-admin.js',
                array( 'jquery', 'jquery-ui-tabs', 'jquery-ui-sortable', 'jquery-ui-slider', 'wp-color-picker' ),
                $this->version,
                true
            );
            
            // Pass data to script
            wp_localize_script(
                $this->plugin_name . '-admin',
                'filterly_admin_params',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => wp_create_nonce( 'filterly-admin-nonce' ),
                    'strings' => array(
                        'confirm_delete' => __( 'Are you sure you want to delete this filter? This action cannot be undone.', 'filterly' ),
                        'saving' => __( 'Saving...', 'filterly' ),
                        'saved' => __( 'Saved!', 'filterly' ),
                        'error' => __( 'Error saving. Please try again.', 'filterly' ),
                        'preview_loading' => __( 'Loading preview...', 'filterly' ),
                    )
                )
            );
        }
    }

    /**
     * Add menu pages.
     *
     * @since    1.0.0
     */
    public function add_menu_pages() {
        // Main menu item
        add_menu_page(
            __( 'Filterly', 'filterly' ),
            __( 'Filterly', 'filterly' ),
            'manage_options',
            'filterly',
            array( $this, 'render_settings_page' ),
            'dashicons-filter',
            25
        );
        
        // Filters submenu (same as main page)
        add_submenu_page(
            'filterly',
            __( 'Filters', 'filterly' ),
            __( 'Filters', 'filterly' ),
            'manage_options',
            'filterly',
            array( $this, 'render_settings_page' )
        );
        
        // Settings submenu
        add_submenu_page(
            'filterly',
            __( 'Settings', 'filterly' ),
            __( 'Settings', 'filterly' ),
            'manage_options',
            'filterly-settings',
            array( $this, 'render_settings_page' )
        );
        
        // Advanced submenu
        add_submenu_page(
            'filterly',
            __( 'Advanced', 'filterly' ),
            __( 'Advanced', 'filterly' ),
            'manage_options',
            'filterly-advanced',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        // Include settings page template
        require_once FILTERLY_PLUGIN_DIR . 'includes/admin/views/settings-page.php';
    }

    /**
     * Register settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Let the settings class handle this
        $this->settings->register_settings();
    }

    /**
     * Handle AJAX filter add/edit.
     *
     * @since    1.0.0
     */
    public function ajax_filter_save() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get filter data and sanitize
        $filter_data = isset( $_POST['filter'] ) ? $this->sanitize_filter_data( $_POST['filter'] ) : array();
        
        if ( empty( $filter_data ) || empty( $filter_data['type'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid filter data', 'filterly' ) ) );
        }
        
        // Get existing filters
        $filters = get_option( 'filterly_filter_settings', array() );
        
        // Check if this is an edit or a new filter
        if ( ! empty( $filter_data['id'] ) ) {
            // Find and update existing filter
            $filter_id = $filter_data['id'];
            $filter_index = -1;
            
            foreach ( $filters as $index => $filter ) {
                if ( isset( $filter['id'] ) && $filter['id'] === $filter_id ) {
                    $filter_index = $index;
                    break;
                }
            }
            
            if ( $filter_index >= 0 ) {
                $filters[ $filter_index ] = $filter_data;
            } else {
                // Not found, add as new
                $filters[] = $filter_data;
            }
        } else {
            // Add new filter with unique ID
            $filter_data['id'] = 'filter_' . uniqid();
            $filters[] = $filter_data;
        }
        
        // Save filters
        update_option( 'filterly_filter_settings', $filters );
        
        // Return success response
        wp_send_json_success( array(
            'message' => __( 'Filter saved successfully', 'filterly' ),
            'filter' => $filter_data,
            'filters' => $filters
        ) );
    }

    /**
     * Handle AJAX filter delete.
     *
     * @since    1.0.0
     */
    public function ajax_filter_delete() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get filter ID
        $filter_id = isset( $_POST['filter_id'] ) ? sanitize_text_field( $_POST['filter_id'] ) : '';
        
        if ( empty( $filter_id ) ) {
            wp_send_json_error( array( 'message' => __( 'No filter specified', 'filterly' ) ) );
        }
        
        // Get existing filters
        $filters = get_option( 'filterly_filter_settings', array() );
        
        // Find and remove the filter
        $filter_index = -1;
        
        foreach ( $filters as $index => $filter ) {
            if ( isset( $filter['id'] ) && $filter['id'] === $filter_id ) {
                $filter_index = $index;
                break;
            }
        }
        
        if ( $filter_index >= 0 ) {
            array_splice( $filters, $filter_index, 1 );
            update_option( 'filterly_filter_settings', $filters );
            
            wp_send_json_success( array(
                'message' => __( 'Filter deleted successfully', 'filterly' ),
                'filters' => $filters
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Filter not found', 'filterly' ) ) );
        }
    }

    /**
     * Handle AJAX filter order update.
     *
     * @since    1.0.0
     */
    public function ajax_filter_order() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get filter IDs in new order
        $filter_ids = isset( $_POST['filter_ids'] ) ? $_POST['filter_ids'] : array();
        
        if ( empty( $filter_ids ) || ! is_array( $filter_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid order data', 'filterly' ) ) );
        }
        
        // Sanitize filter IDs
        $filter_ids = array_map( 'sanitize_text_field', $filter_ids );
        
        // Get existing filters
        $filters = get_option( 'filterly_filter_settings', array() );
        
        // Create a new ordered array
        $new_filters = array();
        
        // Add filters in the new order
        foreach ( $filter_ids as $id ) {
            foreach ( $filters as $filter ) {
                if ( isset( $filter['id'] ) && $filter['id'] === $id ) {
                    $new_filters[] = $filter;
                    break;
                }
            }
        }
        
        // Save the new order
        update_option( 'filterly_filter_settings', $new_filters );
        
        wp_send_json_success( array(
            'message' => __( 'Filter order updated', 'filterly' ),
            'filters' => $new_filters
        ) );
    }

    /**
     * Handle AJAX filter preview.
     *
     * @since    1.0.0
     */
    public function ajax_filter_preview() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get filter data
        $filter_data = isset( $_POST['filter'] ) ? $this->sanitize_filter_data( $_POST['filter'] ) : array();
        
        if ( empty( $filter_data ) || empty( $filter_data['type'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid filter data', 'filterly' ) ) );
        }
        
        // Create temporary filter instance based on type
        $filter = null;
        
        switch ( $filter_data['type'] ) {
            case 'taxonomy':
                if ( ! empty( $filter_data['taxonomy'] ) ) {
                    $filter = new Filterly_Taxonomy_Filter(
                        $filter_data['taxonomy'],
                        $filter_data['label'] ?? '',
                        $filter_data['options'] ?? array()
                    );
                }
                break;
                
            case 'meta':
                if ( ! empty( $filter_data['meta_key'] ) ) {
                    $filter = new Filterly_Meta_Filter(
                        $filter_data['meta_key'],
                        $filter_data['label'] ?? '',
                        $filter_data['options'] ?? array()
                    );
                }
                break;
                
            case 'attribute':
                if ( ! empty( $filter_data['attribute'] ) ) {
                    $filter = new Filterly_Attribute_Filter(
                        $filter_data['attribute'],
                        $filter_data['label'] ?? '',
                        $filter_data['options'] ?? array()
                    );
                }
                break;
                
            case 'variation':
                if ( ! empty( $filter_data['attribute'] ) ) {
                    $filter = new Filterly_Variation_Filter(
                        $filter_data['attribute'],
                        $filter_data['label'] ?? '',
                        $filter_data['options'] ?? array()
                    );
                }
                break;
                
            default:
                // Try to get a custom filter type
                $filter = apply_filters( 'filterly_preview_filter', null, $filter_data );
                break;
        }
        
        if ( ! $filter ) {
            wp_send_json_error( array( 'message' => __( 'Could not create filter preview', 'filterly' ) ) );
        }
        
        // Generate preview HTML
        $preview_html = $filter->render();
        
        wp_send_json_success( array(
            'preview_html' => $preview_html
        ) );
    }

    /**
     * Sanitize filter data.
     *
     * @since    1.0.0
     * @param    array    $data    Raw filter data.
     * @return   array    Sanitized filter data.
     */
    private function sanitize_filter_data( $data ) {
        $sanitized = array();
        
        // Basic fields
        if ( isset( $data['id'] ) ) {
            $sanitized['id'] = sanitize_key( $data['id'] );
        }
        
        if ( isset( $data['type'] ) ) {
            $sanitized['type'] = sanitize_key( $data['type'] );
        }
        
        if ( isset( $data['label'] ) ) {
            $sanitized['label'] = sanitize_text_field( $data['label'] );
        }
        
        // Type-specific fields
        if ( $sanitized['type'] === 'taxonomy' && isset( $data['taxonomy'] ) ) {
            $sanitized['taxonomy'] = sanitize_key( $data['taxonomy'] );
        }
        
        if ( $sanitized['type'] === 'meta' && isset( $data['meta_key'] ) ) {
            $sanitized['meta_key'] = sanitize_key( $data['meta_key'] );
        }
        
        if ( in_array( $sanitized['type'], array( 'attribute', 'variation' ) ) && isset( $data['attribute'] ) ) {
            $sanitized['attribute'] = sanitize_key( $data['attribute'] );
        }
        
        // Options
        if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
            $sanitized['options'] = array();
            
            foreach ( $data['options'] as $key => $value ) {
                $key = sanitize_key( $key );
                
                if ( is_array( $value ) ) {
                    $sanitized['options'][ $key ] = array_map( function( $item ) {
                        return is_numeric( $item ) ? absint( $item ) : sanitize_text_field( $item );
                    }, $value );
                } elseif ( is_bool( $value ) ) {
                    $sanitized['options'][ $key ] = (bool) $value;
                } elseif ( is_numeric( $value ) ) {
                    $sanitized['options'][ $key ] = is_int( $value ) ? absint( $value ) : floatval( $value );
                } else {
                    $sanitized['options'][ $key ] = sanitize_text_field( $value );
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * Import filters from JSON.
     *
     * @since    1.0.0
     */
    public function ajax_import_filters() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get JSON data
        $json_data = isset( $_POST['import_data'] ) ? stripslashes( $_POST['import_data'] ) : '';
        
        if ( empty( $json_data ) ) {
            wp_send_json_error( array( 'message' => __( 'No import data provided', 'filterly' ) ) );
        }
        
        // Decode JSON
        $filters = json_decode( $json_data, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $filters ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Invalid JSON data', 'filterly' ),
                'error' => json_last_error_msg()
            ) );
        }
        
        // Validate filters
        $validated_filters = array();
        
        foreach ( $filters as $filter ) {
            if ( ! isset( $filter['type'] ) ) {
                continue;
            }
            
            // Sanitize and add
            $validated_filters[] = $this->sanitize_filter_data( $filter );
        }
        
        if ( empty( $validated_filters ) ) {
            wp_send_json_error( array( 'message' => __( 'No valid filters found in import data', 'filterly' ) ) );
        }
        
        // Save imported filters
        update_option( 'filterly_filter_settings', $validated_filters );
        
        wp_send_json_success( array(
            'message' => sprintf( 
                __( 'Successfully imported %d filters', 'filterly' ),
                count( $validated_filters )
            ),
            'filters' => $validated_filters
        ) );
    }

    /**
     * Export filters as JSON.
     *
     * @since    1.0.0
     */
    public function ajax_export_filters() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'filterly-admin-nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed', 'filterly' ) ) );
        }
        
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to do this', 'filterly' ) ) );
        }
        
        // Get filters
        $filters = get_option( 'filterly_filter_settings', array() );
        
        // Convert to JSON
        $json = wp_json_encode( $filters, JSON_PRETTY_PRINT );
        
        wp_send_json_success( array(
            'export_data' => $json
        ) );
    }
}