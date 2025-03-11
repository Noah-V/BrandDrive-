<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="branddrive-admin">
    <div class="branddrive-sidebar">
        <div class="branddrive-sidebar-items">
            <a href="<?php echo admin_url('admin.php?page=branddrive'); ?>" 
               class="branddrive-sidebar-item <?php echo $current_tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e('Dashboard', 'branddrive-woocommerce'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=sync'); ?>" 
               class="branddrive-sidebar-item <?php echo $current_tab === 'sync' ? 'active' : ''; ?>">
                <img src="<?php echo BRANDDRIVE_PLUGIN_URL; ?>assets/images/branddrive-icon.svg" alt="" class="branddrive-menu-icon">
                <?php _e('BrandDrive', 'branddrive-woocommerce'); ?>
            </a>
        </div>
    </div>
    
    <div class="branddrive-content">
        <?php if ($current_tab === 'sync'): ?>
            <a href="<?php echo admin_url('admin.php?page=branddrive'); ?>" class="branddrive-back-link">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                <?php _e('Back to dashboard', 'branddrive-woocommerce'); ?>
            </a>
        <?php endif; ?>

