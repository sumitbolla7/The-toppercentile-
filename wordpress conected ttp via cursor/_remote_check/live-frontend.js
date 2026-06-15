(function ($) {
    "use strict";

    function removeExamEnrollNowCtas() {
        if (window.location.pathname.indexOf("/exam/") === -1) {
            return;
        }

        $("a, button").each(function () {
            const text = ($(this).text() || "").trim().toLowerCase();
            if (text.indexOf("enroll now") !== -1 || text.indexOf("enrol now") !== -1) {
                $(this).hide();
            }
        });
    }

    function styleExamViewDetailsCtas() {
        if (window.location.pathname.indexOf("/exam/") === -1) {
            return;
        }

        $("a, button").each(function () {
            const text = ($(this).text() || "").trim().toLowerCase();
            if (text.indexOf("view details") !== -1) {
                $(this).css({
                    background: "#ffd21f",
                    color: "#101010",
                    borderColor: "#ffd21f",
                    fontWeight: "700"
                });
            }
        });
    }

    function enforcePurchasedCourseSingleCta() {
        if (!parseInt(tpspData.isSingleProduct, 10)) {
            return;
        }

        const $form = $("form.cart");
        if (!$form.length) {
            return;
        }

        const productId = parseInt($form.find('input[name="add-to-cart"]').val() || $form.find(".single_add_to_cart_button").val() || 0, 10);
        const purchasedIds = Array.isArray(tpspData.purchasedProductIds) ? tpspData.purchasedProductIds.map(function (id) { return parseInt(id, 10); }) : [];
        const purchasedNames = Array.isArray(tpspData.purchasedCourseNames) ? tpspData.purchasedCourseNames.map(function (name) { return (name || "").trim().toLowerCase(); }) : [];
        const currentName = (tpspData.currentProductName || "").trim().toLowerCase();
        const isPurchasedByName = currentName && purchasedNames.indexOf(currentName) !== -1;
        const isPurchased = parseInt(tpspData.isPurchasedCourse, 10) || (productId > 0 && purchasedIds.indexOf(productId) !== -1) || isPurchasedByName;
        if (!isPurchased) {
            return;
        }

        $form.find(".single_add_to_cart_button, .ttp-buy-now-btn, .ttp-buy-now, button[name='buy-now']").remove();

        if (!$form.find(".tpsp-view-courses-btn").length) {
            const $btn = $('<a class="button alt tpsp-view-courses-btn"></a>')
                .attr("href", tpspData.myCoursesUrl)
                .text("View Courses");
            $form.append($btn);
        }
    }

    // AJAX add-to-cart only on WooCommerce single product page.
    $(document).on("submit", ".single-product form.cart", function (e) {
        const $form = $(this);
        const $btn = $form.find("button.single_add_to_cart_button");

        if (!$btn.length || $form.find('input[name="register_email"], input[name="username"], input[name="email"]').length) {
            return;
        }

        if (!parseInt(tpspData.isLoggedIn, 10)) {
            e.preventDefault();
            window.location.href = tpspData.loginUrl;
            return;
        }

        e.preventDefault();

        const productId = parseInt($btn.val() || $form.find('input[name="add-to-cart"]').val(), 10);
        if (!productId) {
            return;
        }
        const quantity = parseInt($form.find('input.qty').val() || 1, 10);
        const spinner = $('<span class="tpsp-spinner"></span>');

        $btn.prop("disabled", true).after(spinner);
        spinner.show();

        $.ajax({
            type: "POST",
            url: tpspData.ajaxUrl,
            data: {
                action: "tpsp_ajax_add_to_cart",
                nonce: tpspData.nonce,
                product_id: productId,
                quantity: quantity
            }
        }).done(function () {
            $(document.body).trigger("wc_fragment_refresh");
        }).fail(function (xhr) {
            const responseData = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : null;
            if (responseData && responseData.redirect && responseData.loginUrl) {
                window.location.href = responseData.loginUrl;
                return;
            }

            const msg = responseData && responseData.message ? responseData.message : "Unable to add product.";
            // Keep native WooCommerce notice area if available, without fixed popup.
            const $notice = $('<div class="woocommerce-error" role="alert"></div>').text(msg);
            const $wrapper = $form.closest(".product");
            if ($wrapper.length) {
                $wrapper.find(".woocommerce-error, .woocommerce-message").first().remove();
                $wrapper.prepend($notice);
            }
        }).always(function () {
            $btn.prop("disabled", false);
            spinner.remove();
        });
    });

    // Resend verification mail.
    $(document).on("click", "#tpsp-resend-verification", function () {
        const email = $("#tpsp-resend-email").val();
        const $msg = $("#tpsp-resend-message");
        const $btn = $(this);

        $btn.prop("disabled", true);
        $msg.text("");

        $.ajax({
            type: "POST",
            url: tpspData.ajaxUrl,
            data: {
                action: "tpsp_resend_verification",
                nonce: tpspData.nonce,
                email: email
            }
        }).done(function (res) {
            $msg.text(res.data.message).css("color", "#1d7f44");
        }).fail(function (xhr) {
            const msg = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : "Request failed.";
            $msg.text(msg).css("color", "#ac1e1e");
        }).always(function () {
            $btn.prop("disabled", false);
        });
    });

    // Better quantity controls in cart.
    $(document).on("click", ".tpsp-qty-minus, .tpsp-qty-plus", function () {
        const input = $(this).siblings("input.qty");
        if (!input.length) {
            return;
        }

        let value = parseInt(input.val() || 1, 10);
        const min = parseInt(input.attr("min") || 1, 10);
        const max = parseInt(input.attr("max") || 999, 10);

        if ($(this).hasClass("tpsp-qty-plus")) {
            value = Math.min(value + 1, max);
        } else {
            value = Math.max(value - 1, min);
        }

        input.val(value).trigger("change");
    });

    // Remove exam-page enroll-now CTAs per site requirement.
    removeExamEnrollNowCtas();
    styleExamViewDetailsCtas();

    // Handle custom "buy now" links/buttons on product cards for guests.
    $(document).on("click", "a[href*='buy-now'], a[href*='add-to-cart'], button[name='buy-now'], .ttp-buy-now-btn, .ttp-buy-now", function (e) {
        const $form = $("form.cart");
        const productId = parseInt($form.find('input[name="add-to-cart"]').val() || $form.find(".single_add_to_cart_button").val() || 0, 10);
        const purchasedIds = Array.isArray(tpspData.purchasedProductIds) ? tpspData.purchasedProductIds.map(function (id) { return parseInt(id, 10); }) : [];
        const purchasedNames = Array.isArray(tpspData.purchasedCourseNames) ? tpspData.purchasedCourseNames.map(function (name) { return (name || "").trim().toLowerCase(); }) : [];
        const currentName = (tpspData.currentProductName || "").trim().toLowerCase();
        const isPurchasedByName = currentName && purchasedNames.indexOf(currentName) !== -1;
        const isPurchased = parseInt(tpspData.isSingleProduct, 10) && (parseInt(tpspData.isPurchasedCourse, 10) || (productId > 0 && purchasedIds.indexOf(productId) !== -1) || isPurchasedByName);

        if (isPurchased) {
            e.preventDefault();
            e.stopImmediatePropagation();
            window.location.href = tpspData.myCoursesUrl;
            return;
        }

        if (!parseInt(tpspData.isLoggedIn, 10)) {
            e.preventDefault();
            e.stopImmediatePropagation();
            window.location.href = tpspData.loginUrl;
        }
    });

    // Force cart checkout button destination.
    $(document).on("click", ".woocommerce-cart .checkout-button", function (e) {
        e.preventDefault();
        window.location.href = tpspData.checkoutUrl;
    });

    enforcePurchasedCourseSingleCta();
})(jQuery);
