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

/***/ "./src/auto-coupon/frontend.js":
/*!*************************************************!*\
  !*** ./src/auto-coupon/frontend.js + 1 modules ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("// ESM COMPAT FLAG\n__webpack_require__.r(__webpack_exports__);\n\n;// CONCATENATED MODULE: ./src/auto-coupon/block.json\nconst block_namespaceObject = JSON.parse('{\"$schema\":\"https://schemas.wp.org/trunk/block.json\",\"apiVersion\":3,\"name\":\"wt-sc-blocks/auto-coupon\",\"version\":\"1.0.0\",\"title\":\"Auto coupon\",\"category\":\"woocommerce\",\"keywords\":[\"Auto coupon\",\"WooCommerce\"],\"icon\":\"\",\"description\":\"Auto coupon related operations.\",\"parent\":[\"woocommerce/cart-items-block\"],\"attributes\":{\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":true}}},\"textdomain\":\"thetoppercentile-coupons\"}');\n;// CONCATENATED MODULE: ./src/auto-coupon/frontend.js\n\nconst {\n  registerCheckoutFilters,\n  extensionCartUpdate\n} = window.wc.blocksCheckout;\nconst {\n  jQuery\n} = window;\n\n// Declare some global variables.\nvar wbte_auto_coupon_close_remove_timeout = null;\nvar wbte_auto_coupon_quantity_click_timeout = null;\nvar wbte_auto_coupon_needs_refresh_timeout = null;\nvar wbte_auto_coupon_needs_refresh = false;\n\n// Set a flag to trigger an extra refresh\njQuery(document).ready(function () {\n  // On value change.\n  jQuery(document).on('input', '.wc-block-components-quantity-selector__input', function () {\n    clearTimeout(wbte_auto_coupon_quantity_click_timeout);\n    wbte_auto_coupon_quantity_click_timeout = setTimeout(function () {\n      wbte_auto_coupon_needs_refresh = true;\n    }, 1000);\n  });\n\n  // On plus button click.\n  jQuery(document).on('click', '.wc-block-components-quantity-selector__button--plus', function () {\n    clearTimeout(wbte_auto_coupon_quantity_click_timeout);\n    wbte_auto_coupon_quantity_click_timeout = setTimeout(function () {\n      wbte_auto_coupon_needs_refresh = true;\n    }, 1000);\n  });\n});\n\n/**\n *  Register checkout filter to remove auto coupon remove button.\n */\nconst updateDataToCart = (defaultValue, extensions, args) => {\n  // Take auto coupon list from args.\n  let applied_auto_coupon_list = args?.cart?.extensions?.wt_sc_blocks?.applied_auto_coupon_list;\n\n  // Set a timer to avoid multiple function call.\n  clearTimeout(wbte_auto_coupon_close_remove_timeout);\n  wbte_auto_coupon_close_remove_timeout = setTimeout(function () {\n    // Needs refresh, because quantity changed.\n    if (wbte_auto_coupon_needs_refresh) {\n      // Add a timer to avoid continuous multiple requests.\n      clearTimeout(wbte_auto_coupon_needs_refresh_timeout);\n      wbte_auto_coupon_needs_refresh_timeout = setTimeout(function () {\n        if (wbte_auto_coupon_needs_refresh) {\n          // Set a loader.\n          if (typeof window.wbte_sc_block_node === 'function') {\n            window.wbte_sc_block_node(jQuery('.wp-block-woocommerce-cart-order-summary-block'));\n          }\n\n          // Refresh the cart.\n          extensionCartUpdate({\n            namespace: 'wbte-sc-blocks-update-cart',\n            data: {}\n          });\n\n          // Remove the loader. Because sometimes below function will not work.\n          setTimeout(function () {\n            if (typeof window.wbte_sc_unblock_node === 'function') {\n              window.wbte_sc_unblock_node(jQuery('.wp-block-woocommerce-cart-order-summary-block'));\n            }\n          }, 1500);\n        }\n\n        // Reset the refresh required flag.\n        wbte_auto_coupon_needs_refresh = false;\n      }, 500);\n    }\n\n    // Remove the close button for auto coupons\n    if (Array.isArray(applied_auto_coupon_list)) {\n      applied_auto_coupon_list = applied_auto_coupon_list.map(String);\n      jQuery('.wc-block-components-totals-discount__coupon-list-item').each(function () {\n        var coupon_code = jQuery(this).find('.wc-block-components-chip__text').text().trim();\n\n        // This is an auto coupon.\n        if (applied_auto_coupon_list.includes(coupon_code)) {\n          jQuery(this).find('.wc-block-components-chip__remove').remove();\n        }\n\n        // Remove the loader.\n        if (typeof window.wbte_sc_unblock_node === 'function') {\n          window.wbte_sc_unblock_node(jQuery('.wp-block-woocommerce-cart-order-summary-block'));\n        }\n      });\n    }\n  }, 500);\n\n  // Return the default value, because we are not altering any value here.\n  return defaultValue;\n};\nregisterCheckoutFilters('wt-sc-blocks-auto-apply-update-cart-checkout', {\n  itemName: updateDataToCart\n});\n\n//# sourceURL=webpack://wt-sc-blocks/./src/auto-coupon/frontend.js_+_1_modules?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
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
/******/ 	__webpack_modules__["./src/auto-coupon/frontend.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;