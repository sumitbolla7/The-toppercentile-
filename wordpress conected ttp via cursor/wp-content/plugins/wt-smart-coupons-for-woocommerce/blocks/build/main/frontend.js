/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/main/frontend.js":
/*!******************************************!*\
  !*** ./src/main/frontend.js + 7 modules ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("// ESM COMPAT FLAG\n__webpack_require__.r(__webpack_exports__);\n\n// EXPORTS\n__webpack_require__.d(__webpack_exports__, {\n  WtScBlocksMain: () => (/* binding */ WtScBlocksMain)\n});\n\n;// CONCATENATED MODULE: ./src/main/block.json\nconst block_namespaceObject = JSON.parse('{\"$schema\":\"https://schemas.wp.org/trunk/block.json\",\"apiVersion\":3,\"name\":\"wt-sc-blocks/main\",\"version\":\"1.0.0\",\"title\":\"Smart coupon blocks\",\"category\":\"woocommerce\",\"parent\":[\"woocommerce/checkout-fields-block\",\"woocommerce/cart-items-block\"],\"attributes\":{\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":true}}},\"textdomain\":\"thetoppercentile-coupons\"}');\n;// CONCATENATED MODULE: external [\"wp\",\"data\"]\nconst external_wp_data_namespaceObject = window[\"wp\"][\"data\"];\n;// CONCATENATED MODULE: external [\"wp\",\"i18n\"]\nconst external_wp_i18n_namespaceObject = window[\"wp\"][\"i18n\"];\n;// CONCATENATED MODULE: external [\"wp\",\"htmlEntities\"]\nconst external_wp_htmlEntities_namespaceObject = window[\"wp\"][\"htmlEntities\"];\n;// CONCATENATED MODULE: external [\"wc\",\"wcBlocksData\"]\nconst external_wc_wcBlocksData_namespaceObject = window[\"wc\"][\"wcBlocksData\"];\n;// CONCATENATED MODULE: external [\"wp\",\"element\"]\nconst external_wp_element_namespaceObject = window[\"wp\"][\"element\"];\n;// CONCATENATED MODULE: external [\"wc\",\"blocksCheckout\"]\nconst external_wc_blocksCheckout_namespaceObject = window[\"wc\"][\"blocksCheckout\"];\n;// CONCATENATED MODULE: ./src/main/frontend.js\n\n\n\n\n\n\n\n\n// Global import\nconst {\n  registerCheckoutBlock,\n  registerCheckoutFilters\n} = wc.blocksCheckout;\nlet isClickBinded = false;\nconst WtScBlocksMain = () => {\n  const {\n    applyCoupon,\n    removeCoupon\n  } = (0,external_wp_data_namespaceObject.useDispatch)(external_wc_wcBlocksData_namespaceObject.CART_STORE_KEY);\n  const {\n    createErrorNotice,\n    createSuccessNotice\n  } = (0,external_wp_data_namespaceObject.useDispatch)('core/notices');\n  if (!isClickBinded) {\n    /* Set `true` for preventing multiple event binding */\n    isClickBinded = true;\n\n    /* Click event triggered by plugin public JS file */\n    document.addEventListener('wt_sc_api_coupon_clicked', function (e) {\n      const couponCode = e.detail.coupon_code;\n      const couponId = e.detail.coupon_id;\n      const context = 'wc/cart'; /* message context */\n\n      applyCoupon(couponCode).then(response => {\n        /* Trigger a custom event */\n        const coupon_click_done_event = new CustomEvent(\"wt_sc_api_coupon_click_done\", {\n          detail: {\n            'coupon_code': couponCode,\n            'coupon_id': couponId,\n            'status': true\n          }\n        });\n        document.dispatchEvent(coupon_click_done_event);\n\n        /* Show success message */\n        if ((0,external_wc_blocksCheckout_namespaceObject.applyCheckoutFilter)({\n          filterName: 'showApplyCouponNotice',\n          defaultValue: true,\n          arg: {\n            couponCode,\n            context\n          }\n        })) {\n          createSuccessNotice((0,external_wp_i18n_namespaceObject.sprintf)( /* translators: %s coupon code. */\n          (0,external_wp_i18n_namespaceObject.__)('Coupon code \"%s\" has been applied to your cart.', 'thetoppercentile-coupons'), couponCode), {\n            id: 'coupon-form',\n            type: 'snackbar',\n            context\n          });\n        }\n      }).catch(error => {\n        /* Trigger a custom event */\n        const coupon_click_done_event = new CustomEvent(\"wt_sc_api_coupon_click_done\", {\n          detail: {\n            'coupon_code': couponCode,\n            'coupon_id': couponId,\n            'status': false,\n            'message': error.message\n          }\n        });\n        document.dispatchEvent(coupon_click_done_event);\n\n        /** Decode HTML entities (e.g., &quot; → \") in error messages before displaying, as WooCommerce API responses may return encoded HTML in error.message */\n        const textarea = document.createElement('textarea');\n        textarea.innerHTML = error.message;\n\n        /* Show error message */\n        createErrorNotice(textarea.value, {\n          id: 'coupon-form',\n          type: 'snackbar',\n          context\n        });\n      });\n    });\n  }\n  return '';\n};\nconst options = {\n  metadata: block_namespaceObject,\n  component: WtScBlocksMain\n};\nregisterCheckoutBlock(options);\n\n/**\n * In WooCommerce, there is an option to set up specific payments only for specific shipping methods. For example, COD is only for 'free shipping'; if the user selected a shipping method other than 'free shipping,' the 'COD' will be removed, but it's not updating the session, which affects the coupon validation check.\n Another scenario is that WooCommerce has an option set to a specific shipping method only for a specific postal code or address. When there is a change in these shipping method changes, it can also affect a change in the payment method (as mentioned in the first scenario).\n The below code is that it will update the session whenever there is a change in the payment method. \n */\n\n/** Track previous payment method */\nlet prev_payment_method = null;\n\n/** Subscribe to payment method changes */\nwp.data.subscribe(() => {\n  const currentPaymentMethod = wp.data.select('wc/store/payment').getActivePaymentMethod();\n\n  /** Only proceed if payment method has changed */\n  if (currentPaymentMethod && prev_payment_method !== currentPaymentMethod) {\n    /** Update previous payment method */\n    prev_payment_method = currentPaymentMethod;\n\n    /** Update cart with new payment method */\n    wp.data.dispatch('wc/store/cart').applyExtensionCartUpdate({\n      namespace: 'wbte-sc-blocks-update-cart-payment-session',\n      data: {\n        payment_method: currentPaymentMethod\n      }\n    });\n  }\n});\n\n/**\n * The 'itemName' filter is used to edit the product name, but here we are adding coupon blocks HTML to the cart/checkout and returning the default value (product name).\n * \n * @since 2.2.1\n */\nconst addCouponBlocksHtml = (defaultValue, extensions, args) => {\n  jQuery(document).ready(function ($) {\n    $('.wbte_sc_block_coupon_wrapper_div').remove();\n    if ('undefined' !== typeof WTSmartCouponOBJ && \"1\" === WTSmartCouponOBJ?.is_cart) {\n      $('.wp-block-woocommerce-filled-cart-block').before(`<div class=\"wbte_sc_block_coupon_wrapper_div\">${args?.cart?.extensions?.wt_sc_blocks?.coupon_blocks_cart}</div>`);\n    } else {\n      $('.wp-block-woocommerce-checkout').before(`<div class=\"wbte_sc_block_coupon_wrapper_div\">${args?.cart?.extensions?.wt_sc_blocks?.coupon_blocks_checkout}</div>`);\n    }\n  });\n  return defaultValue;\n};\nregisterCheckoutFilters('wbte-sc-cart-checkout-coupon-blocks', {\n  itemName: addCouponBlocksHtml\n});\n\n//# sourceURL=webpack://wt-sc-blocks/./src/main/frontend.js_+_7_modules?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./src/main/frontend.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;