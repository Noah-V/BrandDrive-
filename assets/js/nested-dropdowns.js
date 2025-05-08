/**
 * BrandDrive Nested Dropdowns JavaScript
 * This script handles all dropdown functionality for the BrandDrive admin interface
 */
;(($) => {
    $(document).ready(() => {
        // Add this CSS class to the head of the document at the beginning of the document ready function
        // Add this right after the $(document).ready(() => { line
        $("head").append(`
      <style>
        body.dropdown-open {
          overflow: hidden;
        }
      </style>
    `)

        // DOM elements
        const $body = $("body")
        const $dropdowns = $(".branddrive-dropdown")
        const $overlay = $("#dropdown-overlay")
        const $subdropdownOverlay = $("#subdropdown-overlay")
        const $applyButton = $("#branddrive_apply_filters")

        // Track active dropdowns
        let activeMainDropdown = null
        let activeNestedDropdown = null
        let activeFilterOption = null

        // Track pending filters
        const pendingFilters = {}

        // Initialize from URL parameters
        const urlParams = new URLSearchParams(window.location.search)

        // Status filters
        if (urlParams.has("status")) {
            pendingFilters.status = urlParams.get("status").split(",")

            // Check the corresponding checkboxes
            pendingFilters.status.forEach((status) => {
                $("#filter_status_" + status).prop("checked", true)
            })
        } else {
            pendingFilters.status = []
        }

        // Currency filters
        if (urlParams.has("currency")) {
            pendingFilters.currency = urlParams.get("currency").split(",")

            // Check the corresponding checkboxes
            pendingFilters.currency.forEach((currency) => {
                $("#filter_currency_" + currency.toLowerCase()).prop("checked", true)
            })
        } else {
            pendingFilters.currency = []
        }

        // Date filter
        if (urlParams.has("date")) {
            pendingFilters.date = urlParams.get("date")

            // Check the corresponding radio button
            $("#filter_date_" + pendingFilters.date).prop("checked", true)

            // If custom date range is selected, show the date inputs
            if (pendingFilters.date === "custom") {
                $("#custom_date_range").show()

                // Set the date inputs if they exist in URL
                if (urlParams.has("date_start")) {
                    $("#date_start").val(urlParams.get("date_start"))
                    pendingFilters.date_start = urlParams.get("date_start")
                }

                if (urlParams.has("date_end")) {
                    $("#date_end").val(urlParams.get("date_end"))
                    pendingFilters.date_end = urlParams.get("date_end")
                }
            }
        } else {
            pendingFilters.date = ""
            // Check the "No Date Filter" radio button
            $("#filter_date_none").prop("checked", true)
        }

        // SVG for the right arrow
        const rightArrowSvg = `
      <svg width="24" height="24" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg" class="filter-option-arrow">
        <path d="M12 21.5C16.9706 21.5 21 17.4706 21 12.5C21 7.52944 16.9706 3.5 12 3.5C7.02944 3.5 3 7.52944 3 12.5C3 17.4706 7.02944 21.5 12 21.5Z" fill="#E6F1FB" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M13.5 12.5L10.5 15.5" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M10.5 9.5L13.5 12.5" stroke="#0A5FFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    `

        // Create the filters dropdown content
        function createFiltersDropdown() {
            const $filtersButton = $('.branddrive-dropdown-button:contains("Filters")')
            const $filtersDropdown = $filtersButton.siblings(".branddrive-dropdown-content")

            // Clear existing content
            $filtersDropdown.empty()

            // Add filter options
            const filterOptions = [
                { id: "status", label: "Status" },
                { id: "currency", label: "Currency" },
                { id: "date", label: "Date" },
            ]

            filterOptions.forEach((option) => {
                const $option = $(`
         <div class="filter-option" data-filter="${option.id}">
           <div class="filter-option-content">
             <span>${option.label}</span>
             ${rightArrowSvg}
           </div>
         </div>
       `)

                $filtersDropdown.append($option)
            })

            // Add hover effect
            $filtersDropdown.find(".filter-option").hover(
                function () {
                    $(this).css("background-color", "#edf5ff")
                },
                function () {
                    // Only remove background if not active
                    if (!$(this).hasClass("active")) {
                        $(this).css("background-color", "")
                    }
                },
            )
        }

        // Initialize the dropdowns
        createFiltersDropdown()

        // Handle all dropdown button clicks
        $(".branddrive-dropdown-button").on("click", function (e) {
            e.stopPropagation()

            const $button = $(this)
            const $dropdown = $button.closest(".branddrive-dropdown")
            const $content = $dropdown.find(".branddrive-dropdown-content")

            // If this dropdown is already active, close it
            if ($content.is(":visible")) {
                closeAllDropdowns()
                return
            }

            // Close any open dropdowns
            closeAllDropdowns()

            // Show this dropdown
            $content.show()
            activeMainDropdown = $content.attr("id")

            // Show overlay
            $overlay.show()

            // Disable page scrolling
            $("body").addClass("dropdown-open")
        })

        // Handle filter option click (to show nested dropdown)
        $(document).on("click", ".filter-option[data-filter]", function (e) {
            e.stopPropagation()

            const filterId = $(this).data("filter")

            // Hide any previously shown nested dropdown
            $(".branddrive-nested-dropdown").hide()

            // Remove active class from all filter options
            $(".filter-option").removeClass("active")

            // Add active class to this filter option
            $(this).addClass("active")

            // Store reference to active filter option
            activeFilterOption = $(this)

            // Show the nested dropdown for this filter type
            const $nestedDropdown = $("#" + filterId + "-dropdown")

            if ($nestedDropdown.length) {
                // Position the nested dropdown properly
                const optionRect = this.getBoundingClientRect()

                // Calculate position relative to the viewport
                $nestedDropdown.css({
                    display: "block",
                    position: "fixed",
                    top: optionRect.top,
                    left: optionRect.right + 20,
                    "z-index": 120,
                })

                activeNestedDropdown = filterId

                // Show subdropdown overlay
                $subdropdownOverlay.show()

                // Ensure scrolling remains disabled
                $("body").addClass("dropdown-open")
            }
        })

        // Handle status filter checkboxes
        $(document).on("change", '.filter-checkbox[data-filter="status"]', function () {
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
        $(document).on("change", '.filter-checkbox[data-filter="currency"]', function () {
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
        $(document).on("change", '.filter-radio[data-filter="date"]', function () {
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
        $("#date_start, #date_end").on("change", function () {
            if (pendingFilters.date === "custom") {
                if ($(this).attr("id") === "date_start") {
                    pendingFilters.date_start = $(this).val()
                } else {
                    pendingFilters.date_end = $(this).val()
                }
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
                $("#filter_status_" + filterValue).prop("checked", false)

                // Apply filters immediately
                applyFilters()
            } else if (filterType === "currency") {
                // Remove just this specific currency
                pendingFilters.currency = pendingFilters.currency.filter((currency) => currency !== filterValue)
                $("#filter_currency_" + filterValue.toLowerCase()).prop("checked", false)

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
            const hasChanges = !areFiltersEqual(getCurrentFiltersFromURL(), pendingFilters)
            $applyButton.prop("disabled", !hasChanges)
        }

        // Get current filters from URL
        function getCurrentFiltersFromURL() {
            const currentFilters = {}
            const urlParams = new URLSearchParams(window.location.search)

            if (urlParams.has("status")) {
                currentFilters.status = urlParams.get("status").split(",")
            } else {
                currentFilters.status = []
            }

            if (urlParams.has("currency")) {
                currentFilters.currency = urlParams.get("currency").split(",")
            } else {
                currentFilters.currency = []
            }

            if (urlParams.has("date")) {
                currentFilters.date = urlParams.get("date")

                if (currentFilters.date === "custom") {
                    if (urlParams.has("date_start")) {
                        currentFilters.date_start = urlParams.get("date_start")
                    }

                    if (urlParams.has("date_end")) {
                        currentFilters.date_end = urlParams.get("date_end")
                    }
                }
            } else {
                currentFilters.date = ""
            }

            return currentFilters
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
        $applyButton.on("click", () => {
            applyFilters()
        })

        function applyFilters() {
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
            window.location.href = window.location.pathname + "?" + queryParams.toString()
        }

        // Handle checkbox container click (make entire container clickable)
        $(document).on("click", ".filter-option-checkbox, .filter-option-radio", function (e) {
            // Only toggle if the click was directly on the container, not on inputs or labels
            if (e.target === this) {
                const $input = $(this).find("input")
                $input.prop("checked", !$input.prop("checked")).trigger("change")
            }
        })

        // Handle bulk action option click
        $(document).on("click", ".bulk-action-option", function (e) {
            e.preventDefault()
            e.stopPropagation()

            const action = $(this).data("action")

            // Close dropdowns
            closeAllDropdowns()

            // If it's the sync action, execute it immediately
            if (action === "sync") {
                const selectedOrders = $(".branddrive_order_checkbox:checked")

                if (selectedOrders.length === 0) {
                    alert("Please select at least one order.")
                    return
                }

                const orderIds = []
                selectedOrders.each(function () {
                    orderIds.push($(this).val())
                })

                // Trigger sync action (handled by sync-orders.js)
                $(document).trigger("branddrive:sync-orders", [orderIds])
            }
        })

        // Close dropdowns when clicking outside
        $(document).on("click", (e) => {
            if (!$(e.target).closest(".branddrive-dropdown, .branddrive-nested-dropdown").length) {
                closeAllDropdowns()
            }
        })

        // Close dropdowns when clicking on main overlay
        $overlay.on("click", () => {
            closeAllDropdowns()
        })

        // Close only subdropdown when clicking on subdropdown overlay
        $subdropdownOverlay.on("click", (e) => {
            e.stopPropagation() // Stop event from bubbling to main overlay
            closeSubdropdown()
        })

        // Function to close all dropdowns
        function closeAllDropdowns() {
            $(".branddrive-dropdown-content").hide()
            $(".branddrive-nested-dropdown").hide()
            $(".filter-option").removeClass("active")
            $overlay.hide()
            $subdropdownOverlay.hide()
            activeMainDropdown = null
            activeNestedDropdown = null
            activeFilterOption = null

            // Re-enable page scrolling
            $("body").removeClass("dropdown-open")
        }

        // Function to close only subdropdown
        function closeSubdropdown() {
            $(".branddrive-nested-dropdown").hide()
            $(".filter-option").removeClass("active")
            $subdropdownOverlay.hide()
            activeNestedDropdown = null
            activeFilterOption = null
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

        // Initialize UI
        updateApplyButton()
    })
})(jQuery)
