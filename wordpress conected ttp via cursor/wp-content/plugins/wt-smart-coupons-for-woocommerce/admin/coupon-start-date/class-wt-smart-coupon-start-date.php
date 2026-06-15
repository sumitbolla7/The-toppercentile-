<?php
/**
 * The file that defines the Start date option for coupon
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! class_exists( 'WT_Smart_Coupon_Start_Date' ) ) {

	/**
	 * Implement Start date option for coupon
	 *
	 * @link       http://www.thetoppercentile.com
	 * @since      1.2.2
	 *
	 * @package    Wt_Smart_Coupon
	 * @subpackage Wt_Smart_Coupon/admin
	 */
	class WT_Smart_Coupon_Start_Date {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'woocommerce_coupon_options', array( $this, 'add_coupon_start_date_field' ), 9 );
			add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'save_coupon_start_date' ), 11 );
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'wt_woocommerce_coupon_is_valid' ), 10, 2 );

			add_action( 'wt_sc_before_bogo_coupon_save', array( $this, 'set_start_expiry_data_for_bogo_coupon' ), 10, 2 );
		}


		/**
		 * Add coupon start date field
		 *
		 * @since 1.2.2
		 * @param int $coupon_id Coupon ID.
		 */
		public function add_coupon_start_date_field( $coupon_id ) {

			$start_date = get_post_meta( $coupon_id, '_wt_coupon_start_date', true );

			woocommerce_wp_text_input(
				array(
					'id'                => '_wt_coupon_start_date',
					'value'             => esc_attr( $start_date ),
					'label'             => __( 'Coupon start date', 'thetoppercentile-coupons' ),
					'placeholder'       => 'YYYY-MM-DD',
					'description'       => '',
					'class'             => 'date-picker',
					'custom_attributes' => array(
						/**
						 * Filter the date input pattern.
						 *
						 * @since 1.2.2
						 * @param string $pattern The date input pattern.
						 * @return string The filtered date input pattern.
						 */
						'pattern' => apply_filters( 'woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					),
				)
			);

			?>
			<script type="text/javascript">
				jQuery( document ).ready( function(){
					/* Move start date just above expiry date */
					jQuery( '._wt_coupon_start_date_field' ).insertBefore( '.expiry_date_field' );
				} );
			</script>
			<?php
		}


		/**
		 * Save coupon start date
		 *
		 * @since 1.2.2
		 * @param int $post_id Post ID.
		 */
		public function save_coupon_start_date( $post_id ) {
			if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
			}

			$start_date = ( isset( $_POST['_wt_coupon_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['_wt_coupon_start_date'] ) ) : '' );
			update_post_meta( $post_id, '_wt_coupon_start_date', $start_date );
		}


		/**
		 * Check if coupon is valid
		 *
		 * @since 1.2.2
		 * @param bool   $valid Coupon validity.
		 * @param object $coupon Coupon object.
		 * @return bool Coupon validity.
		 * @throws Exception If coupon is not valid.
		 */
		public function wt_woocommerce_coupon_is_valid( $valid, $coupon ) {
			$coupon_id = $coupon->get_id();

			if ( ! metadata_exists( 'post', $coupon_id, '_wt_coupon_start_date' ) ) {
				return $valid;
			}

			$start_date           = get_post_meta( $coupon_id, '_wt_coupon_start_date', true );
			$start_date_timestamp = 0;

			if ( '' !== $start_date ) {
				$start_date_timestamp = Wt_Smart_Coupon_Common::get_date_timestamp( $start_date, false );
			}

			if ( 0 < $start_date_timestamp && time() < $start_date_timestamp ) {
				$valid = false;
				if ( ! $valid ) {
					throw new Exception(
						// translators: %s: start date.
						sprintf( esc_html__( 'Sorry, this coupon is only available after %s', 'thetoppercentile-coupons' ), esc_html( $start_date ) ),
						109
					);
				}
			}

			return $valid;
		}

		/**
		 * Save start and expiry data for BOGO coupon
		 *
		 * @since 2.2.0
		 * @param int   $post_id    Post id.
		 * @param array $post_data  Array POST data.
		 */
		public function set_start_expiry_data_for_bogo_coupon( $post_id, $post_data ) {

			if ( ! $post_id || ! is_array( $post_data ) || empty( $post_data ) ) {
				return;
			}

			if ( isset( $post_data['_wt_coupon_start_date'] ) && '' !== $post_data['_wt_coupon_start_date'] ) {
				$start_date = Wt_Smart_Coupon_Security_Helper::sanitize_item( $post_data['_wt_coupon_start_date'] );
				update_post_meta( $post_id, '_wt_coupon_start_date', $start_date );

			} else {
				update_post_meta( $post_id, '_wt_coupon_start_date', '' );
			}

			if ( isset( $post_data['expiry_date'] ) && '' !== $post_data['expiry_date'] ) {
				$expiry_date = Wt_Smart_Coupon_Security_Helper::sanitize_item( $post_data['expiry_date'] );
				update_post_meta( $post_id, 'date_expires', Wt_Smart_Coupon_Admin::wt_sc_get_date_prop( $expiry_date )->getTimestamp() );

			} else {
				update_post_meta( $post_id, 'date_expires', '' );
			}
		}
	}

	new WT_Smart_Coupon_Start_Date();
}