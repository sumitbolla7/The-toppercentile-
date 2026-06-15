<?php
/**
 * Other Solutions
 *
 * @link
 * @since 1.4.4
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
?>

<div class="wt_sc_other_solutions_container">
	<div class="wt-sc-other-solutions-tab-content" style="background-color: #FFFFFF;">
		<div class="wt_sc__row" style="background-color: #FFFFFF;"> 
			<div clas="wt_sc_headings">
				<h3 class="wt_sc_other_solutions_heading_1" style="font-weight: bolder;padding-top: 30px;"><?php esc_html_e( 'More Plugins To Make Your Store Stand Out', 'thetoppercentile-coupons' ); ?></h3>
				<h3 class="wt_sc_other_solutions_heading_2"><?php esc_html_e( 'Check out our other plugins that are perfectly suited for WooCommerce store needs.', 'thetoppercentile-coupons' ); ?></h3> 
			</div>
			<div class="wt_sc_other_solutions_container_inner">
			<?php

			/* image location for the logos */
			$wt_sc_other_solutions_images = WT_SMARTCOUPON_MAIN_URL . 'admin/modules/other-solutions/assets/images';

			/* Plugin lists array */
			$other_plugins = array(
				'giftcards_plugin'               => array(
					'title'        => __( 'TheTopPercentile WooCommerce Gift Cards', 'thetoppercentile-coupons' ),
					'description'  => __( 'Create and manage advanced gift cards for WooCommerce stores. Enable your customers to buy, redeem, and share gift cards from your store.', 'thetoppercentile-coupons' ),
					'image_url'    => 'giftcards_plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-gift-cards/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=WooCommerce_Gift_Cards',
					'basic_url'    => 'https://wordpress.org/plugins/wt-gift-cards-woocommerce/',
					'pro_plugin'   => 'wt-woocommerce-gift-cards/wt-woocommerce-gift-cards.php',
					'basic_plugin' => 'wt-gift-cards-woocommerce/wt-gift-cards-woocommerce.php',
				),
				'smart_coupons_plugin'           => array(
					'title'        => __( 'TheTopPercentile Coupons', 'thetoppercentile-coupons' ),
					'description'  => __( 'Create coupons to offer discounts and free products to your customers with TheTopPercentile Coupons. You can set up BOGO coupons, giveaways, gift cards, store credits, and more with this plugin.', 'thetoppercentile-coupons' ),
					'image_url'    => 'smart-coupons-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=smart_coupons',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-smart-coupon-pro/wt-smart-coupon-pro.php',
					'basic_plugin' => '',
				),
				'url_coupons_plugin'             => array(
					'title'        => __( 'URL Coupons for WooCommerce', 'thetoppercentile-coupons' ),
					'description'  => __( 'Generate custom URLs and QR codes for every discount coupon in your WooCommerce store. These unique coupons are easy to share and can even be set to add new products to the cart upon application.', 'thetoppercentile-coupons' ),
					'image_url'    => 'url-coupons-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/url-coupons-for-woocommerce/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=URL_Coupons',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-url-coupons-pro/wt-url-coupons-pro.php',
					'basic_plugin' => '',
				),
				'bulk_generate'                  => array(
					'title'        => __( 'WooCommerce Coupon Generator plugin', 'thetoppercentile-coupons' ),
					'description'  => __( 'Generate coupons in bulk on your WooCommerce store. Add them to the store, email them to recipients or export them to a CSV file. Configure advanced coupon restrictions and offer them to select users.', 'thetoppercentile-coupons' ),
					'image_url'    => 'bulk_generate.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-coupon-generator/?utm_source=other_solution_page&utm_medium=premium_plugin&utm_campaign=Coupon_Generator',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-smart-coupon-generate/wt-smart-coupon-generate.php',
					'basic_plugin' => '',
				),
				'wt_ipc_addon'                   => array(
					'title'        => __( 'WooCommerce PDF Invoices, Packing Slips and Credit Notes', 'thetoppercentile-coupons' ),
					'description'  => __( 'Easily generate & print fully customized PDF Invoices, Packing Slips, and Credit Notes for your orders. Automatically send the documents to the recipients by attaching them to the order status emails.', 'thetoppercentile-coupons' ),
					'image_url'    => 'wt_ipc_logo.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-pdf-invoices-packing-slips/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=PDF_invoice',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woocommerce-invoice-addon/wt-woocommerce-invoice-addon.php',
					'basic_plugin' => '',
				),
				'wt_sdd_addon'                   => array(
					'title'        => __( 'WooCommerce Shipping Labels, Dispatch Labels and Delivery Notes', 'thetoppercentile-coupons' ),
					'description'  => __( 'Automatically generate WooCommerce Shipping Labels, Dispatch Labels, and Delivery Notes with custom settings and layouts. Customize the label sizes and add extra product or order fields as required.', 'thetoppercentile-coupons' ),
					'image_url'    => 'wt_sdd_logo.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-shipping-labels-delivery-notes/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Shipping_Label',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woocommerce-shippinglabel-addon/wt-woocommerce-shippinglabel-addon.php',
					'basic_plugin' => '',
				),
				'wt_pl_addon'                    => array(
					'title'        => __( 'WooCommerce Picklists', 'thetoppercentile-coupons' ),
					'description'  => __( 'Customize, generate and print WooCommerce picklists for all orders on your store and automatically attach them to the order status emails. Add product variation data and other fields to the document.', 'thetoppercentile-coupons' ),
					'image_url'    => 'wt_pl_logo.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-picklist/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Picklist',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woocommerce-picklist-addon/wt-woocommerce-picklist-addon.php',
					'basic_plugin' => '',
				),
				'wt_pi_addon'                    => array(
					'title'        => __( 'WooCommerce Proforma Invoices', 'thetoppercentile-coupons' ),
					'description'  => __( 'Automate the generation of WooCommerce proforma invoices when new orders are placed and send them to your customers via order emails. Customize the layout and content of the invoice as per your needs.', 'thetoppercentile-coupons' ),
					'image_url'    => 'wt_pi_logo.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-proforma-invoice/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Proforma_Invoice',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woocommerce-proforma-addon/wt-woocommerce-proforma-addon.php',
					'basic_plugin' => '',
				),
				'wt_al_addon'                    => array(
					'title'        => __( 'WooCommerce Address Labels', 'thetoppercentile-coupons' ),
					'description'  => __( 'Generate address labels for all orders in your store and easily print them in bulk. Customize the label layout and create labels of different types (shipping, billing, return, from address) with ease.', 'thetoppercentile-coupons' ),
					'image_url'    => 'wt_al_logo.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-address-label/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Address_Label',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woocommerce-addresslabel-addon/wt-woocommerce-addresslabel-addon.php',
					'basic_plugin' => '',
				),
				'product_feed_sync'              => array(
					'title'        => __( 'TheTopPercentile WooCommerce Product Feed & Sync Manager', 'thetoppercentile-coupons' ),
					'description'  => __( 'Generate WooCommerce product feeds for Google Merchant Center and Facebook Business Manager. Use the Facebook catalog sync manager to sync WooCommerce products with Facebook and Instagram shops.', 'thetoppercentile-coupons' ),
					'image_url'    => 'product-feed-sync.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/product-catalog-sync-for-facebook/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=WooCommerce_Product_Feed',
					'basic_url'    => 'https://wordpress.org/plugins/webtoffee-product-feed/',
					'pro_plugin'   => 'webtoffee-product-feed-pro/webtoffee-product-feed-pro.php',
					'basic_plugin' => 'webtoffee-product-feed/webtoffee-product-feed.php',
				),
				'request_quote'                  => array(
					'title'        => __( 'TheTopPercentile Woocommerce Request a Quote', 'thetoppercentile-coupons' ),
					'description'  => __( 'Configure a fully optimized WooCommerce quote request set up in your store. Allow customers to request quotes and store managers to respond to them. Hide product prices, set up email alerts, and more.', 'thetoppercentile-coupons' ),
					'image_url'    => 'request-quote.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-request-a-quote/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Request_Quote',
					'basic_url'    => '',
					'pro_plugin'   => 'wt-woo-request-quote/wt-woo-request-quote.php',
					'basic_plugin' => '',
				),
				'gdpr_cookie_consent_plugin'     => array(
					'title'        => __( 'GDPR Cookie Consent Plugin (CCPA Ready)', 'thetoppercentile-coupons' ),
					'description'  => __( 'The plugin helps you get compliant with GDPR, CCPA, and other major cookie laws. You can create and manage cookie consent banners, scan website cookies, and generate cookie policies with this plugin.', 'thetoppercentile-coupons' ),
					'image_url'    => 'gdpr-cookie-concent-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/gdpr-cookie-consent/?utm_source=other_solution_page&utm_medium=_free_plugin_&utm_campaign=GDPR',
					'basic_url'    => 'https://wordpress.org/plugins/cookie-law-info/',
					'pro_plugin'   => 'webtoffee-gdpr-cookie-consent/cookie-law-info.php',
					'basic_plugin' => '',
				),
				'product_import_export_plugin'   => array(
					'title'        => __( 'Product Import Export Plugin For WooCommerce', 'thetoppercentile-coupons' ),
					'description'  => __( 'Seamlessly import/export your WooCommerce products including simple, variable, custom products and subscriptions. You may also import and export product images, tags, categories, reviews, and ratings.', 'thetoppercentile-coupons' ),
					'image_url'    => 'product-import-export-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/product-import-export-woocommerce/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Product_Import_Export',
					'basic_url'    => 'https://wordpress.org/plugins/product-import-export-for-woo/',
					'pro_plugin'   => 'wt-import-export-for-woo-product/wt-import-export-for-woo-product.php',
					'basic_plugin' => 'product-import-export-for-woo/product-import-export-for-woo.php',
				),
				'customers_import_export_plugin' => array(
					'title'        => __( 'WordPress Users & WooCommerce Customers Import Export', 'thetoppercentile-coupons' ),
					'description'  => __( 'Easily import and export your WordPress users and WooCommerce customers using the Import Export plugin for WooCommerce. The plugin supports the use of CSV, XML, TSV, XLS, and XLSX file formats.', 'thetoppercentile-coupons' ),
					'image_url'    => 'user-import-export-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/wordpress-users-woocommerce-customers-import-export/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=User_Import_Export',
					'basic_url'    => 'https://wordpress.org/plugins/users-customers-import-export-for-wp-woocommerce/',
					'pro_plugin'   => 'wt-import-export-for-woo-user/wt-import-export-for-woo-user.php',
					'basic_plugin' => 'users-customers-import-export-for-wp-woocommerce/users-customers-import-export-for-wp-woocommerce.php',
				),
				'order_import_export_plugin'     => array(
					'title'        => __( 'Order, Coupon, Subscription Export Import for WooCommerce', 'thetoppercentile-coupons' ),
					'description'  => __( 'Export and Import your WooCommerce orders, subscriptions, and discount coupons using a single Import Export plugin. You may customize the export and import files with advanced filters and settings.', 'thetoppercentile-coupons' ),
					'image_url'    => 'order-import-export-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/order-import-export-plugin-for-woocommerce/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Order_Import_Export',
					'basic_url'    => 'https://wordpress.org/plugins/order-import-export-for-woocommerce/',
					'pro_plugin'   => 'wt-import-export-for-woo-order/wt-import-export-for-woo-order.php',
					'basic_plugin' => 'order-import-export-for-woocommerce/order-import-export-for-woocommerce.php',
				),
				'import_export_suit'             => array(
					'title'        => __( 'Import Export Suite for WooCommerce', 'thetoppercentile-coupons' ),
					'description'  => __( 'An all-in-one plugin to import and export WooCommerce store data. You can import and export products, product reviews, orders, customers, discount coupons, and subscriptions using this single plugin.', 'thetoppercentile-coupons' ),
					'image_url'    => 'suite-1-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-import-export-suite/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Import_Export_Suite',
					'basic_url'    => '',
					'pro_plugin'   => array(
						'product' => 'wt-import-export-for-woo-product/wt-import-export-for-woo-product.php',
						'user'    => 'wt-import-export-for-woo-user/wt-import-export-for-woo-user.php',
						'order'   => 'wt-import-export-for-woo-order/wt-import-export-for-woo-order.php',
					),
					'basic_plugin' => '',
				),
				'sequential_order_plugin'        => array(
					'title'        => __( 'Sequential Order Numbers for WooCommerce', 'thetoppercentile-coupons' ),
					'description'  => __( 'Number your WooCommerce orders in a custom, sequential & manageable format. The Sequential Order Number plugin lets your orders follow a custom & unique numbering sequence suitable for your business.', 'thetoppercentile-coupons' ),
					'image_url'    => 'Sequential-order-number-plugin.png',
					'premium_url'  => 'https://www.thetoppercentile.com/product/woocommerce-sequential-order-numbers/?utm_source=other_solution_page&utm_medium=free_plugin&utm_campaign=Sequential_Order_Numbers',
					'basic_url'    => 'https://wordpress.org/plugins/wt-woocommerce-sequential-order-numbers/',
					'pro_plugin'   => 'wt-woocommerce-sequential-order-numbers-pro/wt-advanced-order-number-pro.php',
					'basic_plugin' => 'wt-woocommerce-sequential-order-numbers/wt-advanced-order-number.php',
				),
				'accessibility_plus'        => array(
					'title'        => __( 'Accessibility Tool Kit: WP Accessibility Plugin for WCAG, Section 508, ADA, EAA Compliance', 'thetoppercentile-coupons' ),
					'description'  => __( 'Build an accessible WordPress site that works for everyone. Scan for accessibility issues, get fix recommendations, and ensure WCAG compliance, all without writing code. Inclusive web design made simple.', 'thetoppercentile-coupons' ),
					'image_url'    => 'accessibility_plus.png',
					'premium_url'  => '',
					'basic_url'    => 'https://wordpress.org/plugins/accessibility-plus/',
					'pro_plugin'   => '',
					'basic_plugin' => 'accessibility-plus/accessibility.php',
				),
			);

			foreach ( $other_plugins as $key => $value ) {
				if ( isset( $value['pro_plugin'] ) ) {
					if ( is_array( $value['pro_plugin'] ) && isset( $value['pro_plugin']['product'] ) && isset( $value['pro_plugin']['user'] ) && isset( $value['pro_plugin']['order'] ) ) {
						if ( is_plugin_active( $value['pro_plugin']['product'] ) && is_plugin_active( $value['pro_plugin']['user'] ) && is_plugin_active( $value['pro_plugin']['order'] ) ) {
							continue;
						}
					} elseif ( is_plugin_active( $value['pro_plugin'] ) ) {
							continue;
					}
				}

				?>

				<div class="wt_sc_other_solutions_card">
					<div class="wt_sc_widget_title_wrapper">
						<div class="wt_sc_widget_column_1">
							<img src="<?php echo esc_url( $wt_sc_other_solutions_images . '/' . $value['image_url'] ); ?>" alt="<?php echo esc_attr( $value['title'] ); ?>">
						</div>
						<div class="wt_sc_widget_column_2">
							<h4 class="wt_sc_card-title">
								<?php echo esc_html( $value['title'] ); ?>
							</h4>
						</div>
					</div>
					<div class="wt_sc_widget_column_3">
						<p>
							<?php echo esc_html( $value['description'] ); ?>
						</p>
					</div>
					<div class="wt_sc_buttons">
						<?php
						if ( isset( $value['premium_url'] ) && '' !== $value['premium_url'] ) {
							?>
							<div class="wt_sc_premium_button">
								<a href="<?php echo esc_url( $value['premium_url'] ); ?>" class="wt_sc_get_premium_btn" target="_blank"><img src="<?php echo esc_url( $wt_sc_other_solutions_images . '/promote_crown.png' ); ?>" style="width: 10px;height: 10px;" alt=""><?php esc_html_e( ' Get Premium', 'thetoppercentile-coupons' ); ?></a>
							</div>
							<?php
						}
						if ( is_plugin_active( $value['basic_plugin'] ) ) {
							?>
							<div class="wt_sc_installed_button">
								<button class="wt_sc_installed_btn">
									<?php esc_html_e( 'Installed', 'thetoppercentile-coupons' ); ?>
								</button>
							</div>
							<?php
						} elseif ( isset( $value['basic_plugin'] ) && '' !== $value['basic_plugin'] && ! is_plugin_active( $value['basic_plugin'] )
						&& isset( $value['basic_url'] ) && '' !== $value['basic_url'] ) {
							?>
							<div class="wt_sc_free_button" >
								<a class="wt_sc_free_btn_a" href="<?php echo esc_url( $value['basic_url'] ); ?>" target="_blank">
									<button class="wt_sc_free_btn" >
										<?php esc_html_e( 'Get Free Plugin', 'thetoppercentile-coupons' ); ?>
									</button>
								</a>
							</div>

				<?php } ?>
				
					</div>
				</div>

				<?php
			}
			?>

			</div>
		</div>
	</div>
</div>

