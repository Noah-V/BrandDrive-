<?php
/**
 * Adds BrandDrive-specific fields to WooCommerce products.
 */
class BrandDrive_Product_Fields {
    /**
     * Constructor.
     */
    public function __construct() {
        // Add custom field to product general tab
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_branddrive_fields'));

        // Save custom field value
        add_action('woocommerce_process_product_meta', array($this, 'save_branddrive_fields'));
    }

    /**
     * Add BrandDrive fields to product data.
     */
    public function add_branddrive_fields() {
        global $post;

        echo '<div class="options_group">';

        // BrandDrive Category ID field
        woocommerce_wp_text_input(
            array(
                'id'          => '_branddrive_category_id',
                'label'       => __('BrandDrive Category ID', 'branddrive-woocommerce'),
                'description' => __('Enter the category ID from BrandDrive for this product.', 'branddrive-woocommerce'),
                'desc_tip'    => true,
                'type'        => 'number',
                'custom_attributes' => array(
                    'step' => '1',
                    'min'  => '0'
                )
            )
        );

        echo '</div>';
    }

    /**
     * Save BrandDrive fields.
     */
    public function save_branddrive_fields($post_id) {
        // Save BrandDrive Category ID
        $branddrive_category_id = isset($_POST['_branddrive_category_id']) ? sanitize_text_field($_POST['_branddrive_category_id']) : '';
        update_post_meta($post_id, '_branddrive_category_id', $branddrive_category_id);
    }

    /**
     * Get BrandDrive category ID for a product.
     */
    public function get_product_branddrive_category_id($product) {
        $branddrive_category_id = $product->get_meta('_branddrive_category_id', true);

        if (!empty($branddrive_category_id)) {
            return (int) $branddrive_category_id;
        }

        return null;
    }
}
