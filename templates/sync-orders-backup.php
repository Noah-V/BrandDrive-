
<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Check if integration is enabled and plugin key is set
$is_enabled = $branddrive->settings->is_enabled();
$has_plugin_key = !empty($branddrive->settings->get_plugin_key());
$can_sync = $is_enabled && $has_plugin_key;

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Pagination settings
$items_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 100;
$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Build query args based on filters
$query_args = array(
    'limit' => $items_per_page,
    'offset' => $offset,
    'orderby' => 'date',
    'order' => 'DESC',
    'type' => 'shop_order', // Only get regular orders, not refunds
);

// Apply status filter
if (!empty($status_filter)) {
    $query_args['status'] = $status_filter;
}

// Apply date filter
if (!empty($date_filter)) {
    $date_range = array();

    switch ($date_filter) {
        case 'today':
            $date_range = array(
                'after' => date('Y-m-d 00:00:00'),
                'before' => date('Y-m-d 23:59:59'),
            );
            break;
        case 'week':
            $date_range = array(
                'after' => date('Y-m-d 00:00:00', strtotime('-7 days')),
                'before' => date('Y-m-d 23:59:59'),
            );
            break;
        case 'month':
            $date_range = array(
                'after' => date('Y-m-d 00:00:00', strtotime('-30 days')),
                'before' => date('Y-m-d 23:59:59'),
            );
            break;
    }

    if (!empty($date_range)) {
        $query_args['date_created'] = $date_range;
    }
}

// Apply search filter
if (!empty($search_term)) {
    $query_args['customer'] = $search_term;
}

// Get orders with filters applied
$orders = wc_get_orders($query_args);

// Get total orders count for pagination (with filters)
$count_args = $query_args;
$count_args['limit'] = -1;
$count_args['return'] = 'ids';
$total_orders = wc_get_orders($count_args);
$total_pages = ceil(count($total_orders) / $items_per_page);

// Get active filters for display
$active_filters = array();
if (!empty($status_filter)) {
    $status_label = wc_get_order_status_name($status_filter);
    $active_filters['status'] = array(
        'key' => 'status',
        'value' => $status_filter,
        'label' => 'Status: ' . $status_label
    );
}
if (!empty($date_filter)) {
    $date_labels = array(
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month'
    );
    $active_filters['date'] = array(
        'key' => 'date',
        'value' => $date_filter,
        'label' => 'Date: ' . $date_labels[$date_filter]
    );
}
if (!empty($search_term)) {
    $active_filters['search'] = array(
        'key' => 'search',
        'value' => $search_term,
        'label' => 'Search: ' . $search_term
    );
}
?>

