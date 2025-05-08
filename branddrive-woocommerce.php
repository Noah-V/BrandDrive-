<?php
/**
 * Plugin Name: BrandDrive for WooCommerce
 * Plugin URI: https://branddrive.com/woocommerce
 * Description: Integrate your WooCommerce store with BrandDrive for seamless product management and checkout event tracking.
 * Version: 1.0.0
 * Author: BrandDrive
 * Author URI: https://branddrive.com
 * Text Domain: branddrive-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package BrandDrive
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('BRANDDRIVE_VERSION', '1.0.0');
define('BRANDDRIVE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRANDDRIVE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRANDDRIVE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Include required files
require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-core.php';

// Initialize the plugin
function branddrive_woocommerce_init() {
    global $branddrive;
    $branddrive = new BrandDrive_Core();
    $branddrive->init();
}
add_action('plugins_loaded', 'branddrive_woocommerce_init');

// Register activation hook
register_activation_hook(__FILE__, 'branddrive_woocommerce_activate');
function branddrive_woocommerce_activate() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('BrandDrive for WooCommerce requires WooCommerce to be installed and active.', 'branddrive-woocommerce'));
    }
    
    // Initialize settings on activation
    $settings = get_option('branddrive_settings', array());
    if (empty($settings)) {
        update_option('branddrive_settings', array(
            'enabled' => 'yes', //changed this to yes to "enable" plugin on activation.
            'environment' => 'production',
            'plugin_key' => '',
            'debug_mode' => 'no'
        ));
    }

    update_option('branddrive_settings', array(
        'enabled' => 'yes',
    ));


}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'branddrive_woocommerce_deactivate');
function branddrive_woocommerce_deactivate() {
    // Clean up any temporary data if needed
     update_option('branddrive_settings', array(
            'enabled' => 'no',
            'environment' => 'production',
            'plugin_key' => '',
            'debug_mode' => 'no'
     ));
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . BRANDDRIVE_PLUGIN_BASENAME, 'branddrive_woocommerce_settings_link');
function branddrive_woocommerce_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=branddrive&tab=settings') . '">' . __('Settings', 'branddrive-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Load plugin textdomain
add_action('init', 'branddrive_woocommerce_load_textdomain');
function branddrive_woocommerce_load_textdomain() {
    load_plugin_textdomain('branddrive-woocommerce', false, dirname(BRANDDRIVE_PLUGIN_BASENAME) . '/languages');
}

