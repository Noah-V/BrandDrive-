/**
 * BrandDrive Admin JavaScript
 */
;(($) => {
    // Initialize admin functionality
    $(document).ready(() => {
        // Ensure ajaxurl and branddrive_params are defined
        if (typeof ajaxurl === "undefined" || typeof branddrive_params === "undefined") {
            console.error("ajaxurl or branddrive_params is not defined. Ensure it is properly enqueued.")
            return // Exit if critical variables are missing
        }

        // Paste plugin key from clipboard
        $("#branddrive_paste_key").on("click", function () {
            navigator.clipboard.readText().then(text => {
                $(".branddrive-plugin-key-input input").val(text);
                showNotification("Plugin key pasted from clipboard");
            });

            // Show copied icon
            var $button = $(this)
            var $icon = $button.find(".dashicons")
            $icon.removeClass("dashicons-clipboard").addClass("dashicons-yes")
            setTimeout(() => {
                $icon.removeClass("dashicons-yes").addClass("dashicons-clipboard")
            }, 2000)
        })

        // Verify plugin key
        $("#branddrive_verify_key").on("click", function () {
            var pluginKey = $("#branddrive_plugin_key").val()
            var resultContainer = $("#branddrive_key_verification_result")
            var spinner = $("#branddrive_key_spinner")

            if (!pluginKey) {
                showNotification("Please enter a plugin key", "error")
                return
            }

            // Show loading
            $(this).prop("disabled", true)
            spinner.show()
            // resultContainer.html("")

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "branddrive_verify_plugin_key",
                    plugin_key: pluginKey,
                    nonce: branddrive_params.nonce,
                },
                beforeSend: function (xhr) {
                    console.log("AJAX request is about to be sent");

                },
                success: (response) => {
                    console.log("AJAX success response:", JSON.stringify(response));
                    if (response.success) {
                        showNotification(response.data.message, "success")
                    } else {
                        showNotification(response.data.message, "error")
                    }
                },
                // error: () => {
                //   showNotification("Failed to verify plugin key", "error")
                // },
                // error: (jqXHR, textStatus, errorThrown) => {
                //   console.error("AJAX error:", textStatus, errorThrown);
                //   showNotification("Failed to verify plugin key: " + textStatus, "error");
                // },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("AJAX error details:");
                    console.error("Status:", jqXHR.status);
                    console.error("Status text:", textStatus);
                    console.error("Error thrown:", errorThrown);
                    console.error("Response text:", jqXHR.responseText);

                    let errorMsg = "Failed to verify plugin key";
                    if (jqXHR.responseText) {
                        try {
                            const jsonResponse = JSON.parse(jqXHR.responseText);
                            if (jsonResponse.message) {
                                errorMsg += ": " + jsonResponse.message;
                            }
                        } catch (e) {
                            errorMsg += ": " + textStatus;
                        }
                    }

                    showNotification(errorMsg, "error")
                },
                complete: () => {
                    $("#branddrive_verify_key").prop("disabled", false)
                    spinner.hide()
                },
            })
        })

        // Export all products
        // $("#branddrive_export_all_products, #branddrive_export_products").on("click", function () {
        //     var confirmExport = confirm(branddrive_params.i18n.confirm_export)
        //
        //     var allRows = $(".branddrive-checkbox").closest("tr");
        //     var hasExported = false
        //     var exportProducts = [];
        //
        //     if (!confirmExport) {
        //         return
        //     }
        //
        //     var exportButton = $(this)
        //     var progressContainer = $("#branddrive_export_progress")
        //
        //     // Show loading
        //     exportButton.prop("disabled", true)
        //     progressContainer.show()
        //
        //     // Send AJAX request
        //     $.ajax({
        //         url: branddrive_params.ajax_url,
        //         type: "POST",
        //         data: {
        //             action: "branddrive_export_products",
        //             nonce: branddrive_params.nonce,
        //         },
        //         beforeSend: function (xhr) {
        //             console.log("Export Product AJAX request is about to be sent");
        //         },
        //         success: (response, textStatus, jqXHR) => {
        //             console.log("Response", response);
        //             console.log("Status: ", textStatus);
        //             console.log("Status Code: ", jqXHR.status);
        //             console.log("Response Text: ", jqXHR.responseText);
        //             if (response.success) {
        //                 showNotification(response.data.message, "success")
        //             } else {
        //                 console.log("Response gotten but is not successful");
        //                 showNotification(response.data.message, "error")
        //             }
        //         },
        //         error: (jqXHR, textStatus, errorThrown) => {
        //             console.error("AJAX error details:");
        //             console.error("Status:", jqXHR.status);
        //             console.error("Status text:", textStatus);
        //             console.error("Error thrown:", errorThrown);
        //             console.error("Response text:", jqXHR.responseText);
        //             // showNotification($("#branddrive_export_notice"), "error")
        //             let errorMsg = "Failed to export product";
        //             if (jqXHR.responseText) {
        //                 try {
        //                     const jsonResponse = JSON.parse(jqXHR.responseText);
        //                     if (jsonResponse.message) {
        //                         errorMsg += ": " + jsonResponse.message;
        //                     }
        //                 } catch (e) {
        //                     errorMsg += ": " + textStatus;
        //                 }
        //             }
        //             showNotification(errorMsg, "error");
        //             // showNotification(branddrive_params.i18n.export_error, "error");
        //         },
        //         complete: () => {
        //             exportButton.prop("disabled", false)
        //             progressContainer.hide()
        //         },
        //     })
        // })

        $("#branddrive_export_all_products").on("click", function () {
            var confirmExport = confirm(branddrive_params.i18n.confirm_export)

            var productsToExport = [];
            var allProductsExported = true;

            $('tr[data-product-id]').each(function () {
                var productId = $(this).data('product-id');
                var isExported = $(this).find('.branddrive-status-badge').hasClass('exported');

                if(!isExported) {
                    productsToExport.push(productId);
                    allProductsExported = false;
                }
            })

            if(allProductsExported){
                showNotification("All Products have already been exported", "error")
                return;
            }

            if (!confirmExport) {
                return
            }

            var exportButton = $(this)
            var progressContainer = $("#branddrive_export_progress")

            // Show loading
            exportButton.prop("disabled", true)
            progressContainer.show()

            // Send AJAX request
            $.ajax({
                url: branddrive_params.ajax_url,
                type: "POST",
                data: {
                    action: "branddrive_export_products",
                    nonce: branddrive_params.nonce,
                    product_ids: productsToExport
                },
                success: (response, textStatus, jqXHR) => {
                    console.log("Response", response);
                    console.log("Status: ", textStatus);
                    console.log("Status Code: ", jqXHR.status);
                    console.log("Response Text: ", jqXHR.responseText);
                    const message = response?.data?.message || "";
                    if (response.success) {
                        showNotification(response.data.message, "success")
                        setTimeout(() => {
                            window.location.reload()
                        }, 2000)
                    } else if (message.includes("already exists")) {
                        showNotification(response.data.message, "error")
                    } else {
                        console.log("Response gotten but is not successful");
                        showNotification(response.data.message, "error")
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("AJAX error details:");
                    console.error("Status:", jqXHR.status);
                    console.error("Status text:", textStatus);
                    console.error("Error thrown:", errorThrown);
                    console.error("Response text:", jqXHR.responseText);
                    // showNotification($("#branddrive_export_notice"), "error")
                    let errorMsg = "Failed to export product";
                    if (jqXHR.responseText) {
                        try {
                            const jsonResponse = JSON.parse(jqXHR.responseText);
                            if (jsonResponse.message) {
                                errorMsg += ": " + jsonResponse.message;
                            }
                        } catch (e) {
                            errorMsg += ": " + textStatus;
                        }
                    }
                    showNotification(errorMsg, "error");
                },
                complete: () => {
                    exportButton.prop("disabled", false)
                    progressContainer.hide()
                },
            })
        })

        /**** Export Single Product ***/
        $(".branddrive-export-single-product").on("click", function () {
            var productId = $(this).data("product-id")
            var row = $(this).closest("tr")
            var button = $(this)

            var isExported = row.find(".branddrive-status-badge").hasClass("exported")

            if(isExported) {
                row.addClass("branddrive-error-row");
                showNotification("This product has already been exported", "error")
                setTimeout(() => {
                    window.location.reload()
                }, 2000)
                return;
            }

            // Show loading
            button.prop("disabled", true)
            button.text("Exporting...")

            // Send AJAX request
            $.ajax({
                url: branddrive_params.ajax_url,
                type: "POST",
                data: {
                    action: "branddrive_export_products",
                    nonce: branddrive_params.nonce,
                    product_ids: [productId],
                },
                beforeSend: function (xhr) {
                    console.log("Export Product AJAX request is about to be sent");
                },
                success: (response, textStatus, jqXHR) => {
                    console.log("Response", response);
                    console.log("Status: ", textStatus);
                    console.log("Status Code: ", jqXHR.status);
                    console.log("Response Text: ", jqXHR.responseText);
                    if (response.success) {
                        showNotification(response.data.message, "success")
                        // Update the row status
                        row.find(".branddrive-status-badge").removeClass("not-exported").addClass("exported").text("Exported")

                        // Update the button text
                        button.text("Re-export")

                        // Reload the page after a delay to get the updated BrandDrive ID
                        setTimeout(() => {
                            window.location.reload()
                        }, 2000)
                    } else {
                        showNotification(response.data.message, "error")
                        button.text("Export")
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    console.error("AJAX error details:");
                    console.error("Status:", jqXHR.status);
                    console.error("Status text:", textStatus);
                    console.error("Error thrown:", errorThrown);
                    console.error("Response text:", jqXHR.responseText);
                    // showNotification($("#branddrive_export_notice"), "error")
                    let errorMsg = "Failed to export product";
                    if (jqXHR.responseText) {
                        try {
                            const jsonResponse = JSON.parse(jqXHR.responseText);
                            if (jsonResponse.message) {
                                errorMsg += ": " + jsonResponse.message;
                            }
                        } catch (e) {
                            errorMsg += ": " + textStatus;
                        }
                    }
                    showNotification(errorMsg, "error");
                    button.text("Export")
                },
                complete: () => {
                    button.prop("disabled", false)
                },
            })
        })

        $("#branddrive_select_all_products").on("click", function () {
            $(".branddrive_product_checkbox").prop("checked", $(this).prop("checked"))
        })

        // Bulk action
        $("#branddrive_apply_bulk_action").on("click", function () {
            var action = $("#branddrive_bulk_action").val()


            if (action !== "export") {
                return
            }

            var selectedProducts = $(".branddrive_product_checkbox:checked")


            if (selectedProducts.length === 0) {
                showNotification("Please select at least one product to export", "error");
                return
            }

            var confirmExport = confirm(branddrive_params.i18n.confirm_export)

            if (!confirmExport) {
                return
            }

            var button = $(this)
            var productIds = []
            var hasExported = false;

            selectedProducts.each(function () {
                var checkbox = $(this);
                var row = checkbox.closest("tr");
                var isExported = row.find(".branddrive-status-badge").hasClass("exported")

                if (isExported) {
                    hasExported = true;
                    row.addClass("branddrive-error-row");
                } else {
                    productIds.push($(this).val())
                }
            })

            if(hasExported){
                showNotification("Please select a product that has not been exported", "error");
                setTimeout(() => {
                    window.location.reload()
                }, 2000)
                return;
            }

            // Show loading
            button.prop("disabled", true)
            button.text("Exporting...")

            // Send AJAX request
            $.ajax({
                url: branddrive_params.ajax_url,
                type: "POST",
                data: {
                    action: "branddrive_export_products",
                    nonce: branddrive_params.nonce,
                    product_ids: productIds,
                },
                beforeSend: function (xhr) {
                    console.log("Export Product AJAX request is about to be sent");
                },
                success: (response, textStatus, jqXHR) => {
                    console.log("Response", response);
                    console.log("Status: ", textStatus);
                    console.log("Status Code: ", jqXHR.status);
                    console.log("Response Text: ", jqXHR.responseText);
                    if (response.success) {
                        showNotification(response.data.message, "success")
                    } else {
                        console.log("Response gotten but is not successful");
                        showNotification(response.data.message, "error")
                    }
                },
                error: () => {
                    showNotification($("#branddrive_export_notice"), "error", branddrive_params.i18n.export_error)
                },
                complete: () => {
                    button.prop("disabled", false)
                    button.text("Apply")
                },
            })
        })

        // Sync orders
        $("#branddrive_sync_orders").on("click", function () {
            var button = $(this)

            // Show loading
            // button.prop("disabled", true)
            // button.html('<span class="spinner is-active"></span> Syncing...')

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "branddrive_sync_orders",
                    nonce: branddrive_params.nonce,
                },
                success: (response) => {
                    if (response.success) {
                        showNotification(response.data.message, "success")
                    } else {
                        showNotification(response.data.message, "error")
                    }
                },
                error: () => {
                    showNotification("Failed to sync orders", "error")
                },
                complete: () => {
                    button.prop("disabled", false)
                    button.html("Sync orders")
                },
            })
        })

        // Sync individual order
        $(".sync-order").on("click", function () {
            var button = $(this)
            var orderId = button.data("order-id")

            // Show loading
            button.prop("disabled", true)

            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: "POST",
                data: {
                    action: "branddrive_sync_single_order",
                    order_id: orderId,
                    nonce: branddrive_params.nonce,
                },
                success: (response) => {
                    console.log(response);
                    if (response.success) {
                        showNotification(response.data.message, "success")
                    } else {
                        showNotification(response.data.message, "error")
                    }
                },
                error: () => {
                    showNotification("Failed to sync order", "error")
                },
                complete: () => {
                    button.prop("disabled", false)
                },
            })
        })

        // Show notification
        function showNotification(message, type = "success") {
            // Remove existing notifications
            $(".branddrive-notification").remove()

            // Create notification with appropriate styling
            var notification = $(
                '<div class="branddrive-notification ' +
                type +
                '">' +
                '<span class="message" style="margin-right: 20px">' +
                message +
                "</span>" +
                '<span class="close dashicons dashicons-no-alt"></span>' +
                "</div>",
            )

            // Add to body
            $("body").append(notification)

            // Add close handler
            notification.find(".close").on("click", () => {
                notification.remove()
            })

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(function () {
                    $(this).remove()
                })
            }, 5000)
        }

        $(".branddrive-plugin-key-input input").focusin(() => {
            $(".branddrive-plugin-key-input").addClass("focus");
        });
        $(".branddrive-plugin-key-input input").focusout(() => {
            $(".branddrive-plugin-key-input").removeClass("focus");
        });
    })
})(jQuery)

