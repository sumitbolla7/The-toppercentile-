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

/***/ "./src/giveaway-product/frontend.js":
/*!******************************************************!*\
  !*** ./src/giveaway-product/frontend.js + 1 modules ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("// ESM COMPAT FLAG\n__webpack_require__.r(__webpack_exports__);\n\n;// CONCATENATED MODULE: external [\"wp\",\"data\"]\nconst external_wp_data_namespaceObject = window[\"wp\"][\"data\"];\n;// CONCATENATED MODULE: ./src/giveaway-product/frontend.js\n\nconst {\n  registerCheckoutFilters,\n  extensionCartUpdate\n} = window.wc.blocksCheckout;\n\n// Declare some global variables.\nvar wbte_giveaway_eligible_message_timeout = null;\nvar wbte_giveaway_products_timeout = null;\nvar wbte_cart_obj = null;\nvar wbte_isFirefox = typeof InstallTrigger !== 'undefined';\n\n/**\n *  Register checkout filter to alter cart and show notices\n */\nconst updateDataToCart = (defaultValue, extensions, args) => {\n  wbte_cart_obj = args?.cart;\n\n  // Add giveaway item text to cart item.\n  const cartitem_giveaway_text = args?.cart?.extensions?.wt_sc_blocks?.cartitem_giveaway_text;\n  const cart_item_key = args?.cartItem?.key;\n  if (cart_item_key && cartitem_giveaway_text && cartitem_giveaway_text[cart_item_key]) {\n    args.cartItem.quantity_limits.maximum = args.cartItem.quantity;\n    args.cartItem.quantity_limits.minimum = args.cartItem.quantity;\n    defaultValue += cartitem_giveaway_text[cart_item_key];\n  }\n  jQuery(document).ready(function ($) {\n    // Remove the text-decoration from the product name for cheap/expensive BOGO giveaway products.\n    $('.wc-block-components-product-name').has('.wt_sc_giveaway_cart_item_text').css('text-decoration', 'none');\n  });\n\n  //Show giveaway available message.\n  const {\n    createInfoNotice,\n    removeNotice,\n    removeAllNotices\n  } = (0,external_wp_data_namespaceObject.useDispatch)('core/notices');\n  const context = 'wc/cart';\n  const msg_id = 'wbte-giveaway-eligible-msg';\n  const giveaway_eligible_message = args?.cart?.extensions?.wt_sc_blocks?.giveaway_eligible_message;\n  clearTimeout(wbte_giveaway_eligible_message_timeout);\n  wbte_giveaway_eligible_message_timeout = setTimeout(function () {\n    if (giveaway_eligible_message) {\n      createInfoNotice(giveaway_eligible_message, {\n        id: msg_id,\n        type: 'default',\n        isDismissible: false,\n        context\n      });\n    } else {\n      removeNotice(msg_id, context);\n    }\n  }, 10);\n  return defaultValue;\n};\nregisterCheckoutFilters('wt-sc-blocks-update-cart', {\n  itemName: updateDataToCart\n});\nconst modifyCartItemClass = (defaultValue, extensions, args) => {\n  // Add custom CSS class to giveaway cart item.\n  const cartitem_giveaway_text = args?.cart?.extensions?.wt_sc_blocks?.cartitem_giveaway_text;\n  const cart_item_key = args?.cartItem?.key;\n  if (cart_item_key && cartitem_giveaway_text && cartitem_giveaway_text[cart_item_key]) {\n    return 'wbte-giveaway-cart-item';\n  }\n  return defaultValue;\n};\nregisterCheckoutFilters('wt-sc-blocks-modify-cart-item-class', {\n  cartItemClass: modifyCartItemClass\n});\ndocument.addEventListener('wbte_sc_checkout_value_updated', function (e) {\n  extensionCartUpdate({\n    namespace: 'wbte-sc-blocks-update-checkout',\n    data: {}\n  });\n});\n\n// Webkit browsers (other than Firefox) requires an extra refresh to show the giveaway products\nif (!wbte_isFirefox && \"1\" === WTSmartCouponOBJ.is_cart) {\n  setTimeout(function () {\n    if (wbte_cart_obj) {\n      let html = wbte_cart_obj?.extensions?.wt_sc_blocks?.giveaway_products_html;\n      let temp_elm = document.createElement(\"div\");\n      temp_elm.innerHTML = html;\n      let text = temp_elm.textContent || temp_elm.innerText || \"\";\n\n      // Only do the refresh when giveaway product HTML exists.\n      if (text.trim()) {\n        extensionCartUpdate({\n          namespace: 'wbte-sc-blocks-update-checkout',\n          data: {}\n        });\n      }\n    }\n  }, 100);\n}\n\n/** \n *  Giveaway products block\n */\nconst addGiveawayProductHtml = (defaultValue, extensions, args) => {\n  jQuery(document).ready(function ($) {\n    const giveaway_products_html = wbte_cart_obj?.extensions?.wt_sc_blocks?.giveaway_products_html;\n    const isCart = '1' === WTSmartCouponOBJ?.is_cart;\n    $('.wbte_sc_block_giveaway_products_wrapper_div').remove();\n    if (giveaway_products_html && isCart) {\n      $('.wp-block-woocommerce-cart-items-block').append('<div class=\"wbte_sc_block_giveaway_products_wrapper_div\">' + giveaway_products_html + '</div>');\n    }\n  });\n  return defaultValue;\n};\nregisterCheckoutFilters('wbte-sc-cart-giveaway-product-html', {\n  itemName: addGiveawayProductHtml\n});\n\n//# sourceURL=webpack://wt-sc-blocks/./src/giveaway-product/frontend.js_+_1_modules?");

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
/******/ 	__webpack_modules__["./src/giveaway-product/frontend.js"](0, __webpack_exports__, __webpack_require__);
/******/ 	
/******/ })()
;