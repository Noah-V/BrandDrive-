<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Get plugin status
$is_enabled = $branddrive->settings->is_enabled();
$plugin_key = $branddrive->settings->get_plugin_key();
$environment = $branddrive->settings->get_environment();
$has_plugin_key = !empty($plugin_key);
?>

<div class="branddrive-dashboard">
    <div class="branddrive-logo-container">
        <img src="<?php echo BRANDDRIVE_PLUGIN_URL; ?>assets/images/branddrive-logo.svg" alt="BrandDrive" class="branddrive-logo">
    </div>
    
    <div class="branddrive-card">
        <h2 class="branddrive-card-title"><?php _e('Activate plugin', 'branddrive-woocommerce'); ?></h2>
        
        <div class="branddrive-form-field">
            <label for="branddrive_environment"><?php _e('Status', 'branddrive-woocommerce'); ?></label>
            <div class="branddrive-select-wrapper">
                <select id="branddrive_environment" name="branddrive_environment" class="branddrive-select">
                    <option value="production" <?php selected($environment, 'production'); ?>><?php _e('Live environment', 'branddrive-woocommerce'); ?></option>
                    <option value="staging" <?php selected($environment, 'staging'); ?>><?php _e('Staging environment', 'branddrive-woocommerce'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="branddrive-form-field">
            <label for="branddrive_plugin_key"><?php _e('Plugin key', 'branddrive-woocommerce'); ?></label>
            <div class="branddrive-plugin-key-field">
                <input type="text" id="branddrive_plugin_key" name="branddrive_plugin_key" value="<?php echo esc_attr($plugin_key); ?>" placeholder="Enter your plugin key" />
                <button type="button" id="branddrive_copy_key" class="branddrive-icon-button">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </div>
            <button type="button" id="branddrive_verify_key" class="branddrive-button branddrive-button-primary"><?php _e('Verify', 'branddrive-woocommerce'); ?></button>
        </div>
        
        <div id="branddrive_key_verification_result"></div>
        
        <div class="branddrive-help-text">
            <?php _e('Where is my plugin key?', 'branddrive-woocommerce'); ?> 
            <a href="https://branddrive.com/docs/plugin-key" target="_blank" class="branddrive-link"><?php _e('Find one', 'branddrive-woocommerce'); ?></a>
        </div>
    </div>
    
    <div class="branddrive-card">
        <h2 class="branddrive-card-title"><?php _e('Export products/services', 'branddrive-woocommerce'); ?></h2>
        <p class="branddrive-card-description">
            <?php _e('Easily export your products/services from your WooCommerce to BrandDrive with a single click', 'branddrive-woocommerce'); ?>
        </p>
        <button id="branddrive_export_products" class="branddrive-button branddrive-button-primary">
            <?php _e('Export products/services', 'branddrive-woocommerce'); ?>
        </button>
    </div>
    
    <div class="branddrive-card">
        <h2 class="branddrive-card-title"><?php _e('Sync orders', 'branddrive-woocommerce'); ?></h2>
        <p class="branddrive-card-description">
            <?php _e('Synchronize your orders to BrandDrive', 'branddrive-woocommerce'); ?>
        </p>
        <button id="branddrive_sync_orders" class="branddrive-button branddrive-button-primary">
            <?php _e('Sync orders', 'branddrive-woocommerce'); ?>
        </button>
    </div>
</div>

