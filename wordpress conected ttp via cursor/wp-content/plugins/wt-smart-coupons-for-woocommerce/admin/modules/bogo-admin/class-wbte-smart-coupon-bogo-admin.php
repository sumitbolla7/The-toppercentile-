<?php
/**
 * BOGO admin section
 *
 * @since 2.0.0
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wbte_Smart_Coupon_Bogo_Common' ) ) {
	return;
}

/**
 * The admin-facing functionality of new BOGO module.
 *
 * @since 2.0.0
 */
class Wbte_Smart_Coupon_Bogo_Admin extends Wbte_Smart_Coupon_Bogo_Common {

	/**
	 *  Module name
	 *
	 *  @var string $module_base module name
	 */
	public $module_base = 'bogo';

	/**
	 *  Module Id
	 *
	 *  @var string $module_id module id
	 */
	public $module_id = '';

	/**
	 *  Module static id
	 *
	 *  @var string $module_id_static module static id
	 */
	public static $module_id_static = '';

	/**
	 *  Class instance
	 *
	 *  @var null|object $instance instance of class or null
	 */
	private static $instance = null;

	/**
	 * Bogo page name
	 *
	 * @var string $bogo_page_name Name of the bogo page.
	 */
	public static $bogo_page_name = WT_SC_PLUGIN_NAME . '_bogo';

