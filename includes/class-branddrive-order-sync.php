<?php
/**
 * Handles order synchronization with BrandDrive.
 *
 * PLACEHOLDER IMPLEMENTATION FOR TESTING UI ONLY
 */
class BrandDrive_Order_Sync {
    /**
     * Constructor.
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_branddrive_sync_orders', array($this, 'ajax_sync_orders'));
        add_action('wp_ajax_branddrive_sync_all_orders', array($this, 'ajax_sync_all_orders'));
    }

    /**
     * AJAX handler for syncing specific orders.
     *
     * PLACEHOLDER IMPLEMENTATION FOR TESTING UI ONLY
     */
    public function ajax_sync_orders() {
        // Check nonce
        check_ajax_referer('branddrive-sync', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to sync orders.', 'branddrive-woocommerce')));
            return;
        }

        // Get order IDs
        $order_ids = isset($_POST['order_ids']) ? (array) $_POST['order_ids'] : array();

        if (empty($order_ids)) {
            wp_send_json_error(array('message' => __('No orders selected for syncing.', 'branddrive-woocommerce')));
            return;
        }

        // Log the request for debugging
        error_log('PLACEHOLDER: Would sync these specific orders: ' . implode(', ', $order_ids));

        // Simulate processing time
        sleep(1);

        // Return success response
        wp_send_json_success(array(
            'message' => sprintf(__('%d orders synced successfully.', 'branddrive-woocommerce'), count($order_ids)),
            'synced' => $order_ids
        ));
    }

    /**
     * AJAX handler for syncing all orders matching filters.
     *
     * PLACEHOLDER IMPLEMENTATION FOR TESTING UI ONLY
     */
    public function ajax_sync_all_orders() {
        // Check nonce
        check_ajax_referer('branddrive-sync', 'nonce');

        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to sync orders.', 'branddrive-woocommerce')));
            return;
        }

        // Get filters
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();

        // Log the request for debugging
        error_log('PLACEHOLDER: Would sync ALL orders matching these filters: ' . json_encode($filters));

        // Simulate processing time
        sleep(1);

        // Return success response with a placeholder count
        $count = isset($_POST['total_count']) ? intval($_POST['total_count']) : 100;
        wp_send_json_success(array(
            'message' => sprintf(__('All %d orders synced successfully.', 'branddrive-woocommerce'), $count),
            'synced' => array('all' => true, 'filters' => $filters)
        ));
    }
}
