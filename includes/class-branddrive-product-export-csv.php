<?php
/**
 * Handles product export to CSV for BrandDrive.
 */
class BrandDrive_Product_Export_CSV {
    /**
     * Constructor.
     */
    public function __construct() {
        // Register AJAX handler for CSV export
        add_action('wp_ajax_branddrive_export_products_csv', array($this, 'ajax_export_products_csv'));

        // Add export page to admin menu
        add_action('admin_menu', array($this, 'add_export_csv_page'));
    }

    /**
     * Add export CSV page to admin menu.
     */
    public function add_export_csv_page() {
        add_submenu_page(
            '', // No parent menu
            __('Export Products to CSV', 'branddrive-woocommerce'),
            __('Export Products to CSV', 'branddrive-woocommerce'),
            'manage_woocommerce',
            'branddrive-export-csv',
            array($this, 'render_export_csv_page')
        );
    }

    /**
     * Render export CSV page.
     */
    public function render_export_csv_page() {
        include BRANDDRIVE_PLUGIN_DIR . 'templates/export-csv.php';
    }

    /**
     * AJAX handler for exporting products to CSV.
     */
    public function ajax_export_products_csv() {
        try {
            // Debug log
            $this->log_debug('Starting CSV export process');

            // Check nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'branddrive-admin')) {
                $this->log_debug('Nonce verification failed');
                wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'branddrive-woocommerce')));
                return;
            }

            // Check permissions
            if (!current_user_can('manage_woocommerce')) {
                $this->log_debug('User does not have permission');
                wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'branddrive-woocommerce')));
                return;
            }

            // Get export parameters - ensure we're not passing null values to string functions
            $columns_param = isset($_POST['columns']) ? $_POST['columns'] : '';
            $product_types_param = isset($_POST['product_types']) ? $_POST['product_types'] : '';
            $categories_param = isset($_POST['categories']) ? $_POST['categories'] : '';

            $this->log_debug('Received parameters: ' .
                'columns: ' . print_r($columns_param, true) . ', ' .
                'product_types: ' . print_r($product_types_param, true) . ', ' .
                'categories: ' . print_r($categories_param, true));

            // Convert comma-separated strings to arrays if needed
            $columns = array();
            if (is_array($columns_param)) {
                $columns = $columns_param;
            } elseif (!empty($columns_param)) {
                $columns = explode(',', $columns_param);
            }

            $product_types = array();
            if (is_array($product_types_param)) {
                $product_types = $product_types_param;
            } elseif (!empty($product_types_param)) {
                $product_types = explode(',', $product_types_param);
            }

            $categories = array();
            if (is_array($categories_param)) {
                $categories = $categories_param;
            } elseif (!empty($categories_param)) {
                $categories = explode(',', $categories_param);
            }

            // Sanitize arrays
            $columns = array_map('sanitize_text_field', $columns);
            $product_types = array_map('sanitize_text_field', $product_types);
            $categories = array_map('sanitize_text_field', $categories);

            $export_custom_meta = isset($_POST['export_custom_meta']) && $_POST['export_custom_meta'] === '1';

            $this->log_debug('Processed parameters: ' .
                'columns: ' . print_r($columns, true) . ', ' .
                'product_types: ' . print_r($product_types, true) . ', ' .
                'categories: ' . print_r($categories, true) . ', ' .
                'export_custom_meta: ' . ($export_custom_meta ? 'true' : 'false'));

            // Generate CSV
            $csv_data = $this->generate_csv($columns, $product_types, $categories, $export_custom_meta);

            if (is_wp_error($csv_data)) {
                $this->log_debug('Error generating CSV: ' . $csv_data->get_error_message());
                wp_send_json_error(array('message' => $csv_data->get_error_message()));
                return;
            }

            $this->log_debug('CSV generated successfully, size: ' . strlen($csv_data) . ' bytes');

            // Return CSV data
            wp_send_json_success(array(
                'csv_data' => $csv_data,
                'filename' => 'branddrive-products-' . date('Y-m-d') . '.csv',
                'message' => __('CSV file generated successfully!', 'branddrive-woocommerce')
            ));

        } catch (Exception $e) {
            $this->log_debug('Exception caught: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            wp_send_json_error(array('message' => __('An error occurred: ', 'branddrive-woocommerce') . $e->getMessage()));
        }
    }

    /**
     * Generate CSV file.
     */
    private function generate_csv($columns, $product_types, $categories, $export_custom_meta) {
        try {
            $this->log_debug('Starting generate_csv function');

            // Default columns if none selected
            if (empty($columns)) {
                $columns = array('id', 'name', 'sku', 'price', 'stock_quantity', 'categories', 'images', 'description');
                $this->log_debug('Using default columns: ' . implode(', ', $columns));
            }

            // Query parameters
            $args = array(
                'limit' => -1,
                'status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            );

            // Filter by product type
            if (!empty($product_types) && !in_array('all', $product_types)) {
                $args['type'] = $product_types;
                $this->log_debug('Filtering by product types: ' . implode(', ', $product_types));
            }

            // Filter by category
            if (!empty($categories) && !in_array('all', $categories)) {
                $args['category'] = $categories;
                $this->log_debug('Filtering by categories: ' . implode(', ', $categories));
            }

            $this->log_debug('WC_Product_Query args: ' . print_r($args, true));

            // Get products
            $products = wc_get_products($args);

            if (empty($products)) {
                $this->log_debug('No products found matching criteria');
                return new WP_Error('no_products', __('No products found matching your criteria.', 'branddrive-woocommerce'));
            }

            $this->log_debug('Found ' . count($products) . ' products');

            // Start output buffer to capture CSV data
            ob_start();

            // Create CSV file
            $csv = fopen('php://output', 'w');

            // Add UTF-8 BOM for Excel compatibility
            fputs($csv, "\xEF\xBB\xBF");

            // Add header row
            $header = $this->get_csv_header($columns, $export_custom_meta);
            fputcsv($csv, $header);

            $this->log_debug('CSV header created with ' . count($header) . ' columns');

            // Add product rows
            $row_count = 0;
            foreach ($products as $product) {
                try {
                    $row = $this->get_product_csv_row($product, $columns, $export_custom_meta);
                    fputcsv($csv, $row);
                    $row_count++;
                } catch (Exception $e) {
                    $this->log_debug('Error processing product ID ' . $product->get_id() . ': ' . $e->getMessage());
                }
            }

            $this->log_debug('Added ' . $row_count . ' product rows to CSV');

            fclose($csv);

            // Get CSV data from output buffer
            $csv_data = ob_get_clean();

            $this->log_debug('CSV generation complete');

            return $csv_data;

        } catch (Exception $e) {
            $this->log_debug('Exception in generate_csv: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return new WP_Error('csv_generation_failed', __('Failed to generate CSV: ', 'branddrive-woocommerce') . $e->getMessage());
        }
    }

    /**
     * Get CSV header row.
     */
    private function get_csv_header($columns, $export_custom_meta) {
        try {
            $header = array();

            // Add selected columns to header
            foreach ($columns as $column) {
                switch ($column) {
                    case 'id':
                        $header[] = __('ID', 'branddrive-woocommerce');
                        break;
                    case 'name':
                        $header[] = __('Name', 'branddrive-woocommerce');
                        break;
                    case 'sku':
                        $header[] = __('SKU', 'branddrive-woocommerce');
                        break;
                    case 'price':
                        $header[] = __('Price', 'branddrive-woocommerce');
                        break;
                    case 'regular_price':
                        $header[] = __('Regular Price', 'branddrive-woocommerce');
                        break;
                    case 'sale_price':
                        $header[] = __('Sale Price', 'branddrive-woocommerce');
                        break;
                    case 'stock_quantity':
                        $header[] = __('Stock Quantity', 'branddrive-woocommerce');
                        break;
                    case 'stock_status':
                        $header[] = __('Stock Status', 'branddrive-woocommerce');
                        break;
                    case 'weight':
                        $header[] = __('Weight', 'branddrive-woocommerce');
                        break;
                    case 'dimensions':
                        $header[] = __('Length', 'branddrive-woocommerce');
                        $header[] = __('Width', 'branddrive-woocommerce');
                        $header[] = __('Height', 'branddrive-woocommerce');
                        break;
                    case 'categories':
                        $header[] = __('Categories', 'branddrive-woocommerce');
                        break;
                    case 'tags':
                        $header[] = __('Tags', 'branddrive-woocommerce');
                        break;
                    case 'images':
                        $header[] = __('Featured Image', 'branddrive-woocommerce');
                        $header[] = __('Gallery Images', 'branddrive-woocommerce');
                        break;
                    case 'description':
                        $header[] = __('Description', 'branddrive-woocommerce');
                        break;
                    case 'short_description':
                        $header[] = __('Short Description', 'branddrive-woocommerce');
                        break;
                    case 'date_created':
                        $header[] = __('Date Created', 'branddrive-woocommerce');
                        break;
                    case 'date_modified':
                        $header[] = __('Date Modified', 'branddrive-woocommerce');
                        break;
                    case 'type':
                        $header[] = __('Product Type', 'branddrive-woocommerce');
                        break;
                    case 'attributes':
                        $header[] = __('Attributes', 'branddrive-woocommerce');
                        break;
                    case 'tax_class':
                        $header[] = __('Tax Class', 'branddrive-woocommerce');
                        break;
                    case 'tax_status':
                        $header[] = __('Tax Status', 'branddrive-woocommerce');
                        break;
                    default:
                        $header[] = $column;
                        break;
                }
            }

            // Add BrandDrive specific columns
            $header[] = __('BrandDrive Category ID', 'branddrive-woocommerce');
            $header[] = __('BrandDrive Product ID', 'branddrive-woocommerce');

            // Add custom meta columns if enabled
            if ($export_custom_meta) {
                $header[] = __('Custom Meta', 'branddrive-woocommerce');
            }

            return $header;

        } catch (Exception $e) {
            $this->log_debug('Exception in get_csv_header: ' . $e->getMessage());
            // Return a basic header to avoid breaking the process
            return array('ID', 'Name', 'SKU');
        }
    }

    /**
     * Get product CSV row.
     */
    private function get_product_csv_row($product, $columns, $export_custom_meta) {
        global $branddrive;

        try {
            $row = array();

            // Add selected columns to row
            foreach ($columns as $column) {
                switch ($column) {
                    case 'id':
                        $row[] = $product->get_id();
                        break;
                    case 'name':
                        $row[] = $product->get_name();
                        break;
                    case 'sku':
                        $row[] = $product->get_sku();
                        break;
                    case 'price':
                        $row[] = $product->get_price();
                        break;
                    case 'regular_price':
                        $row[] = $product->get_regular_price();
                        break;
                    case 'sale_price':
                        $row[] = $product->get_sale_price();
                        break;
                    case 'stock_quantity':
                        $row[] = $product->get_stock_quantity();
                        break;
                    case 'stock_status':
                        $row[] = $product->get_stock_status();
                        break;
                    case 'weight':
                        $row[] = $product->get_weight();
                        break;
                    case 'dimensions':
                        $row[] = $product->get_length();
                        $row[] = $product->get_width();
                        $row[] = $product->get_height();
                        break;
                    case 'categories':
                        $categories = array();
                        foreach ($product->get_category_ids() as $category_id) {
                            $category = get_term_by('id', $category_id, 'product_cat');
                            if ($category) {
                                $categories[] = $category->name;
                            }
                        }
                        $row[] = implode(', ', $categories);
                        break;
                    case 'tags':
                        $tags = array();
                        foreach ($product->get_tag_ids() as $tag_id) {
                            $tag = get_term_by('id', $tag_id, 'product_tag');
                            if ($tag) {
                                $tags[] = $tag->name;
                            }
                        }
                        $row[] = implode(', ', $tags);
                        break;
                    case 'images':
                        // Featured image
                        $featured_image = wp_get_attachment_url($product->get_image_id());
                        $row[] = $featured_image ? $featured_image : '';

                        // Gallery images
                        $gallery_images = array();
                        foreach ($product->get_gallery_image_ids() as $image_id) {
                            $image_url = wp_get_attachment_url($image_id);
                            if ($image_url) {
                                $gallery_images[] = $image_url;
                            }
                        }
                        $row[] = implode('|', $gallery_images);
                        break;
                    case 'description':
                        $row[] = strip_tags($product->get_description());
                        break;
                    case 'short_description':
                        $row[] = strip_tags($product->get_short_description());
                        break;
                    case 'date_created':
                        $date_created = $product->get_date_created();
                        $row[] = $date_created ? $date_created->format('Y-m-d H:i:s') : '';
                        break;
                    case 'date_modified':
                        $date_modified = $product->get_date_modified();
                        $row[] = $date_modified ? $date_modified->format('Y-m-d H:i:s') : '';
                        break;
                    case 'type':
                        $row[] = $product->get_type();
                        break;
                    case 'attributes':
                        $attributes = array();
                        foreach ($product->get_attributes() as $attribute) {
                            if ($attribute->is_taxonomy()) {
                                $attribute_name = wc_attribute_label($attribute->get_name());
                                $attribute_values = array();
                                foreach ($attribute->get_terms() as $term) {
                                    $attribute_values[] = $term->name;
                                }
                                $attributes[] = $attribute_name . ': ' . implode(', ', $attribute_values);
                            } else {
                                $attributes[] = $attribute->get_name() . ': ' . implode(', ', $attribute->get_options());
                            }
                        }
                        $row[] = implode('|', $attributes);
                        break;
                    case 'tax_class':
                        $row[] = $product->get_tax_class();
                        break;
                    case 'tax_status':
                        $row[] = $product->get_tax_status();
                        break;
                    default:
                        $row[] = '';
                        break;
                }
            }

            // Add BrandDrive specific columns
            if (method_exists($branddrive->product_fields, 'get_product_branddrive_category_id')) {
                $branddrive_category_id = $branddrive->product_fields->get_product_branddrive_category_id($product);
                $row[] = $branddrive_category_id ? $branddrive_category_id : '';
            } else {
                $this->log_debug('Method get_product_branddrive_category_id does not exist');
                $row[] = '';
            }

            $branddrive_product_id = get_post_meta($product->get_id(), '_branddrive_product_id', true);
            $row[] = $branddrive_product_id ? $branddrive_product_id : '';

            // Add custom meta if enabled
            if ($export_custom_meta) {
                $meta_data = array();
                foreach ($product->get_meta_data() as $meta) {
                    if (substr($meta->key, 0, 1) !== '_') { // Skip hidden meta
                        $meta_data[] = $meta->key . ': ' . maybe_serialize($meta->value);
                    }
                }
                $row[] = implode('|', $meta_data);
            }

            return $row;

        } catch (Exception $e) {
            $this->log_debug('Exception in get_product_csv_row for product ID ' . $product->get_id() . ': ' . $e->getMessage());
            // Return a basic row to avoid breaking the process
            return array($product->get_id(), $product->get_name(), $product->get_sku());
        }
    }

    /**
     * Log debug message
     */
    private function log_debug($message) {
        global $branddrive;

        if (isset($branddrive->settings) && $branddrive->settings->is_debug_mode()) {
            error_log('[BrandDrive CSV Export] ' . $message);
        }

        // Always write to a specific debug log file for CSV export
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/branddrive-csv-export-debug.log';

        $timestamp = date('[Y-m-d H:i:s]');
        file_put_contents($log_file, $timestamp . ' ' . $message . PHP_EOL, FILE_APPEND);
    }
}
