import { useDispatch } from '@wordpress/data';
import metadata from './block.json';
import { useState } from '@wordpress/element';

const { registerCheckoutFilters, extensionCartUpdate, registerCheckoutBlock } = window.wc.blocksCheckout;

// Declare some global variables.
var wbte_isFirefox = typeof InstallTrigger !== 'undefined';
var wbte_giveaway_products_timeout = null;
var wbte_cart_obj = null;
var auto_bogo_coupons  = [];

// Global variables for changing auto BOGO coupon code to title.
var wbte_sc_bogo_title_change_timeout = null;

const modifyItemName = ( defaultValue, extensions, args ) => {

    wbte_cart_obj = args?.cart;
    
    const giveawayText = args?.cart?.extensions?.wt_sc_blocks?.cartitem_bogo_text;

    const cartItemKey = args?.cartItem?.key;

    if ( cartItemKey && giveawayText && giveawayText[ cartItemKey ] ) {
        // Update quantity limits
        args.cartItem.quantity_limits.maximum = args.cartItem.quantity;
        args.cartItem.quantity_limits.minimum = args.cartItem.quantity;

        // Modify the defaultValue (item name) by adding giveaway text
        defaultValue += giveawayText[ cartItemKey ];

    }

    jQuery(document).ready(function($) {

        /** Giveaway msg is added as a sibling of the product name, so remove the text-decoration from the product name */
        $('.wc-block-components-product-name').has('.wt_sc_bogo_cart_item_discount').css('text-decoration', 'none');
    });

    return defaultValue;
};

registerCheckoutFilters('wt-sc-bogo-blocks-update-cart', {
    itemName: modifyItemName,
});

document.addEventListener('wbte_sc_checkout_value_updated', function(e){ 
    extensionCartUpdate( {
        namespace: 'wbte-sc-blocks-update-checkout',
        data: {},
    } );
});

// Webkit browsers (other than Firefox) requires an extra refresh to show the giveaway products.
if ( 'undefined' !== typeof WTSmartCouponOBJ && ! wbte_isFirefox && "1" === WTSmartCouponOBJ.is_cart ) {
    
    setTimeout(function(){      
        if ( wbte_cart_obj ) {

            let html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;
            let temp_elm = document.createElement("div");
            temp_elm.innerHTML = html;
            let text = temp_elm.textContent || temp_elm.innerText || "";

            // Only do the refresh when giveaway product HTML exists.
            if ( text.trim() ) { 
                extensionCartUpdate( {
                    namespace: 'wbte-sc-blocks-update-checkout',
                    data: {},
                } );
            }
        }
    }, 100);
}

/** 
 *  BOGO products block
 */
const addBogoProductHtml = ( defaultValue, extensions, args ) => {

    jQuery( document ).ready( function( $ ) {
        const bogo_products_html = wbte_cart_obj?.extensions?.wt_sc_blocks?.bogo_products_html;
        $( '.wbte_sc_block_bogo_products_wrapper_div' ).remove();
        if ( bogo_products_html ) {
            $( '.wp-block-woocommerce-cart-items-block' ).append(
                '<div class="wbte_sc_block_bogo_products_wrapper_div">' + bogo_products_html + '</div>'
            );
        }
    });

    return defaultValue;
};

registerCheckoutFilters('wbte-sc-cart-bogo-product-html', {
    itemName: addBogoProductHtml,
});

const hideApplyRemoveCouponNotice = ( defaultValue, extensions, args ) => {

    const bogoCouponCodes = auto_bogo_coupons ? Object.keys( auto_bogo_coupons ) : [];

    if ( bogoCouponCodes.includes( args?.couponCode ) ) {

        /** Hiding notice because, notice contain coupon code which is not required for BOGO coupons. */
        return false;
    }

    return defaultValue;
};

/**
 * Hide remove bogo coupon notice for BOGO coupons.
 */
registerCheckoutFilters( 'wt-sc-bogo-blocks-disable-remove-notice', {
    showRemoveCouponNotice: hideApplyRemoveCouponNotice,
} );

/**
 * Hide apply bogo coupon notice for BOGO coupons.
 */
registerCheckoutFilters( 'wt-sc-bogo-blocks-disable-apply-notice', {
    showApplyCouponNotice: hideApplyRemoveCouponNotice,
} );


/**
 * BOGO offer title instead of coupon code for auto BOGO coupons.
 */
const modifyCartItemClass = ( defaultValue, extensions, args ) => {
    
    let auto_bogo_coupons = args?.cart?.extensions?.wt_sc_blocks?.auto_bogo_coupons;

    clearTimeout( wbte_sc_bogo_title_change_timeout );
    wbte_sc_bogo_title_change_timeout = setTimeout( function() {
        
        // Change the coupon code to title for BOGO coupons.
        if( 'object' === typeof auto_bogo_coupons ){
            jQuery('.wc-block-components-totals-discount__coupon-list-item').each( function() {
                var coupon_code = jQuery(this).find('.wc-block-components-chip__text').text().trim();
                if( 'undefined' !== typeof auto_bogo_coupons[coupon_code] ){
                    jQuery(this).find('.wc-block-components-chip__text').text(auto_bogo_coupons[coupon_code]);
                }
            });
        }
        
    }, 500 );

    // Return the default value, because we are not altering any value here.
    return defaultValue;
};

registerCheckoutFilters( 'wt-sc-bogo-blocks-code-alter', {
    cartItemClass: modifyCartItemClass,
} );
