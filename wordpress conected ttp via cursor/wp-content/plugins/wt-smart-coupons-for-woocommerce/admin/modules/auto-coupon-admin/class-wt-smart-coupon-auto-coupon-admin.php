<?php
/**
 * Auto coupon admin
 *
 * @link
 * @since 1.4.1
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Common module class not found so return.
if ( ! class_exists( 'Wt_Smart_Coupon_Auto_Coupon_Common' ) ) {
	return;
}

/**
 * Auto coupon admin class.
 *
 * @since 1.4.1
 */
class Wt_Smart_Coupon_Auto_Coupon_Admin extends Wt_Smart_Coupon_Auto_Coupon_Common {

	/**
	 * Module base.
	 *
	 * @var string
	 */
	public $module_base = 'auto_coupon';

	/**
	 * Module id.
	 *
	 * @var string
	 */
	public $module_id = '';

	/**
	 * Static module id.
	 *
	 * @var string
	 */
	public static $module_id_static = '';

	/**
	 * Instance.
	 *
	 * @var object|null
	 */
	private static $instance = null;

	/**
	 * Add coupon listing page js css.
	 *
	 * @var bool
	 */
	private static $add_coupon_listing_page_js_css = false;

	/**
	 * Current page auto coupon count.
	 *
	 * @var int
	 */
	private static $auto_coupon_count = 0;

	/**
	 * Current page auto coupon ids.
	 *
	 * @var array
	 */
	private static $auto_coupon_ids = null;

	/**
	 * Total auto coupon count.
	 *
	 * @var int
	 */
	private static $total_auto_coupon_count = 0;

	/**
	 * Total auto coupon ids.
	 *
	 * @var array
	 */
	private static $total_auto_coupon_ids = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_action( 'woocommerce_coupon_options', array( $this, 'add_auto_coupon_options' ) );
		add_action( 'woocommerce_process_shop_coupon_meta', array( $this, 'process_shop_coupon_meta' ), 11 );

