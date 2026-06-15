<?php
/**
 * Coupon shortcode admin section.
 *
 * @link
 * @since 1.3.7
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WT_Smart_Coupon_Shortcode' ) ) {
	return;
}

/**
 * Class WT_Smart_Coupon_Shortcode_Admin
 *
 * @since 1.3.7
 */
class WT_Smart_Coupon_Shortcode_Admin extends WT_Smart_Coupon_Shortcode {

	/**
	 * Module base
	 *
	 * @var string Module base.
	 */
	public $module_base = 'coupon_shortcode';

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

		add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'add_short_code_column' ), 10, 1 );
		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'add_shortcode_column_content' ), 10, 2 );
	}

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new WT_Smart_Coupon_Shortcode_Admin();
		}
		return self::$instance;
	}

	/**
	 * Display shortcode column head in admin coupons table
	 *
	 * @since 1.3.7
	 * @param array $defaults Default columns.
	 * @return array Modified columns.
	 */
	public function add_short_code_column( $defaults ) {
		$defaults['wt_short_code'] = __( 'Shortcode', 'thetoppercentile-coupons' );

		return $defaults;
	}

	/**
	 * Display shortcode column data in admin coupons table
	 *
	 * @since 1.3.7
	 * @param string $column_name Column name.
	 * @param int    $post_ID Post ID.
	 */
	public function add_shortcode_column_content( $column_name, $post_ID ) {
		if ( 'wt_short_code' === $column_name ) {
			echo esc_html( '[wt-smart-coupon id=' . absint( $post_ID ) . ']' );
		}
	}
}
WT_Smart_Coupon_Shortcode_Admin::get_instance();
