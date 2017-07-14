<?php

/**
 * Plugin Name: e-motion - Made4ecommerce
 * Plugin URI: http://www.e-motion.com/
 * Version: 1.0.4
 * Description: Adds e-motion service support to your eCommerce.
 * Author: e-motion
 * Author URI: http://www.e-motion.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: e-motion-m4ec
 * Domain Path:       /languages
 *
 *  */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Required functions
 */
if (!function_exists('woothemes_queue_update')) {
    require_once('woo-includes/woo-functions.php');
}

// WC active check
if (!is_woocommerce_active()) {
    return;
}

/**
 * Include emotion class
 */
function e_motion_m4ec_init()
{
    define('E_MOTION_M4EC_VERSION', '0.0.1');
    define('E_MOTION_M4EC_FILE', __FILE__);

    if (!defined('E_MOTION_M4EC_EXPORT_LIMIT')) {
        define('E_MOTION_M4EC_EXPORT_LIMIT', 100);
    }

    if (!defined('E_MOTION_M4EC_PLUGIN_BASENAME')) {
        define('E_MOTION_M4EC_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    load_plugin_textdomain( 'e-motion-m4ec', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

    include_once('includes/class-wc-e_motion-integration.php');


}

add_action('plugins_loaded', 'e_motion_m4ec_init');

/**
 * Define integration
 * @param  array $integrations
 * @return array
 */
function e_motion_m4ec_load_integration($integrations)
{
    $integrations[] = 'WC_e_motion_m4ec_Integration';

    return $integrations;
}

add_filter('woocommerce_integrations', 'e_motion_m4ec_load_integration');

/**
 * Listen for API requests
 */
function e_motion_m4ec_api()
{
    include_once('includes/class-wc-e_motion-api.php');
}

add_action('woocommerce_api_wc_e_motion', 'e_motion_m4ec_api');