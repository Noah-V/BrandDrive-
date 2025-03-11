<?php
/**
 * Handles checkout events tracking.
 */
class BrandDrive_Checkout_Events {
    /**
     * Constructor.
     */
    public function __construct() {
        // Register WooCommerce hooks
        add_action('woocommerce_checkout_order_processed', array($this, 'track_order_created'), 10, 3);
        add_action('woocommerce_order_status_changed', array($this, 'track_order_status_changed'), 10, 4);
        add_action('woocommerce_payment_complete', array($this, 'track_payment_complete'));
    }
    
    /**
     * Track order created event.
     */
    public function track_order_created($order_id, $posted_data, $order) {
        $this->send_event('order_created', $order_id);
    }
    
    /**
     * Track order status changed event.
     */
    public function track_order_status_changed($order_id, $old_status, $new_status, $order) {
        $this->send_event('order_status_changed', $order_id, array(
            'old_status' => $old_status,
            'new_status' => $new_status
        ));
    }
    
    /**
     * Track payment complete event.
     */
    public function track_payment_complete($order_id) {
        $this->send_event('payment_complete', $order_id);
    }
    
    /**
     * Send checkout event to BrandDrive.
     */
    private function send_event($event_type, $order_id, $additional_data = array()) {
        global $branddrive;
        
        if (!$branddrive->settings->is_enabled()) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        // Prepare order data
        $order_data = $this->prepare_order_data($order);
        
        // Add additional data if provided
        if (!empty($additional_data)) {
            $order_data = array_merge($order_data, $additional_data);
        }
        
        // Send event to BrandDrive
        $result = $branddrive->api->send_checkout_event($event_type, $order_id, $order_data);
        
        // Log error if any
        if (is_wp_error($result) && $branddrive->settings->is_debug_mode()) {
            error_log('[BrandDrive] Failed to send ' . $event_type . ' event for order #' . $order_id . ': ' . $result->get_error_message());
        }
    }
    
    /**
     * Prepare order data for sending to BrandDrive.
     */
    private function prepare_order_data($order) {
        // Basic order data
        $order_data = array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'date_created' => $order->get_date_created()->format('Y-m-d H:i:s'),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'customer' => array(
                'id' => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone()
            ),
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'company' => $order->get_billing_company(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone()
            ),
            'shipping' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'company' => $order->get_shipping_company(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country()
            ),
            'items' => array()
        );
        
        // Add line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            
            $item_data = array(
                'id' => $item_id,
                'name' => $item->get_name(),
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total(),
                'sku' => $product ? $product->get_sku() : '',
                'meta_data' => array()
            );
            
            // Add item meta data
            foreach ($item->get_meta_data() as $meta) {
                $item_data['meta_data'][] = array(
                    'key' => $meta->key,
                    'value' => $meta->value
                );
            }
            
            $order_data['items'][] = $item_data;
        }
        
        return $order_data;
    }
}

