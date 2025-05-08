<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

global $branddrive;

// Check if integration is enabled and plugin key is set
$is_enabled = $branddrive->settings->is_enabled();
$has_plugin_key = !empty($branddrive->settings->get_plugin_key());
$can_export = $is_enabled && $has_plugin_key;

// Get all product categories
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));

// Get product types
$product_types = wc_get_product_types();
// Add "All" option at the beginning
$product_types = array('all' => __('All Product Types', 'branddrive-woocommerce')) + $product_types;

// Get available columns for export
$available_columns = array(
    'id' => __('Product ID', 'branddrive-woocommerce'),
    'name' => __('Product Name', 'branddrive-woocommerce'),
    'sku' => __('SKU', 'branddrive-woocommerce'),
    'price' => __('Price', 'branddrive-woocommerce'),
    'regular_price' => __('Regular Price', 'branddrive-woocommerce'),
    'sale_price' => __('Sale Price', 'branddrive-woocommerce'),
    'stock_quantity' => __('Stock Quantity', 'branddrive-woocommerce'),
    'stock_status' => __('Stock Status', 'branddrive-woocommerce'),
    'weight' => __('Weight', 'branddrive-woocommerce'),
    'dimensions' => __('Dimensions', 'branddrive-woocommerce'),
    'categories' => __('Categories', 'branddrive-woocommerce'),
    'tags' => __('Tags', 'branddrive-woocommerce'),
    'images' => __('Images', 'branddrive-woocommerce'),
    'description' => __('Description', 'branddrive-woocommerce'),
    'short_description' => __('Short Description', 'branddrive-woocommerce'),
    'date_created' => __('Date Created', 'branddrive-woocommerce'),
    'date_modified' => __('Date Modified', 'branddrive-woocommerce'),
    'type' => __('Product Type', 'branddrive-woocommerce'),
    'attributes' => __('Attributes', 'branddrive-woocommerce'),
    'tax_class' => __('Tax Class', 'branddrive-woocommerce'),
    'tax_status' => __('Tax Status', 'branddrive-woocommerce'),
);

// Default selected columns
$default_columns = array('id', 'name', 'sku', 'price', 'stock_quantity', 'categories', 'images', 'description');
?>

