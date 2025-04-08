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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of the plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, FILTERLY_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, FILTERLY_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable' ), $this->version, true );
    }

    /**
     * Add menu pages.
     *
     * @since    1.0.0
     */
    public function add_menu_pages() {
        add_menu_page(
            __( 'Filterly', 'filterly' ),
            __( 'Filterly', 'filterly' ),
            'manage_options',
            'filterly',
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-filter',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            'filterly',
            __( 'Dashboard', 'filterly' ),
            __( 'Dashboard', 'filterly' ),
            'manage_options',
            'filterly',
            array( $this, 'display_plugin_admin_page' )
        );
        
        add_submenu_page(
            'filterly',
            __( 'Settings', 'filterly' ),
            __( 'Settings', 'filterly' ),
            'manage_options',
            'filterly-settings',
            array( $this, 'display_plugin_settings_page' )
        );
        
        add_submenu_page(
            'filterly',
            __( 'Shortcode Generator', 'filterly' ),
            __( 'Shortcode Generator', 'filterly' ),
            'manage_options',
            'filterly-shortcode',
            array( $this, 'display_shortcode_generator_page' )
        );
    }

    /**
     * Register plugin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings
        register_setting( 
            'filterly_settings', 
            'filterly_settings', 
            array( $this, 'validate_settings' ) 
        );
        
        // General Settings section
        add_settings_section(
            'filterly_general_settings',
            __( 'General Settings', 'filterly' ),
            array( $this, 'render_general_settings_section' ),
            'filterly-settings'
        );
        
        // Add fields
        add_settings_field(
            'filterly_ajax_enabled',
            __( 'Enable AJAX Filtering', 'filterly' ),
            array( $this, 'render_checkbox_field' ),
            'filterly-settings',
            'filterly_general_settings',
            array(
                'id'    => 'ajax_enabled',
                'desc'  => __( 'Enable AJAX filtering for faster results without page reload.', 'filterly' ),
            )
        );
        
        add_settings_field(
            'filterly_pretty_urls',
            __( 'Enable Pretty URLs', 'filterly' ),
            array( $this, 'render_checkbox_field' ),
            'filterly-settings',
            'filterly_general_settings',
            array(
                'id'    => 'pretty_urls',
                'desc'  => __( 'Generate clean, SEO-friendly URLs for filtered content.', 'filterly' ),
            )
        );
        
        add_settings_field(
            'filterly_auto_submit',
            __( 'Auto-Submit Filters', 'filterly' ),
            array( $this, 'render_checkbox_field' ),
            'filterly-settings',
            'filterly_general_settings',
            array(
                'id'    => 'auto_submit',
                'desc'  => __( 'Apply filters automatically when values change.', 'filterly' ),
            )
        );
        
        // Style Settings section
        add_settings_section(
            'filterly_style_settings',
            __( 'Style Settings', 'filterly' ),
            array( $this, 'render_style_settings_section' ),
        