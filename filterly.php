<?php
/**
 * Plugin Name: Filterly
 * Plugin URI: https://example.com/filterly
 * Description: Advanced content filtering plugin for WordPress and WooCommerce that supports filtering by taxonomies, custom fields, attributes, and more.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: filterly
 * Domain Path: /languages
 * 
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 *
 * @package Filterly
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Current plugin version.
 */
define( 'FILTERLY_VERSION', '1.0.0' );
define( 'FILTERLY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FILTERLY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FILTERLY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_filterly() {
    require_once FILTERLY_PLUGIN_DIR . 'includes/class-filterly-activator.php';
    Filterly_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_filterly() {
    require_once FILTERLY_PLUGIN_DIR . 'includes/class-filterly-deactivator.php';
    Filterly_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_filterly' );
register_deactivation_hook( __FILE__, 'deactivate_filterly' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require FILTERLY_PLUGIN_DIR . 'includes/class-filterly.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_filterly() {
    $plugin = new Filterly();
    $plugin->run();
}

run_filterly();