		/**
		 *  Add auto coupon view in coupon listing page
		 *
		 *  @since 1.7.0
		 */
		add_filter( 'views_edit-shop_coupon', array( $this, 'add_auto_coupon_listing_page_link' ) );
		add_filter( 'posts_where', array( $this, 'filter_auto_coupon_where' ), 10, 2 );
		add_filter( 'posts_orderby', array( $this, 'filter_auto_coupon_orderby' ), 10, 2 );
		add_filter( 'admin_footer', array( $this, 'auto_coupon_listing_page_js_css' ) );
		add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'add_auto_coupon_priority_column_head' ), 10, 1 );
		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'add_auto_coupon_priority_column_content' ), 10, 2 );
		add_filter( 'post_class', array( $this, 'add_css_class_to_non_activated_coupon_rows' ), 10, 3 );

		/**
		 *  Add auto coupon priority adding in plugin activation.
		 *  Add a priority reset/add button in debug tab.
		 *  Check and execute the priority reset/add on reset/add button click.
		 *  Swap the priority.
		 *  Add priority meta data while importing.
		 *
		 *  @since 1.7.0
		 */
		add_action( 'after_wt_smart_coupon_for_woocommerce_is_activated', array( $this, 'check_and_add_auto_coupon_priority' ) );
		add_action( 'wt_sc_module_settings_debug', array( $this, 'auto_coupon_priority_reset_button_in_debug_tab' ) );
		add_action( 'admin_init', array( $this, 'check_and_update_auto_coupon_priority' ) );
		add_action( 'wt_sc_import_alter_coupon_meta_data', array( $this, 'add_priority_meta_data_on_import' ), 10, 2 );

		/**
		 *  Add auto coupon settings.
		 *  Save settings.
		 *
		 *  @since 1.7.0
		 */
		add_filter( 'wt_sc_alter_tooltip_data', array( $this, 'register_tooltips' ), 1 );
		add_action( 'wp_ajax_wbte_sc_update_auto_coupon_settings', array( $this, 'save_settings' ) );

		/**
		 * Clear auto coupon cache when auto coupon meta is deleted
		 *
		 * @since 2.2.3
		 */
		add_action( 'deleted_post_meta', array( $this, 'clear_cache_on_auto_coupon_meta_delete' ), 1001, 3 );

		/**
		 * Clear auto coupon cache when coupon is trashed or untrashed
		 *
		 * @since 2.2.3
		 */
		add_action( 'wp_trash_post', array( $this, 'clear_cache_on_coupon_status_change' ), 10, 1 );
		add_action( 'untrash_post', array( $this, 'clear_cache_on_coupon_status_change' ), 10, 1 );
	}

	/**
	 * Get Instance
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wt_Smart_Coupon_Auto_Coupon_Admin();
		}
		return self::$instance;
	}

	/**
	 * Add coupon meta field for setting AutoCoupon
	 *
	 * @since 1.4.1
	 * @param int $coupon_id Coupon ID.
	 */
	public function add_auto_coupon_options( $coupon_id ) {

		$_wt_make_auto_coupon = get_post_meta( $coupon_id, '_wt_make_auto_coupon', true );

		woocommerce_wp_checkbox(
			array(
				'id'            => '_wt_make_auto_coupon',
				'label'         => __( 'Apply coupon automatically', 'thetoppercentile-coupons' ),
				'desc_tip'      => true,
				'description'   => __( 'Enable to apply coupon automatically without applying code. By default, it works for up to 5 recently created coupons.', 'thetoppercentile-coupons' ),
				'wrapper_class' => 'wt_auto_coupon',
				'value'         => wc_bool_to_string( $_wt_make_auto_coupon ),
			)
		);
	}

	/**
	 * Save Auto coupon meta
	 *
	 *  @since 1.4.1
	 *  @since 1.7.0    Priority updating added.
	 *  @since 2.0.0    Meta data added.
	 *
	 *  @param int $post_id    Post ID.
	 */
	public function process_shop_coupon_meta( $post_id ) {
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		self::process_auto_coupon_meta( $post_id, $_POST );
	}

	/**
	 * Process auto coupon meta
	 *
	 * @since 2.2.2 moved to seperate method from 'process_shop_coupon_meta'
	 * @param int   $post_id Coupon ID.
	 * @param array $meta_data Data to process.
	 */
	public static function process_auto_coupon_meta( $post_id, $meta_data = array() ) {
		if ( isset( $meta_data['_wt_make_auto_coupon'] ) && '' !== sanitize_text_field( $meta_data['_wt_make_auto_coupon'] ) ) {
			update_post_meta( $post_id, '_wt_make_auto_coupon', true );

			$priority = absint( get_post_meta( $post_id, '_wbte_sc_auto_coupon_priority', true ) );
			if ( ! $priority ) {
				update_post_meta( $post_id, '_wbte_sc_auto_coupon_priority', $post_id );
			}
		} else {
			update_post_meta( $post_id, '_wt_make_auto_coupon', false );
			// Here we are not clearing the priority number, this is to retain the position when they re-enables the auto-apply.
		}

		// Clear cache when auto coupon status changes.
		self::clear_auto_coupon_cache();
	}


	/**
	 *  Add auto coupons link in coupon listing page
	 *  Hooked into: `views_edit-shop_coupon`
	 *
	 *  @since  1.7.0
	 *  @param  array $views      Views menu array.
	 *  @return array       Views menu array.
	 */
	public function add_auto_coupon_listing_page_link( $views ) {

		$this->prepare_all_auto_coupon_ids();

		// Add auto apply menu to `views` list.
		$views['wbte_sc_auto_apply'] = sprintf(
			'<a href="%1$s" %2$s>%3$s <span class="count">(%4$s)</span></a>',
			add_query_arg(
				array(
					'wbte_sc_auto_apply' => '1',
					'post_type'          => 'shop_coupon',
					'post_status'        => 'all',
				),
				'edit.php'
			),
			( isset( $_GET['wbte_sc_auto_apply'] ) ? ' class="current" aria-current="page" ' : '' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			__( 'Auto-apply coupons', 'thetoppercentile-coupons' ),
			self::$auto_coupon_count
		);

		return $views;
	}


	/**
	 *  Check current page is auto coupon listing page.
	 *
	 *  @since  1.7.0
	 *  @return bool    True when the current page is auto coupon page.
	 */
	private function is_auto_coupon_listing_page() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			return ( is_admin() && isset( $_GET['wbte_sc_auto_apply'] ) && ! is_null( $screen ) && 'edit-shop_coupon' === $screen->id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			return false;
		}
	}

	/**
	 *  Prepare post ids based on the current filter (If exists)
	 *
	 *  @since  1.7.0
	 */
	public function prepare_all_auto_coupon_ids() {

		if ( ! is_null( self::$auto_coupon_ids ) ) { // Already preared.
			return;
		}

		global $wpdb;
		$lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();
		$posts_tb  = $wpdb->prefix . 'posts';

		// Prepare the filters.
		$filter      = array();
		$coupon_type = isset( $_GET['coupon_type'] ) ? sanitize_text_field( wp_unslash( $_GET['coupon_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $coupon_type ) {
			$filter['discount_type'] = $coupon_type;
		}

		if ( $search ) {
			$filter['search'] = $search;
		}

		if ( ! empty( $filter ) ) {

			$sql                 = "SELECT c.coupon_id AS ID FROM {$lookup_tb} AS c LEFT JOIN {$posts_tb} AS p ON( c.coupon_id = p.ID ) WHERE c.post_status != 'trash' AND c.is_auto_coupon = 1 ";
			$sql_palceholder_val = array();

			if ( isset( $filter['discount_type'] ) ) {
				$sql                  .= ' AND c.discount_type = %s ';
				$sql_palceholder_val[] = $filter['discount_type'];
			}

			if ( isset( $filter['search'] ) ) {
				$sql                  .= ' AND p.post_title LIKE %s ';
				$sql_palceholder_val[] = '%' . $wpdb->esc_like( $filter['search'] ) . '%';
			}

			$sql                  .= ' ORDER BY c.auto_coupon_priority DESC';
			self::$auto_coupon_ids = $wpdb->get_col( $wpdb->prepare( $sql, $sql_palceholder_val ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter

		} else {
			self::$auto_coupon_ids = $this->get_cached_auto_coupon_ids();
		}

		self::$auto_coupon_ids   = is_array( self::$auto_coupon_ids ) ? self::$auto_coupon_ids : array();
		self::$auto_coupon_count = count( self::$auto_coupon_ids );
	}


	/**
	 *  Add JS for auto coupon listing.
	 *  Hooked into: `admin_footer`
	 *
	 *  @since 1.7.0
	 */
	public function auto_coupon_listing_page_js_css() {

		if ( self::$add_coupon_listing_page_js_css ) {
			?>
			<script type="text/javascript">
				var wbte_sc_auto_coupon_settings_link_text = '<?php esc_html_e( 'Auto-apply coupon settings', 'thetoppercentile-coupons' ); ?>';

				function wbte_sc_hide_unwanted_columns_in_auto_coupon_listing_page() {
					if(jQuery('.wp-list-table').length) {
						let not_hide_columns = ['coupon_code', 'type', 'amount', 'description', 'wbte_sc_auto_coupon_priority'];
						jQuery('.wp-list-table thead th').each(function() {
							let id = jQuery(this).attr('id');
							
							if(-1 === jQuery.inArray(id, not_hide_columns)) {
								jQuery(this).hide();
								jQuery('.wp-list-table tbody td.column-'+id).hide();
								jQuery('.wp-list-table tfoot th.column-'+id).hide();
								jQuery('.hide-column-tog[value="'+id+'"]').parent('label').hide();
							}
						});

						jQuery('.wp-list-table .no-items .colspanchange').attr({'colspan': not_hide_columns.length + 1});
					}

					if ( ! jQuery('[name="wbte_sc_auto_apply"]').length ) {
						jQuery('.tablenav.top').append('<input type="hidden" value="1" name="wbte_sc_auto_apply" />');
					}

					if ( ! jQuery('.wbte_sc_auto_coupon_settings_link').length ) {
						jQuery('.tablenav.top .alignleft.actions:last').append('<a class="wbte_sc_auto_coupon_settings_link">' + wbte_sc_auto_coupon_settings_link_text + ' <span>︙</span> </a>');
					}
				}  

				jQuery(document).ready(function(){                  
					wbte_sc_hide_unwanted_columns_in_auto_coupon_listing_page();

					jQuery('.wbte_sc_auto_coupon_settings_link').on('click', function(){
						let popup = jQuery('.wbte_sc_auto_coupon_settings_popup');
						let pos = jQuery(this).offset();
						let wid = popup.outerWidth();
						let lef = ( pos.left - wid ) + jQuery(this).outerWidth() + 52;
						let top = pos.top - 5;
						if(popup.is(':visible')) {
							popup.animate({'top':top, 'left':lef});
						}else{
							popup.css({'top':top, 'left':lef, 'opacity': 0}).animate({'opacity': 1}).show();
						}
					});

					jQuery('.wbte_sc_auto_coupon_settings_popup_close').on('click', function(){
						jQuery('.wbte_sc_auto_coupon_settings_popup').hide();
					});

					jQuery('.wbte_sc_auto_coupon_settings_save').on('click', function(){
						let btn = jQuery(this);
						btn.prop({'disabled':true}).css({'opacity': .5});
						jQuery.ajax({
							url: WTSmartCouponAdminOBJ.ajaxurl,
							type: 'POST',
							dataType:'json',
							data:{ 
								'action' : 'wbte_sc_update_auto_coupon_settings',
								'max_auto_coupons_to_check': jQuery('[name="max_auto_coupons_to_check"]').val(), 
								'max_auto_coupons_to_apply': jQuery('[name="max_auto_coupons_to_apply"]').val(), 
								'_wpnonce': WTSmartCouponAdminOBJ.nonce
							},
							success:function( data ) {
								
								btn.prop({'disabled':false}).css({'opacity': 1});
								
								if ( true === data.status ) {
									wbte_sc_notify_msg.success(data.msg);
									jQuery('.wbte_sc_auto_coupon_settings_popup').hide();
								}else {
									wbte_sc_notify_msg.error(data.msg, false);
								}
							},
							error:function() {
								btn.prop({'disabled':false}).css({'opacity': 1});
								wbte_sc_notify_msg.error(WTSmartCouponAdminOBJ.msgs.settings_error, false);
							}
						});
					});
				});
			</script>
			<style type="text/css">
				.wbte_sc_auto_coupon_settings_link{ text-decoration:none; display:inline-block; line-height:20px; margin-top:5px; cursor:pointer;}
				#the-list tr.type-shop_coupon.status-draft, #the-list tr.type-shop_coupon.wbte_sc_coupon_not_activated{ opacity:.5; }
				#the-list tr.type-shop_coupon.status-publish:not(.wbte_sc_coupon_not_activated, .wbte_sc_coupon_not_able_to_apply) td.column-coupon_code .row-title:after{ content:"<?php esc_html_e( 'Available', 'thetoppercentile-coupons' ); ?>"; position:absolute; background:#26a243; color:#fff; font-size:10px; font-weight:400; padding:1px 5px; margin-left:5px; border-radius:5px; }
				select#shop_coupon_cat{ display:none; }

				.wbte_sc_auto_coupon_settings_popup{ background:#fff; width:500px; padding:20px 40px; box-sizing:border-box; height:auto; border-radius:12px; position:absolute; z-index:1000; box-shadow: 0px 3px 60px 0px rgba(43, 69, 88, 0.2); top:100px; display:none; }
				.wbte_sc_auto_coupon_settings_popup:before { content:""; width:0; height:0; border-left:10px solid transparent; border-right:10px solid transparent; border-bottom:10px solid #fff; position:absolute; top:-10px; right:45px; }
				.wbte_sc_auto_coupon_settings_popup_hd{ width: calc( 100% - 30px ); min-height:40px; line-height:22px; font-size:16px; font-weight:500; }
				.wbte_sc_auto_coupon_settings_popup_close{ float:right; width:30px; height:30px; text-align:right; line-height:40px; color:#555e6b; cursor:pointer; position:absolute; top:20px; right:20px;}
				.wbte_sc_auto_coupon_settings_popup_close:hover{ color:red; }
				.wbte_sc_auto_coupon_settings_popup_con{ width:100%; font-size:14px; font-weight:400; padding:10px 0px; padding-top:20px; }
				.wbte_sc_auto_coupon_settings_popup table td{ padding-top:10px; }
				.wbte_sc_auto_coupon_settings_input{ width:80px; height:40px; padding:5px; box-sizing:border-box; border-radius:6px; border:solid 1.5px #bdc1c6; }
				.wbte_sc_settings_warn{ color:red; display:none; }
				.wbte_sc_auto_coupon_settings_popup button.button-primary{ height:38px; width:90px; background:#3157a6; box-shadow:0px 3px 60px 0px rgba(43, 69, 88, 0.2); margin-top:15px; }
			</style>
			<div class="wbte_sc_auto_coupon_settings_popup">
				<div class="wbte_sc_auto_coupon_settings_popup_hd">
					<?php esc_html_e( 'Auto-apply coupons', 'thetoppercentile-coupons' ); ?>
					<span class="wbte_sc_auto_coupon_settings_popup_close">X</span>     
				</div>
				<div class="wbte_sc_auto_coupon_settings_popup_con">
					<div class="wbte_sc_total_auto_coupons" style="background:#f2f3f4; display:inline-block; padding:10px 15px; border-radius:4px;">
						<?php
						$coupon_count  = self::$total_auto_coupon_count;
						$default_limit = $this->get_default_auto_coupon_limit();

						// translators: %d: Coupon count.
						echo esc_html( sprintf( __( 'Total auto-apply coupons: %d', 'thetoppercentile-coupons' ), $coupon_count ) );
						?>
					</div>
					<table style="width:100%; margin-top:10px;">
						<tr>
							<td>
								<?php
								esc_html_e( 'No of coupons that will be checked for auto apply eligibility', 'thetoppercentile-coupons' );
								echo wp_kses_post( Wt_Smart_Coupon_Admin::set_tooltip( 'max_auto_coupons_to_check', $this->module_id ) );
								$vl = Wt_Smart_Coupon::get_option( 'max_auto_coupons_to_check', $this->module_id );
								?>
							</td>
							<td align="right" style="width:100px;">
								<input type="number" class="wbte_sc_auto_coupon_settings_input" name="max_auto_coupons_to_check" value="<?php echo esc_attr( $vl ); ?>" placeholder="<?php echo esc_attr( $coupon_count ); ?>" min="0">   
							</td>
						</tr>
						<tr>
							<td>
								<?php
								esc_html_e( 'Max auto-apply coupons per cart', 'thetoppercentile-coupons' );
								echo wp_kses_post( Wt_Smart_Coupon_Admin::set_tooltip( 'max_auto_coupons_to_apply', $this->module_id ) );
								$vl = Wt_Smart_Coupon::get_option( 'max_auto_coupons_to_apply', $this->module_id );
								?>
							</td>
							<td align="right">
								<input type="number" class="wbte_sc_auto_coupon_settings_input" name="max_auto_coupons_to_apply" value="<?php echo esc_attr( $vl ); ?>" placeholder="<?php echo esc_attr( $default_limit ); ?>" min="0">   
							</td>
						</tr>
						<tr>
							<td align="right" colspan="2">
								<button type="button" class="button button-primary wbte_sc_auto_coupon_settings_save"><?php esc_html_e( 'Save', 'thetoppercentile-coupons' ); ?></button>  
							</td>
						</tr>
					</table> 
				</div>
			</div>
			<?php
		}
	}


	/**
	 *  Add auto coupon priority column head in coupon listing page table
	 *  Hooked into: `manage_edit-shop_coupon_columns`
	 *
	 *  @since  1.7.0
	 *  @param  array $columns Table columns.
	 *  @return array $columns Table columns.
	 */
	public function add_auto_coupon_priority_column_head( $columns ) {

		// Check the current page is auto coupon listing page.
		if ( ! $this->is_auto_coupon_listing_page() ) {
			return $columns;
		}

		$out = array();
		foreach ( $columns as $column_key => $column_title ) {

			$out[ $column_key ] = $column_title;

			// After description column.
			if ( 'description' === $column_key ) {
				$out['wbte_sc_auto_coupon_priority'] = __( 'Priority', 'thetoppercentile-coupons' );
			}
		}

		return $out;
	}


	/**
	 *  Column content for auto coupon priority column in coupon listing page.
	 *  Hooked into: `manage_shop_coupon_posts_custom_column`
	 *
	 *  @since  1.7.0
	 *  @param  string $column_name Column name.
	 *  @param  int    $post_ID     Post ID.
	 */
	public function add_auto_coupon_priority_column_content( $column_name, $post_ID ) {

		if ( 'wbte_sc_auto_coupon_priority' === $column_name ) {

			$post_ID = (string) $post_ID;
			$sl_no   = array_search( $post_ID, self::$total_auto_coupon_ids, true );
			?>
			<span class="wbte_sc_auto_coupon_priority_number">
				<?php
				if ( false !== $sl_no ) {
					echo esc_html( absint( $sl_no ) + 1 );
				}
				?>
			</span>
			<?php
		}
	}


	/**
	 *  Check and update priority on plugin activation
	 *
	 *  @since 1.7.0
	 */
	public function check_and_add_auto_coupon_priority() {
		if ( ! get_option( 'wbte_sc_auto_coupon_priority_added', false ) ) { // Priority not already added.
			$this->reset_add_auto_coupon_priority(); // Add priority.
			update_option( 'wbte_sc_auto_coupon_priority_added', 1 );
		}
	}


	/**
	 *  Add a priority reset button in debug tab
	 *
	 *  @since 1.7.0
	 */
	public function auto_coupon_priority_reset_button_in_debug_tab() {

		$reset_url = wp_nonce_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME . '&debug&wbte_sc_reset_auto_coupon_priority' ), 'wt_smart_coupons_admin_nonce' );
		$add_url   = wp_nonce_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME . '&debug&wbte_sc_add_auto_coupon_priority' ), 'wt_smart_coupons_admin_nonce' );
		?>
		<table class="wt-sc-form-table" style="padding-top: 10px; padding-bottom: 10px; border-top: dashed 1px #ccc;">
            <tbody>
                <tr>
                    <th><?php esc_html_e( 'Reset auto coupon priority', 'thetoppercentile-coupons' ); ?></th>
                    <td style="width: 100%">
                        <button type="button" class="button-primary" onclick="return wbte_sc_confirm_auto_coupon_priority_reset('<?php echo esc_url( $reset_url ); ?>')">
                            <?php esc_html_e( 'Reset now', 'thetoppercentile-coupons' ); ?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Set priority to missing auto coupons', 'thetoppercentile-coupons' ); ?></th>
                    <td style="width: 100%">
                        <a class="button-primary" href="<?php echo esc_url( $add_url ); ?>">
                            <?php esc_html_e( 'Set now', 'thetoppercentile-coupons' ); ?>
                        </a>
                    </td>
                </tr>
                <script type="text/javascript">
                    function wbte_sc_confirm_auto_coupon_priority_reset( url ) {
                        if ( confirm( '<?php esc_html_e( 'Are you sure? All custom priorities will be reset.', 'thetoppercentile-coupons' ); ?>' ) ) {
                            window.location.href = url + '#wt-sc-debug';
                        }
                    }
                </script>
            </tbody>
        </table>
		<?php
	}


	/**
	 *  Check and verify the current URL, and reset the priority
	 *
	 *  @since 1.7.0
	 */
	public function check_and_update_auto_coupon_priority() {
		if ( isset( $_GET['wbte_sc_reset_auto_coupon_priority'] ) || isset( $_GET['wbte_sc_add_auto_coupon_priority'] ) ) {

			// Nonce verification.
			$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
			$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );

			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
				return;
			}

			$this->reset_add_auto_coupon_priority( isset( $_GET['wbte_sc_reset_auto_coupon_priority'] ) ); // Reset/Add the priority.

			esc_html_e( 'Success !!!', 'thetoppercentile-coupons' );
			?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME ) ); ?>"><?php esc_html_e( 'Goto dashboard', 'thetoppercentile-coupons' ); ?></a>
			<?php
			exit();
		}
	}


	/**
	 *  Reset/add the priority to `post_id`
	 *
	 *  @since 1.7.0
	 *  @param bool $reset Reset flag.
	 */
	private function reset_add_auto_coupon_priority( $reset = true ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- It not fetch frequently, so no need to cache.
		if ( $reset ) {
			$product_data_arr = $wpdb->get_results( "SELECT `post_id` FROM {$wpdb->postmeta} WHERE `meta_key` = '_wt_make_auto_coupon' AND ( `meta_value` = '1' OR `meta_value` = 'yes' ) " );
		} else {
			// List of coupons with priority meta missing.
			$product_data_arr = $wpdb->get_results( "SELECT DISTINCT a.`post_id` FROM {$wpdb->postmeta} AS a LEFT JOIN {$wpdb->postmeta} AS b ON (a.post_id = b.post_id AND b.`meta_key` = '_wbte_sc_auto_coupon_priority') WHERE ( a.`meta_key` = '_wt_make_auto_coupon' AND ( a.`meta_value` = '1' OR a.`meta_value` = 'yes' ) ) AND ( b.`meta_value` IS NULL )" );
		}
		// phpcs:enable
		if ( is_array( $product_data_arr ) ) {
			foreach ( $product_data_arr as $product_data ) {
				update_post_meta( $product_data->post_id, '_wbte_sc_auto_coupon_priority', $product_data->post_id );
			}
		}
	}


	/**
	 *  Hook the tooltip data to main tooltip array
	 *
	 *  @since 1.7.0
	 *  @param array $tooltip_arr Tooltip array.
	 *  @return array $tooltip_arr Tooltip array.
	 */
	public function register_tooltips( $tooltip_arr ) {
		$tooltip_arr[ $this->module_id ] = array(
			'max_auto_coupons_to_apply' => __( 'The cart will apply the selected number of auto apply coupons based on priority', 'thetoppercentile-coupons' ),
			'max_auto_coupons_to_check' => __( 'The chosen quantity of coupons will undergo eligibility verification among the entire pool of auto-apply coupons based on priority. If the limit is set to 100, any coupon with a priority of 101 or lower, will not be considered to apply to the cart, even if eligible', 'thetoppercentile-coupons' ),
		);
		return $tooltip_arr;
	}


	/**
	 *  Save module settings.
	 *  Hooked into `wp_ajax_wbte_sc_update_auto_coupon_settings`
	 *
	 *  @since  1.7.0
	 */
	public function save_settings() {

		// Nonce verification.
		$nonce = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
		$nonce = ( is_array( $nonce ) ? reset( $nonce ) : $nonce );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wt_smart_coupons_admin_nonce' ) || ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {

			echo wp_json_encode(
				array(
					'status' => false,
					'msg'    => __( 'Unauthorized', 'thetoppercentile-coupons' ),
				)
			);
			exit();
		}

		// Take existing settings.
		$the_options = Wt_Smart_Coupon::get_settings( $this->module_id );

		foreach ( $the_options as $key => $value ) {
			if ( isset( $_POST[ $key ] ) ) {
				$the_options[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			}
		}

		// Save the settings.
		Wt_Smart_Coupon::update_settings( $the_options, $this->module_id );

		echo wp_json_encode(
			array(
				'status' => true,
				'msg'    => __( 'Settings Updated', 'thetoppercentile-coupons' ),
			)
		);
		exit();
	}




	/**
	 *  Add a CSS class to the listing table row of non actiavted coupon.
	 *  This is to show the coupon was not activated for usage.
	 *  Hooked into: `post_class`
	 *
	 *  @since 1.7.0
	 *
	 *  @param  string[] $classes   An array of post class names.
	 *  @param  string[] $css_class An array of additional class names added to the post.
	 *  @param  int      $post_ID   The post ID.
	 *  @return string[] $classes   An array of post class names.
	 */
	public function add_css_class_to_non_activated_coupon_rows( $classes, $css_class, $post_ID ) {
		if ( 'shop_coupon' === get_post_type( $post_ID ) ) {
			$coupon = new WC_Coupon( $post_ID );

			if ( ! Wt_Smart_Coupon_Common::is_activated_coupon( $coupon ) ) {
				$classes[] = 'wbte_sc_coupon_not_activated';
			}

			if ( ( $coupon->get_date_expires() && time() > $coupon->get_date_expires()->getTimestamp() ) // WC default expiry.
				|| ( Wt_Smart_Coupon_Admin::module_exists( 'coupon_lifespan' )
					&& ( Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_expired( $coupon )  // Expired.
						|| ! Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_started( $coupon ) // Not started.
						|| ! Wt_Smart_Coupon_Lifespan::get_instance()->is_coupon_available_today( $post_ID ) // Not available today.
						)
					)
				|| ( $coupon->get_usage_limit() && $coupon->get_usage_limit() <= $coupon->get_usage_count() ) // Usage limit exceeded.
			) {

				$classes[] = 'wbte_sc_coupon_not_able_to_apply';
			}
		}

		return $classes;
	}


	/**
	 *  Add auto coupon priority value as coupon id while importing.
	 *
	 *  @since  1.7.0
	 *  @param  array $coupon_meta_data   Associative array of coupon meta data.
	 *  @param  int   $coupon_id          Id of coupon.
	 *  @return array   $coupon_meta_data   Associative array of coupon meta data.
	 */
	public function add_priority_meta_data_on_import( $coupon_meta_data, $coupon_id ) {

		// Check this is an auto coupon.
		if ( isset( $coupon_meta_data['_wt_make_auto_coupon'] ) && '' !== trim( $coupon_meta_data['_wt_make_auto_coupon'] ) ) {
			$coupon_meta_data['_wbte_sc_auto_coupon_priority'] = $coupon_id;
		}

		return $coupon_meta_data;
	}

	/**
	 * Get cached auto coupon IDs
	 *
	 * @since 2.2.3
	 * @return array $auto_coupon_ids Auto coupon IDs.
	 */
	private function get_cached_auto_coupon_ids() {
		$cache_key   = 'wt_sc_auto_coupon_ids';
		$cache_group = 'wt_smart_coupons';

		// Try to get from cache first.
		$auto_coupon_ids = wp_cache_get( $cache_key, $cache_group );

		if ( false === $auto_coupon_ids ) {
			global $wpdb;
			$lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- caching code is in below. 
			$auto_coupon_ids = $wpdb->get_col(
				$wpdb->prepare( "SELECT coupon_id AS ID FROM {$lookup_tb} WHERE post_status != %s AND is_auto_coupon = 1 ORDER BY auto_coupon_priority DESC", 'trash' )
			);
			// phpcs:enable

			wp_cache_set( $cache_key, $auto_coupon_ids, $cache_group, HOUR_IN_SECONDS );
		}

		return $auto_coupon_ids;
	}

	/**
	 * Clear auto coupon cache
	 *
	 * @since 2.2.3
	 */
	private static function clear_auto_coupon_cache() {
		wp_cache_delete( 'wt_sc_auto_coupon_ids', 'wt_smart_coupons' );
	}

	/**
	 * Clear auto coupon cache when auto coupon meta is deleted
	 *
	 * @since 2.2.3
	 * @param int    $meta_id   Meta ID.
	 * @param int    $object_id Object ID.
	 * @param string $meta_key  Meta key.
	 */
	public function clear_cache_on_auto_coupon_meta_delete( $meta_id, $object_id, $meta_key ) {
		if ( 'shop_coupon' === get_post_type( $object_id ) &&
			in_array( $meta_key, array( '_wt_make_auto_coupon', '_wbte_sc_auto_coupon_priority' ), true ) ) {
			self::clear_auto_coupon_cache();
		}
	}

	/**
	 * Clear auto coupon cache when coupon status changes
	 *
	 * @since 2.2.3
	 * @param int $post_id Post ID.
	 */
	public function clear_cache_on_coupon_status_change( $post_id ) {
		if ( 'shop_coupon' === get_post_type( $post_id ) ) {
			self::clear_auto_coupon_cache();
		}
	}

	/**
	 * Filter WHERE clause for auto coupon listing
	 *
	 * @since 2.2.3
	 * @param string   $where WHERE clause.
	 * @param WP_Query $query Query object.
	 * @return string Modified WHERE clause.
	 */
	public function filter_auto_coupon_where( $where, $query ) {
		if ( $this->is_auto_coupon_listing_page() && $query->is_main_query() ) {
			global $wpdb;

			self::$add_coupon_listing_page_js_css = true;

			self::$total_auto_coupon_ids   = $this->get_cached_auto_coupon_ids();
			self::$total_auto_coupon_ids   = is_array( self::$total_auto_coupon_ids ) ? self::$total_auto_coupon_ids : array();
			self::$total_auto_coupon_count = count( self::$total_auto_coupon_ids );

			if ( ! empty( self::$total_auto_coupon_ids ) ) {
				$ids    = implode( ',', array_map( 'intval', self::$total_auto_coupon_ids ) );
				$where .= " AND {$wpdb->posts}.ID IN ({$ids})";
			} else {
				$where .= ' AND 1 = 0'; // No auto coupons found.
			}
		}
		return $where;
	}

	/**
	 * Filter ORDER BY clause for auto coupon listing
	 *
	 * @since 2.2.3
	 * @param string   $orderby ORDER BY clause.
	 * @param WP_Query $query Query object.
	 * @return string Modified ORDER BY clause.
	 */
	public function filter_auto_coupon_orderby( $orderby, $query ) {
		if ( $this->is_auto_coupon_listing_page() && $query->is_main_query() ) {
			global $wpdb;

			$auto_coupon_ids = $this->get_cached_auto_coupon_ids();
			if ( ! empty( $auto_coupon_ids ) ) {
				$ids     = implode( ',', array_map( 'intval', $auto_coupon_ids ) );
				$orderby = "FIELD({$wpdb->posts}.ID, {$ids}) DESC";
			}
		}
		return $orderby;
	}
}
Wt_Smart_Coupon_Auto_Coupon_Admin::get_instance();