<div class="branddrive-sync">
    <a href="<?php echo admin_url('admin.php?page=branddrive'); ?>" class="branddrive-back-link">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left">
            <path d="m12 19-7-7 7-7"></path>
            <path d="M19 12H5"></path>
        </svg>
        Back to dashboard
    </a>

    <h1 class="branddrive-page-title">Sync orders to BrandDrive</h1>

    <div id="branddrive_sync_notice" class="branddrive-notification" style="display: none;"></div>

    <?php if (!$can_sync): ?>
        <div class="branddrive-card">
            <div class="branddrive-notification error">
                <p><?php _e('BrandDrive integration is not properly configured. Please check your settings.', 'branddrive-woocommerce'); ?></p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="branddrive-button branddrive-button-primary">
                <?php _e('Go to Settings', 'branddrive-woocommerce'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="branddrive-actions-bar">
            <div class="branddrive-actions-group">
                <div class="branddrive-dropdown">
                    <button class="branddrive-dropdown-button">
                        Bulk actions
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="branddrive-dropdown-content">
                        <a href="#" data-action="sync" class="bulk-action-option">Sync Selected</a>
                    </div>
                </div>
                <button id="branddrive_apply_bulk_action" class="branddrive-button branddrive-button-apply" disabled>Apply</button>
                <div class="branddrive-dropdown">
                    <button class="branddrive-dropdown-button">
                        Filters
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-down">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="branddrive-dropdown-content">
                        <div class="filter-section">
                            <div class="filter-section-title">Status</div>
                            <a href="#" data-filter="status" data-value="completed" class="filter-option">Completed Orders</a>
                            <a href="#" data-filter="status" data-value="processing" class="filter-option">Processing Orders</a>
                            <a href="#" data-filter="status" data-value="on-hold" class="filter-option">On Hold Orders</a>
                            <a href="#" data-filter="status" data-value="refunded" class="filter-option">Refunded Orders</a>
                            <a href="#" data-filter="status" data-value="draft" class="filter-option">Draft Orders</a>
                        </div>
                        <div class="filter-section">
                            <div class="filter-section-title">Date</div>
                            <a href="#" data-filter="date" data-value="today" class="filter-option">Today</a>
                            <a href="#" data-filter="date" data-value="week" class="filter-option">This Week</a>
                            <a href="#" data-filter="date" data-value="month" class="filter-option">This Month</a>
                        </div>
                    </div>
                </div>
                <div class="branddrive-search">
                    <input type="text" id="branddrive_search" placeholder="Search orders..." value="<?php echo esc_attr($search_term); ?>">
                    <button id="branddrive_search_button" class="branddrive-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="branddrive-active-filters" id="branddrive_active_filters">
                <?php foreach ($active_filters as $filter): ?>
                    <div class="branddrive-filter-tag" data-filter="<?php echo esc_attr($filter['key']); ?>" data-value="<?php echo esc_attr($filter['value']); ?>">
                        <?php echo esc_html($filter['label']); ?>
                        <span class="filter-remove">×</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="branddrive_sync_progress" style="display: none;">
                <span class="spinner is-active"></span>
                <span><?php _e('Syncing orders...', 'branddrive-woocommerce'); ?></span>
            </div>
        </div>

        <div class="branddrive-orders-table">
            <table>
                <thead>
                <tr>
                    <th class="orders-checkbox">
                        <input type="checkbox" id="branddrive_select_all_orders" />
                    </th>
                    <th class="orders-order">Order</th>
                    <th class="orders-date">Date</th>
                    <th class="orders-status">Status</th>
                    <th class="orders-currency">Currency</th>
                    <th class="orders-amount">Amount</th>
                    <th class="orders-view"></th>
                    <th class="orders-sync"></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" class="no-orders">
                            <p><?php _e('No orders found.', 'branddrive-woocommerce'); ?></p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        // Skip if this is a refund
                        if ($order->get_type() !== 'shop_order') {
                            continue;
                        }

                        $order_id = $order->get_id();
                        $order_number = $order->get_order_number();
                        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        $date_created = $order->get_date_created()->date_i18n('d/m/Y, g:ia');
                        $status = $order->get_status();
                        $currency = $order->get_currency();
                        $total = $order->get_formatted_order_total();
                        $is_synced = get_post_meta($order_id, '_branddrive_synced', true);

                        // Calculate time metrics (example)
                        $metrics = '';
                        if ($status === 'on-hold') {
                            $metrics = '<span class="order-metrics">138B • 8S</span>';
                        }
                        ?>
                        <tr data-order-id="<?php echo esc_attr($order_id); ?>">
                            <td>
                                <input type="checkbox" class="branddrive_order_checkbox" value="<?php echo esc_attr($order_id); ?>" />
                            </td>
                            <td>
                                <div class="order-info">
                                    <span class="order-number">#<?php echo esc_html($order_number); ?></span>
                                    <span class="order-customer"><?php echo esc_html($customer_name); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($date_created); ?>
                            </td>
                            <td>
                                    <span class="order-status status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(wc_get_order_status_name($status)); ?>
                                    </span>
                                <?php echo $metrics; ?>
                            </td>
                            <td>
                                <?php echo esc_html($currency); ?>
                            </td>
                            <td>
                                <?php echo wp_kses_post($total); ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>" class="view-button" target="_blank">
                                    View
                                </a>
                            </td>
                            <td>
                                <button class="branddrive-button branddrive-sync-single-order" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    Sync
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="branddrive-pagination">
                <div class="branddrive-per-page">
                    <span>Show</span>
                    <select id="branddrive_per_page" onchange="window.location.href='<?php echo add_query_arg(array('per_page' => ''), admin_url('admin.php?page=branddrive&tab=sync')); ?>' + this.value + '<?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?><?php echo !empty($date_filter) ? '&date=' . $date_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>'">
                        <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                        <option value="200" <?php selected($items_per_page, 200); ?>>200</option>
                    </select>
                    <span>per page</span>
                </div>

                <div class="branddrive-page-nav">
                    <?php if ($current_page > 1): ?>
                        <a href="<?php echo add_query_arg(array('paged' => $current_page - 1, 'per_page' => $items_per_page, 'status' => $status_filter, 'date' => $date_filter, 'search' => $search_term), admin_url('admin.php?page=branddrive&tab=sync')); ?>" class="page-nav-button">Previous</a>
                    <?php else: ?>
                        <span class="page-nav-button disabled">Previous</span>
                    <?php endif; ?>

                    <?php
                    // Display page numbers
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="page-number current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="<?php echo add_query_arg(array('paged' => $i, 'per_page' => $items_per_page, 'status' => $status_filter, 'date' => $date_filter, 'search' => $search_term), admin_url('admin.php?page=branddrive&tab=sync')); ?>" class="page-number"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="<?php echo add_query_arg(array('paged' => $current_page + 1, 'per_page' => $items_per_page, 'status' => $status_filter, 'date' => $date_filter, 'search' => $search_term), admin_url('admin.php?page=branddrive&tab=sync')); ?>" class="page-nav-button">Next</a>
                    <?php else: ?>
                        <span class="page-nav-button disabled">Next</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
    /* Sync Orders Page Styles */
    .branddrive-sync {
        max-width: 100%;
        padding: 24px;
        background-color: #f5f8ff;
        min-height: calc(100vh - 150px);
    }

    .branddrive-back-link {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #0052ff;
        font-size: 14px;
        font-weight: 500;
        margin-bottom: 24px;
    }

    .branddrive-back-link svg {
        margin-right: 8px;
    }

    .branddrive-page-title {
        font-size: 24px;
        font-weight: 600;
        margin: 0 0 24px 0;
        color: #000;
    }

    .branddrive-actions-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .branddrive-actions-group {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* Active Filters */
    .branddrive-active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .branddrive-filter-tag {
        display: flex;
        align-items: center;
        background-color: #e6efff;
        color: #0052ff;
        padding: 4px 10px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
        gap: 6px;
    }

    .filter-remove {
        cursor: pointer;
        font-size: 16px;
        line-height: 1;
        font-weight: bold;
    }

    .filter-remove:hover {
        color: #0046d9;
    }

    /* Search Box */
    .branddrive-search {
        display: flex;
        align-items: center;
    }

    .branddrive-search input {
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 4px 0 0 4px;
        font-size: 14px;
        width: 200px;
    }

    .branddrive-search button {
        border-radius: 0 4px 4px 0;
        padding: 8px;
        background-color: white;
        border: 1px solid #d1d5db;
        border-left: none;
    }

    /* Dropdown Styles */
    .branddrive-dropdown {
        position: relative;
        display: inline-block;
    }

    .branddrive-dropdown-button {
        display: flex;
        align-items: center;
        padding: 8px 16px;
        background-color: white;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        font-size: 14px;
        cursor: pointer;
        gap: 8px;
    }

    .branddrive-dropdown-content {
        display: none;
        position: absolute;
        background-color: white;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        z-index: 1;
        border-radius: 4px;
        overflow: hidden;
    }

    .filter-section {
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .filter-section:last-child {
        border-bottom: none;
    }

    .filter-section-title {
        padding: 4px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280;
    }

    .branddrive-dropdown-content a {
        color: black;
        padding: 8px 16px;
        text-decoration: none;
        display: block;
        font-size: 14px;
    }

    .branddrive-dropdown-content a:hover {
        background-color: #f5f8ff;
    }

    .branddrive-dropdown:hover .branddrive-dropdown-content {
        display: block;
    }

    .branddrive-button-apply {
        background-color: #f5b014;
        color: black;
        border: none;
        padding: 8px 24px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .branddrive-button-apply:hover {
        background-color: #e6a412;
    }

    .branddrive-button-apply:disabled {
        background-color: #f5d78a;
        cursor: not-allowed;
    }

    /* Orders Table Styles */
    .branddrive-orders-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .branddrive-orders-table table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    }

    .branddrive-orders-table thead {
        background-color: #e6efff;
    }

    .branddrive-orders-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 600;
        color: #1f2937;
    }

    .branddrive-orders-table td {
        padding: 16px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }

    .branddrive-orders-table tr:hover {
        background-color: #f9fafb;
    }

    .branddrive-orders-table tr.selected {
        border: 1px solid #0052ff;
        box-shadow: 0 0 0 2px rgba(0, 82, 255, 0.1);
    }

    .orders-checkbox {
        width: 40px;
    }

    .orders-view,
    .orders-sync {
        width: 80px;
        text-align: center;
    }

    .order-info {
        display: flex;
        flex-direction: column;
    }

    .order-number {
        font-weight: 600;
        color: #000;
        margin-bottom: 4px;
    }

    .order-customer {
        color: #6b7280;
    }

    .view-button {
        color: #0052ff;
        text-decoration: none;
        font-weight: 500;
    }

    .branddrive-sync-single-order {
        background-color: #0052ff;
        color: white;
        border: none;
        padding: 6px 16px;
        border-radius: 4px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .branddrive-sync-single-order:hover {
        background-color: #0046d9;
    }

    /* Order Status Styles */
    .order-status {
        display: inline-flex;
        padding: 2px 8px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
    }

    .order-status.status-completed {
        background-color: #ecfdf3;
        color: #027a48;
    }

    .order-status.status-processing {
        background-color: #e6f1fb;
        color: #0052ff;
    }

    .order-status.status-on-hold {
        background-color: #fff4ed;
        color: #c4320a;
    }

    .order-status.status-pending {
        background-color: #fff8e6;
        color: #b54708;
    }

    .order-status.status-cancelled,
    .order-status.status-failed {
        background-color: #ffd8d8;
        color: #c4320a;
    }

    .order-status.status-refunded {
        background-color: #f3e8ff;
        color: #7e22ce;
    }

    .order-status.status-draft {
        background-color: #e8eaed;
        color: #5f6368;
    }

    .order-metrics {
        display: inline-flex;
        margin-left: 8px;
        padding: 2px 8px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
        background-color: #0052ff;
        color: white;
    }

    /* Pagination Styles */
    .branddrive-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 24px;
    }

    .branddrive-per-page {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #6b7280;
    }

    .branddrive-per-page select {
        padding: 4px 8px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        background-color: white;
    }

    .branddrive-page-nav {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .page-nav-button {
        padding: 6px 12px;
        background-color: #f3f4f6;
        color: #4b5563;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }

    .page-nav-button.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .page-number {
        padding: 6px 12px;
        background-color: #f3f4f6;
        color: #4b5563;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }

    .page-number.current {
        background-color: #0052ff;
        color: white;
    }

    .no-orders {
        text-align: center;
        padding: 32px 0;
        color: #6b7280;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Track selected filters
        let selectedFilters = {};
        let hasNewFilters = false;

        // Initialize from URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('status')) {
            selectedFilters.status = urlParams.get('status');
        }
        if (urlParams.has('date')) {
            selectedFilters.date = urlParams.get('date');
        }
        if (urlParams.has('search')) {
            selectedFilters.search = urlParams.get('search');
        }

        // Select/deselect all orders
        $('#branddrive_select_all_orders').on('change', function() {
            $('.branddrive_order_checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedRows();
            updateBulkActionButton();
        });

        // Highlight selected rows
        $('.branddrive_order_checkbox').on('change', function() {
            updateSelectedRows();
            updateBulkActionButton();
        });

        function updateSelectedRows() {
            $('.branddrive_order_checkbox').each(function() {
                if ($(this).prop('checked')) {
                    {
                        if ($(this).prop('checked')) {
                            $(this).closest('tr').addClass('selected');
                        } else {
                            $(this).closest('tr').removeClass('selected');
                        }
                    });
                }

                function updateBulkActionButton() {
                    const hasCheckedOrders = $('.branddrive_order_checkbox:checked').length > 0;
                    $('#branddrive_apply_bulk_action').prop('disabled', !hasCheckedOrders);
                }

                // Apply bulk action
                $('#branddrive_apply_bulk_action').on('click', function() {
                    const selectedOrders = $('.branddrive_order_checkbox:checked');

                    if (selectedOrders.length === 0) {
                        alert('Please select at least one order.');
                        return;
                    }

                    const orderIds = [];
                    selectedOrders.each(function() {
                        orderIds.push($(this).val());
                    });

                    syncOrders(orderIds);
                });

                // Sync single order
                $('.branddrive-sync-single-order').on('click', function() {
                    const orderId = $(this).data('order-id');
                    syncOrders([orderId]);
                });

                // Handle filter selection
                $('.filter-option').on('click', function(e) {
                    e.preventDefault();

                    const filterType = $(this).data('filter');
                    const filterValue = $(this).data('value');

                    // Add to selected filters
                    selectedFilters[filterType] = filterValue;
                    hasNewFilters = true;

                    // Update UI
                    updateFilterTags();

                    // Enable apply button
                    $('#branddrive_apply_bulk_action').prop('disabled', false);
                });

                // Handle filter tag removal
                $(document).on('click', '.filter-remove', function() {
                    const filterTag = $(this).closest('.branddrive-filter-tag');
                    const filterType = filterTag.data('filter');

                    // Remove from selected filters
                    delete selectedFilters[filterType];
                    hasNewFilters = true;

                    // Update UI
                    filterTag.remove();

                    // Enable apply button
                    $('#branddrive_apply_bulk_action').prop('disabled', false);
                });

                // Apply filters
                $('#branddrive_apply_filters').on('click', function() {
                    applyFilters();
                });

                // Search functionality
                $('#branddrive_search_button').on('click', function() {
                    const searchTerm = $('#branddrive_search').val().trim();

                    if (searchTerm) {
                        selectedFilters.search = searchTerm;
                    } else {
                        delete selectedFilters.search;
                    }

                    applyFilters();
                });

                // Handle Enter key in search box
                $('#branddrive_search').on('keypress', function(e) {
                    if (e.which === 13) { // Enter key
                        e.preventDefault();
                        $('#branddrive_search_button').click();
                    }
                });

                function updateFilterTags() {
                    const filterContainer = $('#branddrive_active_filters');
                    filterContainer.empty();

                    // Add tags for each selected filter
                    Object.entries(selectedFilters).forEach(([key, value]) => {
                        let label = '';

                        // Generate appropriate label based on filter type
                        switch (key) {
                            case 'status':
                                // Get status label from the filter option
                                const statusOption = $(`.filter-option[data-filter="status"][data-value="${value}"]`);
                                label = 'Status: ' + (statusOption.length ? statusOption.text() : value);
                                break;
                            case 'date':
                                const dateLabels = {
                                    'today': 'Today',
                                    'week': 'This Week',
                                    'month': 'This Month'
                                };
                                label = 'Date: ' + (dateLabels[value] || value);
                                break;
                            case 'search':
                                label = 'Search: ' + value;
                                break;
                            default:
                                label = key + ': ' + value;
                        }

                        // Create and append the filter tag
                        const filterTag = $(`
                <div class="branddrive-filter-tag" data-filter="${key}" data-value="${value}">
                    ${label}
                    <span class="filter-remove">×</span>
                </div>
            `);

                        filterContainer.append(filterTag);
                    });
                }

                function applyFilters() {
                    // Build query string from selected filters
                    const queryParams = new URLSearchParams();
                    queryParams.append('page', 'branddrive');
                    queryParams.append('tab', 'sync');

                    // Add current pagination settings
                    const currentPerPage = $('#branddrive_per_page').val() || '100';
                    queryParams.append('per_page', currentPerPage);

                    // Add filters
                    Object.entries(selectedFilters).forEach(([key, value]) => {
                        if (value) {
                            queryParams.append(key, value);
                        }
                    });

                    // Redirect to filtered URL
                    window.location.href = `${window.location.pathname}?${queryParams.toString()}`;
                }

                function syncOrders(orderIds) {
                    // Show progress indicator
                    $('#branddrive_sync_progress').show();

                    // Disable buttons
                    $('.branddrive-sync-single-order, #branddrive_apply_bulk_action').prop('disabled', true);

                    // Make AJAX request to sync orders
                    // This is a placeholder - actual implementation would send a request to your server
                    setTimeout(function() {
                        // Hide progress indicator
                        $('#branddrive_sync_progress').hide();

                        // Enable buttons
                        $('.branddrive-sync-single-order, #branddrive_apply_bulk_action').prop('disabled', false);

                        // Show success message
                        const notice = $('#branddrive_sync_notice');
                        notice.removeClass('error').addClass('success')
                            .html('<p>' + orderIds.length + ' orders synced successfully</p><span class="close dashicons dashicons-no-alt"></span>')
                            .show();

                        // Auto-hide after 5 seconds
                        setTimeout(() => {
                            notice.fadeOut();
                        }, 5000);

                        // Add close button functionality
                        notice.find('.close').on('click', function() {
                            notice.fadeOut();
                        });
                    }, 1500);
                }

                // Initialize UI
                updateSelectedRows();
                updateBulkActionButton();
            });
</script>
