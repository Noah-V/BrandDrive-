<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Check if integration is enabled and plugin key is set
$is_enabled = $branddrive->settings->is_enabled();
$has_plugin_key = !empty($branddrive->settings->get_plugin_key());
$can_import = $is_enabled && $has_plugin_key;
?>

<div class="branddrive-import">
    <div class="branddrive-import-header">
        <h2><?php _e('Import Products from BrandDrive', 'branddrive-woocommerce'); ?></h2>
        <p><?php _e('Import your BrandDrive products to your WooCommerce store.', 'branddrive-woocommerce'); ?></p>
    </div>
    
    <div id="branddrive_import_notice" class="branddrive-notice" style="display: none;"></div>
    
    <div class="branddrive-import-content">
        <?php if (!$is_enabled): ?>
            <div class="branddrive-notice branddrive-notice-warning">
                <p><?php _e('BrandDrive integration is currently disabled. Please enable it in the settings to import products.', 'branddrive-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="button">
                    <?php _e('Go to Settings', 'branddrive-woocommerce'); ?>
                </a>
            </div>
        <?php elseif (!$has_plugin_key): ?>
            <div class="branddrive-notice branddrive-notice-warning">
                <p><?php _e('Plugin key is not configured. Please add your BrandDrive plugin key in the settings to import products.', 'branddrive-woocommerce'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="button">
                    <?php _e('Configure Plugin Key', 'branddrive-woocommerce'); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="branddrive-import-info">
                <h3><?php _e('Import Information', 'branddrive-woocommerce'); ?></h3>
                <p><?php _e('Importing products from BrandDrive will:', 'branddrive-woocommerce'); ?></p>
                <ul>
                    <li><?php _e('Create new products in WooCommerce for products that do not exist.', 'branddrive-woocommerce'); ?></li>
                    <li><?php _e('Update existing products that have been previously imported from BrandDrive.', 'branddrive-woocommerce'); ?></li>
                    <li><?php _e('Import product images, categories, attributes, and other metadata.', 'branddrive-woocommerce'); ?></li>
                </ul>
                <p><strong><?php _e('Note:', 'branddrive-woocommerce'); ?></strong> <?php _e('This process may take some time depending on the number of products and images to import.', 'branddrive-woocommerce'); ?></p>
            </div>
            
            <div class="branddrive-import-actions">
                <button id="branddrive_import_products" class="button button-primary">
                    <?php _e('Import Products', 'branddrive-woocommerce'); ?>
                </button>
                <div id="branddrive_import_progress" class="branddrive-import-progress" style="display: none;">
                    <span class="spinner is-active"></span>
                    <span class="branddrive-import-progress-text"><?php _e('Importing products...', 'branddrive-woocommerce'); ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

