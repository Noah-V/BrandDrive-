<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Handles API communication with BrandDrive.
 */
class BrandDrive_API {
    /**
     * API base URLs.
     */
    private $api_urls = array(
        'production' => 'https://api.branddrive.com/api/v1/',
        'staging' => 'https://api.usebranddrive.com/api/v1/'
    );
    
    /**rr
     * Current API URL.
     */
    private $api_url;
    
    /**
     * Constructor.
     */
    public function __construct() {
        global $branddrive;
        
        // Set API URL based on environment
        $environment = isset($branddrive->settings) ? $branddrive->settings->get_environment() : 'staging';
        $this->api_url = $this->api_urls[$environment];
        
        // Register AJAX handlers
        add_action('wp_ajax_branddrive_verify_plugin_key', array($this, 'ajax_verify_plugin_key'));
//        add_action('wp_ajax_branddrive_export_products', array($this, 'ajax_export_products'));
        add_action('wp_ajax_branddrive_sync_orders', array($this, 'ajax_sync_orders'));
    }

    public function getApiUrl() {
        return $this->api_url;
    }
    
    /**
     * Determine if SSL verification should be used.
     */
    private function should_verify_ssl() {
        global $branddrive;
        
        // In production, always verify SSL
        if ($branddrive->settings->get_environment() === 'production') {
            return true;
        }
        
        // In staging, allow disabling SSL verification for development
        return apply_filters('branddrive_verify_ssl', true);
    }
    
    /**
     * Log API request/response if debug mode is enabled.
     */
    private function log($message) {
        global $branddrive;
        
        if ($branddrive->settings->is_debug_mode()) {
            error_log('[BrandDrive] ' . $message);
        }
    }
    
    /**
     * Verify plugin key with BrandDrive API.
     */
    public function verify_plugin_key($plugin_key) {
        // Output debug information
//        echo "Verify Plugin Debug Output\n";
//        echo "Time: " . date('Y-m-d H:i:s') . "\n";
//        echo "Normal Verify POST data: " . print_r($_POST, true) . "\n";

        // Stop execution

        if (empty($plugin_key)) {
            return new WP_Error('empty_plugin_key', __('Plugin key cannot be empty.', 'branddrive-woocommerce'));
        }
        
//        $this->log('Verifying plugin key: ' . substr($plugin_key, 0, 4) . '...');
        
        $response = wp_remote_get(
            $this->api_url . 'business/who-am-i',
            array(
                'timeout' => 30,
                'headers' => array(
//                    'plugin-key' => $plugin_key,
                    'Authorization' => 'Bearer ' . $plugin_key,
                ),
                'sslverify' => $this->should_verify_ssl()
            )
        );

        
        if (is_wp_error($response)) {
//            $this->log('Plugin key verification failed: ' . $response->get_error_message());
//            echo("Plugin key verification failed in verify_plugin_key: " . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

//        var_dump($response_body);

//        $this->log('Plugin key verification response: ' . $response_code . ' - ' . $response_body);

//        echo("Response Data in verify_plugin_key: " . print_r($response_data['description'], true));

        if ($response_code !== 200) {
            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred.', 'branddrive-woocommerce');
            return new WP_Error('api_error', $error_message);
        }

//        if (!isset($response_data['success']) || $response_data['success'] !== true) {
//            return new WP_Error('invalid_plugin_key', __('Invalid plugin key.', 'branddrive-woocommerce'));
//        }

//        echo("Response Data in verify_plugin_key: " . print_r($response_data['description'], true));
//        echo("\n");
//        echo("\n");
//
//        echo("Response has business?: " . isset($response_data['business']));
//        echo("\n");
//        echo("\n");

        if (!isset($response_data['business'])) {
//            echo("No business found");
//            var_dump($response_data);
//            echo("\n");
            return new WP_Error('invalid_plugin_key', __('Invalid plugin key.', 'branddrive-woocommerce'));
        }

//        echo("Verify Plugin Key function's response: " . $response);

        return true;
    }
    
    /**
     * Send checkout event to BrandDrive.
     */
    public function send_checkout_event($event_type, $order_id, $order_data) {
        global $branddrive;
        
        if (!$branddrive->settings->is_enabled()) {
            return new WP_Error('integration_disabled', __('BrandDrive integration is disabled.', 'branddrive-woocommerce'));
        }

        $plugin_key = $branddrive->settings->get_plugin_key();
        if (empty($plugin_key)) {
            return new WP_Error('missing_plugin_key', __('Plugin key is not set.', 'branddrive-woocommerce'));
        }

        // Encrypt order data
        $encrypted_data = $branddrive->encryption->encrypt($order_data, $plugin_key);
        if (!$encrypted_data) {
            return new WP_Error('encryption_failed', __('Failed to encrypt order data.', 'branddrive-woocommerce'));
        }

        // Prepare request data
        $request_data = array(
            'event_type' => $event_type,
            'order_id' => $order_id,
            'encrypted_data' => $encrypted_data
        );

        $this->log('Sending checkout event: ' . $event_type . ' for order #' . $order_id);
        
        // Send request to BrandDrive
        $response = wp_remote_post(
            $this->api_url . 'checkout/events',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'plugin-key' => $plugin_key
                ),
                'body' => json_encode($request_data),
                'sslverify' => $this->should_verify_ssl()
            )
        );

