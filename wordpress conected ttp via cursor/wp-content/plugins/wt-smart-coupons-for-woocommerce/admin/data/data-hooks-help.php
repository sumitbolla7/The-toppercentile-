<?php
/**
 *  Hooks and example array
 *
 *  @since 1.5.2
 * @package Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$wbte_hooks_category_labels     = array(
	'coupon_style'   => __( 'Coupon style', 'thetoppercentile-coupons' ),
	'bogo'           => __( 'BOGO', 'thetoppercentile-coupons' ),
	'coupon_clone'   => __( 'Coupon clone', 'thetoppercentile-coupons' ),
	'coupon_message' => __( 'Coupon message', 'thetoppercentile-coupons' ),
	'my_account'     => __( 'My account', 'thetoppercentile-coupons' ),
	'checkout'       => __( 'Checkout page', 'thetoppercentile-coupons' ),
	'others'         => __( 'Others', 'thetoppercentile-coupons' ),
);
$wbte_filters_help_doc_lists = array(
	// Coupon style.
	'coupon_style'   => array(
		'wt_sc_alter_coupon_template_html'           => array(
			'title'       => __( 'Alter coupon template HTML.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the coupon block HTML before printing.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_coupon_template_html", "wt_sc_customize_coupon_html", 10, 4 );
function wt_sc_customize_coupon_html( $html, $coupon_style, $coupon_type, $coupon ) {

	return \'<div class="wt_sc_single_coupon wt-single-coupon [wt_sc_single_coupon_class]" data-id="[wt_sc_coupon_id]" title="[wt_sc_single_coupon_title]">
    			<div class="wt_sc_coupon_content wt-coupon-content">
			        <div class="wt-coupon-amount">
			            <span class="wt_sc_coupon_amount amount">[wt_sc_coupon_amount]</span>
			        </div>
			        <div class="wt_sc_coupon_code wt-coupon-code"> 
			            <code>[wt_sc_coupon_code]</code>
			        </div>
    			</div>
		</div>\';
}',
		),
		'wt_sc_alter_coupon_html_placeholder_values' => array(
			'title'       => __( 'Add value to custom placeholder.', 'thetoppercentile-coupons' ),
			'description' => __( 'If you want to add any custom dynamic values to the coupon block. Add a placeholder to the coupon template first and assign the value to that placeholder via this filter.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_coupon_html_placeholder_values", "wt_sc_add_value_to_custom_placeholder" );
function wt_sc_add_value_to_custom_placeholder( $find_replace, $coupon, $coupon_type ) {

	// You have to add the [wt_sc_custom_placeholder] in the coupon template first.
	$find_replace["[wt_sc_custom_placeholder]"] = $coupon->get_code();

	return $find_replace;
}',
		),
		'wt_sc_alter_coupon_default_css'             => array(
			'title'       => __( 'Alter the coupon default style.', 'thetoppercentile-coupons' ),
			'description' => __( 'If you want to customize the style, you can use this filter.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_coupon_default_css", "wt_sc_change_coupon_bg" );
function wt_sc_change_coupon_bg( $coupon_css ) {

	$coupon_css .= ".wt_sc_single_coupon{ background:red !important; }"; // change the background color to red.
	return $coupon_css;
}',
		),
		'wt_sc_alter_coupon_title_text'              => array(
			'title'       => __( 'Alter the coupon title.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the coupon title in the coupon block.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_coupon_title_text", "wt_sc_alter_coupon_title_text", 10, 2 );
function wt_sc_alter_coupon_title_text( $label, $coupon ) {
	
	return $coupon->is_type( "wt_sc_bogo" ) ? __( "Buy one get one", "thetoppercentile-coupons" ) : $label; // Change the label in the coupon block when the coupon type is BOGO. 
}',
		),
		'wt_sc_alter_coupon_start_expiry_date_text'  => array(
			'title'       => __( 'Change the text of the coupon\'s start and expire dates.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default, "Start on" and "Expires on" will be the text if a coupon has a starting date and an expiration date; it can be changed by this hook.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_coupon_start_expiry_date_text", "wt_sc_change_coupon_start_expiry_date_text", 10, 3 );

function wt_sc_change_coupon_start_expiry_date_text( $date_text, $date, $type ) {
	
	return ( "start_date" === $type ? __( "Coupon starts on ", "thetoppercentile-coupons" ) : __( "Coupon expires on ", "thetoppercentile-coupons" ) ) . esc_html( date_i18n( get_option( "date_format", "F j, Y" ), $date ) );
}',
		),
	),

	// BOGO.
	'bogo'           => array(
		'wt_sc_alter_giveaway_product_price'               => array(
			'title'       => __( 'Alter the giveaway product price.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the giveaway product price. The code below will change the giveaway product price to 100 for the product with ID 123. This price is used to give discount on the giveaway product. This hook can be used to alter the price of a custom product which price not get from woocommerce methods like get_price, get_sale_price, get_regular_price, etc.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_giveaway_product_price", "wt_sc_alter_giveaway_product_price", 10, 2 );
function wt_sc_alter_giveaway_product_price( $product_price, $product ) {
 
	if ( 123 === $product->get_id() ) {
		$product_price = 100;
	}
 
	return $product_price;
}',
		),
		'wbte_sc_bogo_alter_cart_amount_for_validation'    => array(
			'title'       => __( 'Alter the cart amount for validation.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the cart amount for validation. It can be used if site used multicurrency plugin.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wbte_sc_bogo_alter_cart_amount_for_validation", "wbte_sc_bogo_alter_cart_amount_for_validation", 10, 2 );
function wbte_sc_bogo_alter_cart_amount_for_validation( $cart_amount, $coupon_id ) {
 
	return $cart_amount * 0.8;
}',
		),
		'wt_sc_alter_bogo_giveaway_product_ids_for_cart'   => array(
			'title'       => __( 'Change giveaway product IDs for specific product BOGO type.', 'thetoppercentile-coupons' ),
			'description' => __( 'This hook can be used when using the multi-language plugin.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_bogo_giveaway_product_ids_for_cart", "wt_sc_alter_bogo_giveaway_product_ids_for_cart", 10, 3 );
function wt_sc_alter_bogo_giveaway_product_ids_for_cart( $free_products, $coupon_id, $free_products_original ) {
 
	// Your logic here.
	return $free_products;
}',
		),
		'wt_sc_alter_giveaway_cart_item_data_before_add_to_cart' => array(
			'title'       => __( 'Alter the BOGO cart item data before adding to cart.', 'thetoppercentile-coupons' ),
			'description' => __( 'The code below will add a special meta to the cart item for the product with ID 123.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_giveaway_cart_item_data_before_add_to_cart", "wt_sc_alter_giveaway_cart_item_data_before_add_to_cart", 10, 5 );
function wt_sc_alter_giveaway_cart_item_data_before_add_to_cart( $cart_item_data, $product_id, $variation_id, $quantity, $old_cart_item_data ) {
 
	if ( 123 === $product_id ) {
		$cart_item_data["special_meta"] = "test";
	}

	return $cart_item_data;
}',
		),
		'wbte_sc_alter_allowed_product_types_for_auto_add' => array(
			'title'       => __( 'Alter the allowed product types for auto add.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default simple and variation product types are allowed for automatically adding to cart. This hook can be used to alter the allowed product types for auto add.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wbte_sc_alter_allowed_product_types_for_auto_add", "wbte_sc_alter_allowed_product_types_for_auto_add", 10, 2 );
function wbte_sc_alter_allowed_product_types_for_auto_add( $types, $coupon_id ) {
 
	if ( 123 === $coupon_id ) {
		return array( "simple" );
	}

	return $types;
}',
		),
		'wt_sc_alter_giveaway_cart_lineitem_text'          => array(
			'title'       => __( 'Alter the giveaway cart lineitem text.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the giveaway cart lineitem text. The code below will change the text for the product with ID 123.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_sc_alter_giveaway_cart_lineitem_text", "wt_sc_alter_giveaway_cart_lineitem_text", 10, 2 );
function wt_sc_alter_giveaway_cart_lineitem_text( $text, $cart_item ) {
 
	if ( 123 === $cart_item["product_id"] ) {
		$text = "<p class=\"wbte_sc_bogo_msg_under_free_gift\">" . __( "This is a free product", "thetoppercentile-coupons" ) . "</p>";
	}

	return $text;
}',
		),
		'wbte_sc_bogo_user_can_change_free_product_qty'    => array(
			'title'       => __( 'Alter the user can change the free product quantity.', 'thetoppercentile-coupons' ),
			'description' => __( 'The code below restricts users from changing the free product quantity for the coupon with ID 123.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wbte_sc_bogo_user_can_change_free_product_qty", "wbte_sc_bogo_user_can_change_free_product_qty", 10, 2 );
function wbte_sc_bogo_user_can_change_free_product_qty( $can_change, $coupon_id ) {
 
	if ( 123 === $coupon_id ) {
		return false;
	}

	return $can_change;
}',
		),
		'wbte_sc_bogo_alter_item_price_for_coupon_validation' => array(
			'title'       => __( 'Alter cart item price for BOGO validation.', 'thetoppercentile-coupons' ),
			'description' => __( 'Alter the cart item price for BOGO validation. Can be used for third-party products whose prices are not obtained from WooCommerce methods like get_price, get_sale_price, get_regular_price, etc.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wbte_sc_bogo_alter_item_price_for_coupon_validation", "wbte_sc_bogo_alter_item_price_for_coupon_validation", 10, 3 );
function wbte_sc_bogo_alter_item_price_for_coupon_validation( $item_price, $item, $coupon_id ) {

	if ( 123 === $coupon_id ) {
		return $item["special_price"];
	}

	return $item_price;
}',
		),
		'wbte_sc_alter_bogo_products_categories_validation' => array(
			'title'       => __( 'Alter the BOGO products and categories IDs for validation.', 'thetoppercentile-coupons' ),
			'description' => __( 'Hooks, which help when using a multilingual plugin. These hooks can be used to return translated product or category IDs for BOGO validation.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wbte_sc_alter_bogo_product_ids", "wbte_sc_alter_bogo_product_ids", 10, 2 );
function wbte_sc_alter_bogo_product_ids( $product_ids, $coupon_id ) {

	// Your logic here.

	return $product_ids;
}

add_filter( "wbte_sc_alter_bogo_exclude_product_ids", "wbte_sc_alter_bogo_exclude_product_ids", 10, 2 );
function wbte_sc_alter_bogo_exclude_product_ids( $exclude_product_ids, $coupon_id ) {

	// Your logic here.

	return $exclude_product_ids;
}',
		),
	),

	// Coupon clone.
	'coupon_clone'   => array(
		'wt_smartcoupon_default_duplicate_coupon_status' => array(
			'title'       => __( 'Set the cloned coupon status', 'thetoppercentile-coupons' ),
			'description' => __( 'Set the cloned coupon status.', 'thetoppercentile-coupons' ),
			'example'     => 'add_filter( "wt_smartcoupon_default_duplicate_coupon_status", "wt_sc_set_cloned_coupon_status_to_draft" );
function wt_sc_set_cloned_coupon_status_to_draft( $status ) {

	return "draft"; // Set the coupon status to draft.
}',
		),
	),

	// Coupon message.
	'coupon_message' => array(
		'wt_smart_coupon_auto_coupon_message'       => array(
			'title'       => __( 'Change auto coupon message.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the coupon applied message when the coupon is applied automatically.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_smart_coupon_auto_coupon_message", "wt_sc_change_auto_coupon_message", 10, 1 );

function wt_sc_change_auto_coupon_message( $coupon ) {
	
	return __( "You got a coupon", "thetoppercentile-coupons" ) ; 
}',
		),
		'wt_sc_alter_user_role_validation_message'  => array(
			'title'       => __( 'Change user role validation message.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change user role invalid message when coupon applied by an invalid user.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_user_role_validation_message", "wt_sc_change_user_role_validation_message" );

function wt_sc_change_user_role_validation_message( $message ) {
	return __( "Your role does not qualify for this coupon.", "thetoppercentile-coupons" );
}',
		),
		'wt_sc_alter_individual_min_max_quantity_validation_message' => array(
			'title'       => __( 'Change the minimum and maximum individual quantity validation message.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the minimum and maximum quantity validation messages when enabling "Product/Category restrictions" and "Individual quantity restriction" in coupon settings.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_individual_min_max_quantity_validation_message", "wt_sc_change_individual_min_max_quantity_validation_message", 10, 2 );

function wt_sc_change_individual_min_max_quantity_validation_message( $msg, $array ) {
	if( "min" === $array["type"] ) {
		return sprintf( __( "For this coupon minimum %s quantity of %s is required.", "thetoppercentile-coupons" ), $array["quantity"], $array["item_name"] );
	} elseif ( "max" === $array["type"] ) {
		return sprintf( __( "For this coupon maximum allowed quantity of %s is %s.", "thetoppercentile-coupons" ), $array["item_name"], $array["quantity"] );
	} else {
		return __( "Your cart does not meet the quantity eligibility criteria for this coupon.", "thetoppercentile-coupons" );
	}
}',
		),
		'wt_sc_alter_quantity_restriction_messages' => array(
			'title'       => __( 'Change quantity restriction message', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the minimum and maximum quantity validation messages when "Product/Category restrictions" is disabled. If “Individual quantity restriction” is disabled, the message in if($is_global) will appear.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_quantity_restriction_messages", "wt_sc_change_quantity_restriction_messages", 10, 5 );

function wt_sc_change_quantity_restriction_messages( $out, $coupon_code, $qty, $is_global, $type ) {
	
	if ( "min" === $type ) {
		if ( $is_global ) {
			return sprintf( __( "Coupon valid for %s items; ensure cart has required quantity to redeem.", "thetoppercentile-coupons" ), $qty );
		} else {
			return sprintf( __( "Coupon requires %s minimum eligible items per product; add more to redeem.", "thetoppercentile-coupons" ), $qty );
		}
	} else {
		if( $is_global ) {
			return sprintf( __( "This coupon can be applied to a maximum of %s eligible products.", "thetoppercentile-coupons" ), $qty );
		} else {
			return sprintf( __( "Each eligible item has a maximum allowable quantity of %s.", "thetoppercentile-coupons" ), $qty );
		}
	}
}',
		),
		'wt_sc_alter_multiple_giveaway_added_msg'   => array(
			'title'       => __( 'Change message when multiple giveaway products added', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the message when more than one giveaway product is added to the cart.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_multiple_giveaway_added_msg", "wt_sc_change_multiple_giveaway_added_msg" );

function wt_sc_change_multiple_giveaway_added_msg() {
	return __( "Congratulations! Your cart is now filled with amazing giveaway items!", "thetoppercentile-coupons" );
}',
		),
		'wt_smartcoupon_give_away_message'          => array(
			'title'       => __( 'Change message for choosing giveaway products', 'thetoppercentile-coupons' ),
			'description' => __( 'When a giveaway product is a variable product, a message with a product variation will show to choose. This hook will help to change the title.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_smartcoupon_give_away_message", "wt_sc_change_giveaway_message", 10, 3 );

function wt_sc_change_giveaway_message( $message_html, $coupon_code, $coupon_id ) {
	return \'<h4 class="giveaway-title">\'. __( "Congratulations! Choose your freebie:", "thetoppercentile-coupons" ) .\'<span class="coupon-code">[ \'.$coupon_code.\' ]</span></h4>\';
}',
		),
		'wt_smart_coupon_url_coupon_message'        => array(
			'title'       => __( 'Change url coupon message.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the coupon applied message when the coupon is applied by url.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_smart_coupon_url_coupon_message", "wt_sc_change_url_coupon_message" );

function wt_sc_change_url_coupon_message( $msg ) {
	
	if ( 0 < WC()->cart->get_cart_contents_count() ) {
		return __( "You got a coupon","thetoppercentile-coupons" );
	} else {
		$shop_page_url  = get_page_link( get_option( "woocommerce_shop_page_id" ) );               
		return sprintf( __( "Your cart is empty! Add %s products %s to avail the offer.", "thetoppercentile-coupons" ), \'<a href="\'.esc_url( $shop_page_url ).\'">\', \'</a>\' );
	}
}',
		),

	),

	// My account.
	'my_account'     => array(
		'wt_sc_alter_myaccount_no_available_coupons_msg' => array(
			'title'       => __( 'Message when no coupons available to show in my account coupons page.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the no coupons available message, use this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_myaccount_no_available_coupons_msg", "wt_sc_change_no_coupon_available_msg" );
function wt_sc_change_no_coupon_available_msg( $msg ) {
	
	return __( "No coupons found.", "thetoppercentile-coupons" ); // Add your custom message.
}',
		),
		'wt_sc_alter_myaccount_no_used_coupons_msg'      => array(
			'title'       => __( 'Message when no used coupons available to show in my account coupons page.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the no used coupons available message, use this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_myaccount_no_used_coupons_msg", "wt_sc_change_no_used_coupon_msg" );
function wt_sc_change_no_used_coupon_msg( $msg ) {
	
	return __( "No used coupons found.", "thetoppercentile-coupons" ); // Add your custom message.
}',
		),
		'wt_sc_alter_myaccount_no_expired_coupons_msg'   => array(
			'title'       => __( 'Message when no expired coupons available to show in my account coupons page.', 'thetoppercentile-coupons' ),
			'description' => __( 'To change the no expired coupons available message, use this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_myaccount_no_expired_coupons_msg", "wt_sc_change_no_expired_coupon_msg" );
function wt_sc_change_no_expired_coupon_msg( $msg ) {
	
	return __( "No expired coupons found.", "thetoppercentile-coupons" ); // Add your custom message.
}',
		),
		'wt_sc_alter_available_coupons_sort_order'       => array(
			'title'       => __( 'Change the default sort order for my account coupons.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default the sort order is `Latest last`. You can use this filter to alter the sort order.', 'thetoppercentile-coupons' ) . '<br />' .
							__( 'Applicable values: ', 'thetoppercentile-coupons' ) . 'created_date:desc, created_date:asc, amount:desc, amount:asc',
			'example'     => '
add_filter( "wt_sc_alter_available_coupons_sort_order", "wt_sc_change_my_coupons_default_order_latest_first" );
function wt_sc_change_my_coupons_default_order_latest_first( $default_order ) {
	
	return "created_date:desc";
}',
		),
		'wt_sc_my_account_available_coupons_per_page'    => array(
			'title'       => __( 'Change my account coupons display count per page.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default maximum 20 coupons will display in the my account page. You can change the count by using this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_my_account_available_coupons_per_page", "wt_sc_increase_my_account_coupons_count" );
function wt_sc_increase_my_account_coupons_count( $count ) {

	return 50; // Increase the count to 50.
}',
		),
		'wt_sc_my_account_expired_coupons_per_page'      => array(
			'title'       => __( 'Change my account expired coupons display count.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default maximum 50 expired coupons will display in the my account page. You can change the count by using this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_my_account_expired_coupons_per_page", "wt_sc_change_my_account_expired_coupons_count" );
function wt_sc_change_my_account_expired_coupons_count( $count ) {

	return 100; // Change the count to 100.
}',
		),
		'wt_smart_coupon_before_my_account_coupons'      => array(
			'title'       => __( 'Add heading before My coupons in my account.', 'thetoppercentile-coupons' ),
			'description' => __( 'You can add a custom heading before my coupons in my account.', 'thetoppercentile-coupons' ),
			'example'     => '
add_action( "wt_smart_coupon_before_my_account_coupons", "wt_sc_add_heading_before_my_account_coupons" );
function wt_sc_add_heading_before_my_account_coupons() {

	echo "<p>" . __( "Click on Available Coupons to apply", "thetoppercentile-coupons" ) . "</p>";
}',
		),
	),

	// Checkout page.
	'checkout'       => array(
		'wt_sc_checkout_available_coupons_per_page' => array(
			'title'       => __( 'Change checkout coupons display count.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default maximum 20 coupons will display in the checkout page. You can change the count by using this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_checkout_available_coupons_per_page", "wt_sc_increase_checkout_coupons_count" );
function wt_sc_increase_checkout_coupons_count( $count ) {

	return 50; // Increase the count to 50.
}',
		),
		'wt_smart_coupon_before_checkout_coupons'   => array(
			'title'       => __( 'Add a heading before coupons on the checkout page.', 'thetoppercentile-coupons' ),
			'description' => __( 'You can add a custom heading before coupons on the checkout page.', 'thetoppercentile-coupons' ),
			'example'     => '
add_action( "wt_smart_coupon_before_checkout_coupons", "wt_sc_add_heading_before_checkout_coupon" );
function wt_sc_add_heading_before_checkout_coupon() {

	echo "<p>" . __( "Click coupons to apply", "thetoppercentile-coupons" ) . "</p>";
}',
		),
	),

	// Others.
	'others'         => array(
		'wt_sc_cart_available_coupons_per_page'        => array(
			'title'       => __( 'Change cart coupons display count.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default maximum 20 coupons will display in the cart page. You can change the count by using this filter.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_cart_available_coupons_per_page", "wt_sc_increase_cart_coupons_count" );
function wt_sc_increase_cart_coupons_count( $count ) {

	return 50; // Increase the count to 50.
}',
		),
		'wt_smartcoupon_max_auto_coupons_limit'        => array(
			'title'       => __( 'Change auto coupon apply count.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default, a maximum of 5 coupons will be applied automatically. You can change the count by using this filter. Note: Setting a high number may affect the performance of the website.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_smartcoupon_max_auto_coupons_limit", "wt_sc_increase_auto_coupons_limit" );
function wt_sc_increase_auto_coupons_limit( $count ) {

	return 10; // Increase the count to 10.
}',
		),
		'wt_sc_enable_pagination_in_user_available_coupons' => array(
			'title'       => __( 'Remove pagination after available coupon.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default, a next and prev button will be available after the available coupons. The count of available coupons will be basically 20, but it can be changed with the help of hooks.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_enable_pagination_in_user_available_coupons", "__return_false" );',
		),
		'wt_sc_alter_order_detail_giveaway_info_label' => array(
			'title'       => __( 'Change giveaway product label in the order details.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default, "Free gift" is the label of the giveaway product in the order details, which can be changed by this hook.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_order_detail_giveaway_info_label", "wt_sc_change_order_detail_giveaway_product_label", 10, 4 );

function wt_sc_change_order_detail_giveaway_product_label( $label, $order_item, $order_item_id, $order ) {
	return __( "Gift Product:", "thetoppercentile-coupons" );
}',
		),
		'wt_sc_alter_giveaway_cart_summary_label'      => array(
			'title'       => __( 'Change giveaway product label in the cart summary.', 'thetoppercentile-coupons' ),
			'description' => __( 'By default, "Free gift" is the label of the giveaway product in the cart summary, which can be changed by this hook.', 'thetoppercentile-coupons' ),
			'example'     => '
add_filter( "wt_sc_alter_giveaway_cart_summary_label", "wt_sc_change_giveaway_cart_summary_label", 10, 2 );

function wt_sc_change_giveaway_cart_summary_label( $label, $cart_item ) {
	return __( "Gift Product:", "thetoppercentile-coupons" );
}',
		),
	),
);
