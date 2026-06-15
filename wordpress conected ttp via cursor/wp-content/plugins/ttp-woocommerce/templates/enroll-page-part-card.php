<?php
/**
 * Premium Enrol Now course card (reference marketplace layout).
 *
 * @package TTP_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $legacy_features ) || ! is_array( $legacy_features ) ) {
	$legacy_features = [];
}

$ttp_is_popular = ! empty( $ttp_enroll_is_popular );
$capsules       = isset( $ttp_enroll_capsules ) && is_array( $ttp_enroll_capsules ) ? $ttp_enroll_capsules : [];
$banner_title   = isset( $ttp_enroll_banner_title ) ? (string) $ttp_enroll_banner_title : '';
$slug           = sanitize_title( $product->get_slug() );

$details_url = function_exists( 'ttp_enroll_view_details_url' )
	? ttp_enroll_view_details_url( $product )
	: get_permalink( $product->get_id() );

$image_url = function_exists( 'ttp_enroll_card_image_url_for_product' )
	? ttp_enroll_card_image_url_for_product( $product )
	: '';

$custom_enroll_image = function_exists( 'ttp_enroll_card_image_id_for_product' ) && ttp_enroll_card_image_id_for_product( $product ) > 0;
$slug_banner         = function_exists( 'ttp_enroll_banner_url_for_slug' ) ? ttp_enroll_banner_url_for_slug( $slug ) : '';
$has_branded_banner  = $custom_enroll_image || ( $image_url !== '' && $slug_banner !== '' && $slug_banner === $image_url );

$rating        = apply_filters( 'ttp_enroll_card_rating', '4.95', $product );
$review_label  = function_exists( 'ttp_enroll_review_count_label' )
	? ttp_enroll_review_count_label( $product )
	: apply_filters( 'ttp_enroll_review_count_label', '(512 Reviews)', $product );
$banner_pills  = array_slice( $capsules, 0, 3 );

$feature_lines = [];
if ( isset( $features_html ) && is_string( $features_html ) && $features_html !== '' ) {
	if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', $features_html, $li_matches ) && ! empty( $li_matches[1] ) ) {
		foreach ( $li_matches[1] as $frag ) {
			$t = trim( wp_strip_all_tags( wp_specialchars_decode( $frag ) ) );
			if ( $t !== '' ) {
				$feature_lines[] = $t;
			}
		}
	}
}
if ( empty( $feature_lines ) && ! empty( $legacy_features ) ) {
	foreach ( $legacy_features as $f ) {
		$f = trim( wp_strip_all_tags( (string) $f ) );
		if ( $f !== '' ) {
			$feature_lines[] = $f;
		}
	}
}
if ( empty( $feature_lines ) && $product instanceof WC_Product ) {
	$short = trim( wp_strip_all_tags( $product->get_short_description() ) );
	if ( $short !== '' ) {
		$chunks = preg_split( '/[\n\r•]+/', $short );
		if ( is_array( $chunks ) ) {
			foreach ( $chunks as $chunk ) {
				$chunk = trim( (string) $chunk );
				if ( $chunk !== '' ) {
					$feature_lines[] = $chunk;
				}
			}
		}
	}
}

$dlabel = isset( $discount_text ) ? trim( (string) $discount_text ) : '';

$card_class = 'ttp-course-card ttp-course-card--premium';
if ( $ttp_is_popular ) {
	$card_class .= ' ttp-course-card--popular';
}
if ( $has_branded_banner ) {
	$card_class .= ' ttp-course-card--branded-banner';
}

?>
<article class="<?php echo esc_attr( $card_class ); ?>">
	<div class="ttp-course-card__banner">
		<?php if ( $image_url ) : ?>
			<img class="ttp-course-card__banner-img" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" decoding="async" />
		<?php else : ?>
			<div class="ttp-course-card__banner-img ttp-course-card__banner-img--fallback" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="ttp-course-card__banner-shade" aria-hidden="true"></div>
		<?php if ( ! $has_branded_banner && ( $banner_title !== '' || ! empty( $banner_pills ) ) ) : ?>
			<div class="ttp-course-card__banner-content">
				<?php if ( $banner_title !== '' ) : ?>
					<p class="ttp-course-card__banner-kicker"><?php echo esc_html( $banner_title ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $banner_pills ) ) : ?>
					<div class="ttp-course-card__banner-pills">
						<?php foreach ( $banner_pills as $pill ) : ?>
							<span><?php echo esc_html( $pill ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ( $ttp_is_popular ) : ?>
			<span class="ttp-course-card__ribbon"><?php esc_html_e( 'Most Popular', 'ttp-woocommerce' ); ?></span>
		<?php endif; ?>
	</div>

	<div class="ttp-course-card__body">
		<div class="ttp-course-card__titlebar">
			<h2 class="ttp-course-card__title"><?php echo esc_html( $product->get_name() ); ?></h2>
			<div class="ttp-course-card__rating-block">
				<span class="ttp-course-card__rating">
					<span class="ttp-course-card__rating-star" aria-hidden="true">★</span>
					<?php echo esc_html( $rating ); ?>
				</span>
				<?php if ( $review_label !== '' ) : ?>
					<span class="ttp-course-card__reviews"><?php echo esc_html( $review_label ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( ! empty( $capsules ) ) : ?>
			<div class="ttp-course-card__tags">
				<?php foreach ( $capsules as $cap ) : ?>
					<span><?php echo esc_html( $cap ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ttp-course-card__scroll" tabindex="0" role="region" aria-label="<?php esc_attr_e( 'Course features', 'ttp-woocommerce' ); ?>">
			<?php if ( ! empty( $feature_lines ) ) : ?>
				<ul class="ttp-course-card__list">
					<?php foreach ( $feature_lines as $line ) : ?>
						<li><span class="ttp-course-card__check" aria-hidden="true"></span><?php echo esc_html( $line ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>

	<div class="ttp-course-card__footer">
		<div class="ttp-course-card__pricecol">
			<p class="ttp-course-card__price">
				<?php
				if ( $product->is_on_sale() ) {
					echo wp_kses_post( wc_price( $product->get_sale_price() ) );
				} else {
					echo wp_kses_post( wc_price( $product->get_price() ) );
				}
				?>
			</p>
			<?php if ( $product->is_on_sale() ) : ?>
				<p class="ttp-course-card__was">
					<del><?php echo wp_kses_post( wc_price( $product->get_regular_price() ) ); ?></del>
				</p>
			<?php endif; ?>
			<?php if ( '' !== $dlabel ) : ?>
				<span class="ttp-course-card__save"><?php echo esc_html( $dlabel ); ?></span>
			<?php endif; ?>
		</div>
		<div class="ttp-course-card__actions">
			<button
				type="button"
				class="ttp-course-card__buy ttp-buy-now-btn"
				data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
			>
				<?php esc_html_e( 'Buy Now', 'ttp-woocommerce' ); ?>
			</button>
			<a href="<?php echo esc_url( $details_url ); ?>" class="ttp-course-card__details">
				<?php esc_html_e( 'View Details', 'ttp-woocommerce' ); ?>
			</a>
		</div>
	</div>
</article>
