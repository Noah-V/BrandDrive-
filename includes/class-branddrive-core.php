<?php
/**
 * The core plugin class.
 */
class BrandDrive_Core {
    /**
     * The settings instance.
     */
    public $settings;

    /**
     * The API instance.
     */
    public $api;

    /**
     * The encryption instance.
     */
    public $encryption;

    /**
     * The checkout events instance.
     */
    public $checkout_events;

    /**
     * The product import instance.
     */
    public $product_import;

    /**
     * Initialize the class.
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        // Load dependencies
        $this->load_dependencies();

        // Initialize components
        $this->settings = new BrandDrive_Settings();
        $this->encryption = new BrandDrive_Encryption();
        $this->api = new BrandDrive_API();
        $this->checkout_events = new BrandDrive_Checkout_Events();
        $this->product_import = new BrandDrive_Product_Import();

        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies() {
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-settings.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-encryption.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-api.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-checkout-events.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-product-import.php';
    }

    /**
     * Register the admin menu.
     */
    public function register_admin_menu() {
        // Add top level menu with properly sized icon
        add_menu_page(
            __('BrandDrive', 'branddrive-woocommerce'),
            __('BrandDrive', 'branddrive-woocommerce'),
            'manage_woocommerce',
            'branddrive',
            array($this, 'render_admin_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 36 36" fill="none"><path d="M34.7333 15.9973V28.9446C34.7333 34.6516 30.1056 39.278 24.3999 39.278H12.2543C8.1056 39.278 4.5266 36.832 2.8816 33.3046C2.24579 31.9392 1.91751 30.4508 1.91994 28.9446V15.9973C1.91994 10.2903 6.54727 5.66396 12.2533 5.66396H24.3999C26.3364 5.66055 28.2343 6.20457 29.8749 7.23329C32.7933 9.05996 34.7333 12.3026 34.7333 15.9973Z" fill="#0A5FFF"/><path d="M34.7333 15.9973V23.4213C30.6622 30.2497 24.7044 35.756 17.5769 39.2773H12.2543C8.1056 39.2773 4.5266 36.8313 2.8816 33.304C2.24589 31.9387 1.9176 30.4506 1.91993 28.9446V15.9973C1.91993 10.2903 6.54727 5.66396 12.2533 5.66396H24.3999C26.3364 5.66055 28.2343 6.20457 29.8749 7.23329C32.7933 9.05996 34.7333 12.3026 34.7333 15.9973Z" fill="#5C94FF"/><path d="M24.3999 5.66461C26.3363 5.661 28.2343 6.20479 29.8749 7.23327C25.6273 19.7063 15.5443 29.4813 2.8816 33.3039C2.24589 31.9387 1.9176 30.4506 1.91993 28.9446V15.9973C1.91993 10.2903 6.54727 5.66394 12.2533 5.66394L24.3999 5.66461Z" fill="#337AFF"/><path d="M12.2543 5.66467H18.8509C14.951 12.7294 9.0643 18.4949 1.91992 22.247V15.9973C1.91992 10.2903 6.54725 5.66467 12.2543 5.66467Z" fill="#0A5FFF"/><path d="M23.5347 14.9999C24.0865 14.9759 24.5532 15.4038 24.5772 15.9556L24.9129 23.6759C24.9244 23.9408 24.8302 24.1995 24.651 24.395L21.3121 28.0375L17.8198 31.8473C17.2178 32.504 16.1222 32.1051 16.0836 31.215L15.8347 25.4924C15.8108 24.9406 15.344 24.5128 14.7922 24.5367L9.07005 24.7855C8.17998 24.8242 7.68745 23.7675 8.28945 23.1107L11.7814 19.3014L15.1203 15.659C15.2995 15.4634 15.549 15.3471 15.814 15.3356L23.5347 14.9999Z" fill="white"/></svg>'),
            58 // Position after Posts
        );

        // Add submenu items
        add_submenu_page(
            'branddrive',
            __('Dashboard', 'branddrive-woocommerce'),
            __('Dashboard', 'branddrive-woocommerce'),
            'manage_woocommerce',
            'branddrive',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'branddrive',
            __('Sync Orders', 'branddrive-woocommerce'),
            __('Sync Orders', 'branddrive-woocommerce'),
            'manage_woocommerce',
            'branddrive-sync-orders',
            array($this, 'render_sync_orders_page')
        );
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-header.php';

        switch ($active_tab) {
            case 'sync':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/sync-orders.php';
                break;
            default:
                include BRANDDRIVE_PLUGIN_DIR . 'templates/dashboard.php';
                break;
        }

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-footer.php';
    }

    /**
     * Render the sync orders page.
     */
    public function render_sync_orders_page() {
        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-header.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/sync-orders.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-footer.php';
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue on BrandDrive admin pages
        if (strpos($hook, 'branddrive') === false) {
            return;
        }

        wp_enqueue_style(
            'branddrive-admin',
            BRANDDRIVE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BRANDDRIVE_VERSION
        );

        wp_enqueue_script(
            'branddrive-admin',
            BRANDDRIVE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            BRANDDRIVE_VERSION,
            true
        );

        wp_localize_script('branddrive-admin', 'branddrive_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('branddrive-admin'),
            'i18n' => array(
                'verify_success' => __('Plugin key verified successfully!', 'branddrive-woocommerce'),
                'verify_error' => __('Failed to verify plugin key. Please check and try again.', 'branddrive-woocommerce'),
                'save_success' => __('Settings saved successfully!', 'branddrive-woocommerce'),
                'save_error' => __('Failed to save settings.', 'branddrive-woocommerce'),
                'import_success' => __('Products imported successfully!', 'branddrive-woocommerce'),
                'import_error' => __('Failed to import products.', 'branddrive-woocommerce'),
                'confirm_import' => __('Are you sure you want to import products from BrandDrive? This may overwrite existing products.', 'branddrive-woocommerce')
            )
        ));
    }

    /**
     * Admin notice for missing WooCommerce.
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('BrandDrive for WooCommerce requires WooCommerce to be installed and active.', 'branddrive-woocommerce'); ?></p>
        </div>
        <?php
    }
}

