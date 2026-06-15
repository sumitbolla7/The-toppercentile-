(function ($) {
    "use strict";

    function enhanceExamPlanCtas() {
        if (window.location.pathname.indexOf("/exam/") === -1) {
            return;
        }

        const $allButtons = $("a, button");

        const $buyButtons = $allButtons.filter(function () {
            const text = ($(this).text() || "").trim().toLowerCase();
            return text.indexOf("enroll now") !== -1 || text.indexOf("enrol now") !== -1 || text === "buy now";
        });

        const $viewButtons = $allButtons.filter(function () {
            const text = ($(this).text() || "").trim().toLowerCase();
            return text.indexOf("view details") !== -1;
        });

        $buyButtons.each(function () {
            const $btn = $(this);
            // Skip nav/menu links and only style card CTAs.
            if ($btn.closest("nav, header, .menu, .elementor-nav-menu").length) {
                return;
            }

            $btn
                .show()
                .text("Buy Now")
                .css({
                    background: "#101010",
                    color: "#ffffff",
                    borderColor: "#101010",
                    fontWeight: "700",
                    display: "block",
                    width: "100%",
                    textAlign: "center",
                    marginBottom: "10px"
                });
        });

        $viewButtons.each(function () {
            const $viewBtn = $(this);
            if ($viewBtn.closest("nav, header, .menu, .elementor-nav-menu").length) {
                return;
            }

            const $scope = $viewBtn.parent();
            const $buyBtnInScope = $scope.find("a, button").filter(function () {
                const text = ($(this).text() || "").trim().toLowerCase();
                return text === "buy now";
            }).first();

            if ($buyBtnInScope.length && !$buyBtnInScope.is($viewBtn)) {
                $buyBtnInScope.insertBefore($viewBtn);
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

    function isUserLoggedIn() {
        return parseInt(tpspData.isLoggedIn, 10) === 1 || $("body").hasClass("logged-in");
    }

    function forceExamEnrollLoginRedirect() {
        if (window.location.pathname.indexOf("/exam/") === -1) {
            return;
        }

        $(document).on("click", ".ttp-open-signup, .ttp-btn-enroll, .ttp-buy-now-btn", function (e) {
            const productId = parseInt($(this).attr("data-product-id") || 0, 10);
            if (!productId) {
                return;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            const checkoutWithProduct = tpspData.checkoutUrl + (tpspData.checkoutUrl.indexOf("?") === -1 ? "?" : "&") + "add-to-cart=" + productId;
            const isLoggedIn = isUserLoggedIn();
            if (!isLoggedIn) {
                const loginBase = tpspData.loginUrl || "/login/";
                const sep = loginBase.indexOf("?") === -1 ? "?" : "&";
                window.location.href = loginBase + sep + "redirect_to=" + encodeURIComponent(checkoutWithProduct);
                return;
            }
            window.location.href = checkoutWithProduct;
        });
    }

    function removeTopLoginHeading() {
        const path = (window.location.pathname || "").toLowerCase();
        if (path.indexOf("/login") === -1 && path.indexOf("/my-account") === -1) {
            return;
        }

        $("h1, h2").each(function () {
            const text = ($(this).text() || "").trim().toLowerCase();
            if (text === "login" || text === "log in") {
                $(this).hide();
            }
        });
    }

    function redirectGuestMyAccountToLogin() {
        const path = (window.location.pathname || "").toLowerCase();
        const isMyAccount = path.indexOf("/my-account") !== -1;
        const isLoggedIn = $("body").hasClass("logged-in");

        if (!isMyAccount || isLoggedIn) {
            return;
        }

        window.location.replace("/login/");
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

    function setCourseCardMsg(text, variant) {
        const $slot = $("#ttp-course-card-messages");
        if (!$slot.length) {
            return false;
        }

        $slot.removeClass("msg--error msg--success");
        if (!text) {
            $slot.empty();
            return true;
        }

        if (variant === "error") {
            $slot.addClass("msg--error");
        } else {
            $slot.addClass("msg--success");
        }

        $slot.text(text);
        return true;
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
        setCourseCardMsg("");

        $.ajax({
            type: "POST",
            url: tpspData.ajaxUrl,
            dataType: "json",
            data: {
                action: "tpsp_ajax_add_to_cart",
                nonce: tpspData.nonce,
                product_id: productId,
                quantity: quantity
            }
        })
            .done(function (data) {
                $(document.body).trigger("wc_fragment_refresh");

                if (data && data.success && data.data && data.data.alreadyInCart) {
                    var alreadyMsg =
                        typeof data.data.message === "string" && data.data.message
                            ? data.data.message
                            : "\u2713 Already added to cart";
                    var goCart = data.data.cartUrl || tpspData.cartUrl || "/cart/";
                    setCourseCardMsg(alreadyMsg, "success");
                    window.setTimeout(function () {
                        window.location.href = goCart;
                    }, 350);
                    return;
                }

                if (data && data.fragments) {
                    setCourseCardMsg(
                        tpspData.successText ? String(tpspData.successText) : "Added to cart successfully!",
                        "success"
                    );
                    return;
                }

                if (data && data.success && data.data && data.data.message) {
                    setCourseCardMsg(String(data.data.message), "success");
                }
            })
            .fail(function (xhr) {
                var responseData = xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : null;
                if (responseData && responseData.redirect && responseData.loginUrl) {
                    window.location.href = responseData.loginUrl;
                    return;
                }

                var msg =
                    responseData && responseData.message
                        ? responseData.message
                        : "Unable to add product.";
                if (!setCourseCardMsg(msg, "error")) {
                    alert(msg);
                }
            })
            .always(function () {
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

    // Improve exam-page CTA order and styling.
    enhanceExamPlanCtas();
    styleExamViewDetailsCtas();
    forceExamEnrollLoginRedirect();
    removeTopLoginHeading();
    redirectGuestMyAccountToLogin();

    // Cart / checkout navigation must stay native (checkout/?add-to-cart links, Proceed to checkout, etc.).
    $(document).on("updated_cart_totals updated_wc_div wc_fragments_loaded", function () {
        $(
            "a.checkout-button, button.checkout-button, .wc-proceed-to-checkout .checkout-button, .checkout-button.alt, .wc-proceed-to-checkout a, a.tpsp-proceed-checkout, #tpsp-proceed-checkout, .wc-block-cart__submit-container a"
        ).each(function () {
            const el = this;
            el.removeAttribute("disabled");
            el.removeAttribute("aria-disabled");
            $(el).prop("disabled", false).removeProp("disabled");
            el.style.pointerEvents = "";
            el.style.opacity = "";
            el.style.cursor = "";
        });
    });

    // Handle custom "buy now" links/buttons on product cards for guests — never intercept checkout URLs.
    $(document).on("click", "a[href*='buy-now'], a[href*='add-to-cart'], button[name='buy-now'], .ttp-buy-now-btn, .ttp-buy-now", function (e) {
        var path = (window.location.pathname || "").toLowerCase();
        // Cart / checkout: native WooCommerce navigation only (proceed, coupons, fragments).
        if (path.indexOf("/cart") !== -1 || path.indexOf("/checkout") !== -1) {
            return;
        }

        var href = (($(this).attr("href")) || "").toLowerCase();

        // Never hijack checkout / cart proceed links (coupon plugins reuse add-to-cart in URLs).
        if (href.indexOf("/checkout") !== -1) {
            return;
        }

        if (
            $(this).is(".checkout-button, button.checkout-button") ||
            $(this).closest(".woocommerce-cart, .cart-collaterals, .wc-proceed-to-checkout, .woocommerce-checkout").length
        ) {
            return;
        }

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

    });

    enforcePurchasedCourseSingleCta();
})(jQuery);
