<?php
//// If this file is called directly, abort.
//if (!defined('WPINC')) {
//    die;
//}
//
//global $branddrive;
//
//// Check if integration is enabled and plugin key is set
//$is_enabled = $branddrive->settings->is_enabled();
//$has_plugin_key = !empty($branddrive->settings->get_plugin_key());
//$can_export = $is_enabled && $has_plugin_key;
//?>
<!---->
<!--<div class="branddrive-export">-->
<!--    <div class="branddrive-export-header">-->
<!--        <h2>--><?php //_e('Export Products to BrandDrive', 'branddrive-woocommerce'); ?><!--</h2>-->
<!--        <p>--><?php //_e('Export your WooCommerce products to BrandDrive.', 'branddrive-woocommerce'); ?><!--</p>-->
<!--    </div>-->
<!---->
<!--    <div id="branddrive_export_notice" class="branddrive-notice" style="display: none;"></div>-->
<!---->
<!--    <div class="branddrive-export-content">-->
<!--        --><?php //if (!$is_enabled): ?>
<!--            <div class="branddrive-notice branddrive-notice-warning">-->
<!--                <p>--><?php //_e('BrandDrive integration is currently disabled. Please enable it in the settings to export products.', 'branddrive-woocommerce'); ?><!--</p>-->
<!--                <a href="--><?php //echo admin_url('admin.php?page=branddrive&tab=settings'); ?><!--" class="button">-->
<!--                    --><?php //_e('Go to Settings', 'branddrive-woocommerce'); ?>
<!--                </a>-->
<!--            </div>-->
<!--        --><?php //elseif (!$has_plugin_key): ?>
<!--            <div class="branddrive-notice branddrive-notice-warning">-->
<!--                <p>--><?php //_e('Plugin key is not configured. Please add your BrandDrive plugin key in the settings to export products.', 'branddrive-woocommerce'); ?><!--</p>-->
<!--                <a href="--><?php //echo admin_url('admin.php?page=branddrive&tab=settings'); ?><!--" class="button">-->
<!--                    --><?php //_e('Configure Plugin Key', 'branddrive-woocommerce'); ?>
<!--                </a>-->
<!--            </div>-->
<!--        --><?php //else: ?>
<!--            <div class="branddrive-export-info">-->
<!--                <h3>--><?php //_e('Export Information', 'branddrive-woocommerce'); ?><!--</h3>-->
<!--                <p>--><?php //_e('Exporting products to BrandDrive will:', 'branddrive-woocommerce'); ?><!--</p>-->
<!--                <ul>-->
<!--                    <li>--><?php //_e('Create new products in BrandDrive for products that do not exist.', 'branddrive-woocommerce'); ?><!--</li>-->
<!--                    <li>--><?php //_e('Update existing products in BrandDrive that have been previously exported.', 'branddrive-woocommerce'); ?><!--</li>-->
<!--                    <li>--><?php //_e('Export product images, categories, attributes, and other metadata.', 'branddrive-woocommerce'); ?><!--</li>-->
<!--                    <li>--><?php //_e('Handle different product types (Simple, Grouped, Variable) appropriately.', 'branddrive-woocommerce'); ?><!--</li>-->
<!--                </ul>-->
<!--                <p><strong>--><?php //_e('Note:', 'branddrive-woocommerce'); ?><!--</strong> --><?php //_e('This process may take some time depending on the number of products and images to export.', 'branddrive-woocommerce'); ?><!--</p>-->
<!--            </div>-->
<!---->
<!--            <div class="branddrive-export-options">-->
<!--                <h3>--><?php //_e('Export Options', 'branddrive-woocommerce'); ?><!--</h3>-->
<!---->
<!--                <div class="branddrive-export-option">-->
<!--                    <h4>--><?php //_e('Export All Products', 'branddrive-woocommerce'); ?><!--</h4>-->
<!--                    <p>--><?php //_e('Export all published products from your WooCommerce store to BrandDrive.', 'branddrive-woocommerce'); ?><!--</p>-->
<!--                    <button id="branddrive_export_all_products" class="button button-primary">-->
<!--                        --><?php //_e('Export All Products', 'branddrive-woocommerce'); ?>
<!--                    </button>-->
<!--                </div>-->
<!---->
<!--                <div class="branddrive-export-option">-->
<!--                    <h4>--><?php //_e('Export Selected Products', 'branddrive-woocommerce'); ?><!--</h4>-->
<!--                    <p>--><?php //_e('You can export selected products from the WooCommerce Products page using the bulk action "Export to BrandDrive".', 'branddrive-woocommerce'); ?><!--</p>-->
<!--                    <a href="--><?php //echo admin_url('edit.php?post_type=product'); ?><!--" class="button">-->
<!--                        --><?php //_e('Go to Products', 'branddrive-woocommerce'); ?>
<!--                    </a>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <div id="branddrive_export_progress" class="branddrive-export-progress" style="display: none;">-->
<!--                <span class="spinner is-active"></span>-->
<!--                <span class="branddrive-export-progress-text">--><?php //_e('Exporting products...', 'branddrive-woocommerce'); ?><!--</span>-->
<!--            </div>-->
<!--        --><?php //endif; ?>
<!--    </div>-->
<!--</div>-->
<!---->

