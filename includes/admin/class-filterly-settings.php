<?php
/**
 * The settings functionality of the plugin.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The settings functionality of the plugin.
 */
class Filterly_Settings {

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
     * The settings fields.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $settings    The settings fields.
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
        $this->settings = $this->get_settings_fields();
    }

    /**
     * Register all settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings
        register_setting(
            'filterly_settings',
            'filterly_general_settings',
            array( $this, 'sanitize_general_settings' )
        );
        
        register_setting(
            'filterly_settings',
            'filterly_advanced_settings',
            array( $this, 'sanitize_advanced_settings' )
        );
        
        // Register sections
        add_settings_section(
            'filterly_general_section',
            __( 'General Settings', 'filterly' ),
            array( $this, 'render_general_section' ),
            'filterly_settings'
        );
        
        add_settings_section(
            'filterly_advanced_section',
            __( 'Advanced Settings', 'filterly' ),
            array( $this, 'render_advanced_section' ),
            'filterly_settings'
        );
        
        // Register general settings fields
        foreach ( $this->settings['general'] as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['label'],
                array( $this, 'render_field' ),
                'filterly_settings',
                'filterly_general_section',
                array(
                    'id' => $field_id,
                    'section' => 'general',
                    'field' => $field,
                )
            );
        }
        
        // Register advanced settings fields
        foreach ( $this->settings['advanced'] as $field_id => $field ) {
            add_settings_field(
                $field_id,
                $field['label'],
                array( $this, 'render_field' ),
                'filterly_settings',
                'filterly_advanced_section',
                array(
                    'id' => $field_id,
                    'section' => 'advanced',
                    'field' => $field,
                )
            );
        }
    }

    /**
     * Get settings fields.
     *
     * @since    1.0.0
     * @return   array    The settings fields.
     */
    private function get_settings_fields() {
        return array(
            'general' => array(
                'use_ajax' => array(
                    'label' => __( 'Use AJAX Filtering', 'filterly' ),
                    'description' => __( 'Enable AJAX-based filtering for a smoother user experience without page reloads.', 'filterly' ),
                    'type' => 'checkbox',
                    'default' => true,
                ),
                'use_pretty_urls' => array(
                    'label' => __( 'Use Pretty URLs', 'filterly' ),
                    'description' => __( 'Generate clean, SEO-friendly URLs for filtered results.', 'filterly' ),
                    'type' => 'checkbox',
                    'default' => true,
                ),
                'mobile_behavior' => array(
                    'label' => __( 'Mobile Behavior', 'filterly' ),
                    'description' => __( 'Control how filters appear on mobile devices.', 'filterly' ),
                    'type' => 'select',
                    'options' => array(
                        'normal' => __( 'Normal (always visible)', 'filterly' ),
                        'toggle' => __( 'Toggleable (show/hide button)', 'filterly' ),
                        'offcanvas' => __( 'Off-canvas (slide-in panel)', 'filterly' ),
                    ),
                    'default' => 'toggle',
                ),
                'filter_position' => array(
                    'label' => __( 'Filter Position', 'filterly' ),
                    'description' => __( 'Where to display filters in relation to results.', 'filterly' ),
                    'type' => 'select',
                    'options' => array(
                        'left' => __( 'Left sidebar', 'filterly' ),
                        'right' => __( 'Right sidebar', 'filterly' ),
                        'top' => __( 'Above results', 'filterly' ),
                    ),
                    'default' => 'left',
                ),
            ),
            'advanced' => array(
                'cache_results' => array(
                    'label' => __( 'Cache Filter Choices', 'filterly' ),
                    'description' => __( 'Cache filter choices to improve performance.', 'filterly' ),
                    'type' => 'checkbox',
                    'default' => true,
                ),
                'cache_duration' => array(
                    'label' => __( 'Cache Duration', 'filterly' ),
                    'description' => __( 'How long to cache filter choices (in hours).', 'filterly' ),
                    'type' => 'number',
                    'min' => 1,
                    'max' => 168,
                    'default' => 24,
                ),
                'disable_styles' => array(
                    'label' => __( 'Disable Plugin Styles', 'filterly' ),
                    'description' => __( 'Disable the built-in styles if you want to use your own custom CSS.', 'filterly' ),
                    'type' => 'checkbox',
                    'default' => false,
                ),
                'query_limit' => array(
                    'label' => __( 'Query Size Limit', 'filterly' ),
                    'description' => __( 'Maximum number of items to query for filter choices.', 'filterly' ),
                    'type' => 'number',
                    'min' => 100,
                    'max' => 5000,
                    'default' => 1000,
                ),
                'custom_css' => array(
                    'label' => __( 'Custom CSS', 'filterly' ),
                    'description' => __( 'Add custom CSS to customize the appearance.', 'filterly' ),
                    'type' => 'textarea',
                    'default' => '',
                ),
            ),
        );
    }

    /**
     * Render a settings field.
     *
     * @since    1.0.0
     * @param    array    $args    Arguments for the field.
     */
    public function render_field( $args ) {
        $id = $args['id'];
        $section = $args['section'];
        $field = $args['field'];
        
        // Get current value
        $option_name = 'filterly_' . $section . '_settings';
        $options = get_option( $option_name, array() );
        $value = isset( $options[ $id ] ) ? $options[ $id ] : $field['default'];
        
        // Field name
        $name = $option_name . '[' . $id . ']';
        
        // Render based on type
        switch ( $field['type'] ) {
            case 'checkbox':
                echo '<label>';
                echo '<input type="checkbox" name="' . esc_attr( $name ) . '" ' . checked( $value, true, false ) . ' value="1">';
                echo '<span class="filterly-field-description">' . esc_html( $field['description'] ) . '</span>';
                echo '</label>';
                break;
                
            case 'text':
                echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
                break;
                
            case 'number':
                $min = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
                $max = isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
                $step = isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : '';
                
                echo '<input type="number" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . $min . $max . $step . ' class="small-text">';
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
                break;
                
            case 'select':
                echo '<select name="' . esc_attr( $name ) . '">';
                
                foreach ( $field['options'] as $option_value => $option_label ) {
                    echo '<option value="' . esc_attr( $option_value ) . '" ' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
                }
                
                echo '</select>';
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
                break;
                
            case 'textarea':
                echo '<textarea name="' . esc_attr( $name ) . '" rows="5" cols="50" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
                break;
                
            case 'color':
                echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" class="filterly-colorpicker">';
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
                break;
        }
    }

    /**
     * Render general section.
     *
     * @since    1.0.0
     */
    public function render_general_section() {
        echo '<p>' . esc_html__( 'Configure general plugin settings that control how filters behave and appear.', 'filterly' ) . '</p>';
    }

    /**
     * Render advanced section.
     *
     * @since    1.0.0
     */
    public function render_advanced_section() {
        echo '<p>' . esc_html__( 'Advanced settings for performance optimization and customization.', 'filterly' ) . '</p>';
    }

    /**
     * Sanitize general settings.
     *
     * @since    1.0.0
     * @param    array    $input    The input values.
     * @return   array    Sanitized values.
     */
    public function sanitize_general_settings( $input ) {
        $sanitized = array();
        $fields = $this->settings['general'];
        
        foreach ( $fields as $field_id => $field ) {
            switch ( $field['type'] ) {
                case 'checkbox':
                    $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? true : false;
                    break;
                    
                case 'text':
                    $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? sanitize_text_field( $input[ $field_id ] ) : '';
                    break;
                    
                case 'number':
                    $value = isset( $input[ $field_id ] ) ? absint( $input[ $field_id ] ) : $field['default'];
                    
                    if ( isset( $field['min'] ) && $value < $field['min'] ) {
                        $value = $field['min'];
                    }
                    
                    if ( isset( $field['max'] ) && $value > $field['max'] ) {
                        $value = $field['max'];
                    }
                    
                    $sanitized[ $field_id ] = $value;
                    break;
                    
                case 'select':
                    if ( isset( $input[ $field_id ] ) && array_key_exists( $input[ $field_id ], $field['options'] ) ) {
                        $sanitized[ $field_id ] = $input[ $field_id ];
                    } else {
                        $sanitized[ $field_id ] = $field['default'];
                    }
                    break;
                    
                default:
                    if ( isset( $input[ $field_id ] ) ) {
                        $sanitized[ $field_id ] = sanitize_text_field( $input[ $field_id ] );
                    } else {
                        $sanitized[ $field_id ] = $field['default'];
                    }
                    break;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize advanced settings.
     *
     * @since    1.0.0
     * @param    array    $input    The input values.
     * @return   array    Sanitized values.
     */
    public function sanitize_advanced_settings( $input ) {
        $sanitized = array();
        $fields = $this->settings['advanced'];
        
        foreach ( $fields as $field_id => $field ) {
            switch ( $field['type'] ) {
                case 'checkbox':
                    $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? true : false;
                    break;
                    
                case 'text':
                    $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? sanitize_text_field( $input[ $field_id ] ) : '';
                    break;
                    
                case 'number':
                    $value = isset( $input[ $field_id ] ) ? absint( $input[ $field_id ] ) : $field['default'];
                    
                    if ( isset( $field['min'] ) && $value < $field['min'] ) {
                        $value = $field['min'];
                    }
                    
                    if ( isset( $field['max'] ) && $value > $field['max'] ) {
                        $value = $field['max'];
                    }
                    
                    $sanitized[ $field_id ] = $value;
                    break;
                    
                case 'select':
                    if ( isset( $input[ $field_id ] ) && array_key_exists( $input[ $field_id ], $field['options'] ) ) {
                        $sanitized[ $field_id ] = $input[ $field_id ];
                    } else {
                        $sanitized[ $field_id ] = $field['default'];
                    }
                    break;
                    
                case 'textarea':
                    if ( $field_id === 'custom_css' ) {
                        $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? wp_strip_all_tags( $input[ $field_id ] ) : '';
                    } else {
                        $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? sanitize_textarea_field( $input[ $field_id ] ) : '';
                    }
                    break;
                    
                case 'color':
                    $sanitized[ $field_id ] = isset( $input[ $field_id ] ) ? sanitize_hex_color( $input[ $field_id ] ) : '';
                    break;
                    
                default:
                    if ( isset( $input[ $field_id ] ) ) {
                        $sanitized[ $field_id ] = sanitize_text_field( $input[ $field_id ] );
                    } else {
                        $sanitized[ $field_id ] = $field['default'];
                    }
                    break;
            }
        }
        
        return $sanitized;
    }

    /**
     * Get all post types for selection.
     *
     * @since    1.0.0
     * @return   array    The post types.
     */
    public function get_post_types() {
        $post_types = get_post_types( array(
            'public' => true,
        ), 'objects' );
        
        $options = array();
        
        foreach ( $post_types as $post_type ) {
            $options[ $post_type->name ] = $post_type->labels->singular_name;
        }
        
        return $options;
    }

    /**
     * Get all taxonomies for selection.
     *
     * @since    1.0.0
     * @return   array    The taxonomies.
     */
    public function get_taxonomies() {
        $taxonomies = get_taxonomies( array(
            'public' => true,
        ), 'objects' );
        
        $options = array();
        
        foreach ( $taxonomies as $taxonomy ) {
            $options[ $taxonomy->name ] = $taxonomy->labels->singular_name;
        }
        
        return $options;
    }

    /**
     * Get WooCommerce product attributes if available.
     *
     * @since    1.0.0
     * @return   array    The product attributes.
     */
    public function get_product_attributes() {
        $options = array();
        
        if ( ! class_exists( 'WooCommerce' ) ) {
            return $options;
        }
        
        $attribute_taxonomies = wc_get_attribute_taxonomies();
        
        if ( $attribute_taxonomies ) {
            foreach ( $attribute_taxonomies as $tax ) {
                $options[ 'pa_' . $tax->attribute_name ] = $tax->attribute_label;
            }
        }
        
        return $options;
    }

    /**
     * Get available meta keys.
     *
     * @since    1.0.0
     * @return   array    The meta keys.
     */
    public function get_meta_keys() {
        global $wpdb;
        
        $query = "
            SELECT DISTINCT meta_key
            FROM $wpdb->postmeta
            WHERE meta_key NOT LIKE '\_%'
            ORDER BY meta_key
            LIMIT 100
        ";
        
        $meta_keys = $wpdb->get_col( $query );
        
        $options = array();
        
        foreach ( $meta_keys as $meta_key ) {
            $options[ $meta_key ] = $meta_key;
        }
        
        return $options;
    }
}