<div class="branddrive-export-csv">
    <div class="branddrive-back-link">
        <a href="<?php echo admin_url('admin.php?page=branddrive'); ?>" class="branddrive-back-button">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php _e('Back to dashboard', 'branddrive-woocommerce'); ?>
        </a>
    </div>

    <h1 class="branddrive-page-title"><?php _e('Export products', 'branddrive-woocommerce'); ?></h1>

    <?php if (!$can_export): ?>
        <div class="branddrive-card">
            <div class="branddrive-notification error">
                <p><?php _e('BrandDrive integration is not properly configured. Please check your settings.', 'branddrive-woocommerce'); ?></p>
            </div>
            <a href="<?php echo admin_url('admin.php?page=branddrive&tab=settings'); ?>" class="branddrive-button branddrive-button-primary">
                <?php _e('Go to Settings', 'branddrive-woocommerce'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="branddrive-card">
            <h2 class="branddrive-card-title"><?php _e('Export products', 'branddrive-woocommerce'); ?></h2>

            <div id="branddrive_export_csv_notice" class="branddrive-notification" style="display: none;"></div>

            <form id="branddrive_export_csv_form" method="post" action="">
                <?php wp_nonce_field('branddrive_export_csv', 'branddrive_export_csv_nonce'); ?>

                <div class="branddrive-form-field">
                    <label for="branddrive_export_columns_input"><?php _e('Which columns should be exported?', 'branddrive-woocommerce'); ?></label>
                    <div class="branddrive-tag-input-container">
                        <div class="branddrive-selected-tags" id="branddrive_columns_tags"></div>
                        <input type="text" id="branddrive_export_columns_input" class="branddrive-tag-input" placeholder="<?php _e('Type to search columns...', 'branddrive-woocommerce'); ?>" />
                    </div>
                    <div class="branddrive-tag-suggestions" id="branddrive_columns_suggestions"></div>
                    <input type="hidden" id="branddrive_export_columns" name="branddrive_export_columns" value="<?php echo esc_attr(implode(',', $default_columns)); ?>" />
                    <div class="branddrive-tag-suggestions" id="branddrive_columns_suggestions"></div>
                </div>

                <div class="branddrive-form-field">
                    <label for="branddrive_export_product_types_input"><?php _e('Which product types should be exported?', 'branddrive-woocommerce'); ?></label>
                    <div class="branddrive-tag-input-container">
                        <div class="branddrive-selected-tags" id="branddrive_product_types_tags"></div>
                        <input type="text" id="branddrive_export_product_types_input" class="branddrive-tag-input" placeholder="<?php _e('Type to search product types...', 'branddrive-woocommerce'); ?>" />
                    </div>
                    <div class="branddrive-tag-suggestions" id="branddrive_product_types_suggestions"></div>
                    <input type="hidden" id="branddrive_export_product_types" name="branddrive_export_product_types" value="all" />
                    <div class="branddrive-tag-suggestions" id="branddrive_product_types_suggestions"></div>
                </div>

                <div class="branddrive-form-field">
                    <label for="branddrive_export_categories_input"><?php _e('Which product category should be exported?', 'branddrive-woocommerce'); ?></label>
                    <div class="branddrive-tag-input-container">
                        <div class="branddrive-selected-tags" id="branddrive_categories_tags"></div>
                        <input type="text" id="branddrive_export_categories_input" class="branddrive-tag-input" placeholder="<?php _e('Type to search categories...', 'branddrive-woocommerce'); ?>" />
                    </div>
                    <div class="branddrive-tag-suggestions" id="branddrive_categories_suggestions"></div>
                    <input type="hidden" id="branddrive_export_categories" name="branddrive_export_categories" value="all" />
                    <div class="branddrive-tag-suggestions" id="branddrive_categories_suggestions"></div>
                </div>

                <div class="branddrive-form-field">
                    <label><?php _e('Export custom meta?', 'branddrive-woocommerce'); ?></label>
                    <div class="branddrive-checkbox-field">
                        <input type="checkbox" id="branddrive_export_custom_meta" name="branddrive_export_custom_meta" value="1" checked>
                        <label for="branddrive_export_custom_meta"><?php _e('Yes, export all custom meta', 'branddrive-woocommerce'); ?></label>
                    </div>
                </div>

                <div class="branddrive-form-actions">
                    <button type="submit" id="branddrive_generate_csv" class="branddrive-button branddrive-button-primary">
                        <?php _e('Generate CSV', 'branddrive-woocommerce'); ?>
                    </button>
                    <div id="branddrive_export_csv_progress" style="display: none;">
                        <span class="spinner is-active"></span>
                        <span><?php _e('Generating CSV...', 'branddrive-woocommerce'); ?></span>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    .branddrive-export-csv {
        max-width: 800px;
        margin: 20px auto;
    }

    .branddrive-back-link {
        margin-bottom: 20px;
    }

    .branddrive-back-button {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
        color: #0052ff;
        font-weight: 500;
    }

    .branddrive-back-button .dashicons {
        margin-right: 5px;
    }

    .branddrive-page-title {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .branddrive-tag-input-container {
        position: relative;
        border: 1px solid #e7e8ea;
        border-radius: 4px;
        padding: 8px;
        background-color: #fff;
        min-height: 40px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
    }

    .branddrive-tag-input-container:focus-within {
        border-color: #0052ff;
        box-shadow: 0 0 0 1px #0052ff;
    }

    .branddrive-selected-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .branddrive-tag {
        background-color: #f0f2f5;
        border-radius: 4px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
        font-size: 13px;
    }

    .branddrive-tag-remove {
        margin-left: 5px;
        cursor: pointer;
        color: #666;
    }

    .branddrive-tag-input {
        flex: 1;
        border: none;
        outline: none;
        padding: 4px;
        min-width: 100px;
        background: transparent;
    }

    .branddrive-tag-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background-color: #fff;
        border: 1px solid #e7e8ea;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 100;
        display: none;
    }

    .branddrive-tag-suggestion {
        padding: 8px 12px;
        cursor: pointer;
    }

    .branddrive-tag-suggestion:hover {
        background-color: #f0f2f5;
    }

    .branddrive-checkbox-field {
        display: flex;
        align-items: center;
    }

    .branddrive-checkbox-field input[type="checkbox"] {
        margin-right: 8px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data for tag inputs
        const columnsData = <?php echo json_encode($available_columns); ?>;
        const productTypesData = <?php echo json_encode($product_types); ?>;
        const categoriesData = [
            { id: 'all', name: '<?php _e('All Categories', 'branddrive-woocommerce'); ?>' },
            <?php foreach ($categories as $category): ?>
            { id: '<?php echo esc_attr($category->term_id); ?>', name: '<?php echo esc_js($category->name); ?>' },
            <?php endforeach; ?>
        ];

        // Initialize tag inputs
        initTagInput('branddrive_export_columns', columnsData, <?php echo json_encode($default_columns); ?>);
        initTagInput('branddrive_export_product_types', productTypesData, ['all']);
        initTagInput('branddrive_export_categories', categoriesData, ['all']);

        function initTagInput(fieldId, data, defaultValues) {
            const hiddenInput = document.getElementById(fieldId);
            const inputField = document.getElementById(fieldId + '_input');
            const tagsContainer = document.getElementById(fieldId.replace('export_', '') + '_tags');
            const suggestionsContainer = document.getElementById(fieldId.replace('export_', '') + '_suggestions');

            // Check if all elements exist
            if (!hiddenInput || !inputField || !tagsContainer || !suggestionsContainer) {
                console.error('Missing DOM elements for tag input:', fieldId);
                return;
            }

            let selectedTags = [];

            // Initialize with default values
            if (defaultValues && defaultValues.length) {
                defaultValues.forEach(value => {
                    let tagData;
                    if (typeof data === 'object' && !Array.isArray(data)) {
                        // For objects like columnsData
                        if (data[value]) {
                            tagData = { id: value, name: data[value] };
                        }
                    } else if (Array.isArray(data)) {
                        // For arrays like categoriesData
                        tagData = data.find(item => item.id === value || item === value);
                    } else if (typeof data === 'object') {
                        // For product types which is an object but needs different handling
                        Object.entries(data).forEach(([key, label]) => {
                            if (key === value || value === 'all') {
                                tagData = { id: key, name: label };
                            }
                        });
                    }

                    if (tagData) {
                        addTag(tagData.id, tagData.name || tagData);
                    }
                });
            }

            // Show suggestions only on focus
            inputField.addEventListener('focus', function() {
                showSuggestions();
            });

            // Filter suggestions on input
            inputField.addEventListener('input', function() {
                showSuggestions();
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!suggestionsContainer.contains(e.target) && e.target !== inputField) {
                    suggestionsContainer.style.display = 'none';
                }
            });

            function showSuggestions() {
                const searchTerm = inputField.value.toLowerCase();
                let suggestions = [];

                if (typeof data === 'object' && !Array.isArray(data)) {
                    if (fieldId === 'branddrive_export_product_types') {
                        // Handle product types (object with key-value pairs)
                        suggestions = Object.entries(data)
                            .filter(([id, name]) => !selectedTags.some(tag => tag.id === id) &&
                                name.toLowerCase().includes(searchTerm))
                            .map(([id, name]) => ({ id, name }));
                    } else {
                        // For objects like columnsData
                        suggestions = Object.entries(data)
                            .filter(([id, name]) => !selectedTags.some(tag => tag.id === id))
                            .filter(([id, name]) => name.toLowerCase().includes(searchTerm))
                            .map(([id, name]) => ({ id, name }));
                    }
                } else if (Array.isArray(data)) {
                    // For arrays like categoriesData
                    suggestions = data
                        .filter(item => {
                            const itemName = typeof item === 'object' ? item.name : item;
                            const itemId = typeof item === 'object' ? item.id : item;
                            return !selectedTags.some(tag => tag.id === itemId) &&
                                itemName.toLowerCase().includes(searchTerm);
                        });
                }

                renderSuggestions(suggestions);
            }

            function renderSuggestions(suggestions) {
                suggestionsContainer.innerHTML = '';

                if (suggestions.length === 0) {
                    suggestionsContainer.style.display = 'none';
                    return;
                }

                suggestions.forEach(suggestion => {
                    const suggestionElement = document.createElement('div');
                    suggestionElement.className = 'branddrive-tag-suggestion';
                    suggestionElement.textContent = typeof suggestion === 'object' ? suggestion.name : suggestion;
                    suggestionElement.addEventListener('click', function() {
                        const id = typeof suggestion === 'object' ? suggestion.id : suggestion;
                        const name = typeof suggestion === 'object' ? suggestion.name : suggestion;
                        addTag(id, name);
                        inputField.value = '';
                        suggestionsContainer.style.display = 'none';
                    });

                    suggestionsContainer.appendChild(suggestionElement);
                });

                suggestionsContainer.style.display = 'block';
            }

            function addTag(id, name) {
                // Special case: if 'all' is selected, remove all other tags
                if (id === 'all') {
                    selectedTags = [];
                    tagsContainer.innerHTML = '';
                }
                // If adding a specific tag and 'all' is already selected, remove 'all'
                else if (selectedTags.some(tag => tag.id === 'all')) {
                    selectedTags = selectedTags.filter(tag => tag.id !== 'all');
                    tagsContainer.innerHTML = '';
                }

                // Add the new tag
                selectedTags.push({ id, name });

                const tagElement = document.createElement('div');
                tagElement.className = 'branddrive-tag';
                tagElement.innerHTML = `
                ${name}
                <span class="branddrive-tag-remove dashicons dashicons-no-alt"></span>
            `;

                const removeButton = tagElement.querySelector('.branddrive-tag-remove');
                if (removeButton) {
                    removeButton.addEventListener('click', function() {
                        removeTag(id);
                    });
                }

                tagsContainer.appendChild(tagElement);
                updateHiddenInput();
            }

            function removeTag(id) {
                selectedTags = selectedTags.filter(tag => tag.id !== id);
                tagsContainer.innerHTML = '';

                selectedTags.forEach(tag => {
                    const tagElement = document.createElement('div');
                    tagElement.className = 'branddrive-tag';
                    tagElement.innerHTML = `
                    ${tag.name}
                    <span class="branddrive-tag-remove dashicons dashicons-no-alt"></span>
                `;

                    const removeButton = tagElement.querySelector('.branddrive-tag-remove');
                    if (removeButton) {
                        removeButton.addEventListener('click', function() {
                            removeTag(tag.id);
                        });
                    }

                    tagsContainer.appendChild(tagElement);
                });

                updateHiddenInput();
            }

            function updateHiddenInput() {
                hiddenInput.value = selectedTags.map(tag => tag.id).join(',');
            }
        }
    });