        if (is_wp_error($response)) {
            $this->log('Checkout event failed: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->log('Checkout event response: ' . $response_code . ' - ' . $response_body);
        
        if ($response_code !== 200) {
            $response_data = json_decode($response_body, true);
            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred.', 'branddrive-woocommerce');
            return new WP_Error('api_error', $error_message);
        }

        return true;
    }
    
    /**
     * Export products to BrandDrive.
     */
//    public function export_products() {
//        global $branddrive;
//
//        if (!$branddrive->settings->is_enabled()) {
//            return new WP_Error('integration_disabled', __('BrandDrive integration is disabled.', 'branddrive-woocommerce'));
//        }
//
//        $plugin_key = $branddrive->settings->get_plugin_key();
//        if (empty($plugin_key)) {
//            return new WP_Error('missing_plugin_key', __('Plugin key is not set.', 'branddrive-woocommerce'));
//        }
//
//        // Get products from WooCommerce
//        $products = wc_get_products(array(
//            'limit' => -1,
//            'status' => 'publish'
//        ));
//
//        if (empty($products)) {
//            return new WP_Error('no_products', __('No products found to export.', 'branddrive-woocommerce'));
//        }
//
//        // Prepare products data
//        $products_data = array();
//        foreach ($products as $product) {
//            $products_data[] = $this->prepare_product_data($product);
//        }
//
//        // Encrypt products data
//        $encrypted_data = $branddrive->encryption->encrypt($products_data, $plugin_key);
//        if (!$encrypted_data) {
//            return new WP_Error('encryption_failed', __('Failed to encrypt products data.', 'branddrive-woocommerce'));
//        }
//
//        // Prepare request data
//        $request_data = array(
//            'encrypted_data' => $encrypted_data
//        );
//
//        $this->log('Exporting ' . count($products_data) . ' products to BrandDrive');
//
//        // Send request to BrandDrive
//        $response = wp_remote_post(
//            $this->api_url . '/product',
//            array(
//                'timeout' => 60,
//                'headers' => array(
//                    'Content-Type' => 'application/json',
//                    'plugin-key' => $plugin_key
//                ),
//                'body' => json_encode($request_data),
//                'sslverify' => $this->should_verify_ssl()
//            )
//        );
//
//        if (is_wp_error($response)) {
//            $this->log('Products export failed: ' . $response->get_error_message());
//            return $response;
//        }
//
//        $response_code = wp_remote_retrieve_response_code($response);
//        $response_body = wp_remote_retrieve_body($response);
//
//        $this->log('Products export response: ' . $response_code . ' - ' . $response_body);
//
//        if ($response_code !== 200) {
//            $response_data = json_decode($response_body, true);
//            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred.', 'branddrive-woocommerce');
//            return new WP_Error('api_error', $error_message);
//        }
//
//        return count($products_data);
//    }
    
    /**
     * Prepare product data for export.
     */
    private function prepare_product_data($product) {
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'weight' => $product->get_weight(),
            'dimensions' => array(
                'length' => $product->get_length(),
                'width' => $product->get_width(),
                'height' => $product->get_height()
            ),
            'categories' => array(),
            'tags' => array(),
            'attributes' => array(),
            'images' => array(),
            'meta_data' => array()
        );
        
        // Add categories
        $categories = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($categories) && !is_wp_error($categories)) {
            foreach ($categories as $category) {
                $product_data['categories'][] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug
                );
            }
        }
        
        // Add tags
        $tags = get_the_terms($product->get_id(), 'product_tag');
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $product_data['tags'][] = array(
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                );
            }
        }
        
        // Add attributes
        $attributes = $product->get_attributes();
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attr_data = array(
                    'name' => $attribute->get_name(),
                    'options' => $attribute->get_options(),
                    'visible' => $attribute->get_visible(),
                    'variation' => $attribute->get_variation()
                );
                $product_data['attributes'][] = $attr_data;
            }
        }
        
        // Add images
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $product_data['images'][] = array(
                    'id' => $image_id,
                    'url' => $image_url,
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                    'position' => 0
                );
            }
        }
        
        $gallery_image_ids = $product->get_gallery_image_ids();
        if (!empty($gallery_image_ids)) {
            $position = 1;
            foreach ($gallery_image_ids as $gallery_image_id) {
                $image_url = wp_get_attachment_url($gallery_image_id);
                if ($image_url) {
                    $product_data['images'][] = array(
                        'id' => $gallery_image_id,
                        'url' => $image_url,
                        'alt' => get_post_meta($gallery_image_id, '_wp_attachment_image_alt', true),
                        'position' => $position++
                    );
                }
            }
        }
        
        // Add meta data
        $meta_data = $product->get_meta_data();
        if (!empty($meta_data)) {
            foreach ($meta_data as $meta) {
                $product_data['meta_data'][] = array(
                    'key' => $meta->key,
                    'value' => $meta->value
                );
            }
        }
        
        return $product_data;
    }
    
    /**
     * Sync orders with BrandDrive.
     */
    public function sync_orders() {
        global $branddrive;
        
        if (!$branddrive->settings->is_enabled()) {
            return new WP_Error('integration_disabled', __('BrandDrive integration is disabled.', 'branddrive-woocommerce'));
        }

        $plugin_key = $branddrive->settings->get_plugin_key();
        if (empty($plugin_key)) {
            return new WP_Error('missing_plugin_key', __('Plugin key is not set.', 'branddrive-woocommerce'));
        }

        // Get recent orders
        $orders = wc_get_orders(array(
            'limit' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        if (empty($orders)) {
            return new WP_Error('no_orders', __('No orders found to sync.', 'branddrive-woocommerce'));
        }

        // Prepare orders data
        $orders_data = array();
        foreach ($orders as $order) {
            $orders_data[] = $branddrive->checkout_events->prepare_order_data($order);
        }

        // Encrypt orders data
        $encrypted_data = $branddrive->encryption->encrypt($orders_data, $plugin_key);
        if (!$encrypted_data) {
            return new WP_Error('encryption_failed', __('Failed to encrypt orders data.', 'branddrive-woocommerce'));
        }

        // Prepare request data
        $request_data = array(
            'encrypted_data' => $encrypted_data
        );

        $this->log('Syncing ' . count($orders_data) . ' orders to BrandDrive');
        
        // Send request to BrandDrive
        $response = wp_remote_post(
            $this->api_url . 'orders/sync',
            array(
                'timeout' => 60,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'plugin-key' => $plugin_key
                ),
                'body' => json_encode($request_data),
                'sslverify' => $this->should_verify_ssl()
            )
        );

        if (is_wp_error($response)) {
            $this->log('Orders sync failed: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->log('Orders sync response: ' . $response_code . ' - ' . $response_body);
        
        if ($response_code !== 200) {
            $response_data = json_decode($response_body, true);
            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred.', 'branddrive-woocommerce');
            return new WP_Error('api_error', $error_message);
        }

        return count($orders_data);
    }
    
    /**
     * AJAX handler for verifying plugin key.
     */
    public function ajax_verify_plugin_key() {
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers to prevent caching and ensure plain text
        header('Content-Type: text/plain');
        header('Cache-Control: no-cache, must-revalidate');

        // Output debug information
//        echo "AJAX Debug Output\n";
//        echo "Time: " . date('Y-m-d H:i:s') . "\n";
//        echo "POST data: " . print_r($_POST, true) . "\n";

        // Stop execution
        check_ajax_referer('branddrive-admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'branddrive-woocommerce')));
        }
        
        $plugin_key = isset($_POST['plugin_key']) ? sanitize_text_field($_POST['plugin_key']) : '';

//        echo "<pre>";
//        print_r('BrandDrive: Verifying plugin key via AJAX: ' . substr($plugin_key, 0, 4) . '...');
//        var_dump('BrandDrive: Verifying plugin key via AJAX: ' . substr($plugin_key, 0, 4) . '...');
//        echo "<pre>";

//        die('AJAX function reached!');

        
//        $result = $this->verify_plugin_key($plugin_key);
        
//        if (is_wp_error($result)) {
//            wp_send_json_error(array('message' => $result->get_error_message()));
//        }
//
//        // Update plugin key in settings
//        global $branddrive;
//        $branddrive->settings->update(array('plugin_key' => $plugin_key));
//
//        wp_send_json_success(array('message' => __('Plugin key verified successfully!', 'branddrive-woocommerce')));

        try {
            $result = $this->verify_plugin_key($plugin_key);


            if (is_wp_error($result)) {
//                error_log('BrandDrive: Plugin key verification failed: ' . $result->get_error_message());
//                var_dump("Error Message in ajax_verify_plugin_key: " . $result->get_error_message());
                wp_send_json_error(array('message' => $result->get_error_message()));
            }

            // Update plugin key in settings
            global $branddrive;
            $branddrive->settings->update(array('plugin_key' => $plugin_key));

//            error_log('BrandDrive: Plugin key verified successfully');
            wp_send_json_success(array('message' => __('Plugin key verified successfully!', 'branddrive-woocommerce')));
        } catch (Exception $e) {
//            error_log('BrandDrive: Exception during plugin key verification: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Exception: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for exporting products.
     */
    public function ajax_export_products() {
        check_ajax_referer('branddrive-admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'branddrive-woocommerce')));
        }
        
        $result = $this->export_products();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully exported %d products to BrandDrive.', 'branddrive-woocommerce'), $result)
        ));
    }
    
    /**
     * AJAX handler for syncing orders.
     */
    public function ajax_sync_orders() {
        check_ajax_referer('branddrive-admin', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'branddrive-woocommerce')));
        }
        
        $result = $this->sync_orders();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Successfully synchronized %d orders with BrandDrive.', 'branddrive-woocommerce'), $result)
        ));
    }
}

