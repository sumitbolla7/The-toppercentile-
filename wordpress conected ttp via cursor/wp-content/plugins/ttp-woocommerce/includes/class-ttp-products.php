<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_Products {

	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'add_tcy_fields' ] );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_tcy_fields' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

		add_action( 'woocommerce_before_add_to_cart_form', [ $this, 'show_validity' ], 10 );
		add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'open_button_row' ], 8 );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'buy_now_button' ], 10 );
		add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'close_button_row' ], 11 );

		add_filter( 'woocommerce_product_get_image_id', [ $this, 'use_enroll_card_image_as_product_image' ], 10, 2 );
	}

	/**
	 * @param string $hook Admin hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->id ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'ttp-product-admin',
			TTP_URL . 'assets/js/ttp-product-admin.js',
			[ 'jquery' ],
			TTP_VERSION,
			true
		);
	}

	public function add_tcy_fields() {
		global $post;
		$image_id  = (int) get_post_meta( $post->ID, '_ttp_enroll_card_image_id', true );
		$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'medium' ) : '';

		echo '<div class="options_group"><h4 style="padding-left:12px;color:#e8540a;">TCY Platform Settings</h4>';
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_tcy_course_id',
				'label'       => 'TCY Course ID',
				'desc_tip'    => true,
				'description' => 'course_id in API (e.g. 90069). Required for checkout + shows this product on /exam/ Enrol page automatically.',
				'value'       => get_post_meta( $post->ID, '_ttp_tcy_course_id', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_tcy_category_id',
				'label'       => 'TCY category_id (API)',
				'desc_tip'    => true,
				'description' => 'Sent as category_id in register/add_course — use 100000 (MBA Entrance) for all plans.',
				'value'       => get_post_meta( $post->ID, '_ttp_tcy_category_id', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_tcy_entrance_category_id',
				'label'       => 'MBA Entrance category',
				'desc_tip'    => true,
				'description' => 'Usually 100000 — same value as category_id in the API.',
				'value'       => get_post_meta( $post->ID, '_ttp_tcy_entrance_category_id', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_validity',
				'label'       => 'Course Validity',
				'desc_tip'    => true,
				'description' => 'e.g. 12 Months',
				'value'       => get_post_meta( $post->ID, '_ttp_validity', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_coupon_timer',
				'label'       => 'Discount Timer (minutes)',
				'desc_tip'    => true,
				'description' => 'Countdown minutes',
				'value'       => get_post_meta( $post->ID, '_ttp_coupon_timer', true ),
			]
		);
		woocommerce_wp_text_input(
			[
				'id'          => '_ttp_course_badge_label',
				'label'       => 'Course card badge (optional)',
				'desc_tip'    => true,
				'description' => 'Short label shown under the title. Falls back to the first product category name.',
				'value'       => get_post_meta( $post->ID, '_ttp_course_badge_label', true ),
			]
		);
		woocommerce_wp_textarea_input(
			[
				'id'            => '_ttp_course_feature_grid',
				'label'         => 'Course card feature grid',
				'description'   => 'Up to four rows for the pricing card. Each line: Left title | Left description | Right title | Right description. Example: Live Classes | Daily live sessions | Mock Tests | 35+ CET mocks',
				'value'         => get_post_meta( $post->ID, '_ttp_course_feature_grid', true ),
				'wrapper_class' => 'form-field-wide',
			]
		);
		echo '</div>';

		echo '<div class="options_group"><h4 style="padding-left:12px;color:#e8540a;">' . esc_html__( 'Enrol Now page card', 'ttp-woocommerce' ) . '</h4>';
		woocommerce_wp_textarea_input(
			[
				'id'            => '_ttp_enroll_capsules',
				'label'         => __( 'Card capsules (tags)', 'ttp-woocommerce' ),
				'description'   => __( 'One per line or comma-separated — e.g. Live Sessions, Mock GD and PI. Shown under the title on /exam/.', 'ttp-woocommerce' ),
				'value'         => get_post_meta( $post->ID, '_ttp_enroll_capsules', true ),
				'wrapper_class' => 'form-field-wide',
			]
		);
		woocommerce_wp_textarea_input(
			[
				'id'            => '_ttp_enroll_card_bullets',
				'label'         => __( 'Card description bullets', 'ttp-woocommerce' ),
				'description'   => __( 'One bullet per line — scrollable list on the Enrol Now card and used as short description when saved.', 'ttp-woocommerce' ),
				'value'         => get_post_meta( $post->ID, '_ttp_enroll_card_bullets', true ),
				'wrapper_class' => 'form-field-wide',
				'rows'          => 10,
			]
		);
		echo '</div>';

		echo '<div class="options_group ttp-enroll-card-image-field">';
		echo '<p class="form-field" style="padding:0 12px;">';
		echo '<label style="display:block;font-weight:600;margin-bottom:8px;">' . esc_html__( 'Enrol Now card image', 'ttp-woocommerce' ) . '</label>';
		echo '<span class="description" style="display:block;margin-bottom:10px;">' . esc_html__( 'Shown on the Enrol Now page and as the main image on the product Details page.', 'ttp-woocommerce' ) . '</span>';
		echo '<div class="ttp-enroll-image-preview" style="margin-bottom:10px;">';
		if ( $image_url ) {
			echo '<img src="' . esc_url( $image_url ) . '" alt="" style="max-width:280px;height:auto;border-radius:8px;" />';
		}
		echo '</div>';
		echo '<input type="hidden" name="_ttp_enroll_card_image_id" value="' . esc_attr( (string) $image_id ) . '" />';
		echo '<button type="button" class="button ttp-enroll-image-upload">' . esc_html__( 'Upload / select image', 'ttp-woocommerce' ) . '</button> ';
		echo '<button type="button" class="button ttp-enroll-image-remove" ' . ( $image_id ? '' : 'style="display:none;"' ) . '>' . esc_html__( 'Remove image', 'ttp-woocommerce' ) . '</button>';
		echo '</p></div>';
	}

	public function save_tcy_fields( $post_id ) {
		foreach (
			[
				'_ttp_tcy_course_id',
				'_ttp_tcy_category_id',
				'_ttp_tcy_entrance_category_id',
				'_ttp_validity',
				'_ttp_coupon_timer',
				'_ttp_course_badge_label',
			] as $f
		) {
			if ( isset( $_POST[ $f ] ) ) {
				update_post_meta( $post_id, $f, sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) );
			}
		}
		if ( isset( $_POST['_ttp_course_feature_grid'] ) ) {
			update_post_meta( $post_id, '_ttp_course_feature_grid', sanitize_textarea_field( wp_unslash( $_POST['_ttp_course_feature_grid'] ) ) );
		}
		if ( isset( $_POST['_ttp_enroll_capsules'] ) ) {
			update_post_meta( $post_id, '_ttp_enroll_capsules', sanitize_textarea_field( wp_unslash( $_POST['_ttp_enroll_capsules'] ) ) );
		}
		if ( isset( $_POST['_ttp_enroll_card_bullets'] ) ) {
			$bullets_raw = sanitize_textarea_field( wp_unslash( $_POST['_ttp_enroll_card_bullets'] ) );
			update_post_meta( $post_id, '_ttp_enroll_card_bullets', $bullets_raw );
			if ( $bullets_raw !== '' && function_exists( 'ttp_enroll_parse_lines_meta' ) && class_exists( 'TTP_Catalog_Seed' ) ) {
				$lines = ttp_enroll_parse_lines_meta( $bullets_raw );
				if ( ! empty( $lines ) ) {
					$product = wc_get_product( $post_id );
					if ( $product instanceof WC_Product ) {
						$product->set_short_description( TTP_Catalog_Seed::build_short_description_html( $lines ) );
						$product->save();
					}
				}
			}
		}

		if ( isset( $_POST['_ttp_enroll_card_image_id'] ) ) {
			$image_id = absint( wp_unslash( $_POST['_ttp_enroll_card_image_id'] ) );
			if ( $image_id > 0 && ! wp_attachment_is_image( $image_id ) ) {
				$image_id = 0;
			}
			update_post_meta( $post_id, '_ttp_enroll_card_image_id', $image_id );
			if ( $image_id > 0 ) {
				set_post_thumbnail( $post_id, $image_id );
			}
		}
	}

	/**
	 * Product page + shop use the same image as Enrol Now cards.
	 *
	 * @param int|string $image_id Attachment ID.
	 * @param WC_Product $product  Product.
	 * @return int|string
	 */
	public function use_enroll_card_image_as_product_image( $image_id, $product ) {
		if ( ! $product instanceof WC_Product ) {
			return $image_id;
		}
		$custom = (int) get_post_meta( $product->get_id(), '_ttp_enroll_card_image_id', true );
		if ( $custom > 0 && wp_attachment_is_image( $custom ) ) {
			return $custom;
		}
		return $image_id;
	}

	public function show_validity() {
		global $product;
		if ( ! $product ) {
			return;
		}

		$validity = get_post_meta( $product->get_id(), '_ttp_validity', true );
		$timer    = get_post_meta( $product->get_id(), '_ttp_coupon_timer', true );

		if ( $timer ) {
			echo '<div class="ttp-timer-wrap">'
				. '<strong>Limited Time Offer</strong> &mdash; Expires in: '
				. '<span class="ttp-countdown" data-minutes="' . esc_attr( $timer ) . '">--:--</span>'
				. '</div>';
		}

		if ( $validity ) {
			echo '<div class="ttp-validity-wrap">'
				. '<strong>Course Validity:</strong> ' . esc_html( $validity )
				. '</div>';
		}
	}

	public function open_button_row() {
		echo '<div class="ttp-product-cart-buttons">';
	}

	public function close_button_row() {
		echo '</div>';
	}

	public function buy_now_button() {
		global $product;
		if ( ! $product ) {
			return;
		}
		echo '<a href="#" class="ttp-buy-now-btn button alt" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html__( 'Buy Now', 'ttp-woocommerce' ) . '</a>';
	}
}
