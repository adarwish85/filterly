<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Filterly
 * @subpackage Filterly/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The core plugin class.
 */
class Filterly {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Filterly_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = FILTERLY_VERSION;
        $this->plugin_name = 'filterly';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_filter_types();
        $this->register_integrations();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Core plugin loader class that registers hooks
        require_once FILTERLY_PLUGIN_DIR . 'includes/class-filterly-loader.php';

        // Internationalization class
        require_once FILTERLY_PLUGIN_DIR . 'includes/class-filterly-i18n.php';

        // Admin-specific functionality
        require_once FILTERLY_PLUGIN_DIR . 'includes/admin/class-filterly-admin.php';

        // Public-facing functionality
        require_once FILTERLY_PLUGIN_DIR . 'includes/public/class-filterly-public.php';

        // Base filter class
        require_once FILTERLY_PLUGIN_DIR . 'includes/filters/class-filterly-filter-base.php';

        // Create loader instance
        $this->loader = new Filterly_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Filterly_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Filterly_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu_pages' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Filterly_Public( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action( 'wp_ajax_filterly_filter', $plugin_public, 'process_ajax_filter' );
        $this->loader->add_action( 'wp_ajax_nopriv_filterly_filter', $plugin_public, 'process_ajax_filter' );
        
        // Register shortcode
        $this->loader->add_shortcode( 'filterly', $plugin_public, 'shortcode_handler' );
    }

    /**
     * Register filter types.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_filter_types() {
        // Load specific filter type classes
        require_once FILTERLY_PLUGIN_DIR . 'includes/filters/class-filterly-taxonomy-filter.php';
        require_once FILTERLY_PLUGIN_DIR . 'includes/filters/class-filterly-meta-filter.php';
        
        // Load WooCommerce specific filters if WooCommerce is active
        if ( $this->is_woocommerce_active() ) {
            require_once FILTERLY_PLUGIN_DIR . 'includes/filters/class-filterly-attribute-filter.php';
            require_once FILTERLY_PLUGIN_DIR . 'includes/filters/class-filterly-variation-filter.php';
        }
    }

    /**
     * Register integrations with third-party plugins.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_integrations() {
        // WooCommerce integration
        if ( $this->is_woocommerce_active() ) {
            require_once FILTERLY_PLUGIN_DIR . 'includes/integrations/class-filterly-woocommerce.php';
            $wc_integration = new Filterly_WooCommerce( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_filter( 'filterly_post_types', $wc_integration, 'add_product_post_type' );
        }

        // ACF integration
        if ( $this->is_acf_active() ) {
            require_once FILTERLY_PLUGIN_DIR . 'includes/integrations/class-filterly-acf.php';
            $acf_integration = new Filterly_ACF( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_filter( 'filterly_meta_fields', $acf_integration, 'add_acf_fields' );
        }

        // WPML integration
        if ( $this->is_wpml_active() ) {
            require_once FILTERLY_PLUGIN_DIR . 'includes/integrations/class-filterly-wpml.php';
            $wpml_integration = new Filterly_WPML( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_filter( 'filterly_filter_items', $wpml_integration, 'translate_items' );
        }
    }

    /**
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Check if ACF is active.
     *
     * @return bool
     */
    private function is_acf_active() {
        return class_exists( 'ACF' );
    }

    /**
     * Check if WPML is active.
     *
     * @return bool
     */
    private function is_wpml_active() {
        return defined( 'ICL_SITEPRESS_VERSION' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Filterly_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}