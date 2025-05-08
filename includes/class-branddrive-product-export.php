<?php

/**
 * Handles product export to BrandDrive.
 */
class BrandDrive_Product_Export
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        // Register AJAX handler for product export
        add_action('wp_ajax_branddrive_export_products', array($this, 'ajax_export_products'));

        // Add export button to product list
        add_filter('bulk_actions-edit-product', array($this, 'register_bulk_export_action'));
        add_filter('handle_bulk_actions-edit-product', array($this, 'handle_bulk_export_action'), 10, 3);

        // Add export button to single product
        add_action('woocommerce_admin_order_actions_end', array($this, 'add_single_product_export_button'));
    }

    /**
     * Register bulk export action.
     */
    public function register_bulk_export_action($bulk_actions)
    {
        $bulk_actions['export_to_branddrive'] = __('Export to BrandDrive', 'branddrive-woocommerce');
        return $bulk_actions;
    }

    /**
     * Handle bulk export action.
     */
    public function handle_bulk_export_action($redirect_to, $action, $post_ids)
    {
        if ($action !== 'export_to_branddrive') {
            return $redirect_to;
        }

        $exported = $this->export_products($post_ids);

        return add_query_arg(array(
            'exported_to_branddrive' => count($exported),
            'exported_to_branddrive_failed' => count($post_ids) - count($exported)
        ), $redirect_to);
    }

    /**
     * Add single product export button.
     */
    public function add_single_product_export_button($product)
    {
        echo '<a href="' . wp_nonce_url(admin_url('admin-ajax.php?action=branddrive_export_product&product_id=' . $product->get_id()), 'export_product_to_branddrive') . '" class="button branddrive-export-button">' . __('Export to BrandDrive', 'branddrive-woocommerce') . '</a>';
    }

    /**
     * AJAX handler for exporting products.
     */
    public function ajax_export_products()
    {
        check_ajax_referer('branddrive-admin', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'branddrive-woocommerce')));
        }

        $product_ids = isset($_POST['product_ids']) ? array_map('intval', (array)$_POST['product_ids']) : array();

        if (empty($product_ids)) {
            // If no specific products are provided, export all products
            $products = wc_get_products(array(
                'limit' => -1,
                'status' => 'publish'
            ));

            $product_ids = array_map(function ($product) {
                return $product->get_id();
            }, $products);
        }

        $exported = $this->export_products($product_ids);

        if (count($exported) > 0) {
            wp_send_json_success(array(
                'message' => sprintf(__('Successfully exported %d products to BrandDrive.', 'branddrive-woocommerce'), count($exported)),
                'exported' => $exported
            ));
        } else {
            wp_send_json_error(array('message' => __('No products were exported to BrandDrive.', 'branddrive-woocommerce')));
        }
    }

    /**
     * Export products to BrandDrive.
     */
    public function export_products($product_ids)
    {
        global $branddrive;

        if (!$branddrive->settings->is_enabled()) {
            return array();
        }

        $exported = array();

        foreach ($product_ids as $product_id) {
            $product = wc_get_product($product_id);

            if (!$product) {
                continue;
            }

            $result = $this->export_product($product);

            if (!is_wp_error($result)) {
                $exported[] = $product_id;

                // Store BrandDrive product ID if returned
//                if (isset($result['id'])) {
//                    update_post_meta($product_id, '_branddrive_product_id', $result['id']);
//                }
                if (isset($result['createdProduct']) && isset($result['createdProduct']['id'])) {
                    update_post_meta($product_id, '_branddrive_product_id', $result['createdProduct']['id']);
                }
            } elseif ($branddrive->settings->is_debug_mode()) {
                error_log('[BrandDrive] Failed to export product #' . $product_id . ': ' . $result->get_error_message());
            }
        }

        return $exported;
    }

    /**
     * Export a single product to BrandDrive.
     */
    public function export_product($product)
    {
        global $branddrive;

        // Determine product type and prepare data accordingly
        $product_type = $product->get_type();

        switch ($product_type) {
            case 'variable':
                return $this->export_variable_product($product);
            case 'grouped':
                return $this->export_grouped_product($product);
            default:
                return $this->export_simple_product($product);
        }
    }

    /**
     * Export a simple product to BrandDrive.
     */
    private function export_simple_product($product)
    {
        global $branddrive;

        // product data according to BrandDrive API requirements
        $product_data = array(
            // Required fields
            'name' => trim($product->get_name()),
            'sellingPrice' => (float)$product->get_regular_price() ?: 0,
            'stock' => $product->get_manage_stock() ? (int)$product->get_stock_quantity() : 100,
            'minimumStockAlert' => (int)$product->get_low_stock_amount() ?: 5,
            'currency' => get_woocommerce_currency(),
            'barcode' => $product->get_sku(),
            'discount' => $product->is_on_sale() ?
                (float)($product->get_regular_price() - $product->get_sale_price()) : 0,
            'inStore' => $product->is_in_stock(),

            // Optional fields
            'description' => $product->get_description() ?: $product->get_short_description(),
            'costPrice' => (float)$product->get_meta('_cost', true) ?: 0,
        );

//        // Add categories if available
//        $categories = $product->get_category_ids();
//        if (!empty($categories)) {
//            $category_id = reset($categories); // Use the first category
//            $product_data['categoryId'] = (int)$category_id;
//        }

        // Get BrandDrive category ID from product custom field
        $branddrive_category_id = $branddrive->product_fields->get_product_branddrive_category_id($product);

        if (!empty($branddrive_category_id)) {
            // Use the BrandDrive-specific category ID if available
            $product_data['categoryId'] = $branddrive_category_id;
        } else {
            // Fallback to WooCommerce category if no BrandDrive category ID is set
            $categories = $product->get_category_ids();
            if (!empty($categories)) {
                $category_id = reset($categories); // Use the first category
                $product_data['categoryId'] = (int) $category_id;
            }
        }

        // Add product images
        $product_data['images'] = $this->get_product_images($product);

        // Add product attributes
        $product_data['attributes'] = $this->get_product_attributes($product);

        // Send to BrandDrive API
        return $this->send_to_branddrive('product', $product_data);
    }

    /**
     * Export a grouped product to BrandDrive.
     */
    private function export_grouped_product($product)
    {
        global $branddrive;

        // First, ensure all child products are exported
        $child_ids = $product->get_children();
        $exported_children = array();

        foreach ($child_ids as $child_id) {
            $child_product = wc_get_product($child_id);
            if ($child_product) {
                $result = $this->export_simple_product($child_product);
                if (!is_wp_error($result) && isset($result['id'])) {
                    $exported_children[] = $result['id'];
                }
            }
        }

        // Prepare grouped product data
        $product_data = array(
            // Required fields
            'name' => $product->get_name(),
            'description' => $product->get_description() ?: $product->get_short_description(),
            'costPrice' => 0, // Grouped products don't have their own price
            'sellingPrice' => 0, // Grouped products don't have their own price
            'stock' => 100, // Grouped products don't have their own stock
            'minimumStockAlert' => 5,
            'group' => $exported_children, // Array of child product IDs in BrandDrive

            // Optional fields
            'barcode' => $product->get_sku(),
            'currency' => get_woocommerce_currency(),
            'inStore' => true,
        );

        // Add categories if available
//        $categories = $product->get_category_ids();
//        if (!empty($categories)) {
//            $category_id = reset($categories); // Use the first category
//            $product_data['categoryId'] = (int)$category_id;
//        }

        // Get BrandDrive category ID from product custom field
        $branddrive_category_id = $branddrive->product_fields->get_product_branddrive_category_id($product);

        if (!empty($branddrive_category_id)) {
            // Use the BrandDrive-specific category ID if available
            $product_data['categoryId'] = $branddrive_category_id;
        } else {
            // Fallback to WooCommerce category if no BrandDrive category ID is set
            $categories = $product->get_category_ids();
            if (!empty($categories)) {
                $category_id = reset($categories); // Use the first category
                $product_data['categoryId'] = (int) $category_id;
            }
        }

        // Add product images
        $product_data['images'] = $this->get_product_images($product);

        // Add product attributes
        $product_data['attributes'] = $this->get_product_attributes($product);

        // Send to BrandDrive API
        return $this->send_to_branddrive('product', $product_data);
    }

    /**
     * Export a variable product to BrandDrive.
     */
    private function export_variable_product($product)
    {
        global $branddrive;

        // First, export the main product as a simple product
        $result = $this->export_simple_product($product);

        if (is_wp_error($result) || !isset($result['id'])) {
            return $result;
        }

        $product_id = $result['id'];

        // Now prepare variations
        $variations = array();
        $variation_ids = $product->get_children();

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }

            // Get variation attributes
            $variation_attributes = array();
            $attributes = $variation->get_attributes();

            foreach ($attributes as $attribute_name => $attribute_value) {
                if (empty($attribute_value)) {
                    continue;
                }

                // Get attribute label
                $taxonomy = str_replace('pa_', '', $attribute_name);
                $term = get_term_by('slug', $attribute_value, 'pa_' . $taxonomy);

                $variation_attributes[] = array(
                    'name' => wc_attribute_label($attribute_name),
                    'value' => $term ? $term->name : $attribute_value
                );
            }

            // Prepare variation data
            $variations[] = array(
                'sku' => $variation->get_sku(),
                'barcode' => $variation->get_sku(),
                'costPrice' => (float)$variation->get_meta('_cost', true) ?: 0,
                'sellingPrice' => (float)$variation->get_regular_price() ?: 0,
                'stock' => $variation->get_manage_stock() ? (int)$variation->get_stock_quantity() : 100,
                'attributes' => $variation_attributes,
                'images' => $this->get_product_images($variation)
            );

            // Add category ID if available for the variation
            if (!empty($variation_branddrive_category_id)) {
                $variation_data['categoryId'] = $variation_branddrive_category_id;
            }
        }

        // Send variations to BrandDrive
        $variation_data = array(
            'variations' => $variations
        );

        return $this->send_to_branddrive('product/' . $product_id . '/variation', $variation_data);
    }

    /**
     * Get product images.
     */
    private function get_product_images($product)
    {
        $images = array();

        // Add main image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_url($image_id);
            if ($image_url) {
                $images[] = $image_url;
            }
        }

        // Add gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
            $gallery_url = wp_get_attachment_url($gallery_id);
            if ($gallery_url) {
                $images[] = $gallery_url;
            }
        }

        return $images;
    }

    /**
     * Get product attributes.
     */
    private function get_product_attributes($product)
    {
        $attributes = array();

        $product_attributes = $product->get_attributes();

        foreach ($product_attributes as $attribute_name => $attribute) {
            if ($attribute->is_taxonomy()) {
                $taxonomy = $attribute->get_taxonomy_object();
                $attribute_label = $taxonomy->attribute_label;

                $terms = $attribute->get_terms();
                $values = array();

                foreach ($terms as $term) {
                    $values[] = $term->name;
                }

                $attributes[] = array(
                    'name' => $attribute_label,
                    'values' => $values
                );
            } else {
                $attributes[] = array(
                    'name' => $attribute->get_name(),
                    'values' => $attribute->get_options()
                );
            }
        }

        return $attributes;
    }

    /**
     * Send data to BrandDrive API.
     */
    private function send_to_branddrive($endpoint, $data)
    {
        global $branddrive;

        if (!$branddrive->settings->is_enabled()) {
            return new WP_Error('integration_disabled', __('BrandDrive integration is disabled.', 'branddrive-woocommerce'));
        }

        $plugin_key = $branddrive->settings->get_plugin_key();
        if (empty($plugin_key)) {
            return new WP_Error('missing_plugin_key', __('Plugin key is not set.', 'branddrive-woocommerce'));
        }

        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            error_log('[BrandDrive] Product name after trim: "' . $data['name'] . '"');
            error_log('[BrandDrive] Product name length after trim: ' . strlen($data['name']));

            if (empty($data['name'])) {
                error_log('[BrandDrive] Invalid Data: Product name is empty');
                return new WP_Error('invalid_data', __('Product name cannot be empty.', 'branddrive-woocommerce'));
            }
        }


        $api_url = $branddrive->api->getApiUrl();

        // Encrypt data if needed