<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Check if integration is enabled and plugin key is set
$is_enabled = $branddrive->settings->is_enabled();
$has_plugin_key = !empty($branddrive->settings->get_plugin_key());
$can_export = $is_enabled && $has_plugin_key;

// Get all products
$products = wc_get_products(array(
    'limit' => -1,
    'status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC',
));

// Get products that have been exported to BrandDrive
$exported_products = array();
foreach ($products as $product) {
    $branddrive_id = get_post_meta($product->get_id(), '_branddrive_product_id', true);
    if (!empty($branddrive_id)) {
        $exported_products[$product->get_id()] = $branddrive_id;
    }
}
?>

<div class="branddrive-export">
    <h2 class="branddrive-card-title"><?php _e('Export Products to BrandDrive', 'branddrive-woocommerce'); ?></h2>
    <p class="branddrive-card-description"><?php _e('Export your WooCommerce products to BrandDrive.', 'branddrive-woocommerce'); ?></p>

<!--    <div id="branddrive_export_notice" class="branddrive-notification" style="display: none;"></div>-->

    <?php if (!$is_enabled): ?>
        <div class="branddrive-card">
            <div class="branddrive-notification error">
                <p><?php _e('BrandDrive integration is currently disabled. Please enable it in the settings to export products.', 'branddrive-woocommerce'); ?></p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="branddrive-button branddrive-button-primary">
                <?php _e('Go to Settings', 'branddrive-woocommerce'); ?>
            </a>
        </div>
    <?php elseif (!$has_plugin_key): ?>
        <div class="branddrive-card">
            <div class="branddrive-notification error">
                <p><?php _e('Plugin key is not configured. Please add your BrandDrive plugin key in the settings to export products.', 'branddrive-woocommerce'); ?></p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="branddrive-button branddrive-button-primary">
                <?php _e('Configure Plugin Key', 'branddrive-woocommerce'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="branddrive-card">
            <div class="branddrive-export-header">
                <h2 class="branddrive-card-title"><?php _e('Products', 'branddrive-woocommerce'); ?></h2>
                <div class="branddrive-export-actions">
                    <button id="branddrive_export_all_products" class="branddrive-button branddrive-button-primary">
                        <?php _e('Export All Products', 'branddrive-woocommerce'); ?>
                    </button>
                    <div id="branddrive_export_progress" style="display: none;">
                        <span class="spinner is-active"></span>
                        <span><?php _e('Exporting products...', 'branddrive-woocommerce'); ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="branddrive-notification info">
                    <p><?php _e('No products found in your WooCommerce store.', 'branddrive-woocommerce'); ?></p>
                </div>
            <?php else: ?>
                <div class="branddrive-table-container" style="margin-top: 20px;">
                    <table class="branddrive-table">
                        <thead>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" id="branddrive_select_all_products" />
                            </th>
                            <th><?php _e('Product', 'branddrive-woocommerce'); ?></th>
                            <th><?php _e('SKU', 'branddrive-woocommerce'); ?></th>
                            <th><?php _e('Price', 'branddrive-woocommerce'); ?></th>
                            <th><?php _e('Type', 'branddrive-woocommerce'); ?></th>
                            <th><?php _e('Status', 'branddrive-woocommerce'); ?></th>
                            <th><?php _e('Actions', 'branddrive-woocommerce'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php
                            $product_id = $product->get_id();
                            $is_exported = isset($exported_products[$product_id]);
                            $status_class = $is_exported ? 'exported' : 'not-exported';
                            $status_text = $is_exported ? __('Exported', 'branddrive-woocommerce') : __('Not Exported', 'branddrive-woocommerce');
                            ?>
                            <tr data-product-id="<?php echo esc_attr($product_id); ?>">
                                <td class="check-column">
                                    <input type="checkbox" class="branddrive_product_checkbox" value="<?php echo esc_attr($product_id); ?>" />
                                </td>
                                <td>
                                    <?php if ($product->get_image_id()): ?>
                                        <img src="<?php echo wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="branddrive-product-thumbnail" />
                                    <?php endif; ?>
                                    <strong><?php echo esc_html($product->get_name()); ?></strong>
                                </td>
                                <td><?php echo esc_html($product->get_sku()); ?></td>
                                <td><?php echo wc_price($product->get_price()); ?></td>
                                <td><?php echo ucfirst($product->get_type()); ?></td>
                                <td>
                                        <span class="branddrive-status-badge <?php echo esc_attr($status_class); ?>">
                                            <?php echo esc_html($status_text); ?>
                                        </span>
                                </td>
                                <td>
                                    <button class="branddrive-button branddrive-export-single-product" data-product-id="<?php echo esc_attr($product_id); ?>">
                                        <?php echo $is_exported ? __('Re-export', 'branddrive-woocommerce') : __('Export', 'branddrive-woocommerce'); ?>
                                    </button>
                                    <?php if ($is_exported): ?>
                                        <span class="branddrive-id-badge" title="<?php _e('BrandDrive ID', 'branddrive-woocommerce'); ?>">
                                                ID: <?php echo esc_html($exported_products[$product_id]); ?>
                                            </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="branddrive-bulk-actions">
                    <select id="branddrive_bulk_action">
                        <option value=""><?php _e('Bulk Actions', 'branddrive-woocommerce'); ?></option>
                        <option value="export"><?php _e('Export Selected', 'branddrive-woocommerce'); ?></option>
                    </select>
                    <button id="branddrive_apply_bulk_action" class="branddrive-button"><?php _e('Apply', 'branddrive-woocommerce'); ?></button>
                </div>
            <?php endif; ?>
        </div>

        <div class="branddrive-card">
            <h2 class="branddrive-card-title"><?php _e('Export Information', 'branddrive-woocommerce'); ?></h2>
            <p class="branddrive-card-description"><?php _e('Exporting products to BrandDrive will:', 'branddrive-woocommerce'); ?></p>
            <ul>
                <li><?php _e('Create new products in BrandDrive for products that do not exist.', 'branddrive-woocommerce'); ?></li>
                <li><?php _e('Update existing products in BrandDrive that have been previously exported.', 'branddrive-woocommerce'); ?></li>
                <li><?php _e('Export product images, categories, attributes, and other metadata.', 'branddrive-woocommerce'); ?></li>
                <li><?php _e('Handle different product types (Simple, Grouped, Variable) appropriately.', 'branddrive-woocommerce'); ?></li>
            </ul>
            <p><strong><?php _e('Note:', 'branddrive-woocommerce'); ?></strong> <?php _e('This process may take some time depending on the number of products and images to export.', 'branddrive-woocommerce'); ?></p>
        </div>
    <?php endif; ?>
</div>