	/**
	 * Constructor function of the class
	 * Add submenu for new BOGO
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_sc_admin_menu', array( $this, 'add_admin_pages' ), 9 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_head', array( $this, 'remove_all_notices_on_bogo_page' ) );

		add_action( 'admin_head', array( $this, 'add_css_for_smart_coupon_menu_highlight' ) );

		/**
		 *  Ajax hooks
		 */
		$this->hooks_ajax();

		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'prevent_old_bogo_from_publishing' ), 11 );

		add_action( 'admin_enqueue_scripts', array( $this, 'alter_publish_button_for_old_bogo' ) );

		add_filter( 'woocommerce_coupon_get_code', array( $this, 'alter_new_bogo_title_on_auto_coupons_listing' ), 10, 2 );

		add_action( 'pre_get_posts', array( $this, 'hide_bogo_coupons_from_coupon_listing' ) );

		add_action( 'admin_init', array( $this, 'block_bogo_coupon_edit_page' ) );

		/** Update total coupon counts after hiding bogo coupons */
		add_filter( 'wp_count_posts', array( $this, 'alter_total_coupon_count' ), 11, 3 );

		/**  Hide `Mine` section. */
		add_filter( 'views_edit-shop_coupon', array( $this, 'hide_mine_post_count' ) );

		add_filter( 'woocommerce_order_item_get_code', array( $this, 'bogo_title_instead_code_on_order_detail_page' ) );

		add_action( 'wbte_sc_bogo_new_coupon_created', array( $this, 'delete_bogo_ids_transient' ), 10, 2 );

		add_action( 'wbte_sc_bogo_edit_step1_content', array( $this, 'bogo_edit_step1_content' ) );

		add_action( 'wbte_sc_bogo_edit_step2_content', array( $this, 'bogo_edit_step2_content' ) );

		add_action( 'wbte_sc_bogo_edit_step3_content', array( $this, 'bogo_edit_step3_content' ) );

		add_action( 'wbte_sc_bogo_bulk_container_content', array( $this, 'bogo_bulk_container_content' ) );

		add_action( 'wbte_sc_bogo_process_shop_coupon_meta', array( $this, 'handle_bogo_process_meta' ), 10 );
	}

	/**
	 * Get Instance
	 *
	 * @since 2.0.0
	 * @return object Class instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Wbte_Smart_Coupon_Bogo_Admin();
		}
		return self::$instance;
	}

	/**
	 *  Admin page
	 *
	 *  @since 2.0.0
	 *  @param  array $menus Submenus of Smart coupon.
	 *  @return array        new submeny array.
	 */
	public function add_admin_pages( $menus ) {

		$out               = array();
		$menu_capabilities = Wt_Smart_Coupon_Security_Helper::menu_items_capability();

		foreach ( $menus as $menu ) {
			$out[] = $menu;
			if ( 'submenu' === $menu[0] && 'post-new.php?post_type=shop_coupon' === $menu[5] ) {
				$out[] = array(
					'submenu',
					WT_SC_PLUGIN_NAME,
					__( 'BOGO', 'thetoppercentile-coupons' ),
					__( 'BOGO', 'thetoppercentile-coupons' ),
					isset( $menu_capabilities['bogo'] ) ? $menu_capabilities['bogo'] : 'edit_shop_coupons',
					$this->module_id,
					array( $this, 'bogo_page_content' ),
				);
			}
		}
		return $out;
	}

	/**
	 *  Page content for BOGO submenu
	 *
	 *  @since 2.0.0
	 */
	public function bogo_page_content() {
		$admin_img_path = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';
		include_once plugin_dir_path( __FILE__ ) . 'views/-bogo-main.php';
	}

	/**
	 *  Enqueue style and javascript file for new BOGO admin page
	 *
	 *  @since 2.0.0
	 */
	public function enqueue_scripts() {

		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === self::$bogo_page_name ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			wp_enqueue_style( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION );

			if ( is_rtl() ) {
				wp_enqueue_style( $this->module_id . '-rtl', plugin_dir_url( __FILE__ ) . 'assets/css/style-rtl.css', array(), WEBTOFFEE_SMARTCOUPON_VERSION );
			}

			wp_enqueue_script( $this->module_id, plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array( 'jquery', 'jquery-tiptip', 'clipboard' ), WEBTOFFEE_SMARTCOUPON_VERSION, false );

			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );

			wp_localize_script(
				$this->module_id,
				'wbte_sc_bogo_params',
				self::get_bogo_params()
			);
		}
	}

	/**
	 * Remove all notices on BOGO page.
	 *
	 * @since 2.0.0
	 */
	public function remove_all_notices_on_bogo_page() {
		if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === self::$bogo_page_name ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// Remove all admin notices.
			if ( method_exists( 'Wt_Smart_Coupon_Admin', 'is_bfcm_season' ) && ! Wt_Smart_Coupon_Admin::is_bfcm_season() ) {
				remove_all_actions( 'admin_notices' );
			}
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Add a highlight to the BOGO menu. Remove the highlight once the BOGO page was visited.
	 *
	 * @since 2.0.0
	 */
	public function add_css_for_smart_coupon_menu_highlight() {
		if ( 1 !== absint( get_option( 'wbte_sc_bogo_menu_higlight_is_removed', 0 ) ) ) {
			$print_css = true;

			// Hide the highlight once the BOGO page was visited.
			if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === self::$bogo_page_name ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_option( 'wbte_sc_bogo_menu_higlight_is_removed', 1 );
				$print_css = false;
			}

			if ( $print_css ) {
				$settings_page_url = 'admin.php?page=' . self::$bogo_page_name;
				?>
				<style type="text/css">
					li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]{ position:relative; }
					a.toplevel_page_wt-smart-coupon-for-woo::after, li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ content: "."; position:absolute; color:#d63638; font-size:45px; font-weight:bold; margin-top:-5px; top:0px; line-height:0px; }  

					<?php if ( is_rtl() ) { ?>
						a.toplevel_page_wt-smart-coupon-for-woo::after, li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ left: 10px; } 
						li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ left: 10px; }
					<?php } else { ?>
						a.toplevel_page_wt-smart-coupon-for-woo::after, li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ right: 10px; } 
						li.toplevel_page_wt-smart-coupon-for-woo ul.wp-submenu li a[href="<?php echo esc_url( $settings_page_url ); ?>"]::after{ left: 55px; }
					<?php } ?>
				</style>
				<?php
			}
		}
	}

	/**
	 *  This function lists all ajax hooks.
	 *
	 *  @since 2.0.0
	 */
	public function hooks_ajax() {

		add_action( 'wp_ajax_wbte_sc_switch_to_new_bogo', array( $this, 'switch_to_new_bogo' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_general_settings', array( $this, 'general_settings_submit' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_add_new', array( $this, 'add_new_bogo' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_coupon_save', array( $this, 'bogo_coupon_save' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_delete_on_listing', array( $this, 'bogo_coupon_delete_on_listing' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_single_duplicate', array( $this, 'bogo_coupon_single_duplicate' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_listing_update_status_on_toggle', array( $this, 'update_status_on_toggle' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_multiple_enable', array( $this, 'bogo_coupon_multiple_enable' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_multiple_disable', array( $this, 'bogo_coupon_multiple_disable' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_delete_multiple', array( $this, 'bogo_coupon_dlt_multiple' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_restore_on_listing', array( $this, 'bogo_coupon_single_restore' ) );

		add_action( 'wp_ajax_wbte_sc_trash_bogo_count_ajax', array( $this, 'trash_bogo_count_ajax' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_perm_dlt_on_listing', array( $this, 'bogo_coupon_single_perm_dlt' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_restore_multiple', array( $this, 'bogo_coupon_multiple_restore' ) );

		add_action( 'wp_ajax_wbte_sc_bogo_perm_dlt_multiple', array( $this, 'bogo_coupon_multiple_dlt_permenantly' ) );

		add_action( 'wp_ajax_wbte_sc_get_auto_offer_code', array( $this, 'get_auto_offer_code' ) );
	}

	/**
	 * Ajax function to switch to new bogo.
	 *
	 * @since 2.0.0
	 */
	public function switch_to_new_bogo() {
		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );
		update_option( 'wbte_sc_new_bogo_actvated', true );
		$this->change_old_bogo_status_to_draft();
		echo true;
		die();
	}

	/**
	 * Change old bogo status to draft when activating new bogo.
	 *
	 * @since 2.0.0
	 */
	private static function change_old_bogo_status_to_draft() {
		global $wpdb;
		$lookup_table        = Wt_Smart_Coupon::get_lookup_table_name();
		$old_bogo_coupon_ids = $wpdb->get_col( $wpdb->prepare( "SELECT coupon_id FROM {$lookup_table} WHERE discount_type = %s AND post_status = %s", 'wt_sc_bogo', 'publish' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Only used when activating new bogo, so no need to cache.

		if ( ! empty( $old_bogo_coupon_ids ) ) {
			foreach ( $old_bogo_coupon_ids as $coupon_id ) {
				$post_data = array(
					'ID'          => $coupon_id,
					'post_status' => 'draft',
				);
				wp_update_post( $post_data );
			}
		}
		update_option( 'wbte_sc_old_bogo_ids', $old_bogo_coupon_ids );
	}

	/**
	 * Transient cache key prefix for BOGO counts.
	 *
	 * @since 2.2.8
	 * @var string
	 */
	const BOGO_COUNT_CACHE_PREFIX = 'wbte_sc_bogo_count_';

	/**
	 * To get the total count of BOGO coupons.
	 * If $args has 'is_trash' set to true, it will return the count of trashed coupons, otherwise it will return the count of publish and draft BOGO coupons. If $args is empty, it will return the count of all BOGO coupons.
	 * IDE showing 0 references to this method, but it is used.
	 *
	 * @since 2.0.0
	 * @param  array $args Optional arguments to filter the count.
	 * @return int         Total count of BOGO coupons.
	 */
	public static function get_total_bogo_counts( $args = array() ) {
		$cache_key = self::get_bogo_count_cache_key( $args );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;
		$lookup_table    = Wt_Smart_Coupon::get_lookup_table_name();
		$sql_placeholder = array( self::$bogo_coupon_type_name );
		$status          = ( isset( $args['is_trash'] ) && true === $args['is_trash'] ) ? "( 'trash' )" : "( 'publish', 'draft' )";
		$sql             = "SELECT COUNT(id) 
				FROM {$lookup_table}
				WHERE discount_type = %s";

		if ( ! empty( $args ) ) {
			$sql .= " AND post_status IN {$status}";
		}
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $sql_placeholder ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

		set_transient( $cache_key, $count, HOUR_IN_SECONDS );

		return $count;
	}

	/**
	 * Get the cache key for BOGO count transients.
	 *
	 * @since 2.2.8
	 * @param  array $args Optional arguments passed to get_total_bogo_counts().
	 * @return string      Transient key.
	 */
	private static function get_bogo_count_cache_key( $args = array() ) {
		$suffix = 'all';
		if ( ! empty( $args ) ) {
			$suffix = ( isset( $args['is_trash'] ) && true === $args['is_trash'] ) ? 'trash' : 'active';
		}
		return self::BOGO_COUNT_CACHE_PREFIX . $suffix;
	}

	/**
	 * Delete cached BOGO counts. Call when BOGO coupons are created, updated, or trashed.
	 *
	 * @since 2.2.8
	 */
	private static function delete_bogo_count_cache() {
		delete_transient( self::BOGO_COUNT_CACHE_PREFIX . 'all' );
		delete_transient( self::BOGO_COUNT_CACHE_PREFIX . 'trash' );
		delete_transient( self::BOGO_COUNT_CACHE_PREFIX . 'active' );
	}

	/**
	 * Ajax function to save BOGO general settings.
	 *
	 * @since 2.0.0
	 */
	public static function general_settings_submit() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['data'] ) ) {

			parse_str( wp_unslash( $_POST['data'] ), $result ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- It's sanitizing afterwards before writing to the database.

			foreach ( $result as $key => $value ) {
				$result[ $key ] = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_textarea_field( $value );
			}

			update_option( 'wbte_sc_bogo_general_settings', $result );

			$return = array(
				'status' => true,
				'msg'    => __( 'General settings updated successfully!', 'thetoppercentile-coupons' ),
			);
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to add new BOGO coupon.
	 * If default BOGO is selected, then add predefined settings. BOGO will be AUTO coupon, in draft status. Coupon code will be created by slugifying the coupon name.
	 *
	 * @since 2.0.0
	 */
	public function add_new_bogo() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
			'id'     => 0,
			'url'    => '',
		);
		if ( isset( $_POST['data'] ) ) {

			parse_str( wp_unslash( $_POST['data'] ), $result ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- It's sanitizing afterwards before writing to the database.
			$result = Wt_Smart_Coupon_Security_Helper::sanitize_item( $result, 'text_arr' );

			$predefined_settings = array(
				// Buy 1 item, get 1(specific) @ 50% off. Buy 2 items, get 2(specific) @ 50% off — and so on.
				'default_1' => array(
					'wbte_sc_bogo_triggers_when' => 'wbte_sc_bogo_triggers_qty',
					'_wbte_sc_bogo_min_qty'      => 1,
					'wbte_sc_bogo_customer_gets_discount_type' => 'wbte_sc_bogo_customer_gets_with_perc_discount',
					'wbte_sc_bogo_customer_gets_discount_perc' => 50,
					'wbte_sc_bogo_apply_offer'   => 'wbte_sc_bogo_apply_repeatedly',
				),
				// Buy 2 item, get 1(specific) for free.
				'default_2' => array(
					'wbte_sc_bogo_triggers_when' => 'wbte_sc_bogo_triggers_qty',
					'_wbte_sc_bogo_min_qty'      => 2,
				),
				// Spend X(here 100) Get a free gift.
				'default_3' => array(
					'wbte_sc_bogo_triggers_when' => 'wbte_sc_bogo_triggers_subtotal',
					'_wbte_sc_bogo_min_amount'   => 100,
				),
				'custom'    => array(
					'wbte_sc_bogo_type' => 'wbte_sc_bogo_bxgx',
				),
			);

			$coupon_code = self::slugify_coupon_code( sanitize_text_field( $result['wbte_sc_bogo_coupon_name'] ) );
			$c_title     = $coupon_code;
			$counter     = 1;

			while ( post_exists( $c_title ) ) {
				$c_title = "{$coupon_code}{$counter}";
				++$counter;
			}

			$post_data = array(
				'post_title'   => $c_title,
				'post_type'    => 'shop_coupon',
				'post_status'  => 'draft',
				'post_excerpt' => isset( $result['wbte_sc_bogo_campaign_description'] ) ? sanitize_textarea_field( $result['wbte_sc_bogo_campaign_description'] ) : '',
			);

			$coupon_id = wp_insert_post( $post_data );

			if ( $coupon_id && ! is_wp_error( $coupon_id ) ) {

				$coupon = new WC_Coupon( $coupon_id );
				$coupon->set_code( $c_title );
				$coupon->set_discount_type( self::$bogo_coupon_type_name );
				if ( is_null( $coupon->get_date_created() ) ) {
					$coupon->set_date_created( current_time( 'mysql' ) );
				}
				$coupon->save();

				add_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', $result['wbte_sc_bogo_coupon_name'] );
				Wt_Smart_Coupon_Auto_Coupon_Admin::process_auto_coupon_meta( $coupon_id, array( '_wt_make_auto_coupon' => 'yes' ) );

				foreach ( $predefined_settings[ $result['wbte_sc_bogo_campaign_selected_default'] ] as $key => $value ) {
					update_post_meta( $coupon_id, $key, $value );
				}

				$skip_arr = array( 'wbte_sc_bogo_type', 'wbte_sc_bogo_coupon_name', 'wbte_sc_bogo_campaign_description' );

				foreach ( self::$meta_arr as $meta_key => $meta_info ) {
					if ( in_array( $meta_key, $skip_arr, true ) || array_key_exists( $meta_key, $predefined_settings[ $result['wbte_sc_bogo_campaign_selected_default'] ] ) ) {
						continue;
					}
					$val = $meta_info['default'] ?? '';

					// Save the post meta.
					update_post_meta( $coupon_id, $meta_key, $val );
				}

				$return = array(
					'status' => true,
					'id'     => $coupon_id,
					'url'    => htmlspecialchars_decode( esc_url( admin_url( 'admin.php?page=' . self::$bogo_page_name . '&wbte_bogo_id=' . $coupon_id . '&newly_created=true' ) ) ),
				);
				/**
				 * Action hook to execute after a new BOGO coupon is created.
				 *
				 * @since 2.0.0
				 *
				 * @param int $coupon_id The ID of the newly created coupon.
				 */
				do_action( 'wbte_sc_bogo_new_coupon_created', $coupon_id );
			}
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Convert user input to a valid coupon code format.
	 * Remove everything except letters, numbers, and hyphens, convert to lowercase, convert spaces to hyphens, and trim hyphens from the beginning and end of the string.
	 *
	 * @since 2.0.0
	 * @param  string $text   The text which needs to be converted.
	 * @return string          The converted text.
	 */
	public static function slugify_coupon_code( $text ) {
		// Convert to lowercase.
		$text = strtolower( $text );

		// Replace spaces with hyphens.
		$text = str_replace( ' ', '-', $text );

		// Remove any character that is not a-z, 0-9, or a hyphen.
		$text = preg_replace( '/[^a-z0-9\-]/', '', $text );

		// Replace multiple consecutive hyphens with a single hyphen.
		$text = preg_replace( '/-+/', '-', $text );

		// Trim hyphens from the beginning and end of the string.
		$text = trim( $text, '-' );

		return $text;
	}

	/**
	 * Ajax function to save BOGO coupons on edit page.
	 *
	 * @since 2.0.0
	 */
	public function bogo_coupon_save() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['data'] ) ) {
			parse_str( wp_unslash( $_POST['data'] ), $result ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- It's sanitizing afterwards before writing to the database.

			$post_id         = $result['wt_sc_bogo_coupon_id'];
			$old_coupon_code = wc_format_coupon_code( get_the_title( $post_id ) );

			// Autocoupon.
			if ( isset( $result['wbte_sc_bogo_code_condition'] ) && ( 'wbte_sc_bogo_code_auto' === $result['wbte_sc_bogo_code_condition'] ) ) {
				$coupon_code = wc_format_coupon_code( self::slugify_coupon_code( sanitize_text_field( $result['wbte_sc_bogo_coupon_name'] ) ) );

				$c_title = $coupon_code;
				$counter = 1;

				while ( post_exists( $c_title ) ) {
					if ( $old_coupon_code === $c_title ) {
						break;
					}
					$c_title = "{$coupon_code}{$counter}";
					++$counter;
				}
				$coupon_code = $c_title;
			} else {
				$coupon_code = isset( $result['wbte_sc_bogo_coupon_code'] ) ? wc_format_coupon_code( self::slugify_coupon_code( sanitize_text_field( $result['wbte_sc_bogo_coupon_code'] ) ) ) : '';
			}

			if ( '' === $coupon_code ) {
				$return['msg'] = __( 'Enter a valid coupon code', 'thetoppercentile-coupons' );
				echo wp_json_encode( $return );
				die();
			}
			if ( $old_coupon_code !== $coupon_code && post_exists( $coupon_code ) ) {
				$return['msg'] = __( 'Coupon code already exists', 'thetoppercentile-coupons' );
				echo wp_json_encode( $return );
				die();
			}

			$this->process_bogo_meta( $post_id, $result );

			$coupon = new WC_Coupon( $post_id );
			$coupon->set_code( $coupon_code );
			if ( 'draft' === $coupon->get_status() ) {
				if ( is_null( $coupon->get_date_created() ) ) {
					$coupon->set_date_created( current_time( 'mysql' ) );
				}
			}
			$coupon->save();

			$post_data = array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			);
			$return    = array(
				'status'   => true,
				'msg'      => __( 'Your BOGO offer saved successfully!', 'thetoppercentile-coupons' ),
				'bogo_sts' => 'draft',
			);
			if ( 'wbte_sc_bogo_save_and_activate' === $result['clicked_button'] || 'publish' === $result['_wbte_sc_bogo_selected_sts'] ) {
				$post_data['post_status'] = 'publish';
				$return['bogo_sts']       = 'publish';
				$return['msg']            = __( 'Your BOGO offer is now live!', 'thetoppercentile-coupons' );
			}
			wp_update_post( $post_data );
			$return['status'] = true;
			self::delete_bogo_count_cache();
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Get the list of BOGO coupons.
	 * Parameter $args is optional. It can be used to filter the list of coupons.
	 * Fields in $args array:
	 * - limit: Number of coupons to fetch.
	 * - is_trash: If set to true, fetches only trashed coupons.
	 * - no_limit: If set to true, fetches all coupons without any limit and offset.
	 * - search: Search term to filter the coupons.
	 * - listing_filters: Array of coupon statuses to filter the coupons.
	 * - pagenum: Current page number (for pagination purpose).
	 *
	 * @since 2.0.0
	 *
	 * @param  array $args  Arguments to filter the list of coupons.
	 * @return array        Array of post ids.
	 */
	public static function get_bogo_coupons_list( $args = array() ) {

		global $wpdb;
		$pagenum          = isset( $_GET['pagenum'] ) ? intval( $_GET['pagenum'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$limit            = isset( $args['limit'] ) ? intval( $args['limit'] ) : 20;
		$offset           = ( $pagenum - 1 ) * 20;
		$lookup_table     = Wt_Smart_Coupon::get_lookup_table_name();
		$bogo_coupon_type = self::$bogo_coupon_type_name;
		$sql_placeholder  = array( $bogo_coupon_type );
		$_sts             = isset( $args['is_trash'] ) ? "( 'trash' )" : "( 'publish', 'draft' )";

		// Start building the base SQL query.
		$sql = "SELECT lookup.coupon_id 
                FROM {$lookup_table} AS lookup
                INNER JOIN {$wpdb->posts} AS posts ON lookup.coupon_id = posts.ID
                LEFT JOIN {$wpdb->postmeta} AS meta ON lookup.coupon_id = meta.post_id AND meta.meta_key = 'wbte_sc_bogo_coupon_name'
                WHERE posts.post_status IN {$_sts} 
                AND lookup.discount_type = %s";

		// Filter coupons by status if provided.
		if ( isset( $_GET['listing_filters'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$status     = array_map( 'sanitize_text_field', (array) wp_unslash( $_GET['listing_filters'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$gmt_offset = wc_timezone_offset();

			if ( in_array( 'expired', $status, true ) ) {
				// Remove 'expired' from status and use other statuses if available.
				$status = array_diff( $status, array( 'expired' ) );

				if ( empty( $status ) ) {
					// If only 'expired' is present, filter solely by expiry.
					$sql .= " AND lookup.expiry != '' 
                              AND (( UNIX_TIMESTAMP(lookup.expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP())) ) - %d ) < UNIX_TIMESTAMP()";
				} else {
					// If 'expired' is combined with other statuses, use OR condition.
					$status_list = "'" . implode( "','", array_map( 'esc_sql', $status ) ) . "'";
					$sql        .= " AND ( posts.post_status IN ( {$status_list} ) 
                               OR (lookup.expiry != '' 
                                   AND (( UNIX_TIMESTAMP(lookup.expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP())) ) - %d ) < UNIX_TIMESTAMP()) )";
				}
			} else {
				// No 'expired' status, filter only by the given statuses.
				$status_list = "'" . implode( "','", array_map( 'esc_sql', $status ) ) . "'";
				$sql        .= " AND posts.post_status IN ( {$status_list} ) AND ( ( lookup.expiry != '' 
				AND (( UNIX_TIMESTAMP(lookup.expiry) + TIME_TO_SEC(TIMEDIFF(NOW(), UTC_TIMESTAMP())) ) - %d ) >= UNIX_TIMESTAMP()) OR lookup.expiry = '' ) ";
			}
			$sql_placeholder[] = $gmt_offset;
		}

		// Add search filter if provided.
		if ( isset( $_GET['search'] ) && ! empty( $_GET['search'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_term = sanitize_text_field( wp_unslash( $_GET['search'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Check if the search term starts with 'email:'.
			if ( 0 === stripos( $search_term, 'email:' ) ) {
				// Extract the email from the search term.
				$email = trim( substr( $search_term, 6 ) );

				// Adjust SQL to search for email in the serialized 'customer_email' meta field.
				$sql .= " AND (
                            EXISTS (
                                SELECT 1 
                                FROM {$wpdb->postmeta} AS email_meta 
                                WHERE email_meta.post_id = lookup.coupon_id 
                                AND email_meta.meta_key = 'customer_email' 
                                AND email_meta.meta_value LIKE %s
                            )
                         )";

				$sql_placeholder[] = '%' . $wpdb->esc_like( $email ) . '%';
			} else {
				// Regular search for coupon code, title, or description.
				$sql .= ' AND (
                            posts.post_title LIKE %s
                            OR posts.post_excerpt LIKE %s
                            OR meta.meta_value LIKE %s
                         )';
				// Add the search term three times for each LIKE clause.
				$sql_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
				$sql_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
				$sql_placeholder[] = '%' . $wpdb->esc_like( $search_term ) . '%';
			}
		}

		// Apply sorting and limits.
		$sql .= ' ORDER BY lookup.coupon_id DESC';

		// Add pagination if no_limit is not set.
		if ( ! isset( $args['no_limit'] ) ) {
			$sql              .= ' LIMIT %d OFFSET %d';
			$sql_placeholder[] = $limit;
			$sql_placeholder[] = $offset;
		}

		// Prepare and execute the final SQL query.
		return $wpdb->get_col( $wpdb->prepare( $sql, $sql_placeholder ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter 
	}

	/**
	 * Ajax function to make BOGO coupon as trash.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_delete_on_listing() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['coupon_id'] ) ) {
			wp_trash_post( absint( wp_unslash( $_POST['coupon_id'] ) ) );
			self::delete_bogo_count_cache();
			$return['status'] = true;
			$return['msg']    = __( 'BOGO promotion removed successfully!', 'thetoppercentile-coupons' );
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to duplicate BOGO coupon.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_single_duplicate() {
		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
			'id'     => 0,
			'url'    => '',
		);

		if ( isset( $_POST['coupon_id'] )
			&& class_exists( 'WT_Duplicate_Shop_Coupon' )
			&& method_exists( 'WT_Duplicate_Shop_Coupon', 'clone_coupon' )
		) {
			$old_coupon_id = absint( wp_unslash( $_POST['coupon_id'] ) );
			$old_coupon    = new WC_Coupon( $old_coupon_id );

			$old_coupon_code = $old_coupon->get_code();

			$c_title = $old_coupon_code;
			$counter = 1;

			while ( post_exists( $c_title ) ) {
				$c_title = "{$old_coupon_code}{$counter}";
				++$counter;
			}

			$duplicate_coupon_id = WT_Duplicate_Shop_Coupon::clone_coupon( $old_coupon_id, $c_title );

			if ( $duplicate_coupon_id ) {
				$coupon = new WC_Coupon( $duplicate_coupon_id );

				if ( 'draft' === $coupon->get_status() ) {
					if ( is_null( $coupon->get_date_created() ) ) {
						$coupon->set_date_created( current_time( 'mysql' ) );
					}
				}

				// Delete coupon usage limit when duplicating.
				delete_post_meta( $duplicate_coupon_id, 'usage_count' );
				delete_post_meta( $duplicate_coupon_id, '_used_by' );

				$post_data = array(
					'ID'          => $duplicate_coupon_id,
					'post_status' => 'draft',
				);
				wp_update_post( $post_data );
				$return = array(
					'status' => true,
					'id'     => $duplicate_coupon_id,
					'url'    => htmlspecialchars_decode( esc_url( admin_url( 'admin.php?page=' . self::$bogo_page_name . '&wbte_bogo_id=' . $duplicate_coupon_id ) ) ),
				);
				/**
				 * Action hook to execute after a new BOGO coupon is created.
				 *
				 * @since 2.0.0
				 *
				 * @param int $duplicate_coupon_id The ID of the newly created coupon.
				 */
				do_action( 'wbte_sc_bogo_new_coupon_created', $duplicate_coupon_id );
			}
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to update BOGO coupon status on toggle.
	 *
	 * @since 2.0.0
	 */
	public function update_status_on_toggle() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['data'] ) && isset( $_POST['data']['coupon_id'] ) && isset( $_POST['data']['is_checked'] ) ) {
			$coupon_id  = 0 < $_POST['data']['coupon_id'] ? absint( wp_unslash( $_POST['data']['coupon_id'] ) ) : 0;
			$is_checked = 'true' === sanitize_text_field( wp_unslash( $_POST['data']['is_checked'] ) ) ? true : false;
			if ( $coupon_id ) {
				$coupon = new WC_Coupon( $coupon_id );
				if ( $is_checked ) { // New status is publish.
					wp_update_post(
						array(
							'ID'          => $coupon_id,
							'post_status' => 'publish',
						)
					);
					$return['transition_to']       = __( 'Active', 'thetoppercentile-coupons' );
					$return['transition_to_class'] = 'wbte_sc_label-success';
					$return['msg']                 = __( 'BOGO promotion enabled successfully!', 'thetoppercentile-coupons' );
				} else {
					wp_update_post(
						array(
							'ID'          => $coupon_id,
							'post_status' => 'draft',
						)
					);
					$return['transition_to']       = __( 'Inactive', 'thetoppercentile-coupons' );
					$return['transition_to_class'] = 'wbte_sc_label-warning';
					$return['msg']                 = __( 'BOGO promotion disabled successfully!', 'thetoppercentile-coupons' );
				}
				$coupon->save();
				$return['status'] = true;
			}
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to make selected BOGO coupons as publish from draft.

	 * @since 2.0.0
	 */
	public static function bogo_coupon_multiple_enable() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['coupon_ids'] ) ) {
			$changed_arrs = array();
			$_coupon_ids  = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			foreach ( $_coupon_ids as $coupon_id ) {
				$coupon = new WC_Coupon( $coupon_id );

				if ( 'draft' !== $coupon->get_status() ) {
					continue;
				}

				wp_update_post(
					array(
						'ID'          => intval( $coupon_id ),
						'post_status' => 'publish',
					)
				);
				$changed_arrs[] = $coupon_id;
			}
			$return = array(
				'status'              => true,
				'transition_to'       => __( 'Active', 'thetoppercentile-coupons' ),
				'transition_to_class' => 'wbte_sc_label-success',
				'changed_arrs'        => $changed_arrs,
				'msg'                 => __( 'BOGO promotions enabled successfully!', 'thetoppercentile-coupons' ),
			);
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to make selected BOGO coupons as draft from publish.

	 * @since 2.0.0
	 */
	public static function bogo_coupon_multiple_disable() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status' => false,
		);
		if ( isset( $_POST['coupon_ids'] ) ) {
			$_coupon_ids  = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			$changed_arrs = array();
			foreach ( $_coupon_ids as $coupon_id ) {
				$coupon = new WC_Coupon( $coupon_id );

				if ( 'publish' !== $coupon->get_status() ) {
					continue;
				}

				wp_update_post(
					array(
						'ID'          => intval( $coupon_id ),
						'post_status' => 'draft',
					)
				);
				$changed_arrs[] = $coupon_id;
			}
			self::delete_bogo_count_cache();
			$return = array(
				'status'              => true,
				'transition_to'       => __( 'Inactive', 'thetoppercentile-coupons' ),
				'transition_to_class' => 'wbte_sc_label-warning',
				'changed_arrs'        => $changed_arrs,
				'msg'                 => __( 'BOGO promotions disabled successfully!', 'thetoppercentile-coupons' ),
			);
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to make selected BOGO coupons as trash.

	 * @since 2.0.0
	 */
	public static function bogo_coupon_dlt_multiple() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = false;
		if ( isset( $_POST['coupon_ids'] ) ) {
			$_coupon_ids = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			foreach ( $_coupon_ids as $coupon_id ) {
				wp_trash_post( intval( $coupon_id ) );
			}
			self::delete_bogo_count_cache();
			$return = true;
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to restore BOGO coupon from trash to publish.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_single_restore() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = false;
		if ( isset( $_POST['coupon_id'] ) ) {
			$coupon_update = array(
				'ID'          => absint( wp_unslash( $_POST['coupon_id'] ) ),
				'post_status' => 'publish',
			);
			$return        = wp_update_post( $coupon_update );
			if ( $return ) {
				self::delete_bogo_count_cache();
			}
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to get count of trash bogo coupons.
	 *
	 * @since 2.0.0
	 */
	public static function trash_bogo_count_ajax() {
		$return           = array(
			'status' => false,
			'count'  => 0,
		);
		$trash_bogo_count = count(
			self::get_bogo_coupons_list(
				array(
					'no_limit' => true,
					'is_trash' => true,
				)
			)
		);
		if ( $trash_bogo_count ) {
			$return = array(
				'status' => true,
				'count'  => $trash_bogo_count,
			);
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to delete BOGO coupon permanently.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_single_perm_dlt() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		if ( isset( $_POST['coupon_id'] ) ) {
			$result = wp_delete_post( absint( wp_unslash( $_POST['coupon_id'] ) ), true );
			if ( $result ) {
				self::delete_bogo_count_cache();
			}
			return $result;
		}
		echo false;
		die();
	}

	/**
	 * Ajax function to restore selected BOGO coupons from trash to publish.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_multiple_restore() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = false;
		if ( isset( $_POST['coupon_ids'] ) ) {
			$_coupon_ids = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			foreach ( $_coupon_ids as $coupon_id ) {
				$coupon_update = array(
					'ID'          => $coupon_id,
					'post_status' => 'publish',
				);
				wp_update_post( $coupon_update );
			}
			self::delete_bogo_count_cache();
			$return = true;
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Ajax function to delete selected BOGO coupons permanently.
	 *
	 * @since 2.0.0
	 */
	public static function bogo_coupon_multiple_dlt_permenantly() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = false;
		if ( isset( $_POST['coupon_ids'] ) ) {
			$_coupon_ids = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['coupon_ids'] ), 'int_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- already sanitized with helper function.
			foreach ( $_coupon_ids as $coupon_id ) {
				wp_delete_post( $coupon_id, true );
			}
			self::delete_bogo_count_cache();
			$return = true;
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * If new bogo is activated, then prevent publishing old bogo coupons.
	 *
	 * @since 2.0.0
	 * @param int $coupon_id Coupon id.
	 */
	public function prevent_old_bogo_from_publishing( $coupon_id ) {
		$coupon = new WC_Coupon( $coupon_id );
		if ( self::is_new_bogo_activated() && $coupon && 'wt_sc_bogo' === $coupon->get_discount_type() && 'publish' === $coupon->get_status() ) {
			wp_update_post(
				array(
					'ID'          => $coupon_id,
					'post_status' => 'draft',
				)
			);
		}
	}

	/**
	 * To make the publish button as update for old bogo coupons if new bogo is activated.
	 *
	 * @since 2.0.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function alter_publish_button_for_old_bogo( $hook_suffix ) {
		global $post;

		if ( self::is_new_bogo_activated()
			&& ( 'post-new.php' === $hook_suffix || 'post.php' === $hook_suffix )
			&& 'shop_coupon' === $post->post_type
			&& 'wt_sc_bogo' === get_post_meta( $post->ID, 'discount_type', true )
		) {
			?>
			<script type="text/javascript">
				document.addEventListener( "DOMContentLoaded", function( event ) { 
					document.getElementById( 'publish' ).value = '<?php esc_html_e( 'Update', 'thetoppercentile-coupons' ); ?>';
				} );
			</script>
			<?php
		}
	}

	/**
	 * Display BOGO title instead of coupon code on auto coupons listing.
	 *
	 * @since 2.0.0
	 * @param  string    $value      Coupon code.
	 * @param  WC_Coupon $coupon     Coupon object.
	 * @return string                If BOGO coupon, then return BOGO title else return the coupon code.
	 */
	public function alter_new_bogo_title_on_auto_coupons_listing( $value, $coupon ) {
		if ( is_admin() && isset( $_GET['wbte_sc_auto_apply'] ) && self::is_bogo( $coupon->get_id() ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return self::get_coupon_meta_value( $coupon->get_id(), 'wbte_sc_bogo_coupon_name' );
		}
		return $value;
	}

	/**
	 * Hide BOGO coupons from the coupon listing page.
	 *
	 * @since 2.0.0
	 * @param WP_Query $query The current query object.
	 */
	public function hide_bogo_coupons_from_coupon_listing( $query ) {
		global $pagenow, $wpdb;

		if ( 'edit.php' !== $pagenow || ! $query->is_admin || 'shop_coupon' !== $query->get( 'post_type' ) || ! $query->is_main_query() ) {
			return;
		}

		// Don't hide BOGO coupons on auto coupon listing page.
		if ( isset( $_GET['wbte_sc_auto_apply'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$table_name = Wt_Smart_Coupon::get_lookup_table_name();

		if ( Wt_Smart_Coupon::is_table_exists( $table_name ) ) {
			$cache_key = 'wbte_sc_bogo_coupon_ids';

			$bogo_coupons = get_transient( $cache_key );

			if ( false === $bogo_coupons ) {
				$bogo_coupons = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
					$wpdb->prepare(
						"SELECT coupon_id FROM {$table_name} WHERE discount_type = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						self::$bogo_coupon_type_name
					)
				);

				if ( ! empty( $bogo_coupons ) ) {
					set_transient( $cache_key, $bogo_coupons, HOUR_IN_SECONDS );
				}
			}

			if ( ! empty( $bogo_coupons ) ) {
				$query->set( 'post__not_in', $bogo_coupons );
			}
		}
	}

	/**
	 *  Block bogo coupon edit page
	 *  Instead of editing the coupon, redirect to the bogo coupon edit page.
	 *
	 * @since 2.0.0
	 */
	public function block_bogo_coupon_edit_page() {
		$basename = isset( $_SERVER['PHP_SELF'] ) ? basename( wp_parse_url( wp_unslash( $_SERVER['PHP_SELF'] ), PHP_URL_PATH ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_id  = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'post.php' === $basename && 0 < $post_id ) {

			if ( get_post_meta( $post_id, 'discount_type', true ) === self::$bogo_coupon_type_name ) { // bogo coupon.
				wp_safe_redirect( admin_url( 'admin.php?page=' . self::$bogo_page_name . '&wbte_bogo_id=' . $post_id ) );
				exit;
			}
		}
	}

	/**
	 *  Update total coupon counts after hiding bogo coupons
	 *
	 *  @since 2.0.0
	 *  @param object $counts Count of posts by post status.
	 *  @param string $type   Post type.
	 *  @param string $perm   Capability.
	 *  @return object
	 */
	public function alter_total_coupon_count( $counts, $type, $perm ) {
		global $pagenow, $wpdb;

		// Only run this on the WooCommerce coupon listing page.
		if ( empty( $pagenow ) || 'edit.php' !== $pagenow || 'shop_coupon' !== $type ) {
			return $counts;
		}

		$lookup_table = Wt_Smart_Coupon::get_lookup_table_name();

		if ( Wt_Smart_Coupon::is_table_exists( $lookup_table ) ) {

			$results = $wpdb->get_results( $wpdb->prepare( "SELECT post_status, COUNT(*) AS num_posts FROM {$lookup_table} WHERE discount_type != %s GROUP BY post_status", sanitize_text_field( self::$bogo_coupon_type_name ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Already cached in the function.

			// Initialize counts as an object.
			$counts = (object) array_fill_keys( get_post_stati(), 0 );

			// Check if there are results and update the counts.
			if ( ! empty( $results ) ) {
				foreach ( $results as $row ) {
					if ( isset( $row['post_status'] ) ) {
						$counts->{$row['post_status']} = (int) $row['num_posts'];
					}
				}
			}

			// Cache the results to improve performance.
			$cache_key = _count_posts_cache_key( $type, $perm );
			wp_cache_set( $cache_key, $counts, 'counts' );
		}

		return $counts;
	}

	/**
	 *  When we alter the total coupon count, there is a chance for a new block named `Mine`. And its count will be the old count, so we are hiding that section.
	 *
	 *  @since 2.0.0
	 *  @param  array $views An array of views.
	 *  @return array        Modified array of views.
	 */
	public function hide_mine_post_count( $views ) {
		if ( ! isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			unset( $views['mine'] );
		}
		return $views;
	}

	/**
	 * Display BOGO title instead of coupon code on order detail page.
	 *
	 * @since  2.0.0
	 * @param  string $value  Coupon code.
	 * @return string         BOGO title if the coupon is BOGO coupon else return the coupon code.
	 */
	public function bogo_title_instead_code_on_order_detail_page( $value ) {

		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( isset( $screen->id ) && 'woocommerce_page_wc-orders' === $screen->id ) {
				$_coupon_id = wc_get_coupon_id_by_code( $value );
				if ( $_coupon_id && 'wbte_sc_bogo' === get_post_meta( $_coupon_id, 'discount_type', true ) && self::is_auto_bogo( $_coupon_id ) ) {
					return get_post_meta( $_coupon_id, 'wbte_sc_bogo_coupon_name', true );
				}
			}
		}
		return $value;
	}

	/**
	 * Delete BOGO-related transients when new BOGO coupon is created.
	 * Clears: BOGO coupon IDs (used to hide BOGO from main coupon list) and BOGO count cache.
	 *
	 * @since 2.0.0
	 * @param int $coupon_id Coupon ID.
	 */
	public static function delete_bogo_ids_transient( $coupon_id ) {
		if ( empty( $coupon_id ) ) {
			return;
		}
		delete_transient( 'wbte_sc_bogo_coupon_ids' );
		self::delete_bogo_count_cache();
	}

	/**
	 * Ajax function to get auto offer code.
	 *
	 * @since 2.1.0
	 */
	public static function get_auto_offer_code() {

		check_ajax_referer( 'wbte_sc_bogo_admin_nonce', '_wpnonce' );

		$return = array(
			'status'      => false,
			'coupon_code' => '',
		);
		if ( isset( $_POST['coupon_name'], $_POST['coupon_id'] ) ) {

			$current_coupon_code = get_the_title( absint( wp_unslash( $_POST['coupon_id'] ) ) );
			$coupon_code         = self::slugify_coupon_code( sanitize_text_field( wp_unslash( $_POST['coupon_name'] ) ) );

			$c_title = $coupon_code;
			$counter = 1;

			while ( post_exists( $c_title ) ) {
				if ( $current_coupon_code === $c_title ) {
					break;
				}
				$c_title = "{$coupon_code}{$counter}";
				++$counter;
			}
			$coupon_code = $c_title;
			$return      = array(
				'status'      => true,
				'coupon_code' => $coupon_code,
			);
		}
		echo wp_json_encode( $return );
		die();
	}

	/**
	 * Display BOGO edit step 1 content.
	 * hook: wbte_sc_bogo_edit_step1_content
	 *
	 * @since 2.2.0
	 * @param int $coupon_id Coupon ID.
	 */
	public function bogo_edit_step1_content( $coupon_id ) {
		require_once plugin_dir_path( __FILE__ ) . 'views/-step1.php';
	}

	/**
	 * Display BOGO edit step 2 content.
	 * hook: wbte_sc_bogo_edit_step2_content
	 *
	 * @since 2.2.0
	 * @param int $coupon_id Coupon ID.
	 */
	public function bogo_edit_step2_content( $coupon_id ) {
		require_once plugin_dir_path( __FILE__ ) . 'views/-step2.php';
	}

	/**
	 * Display BOGO edit step 3 content.
	 * hook: wbte_sc_bogo_edit_step3_content
	 *
	 * @since 2.2.0
	 * @param int $coupon_id Coupon ID.
	 */
	public function bogo_edit_step3_content( $coupon_id ) {
		require_once plugin_dir_path( __FILE__ ) . 'views/-step3.php';
	}

	/**
	 * Display BOGO bulk container content.
	 * hook: wbte_sc_bogo_bulk_container_content
	 *
	 * @since 2.2.0
	 */
	public function bogo_bulk_container_content() {
		require_once plugin_dir_path( __FILE__ ) . 'views/-bogo-bulk-container.php';
	}

	/**
	 * Get BOGO localize params.
	 *
	 * @since 2.2.0 Moved to seperate method.
	 * @return array BOGO localize params.
	 */
	public static function get_bogo_params() {

		$timezone_string = get_option( 'timezone_string' );
		$gmt_offset      = get_option( 'gmt_offset' );

		if ( empty( $timezone_string ) ) {
			$timezone_string = timezone_name_from_abbr( '', $gmt_offset * 3600, 0 );
		}

		$admin_img_path = WT_SMARTCOUPON_MAIN_URL . 'admin/images/';

		return array(
			'admin_nonce'        => wp_create_nonce( 'wbte_sc_bogo_admin_nonce' ),
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'is_rtl'             => is_rtl(),
			'urls'               => array(
				'image_path' => esc_url( WT_SMARTCOUPON_MAIN_URL . 'admin/images/' ),
			),
			'text'               => array(
				'error'               => esc_html__( 'Error', 'thetoppercentile-coupons' ),
				'buys'                => esc_html__( 'Buy', 'thetoppercentile-coupons' ),
				'spends'              => esc_html__( 'Spend', 'thetoppercentile-coupons' ),
				'and_so_on'           => esc_html__( 'And so on', 'thetoppercentile-coupons' ),
				'currency_symbol'     => get_woocommerce_currency_symbol(),
				'selected'            => esc_html__( 'selected', 'thetoppercentile-coupons' ),
				'continue_confirm'    => esc_html__( 'Are you sure you want to disable the old BOGO coupons?', 'thetoppercentile-coupons' ),
				'success_copy'        => sprintf(
					// Translators: 1: Success tick icon.
					esc_html__( "Code '{coupon_code}' copied %s", 'thetoppercentile-coupons' ),
					wp_kses_post( '<img class="wbte_sc_bogo_code_copy" src="' . esc_url( "{$admin_img_path}success_icon.svg" ) . '" alt="' . esc_attr__( 'success', 'thetoppercentile-coupons' ) . '" />' ),
				),
				'coupon_copy_tooltip' => esc_html__( 'Copy coupon code for admin use', 'thetoppercentile-coupons' ),
				'bogo'                => __( 'BOGO', 'thetoppercentile-coupons' ),
			),
			'summary_text'       => array(
				// Step 1.
				'discount_free'           => sprintf(
					// Translators: 1: Free qty 2: span opening for customer gets 3: span closing 4: span opening 5: span closing.
					esc_html__( '%1$s quantities of %2$s Specific product(s) %3$s for %4$s Free %5$s', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_step1_summary_qty"></span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
				),
				'discount_perc_fixed'     => sprintf(
					// Translators: 1: Free qty 2: span opening for customer gets 3: span closing 4: span opening 5: span closing 6: Discount amount.
					esc_html__( '%1$s quantities of %2$s Specific product(s) %3$s with %4$s Discount %5$s each', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_step1_summary_qty"></span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_s2_summary_discount_amount"></span> <span>' ),
					wp_kses_post( '</span>' ),
				),
				// Step 2.
				'spends_between_any'      => sprintf(
					// Translators: 1: span opening 2: span closing 3:span opening 4:span closing 5:span opening 6:span closing.
					esc_html__( 'Customer %1$s Spends %2$s between %3$s {min} %4$s and %5$s {max} %6$s on any products', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				'spends_between_selected' => sprintf(
					// Translators: 1: span opening 2: span closing 3:span opening 4:span closing 5:span opening 6:span closing 7:span opening 8:span closing.
					esc_html__( 'Customer %1$s Spends %2$s between %3$s {min} %4$s and %5$s {max} %6$s on %7$s Selected product(s) %8$s', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>',
				),
				'spends_atleast_any'      => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing 5: span opening 6: span closing.
					esc_html__( 'Customer %1$s Spends %2$s at least %3$s {min} %4$s on any products', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				'spends_atleast_selected' => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing 5: span opening 6: span closing.
					esc_html__( 'Customer %1$s Spends %2$s at least %3$s {min} %4$s on %5$s Selected product(s) %6$s', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				'buys_between_any'        => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing 5: span opening 6: span closing.
					esc_html__( 'Customer %1$s Buys %2$s between %3$s {min} %4$s and %5$s {max} %6$s quantities of any products', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				'buys_between_selected'   => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing 5: span opening 6: span closing 7: span opening 8: span closing.
					esc_html__( 'Customer %1$s Buys %2$s between %3$s {min} %4$s and %5$s {max} %6$s quantities of %7$s Selected product(s) %8$s', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>',
				),
				'buys_atleast_any'        => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing.
					esc_html__( 'Customer %1$s Buys %2$s at least %3$s {min} %4$s quantities of any products', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				'buys_atleast_selected'   => sprintf(
					// Translators: 1: span opening 2: span closing 3: span opening 4: span closing 5: span opening 6: span closing.
					esc_html__( 'Customer %1$s Buys %2$s at least %3$s {min} %4$s quantities of %5$s Selected product(s) %6$s', 'thetoppercentile-coupons' ),
					'<span>',
					'</span>',
					'<span>',
					'</span>',
					'<span>',
					'</span>'
				),
				// Step 3.
				'once_buys'               => sprintf(
					// Translators: 1: Minimum qty 2: Maximum qty 3: Span opening 4: Span closing 5: Free qty.
					esc_html__( 'Buy %1$s to %2$s items, Get %3$s Specific product(s) %4$s x %5$s', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_custom_min_sum"></span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_custom_max_sum"></span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_free_count_sum"></span>' )
				),
				'once_spends'             => sprintf(
					// Translators: 1: Minimum spend 2: Maximum spend 3: Span opening 4: Span closing 5: Selected customer gets 6: Free qty.
					esc_html__( 'Spend %1$s to %2$s on items, Get %3$s Specific product(s) %4$s x %5$s', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_custom_min_sum"></span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_custom_max_sum"></span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_free_count_sum"></span>' )
				),
				'repeatedly_buys'         => sprintf(
					// Translators: 1: min qty 2: Span opening 3: Span closing 4: Customer gets qty .
					esc_html__( 'Buy %1$s , Get %2$s Specific product(s) %3$s x %4$s', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_repeatedly_min_sum">{buy_spend_val}</span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_repeatedly_free_count_sum">{repeatedly_free_count}</span>' )
				),
				'repeatedly_spends'       => sprintf(
					// Translators: 1: min spend 2: span opening 3: span closing 4: Customer gets qty .
					esc_html__( 'Spend %1$s , Get %2$s Specific product(s) %3$s x %4$s', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_repeatedly_min_sum">{buy_spend_val}</span>' ),
					wp_kses_post( '<span>' ),
					wp_kses_post( '</span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_repeatedly_free_count_sum">{repeatedly_free_count}</span>' )
				),
			),
			'err_msgs'           => array(
				'gre_equal_1'       => esc_html__( 'Enter a number greater than or equal to 1', 'thetoppercentile-coupons' ),
				'gre_min'           => esc_html__( 'Enter a number greater than the minimum value.', 'thetoppercentile-coupons' ),
				'gre_0'             => esc_html__( 'Enter a value greater than 0.', 'thetoppercentile-coupons' ),
				'atleast_1_prod'    => esc_html__( 'Select at least one product', 'thetoppercentile-coupons' ),
				'atleast_1_ex_prod' => esc_html__( 'Select at least one product to exclude.', 'thetoppercentile-coupons' ),
				'no_camp_title'     => esc_html__( 'Enter a valid input', 'thetoppercentile-coupons' ),
				'perc_less_eq_100'  => esc_html__( 'Enter a value not greater than 100', 'thetoppercentile-coupons' ),
				'email_error'       => esc_html__( 'Enter a valid email address', 'thetoppercentile-coupons' ),
				'coupon_code_error' => esc_html__( 'Enter a valid input (letters or numbers).', 'thetoppercentile-coupons' ),
				'browser_leaving'   => esc_html__( 'Are you sure you want to leave this page? Your changes will not be saved.', 'thetoppercentile-coupons' ),
				'empty_schedule'    => esc_html__( 'Please select a schedule', 'thetoppercentile-coupons' ),
			),
			'short_summary_text' => array(
				'add_conditions'   => esc_html__( 'Additional conditions:', 'thetoppercentile-coupons' ),
				'limit_per_user'   => sprintf(
					// Translators: 1: Usage limit per user.
					esc_html__( 'Usage limit of %s per user.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_per_user_sum"></span>' )
				),
				'limit_per_coupon' => sprintf(
					// Translators: 1: Usage limit per coupon.
					esc_html__( 'Usage limit of %s per offer.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_per_coupon_sum"></span>' )
				),
				'qty'              => sprintf(
					// Translators: 1: Additional minimum qty 2: Additional maximum qty.
					esc_html__( 'Minimum %1$s and maximum %2$s quantity of any item.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_min_sum"></span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_max_sum"></span>' )
				),
				'qty_each'         => sprintf(
					// Translators: 1: Additional minimum qty each 2: Additional maximum qty each.
					esc_html__( 'Minimum %1$s and maximum %2$s quantity of each item.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_each_min_sum"></span>' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_each_max_sum"></span>' )
				),
				'min_qty'          => sprintf(
					// Translators: 1: Minimum qty.
					esc_html__( 'Minimum %s quantity of any item.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_min_sum"></span>' )
				),
				'min_qty_each'     => sprintf(
					// Translators: 1: Minimum qty each.
					esc_html__( 'Minimum %s quantity of each item.', 'thetoppercentile-coupons' ),
					wp_kses_post( '<span class="wbte_sc_bogo_add_qty_each_min_sum"></span>' )
				),
				'email'            => esc_html__( 'Allowed emails:', 'thetoppercentile-coupons' ),
			),
			'timezone'           => $timezone_string,
		);
	}

	/**
	 * Handle BOGO coupon meta processing for bulk generation
	 *
	 * @since 2.2.0
	 * @param int $post_id Coupon ID.
	 */
	public function handle_bogo_process_meta( $post_id ) {
		if ( ! isset( $_POST[ WT_SC_PLUGIN_NAME . '-bogo' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ WT_SC_PLUGIN_NAME . '-bogo' ] ) ), WT_SC_PLUGIN_NAME ) ) {
			return;
		}

		if ( ! self::is_bogo( $post_id ) ) {
			return;
		}

		$this->process_bogo_meta( $post_id, $_POST );
	}

	/**
	 * Process BOGO coupon meta data
	 *
	 * @since 2.2.0
	 * @param int   $coupon_id Coupon ID.
	 * @param array $data Data to process.
	 */
	private function process_bogo_meta( $coupon_id, $data ) {
		$coupon = new WC_Coupon( $coupon_id );

		// Process auto coupon setting.
		if ( isset( $data['wbte_sc_bogo_code_condition'] ) && 'wbte_sc_bogo_code_auto' === $data['wbte_sc_bogo_code_condition'] ) {
			$data['_wt_make_auto_coupon'] = 'yes';
		} else {
			$data['_wt_make_auto_coupon'] = '';
		}

		// Save Auto coupon meta.
		Wt_Smart_Coupon_Auto_Coupon_Admin::process_auto_coupon_meta( $coupon_id, $data );

		foreach ( self::$meta_arr as $meta_key => $meta_info ) {
			$val = ( isset( $data[ $meta_key ] ) && ! empty( $data[ $meta_key ] ) )
					? ( isset( $meta_info['type'] )
						? Wt_Smart_Coupon_Security_Helper::sanitize_item( $data[ $meta_key ], $meta_info['type'] )
						: sanitize_text_field( $data[ $meta_key ] ) )
					: ( $meta_info['default'] ?? '' );

			if ( isset( $meta_info['save_as'] ) ) {
				if ( is_string( $val ) && 'array' === $meta_info['save_as'] ) {
					$val = explode( ',', $val );
				} elseif ( is_array( $val ) && 'text' === $meta_info['save_as'] ) {
					$val = implode( ',', $val );
				}
			}

			// Save the post meta.
			update_post_meta( $coupon_id, $meta_key, $val );
		}

		// Process coupon name.
		if ( isset( $data['wbte_sc_bogo_coupon_name'] ) ) {
			update_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', sanitize_text_field( $data['wbte_sc_bogo_coupon_name'] ) );
		} else {
			update_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', sanitize_text_field( $coupon->get_code() ) );
		}

		$description = isset( $data['excerpt'] ) ? sanitize_text_field( $data['excerpt'] ) : '';

		$post_data = array(
			'ID'           => $coupon_id,
			'post_excerpt' => $description,
		);

		// Update post.
		wp_update_post( $post_data );

		/**
		 * Fires before saving a BOGO coupon
		 *
		 * @since 2.2.0
		 * @param int   $coupon_id  The coupon post ID
		 * @param array $data       The coupon data
		 */
		do_action( 'wt_sc_before_bogo_coupon_save', $coupon_id, $data ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Generate main general settings form field.
	 *
	 * @since 2.2.5
	 *
	 * @param array $args Arguments.
	 */
	public static function generate_main_general_settings_form_field( $args ) {
		require_once plugin_dir_path( __FILE__ ) . 'views/-main-general-form-field-generator.php'; // NOSONAR.
	}

	/**
	 * Render general settings placeholders.
	 *
	 * @since 2.2.5 moved from bogo_main_general.php
	 *
	 * @param string $field_name Field name.
	 */
	public static function render_general_settings_placeholders( $field_name ) {

		$placeholders = self::get_general_settings_placeholders();
		if ( isset( $placeholders[ $field_name ] ) ) {
			esc_html_e( 'Available placeholders: ', 'thetoppercentile-coupons' );
			foreach ( $placeholders[ $field_name ] as $placeholder ) {
				echo "<span class='wbte_sc_bogo_placeholder' id='" . esc_attr( $placeholder ) . "' data-parent-input='" . esc_attr( $field_name ) . "'>" . esc_html( $placeholder ) . '</span>&nbsp;';
			}
		}
	}

	/**
	 * Get general settings placeholders.
	 *
	 * @since 2.2.5
	 *
	 * @return array General settings placeholders.
	 */
	public static function get_general_settings_placeholders() {
		$title_placeholder = '{bogo_title}';

		return array(
			'wbte_sc_bogo_general_discount_apply_message' => array(
				$title_placeholder,
			),
			'wbte_sc_bogo_general_product_added_message'  => array(
				$title_placeholder,
			),
			'wbte_sc_bogo_general_discount_under_product_msg' => array(
				$title_placeholder,
			),
			'wbte_sc_bogo_general_apply_choose_product_title' => array(
				$title_placeholder,
				'{qty_counter}',
			),
		);
	}
}
Wbte_Smart_Coupon_Bogo_Admin::get_instance();