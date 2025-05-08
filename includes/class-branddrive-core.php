<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * The core plugin class.
 */
class BrandDrive_Core
{
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

    /***
     * The product export instance
     */

    public $product_export;

    /**
     * The categories instance.
     */
    public $categories;

    /**
     * The product fields instance.
     */
    public $product_fields;

    /**
     * The product export CSV instance.
     */
    public $product_export_csv;


    /**
     * Initialize the class.
     */
    public function init()
    {
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
//        $this->product_export = new BrandDrive_Product_Export();
        $this->product_export_csv = new BrandDrive_Product_Export_CSV();
        $this->categories = new BrandDrive_Categories();
        $this->product_fields = new BrandDrive_Product_Fields();

        // Register admin menu
        add_action('admin_menu', array($this, 'register_admin_menu'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        add_action('wp_ajax_branddrive_get_debug_log', array($this, 'ajax_get_debug_log'));
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies()
    {
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-settings.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-encryption.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-api.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-checkout-events.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-product-import.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-product-export.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-product-export-csv.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-categories.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/class-branddrive-product-fields.php';
        require_once BRANDDRIVE_PLUGIN_DIR . 'includes/svg-icons.php';
    }

    /**
     * AJAX handler for getting debug log content
     */
    public function ajax_get_debug_log() {
        check_ajax_referer('branddrive_debug_log', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to view debug logs.', 'branddrive-woocommerce')));
            return;
        }

        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/branddrive-csv-export-debug.log';

        if (!file_exists($log_file)) {
            wp_send_json_error(array('message' => __('Debug log file does not exist.', 'branddrive-woocommerce')));
            return;
        }

        $log_content = file_get_contents($log_file);

        if ($log_content === false) {
            wp_send_json_error(array('message' => __('Failed to read debug log file.', 'branddrive-woocommerce')));
            return;
        }

        wp_send_json_success(array('log_content' => $log_content));
    }

    /**
     * Register the admin menu.
     */
    public function register_admin_menu()
    {
        // Add top level menu with white icon for dark menu
        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 36 36" fill="none">
            <path d="M34.7333 15.9973V28.9446C34.7333 34.6516 30.1056 39.278 24.3999 39.278H12.2543C8.1056 39.278 4.5266 36.832 2.8816 33.3046C2.24579 31.9392 1.91751 30.4508 1.91994 28.9446V15.9973C1.91994 10.2903 6.54727 5.66396 12.2533 5.66396H24.3999C26.3364 5.66055 28.2343 6.20457 29.8749 7.23329C32.7933 9.05996 34.7333 12.3026 34.7333 15.9973Z" fill="white"/>
            <path d="M23.5347 14.9999C24.0865 14.9759 24.5532 15.4038 24.5772 15.9556L24.9129 23.6759C24.9244 23.9408 24.8302 24.1995 24.651 24.395L21.3121 28.0375L17.8198 31.8473C17.2178 32.504 16.1222 32.1051 16.0836 31.215L15.8347 25.4924C15.8108 24.9406 15.344 24.5128 14.7922 24.5367L9.07005 24.7855C8.17998 24.8242 7.68745 23.7675 8.28945 23.1107L11.7814 19.3014L15.1203 15.659C15.2995 15.4634 15.549 15.3471 15.814 15.3356L23.5347 14.9999Z" fill="#12141c"/>
        </svg>';

        // Add top level menu page
        add_menu_page(
            __('BrandDrive', 'branddrive-woocommerce'),
            __('BrandDrive', 'branddrive-woocommerce'),
            'manage_woocommerce',
            'branddrive',
            array($this, 'render_admin_page'),
            'data:image/svg+xml;base64,' . base64_encode($icon_svg),
            58 // Position after Posts
        );

        // We're not adding submenu items to keep the sidebar clean
        // But we'll keep the page handlers for direct URL access
    }

    /**
     * Render the admin page.
     */
    public function render_admin_page()
    {
        // Determine the active tab based on the page parameter
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Check if we're on a submenu page and set the active tab accordingly
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($page === 'branddrive-export-products') {
            $active_tab = 'export';
        } elseif ($page === 'branddrive-sync-orders') {
            $active_tab = 'sync';
        }

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-header.php';

        switch ($active_tab) {
            case 'settings':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/settings.php';
                break;
            case 'categories':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/categories.php';
                break;
            case 'import':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/import.php';
                break;
            case 'sync':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/sync-orders.php';
                break;
            case 'export':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/export.php';
                break;
            case 'export-csv':
                include BRANDDRIVE_PLUGIN_DIR . 'templates/export-csv.php';
                break;
            default:
                include BRANDDRIVE_PLUGIN_DIR . 'templates/dashboard.php';
                break;
        }

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-footer.php';
    }

    /**
     * Render the export page
     */
    public function render_export_to_branddrive_page()
    {
        // Set active tab to 'export' for the export page
        $active_tab = 'export';

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-header.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/export-csv.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-footer.php';
    }

    /**
     * Render the sync orders page.
     */
    public function render_sync_orders_page()
    {
        // Set active tab to 'sync' for the sync orders page
        $active_tab = 'sync';

        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-header.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/sync-orders.php';
        include BRANDDRIVE_PLUGIN_DIR . 'templates/admin-footer.php';
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_assets($hook)
    {
        // Only enqueue on BrandDrive admin pages
        if (!is_string($hook) || strpos($hook, 'branddrive') === false) {
            return;
        }

        // Enqueue common admin styles
        wp_enqueue_style(
            'branddrive-admin',
            BRANDDRIVE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BRANDDRIVE_VERSION
        );

        // Add inline CSS to hide WordPress admin sidebar submenu items
        $inline_css = "
            /* Hide WordPress admin sidebar submenu items for BrandDrive */
            #adminmenu .wp-submenu li a[href*='branddrive-export-products'],
            #adminmenu .wp-submenu li a[href*='branddrive-sync-orders'] {
                display: none;
            }
        ";
        wp_add_inline_style('branddrive-admin', $inline_css);

        // Get the active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Create nonce here, before any conditionals that might use it
        $nonce = wp_create_nonce('branddrive-admin');

        // Enqueue specific styles and scripts based on the active tab
        if ($active_tab === 'sync') {
            // Enqueue sync orders specific styles
            wp_enqueue_style(
                'branddrive-sync-orders',
                BRANDDRIVE_PLUGIN_URL . 'assets/css/sync-orders.css',
                array('branddrive-admin'),
                BRANDDRIVE_VERSION
            );

            // Enqueue sync orders specific scripts
            wp_enqueue_script(
                'branddrive-sync-orders',
                BRANDDRIVE_PLUGIN_URL . 'assets/js/sync-orders.js',
                array('jquery'),
                BRANDDRIVE_VERSION,
                true
            );

            // Enqueue nested dropdowns specific styles
            wp_enqueue_style(
                'branddrive-nested-dropdowns',
                BRANDDRIVE_PLUGIN_URL . 'assets/css/nested-dropdowns.css',
                array('branddrive-admin', 'branddrive-sync-orders'),
                BRANDDRIVE_VERSION
            );

            // Enqueue nested dropdowns specific scripts
            wp_enqueue_script(
                'branddrive-nested-dropdowns',
                BRANDDRIVE_PLUGIN_URL . 'assets/js/nested-dropdowns.js',
                array('jquery', 'branddrive-sync-orders'),
                BRANDDRIVE_VERSION,
                true
            );

            // Pass data to the sync orders script
            wp_localize_script('branddrive-sync-orders', 'branddrive_sync_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $nonce,
                'i18n' => array(
                    'sync_success' => __('Orders synced successfully!', 'branddrive-woocommerce'),
                    'sync_error' => __('Failed to sync orders.', 'branddrive-woocommerce'),
                    'confirm_sync' => __('Are you sure you want to sync these orders to BrandDrive?', 'branddrive-woocommerce')
                )
            ));
        }

        if ($active_tab === 'export') {
            wp_enqueue_style(
                'branddrive-export',
                BRANDDRIVE_PLUGIN_URL . 'assets/css/export.css',
                array('branddrive-admin'),
                BRANDDRIVE_VERSION
            );
        }

        if ($active_tab === 'export-csv') {
            wp_enqueue_style(
                'branddrive-export-csv',
                BRANDDRIVE_PLUGIN_URL . 'assets/css/export-csv.css',
                array('branddrive-admin'),
                BRANDDRIVE_VERSION
            );

            wp_enqueue_script(
                'branddrive-export-csv',
                BRANDDRIVE_PLUGIN_URL . 'assets/js/export-csv.js',
                array('jquery'),
                BRANDDRIVE_VERSION,
                true
            );

            wp_localize_script('branddrive-export-csv', 'branddrive_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => $nonce,
                'i18n' => array(
                    'export_success' => __('CSV file generated successfully!', 'branddrive-woocommerce'),
                    'export_error' => __('Failed to generate CSV file.', 'branddrive-woocommerce')
                )
            ));
        }

        // Enqueue common admin scripts
        wp_enqueue_script(
            'branddrive-admin',
            BRANDDRIVE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            BRANDDRIVE_VERSION,
            true
        );

        // Pass data to the admin script
        wp_localize_script('branddrive-admin', 'branddrive_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $nonce,
            'i18n' => array(
                'verify_success' => __('Plugin key verified successfully!', 'branddrive-woocommerce'),
                'verify_error' => __('Failed to verify plugin key. Please check and try again.', 'branddrive-woocommerce'),
                'save_success' => __('Settings saved successfully!', 'branddrive-woocommerce'),
                'save_error' => __('Failed to save settings.', 'branddrive-woocommerce'),
                'import_success' => __('Products imported successfully!', 'branddrive-woocommerce'),
                'import_error' => __('Failed to import products.', 'branddrive-woocommerce'),
                'confirm_import' => __('Are you sure you want to import products from BrandDrive? This may overwrite existing products.', 'branddrive-woocommerce'),
                'export_success' => __('Products exported successfully!', 'branddrive-woocommerce'),
                'export_error' => __('Failed to export products.', 'branddrive-woocommerce'),
                'confirm_export' => __('Are you sure you want to export products to BrandDrive? This may overwrite existing products in BrandDrive.', 'branddrive-woocommerce')
            )
        ));
    }

    /**
     * Admin notice for missing WooCommerce.
     */
    public function woocommerce_missing_notice()
    {
        ?>
        <div class="error">
            <p><?php _e('BrandDrive for WooCommerce requires WooCommerce to be installed and active.', 'branddrive-woocommerce'); ?></p>
        </div>
        <?php
    }
}
