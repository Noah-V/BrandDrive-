/**
 * BrandDrive Sync Orders JavaScript
 */
;(($) => {
    $(document).ready(() => {
        // Check if branddrive_sync_params is defined, if not, define it as an empty object
        if (typeof branddrive_sync_params === "undefined") {
            var branddrive_sync_params = {}
        }

        // Track current and pending filters
        const currentFilters = {}
        let pendingFilters = {}
        const activeDropdown = null
        const activeNestedDropdown = null

        // Selection state tracking
        let selectedOrderIds = []
        let selectAllAcrossPages = false
        const totalOrdersCount = Number.parseInt($("#total_orders_count").val() || "0")
        const currentPageOrderIds = []

        // Collect all order IDs on the current page
        $(".branddrive_order_checkbox").each(function () {
            currentPageOrderIds.push($(this).val())
        })

        // Initialize from URL parameters
        const urlParams = new URLSearchParams(window.location.search)
        if (urlParams.has("status")) {
            currentFilters.status = urlParams.get("status").split(",")

            // Check the corresponding checkboxes
            currentFilters.status.forEach((status) => {
                $(`#filter_status_${status}`).prop("checked", true)
            })
        } else {
            currentFilters.status = []
        }

        if (urlParams.has("currency")) {
            currentFilters.currency = urlParams.get("currency").split(",")

            // Check the corresponding checkboxes
            currentFilters.currency.forEach((currency) => {
                $(`#filter_currency_${currency.toLowerCase()}`).prop("checked", true)
            })
        } else {
            currentFilters.currency = []
        }

        if (urlParams.has("date")) {
            currentFilters.date = urlParams.get("date")

            // Check the corresponding radio button
            $(`#filter_date_${currentFilters.date}`).prop("checked", true)

            // If custom date range is selected, show the date inputs
            if (currentFilters.date === "custom") {
                $("#custom_date_range").show()

                // Set the date inputs if they exist in URL
                if (urlParams.has("date_start")) {
                    $("#date_start").val(urlParams.get("date_start"))
                    currentFilters.date_start = urlParams.get("date_start")
                    pendingFilters.date_start = urlParams.get("date_start")
                }

                if (urlParams.has("date_end")) {
                    $("#date_end").val(urlParams.get("date_end"))
                    currentFilters.date_end = urlParams.get("date_end")
                    pendingFilters.date_end = urlParams.get("date_end")
                }
            }
        } else {
            currentFilters.date = ""
            // Check the "No Date Filter" radio button
            $("#filter_date_none").prop("checked", true)
        }

        // Initialize pending filters with current filters
        pendingFilters = JSON.parse(JSON.stringify(currentFilters))

        // Try to restore selection state from localStorage
        try {
            const savedSelection = localStorage.getItem("branddrive_selected_orders")
            if (savedSelection) {
                const savedState = JSON.parse(savedSelection)
                selectedOrderIds = savedState.selectedOrderIds || []
                selectAllAcrossPages = savedState.selectAllAcrossPages || false

                // Update UI based on restored state
                updateSelectionUI()
            }
        } catch (e) {
            console.error("Error restoring selection state:", e)
            // Reset selection state if there was an error
            selectedOrderIds = []
            selectAllAcrossPages = false
        }

        // We'll let nested-dropdowns.js handle all dropdown functionality
        // No replacement code needed here

        // Handle status filter checkboxes
        $('.filter-checkbox[data-filter="status"]').on("change", function () {
            const value = $(this).data("value")

            // Initialize status array if it doesn't exist
            if (!pendingFilters.status) {
                pendingFilters.status = []
            }

            if ($(this).prop("checked")) {
                // Add status to pending filters if not already there
                if (!pendingFilters.status.includes(value)) {
                    pendingFilters.status.push(value)
                }
            } else {
                // Remove status from pending filters
                pendingFilters.status = pendingFilters.status.filter((status) => status !== value)
            }

            // Enable apply button if filters have changed
            updateApplyButton()
        })

        // Handle currency filter checkboxes
        $('.filter-checkbox[data-filter="currency"]').on("change", function () {
            const value = $(this).data("value")

            // Initialize currency array if it doesn't exist
            if (!pendingFilters.currency) {
                pendingFilters.currency = []
            }

            if ($(this).prop("checked")) {
                // Add currency to pending filters if not already there
                if (!pendingFilters.currency.includes(value)) {
                    pendingFilters.currency.push(value)
                }
            } else {
                // Remove currency from pending filters
                pendingFilters.currency = pendingFilters.currency.filter((currency) => currency !== value)
            }

            // Enable apply button if filters have changed
            updateApplyButton()
        })

        // Handle date filter radio buttons
        $('.filter-radio[data-filter="date"]').on("change", function () {
            const value = $(this).data("value")
            pendingFilters.date = value

            // Show/hide custom date range inputs
            if (value === "custom") {
                $("#custom_date_range").show()
            } else {
                $("#custom_date_range").hide()
                // Clear custom date values if not using custom date filter
                delete pendingFilters.date_start
                delete pendingFilters.date_end
            }

            // Enable apply button if filters have changed
            updateApplyButton()
        })

        // Handle custom date inputs
        $("#date_start, #date_end").on("change", () => {
            if (pendingFilters.date === "custom") {
                pendingFilters.date_start = $("#date_start").val()
                pendingFilters.date_end = $("#date_end").val()
                updateApplyButton()
            }
        })

        // Handle filter tag removal - apply immediately
        $(document).on("click", ".filter-remove", function () {
            const filterTag = $(this).closest(".branddrive-filter-tag")
            const filterType = filterTag.data("filter")
            const filterValue = filterTag.data("value")

            if (filterType === "status") {
                // Remove just this specific status
                pendingFilters.status = pendingFilters.status.filter((status) => status !== filterValue)
                $(`#filter_status_${filterValue}`).prop("checked", false)

                // Apply filters immediately
                applyFilters()
            } else if (filterType === "currency") {
                // Remove just this specific currency
                pendingFilters.currency = pendingFilters.currency.filter((currency) => currency !== filterValue)
                $(`#filter_currency_${filterValue.toLowerCase()}`).prop("checked", false)

                // Apply filters immediately
                applyFilters()
            } else if (filterType === "date") {
                pendingFilters.date = ""
                delete pendingFilters.date_start
                delete pendingFilters.date_end
                $("#filter_date_none").prop("checked", true)
                $("#custom_date_range").hide()

                // Apply filters immediately
                applyFilters()
            } else if (filterType === "date_range") {
                // Keep the custom date filter but remove the date range
                delete pendingFilters.date_start
                delete pendingFilters.date_end
                $("#date_start").val("")
                $("#date_end").val("")

                // Apply filters immediately
                applyFilters()
            }
        })

        // Update apply button state
        function updateApplyButton() {
            const hasChanges = !areFiltersEqual(currentFilters, pendingFilters)
            $("#branddrive_apply_filters").prop("disabled", !hasChanges)
        }

        // Compare filters deeply
        function areFiltersEqual(filters1, filters2) {
            // Check if both objects have the same keys
            const keys1 = Object.keys(filters1)
            const keys2 = Object.keys(filters2)

            if (keys1.length !== keys2.length) {
                return false
            }

            // Check each key's value
            for (const key of keys1) {
                if (!filters2.hasOwnProperty(key)) {
                    return false
                }

                // Handle arrays (like status)
                if (Array.isArray(filters1[key])) {
                    if (
                        !Array.isArray(filters2[key]) ||
                        filters1[key].length !== filters2[key].length ||
                        !filters1[key].every((val) => filters2[key].includes(val))
                    ) {
                        return false
                    }
                }
                // Handle primitive values
                else if (filters1[key] !== filters2[key]) {
                    return false
                }
            }

            return true
        }

        // Apply filters when the apply button is clicked
        $("#branddrive_apply_filters").on("click", () => {
            applyFilters()
        })

        function applyFilters() {
            // Reset selection state when filters change
            selectedOrderIds = []
            selectAllAcrossPages = false
            saveSelectionState()

            // Build query string from pending filters
            const queryParams = new URLSearchParams()
            queryParams.append("page", "branddrive")
            queryParams.append("tab", "sync")

            // Add current pagination settings
            const currentPerPage = $("#branddrive_per_page").val() || "100"
            queryParams.append("per_page", currentPerPage)

            // Add status filter if any statuses are selected
            if (pendingFilters.status && pendingFilters.status.length > 0) {
                queryParams.append("status", pendingFilters.status.join(","))
            }

            // Add currency filter if any currencies are selected
            if (pendingFilters.currency && pendingFilters.currency.length > 0) {
                queryParams.append("currency", pendingFilters.currency.join(","))
            }

            // Add date filter if selected
            if (pendingFilters.date) {
                queryParams.append("date", pendingFilters.date)

                // Add custom date range if applicable
                if (pendingFilters.date === "custom") {
                    if (pendingFilters.date_start) {
                        queryParams.append("date_start", pendingFilters.date_start)
                    }
                    if (pendingFilters.date_end) {
                        queryParams.append("date_end", pendingFilters.date_end)
                    }
                }
            }

            // Redirect to filtered URL
            window.location.href = `${window.location.pathname}?${queryParams.toString()}`
        }

        // Select/deselect all orders on current page
        $("#branddrive_select_all_orders").on("change", function () {
            const isChecked = $(this).prop("checked")

            // Update checkboxes on current page
            $(".branddrive_order_checkbox").prop("checked", isChecked)

            if (isChecked) {
                // If checking the box, show the "select all across pages" message
                $("#branddrive_select_all_message").show()

                // Add all current page order IDs to selection if they're not already there
                currentPageOrderIds.forEach((orderId) => {
                    if (!selectedOrderIds.includes(orderId)) {
                        selectedOrderIds.push(orderId)
                    }
                })
            } else {
                // If unchecking, hide the message and remove current page orders from selection
                $("#branddrive_select_all_message").hide()
                selectAllAcrossPages = false

                // Remove all current page order IDs from selection
                selectedOrderIds = selectedOrderIds.filter((id) => !currentPageOrderIds.includes(id))
            }

            updateSelectionUI()
            saveSelectionState()
        })

        // Handle individual order checkbox changes
        $(".branddrive_order_checkbox").on("change", function () {
            const orderId = $(this).val()
            const isChecked = $(this).prop("checked")

            if (isChecked) {
                // Add to selected IDs if not already there
                if (!selectedOrderIds.includes(orderId)) {
                    selectedOrderIds.push(orderId)
                }
            } else {
                // Remove from selected IDs
                selectedOrderIds = selectedOrderIds.filter((id) => id !== orderId)

                // If we had "select all across pages" active, deactivate it
                if (selectAllAcrossPages) {
                    selectAllAcrossPages = false
                    $("#branddrive_select_all_message").hide()
                }
            }

            // Update the "select all" checkbox based on current page selection
            updateSelectAllCheckbox()
            updateSelectionUI()
            saveSelectionState()
        })

        // Handle "Select all across pages" button click
        $("#branddrive_select_all_across_pages").on("click", (e) => {
            e.preventDefault()

            selectAllAcrossPages = true

            // Check all checkboxes on the current page
            $("#branddrive_select_all_orders").prop("checked", true)
            $(".branddrive_order_checkbox").prop("checked", true)

            // Add all current page order IDs to selection
            currentPageOrderIds.forEach((orderId) => {
                if (!selectedOrderIds.includes(orderId)) {
                    selectedOrderIds.push(orderId)
                }
            })

            updateSelectionUI()
            saveSelectionState()
        })

        // Handle "Clear selection" button click
        $("#branddrive_clear_selection").on("click", (e) => {
            e.preventDefault()

            // Reset selection state
            selectedOrderIds = []
            selectAllAcrossPages = false

            // Uncheck all checkboxes on the current page
            $("#branddrive_select_all_orders").prop("checked", false)
            $(".branddrive_order_checkbox").prop("checked", false)

            // Hide the selection message
            $("#branddrive_select_all_message").hide()

            updateSelectionUI()
            saveSelectionState()
        })

        // Update the "select all" checkbox based on current page selection
        function updateSelectAllCheckbox() {
            const totalCheckboxes = $(".branddrive_order_checkbox").length
            const checkedCheckboxes = $(".branddrive_order_checkbox:checked").length

            // If all checkboxes on the current page are checked, check the "select all" checkbox
            $("#branddrive_select_all_orders").prop("checked", totalCheckboxes > 0 && checkedCheckboxes === totalCheckboxes)

            // Show/hide the "select all across pages" message
            if (checkedCheckboxes > 0) {
                $("#branddrive_select_all_message").show()
            } else {
                $("#branddrive_select_all_message").hide()
            }
        }

        // Update the UI to reflect the current selection state
        function updateSelectionUI() {
            // Update individual checkboxes on the current page
            $(".branddrive_order_checkbox").each(function () {
                const orderId = $(this).val()
                $(this).prop("checked", selectedOrderIds.includes(orderId))
            })

            // Update row highlighting
            updateSelectedRows()

            // Update the "select all" checkbox
            updateSelectAllCheckbox()

            // Update the selection count
            let selectionText
            if (selectAllAcrossPages) {
                selectionText = `All ${totalOrdersCount} orders across all pages are selected`
                $("#branddrive_select_all_across_pages").hide()
                $("#branddrive_clear_selection").show()
            } else {
                selectionText = `${selectedOrderIds.length} orders selected`

                // Show "select all across pages" button if some orders are selected
                if (selectedOrderIds.length > 0) {
                    $("#branddrive_select_all_across_pages").show()
                    $("#branddrive_clear_selection").show()
                } else {
                    $("#branddrive_select_all_across_pages").hide()
                    $("#branddrive_clear_selection").hide()
                }
            }

            $("#branddrive_selection_count").text(selectionText)

            // Enable/disable the bulk action button based on selection
            updateBulkActionButton()
        }

        // Save selection state to localStorage
        function saveSelectionState() {
            try {
                const selectionState = {
                    selectedOrderIds,
                    selectAllAcrossPages,
                    filters: currentFilters,
                }
                localStorage.setItem("branddrive_selected_orders", JSON.stringify(selectionState))
            } catch (e) {
                console.error("Error saving selection state:", e)
            }
        }

        function updateSelectedRows() {
            $(".branddrive_order_checkbox").each(function () {
                if ($(this).prop("checked")) {
                    $(this).closest("tr").addClass("selected")
                } else {
                    $(this).closest("tr").removeClass("selected")
                }
            })
        }

        // Handle bulk action selection - trigger sync immediately
        $(document).on("click", ".bulk-action-option", function (e) {
            e.preventDefault()

            // Get the action type
            const action = $(this).data("action")

            // If it's the sync action, execute it immediately
            if (action === "sync") {
                if (selectedOrderIds.length === 0 && !selectAllAcrossPages) {
                    alert("Please select at least one order.")
                    return
                }

                // If "select all across pages" is active, pass a special flag to the server
                if (selectAllAcrossPages) {
                    syncAllOrders()
                } else {
                    syncOrders(selectedOrderIds)
                }
            }
        })

        // Update bulk action button state
        function updateBulkActionButton() {
            const hasSelection = selectedOrderIds.length > 0 || selectAllAcrossPages
            $("#bulk-actions-btn").prop("disabled", !hasSelection)
        }

        // Sync single order
        $(".branddrive-sync-single-order").on("click", function () {
            const orderId = $(this).data("order-id")
            syncOrders([orderId])
        })

        // Replace the syncOrders and syncAllOrders functions with placeholder implementations

        // Find the syncOrders function and replace it with this placeholder
        function syncOrders(orderIds) {
            // Show progress indicator
            $("#branddrive_sync_progress").show()

            // Disable buttons
            $(".branddrive-sync-single-order").prop("disabled", true)
            $("#bulk-actions-btn").prop("disabled", true)

            // PLACEHOLDER: This is a mock implementation for testing the UI
            console.log("PLACEHOLDER: Would sync these specific orders:", orderIds)

            // Simulate a network request with setTimeout
            setTimeout(() => {
                // Hide progress indicator
                $("#branddrive_sync_progress").hide()

                // Enable buttons
                $(".branddrive-sync-single-order").prop("disabled", false)
                $("#bulk-actions-btn").prop("disabled", false)

                // Show success message
                const notice = $("#branddrive_sync_notice")
                notice
                    .removeClass("error")
                    .addClass("success")
                    .html(
                        "<p>" +
                        orderIds.length +
                        ' orders synced successfully</p><span class="close dashicons dashicons-no-alt"></span>',
                    )
                    .show()

                // Clear selection after successful sync
                selectedOrderIds = []
                selectAllAcrossPages = false
                saveSelectionState()
                updateSelectionUI()

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    notice.fadeOut()
                }, 5000)

                // Add close button functionality
                notice.find(".close").on("click", () => {
                    notice.fadeOut()
                })
            }, 1500)
        }

        // Find the syncAllOrders function and replace it with this placeholder
        function syncAllOrders() {
            // Show progress indicator
            $("#branddrive_sync_progress").show()

            // Disable buttons
            $(".branddrive-sync-single-order").prop("disabled", true)
            $("#bulk-actions-btn").prop("disabled", true)

            // PLACEHOLDER: This is a mock implementation for testing the UI
            console.log("PLACEHOLDER: Would sync ALL orders matching these filters:", currentFilters)

            // Simulate a network request with setTimeout
            setTimeout(() => {
                // Hide progress indicator
                $("#branddrive_sync_progress").hide()

                // Enable buttons
                $(".branddrive-sync-single-order").prop("disabled", false)
                $("#bulk-actions-btn").prop("disabled", false)

                // Show success message
                const notice = $("#branddrive_sync_notice")
                notice
                    .removeClass("error")
                    .addClass("success")
                    .html(
                        "<p>All " +
                        totalOrdersCount +
                        ' orders synced successfully</p><span class="close dashicons dashicons-no-alt"></span>',
                    )
                    .show()

                // Clear selection after successful sync
                selectedOrderIds = []
                selectAllAcrossPages = false
                saveSelectionState()
                updateSelectionUI()

                // Auto-hide after 5 seconds
                setTimeout(() => {
                    notice.fadeOut()
                }, 5000)

                // Add close button functionality
                notice.find(".close").on("click", () => {
                    notice.fadeOut()
                })
            }, 1500)
        }

        // Handle sync response
        function handleSyncResponse(response, count) {
            // Clear selection after successful sync
            selectedOrderIds = []
            selectAllAcrossPages = false
            saveSelectionState()
            updateSelectionUI()

            // Show success/error message
            const notice = $("#branddrive_sync_notice")
            if (response.success) {
                notice
                    .removeClass("error")
                    .addClass("success")
                    .html("<p>" + count + " orders synced successfully</p><span class='close dashicons dashicons-no-alt'></span>")
                    .show()
            } else {
                notice
                    .removeClass("success")
                    .addClass("error")
                    .html(
                        "<p>" +
                        (response.data ? response.data.message : "Error syncing orders") +
                        "</p><span class='close dashicons dashicons-no-alt'></span>",
                    )
                    .show()
            }

            // Auto-hide after 5 seconds
            setTimeout(() => {
                notice.fadeOut()
            }, 5000)

            // Add close button functionality
            notice.find(".close").on("click", () => {
                notice.fadeOut()
            })
        }

        // Initialize UI
        updateSelectionUI()
        updateBulkActionButton()
        updateApplyButton()

        // Add scroll event listener to handle sticky header shadow effect
        const ordersTable = document.querySelector(".branddrive-orders-table")
        if (ordersTable) {
            window.addEventListener("scroll", () => {
                // Add 'is-scrolled' class when scrolled past the top of the table
                const tableTop = ordersTable.getBoundingClientRect().top
                if (tableTop < 32) {
                    // 32px is the WordPress admin bar height
                    ordersTable.classList.add("is-scrolled")
                } else {
                    ordersTable.classList.remove("is-scrolled")
                }
            })
        }

        // Handle clear all filters button
        $(document).on("click", ".branddrive-clear-filters-button", () => {
            // Clear all pending filters
            pendingFilters.status = []
            pendingFilters.currency = []
            pendingFilters.date = ""
            delete pendingFilters.date_start
            delete pendingFilters.date_end

            // Uncheck all checkboxes
            $(".filter-checkbox").prop("checked", false)

            // Reset date radio buttons
            $("#filter_date_none").prop("checked", true)

            // Hide custom date range
            $("#custom_date_range").hide()
            $("#date_start").val("")
            $("#date_end").val("")

            // Apply the cleared filters
            applyFilters()
        })

        // Function to update clear all filters button visibility
        function updateClearAllButton() {
            const filterCount = getActiveFilterCount()
            if (filterCount > 2) {
                $("#branddrive_clear_all_filters").show()
            } else {
                $("#branddrive_clear_all_filters").hide()
            }
        }

        // Function to count active filters
        function getActiveFilterCount() {
            let count = 0

            // Count status filters
            if (pendingFilters.status && pendingFilters.status.length > 0) {
                count += pendingFilters.status.length
            }

            // Count currency filters
            if (pendingFilters.currency && pendingFilters.currency.length > 0) {
                count += pendingFilters.currency.length
            }

            // Count date filter
            if (pendingFilters.date) {
                count += 1

                // Custom date range counts as an additional filter if both dates are set
                if (pendingFilters.date === "custom" && pendingFilters.date_start && pendingFilters.date_end) {
                    count += 1
                }
            }

            return count
        }

        // Update clear all button when filters change
        $(document).on("change", ".filter-checkbox, .filter-radio", () => {
            updateClearAllButton()
        })

        // Update clear all button when custom date inputs change
        $("#date_start, #date_end").on("change", () => {
            updateClearAllButton()
        })

        // Initialize clear all button on page load
        updateClearAllButton()
    })
})(jQuery)
