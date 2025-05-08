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
$status_filter = isset($_GET['status']) ? explode(',', sanitize_text_field($_GET['status'])) : array();
$currency_filter = isset($_GET['currency']) ? explode(',', sanitize_text_field($_GET['currency'])) : array();
$date_filter = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';
$date_start = isset($_GET['date_start']) ? sanitize_text_field($_GET['date_start']) : '';
$date_end = isset($_GET['date_end']) ? sanitize_text_field($_GET['date_end']) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Pagination settings
$items_per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 100;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
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
    switch ($date_filter) {
        case 'today':
            $query_args['date_created'] = '>' . strtotime('today midnight');
            break;
        case 'week':
            $query_args['date_created'] = '>' . strtotime('-7 days');
            break;
        case 'month':
            $query_args['date_created'] = '>' . strtotime('-30 days');
            break;
        case 'custom':
            // Handle custom date range
            if (!empty($date_start) && !empty($date_end)) {
                // Convert to timestamps and adjust end date to include the entire day
                $start_timestamp = strtotime($date_start . ' 00:00:00');
                $end_timestamp = strtotime($date_end . ' 23:59:59');

                if ($start_timestamp && $end_timestamp) {
                    $query_args['date_created'] = $start_timestamp . '...' . $end_timestamp;
                }
            } elseif (!empty($date_start)) {
                // Only start date is provided
                $start_timestamp = strtotime($date_start . ' 00:00:00');
                if ($start_timestamp) {
                    $query_args['date_created'] = '>' . $start_timestamp;
                }
            } elseif (!empty($date_end)) {
                // Only end date is provided
                $end_timestamp = strtotime($date_end . ' 23:59:59');
                if ($end_timestamp) {
                    $query_args['date_created'] = '<' . $end_timestamp;
                }
            }
            break;
        case 'last_week':
            $query_args['date_created'] = array(
                'after'  => strtotime('last week monday midnight'),
                'before' => strtotime('last week sunday 23:59:59'),
                'inclusive' => true,
            );
            break;
        case 'last_month':
            $query_args['date_created'] = array(
                'after'  => strtotime('first day of last month midnight'),
                'before' => strtotime('last day of last month 23:59:59'),
                'inclusive' => true,
            );
            break;
        case 'last_quarter':
            $current_month = date('n');
            $current_quarter = ceil($current_month / 3);
            $last_quarter_start_month = ($current_quarter - 2) * 3 + 1;
            if ($last_quarter_start_month <= 0) {
                $last_quarter_start_month = 10; // October
                $year = date('Y') - 1;
            } else {
                $year = date('Y');
            }
            $last_quarter_end_month = $last_quarter_start_month + 2;

            $query_args['date_created'] = array(
                'after'  => strtotime(date('Y-m-d', mktime(0, 0, 0, $last_quarter_start_month, 1, $year))),
                'before' => strtotime(date('Y-m-d', mktime(0, 0, 0, $last_quarter_end_month + 1, 0, $year)) . ' 23:59:59'),
                'inclusive' => true,
            );
            break;
        case 'last_year':
            $query_args['date_created'] = array(
                'after'  => strtotime('first day of January last year midnight'),
                'before' => strtotime('last day of December last year 23:59:59'),
                'inclusive' => true,
            );
            break;
    }
}

// Apply currency filter
if (!empty($currency_filter)) {
    // WooCommerce doesn't have a direct way to filter by currency in wc_get_orders
    // We'll need to filter the results after fetching them
    // This is a placeholder for the actual implementation
    $query_args['currency_filter'] = $currency_filter;
}

// Apply search filter
if (!empty($search_term)) {
    $query_args['customer'] = $search_term;
}

// Get orders with filters applied
$orders = wc_get_orders($query_args);

// Filter orders by currency if currency filter is applied
if (!empty($currency_filter) && !empty($orders)) {
    $filtered_orders = array();
    foreach ($orders as $order) {
        if (in_array($order->get_currency(), $currency_filter)) {
            $filtered_orders[] = $order;
        }
    }
    $orders = $filtered_orders;
}

// Get total orders count for pagination (with filters)
$count_args = $query_args;
$count_args['limit'] = -1;
$count_args['return'] = 'ids';
// Remove pagination parameters that affect the count
unset($count_args['offset']);
$total_orders = wc_get_orders($count_args);

