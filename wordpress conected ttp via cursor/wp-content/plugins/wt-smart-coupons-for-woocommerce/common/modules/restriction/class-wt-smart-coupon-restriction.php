<?php
/**
 * Coupon usage restriction common section
 *
 * @link
 * @since 1.4.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon usage restriction common section
 *
 * @link
 * @since 1.4.0
 *
 * @package  Wt_Smart_Coupon
 */
class Wt_Smart_Coupon_Restriction {

	/**
	 * Module base
	 *
	 * @var string
	 */
	public $module_base = 'coupon_restriction';

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
	 * Instance
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Meta array
	 *
	 * @var array
	 */
	public static $meta_arr = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		self::$meta_arr = array(
			'_wt_category_condition'                  => array(
				'default' => 'or', /* default value */
				'type'    => 'text', /* value type */
			),
			'_wt_enable_product_category_restriction' => array(
				'default' => 'yes',
				'type'    => 'text',
			),
			'_wt_product_condition'                   => array(
				'default' => 'or',
				'type'    => 'text',
			),
			'_wt_use_individual_min_max'              => array(
				'default' => 'no',
				'type'    => 'text',
			),
			'_wt_min_matching_product_qty'            => array(
				'default' => '',
				'type'    => 'absint',
			),
			'_wt_max_matching_product_qty'            => array(
				'default' => '',
				'type'    => 'absint',
			),
			'_wt_min_matching_product_subtotal'       => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wt_max_matching_product_subtotal'       => array(
				'default' => '',
				'type'    => 'float',
			),
			'_wt_sc_coupon_products'                  => array(
				'default' => array(),
				'type'    => 'text_arr',
			),
			'_wt_sc_coupon_categories'                => array(
				'default' => array(),
				'type'    => 'text_arr',
			),
		);
	}

	/**
	 * Get Instance
	 *
	 * @return object Instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wt_Smart_Coupon_Restriction();
		}
		return self::$instance;
	}

	/**
	 * Prepare meta value, If meta not exists, use default value
	 *
	 * @since 1.4.0
	 *
	 * @param int    $post_id Post ID.
	 * @param string $meta_key Meta key.
	 * @param string $default_val Default value.
	 * @return mixed Meta value.
	 */
	public static function get_coupon_meta_value( $post_id, $meta_key, $default_val = '' ) {
		$default_vl = ( isset( self::$meta_arr[ $meta_key ] ) && isset( self::$meta_arr[ $meta_key ]['default'] ) ? self::$meta_arr[ $meta_key ]['default'] : $default_val );
		return ( metadata_exists( 'post', $post_id, $meta_key ) ? get_post_meta( $post_id, $meta_key, true ) : $default_vl );
	}

	/**
	 * Prepare items data
	 *
	 * @param array $item_ids Item IDs.
	 * @param array $wt_sc_items_data Items data.
	 * @return array Items data.
	 */
	public static function prepare_items_data( $item_ids, $wt_sc_items_data ) {
		$dummy_min_max = self::get_dummy_min_max();
		$items_data    = array();
		if ( ! empty( $item_ids ) ) {
			$min_max_dummy = array_fill( 0, count( $item_ids ), $dummy_min_max );
			$items_data    = array_combine( $item_ids, $min_max_dummy );
		}

		if ( ! empty( $wt_sc_items_data ) ) {
			foreach ( $items_data as $item_id => $item_data ) {
				$items_data[ $item_id ] = ( isset( $wt_sc_items_data[ $item_id ] ) ? $wt_sc_items_data[ $item_id ] : $item_data );
			}
		}

		return $items_data;
	}

	/**
	 * Get dummy min max
	 *
	 * @return array Dummy min max.
	 */
	public static function get_dummy_min_max() {
		return array(
			'min' => '',
			'max' => '',
		);
	}
}
Wt_Smart_Coupon_Restriction::get_instance();
