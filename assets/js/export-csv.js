/**
 * BrandDrive Export CSV JavaScript
 */
;(($) => {
    // Check if jQuery is defined
    if (typeof jQuery === "undefined") {
        console.error("jQuery is not defined. Please ensure jQuery is properly loaded.")
        return
    }

    // Check if branddrive_params is defined
    if (typeof branddrive_params === "undefined") {
        console.error("branddrive_params is not defined. Please ensure it is properly loaded.")
        return
    }

    // Initialize when document is ready
    $(document).ready(() => {
        console.log("Export CSV script initialized")

        // Show notification function
        function showNotification(container, type, message) {
            container
                .removeClass("success error warning info")
                .addClass(type)
                .html("<p>" + message + '</p><span class="close dashicons dashicons-no-alt"></span>')
                .show()

            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.fadeOut()
            }, 5000)

            // Close button
            container.find(".close").on("click", () => {
                container.fadeOut()
            })
        }

        // Handle CSV export form submission
        $("#branddrive_export_csv_form").on("submit", function (e) {
            e.preventDefault()
            console.log("Export CSV form submitted")

            const form = $(this)
            const submitButton = $("#branddrive_generate_csv")
            const progressIndicator = $("#branddrive_export_csv_progress")
            const noticeContainer = $("#branddrive_export_csv_notice")

            // Get form data
            const columnsValue = $("#branddrive_export_columns").val() || ""
            const productTypesValue = $("#branddrive_export_product_types").val() || ""
            const categoriesValue = $("#branddrive_export_categories").val() || ""

            console.log("Form data:", {
                columns: columnsValue,
                productTypes: productTypesValue,
                categories: categoriesValue,
            })

            // Make sure we're sending valid data
            const columns = columnsValue ? columnsValue.split(",") : []
            const productTypes = productTypesValue ? productTypesValue.split(",") : []
            const categories = categoriesValue ? categoriesValue.split(",") : []
            const exportCustomMeta = $("#branddrive_export_custom_meta").is(":checked") ? "1" : "0"

            // Show loading
            submitButton.prop("disabled", true)
            progressIndicator.show()

            console.log("Sending AJAX request to:", branddrive_params.ajax_url)
            console.log("Request data:", {
                action: "branddrive_export_products_csv",
                nonce: branddrive_params.nonce,
                columns: columns,
                product_types: productTypes,
                categories: categories,
                export_custom_meta: exportCustomMeta,
            })

            // Send AJAX request
            $.ajax({
                url: branddrive_params.ajax_url,
                type: "POST",
                data: {
                    action: "branddrive_export_products_csv",
                    nonce: branddrive_params.nonce,
                    columns: columns,
                    product_types: productTypes,
                    categories: categories,
                    export_custom_meta: exportCustomMeta,
                },
                success: (response) => {
                    console.log("AJAX response:", response)

                    if (response.success) {
                        showNotification(noticeContainer, "success", response.data.message)

                        // Download CSV file
                        downloadCSV(response.data.csv_data, response.data.filename)
                    } else {
                        console.error("Error response:", response)
                        let errorMessage = "An error occurred while generating the CSV file."

                        if (response.data && response.data.message) {
                            errorMessage = response.data.message
                            console.error("Error message:", errorMessage)
                        }

                        showNotification(noticeContainer, "error", errorMessage)
                    }
                },
                error: (xhr, status, error) => {
                    console.error("AJAX error details:")
                    console.error("Status:", status)
                    console.error("Error:", error)
                    console.error("Response:", xhr.responseText)

                    let errorMessage = "An error occurred while generating the CSV file."

                    try {
                        const response = JSON.parse(xhr.responseText)
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message
                        }
                    } catch (e) {
                        console.error("Error parsing response:", e)
                        errorMessage += " Server response: " + xhr.responseText
                    }

                    showNotification(noticeContainer, "error", errorMessage)
                },
                complete: () => {
                    submitButton.prop("disabled", false)
                    progressIndicator.hide()
                },
            })
        })

        // Function to download CSV file
        function downloadCSV(csvData, filename) {
            console.log("Downloading CSV file:", filename)

            const blob = new Blob([csvData], { type: "text/csv;charset=utf-8;" })

            // Create download link
            const link = document.createElement("a")
            const url = URL.createObjectURL(blob)

            link.setAttribute("href", url)
            link.setAttribute("download", filename)
            link.style.visibility = "hidden"

            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)

            console.log("CSV download initiated")
        }
    })
})(jQuery)