// Filter total orders by currency if currency filter is applied
if (!empty($currency_filter) && !empty($total_orders)) {
    $filtered_total = array();
    foreach ($total_orders as $order_id) {
        $order = wc_get_order($order_id);
        if ($order && in_array($order->get_currency(), $currency_filter)) {
            $filtered_total[] = $order_id;
        }
    }
    $total_orders = $filtered_total;
}

$total_orders_count = count($total_orders);
$total_pages = ceil($total_orders_count / $items_per_page);

// Ensure current page doesn't exceed total pages
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    // Update the offset based on the corrected current page
    $offset = ($current_page - 1) * $items_per_page;
    $query_args['offset'] = $offset;
    // Re-fetch orders with the corrected offset
    $orders = wc_get_orders($query_args);
}

?>

<div class="branddrive-sync">
    <a href="<?php echo admin_url('admin.php?page=branddrive'); ?>" class="branddrive-back-link">
        <?php branddrive_back_arrow_icon(); ?>
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
        <!-- Hidden input to store total orders count for JavaScript -->
        <input type="hidden" id="total_orders_count" value="<?php echo esc_attr($total_orders_count); ?>">

        <div class="branddrive-actions-bar">
            <div class="branddrive-actions-group">
                <div class="branddrive-dropdown">
                    <button class="branddrive-dropdown-button" id="bulk-actions-btn">
                        Bulk actions
                        <?php branddrive_dropdown_arrow_icon(); ?>
                    </button>
                    <div class="branddrive-dropdown-content" id="bulk-actions-dropdown">
                        <a href="#" data-action="sync" class="bulk-action-option">Sync Selected</a>
                    </div>
                </div>
                <button id="branddrive_apply_filters" class="branddrive-button branddrive-button-apply" disabled>Apply</button>
                <div class="branddrive-dropdown">
                    <button class="branddrive-dropdown-button" id="filters-btn">
                        Filters
                        <?php branddrive_dropdown_arrow_icon(); ?>
                    </button>

                    <div class="branddrive-dropdown-content" id="filters-dropdown">
                        <div class="filter-option" data-filter="status">
                            <div class="filter-option-content">
                                <span>Status</span>
                                <?php branddrive_right_arrow_icon(); ?>
                            </div>
                        </div>
                        <div class="filter-option" data-filter="currency">
                            <div class="filter-option-content">
                                <span>Currency</span>
                                <?php branddrive_right_arrow_icon(); ?>
                            </div>
                        </div>
                        <div class="filter-option" data-filter="date">
                            <div class="filter-option-content">
                                <span>Date</span>
                                <?php branddrive_right_arrow_icon(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Filter Dropdown -->
            <div class="branddrive-nested-dropdown" id="status-dropdown">
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_completed" class="filter-checkbox" data-filter="status" data-value="completed" <?php echo in_array('completed', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_completed">Completed</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_processing" class="filter-checkbox" data-filter="status" data-value="processing" <?php echo in_array('processing', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_processing">Processing</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_on-hold" class="filter-checkbox" data-filter="status" data-value="on-hold" <?php echo in_array('on-hold', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_on-hold">On Hold</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_pending" class="filter-checkbox" data-filter="status" data-value="pending" <?php echo in_array('pending', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_pending">Pending Payment</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_refunded" class="filter-checkbox" data-filter="status" data-value="refunded" <?php echo in_array('refunded', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_refunded">Refunded</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_cancelled" class="filter-checkbox" data-filter="status" data-value="cancelled" <?php echo in_array('cancelled', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_cancelled">Cancelled</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_status_failed" class="filter-checkbox" data-filter="status" data-value="failed" <?php echo in_array('failed', $status_filter) ? 'checked' : ''; ?> />
                    <label for="filter_status_failed">Failed</label>
                </div>
            </div>

            <!-- Currency Filter Dropdown -->
            <div class="branddrive-nested-dropdown" id="currency-dropdown">
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_currency_ngn" class="filter-checkbox" data-filter="currency" data-value="NGN" <?php echo in_array('NGN', $currency_filter) ? 'checked' : ''; ?> />
                    <label for="filter_currency_ngn">Nigerian Naira</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_currency_usd" class="filter-checkbox" data-filter="currency" data-value="USD" <?php echo in_array('USD', $currency_filter) ? 'checked' : ''; ?> />
                    <label for="filter_currency_usd">US Dollar</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_currency_eur" class="filter-checkbox" data-filter="currency" data-value="EUR" <?php echo in_array('EUR', $currency_filter) ? 'checked' : ''; ?> />
                    <label for="filter_currency_eur">European Euro</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_currency_gbp" class="filter-checkbox" data-filter="currency" data-value="GBP" <?php echo in_array('GBP', $currency_filter) ? 'checked' : ''; ?> />
                    <label for="filter_currency_gbp">British Pound</label>
                </div>
                <div class="filter-option-checkbox">
                    <input type="checkbox" id="filter_currency_inr" class="filter-checkbox" data-filter="currency" data-value="INR" <?php echo in_array('INR', $currency_filter) ? 'checked' : ''; ?> />
                    <label for="filter_currency_inr">Indian Rupee</label>
                </div>
            </div>

            <!-- Date Filter Dropdown -->
            <div class="branddrive-nested-dropdown" id="date-dropdown">
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_today" class="filter-radio" data-filter="date" data-value="today" <?php echo $date_filter === 'today' ? 'checked' : ''; ?> />
                    <label for="filter_date_today">Today</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_week" class="filter-radio" data-filter="date" data-value="week" <?php echo $date_filter === 'week' ? 'checked' : ''; ?> />
                    <label for="filter_date_week">This Week</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_month" class="filter-radio" data-filter="date" data-value="month" <?php echo $date_filter === 'month' ? 'checked' : ''; ?> />
                    <label for="filter_date_month">This Month</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_last_week" class="filter-radio" data-filter="date" data-value="last_week" <?php echo $date_filter === 'last_week' ? 'checked' : ''; ?> />
                    <label for="filter_date_last_week">Last Week</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_last_month" class="filter-radio" data-filter="date" data-value="last_month" <?php echo $date_filter === 'last_month' ? 'checked' : ''; ?> />
                    <label for="filter_date_last_month">Last Month</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_last_quarter" class="filter-radio" data-filter="date" data-value="last_quarter" <?php echo $date_filter === 'last_quarter' ? 'checked' : ''; ?> />
                    <label for="filter_date_last_quarter">Last Quarter</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_last_year" class="filter-radio" data-filter="date" data-value="last_year" <?php echo $date_filter === 'last_year' ? 'checked' : ''; ?> />
                    <label for="filter_date_last_year">Last Year</label>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_custom" class="filter-radio" data-filter="date" data-value="custom" <?php echo $date_filter === 'custom' ? 'checked' : ''; ?> />
                    <label for="filter_date_custom">Custom Range</label>
                </div>
                <div id="custom_date_range" class="custom-date-range" style="display: <?php echo $date_filter === 'custom' ? 'block' : 'none'; ?>;">
                    <div class="date-range-inputs">
                        <div class="date-input-group">
                            <label for="date_start">Start Date</label>
                            <input type="date" id="date_start" class="date-input" value="<?php echo esc_attr($date_start); ?>" />
                        </div>
                        <div class="date-input-group">
                            <label for="date_end">End Date</label>
                            <input type="date" id="date_end" class="date-input" value="<?php echo esc_attr($date_end); ?>" />
                        </div>
                    </div>
                </div>
                <div class="filter-option-radio">
                    <input type="radio" name="filter_date" id="filter_date_none" class="filter-radio" data-filter="date" data-value="" <?php echo empty($date_filter) ? 'checked' : ''; ?> />
                    <label for="filter_date_none">No Date Filter</label>
                </div>
            </div>


            <!-- Overlay for dropdowns -->
            <div id="dropdown-overlay" class="dropdown-overlay"></div>

            <!-- Add the subdropdown overlay element -->
            <div id="subdropdown-overlay" class="subdropdown-overlay"></div>

            <!-- Active filters section with Clear all filters button above -->
            <div class="branddrive-active-filters-container">
                <!-- Clear all filters button in its own row -->
                <div class="branddrive-filters-header">
                    <div id="branddrive_clear_all_filters" class="branddrive-clear-all-filters" style="display: none;">
                        <button type="button" class="branddrive-clear-filters-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="clear-filters-icon">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                            Clear all filters
                        </button>
                    </div>
                </div>

                <!-- Filter tags in a separate row below -->
                <div class="branddrive-active-filters" id="branddrive_active_filters">
                    <?php
                    // Get active filters for display
                    $active_filters = array();
                    if (!empty($status_filter)) {
                        foreach ($status_filter as $status) {
                            $status_label = wc_get_order_status_name($status);
                            $active_filters['status_' . $status] = array(
                                'key' => 'status',
                                'value' => $status,
                                'label' => 'Status: ' . $status_label
                            );
                        }
                    }
                    if (!empty($currency_filter)) {
                        foreach ($currency_filter as $currency) {
                            $currency_labels = array(
                                'NGN' => 'Nigerian Naira',
                                'USD' => 'US Dollar',
                                'EUR' => 'European Euro',
                                'GBP' => 'British Pound',
                                'INR' => 'Indian Rupee'
                            );
                            $currency_label = isset($currency_labels[$currency]) ? $currency_labels[$currency] : $currency;
                            $active_filters['currency_' . $currency] = array(
                                'key' => 'currency',
                                'value' => $currency,
                                'label' => 'Currency: ' . $currency_label
                            );
                        }
                    }
                    if (!empty($date_filter)) {
                        $date_labels = array(
                            'today' => 'Today',
                            'week' => 'This Week',
                            'month' => 'This Month',
                            'custom' => 'Custom Range',
                            'last_week' => 'Last Week',
                            'last_month' => 'Last Month',
                            'last_quarter' => 'Last Quarter',
                            'last_year' => 'Last Year'
                        );

                        if (isset($date_labels[$date_filter])) {
                            $active_filters['date'] = array(
                                'key' => 'date',
                                'value' => $date_filter,
                                'label' => 'Date: ' . $date_labels[$date_filter]
                            );
                        }

                        // Add date range as a separate filter tag if using custom range
                        if ($date_filter === 'custom' && (!empty($date_start) || !empty($date_end))) {
                            $date_range_label = '';
                            if (!empty($date_start) && !empty($date_end)) {
                                $date_range_label = date('M j, Y', strtotime($date_start)) . ' - ' . date('M j, Y', strtotime($date_end));
                            } elseif (!empty($date_start)) {
                                $date_range_label = 'From ' . date('M j, Y', strtotime($date_start));
                            } elseif (!empty($date_end)) {
                                $date_range_label = 'Until ' . date('M j, Y', strtotime($date_end));
                            }

                            if (!empty($date_range_label)) {
                                $active_filters['date_range'] = array(
                                    'key' => 'date_range',
                                    'value' => 'custom',
                                    'label' => $date_range_label
                                );
                            }
                        }
                    }
                    if (!empty($search_term)) {
                        $active_filters['search'] = array(
                            'key' => 'search',
                            'value' => $search_term,
                            'label' => 'Search: ' . $search_term
                        );
                    }

                    // Show/hide clear all filters button based on filter count
                    if (count($active_filters) > 2) {
                        echo '<script>document.getElementById("branddrive_clear_all_filters").style.display = "flex";</script>';
                    }
                    ?>
                    <?php foreach ($active_filters as $filter): ?>
                        <div class="branddrive-filter-tag" data-filter="<?php echo esc_attr($filter['key']); ?>" data-value="<?php echo esc_attr($filter['value']); ?>">
                            <?php echo esc_html($filter['label']); ?>
                            <span class="filter-remove">×</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Selection info UI - MOVED HERE as requested -->
                <div id="branddrive_select_all_message" class="branddrive-selection-info" style="display: none;">
                    <span id="branddrive_selection_count">0 orders selected</span>
                    <div class="branddrive-selection-actions">
                        <a href="#" id="branddrive_select_all_across_pages" class="branddrive-select-all-link">Select all orders across all pages</a>
                        <a href="#" id="branddrive_clear_selection" class="branddrive-clear-selection-link" style="display: none;">Clear selection</a>
                    </div>
                </div>
            </div>

            <div id="branddrive_sync_progress" style="display: none;">
                <span class="spinner is-active"></span>
                <span><?php _e('Syncing orders...', 'branddrive-woocommerce'); ?></span>
            </div>
        </div>

        <div class="branddrive-orders-table">
            <?php
            // Add a hidden input with the total order count
            ?>
            <input type="hidden" id="total_orders_count" value="<?php echo esc_attr($total_orders_count); ?>">
            <table cellspacing="0" cellpadding="0">
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
                            <td class="orders-view">
                                <a href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit'); ?>" class="view-button" target="_blank">
                                    View
                                </a>
                            </td>
                            <td class="orders-sync">
                                <button class="branddrive-sync-single-order" data-order-id="<?php echo esc_attr($order_id); ?>">
                                    Sync
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php
// Debug info to troubleshoot pagination
        $debug_info = "Total orders: " . $total_orders_count . ", Items per page: $items_per_page, Total pages: $total_pages, Current page: $current_page";
        if ($branddrive->settings->is_debug_mode()) {
            error_log("[BrandDrive] Pagination debug: " . $debug_info);
        }

// Always show pagination controls, even with just one page
        ?>
        <div class="branddrive-pagination">
            <div class="branddrive-per-page">
                <span>Show</span>
                <select id="branddrive_per_page" onchange="window.location.href='<?php echo esc_url(add_query_arg(array('page' => 'branddrive', 'tab' => 'sync', 'per_page' => ''), admin_url('admin.php'))); ?>' + this.value + '<?php echo !empty($status_filter) ? '&status=' . implode(',', $status_filter) : ''; ?><?php echo !empty($currency_filter) ? '&currency=' . implode(',', $currency_filter) : ''; ?><?php echo !empty($date_filter) ? '&date=' . $date_filter : ''; ?><?php echo !empty($date_start) ? '&date_start=' . $date_start : ''; ?><?php echo !empty($date_end) ? '&date_end=' . $date_end : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>'">
                    <option value="10" <?php selected($items_per_page, 10); ?>>10</option>
                    <option value="25" <?php selected($items_per_page, 25); ?>>25</option>
                    <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                    <option value="200" <?php selected($items_per_page, 200); ?>>200</option>
                </select>
                <span>per page</span>
            </div>

            <div class="branddrive-page-nav">
                <?php
                // Always show page navigation even with just one page
                if ($current_page > 1): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('paged' => $current_page - 1), $_SERVER['REQUEST_URI'])); ?>" class="page-nav-button prev">Previous</a>
                <?php else: ?>
                    <span class="page-nav-button prev disabled">Previous</span>
                <?php endif; ?>

                <?php
                // Display page numbers with improved logic
                // Ensure total_pages is at least 1
                $total_pages = max(1, $total_pages);

                // Calculate start and end pages to show
                $start_page = max(1, min($current_page - 2, $total_pages - 4));
                $end_page = min($total_pages, max($current_page + 2, 5));

                // Always show at least 5 pages if available
                if ($end_page - $start_page + 1 < 5 && $total_pages >= 5) {
                    if ($start_page == 1) {
                        $end_page = min(5, $total_pages);
                    } elseif ($end_page == $total_pages) {
                        $start_page = max(1, $total_pages - 4);
                    }
                }

                // Debug pagination values
                if ($branddrive->settings->is_debug_mode()) {
                    error_log("[BrandDrive] Pagination values: start_page=$start_page, end_page=$end_page, current_page=$current_page, total_pages=$total_pages");
                }

                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span class="page-number current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="<?php echo esc_url(add_query_arg(array('paged' => $i), $_SERVER['REQUEST_URI'])); ?>" class="page-number"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="<?php echo esc_url(add_query_arg(array('paged' => $current_page + 1), $_SERVER['REQUEST_URI'])); ?>" class="page-nav-button next">Next</a>
                <?php else: ?>
                    <span class="page-nav-button next disabled">Next</span>
                <?php endif; ?>
            </div>

            <?php if ($branddrive->settings->is_debug_mode()): ?>
                <div class="branddrive-pagination-debug" style="font-size: 11px; color: #666; margin-top: 8px;">
                    <?php echo esc_html($debug_info); ?>
                    <br>Current Page: <?php echo $current_page; ?>,
                    Start Page: <?php echo $start_page; ?>,
                    End Page: <?php echo $end_page; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
