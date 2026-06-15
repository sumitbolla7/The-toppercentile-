<?php
/**
 * Other Solutions
 *
 * @link
 * @since 1.4.4
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Other Solutions class
 *
 * @since 1.4.4
 */
class Wt_Smart_Coupon_Other_Solutions {

	/**
	 * Module id
	 *
	 * @var string
	 */
	public $module_id = '';

	/**
	 * Module id static
	 *
	 * @var string
	 */
	public static $module_id_static = '';

	/**
	 * Module base
	 *
	 * @var string
	 */
	public $module_base = 'other_solutions';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = $this->module_base;
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_sc_plugin_settings_tabhead', array( $this, 'settings_tabhead' ), 1 );
		add_filter( 'wt_sc_plugin_out_settings_form', array( $this, 'out_settings_form' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ), 10, 0 );
	}


	/**
	 *  Tab head for plugin settings page
	 *
	 *  @since 1.4.4
	 *  @param array $arr Array of settings tabs.
	 *  @return array Modified array of settings tabs.
	 */
	public function settings_tabhead( $arr ) {
		$added   = 0;
		$out_arr = array();
		foreach ( $arr as $k => $v ) {
			$out_arr[ $k ] = $v;
			if ( 'wt-sc-freevspro' === $k && 0 === $added ) {
				$out_arr[ 'wt-sc-' . $this->module_base ] = __( 'Other Solutions', 'thetoppercentile-coupons' );
				$added                                    = 1;
			}
		}
		if ( 0 === $added ) {
			$out_arr[ 'wt-sc-' . $this->module_base ] = __( 'Other Solutions', 'thetoppercentile-coupons' );
		}

		return $out_arr;
	}

	/**
	 *  Coupon banner tab content
	 *
	 *  @since 1.4.4
	 */
	public function out_settings_form() {
		$view_file   = plugin_dir_path( __FILE__ ) . 'views/other-solutions-content.php';
		$view_params = array();

		Wt_Smart_Coupon_Admin::envelope_settings_tabcontent( 'wt-sc-' . $this->module_base, $view_file, '', $view_params, 0 );
	}

	/**
	 * Enqueue necessary style for the module
	 */
	public function enqueue_scripts_styles() {
		if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
			wp_enqueue_style( $this->module_id . 'other-solutions', plugin_dir_url( __FILE__ ) . 'assets/css/main.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION, 'all' );
		}
	}
}

new Wt_Smart_Coupon_Other_Solutions();
