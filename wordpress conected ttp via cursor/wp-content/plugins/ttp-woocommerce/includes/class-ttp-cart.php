<?php
/**
 * Custom WooCommerce cart page renderer for Top Percentile.
 *
 * - Empty cart  -> branded panel with CTA to /exam/.
 * - Filled cart -> custom styled cart with items, qty, totals, checkout button.
 *
 * Replaces the post content of the WooCommerce cart page only.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'TTP_Cart', false ) ) {
	return;
}

class TTP_Cart {

	const VERSION = '1.5.0';

	public function __construct() {
		add_filter( 'the_content', array( $this, 'maybe_render' ), 99 );
		add_action( 'wp_head', array( $this, 'print_styles' ), 99 );
		add_action( 'wp_footer', array( $this, 'print_cart_checkout_click_fix' ), 5 );
		add_action( 'wp_footer', array( $this, 'print_hide_yellow_floating_cta' ), 999 );
	}

	/**
	 * @return string
	 */
	public static function browse_url() {
		return (string) apply_filters( 'ttp_empty_cart_browse_url', home_url( '/exam/' ) );
	}

	/**
	 * @return string
	 */
	public static function browse_label() {
		return (string) apply_filters( 'ttp_empty_cart_browse_label', __( 'Browse Courses', 'ttp' ) );
	}

	/**
	 * Checkout URL for cart CTA — uses raw /checkout/ permalink so TPSP login filters cannot rewrite it.
	 *
	 * @return string
	 */
	public static function checkout_url() {
		if ( function_exists( 'ttp_guest_checkout_url_with_cart' ) ) {
			return (string) ttp_guest_checkout_url_with_cart();
		}
		if ( function_exists( 'ttp_guest_checkout_direct_url' ) ) {
			$base = (string) ttp_guest_checkout_direct_url();
		} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
			$base = (string) wc_get_page_permalink( 'checkout' );
		} else {
			$base = home_url( '/checkout/' );
		}

		if ( function_exists( 'WC' ) && WC()->cart && ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$product_id = isset( $item['product_id'] ) ? (int) $item['product_id'] : 0;
				if ( $product_id > 0 ) {
					return add_query_arg( 'add-to-cart', $product_id, $base );
				}
			}
		}

		return $base;
	}

	/**
	 * Loose check — usable in wp_head where the main loop has not started yet.
	 *
	 * @return bool
	 */
	private function is_cart_page() {
		if ( is_admin() || wp_doing_ajax() ) {
			return false;
		}
		if ( ! function_exists( 'is_cart' ) ) {
			return false;
		}
		if ( ! is_cart() ) {
			return false;
		}
		return true;
	}

	/**
	 * Strict check — only true inside the main loop on the cart page; safe for the_content.
	 *
	 * @return bool
	 */
	private function is_cart_page_context() {
		if ( ! $this->is_cart_page() ) {
			return false;
		}
		if ( ! in_the_loop() || ! is_main_query() ) {
			return false;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart instanceof WC_Cart ) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $content Original post content.
	 * @return string
	 */
	public function maybe_render( $content ) {
		if ( ! $this->is_cart_page_context() ) {
			return $content;
		}

		ob_start();
		echo '<div class="ttp-cart-page">';
		if ( WC()->cart->is_empty() ) {
			$this->render_empty();
		} else {
			$this->render_filled();
		}
		echo '</div>';

		return (string) ob_get_clean();
	}

	private function render_empty() {
		$url   = esc_url( self::browse_url() );
		$label = esc_html( self::browse_label() );
		?>
		<div class="ttp-cart-empty">
			<div class="ttp-cart-empty__icon" aria-hidden="true">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 8H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
					<circle cx="10" cy="20" r="1.4" fill="currentColor"/>
					<circle cx="17" cy="20" r="1.4" fill="currentColor"/>
				</svg>
			</div>
			<h2 class="ttp-cart-empty__title"><?php esc_html_e( 'Your cart is empty', 'ttp' ); ?></h2>
			<p class="ttp-cart-empty__subtitle">
				<?php esc_html_e( 'Pick a course plan and start your MBA prep with The Top Percentile.', 'ttp' ); ?>
			</p>
			<a class="ttp-cart-empty__cta" href="<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<span><?php echo $label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</a>
			<p class="ttp-cart-empty__hint">
				<?php
				printf(
					/* translators: %s: link to login page */
					esc_html__( 'Already enrolled? %s to access your study portal.', 'ttp' ),
					'<a href="' . esc_url( home_url( '/login/' ) ) . '">' . esc_html__( 'Log in', 'ttp' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}

	private function render_filled() {
		$cart = WC()->cart;
		?>
		<form class="ttp-cart" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post" novalidate>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

			<div class="ttp-cart__grid">
				<div class="ttp-cart__items">
					<h1 class="ttp-cart__title">
						<?php
						printf(
							/* translators: %d: number of items */
							esc_html( _n( 'Your Cart (%d item)', 'Your Cart (%d items)', (int) $cart->get_cart_contents_count(), 'ttp' ) ),
							(int) $cart->get_cart_contents_count()
						);
						?>
					</h1>

					<?php
					do_action( 'woocommerce_before_cart_contents' );

					foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
						$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
						$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

						if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 ) {
							continue;
						}

						$visible    = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );
						if ( ! $visible ) {
							continue;
						}

						$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
						$thumbnail         = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );
						$product_name      = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
						$product_subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', $cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
						$remove_url        = wc_get_cart_remove_url( $cart_item_key );
						?>
						<article class="ttp-cart-item" data-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
							<div class="ttp-cart-item__thumb">
								<?php if ( $product_permalink ) : ?>
									<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
								<?php else : ?>
									<?php echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php endif; ?>
							</div>
							<div class="ttp-cart-item__body">
								<h3 class="ttp-cart-item__name">
									<?php if ( $product_permalink ) : ?>
										<a href="<?php echo esc_url( $product_permalink ); ?>"><?php echo wp_kses_post( $product_name ); ?></a>
									<?php else : ?>
										<?php echo wp_kses_post( $product_name ); ?>
									<?php endif; ?>
								</h3>

								<?php
								echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								if ( $_product->is_sold_individually() ) {
									$quantity_html = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
								} else {
									$quantity_html = woocommerce_quantity_input(
										array(
											'input_name'   => "cart[{$cart_item_key}][qty]",
											'input_value'  => $cart_item['quantity'],
											'max_value'    => $_product->get_max_purchase_quantity(),
											'min_value'    => '0',
											'product_name' => $_product->get_name(),
										),
										$_product,
										false
									);
								}
								?>
								<div class="ttp-cart-item__meta">
									<div class="ttp-cart-item__qty">
										<label class="ttp-cart-item__qty-label"><?php esc_html_e( 'Qty', 'ttp' ); ?></label>
										<?php echo apply_filters( 'woocommerce_cart_item_quantity', $quantity_html, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<div class="ttp-cart-item__subtotal"><?php echo $product_subtotal; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								</div>

								<a class="ttp-cart-item__remove" href="<?php echo esc_url( $remove_url ); ?>" aria-label="<?php esc_attr_e( 'Remove item', 'ttp' ); ?>">
									<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
										<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6h12z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
									</svg>
									<span><?php esc_html_e( 'Remove', 'ttp' ); ?></span>
								</a>
							</div>
						</article>
						<?php
					}

					do_action( 'woocommerce_cart_contents' );
					?>

					<div class="ttp-cart__actions">
						<?php if ( wc_coupons_enabled() ) : ?>
							<div class="ttp-cart__coupon">
								<input type="text" name="coupon_code" class="ttp-cart__coupon-input" placeholder="<?php esc_attr_e( 'Coupon code', 'ttp' ); ?>" />
								<button type="submit" class="ttp-cart__coupon-apply" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'ttp' ); ?>"><?php esc_html_e( 'Apply', 'ttp' ); ?></button>
							</div>
						<?php endif; ?>
						<button type="submit" class="ttp-cart__update" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'ttp' ); ?>"><?php esc_html_e( 'Update cart', 'ttp' ); ?></button>
					</div>
					<?php do_action( 'woocommerce_after_cart_contents' ); ?>
				</div>

				<aside class="ttp-cart__summary" aria-label="<?php esc_attr_e( 'Order summary', 'ttp' ); ?>">
					<h2 class="ttp-cart__summary-title"><?php esc_html_e( 'Order Summary', 'ttp' ); ?></h2>

					<div class="ttp-cart__summary-row">
						<span><?php esc_html_e( 'Subtotal', 'ttp' ); ?></span>
						<span><?php echo $cart->get_cart_subtotal(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>

					<?php foreach ( $cart->get_coupons() as $code => $coupon ) : ?>
						<div class="ttp-cart__summary-row ttp-cart__summary-row--discount">
							<span><?php echo esc_html( wc_cart_totals_coupon_label( $coupon, false ) ); ?></span>
							<span>
								<?php echo wc_cart_totals_coupon_html( $coupon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</div>
					<?php endforeach; ?>

					<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
						<div class="ttp-cart__summary-row">
							<span><?php esc_html_e( 'Tax', 'ttp' ); ?></span>
							<span><?php echo wc_price( $cart->get_taxes_total() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						</div>
					<?php endif; ?>

					<div class="ttp-cart__summary-row ttp-cart__summary-row--total">
						<span><?php esc_html_e( 'Total', 'ttp' ); ?></span>
						<span><?php echo wc_price( $cart->get_total( 'edit' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</div>

					<a class="ttp-cart__checkout" href="<?php echo esc_url( self::checkout_url() ); ?>">
						<span><?php esc_html_e( 'Proceed to Checkout', 'ttp' ); ?></span>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</a>

					<a class="ttp-cart__continue" href="<?php echo esc_url( self::browse_url() ); ?>">
						<?php esc_html_e( '← Continue browsing courses', 'ttp' ); ?>
					</a>

					<p class="ttp-cart__secure">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
							<path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<?php esc_html_e( 'Secure checkout — your data is encrypted.', 'ttp' ); ?>
					</p>
				</aside>
			</div>
		</form>
		<?php
	}

	public function print_styles() {
		if ( ! $this->is_cart_page() ) {
			return;
		}
		?>
		<style id="ttp-cart-css">
		.ttp-cart-page { max-width: 1100px; margin: 30px auto 60px; padding: 0 16px; font-family: inherit; color: #1f2937; }

		.ttp-cart-empty {
			max-width: 560px;
			margin: 40px auto;
			padding: 44px 28px;
			background: #ffffff;
			border: 1px solid #f1e3d8;
			border-radius: 16px;
			box-shadow: 0 12px 32px rgba(232, 84, 10, 0.08);
			text-align: center;
		}
		.ttp-cart-empty__icon {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			width: 92px; height: 92px;
			margin: 0 auto 18px;
			border-radius: 50%;
			background: linear-gradient(135deg, #fff1e6 0%, #ffe1cc 100%);
			color: #e8540a;
		}
		.ttp-cart-empty__title { margin: 0 0 8px; font-size: 28px; line-height: 1.25; font-weight: 700; color: #111827; }
		.ttp-cart-empty__subtitle { margin: 0 0 26px; font-size: 15px; line-height: 1.55; color: #4b5563; }
		.ttp-cart-empty__cta {
			display: inline-flex; align-items: center; gap: 8px;
			padding: 14px 28px;
			background: #e8540a; color: #ffffff !important;
			font-size: 15px; font-weight: 600; letter-spacing: 0.2px;
			text-decoration: none !important;
			border-radius: 999px;
			box-shadow: 0 8px 20px rgba(232, 84, 10, 0.28);
			transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
		}
		.ttp-cart-empty__cta:hover, .ttp-cart-empty__cta:focus { background: #cf480a; transform: translateY(-1px); box-shadow: 0 12px 24px rgba(232, 84, 10, 0.32); outline: none; }
		.ttp-cart-empty__cta svg { transition: transform .2s ease; }
		.ttp-cart-empty__cta:hover svg { transform: translateX(3px); }
		.ttp-cart-empty__hint { margin: 22px 0 0; font-size: 13px; color: #6b7280; }
		.ttp-cart-empty__hint a { color: #e8540a; font-weight: 600; text-decoration: none; }
		.ttp-cart-empty__hint a:hover { text-decoration: underline; }

		.ttp-cart__grid { display: grid; grid-template-columns: 1fr 360px; gap: 28px; align-items: start; }
		@media (max-width: 900px) { .ttp-cart__grid { grid-template-columns: 1fr; } }

		.ttp-cart__title { margin: 0 0 18px; font-size: 24px; font-weight: 700; color: #111827; }

		.ttp-cart-item {
			display: grid;
			grid-template-columns: 96px 1fr;
			gap: 16px;
			padding: 16px;
			margin-bottom: 12px;
			background: #ffffff;
			border: 1px solid #ececec;
			border-radius: 14px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
		}
		.ttp-cart-item__thumb img {
			width: 96px; height: 96px; object-fit: cover; border-radius: 10px;
			background: #f7f7f7;
		}
		.ttp-cart-item__body { display: flex; flex-direction: column; gap: 8px; min-width: 0; }
		.ttp-cart-item__name { margin: 0; font-size: 16px; font-weight: 600; color: #111827; line-height: 1.35; }
		.ttp-cart-item__name a { color: inherit; text-decoration: none; }
		.ttp-cart-item__name a:hover { color: #e8540a; }
		.ttp-cart-item__meta {
			display: flex; align-items: center; justify-content: space-between; gap: 12px;
			margin-top: 4px;
		}
		.ttp-cart-item__qty { display: flex; align-items: center; gap: 8px; }
		.ttp-cart-item__qty-label { font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
		.ttp-cart-item__qty .quantity input.qty,
		.ttp-cart-item__qty input[type="number"] {
			width: 64px; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 8px;
			font-size: 14px; text-align: center;
		}
		.ttp-cart-item__subtotal { font-weight: 700; color: #111827; }
		.ttp-cart-item__remove {
			display: inline-flex; align-items: center; gap: 6px;
			align-self: flex-start;
			padding: 6px 10px;
			border-radius: 8px;
			color: #b91c1c; font-size: 13px; text-decoration: none;
		}
		.ttp-cart-item__remove:hover { background: #fef2f2; color: #991b1b; }

		.ttp-cart__actions {
			display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
			gap: 12px;
			margin-top: 8px; padding: 14px 4px;
			border-top: 1px dashed #e5e7eb;
		}
		.ttp-cart__coupon { display: flex; gap: 8px; flex: 1 1 260px; min-width: 0; }
		.ttp-cart__coupon-input {
			flex: 1; min-width: 0;
			padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px;
			font-size: 14px;
		}
		.ttp-cart__coupon-apply, .ttp-cart__update {
			padding: 10px 16px; border: 1px solid #e5e7eb;
			background: #f9fafb; color: #111827;
			border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
		}
		.ttp-cart__coupon-apply:hover, .ttp-cart__update:hover { background: #f3f4f6; }

		.ttp-cart__summary {
			padding: 22px;
			background: #ffffff;
			border: 1px solid #ececec;
			border-radius: 14px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
			position: sticky; top: 24px;
		}
		.ttp-cart__summary-title { margin: 0 0 14px; font-size: 18px; font-weight: 700; color: #111827; }
		.ttp-cart__summary-row {
			display: flex; align-items: center; justify-content: space-between;
			padding: 8px 0; font-size: 14px; color: #4b5563;
		}
		.ttp-cart__summary-row--discount span:last-child { color: #16a34a; font-weight: 600; }
		.ttp-cart__summary-row--total {
			margin-top: 6px; padding-top: 14px; border-top: 1px solid #e5e7eb;
			font-size: 18px; font-weight: 700; color: #111827;
		}
		.ttp-cart__checkout {
			display: inline-flex; align-items: center; justify-content: center; gap: 8px;
			width: 100%;
			margin-top: 14px;
			padding: 14px 16px;
			background: #e8540a; color: #ffffff !important;
			font-size: 15px; font-weight: 700; letter-spacing: .2px;
			text-decoration: none !important;
			border-radius: 999px;
			box-shadow: 0 8px 20px rgba(232, 84, 10, 0.28);
			transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
		}
		.ttp-cart__checkout:hover { background: #cf480a; transform: translateY(-1px); box-shadow: 0 12px 24px rgba(232, 84, 10, 0.32); }
		.ttp-cart__checkout svg { transition: transform .2s ease; }
		.ttp-cart__checkout:hover svg { transform: translateX(3px); }
		.ttp-cart__continue {
			display: inline-block; margin-top: 12px;
			color: #4b5563; font-size: 13px; text-decoration: none;
		}
		.ttp-cart__continue:hover { color: #e8540a; }
		.ttp-cart__secure {
			display: flex; align-items: center; gap: 6px;
			margin: 14px 0 0; font-size: 12px; color: #6b7280;
		}
		.ttp-cart__secure svg { color: #16a34a; }

		@media (max-width: 520px) {
			.ttp-cart-item { grid-template-columns: 72px 1fr; padding: 12px; }
			.ttp-cart-item__thumb img { width: 72px; height: 72px; }
			.ttp-cart-empty { margin: 24px 12px; padding: 32px 20px; border-radius: 14px; }
			.ttp-cart-empty__title { font-size: 22px; }
		}

		/* Kill TPSP yellow floating fallback and stray Elementor sticky checkout bars. */
		body.woocommerce-cart #tpsp-floating-checkout,
		body.woocommerce-cart a#tpsp-floating-checkout { display: none !important; visibility: hidden !important; pointer-events: none !important; }

		/* Our checkout button must always be clickable. */
		body.woocommerce-cart a.ttp-cart__checkout,
		body.woocommerce-cart a.ttp-cart-empty__cta { display: inline-flex !important; visibility: visible !important; opacity: 1 !important; pointer-events: auto !important; cursor: pointer !important; }
		</style>
		<?php
	}

	/**
	 * Force navigation when our checkout button is clicked (beats coupon/theme handlers).
	 *
	 * @return void
	 */
	public function print_cart_checkout_click_fix() {
		if ( ! $this->is_cart_page() ) {
			return;
		}
		$checkout_url = self::checkout_url();
		?>
		<script id="ttp-cart-checkout-click">
		(function () {
			var checkoutUrl = <?php echo wp_json_encode( $checkout_url ); ?>;
			if (!checkoutUrl) { return; }
			document.addEventListener('click', function (e) {
				var btn = e.target && e.target.closest ? e.target.closest('a.ttp-cart__checkout') : null;
				if (!btn) { return; }
				e.preventDefault();
				e.stopPropagation();
				if (typeof e.stopImmediatePropagation === 'function') { e.stopImmediatePropagation(); }
				window.location.assign(btn.getAttribute('href') || checkoutUrl);
			}, true);
		})();
		</script>
		<?php
	}

	/**
	 * Remove TPSP yellow floating "Proceed to checkout" (#tpsp-floating-checkout).
	 *
	 * @return void
	 */
	public function print_hide_yellow_floating_cta() {
		if ( ! $this->is_cart_page() ) {
			return;
		}
		?>
		<script id="ttp-cart-kill-yellow-popup">
		(function () {
			function kill() {
				var el = document.getElementById('tpsp-floating-checkout');
				if (el && el.parentNode) { el.parentNode.removeChild(el); }
			}
			kill();
			document.addEventListener('DOMContentLoaded', kill);
			setTimeout(kill, 500);
			setTimeout(kill, 1500);
			setTimeout(kill, 3000);
			if (typeof MutationObserver !== 'undefined' && document.body) {
				try {
					new MutationObserver(kill).observe(document.body, { childList: true, subtree: true });
				} catch (e) { /* ignore */ }
			}
		})();
		</script>
		<?php
	}
}
