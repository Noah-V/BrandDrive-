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
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=export'); ?>"
               class="branddrive-sidebar-item <?php echo $current_tab === 'export' ? 'active' : ''; ?>">
                <span class="dashicons dashicons-dashboard"></span>
                <?php _e('Export Products', 'branddrive-woocommerce'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=sync'); ?>"
               class="branddrive-sidebar-item <?php echo $current_tab === 'sync' ? 'active' : ''; ?>">
                <span class="branddrive-menu-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36" fill="none">
                        <path d="M23.5347 14.9999C24.0865 14.9759 24.5532 15.4038 24.5772 15.9556L24.9129 23.6759C24.9244 23.9408 24.8302 24.1995 24.651 24.395L21.3121 28.0375L17.8198 31.8473C17.2178 32.504 16.1222 32.1051 16.0836 31.215L15.8347 25.4924C15.8108 24.9406 15.344 24.5128 14.7922 24.5367L9.07005 24.7855C8.17998 24.8242 7.68745 23.7675 8.28945 23.1107L11.7814 19.3014L15.1203 15.659C15.2995 15.4634 15.549 15.3471 15.814 15.3356L23.5347 14.9999Z"
                              fill="white"/>
                    </svg>
                </span>
                <?php _e('Sync Orders', 'branddrive-woocommerce'); ?>
            </a>
            <!--            <a href="-->
<!--            --><?php //echo admin_url('admin.php?page=branddrive&tab=export'); ?><!--" class="nav-tab -->
<!--            --><?php //echo $current_tab
//                        === 'export' ? 'nav-tab-active' : ''; ?><!--">-->
<!--                            --><?php //_e('Export Products', 'branddrive-woocommerce'); ?>
<!--                        </a>-->
        </div>
    </div>

    <div class="branddrive-content">
<!--        --><?php //if ($current_tab === 'sync'): ?>
<!--            <a href="--><?php //echo admin_url('admin.php?page=branddrive'); ?><!--" class="branddrive-back-link">-->
<!--                <span class="dashicons dashicons-arrow-left-alt"></span>-->
<!--                --><?php //_e('Back to dashboard', 'branddrive-woocommerce'); ?>
<!--            </a>-->
<!--        --><?php //endif; ?>

