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

    // Copy plugin key
    $("#branddrive_copy_key").on("click", function () {
      var pluginKey = $("#branddrive_plugin_key").val()
      if (!pluginKey) return

      navigator.clipboard.writeText(pluginKey).then(() => {
        showNotification("Plugin key copied to clipboard")
      })

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

      if (!pluginKey) {
        showNotification("Please enter a plugin key", "error")
        return
      }

      // Show loading
      $(this).prop("disabled", true)

      // Send AJAX request
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "branddrive_verify_plugin_key",
          plugin_key: pluginKey,
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
          showNotification("Failed to verify plugin key", "error")
        },
        complete: () => {
          $("#branddrive_verify_key").prop("disabled", false)
        },
      })
    })

    // Export products
    $("#branddrive_export_products").on("click", function () {
      var button = $(this)

      // Show loading
      button.prop("disabled", true)
      button.html('<span class="spinner is-active"></span> Exporting...')

      // Send AJAX request
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "branddrive_export_products",
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
          showNotification("Failed to export products", "error")
        },
        complete: () => {
          button.prop("disabled", false)
          button.html("Export products/services")
        },
      })
    })

    // Sync orders
    $("#branddrive_sync_orders").on("click", function () {
      var button = $(this)

      // Show loading
      button.prop("disabled", true)
      button.html('<span class="spinner is-active"></span> Syncing...')

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

      // Create notification
      var notification = $(
        '<div class="branddrive-notification ' +
          type +
          '">' +
          '<span class="message">' +
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
  })
})(jQuery)