</script>

<?php if (isset($branddrive->settings) && $branddrive->settings->is_debug_mode()): ?>
    <div class="branddrive-card" id="branddrive-debug-section">
        <h2 class="branddrive-card-title"><?php _e('Debug Information', 'branddrive-woocommerce'); ?></h2>

        <div class="branddrive-debug-info">
            <p><?php _e('Debug mode is enabled. Check the browser console for detailed logs.', 'branddrive-woocommerce'); ?></p>

            <p><?php _e('Debug log file location:', 'branddrive-woocommerce'); ?></p>
            <code><?php echo esc_html(wp_upload_dir()['basedir'] . '/branddrive-csv-export-debug.log'); ?></code>

            <div class="branddrive-form-actions">
                <button type="button" id="branddrive_view_debug_log" class="branddrive-button">
                    <?php _e('View Debug Log', 'branddrive-woocommerce'); ?>
                </button>
            </div>

            <div id="branddrive_debug_log_content" style="display: none; margin-top: 15px;">
                <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;"></textarea>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add debug log viewer functionality
            document.getElementById('branddrive_view_debug_log').addEventListener('click', function() {
                const logContent = document.getElementById('branddrive_debug_log_content');
                const textarea = logContent.querySelector('textarea');
                const button = this;

                if (logContent.style.display === 'none') {
                    // Show log content
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            'action': 'branddrive_get_debug_log',
                            'nonce': '<?php echo wp_create_nonce('branddrive_debug_log'); ?>'
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                textarea.value = data.data.log_content;
                                logContent.style.display = 'block';
                                button.textContent = '<?php _e('Hide Debug Log', 'branddrive-woocommerce'); ?>';
                            } else {
                                console.error('Failed to load debug log:', data.data.message);
                                alert('Failed to load debug log: ' + data.data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching debug log:', error);
                            alert('Error fetching debug log: ' + error.message);
                        });
                } else {
                    // Hide log content
                    logContent.style.display = 'none';
                    button.textContent = '<?php _e('View Debug Log', 'branddrive-woocommerce'); ?>';
                }
            });
        });
    </script>
<?php endif; ?>
