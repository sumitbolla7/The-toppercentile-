<?php
/**
 * Coupon style admin section
 *
 * @link
 * @since 1.4.7
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Style' ) ) {
	return;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Style_Admin' ) ) {

	/**
	 * Class Wt_Smart_Coupon_Style_Admin
	 *
	 * @since 1.4.7
	 */
	class Wt_Smart_Coupon_Style_Admin extends Wt_Smart_Coupon_Style {

		/**
		 * Module base
		 *
		 * @var string Module base.
		 */
		public $module_base = 'coupon_style';

		/**
		 * Module id
		 *
		 * @var string Module id.
		 */
		public $module_id = '';

		/**
		 * Module id static
		 *
		 * @var string Module id static.
		 */
		public static $module_id_static = '';

		/**
		 * Instance
		 *
		 * @var object|null Class instance.
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_action( 'wp_ajax_wt_sc_customize_save', array( $this, 'customize_save' ), 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

			add_filter( 'wt_sc_plugin_settings_tabhead', array( $this, 'settings_tabhead' ), 1 );

			add_filter( 'wt_sc_plugin_out_settings_form', array( $this, 'out_settings_form' ), 1 );
		}


		/**
		 * Get Instance
		 *
		 * @since 1.4.7
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new Wt_Smart_Coupon_Style_Admin();
			}
			return self::$instance;
		}


		/**
		 * Save customization
		 *
		 * @since 1.4.7
		 */
		public function customize_save() {
			check_ajax_referer( WT_SC_PLUGIN_NAME );

			$out = array(
				'status' => 0,
				'msg'    => __( 'Error', 'thetoppercentile-coupons' ),
			);

			if ( Wt_Smart_Coupon_Security_Helper::check_write_access( $this->module_id, WT_SC_PLUGIN_NAME ) ) {
				$style_settings = isset( $_POST['wt_coupon_styles'] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['wt_coupon_styles'] ), 'text_arr' ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
				Wt_Smart_Coupon::update_settings( $style_settings, $this->module_id );

				/* Save general settings */
				$general_settings = Wt_Smart_Coupon::get_settings();

				/**
				 * Action hook to execute before updating settings.
				 *
				 * @since 1.4.7
				 *
				 * @param array $general_settings General settings array.
				 */
				do_action( 'wt_sc_intl_before_setting_update', $general_settings, '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				$general_settings['display_used_coupons_my_account']    = isset( $_POST['display_used_coupons_my_account'] ) ? sanitize_text_field( wp_unslash( $_POST['display_used_coupons_my_account'] ) ) : false;
				$general_settings['display_expired_coupons_my_account'] = isset( $_POST['display_expired_coupons_my_account'] ) ? sanitize_text_field( wp_unslash( $_POST['display_expired_coupons_my_account'] ) ) : false;
				Wt_Smart_Coupon::update_settings( $general_settings );

				/**
				 * Action hook to execute after updating settings.
				 *
				 * @since 1.4.7
				 *
				 * @param array $general_settings General settings array.
				 */
				do_action( 'wt_sc_intl_after_setting_update', $general_settings, '' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				$out = array(
					'status' => 1,
					'msg'    => __( 'Success', 'thetoppercentile-coupons' ),
				);
			}

			echo wp_json_encode( $out );
			exit();
		}

		/**
		 * Enqueue scripts
		 *
		 * @since 1.4.7
		 */
		public function enqueue_scripts() {
			if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery', 'wp-color-picker', 'jquery-tiptip' ), WEBTOFFEE_SMARTCOUPON_VERSION, false );
				wp_localize_script(
					$this->module_id,
					'wt_sc_customizer_params',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
						'msgs'     => array(
							'settings_error' => __( 'Unable to save settings', 'thetoppercentile-coupons' ),
						),
					)
				);
			}
		}


		/**
		 *  Tab head for plugin settings page
		 *
		 * @since 1.4.7
		 * @param array $arr Tab head array.
		 * @return array Modified tab head array.
		 */
		public function settings_tabhead( $arr ) {
			$added   = 0;
			$out_arr = array( 'wt-sc-' . $this->module_base => __( 'Layouts', 'thetoppercentile-coupons' ) );

			return $out_arr + $arr; // first tab.
		}


		/**
		 *  Customize tab content
		 *
		 *  @since 1.4.7
		 *  @param array $args Array of arguments.
		 */
		public function out_settings_form( $args ) {
			$view_file = plugin_dir_path( __FILE__ ) . 'views/-customize.php';

			/* coupon dummy data for preview */
			$coupon_data_dummy = array(
				'coupon_amount' => '10',
				'coupon_type'   => __( 'Cart discount', 'thetoppercentile-coupons' ),
				'coupon_code'   => __( 'coupon-code', 'thetoppercentile-coupons' ),
				'preview_mode'  => true,
				'coupon_expiry' => '2556100799',
				'coupon_desc'   => __( 'This is a sample coupon description', 'thetoppercentile-coupons' ),
			);

			$view_params = array(
				'coupon_types'         => self::get_coupon_types(),
				'current_coupon_style' => self::get_current_coupon_style(),
				'coupon_styles'        => self::coupon_styles(),
				'coupon_data_dummy'    => $coupon_data_dummy,
				'general_settings'     => Wt_Smart_Coupon::get_settings(),
			);

			Wt_Smart_Coupon_Admin::envelope_settings_tabcontent( 'wt-sc-' . $this->module_base, $view_file, '', $view_params, 0 );
		}
	}
	Wt_Smart_Coupon_Style_Admin::get_instance();
}
