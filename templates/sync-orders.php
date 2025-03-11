<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Get orders
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$per_page = 100;

$orders = wc_get_orders(array(
    'limit' => $per_page,
    'paged' => $page,
    'orderby' => 'date',
    'order' => 'DESC'
));

$total_orders = wc_get_orders(array(
    'limit' => -1,
    'return' => 'ids',
));

$total_pages = ceil(count($total_orders) / $per_page);
?>

<div class="branddrive-sync-orders">
    <h1 class="branddrive-page-title"><?php _e('Sync orders to BrandDrive', 'branddrive-woocommerce'); ?></h1>
    
    <div class="branddrive-table-controls">
        <div class="branddrive-bulk-actions">
            <select class="branddrive-select">
                <option value=""><?php _e('Bulk actions', 'branddrive-woocommerce'); ?></option>
                <option value="sync"><?php _e('Sync selected', 'branddrive-woocommerce'); ?></option>
            </select>
            <button class="branddrive-button branddrive-button-secondary"><?php _e('Apply', 'branddrive-woocommerce'); ?></button>
        </div>
        
        <div class="branddrive-filters">
            <button class="branddrive-button branddrive-button-secondary">
                <?php _e('Filters', 'branddrive-woocommerce'); ?>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
        </div>
    </div>
    
    <div class="branddrive-table-container">
        <table class="branddrive-table">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" />
                    </th>
                    <th><?php _e('Order', 'branddrive-woocommerce'); ?></th>
                    <th><?php _e('Date', 'branddrive-woocommerce'); ?></th>
                    <th><?php _e('Status', 'branddrive-woocommerce'); ?></th>
                    <th><?php _e('Currency', 'branddrive-woocommerce'); ?></th>
                    <th><?php _e('Amount', 'branddrive-woocommerce'); ?></th>
                    <th class="actions-column"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="check-column">
                            <input type="checkbox" name="order_ids[]" value="<?php echo esc_attr($order->get_id()); ?>" />
                        </td>
                        <td>
                            #<?php echo $order->get_order_number(); ?> <?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?>
                        </td>
                        <td><?php echo $order->get_date_created()->format('d/m/Y, g:ia'); ?></td>
                        <td>
                            <span class="branddrive-status-badge status-<?php echo esc_attr($order->get_status()); ?>">
                                <?php echo wc_get_order_status_name($order->get_status()); ?>
                            </span>
                        </td>
                        <td><?php echo $order->get_currency(); ?></td>
                        <td><?php echo $order->get_formatted_order_total(); ?></td>
                        <td class="actions-column">
                            <a href="<?php echo esc_url($order->get_edit_order_url()); ?>" class="branddrive-link">
                                <?php _e('View', 'branddrive-woocommerce'); ?>
                            </a>
                            <button class="branddrive-link sync-order" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                <?php _e('Sync', 'branddrive-woocommerce'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="branddrive-table-footer">
        <div class="branddrive-per-page">
            <span><?php _e('Show', 'branddrive-woocommerce'); ?></span>
            <select class="branddrive-select">
                <option value="100">100</option>
                <option value="50">50</option>
                <option value="25">25</option>
            </select>
            <span><?php _e('per page', 'branddrive-woocommerce'); ?></span>
        </div>
        
        <div class="branddrive-pagination">
            <?php if ($page > 1): ?>
                <a href="<?php echo add_query_arg('paged', $page - 1); ?>" class="branddrive-button branddrive-button-secondary">
                    <?php _e('Previous', 'branddrive-woocommerce'); ?>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?php echo add_query_arg('paged', $i); ?>" 
                   class="branddrive-button <?php echo $i === $page ? 'branddrive-button-primary' : 'branddrive-button-secondary'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="<?php echo add_query_arg('paged', $page + 1); ?>" class="branddrive-button branddrive-button-secondary">
                    <?php _e('Next', 'branddrive-woocommerce'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

