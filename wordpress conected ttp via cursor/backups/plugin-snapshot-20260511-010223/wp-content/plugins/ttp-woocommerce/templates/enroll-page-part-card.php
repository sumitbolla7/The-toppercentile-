<?php
/**
 * Single plan card partial — used by enroll-page for consistent markup / equal-height layout.
 *
 * @package TTP_WooCommerce
 *
 * @var WC_Product $product
 * @var string     $badge
 * @var string     $discount_text
 * @var string     $features_html   Optional HTML from short description.
 * @var array      $legacy_features Legacy string list when not using WC HTML.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $legacy_features ) || ! is_array( $legacy_features ) ) {
	$legacy_features = [];
}

$url = get_permalink( $product->get_id() );
?>
<div class="ttp-plan-card">
	<div class="ttp-plan-card__inner">
		<div class="ttp-plan-card__head">
			<div class="ttp-plan-badge" aria-hidden="true"><?php echo esc_html( $badge ); ?></div>
			<h2 class="ttp-plan-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<?php if ( ! empty( $discount_text ) ) : ?>
				<div class="ttp-plan-discount"><?php echo esc_html( $discount_text ); ?></div>
			<?php endif; ?>
		</div>

		<div class="ttp-plan-card__price-block">
			<div class="ttp-price-row">
				<?php if ( $product->is_on_sale() ) : ?>
					<span class="ttp-price-current"><?php echo wp_kses_post( wc_price( $product->get_sale_price() ) ); ?></span>
					<span class="ttp-price-original"><?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></span>
				<?php else : ?>
					<span class="ttp-price-current"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="ttp-plan-card__body">
			<?php if ( ! empty( $features_html ) ) : ?>
				<div class="ttp-features-list ttp-features-list--from-wc">
					<?php echo wp_kses_post( $features_html ); ?>
				</div>
			<?php elseif ( ! empty( $legacy_features ) && is_array( $legacy_features ) ) : ?>
				<ul class="ttp-features-list">
					<?php foreach ( $legacy_features as $feature ) : ?>
						<li><span class="ttp-check" aria-hidden="true"></span><?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="ttp-plan-card__foot">
		<button
			type="button"
			class="ttp-btn-enroll ttp-buy-now-btn"
			data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
		>
			<?php esc_html_e( 'Enroll now', 'ttp-woocommerce' ); ?>
		</button>
		<a href="<?php echo esc_url( $url ); ?>" class="ttp-btn-cart">
			<?php esc_html_e( 'View details', 'ttp-woocommerce' ); ?>
		</a>
	</div>
</div>
