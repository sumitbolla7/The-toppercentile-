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

/***/ "./src/bogo/frontend.js":
/*!******************************************!*\
  !*** ./src/bogo/frontend.js + 3 modules ***!
  \******************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("// ESM COMPAT FLAG\n__webpack_require__.r(__webpack_exports__);\n\n;// CONCATENATED MODULE: external [\"wp\",\"data\"]\nconst external_wp_data_namespaceObject = window[\"wp\"][\"data\"];\n;// CONCATENATED MODULE: ./src/bogo/block.json\nconst block_namespaceObject = JSON.parse('{\"$schema\":\"https://schemas.wp.org/trunk/block.json\",\"apiVersion\":3,\"name\":\"wt-sc-blocks/bogo\",\"version\":\"1.0.0\",\"title\":\"BOGO\",\"category\":\"woocommerce\",\"keywords\":[\"Bogo\",\"WooCommerce\"],\"icon\":\"\",\"description\":\"BOGO coupon related operations.\",\"parent\":[\"woocommerce/cart-items-block\"],\"attributes\":{\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":true}}},\"textdomain\":\"thetoppercentile-coupons\"}');\n;// CONCATENATED MODULE: external [\"wp\",\"element\"]\nconst external_wp_element_namespaceObject = window[\"wp\"][\"element\"];\n;// CONCATENATED MODULE: ./src/bogo/frontend.js\n\n\n\nconst {\n  registerCheckoutFilters,\n  extensionCartUpdate,\n  registerCheckoutBlock\n} = window.wc.blocksCheckout;\n\n// Declare some global variables.\nvar wbte_isFirefox = typeof InstallTrigger !== 'undefined';\nvar wbte_giveaway_products_timeout = null;\nvar wbte_cart_obj = null;\nvar auto_bogo_coupons = [];\n\n// Global variables for changing auto BOGO coupon code to title.\nvar wbte_sc_bogo_title_change_timeout = null;\nconst modifyItemName = (defaultValue, extensions, args) => {\n  wbte_cart_obj = args?.cart;\n  const giveawayText = args?.cart?.extensions?.wt_sc_blocks?.cartitem_bogo_text;\n  const cartItemKey = args?.cartItem?.key;\n  if (cartItemKey && giveawayText && giveawayText[cartItemKey]) {\n    // Update quantity limits\n    args.cartItem.quantity_limits.maximum = args.cartItem.quantity;\n    args.cartItem.quantity_limits.minimum = args.cartItem.quantity;\n\n    // Modify the defaultValue (item name) by adding giveaway text\n    defaultValue += giveawayText[cartItemKey];\n  }\n  jQuery(document).ready(function ($) {\n    /** Giveaway msg is added as a sibling of the product name, so remove the text-decoration from the product name */\n    $('.wc-block-components-product-name').has('.wt_sc_bogo_cart_item_discount').css('text-decoration', 'none');\n  });\n  return defaultValue;\n};\nregisterCheckoutFilters('wt-sc-bogo-blocks-update-cart', {\n  itemName: modifyItemName\n});\ndocument.addEventListener('wbte_sc_checkout_value_updated', function (e) {\n  extensionCartUpdate({\n    namespace: 'wbte-sc-blocks-update-checkout',\n    data: {}\n  });\n});\n\n// Webkit browsers (other than Firefox) requires an extra refresh to show the giveaway products.\nif ('undefined' !== typeof WTSmartCouponOBJ && !wbte_isFirefox && \"1\" === WTSmartCouponOBJ.is_cart) {\n  setTimeout(function () {\n    if (wbte_cart_obj) {\n      let html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;\n      let temp_elm = document.createElement(\"div\");\n      temp_elm.innerHTML = html;\n      let text = temp_elm.textContent || temp_elm.innerText || \"\";\n\n      // Only do the refresh when giveaway product HTML exists.\n      if (text.trim()) {\n        extensionCartUpdate({\n          namespace: 'wbte-sc-blocks-update-checkout',\n          data: {}\n        });\n      }\n    }\n  }, 100);\n}\n\n/** \n *  BOGO products block\n */\nconst addBogoProductHtml = (defaultValue, extensions, args) => {\n  jQuery(document).ready(function ($) {\n    const bogo_products_html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;\n    $('.wbte_sc_block_bogo_products_wrapper_div').remove();\n    if (bogo_products_html) {\n      $('.wp-block-woocommerce-cart-items-block').append('<div class=\"wbte_sc_block_bogo_products_wrapper_div\">' + bogo_products_html + '</div>');\n    }\n  });\n  return defaultValue;\n};\nregisterCheckoutFilters('wbte-sc-cart-bogo-product-html', {\n  itemName: addBogoProductHtml\n});\nconst hideApplyRemoveCouponNotice = (defaultValue, extensions, args) => {\n  const bogoCouponCodes = auto_bogo_coupons ? Object.keys(auto_bogo_coupons) : [];\n  if (bogoCouponCodes.includes(args?.couponCode)) {\n    /** Hiding notice because, notice contain coupon code which is not required for BOGO coupons. */\n    return false;\n  }\n  return defaultValue;\n};\n\n/**\n * Hide remove bogo coupon notice for BOGO coupons.\n */\nregisterCheckoutFilters('wt-sc-bogo-blocks-disable-remove-notice', {\n  showRemoveCouponNotice: hideApplyRemoveCouponNotice\n});\n\n/**\n * Hide apply bogo coupon notice for BOGO coupons.\n */\nregisterCheckoutFilters('wt-sc-bogo-blocks-disable-apply-notice', {\n  showApplyCouponNotice: hideApplyRemoveCouponNotice\n});\n\n/**\n * BOGO offer title instead of coupon code for auto BOGO coupons.\n */\nconst modifyCartItemClass = (defaultValue, extensions, args) => {\n  let auto_bogo_coupons = args?.cart?.extensions?.wt_sc_blocks?.auto_bogo_coupons;\n  clearTimeout(wbte_sc_bogo_title_change_timeout);\n  wbte_sc_bogo_title_change_timeout = setTimeout(function () {\n    // Change the coupon code to title for BOGO coupons.\n    if ('object' === typeof auto_bogo_coupons) {\n      jQuery('.wc-block-components-totals-discount__coupon-list-item').each(function () {\n        var coupon_code = jQuery(this).find('.wc-block-components-chip__text').text().trim();\n        if ('undefined' !== typeof auto_bogo_coupons[coupon_code]) {\n          jQuery(this).find('.wc-block-components-chip__text').text(auto_bogo_coupons[coupon_code]);\n        }\n      });\n    }\n  }, 500);\n\n  // Return the default value, because we are not altering any value here.\n  return defaultValue;\n};\nregisterCheckoutFilters('wt-sc-bogo-blocks-code-alter', {\n  cartItemClass: modifyCartItemClass\n});\n\n//# sourceURL=webpack://wt-sc-blocks/./src/bogo/frontend.js_+_3_modules?");

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
/******/ 	__webpack_modules__["./src/bogo/frontend.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;