<?php
/**
 * BFCM 2025 Banner.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wt_Bfcm_Twenty_Twenty_Five' ) ) {

	/**
	 * Class Wt_Bfcm_Twenty_Twenty_Five
	 *
	 * This class is responsible for displaying and handling the Black Friday and Cyber Monday CTA banners for 2025.
	 */
	class Wt_Bfcm_Twenty_Twenty_Five { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

		/**
		 * Banner id.
		 *
		 * @var string
		 */
		private $banner_id = 'wt-bfcm-twenty-twenty-five';

		/**
		 * Banner state option name.
		 *
		 * @var string
		 */
		private static $banner_state_option_name = 'wt_bfcm_twenty_twenty_five_banner_state'; // Banner state, 1: Show, 2: Closed by user, 3: Clicked the grab button.

		/**
		 * Banner state.
		 *
		 * @var int
		 */
		private $banner_state = 1;

		/**
		 * Show banner.
		 *
		 * @var bool|null
		 */
		private static $show_banner = null;

		/**
		 * Ajax action name.
		 *
		 * @var string
		 */
		private static $ajax_action_name = 'wt_bcfm_twenty_twenty_five_banner_state';

		/**
		 * Promotion link.
		 *
		 * @var string
		 */
		private static $promotion_link = 'https://www.thetoppercentile.com/plugins/?utm_source=BFCM_promotion&utm_medium=Recommendations&utm_campaign=BFCM-Promotion';

		/**
		 * Banner version.
		 *
		 * @var string
		 */
		private static $banner_version = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$banner_version = WEBTOFFEE_SMARTCOUPON_VERSION; // Plugin version.

			$this->banner_state = get_option( self::$banner_state_option_name ); // Current state of the banner.
			$this->banner_state = absint( false === $this->banner_state ? 1 : $this->banner_state );

			// Enqueue styles.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_and_scripts' ) );

			// Add banner.
			add_action( 'admin_notices', array( $this, 'show_banner' ) );

			// Ajax hook to save banner state.
			add_action( 'wp_ajax_' . self::$ajax_action_name, array( $this, 'update_banner_state' ) );
		}

		/**
		 * To add the banner styles
		 */
		public function enqueue_styles_and_scripts() {
			wp_enqueue_style( $this->banner_id . '-css', plugin_dir_url( __FILE__ ) . 'assets/css/wt-bfcm-twenty-twenty-five.css', array(), self::$banner_version, 'all' );
			$params = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wt_bfcm_twenty_twenty_five_banner_nonce' ),
				'action'   => self::$ajax_action_name,
				'cta_link' => self::$promotion_link,
			);
			wp_enqueue_script( $this->banner_id . '-js', plugin_dir_url( __FILE__ ) . 'assets/js/wt-bfcm-twenty-twenty-five.js', array( 'jquery' ), self::$banner_version, false );
			wp_localize_script( $this->banner_id . '-js', 'wt_bfcm_twenty_twenty_five_banner_js_params', $params );
		}


		/**
		 * Show the banner.
		 */
		public function show_banner() {
			if ( $this->is_show_banner() ) {
				?>
					<div class="wt-bfcm-banner-2025 notice is-dismissible">
						<div class="wt-bfcm-banner-body">
							<div class="wt-bfcm-banner-body-img-section">
								<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/images/black-friday-2025.svg' ); ?>" alt="<?php esc_attr_e( 'Black Friday Cyber Monday 2025', 'thetoppercentile-coupons' ); ?>">
							</div>
							<div class="wt-bfcm-banner-body-info">
								<div class="wt-bfcm-never-miss-this-deal">
									<p><?php echo esc_html__( 'Never Miss This Deal', 'thetoppercentile-coupons' ); ?></p>
								</div>
								<div class="info">
									<p>
									<?php
										printf(
											// translators: 1: Discount text with span wrapper, e.g. <span>30% OFF</span>.
											esc_html__( 'Your Last Chance to Avail %1$s on TheTopPercentile Plugins. Grab the deal before it`s gone!', 'thetoppercentile-coupons' ),
											'<span>30% ' . esc_html__( 'OFF', 'thetoppercentile-coupons' ) . '</span>'
										);
									?>
									</p>
								</div>
								<div class="info-button">
									<a href="<?php echo esc_url( self::$promotion_link ); ?>" class="bfcm_cta_button" target="_blank"><?php echo esc_html__( 'View plugins', 'thetoppercentile-coupons' ); ?> <span class="dashicons dashicons-arrow-right-alt"></span></a>
								</div>
							</div>
						</div>
					</div>
				<?php
			}
		}

		/**
		 * Check if the banner should be shown.
		 *
		 * @return bool
		 */
		public function is_show_banner() {

			// Check if the current date is less than the start date then wait for the start date.
			if ( ! method_exists( 'Wt_Smart_Coupon_Admin', 'is_bfcm_season' ) || ! Wt_Smart_Coupon_Admin::is_bfcm_season() ) {
				self::$show_banner = false;
				return self::$show_banner;
			}

			// Already checked.
			if ( ! is_null( self::$show_banner ) ) {
				return self::$show_banner;
			}

			// Check current banner state.
			if ( 1 !== $this->banner_state ) {
				self::$show_banner = false;
				return self::$show_banner;
			}

			// Check screens.
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			/**
			 *  Pages to show this black friday and cyber monday banner for 2025.
			 *
			 *  @since 1.1.0
			 *  @param  string[]    Default screen ids
			 */
			$screens_to_show = (array) apply_filters( 'wt_bfcm_banner_screens', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			self::$show_banner = in_array( $screen_id, $screens_to_show, true );
			return self::$show_banner;
		}

		/**
		 *  Update banner state ajax hook
		 */
		public function update_banner_state() {
			check_ajax_referer( 'wt_bfcm_twenty_twenty_five_banner_nonce' );
			if ( isset( $_POST['wt_bfcm_twenty_twenty_five_banner_action_type'] ) ) {

				$action_type = absint( sanitize_text_field( wp_unslash( $_POST['wt_bfcm_twenty_twenty_five_banner_action_type'] ) ) );
				if ( in_array( $action_type, array( 2, 3 ), true ) ) {
					update_option( self::$banner_state_option_name, $action_type );
				}
			}
			exit();
		}
	}

	new Wt_Bfcm_Twenty_Twenty_Five();
}