<?php
/**
 * Handles product import from BrandDrive.
 */
class BrandDrive_Product_Import {
    /**
     * Constructor.
     */
    public function __construct() {
        // No hooks needed here, import is triggered manually
    }
    
    /**
     * Import products from BrandDrive.
     */
    public function import_products() {
        global $branddrive;
        
        if (!$branddrive->settings->is_enabled()) {
            return new WP_Error('integration_disabled', __('BrandDrive integration is disabled.', 'branddrive-woocommerce'));
        }
        
        // Get products from BrandDrive
        $products = $branddrive->api->get_products();
        
        if (is_wp_error($products)) {
            return $products;
        }
        
        if (empty($products)) {
            return new WP_Error('no_products', __('No products found in BrandDrive.', 'branddrive-woocommerce'));
        }
        
        $imported_count = 0;
        
        // Process each product
        foreach ($products as $product_data) {
            $result = $this->process_product($product_data);
            
            if (!is_wp_error($result)) {
                $imported_count++;
            } elseif ($branddrive->settings->is_debug_mode()) {
                error_log('[BrandDrive] Failed to import product: ' . $result->get_error_message());
            }
        }
        
        return $imported_count;
    }
    
    /**
     * Process a single product.
     */
    private function process_product($product_data) {
        // Check required fields
        if (empty($product_data['id']) || empty($product_data['name'])) {
            return new WP_Error('missing_required_fields', __('Product is missing required fields.', 'branddrive-woocommerce'));
        }
        
        // Check if product already exists by SKU or external ID
        $product_id = $this->get_existing_product_id($product_data);
        
        if ($product_id) {
            // Update existing product
            return $this->update_product($product_id, $product_data);
        } else {
            // Create new product
            return $this->create_product($product_data);
        }
    }
    
    /**
     * Get existing product ID by SKU or external ID.
     */
    private function get_existing_product_id($product_data) {
        $product_id = 0;
        
        // Check by SKU
        if (!empty($product_data['sku'])) {
            $product_id = wc_get_product_id_by_sku($product_data['sku']);
        }
        
        // Check by external ID
        if (!$product_id && !empty($product_data['id'])) {
            $products = wc_get_products(array(
                'meta_key' => '_branddrive_product_id',
                'meta_value' => $product_data['id'],
                'limit' => 1
            ));
            
            if (!empty($products)) {
                $product_id = $products[0]->get_id();
            }
        }
        
        return $product_id;
    }
    
    /**
     * Create a new product.
     */
    private function create_product($product_data) {
        // Determine product type
        $product_type = 'simple';
        if (!empty($product_data['type'])) {
            switch ($product_data['type']) {
                case 'variable':
                    $product_type = 'variable';
                    break;
                case 'grouped':
                    $product_type = 'grouped';
                    break;
                case 'external':
                    $product_type = 'external';
                    break;
                default:
                    $product_type = 'simple';
            }
        }
        
        // Create product
        $product = new WC_Product_Simple();
        
        // Set product data
        $this->set_product_data($product, $product_data);
        
        // Save product
        $product_id = $product->save();
        
        if (!$product_id) {
            return new WP_Error('product_creation_failed', __('Failed to create product.', 'branddrive-woocommerce'));
        }
        
        // Store BrandDrive product ID
        update_post_meta($product_id, '_branddrive_product_id', $product_data['id']);
        
        return $product_id;
    }
    
