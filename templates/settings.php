<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Handle form submission
$form_submitted = false;
$form_error = false;
$form_message = '';

if (isset($_POST['branddrive_save_settings'])) {
    $result = $branddrive->settings->handle_form_submission();
    $form_submitted = true;
    
    if (is_wp_error($result)) {
        $form_error = true;
        $form_message = $result->get_error_message();
    } else {
        $form_message = __('Settings saved successfully!', 'branddrive-woocommerce');
    }
}

// Get current settings
$settings = $branddrive->settings->get_all();
?>

<div class="branddrive-settings">
    <div class="branddrive-settings-header">
        <h2><?php _e('BrandDrive Settings', 'branddrive-woocommerce'); ?></h2>
        <p><?php _e('Configure your BrandDrive integration with WooCommerce.', 'branddrive-woocommerce'); ?></p>
    </div>
    
    <?php if ($form_submitted): ?>
        <div class="branddrive-notice <?php echo $form_error ? 'branddrive-notice-error' : 'branddrive-notice-success'; ?>">
            <p><?php echo esc_html($form_message); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="post" action="" class="branddrive-settings-form">
        <?php wp_nonce_field('save_branddrive_settings', 'branddrive_settings_nonce'); ?>
        
        <div class="branddrive-form-section">
            <h3><?php _e('General Settings', 'branddrive-woocommerce'); ?></h3>
            
            <div class="branddrive-form-field">
                <label for="branddrive_enabled">
                    <input type="checkbox" id="branddrive_enabled" name="branddrive_enabled" value="1" <?php checked($settings['enabled'], 'yes'); ?> />
                    <?php _e('Enable BrandDrive Integration', 'branddrive-woocommerce'); ?>
                </label>
                <p class="description"><?php _e('Enable or disable the BrandDrive integration.', 'branddrive-woocommerce'); ?></p>
            </div>
            
            <div class="branddrive-form-field">
                <label for="branddrive_environment"><?php _e('Environment', 'branddrive-woocommerce'); ?></label>
                <select id="branddrive_environment" name="branddrive_environment">
                    <option value="production" <?php selected($settings['environment'], 'production'); ?>><?php _e('Production', 'branddrive-woocommerce'); ?></option>
                    <option value="staging" <?php selected($settings['environment'], 'staging'); ?>><?php _e('Staging', 'branddrive-woocommerce'); ?></option>
                </select>
                <p class="description"><?php _e('Select the BrandDrive environment to connect to.', 'branddrive-woocommerce'); ?></p>
            </div>
        </div>
        
        <div class="branddrive-form-section">
            <h3><?php _e('API Configuration', 'branddrive-woocommerce'); ?></h3>
            
            <div class="branddrive-form-field">
                <label for="branddrive_plugin_key"><?php _e('Plugin Key', 'branddrive-woocommerce'); ?></label>
                <div class="branddrive-plugin-key-field">
                    <input type="text" id="branddrive_plugin_key" name="branddrive_plugin_key" value="<?php echo esc_attr($settings['plugin_key']); ?>" />
                    <button type="button" id="branddrive_verify_key" class="button"><?php _e('Verify', 'branddrive-woocommerce'); ?></button>
                </div>
                <p class="description"><?php _e('Enter your BrandDrive plugin key. You can find this in your BrandDrive account.', 'branddrive-woocommerce'); ?></p>
                <div id="branddrive_key_verification_result"></div>
            </div>
            
            <div class="branddrive-form-field">
                <label for="branddrive_debug_mode">
                    <input type="checkbox" id="branddrive_debug_mode" name="branddrive_debug_mode" value="1" <?php checked($settings['debug_mode'], 'yes'); ?> />
                    <?php _e('Enable Debug Mode', 'branddrive-woocommerce'); ?>
                </label>
                <p class="description"><?php _e('Enable debug mode to log API requests and responses. Logs will be written to the WordPress debug log.', 'branddrive-woocommerce'); ?></p>
            </div>
        </div>
        
        <div class="branddrive-form-actions">
            <button type="submit" name="branddrive_save_settings" class="button button-primary"><?php _e('Save Settings', 'branddrive-woocommerce'); ?></button>
        </div>
    </form>
</div>

