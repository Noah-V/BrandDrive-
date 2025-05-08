<?php
/**
 * Handles BrandDrive category integration with WooCommerce.
 */
class BrandDrive_Categories {
    /**
     * Constructor.
     */
    public function __construct() {
        // Add custom field to category edit form
        add_action('product_cat_add_form_fields', array($this, 'add_category_fields'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_fields'), 10, 2);

        // Save custom field value
        add_action('created_product_cat', array($this, 'save_category_fields'));
        add_action('edited_product_cat', array($this, 'save_category_fields'));
    }

    /**
     * Add custom field to add category form.
     */
    public function add_category_fields() {
        ?>
        <div class="form-field">
            <label for="branddrive_category_id"><?php _e('BrandDrive Category ID', 'branddrive-woocommerce'); ?></label>
            <input type="number" name="branddrive_category_id" id="branddrive_category_id" value="" />
            <p class="description"><?php _e('Enter the corresponding category ID from BrandDrive.', 'branddrive-woocommerce'); ?></p>
        </div>
        <?php
    }

    /**
     * Add custom field to edit category form.
     */
    public function edit_category_fields($term, $taxonomy) {
        $branddrive_category_id = get_term_meta($term->term_id, 'branddrive_category_id', true);
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="branddrive_category_id"><?php _e('BrandDrive Category ID', 'branddrive-woocommerce'); ?></label>
            </th>
            <td>
                <input type="number" name="branddrive_category_id" id="branddrive_category_id" value="<?php echo esc_attr($branddrive_category_id); ?>" />
                <p class="description"><?php _e('Enter the corresponding category ID from BrandDrive.', 'branddrive-woocommerce'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save custom field value.
     */
    public function save_category_fields($term_id) {
        if (isset($_POST['branddrive_category_id'])) {
            $branddrive_category_id = sanitize_text_field($_POST['branddrive_category_id']);
            update_term_meta($term_id, 'branddrive_category_id', $branddrive_category_id);
        }
    }

    /**
     * Get BrandDrive category ID for a WooCommerce category.
     */
    public function get_branddrive_category_id($wc_category_id) {
        $branddrive_category_id = get_term_meta($wc_category_id, 'branddrive_category_id', true);
        return !empty($branddrive_category_id) ? (int) $branddrive_category_id : null;
    }

    /**
     * Get BrandDrive category ID for a product.
     * If a product has multiple categories, returns the first one with a BrandDrive ID.
     */
    public function get_product_branddrive_category_id($product) {
        $category_ids = $product->get_category_ids();

        if (empty($category_ids)) {
            return null;
        }

        foreach ($category_ids as $category_id) {
            $branddrive_category_id = $this->get_branddrive_category_id($category_id);
            if (!empty($branddrive_category_id)) {
                return $branddrive_category_id;
            }
        }

        // If no category has a BrandDrive ID, return the first category ID as fallback
        return (int) $category_ids[0];
    }
}