    /**
     * Update an existing product.
     */
    private function update_product($product_id, $product_data) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', __('Product not found.', 'branddrive-woocommerce'));
        }
        
        // Set product data
        $this->set_product_data($product, $product_data);
        
        // Save product
        $product_id = $product->save();
        
        if (!$product_id) {
            return new WP_Error('product_update_failed', __('Failed to update product.', 'branddrive-woocommerce'));
        }
        
        return $product_id;
    }
    
    /**
     * Set product data.
     */
    private function set_product_data($product, $product_data) {
        // Set basic product data
        if (!empty($product_data['name'])) {
            $product->set_name($product_data['name']);
        }
        
        if (isset($product_data['description'])) {
            $product->set_description($product_data['description']);
        }
        
        if (isset($product_data['short_description'])) {
            $product->set_short_description($product_data['short_description']);
        }
        
        if (!empty($product_data['sku'])) {
            $product->set_sku($product_data['sku']);
        }
        
        if (isset($product_data['regular_price'])) {
            $product->set_regular_price($product_data['regular_price']);
        }
        
        if (isset($product_data['sale_price'])) {
            $product->set_sale_price($product_data['sale_price']);
        }
        
        if (isset($product_data['stock_quantity'])) {
            $product->set_stock_quantity($product_data['stock_quantity']);
            $product->set_manage_stock(true);
            
            if ($product_data['stock_quantity'] > 0) {
                $product->set_stock_status('instock');
            } else {
                $product->set_stock_status('outofstock');
            }
        }
        
        if (isset($product_data['weight'])) {
            $product->set_weight($product_data['weight']);
        }
        
        if (isset($product_data['dimensions'])) {
            if (isset($product_data['dimensions']['length'])) {
                $product->set_length($product_data['dimensions']['length']);
            }
            
            if (isset($product_data['dimensions']['width'])) {
                $product->set_width($product_data['dimensions']['width']);
            }
            
            if (isset($product_data['dimensions']['height'])) {
                $product->set_height($product_data['dimensions']['height']);
            }
        }
        
        // Set product categories
        if (!empty($product_data['categories'])) {
            $category_ids = array();
            
            foreach ($product_data['categories'] as $category_name) {
                $term = get_term_by('name', $category_name, 'product_cat');
                
                if (!$term) {
                    // Create category if it doesn't exist
                    $term = wp_insert_term($category_name, 'product_cat');
                    
                    if (!is_wp_error($term)) {
                        $category_ids[] = $term['term_id'];
                    }
                } else {
                    $category_ids[] = $term->term_id;
                }
            }
            
            if (!empty($category_ids)) {
                $product->set_category_ids($category_ids);
            }
        }
        
        // Set product images
        if (!empty($product_data['images'])) {
            $image_ids = array();
            
            foreach ($product_data['images'] as $index => $image_url) {
                $attachment_id = $this->upload_image_from_url($image_url, $product->get_name());
                
                if ($attachment_id) {
                    if ($index === 0) {
                        $product->set_image_id($attachment_id);
                    } else {
                        $image_ids[] = $attachment_id;
                    }
                }
            }
            
            if (!empty($image_ids)) {
                $product->set_gallery_image_ids($image_ids);
            }
        }
        
        // Set product attributes
        if (!empty($product_data['attributes'])) {
            $attributes = array();
            
            foreach ($product_data['attributes'] as $attribute) {
                if (empty($attribute['name'])) {
                    continue;
                }
                
                $attribute_name = wc_clean($attribute['name']);
                $attribute_values = isset($attribute['values']) ? (array) $attribute['values'] : array();
                
                $attribute_object = new WC_Product_Attribute();
                $attribute_object->set_name($attribute_name);
                $attribute_object->set_options($attribute_values);
                $attribute_object->set_visible(true);
                $attribute_object->set_variation(false);
                
                $attributes[] = $attribute_object;
            }
            
            $product->set_attributes($attributes);
        }
        
        // Set product meta data
        if (!empty($product_data['meta_data'])) {
            foreach ($product_data['meta_data'] as $meta) {
                if (!empty($meta['key'])) {
                    $product->update_meta_data($meta['key'], $meta['value']);
                }
            }
        }
        
        return $product;
    }
    
    /**
     * Upload image from URL.
     */
    private function upload_image_from_url($image_url, $product_name) {
        // Get the file
        $response = wp_remote_get($image_url, array(
            'timeout' => 30
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        
        if (empty($image_data)) {
            return false;
        }
        
        // Get file name and extension
        $file_name = basename($image_url);
        
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        
        // Generate unique file name
        $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
        $upload_file = $upload_dir['path'] . '/' . $unique_file_name;
        
        // Save image to upload directory
        file_put_contents($upload_file, $image_data);
        
        // Check image file type
        $wp_filetype = wp_check_filetype($unique_file_name, null);
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($product_name),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $upload_file);
        
        if (!$attachment_id) {
            return false;
        }
        
        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return $attachment_id;
    }
}

