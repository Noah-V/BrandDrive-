<?php
/**
 * Handles plugin settings.
 */
class BrandDrive_Settings {
    /**
     * The settings option name.
     */
    private $option_name = 'branddrive_settings';
    
    /**
     * Default settings.
     */
    private $defaults = array(
        'enabled' => 'no',
        'environment' => 'production',
        'plugin_key' => '',
        'debug_mode' => 'no'
    );
    
    /**
     * Get all settings.
     */
    public function get_all() {
        $settings = get_option($this->option_name, array());
        return wp_parse_args($settings, $this->defaults);
    }
    
    /**
     * Get a specific setting.
     */
    public function get($key) {
        $settings = $this->get_all();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Update settings.
     */
    public function update($settings) {
        $current_settings = $this->get_all();
        $updated_settings = wp_parse_args($settings, $current_settings);
        update_option($this->option_name, $updated_settings);
        return true;
    }
    
    /**
     * Check if integration is enabled.
     */
    public function is_enabled() {
        return $this->get('enabled') === 'yes';
    }
    
    /**
     * Get the plugin key.
     */
    public function get_plugin_key() {
        return $this->get('plugin_key');
    }
    
    /**
     * Get the API environment.
     */
    public function get_environment() {
        return $this->get('environment');
    }
    
    /**
     * Check if debug mode is enabled.
     */
    public function is_debug_mode() {
        return $this->get('debug_mode') === 'yes';
    }
    
    /**
     * Verify the plugin key with BrandDrive API.
     */
    public function verify_plugin_key($plugin_key) {
        global $branddrive;
        
        if (empty($plugin_key)) {
            return new WP_Error('empty_plugin_key', __('Plugin key cannot be empty.', 'branddrive-woocommerce'));
        }
        
        // Send verification request to BrandDrive
        $response = $branddrive->api->verify_plugin_key($plugin_key);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Update settings with verified plugin key
        $this->update(array(
            'plugin_key' => $plugin_key
        ));
        
        return true;
    }
    
    /**
     * Handle settings form submission.
     */
    public function handle_form_submission() {
        if (!isset($_POST['branddrive_settings_nonce']) || !wp_verify_nonce($_POST['branddrive_settings_nonce'], 'save_branddrive_settings')) {
            return new WP_Error('invalid_nonce', __('Security check failed.', 'branddrive-woocommerce'));
        }
        
        $settings = array(
            'enabled' => isset($_POST['branddrive_enabled']) ? 'yes' : 'no',
            'environment' => isset($_POST['branddrive_environment']) ? sanitize_text_field($_POST['branddrive_environment']) : 'production',
            'debug_mode' => isset($_POST['branddrive_debug_mode']) ? 'yes' : 'no'
        );
        
        // Handle plugin key separately
        $plugin_key = isset($_POST['branddrive_plugin_key']) ? sanitize_text_field($_POST['branddrive_plugin_key']) : '';
        $current_plugin_key = $this->get_plugin_key();
        
        // If plugin key has changed, verify it
        if ($plugin_key !== $current_plugin_key && !empty($plugin_key)) {
            $verify_result = $this->verify_plugin_key($plugin_key);
            if (is_wp_error($verify_result)) {
                return $verify_result;
            }
        } else {
            // Keep the current plugin key
            $settings['plugin_key'] = $current_plugin_key;
        }
        
        // Update settings
        $this->update($settings);
        
        return true;
    }
}

