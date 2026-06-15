<?php
/**
 * Free vs Pro Comparison
 *
 * @link
 * @since 1.3.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wt_Smart_Coupon_Freevspro
 *
 * @since 1.3.0
 */
class Wt_Smart_Coupon_Freevspro {

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
	 * Module base
	 *
	 * @var string Module base.
	 */
	public $module_base = 'freevspro';

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
		$this->module_id        = $this->module_base;
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_sc_plugin_settings_tabhead', array( $this, 'settings_tabhead' ), 1 );
		add_filter( 'wt_sc_plugin_out_settings_form', array( $this, 'out_settings_form' ), 1 );
	}


	/**
	 *  Get Instance
	 *
	 *  @since 1.4.4
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wt_Smart_Coupon_Freevspro();
		}
		return self::$instance;
	}


	/**
	 *  Coupon banner tab content
	 *
	 *  @since 1.4.4
	 */
	public function out_settings_form() {
		$view_file = plugin_dir_path( __FILE__ ) . 'views/goto-pro.php';

		$view_params = array();

		Wt_Smart_Coupon_Admin::envelope_settings_tabcontent( 'wt-sc-' . $this->module_base, $view_file, '', $view_params, 0 );
	}

	/**
	 *  Tab head for plugin settings page
	 *
	 *  @since 1.4.4
	 *  @param array $arr Array of arguments.
	 *  @return array Modified array of arguments.
	 */
	public function settings_tabhead( $arr ) {
		$added   = 0;
		$out_arr = array();
		foreach ( $arr as $k => $v ) {
			$out_arr[ $k ] = $v;
			if ( 'wbte-sc-develop' === $k && 0 === $added ) {
				$out_arr[ "wt-sc-{$this->module_base}" ] = esc_html__( 'Free vs. Pro', 'thetoppercentile-coupons' );
				$added                                   = 1;
			}
		}
		if ( 0 === $added ) {
			$out_arr[ "wt-sc-{$this->module_base}" ] = esc_html__( 'Free vs. Pro', 'thetoppercentile-coupons' );
		}
		return $out_arr;
	}
}
Wt_Smart_Coupon_Freevspro::get_instance();