//        $encrypted_data = $branddrive->encryption->encrypt($data, $plugin_key);
//        if (!$encrypted_data) {
//            return new WP_Error('encryption_failed', __('Failed to encrypt data.', 'branddrive-woocommerce'));
//        }

        // Prepare request
//        $request_data = array(
//            'encrypted_data' => $encrypted_data
//        );

        $request_data = $data;


        //checking the data that we're sending
        $json_data = json_encode($request_data, JSON_PRETTY_PRINT);
        error_log('[BrandDrive] Sending data to ' . $api_url . $endpoint . ': ' . $json_data);
        error_log('[BrandDrive] JSON length: ' . strlen($json_data));

        //checking for encoding errors
        $json_data = json_encode($request_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[BrandDrive] JSON encoding error: ' . json_last_error_msg());
            return new WP_Error('json_error', __('Failed to encode product data as JSON.', 'branddrive-woocommerce'));
        }

//        if ($branddrive->settings->is_debug_mode()) {
//        }

        // Send request to BrandDrive
//        $response = wp_remote_post(
//            $api_url . $endpoint,
//            array(
//                'timeout' => 60,
//                'headers' => array(
//                    'Authorization' => 'Bearer ' . $plugin_key,
//                ),
////                'body' => json_encode($request_data),
//                'body' => $json_data,
//                'data_format' => 'body',
////                'sslverify' => $environment === 'production'
//            )
//        );

        // Send request to BrandDrive
        $args = array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $plugin_key,
            ),
            'body' => $json_data,
        );

        error_log('[BrandDrive] Request args: ' . print_r($args, true));


        if ($endpoint === 'product') {
            $test_payload = '{
                "name": "' . $data['name'] . '",
                "sellingPrice": ' . $data['sellingPrice'] . ',
                "stock": ' . $data['stock'] . ',
                "minimumStockAlert": ' . $data['minimumStockAlert'] . ',
                "currency": "' . $data['currency'] . '",
                "barcode": "' . $data['barcode'] . '",
                "discount": ' . $data['discount'] . ',
                "inStore": ' . ($data['inStore'] ? 'true' : 'false') . ',
                "description": "' . addslashes($data['description']) . '",
                "costPrice": ' . $data['costPrice'] . ',
                "categoryId": ' . (isset($data['categoryId']) ? $data['categoryId'] : 'null') . '
            }';

            error_log('[BrandDrive] Testing with hardcoded payload : ' . $test_payload);


            $response = wp_remote_post(
                $api_url . $endpoint,
                array(
                    'timeout' => 60,
                    'headers' => array(
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $plugin_key,
                    ),
                    'body' => $test_payload,
                )
            );
        } else {
            $response = wp_remote_post(
                $api_url . $endpoint,
                $args
            );
        }

        if (is_wp_error($response)) {
            error_log('[BrandDrive] API request failed: ' . $response->get_error_message());
            return $response;
        }

        if (is_wp_error($response)) {
//            if ($branddrive->settings->is_debug_mode()) {
//            }
            error_log('[BrandDrive] API request failed: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);



        error_log('[BrandDrive] API response: ' . $response_code . ' - ' . $response_body);
        error_log('Current PHP error log file: ' . ini_get('error_log'));

        if ($response_code !== 200 && $response_code !== 201) {
            $response_data = json_decode($response_body, true);
            $error_message = isset($response_data['message']) ? $response_data['message'] : __('Unknown error occurred.', 'branddrive-woocommerce');
            error_log($error_message);
            return new WP_Error('api_error', $error_message);
        }

        return $response_data;
    }
}

