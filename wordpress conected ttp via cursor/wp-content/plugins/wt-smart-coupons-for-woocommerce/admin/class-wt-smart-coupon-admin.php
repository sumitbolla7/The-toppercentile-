<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.thetoppercentile.com
 * @since      1.0.0
 *
 * @package    Wt_Smart_Coupon
 * @subpackage Wt_Smart_Coupon/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


if ( ! class_exists( 'Wt_Smart_Coupon_Admin' ) ) {
	/**
	 * The admin-specific functionality of the plugin.
	 *
	 * Defines the plugin name, version, and two examples hooks for how to
	 * enqueue the admin-specific stylesheet and JavaScript.
	 *
	 * @package    Wt_Smart_Coupon
	 * @subpackage Wt_Smart_Coupon/admin
	 */
	class Wt_Smart_Coupon_Admin {

		/**
		 * Plugin name.
		 *
		 * @var string Plugin name.
		 */
		private $plugin_name;

		/**
		 * Version.
		 *
		 * @var string Version.
		 */
		private $version;

		/**
		 * Module list, Module folder and main file must be same as that of module name
		 * Please check the `register_modules` method for more details
		 *
		 * @since 1.3.5
		 * @var array Modules.
		 */
		public static $modules = array(
			'url-coupon-admin',
			'limit-max-discount-admin',
			'shortcode-admin',
			'giveaway-product-admin',
			'auto-coupon-admin',
			'checkout-options-admin',
			'style-admin',
			'exclude-product-admin',
		);

		/**
		 * Existing modules.
		 *
		 * @var array Existing modules.
		 */
		public static $existing_modules = array();

		/**
		 * Tooltip array.
		 *
		 * @var array Tooltip array.
		 */
		public static $tooltip_arr = array();

		/**
		 * Instance of the class.
		 *
		 * @var null|object Wt_Smart_Coupon_Admin
		 */
		private static $instance = null;

		/**
		 * Constructor
		 *
		 * @param string $plugin_name Plugin name.
		 * @param string $version Plugin version.
		 */
		public function __construct( $plugin_name, $version ) {

			$this->plugin_name = $plugin_name;
			$this->version     = $version;
		}

		/**
		 * Get Instance.
		 *
		 * @param string $plugin_name Plugin name.
		 * @param string $version Plugin version.
		 * @return Wt_Smart_Coupon_Admin Instance of the class.
		 * @since 1.4.1
		 */
		public static function get_instance( $plugin_name, $version ) {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Admin( $plugin_name, $version );
			}

			return self::$instance;
		}

		/**
		 * Admin settings right sidebar
		 *
		 * @since 1.4.0
		 */
		public static function admin_right_sidebar() {
			include WT_SMARTCOUPON_MAIN_PATH . '/admin/views/-admin-right-sidebar.php';
		}

		/**
		 *  Setup video
		 *
		 *  @since 1.4.0
		 */
		public static function setup_video_sidebar() {
			include WT_SMARTCOUPON_MAIN_PATH . '/admin/views/-setup-video-sidebar.php';
		}

		/**
		 *  Premium features
		 *
		 *  @since 1.4.0
		 */
		public static function premium_features_sidebar() {
			$wbte_premium_url = esc_url( 'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/?utm_source=free_plugin_sidebar&utm_medium=smart_coupons_basic&utm_campaign=smart_coupons' );
			include WT_SMARTCOUPON_MAIN_PATH . '/admin/views/-premium-features-sidebar.php';
		}

		/**
		 * Help links metabox html
		 *
		 * @since 1.3.5
		 */
		public function help_links_meta_box_html() {
			include WT_SMARTCOUPON_MAIN_PATH . '/admin/views/-help-links-meta-box.php';
		}


		/**
		 * Help links metabox
		 *
		 * @since 1.3.5
		 */
		public function help_links_meta_box() {
			add_meta_box( 'wt-sc-help-links', __( 'Quick links', 'thetoppercentile-coupons' ), array( $this, 'help_links_meta_box_html' ), 'shop_coupon', 'side', 'default', null );
		}

		/**
		 * Save Custom meata fields added in coupon
		 *
		 * @since 1.0.0
		 * @param int $post_id Post ID.
		 */
		public function process_shop_coupon_meta( $post_id ) {
			if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
				return;
			}

			if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
				wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
			}

			if ( isset( $_POST['_wt_valid_for_number'] ) ) {
				$wt_valid_for_number = sanitize_text_field( wp_unslash( $_POST['_wt_valid_for_number'] ) );
				if ( '' !== $wt_valid_for_number ) {
					update_post_meta( $post_id, '_wt_valid_for_number', $wt_valid_for_number );
				}
				if ( isset( $_POST['_wt_valid_for_type'] ) && '' !== $_POST['_wt_valid_for_type'] ) {
					$wt_valid_for_type = sanitize_text_field( wp_unslash( $_POST['_wt_valid_for_type'] ) );
				} else {
					$wt_valid_for_type = 'days';
				}
				update_post_meta( $post_id, '_wt_valid_for_type', $wt_valid_for_type );

			}

			// Keep free-shipping coupon behavior disabled for this customized build.
			update_post_meta( $post_id, 'free_shipping', 'no' );

			if ( isset( $_POST['_wc_make_coupon_available'] ) && '' !== $_POST['_wc_make_coupon_available'] ) {
				$_wc_make_coupon_available = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST['_wc_make_coupon_available'] ), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.
				update_post_meta( $post_id, '_wc_make_coupon_available', implode( ',', $_wc_make_coupon_available ) );
			} else {
				update_post_meta( $post_id, '_wc_make_coupon_available', '' );
			}
		}

		/**
		 * Hide free-shipping coupon option from coupon editor.
		 *
		 * @since 2.2.9
		 */
		public function hide_free_shipping_coupon_field() {
			global $pagenow, $post_type;

			$is_coupon_editor = in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) && 'shop_coupon' === $post_type;
			if ( ! $is_coupon_editor ) {
				return;
			}
			?>
			<style>
				#woocommerce-coupon-data .free_shipping_field { display: none !important; }
			</style>
			<?php
		}

		/**
		 * Enqueue Admin styles.
		 *
		 * @since 1.0.0
		 * @since 1.3.5 Styles limited to WC pages and Smart coupon settings pages
		 */
		public function enqueue_styles() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( ( function_exists( 'wc_get_screen_ids' ) && in_array( $screen_id, wc_get_screen_ids(), true ) ) ||
				( isset( $_GET['page'] ) && ( WT_SC_PLUGIN_NAME === $_GET['page'] || 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), WT_SC_PLUGIN_NAME ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) {
				wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wt-smart-coupon-admin.css', array(), $this->version, 'all' );
				wp_enqueue_style( 'wp-color-picker' );
			}

			/**
			 *  Enqueue style for code preview in hooks help section
			 *
			 *  @since 1.5.2
			 */
			if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_style( $this->plugin_name . '_highlightjs', esc_url( plugin_dir_url( __FILE__ ) ) . 'assets/libraries/highlight/styles/stackoverflow-light.min.css', array(), $this->version, 'all' );
			}
		}

		/**
		 * Enqueue Admin Scripts.
		 *
		 * @since 1.0.0
		 * @since 1.3.5 Scripts limited to WC pages and Smart coupon settings pages
		 */
		public function enqueue_scripts() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( ( function_exists( 'wc_get_screen_ids' ) && in_array( $screen_id, wc_get_screen_ids(), true ) ) ||
				( isset( $_GET['page'] ) && ( WT_SC_PLUGIN_NAME === $_GET['page'] || 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), WT_SC_PLUGIN_NAME ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			) {
				wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wt-smart-coupon-admin.js', array( 'jquery', 'wp-color-picker', 'clipboard' ), $this->version, false );

				$script_parameters = array(
					'msgs'                  => array(
						// translators: %1$s: br tag, %2$s: a tag, %3$s: a tag closing.
						'settings_error'         => sprintf( __( 'Unable to update settings due to an internal error. %1$s To troubleshoot please click %2$s here. %3$s', 'thetoppercentile-coupons' ), '<br />', '<a href="https://www.thetoppercentile.com/how-to-fix-the-unable-to-save-settings-issue/" target="_blank">', '</a>' ),
						'is_required'            => __( 'is required', 'thetoppercentile-coupons' ),
						'copied'                 => __( 'Copied!', 'thetoppercentile-coupons' ),
						'error'                  => __( 'Error', 'thetoppercentile-coupons' ),
						'loading'                => __( 'Loading...', 'thetoppercentile-coupons' ),
						'please_wait'            => __( 'Please wait...', 'thetoppercentile-coupons' ),
						'are_you_sure'           => __( 'Are you sure?', 'thetoppercentile-coupons' ),
						'are_you_sure_to_delete' => __( 'Are you sure you want to delete?', 'thetoppercentile-coupons' ),
						'old_bogo_disabled'      => __( 'Old BOGO module is disabled', 'thetoppercentile-coupons' ),
						'switch_new_bogo'        => __( 'Switch to our new BOGO module for the latest features', 'thetoppercentile-coupons' ),
						'update_now'             => __( 'Update now', 'thetoppercentile-coupons' ),
						'copy_failed'            => esc_html__( 'Failed to copy', 'thetoppercentile-coupons' ),
					),
					'is_new_bogo_activated' => class_exists( 'Wbte_Smart_Coupon_Bogo_Common' )
						&& method_exists( 'Wbte_Smart_Coupon_Bogo_Common', 'is_new_bogo_activated' )
						&& Wbte_Smart_Coupon_Bogo_Common::is_new_bogo_activated(),
				);

				$script_parameters['ajaxurl'] = admin_url( 'admin-ajax.php' );
				$script_parameters['nonce']   = wp_create_nonce( 'wt_smart_coupons_admin_nonce' );

				wp_localize_script( $this->plugin_name, 'WTSmartCouponAdminOBJ', $script_parameters );
			}

			/**
			 *  Enqueue script for code preview in hooks help section
			 *
			 *  @since 1.5.2
			 */
			if ( isset( $_GET['page'] ) && WT_SC_PLUGIN_NAME === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				wp_enqueue_script( $this->plugin_name . '_highlightjs', plugin_dir_url( __FILE__ ) . 'assets/libraries/highlight/highlight.min.js', array(), $this->version, false );
			}
		}


		/**
		 * Plugin action link.
		 *
		 * @since 1.0.0
		 * @since 1.3.9 Some links moved to plugin description section
		 * @param array $links Links.
		 * @return array Links.
		 */
		public function add_plugin_links_wt_smartcoupon( $links ) {
			$out = array(
				'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME . '&tab=settings' ) ) . '">' . esc_html__( 'Settings', 'thetoppercentile-coupons' ) . '</a>',
			);
			if ( isset( $links['deactivate'] ) ) {
				$out['deactivate'] = str_replace( '<a', '<a class="smartcoupon-deactivate-link"', $links['deactivate'] );
			}
			return $out;
		}

		/**
		 * Links under plugin description section of plugins page.
		 *
		 * @since 1.3.9
		 * @param array  $links Links.
		 * @param string $file File.
		 * @return array Links.
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( WT_SMARTCOUPON_BASE_NAME !== $file ) {
				return $links;
			}
			return array();
		}


		/**
		 * Add coupon visibility options in coupon general settings section
		 *
		 * @since 1.3.7 Added option to show coupons in checkout page
		 * @param int $coupon_id Coupon ID.
		 */
		public function add_new_coupon_options( $coupon_id ) {
			$wc_make_coupon_available = get_post_meta( $coupon_id, '_wc_make_coupon_available', true );
			$coupon_available_arr     = ( $wc_make_coupon_available ? explode( ',', $wc_make_coupon_available ) : array() );

			$coupon_availability_options = array(
				'my_account' => __( 'My Account', 'thetoppercentile-coupons' ),
				'checkout'   => __( 'Checkout', 'thetoppercentile-coupons' ),
				'cart'       => __( 'Cart', 'thetoppercentile-coupons' ),
			);
			?>
			<p class="form-field">
				<label for="_wc_make_coupon_available"><?php esc_html_e( 'Display coupon in', 'thetoppercentile-coupons' ); ?></label>
				<select id="_wc_make_coupon_available" name="_wc_make_coupon_available[]" style="width: 50%;"  class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_html_e( 'Please select', 'thetoppercentile-coupons' ); ?>">
					<?php
					foreach ( $coupon_availability_options as $section => $name ) {
						$selected = ( in_array( $section, $coupon_available_arr, true ) ? 'selected = selected' : '' );
						echo '<option value="' . esc_attr( $section ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $name ) . '</option>';
					}
					?>
				</select> 
				<?php echo wp_kses_post( wc_help_tip( __( 'Display coupon in the selected pages', 'thetoppercentile-coupons' ) ) ); ?>
			</p>

			<?php
		}

		/**
		 * Remove WooCommerce usage restriction tab from coupon edit screen.
		 *
		 * @since 2.2.9
		 * @param array $tabs Coupon data tabs.
		 * @return array
		 */
		public function remove_usage_restriction_tab( $tabs ) {
			if ( isset( $tabs['usage_restriction'] ) ) {
				unset( $tabs['usage_restriction'] );
			}
			return $tabs;
		}


		/**
		 * Ajax action function for checking product type
		 *
		 * @since 1.0.0
		 */
		public function check_product_type() {

			if ( check_ajax_referer( 'wt_smart_coupons_nonce', 'security' ) && class_exists( 'Wt_Smart_Coupon_Security_Helper ' ) && method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) && Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {

				$product_id = isset( $_POST['product'] ) ? intval( wp_unslash( $_POST['product'] ) ) : '';
				if ( '' === $product_id ) {
					return false;
				}
				$product = wc_get_product( $product_id );
				echo esc_html( $product->get_type() );
				die();
			}
		}

		/**
		 * Get Smartcoupon Settings options
		 *
		 * @deprecated 1.4.7 In favor of Wt_Smart_Coupon::get_settings.
		 *
		 * @since 1.0.1
		 */
		public static function get_options() {
			return Wt_Smart_Coupon::get_settings();
		}

		/**
		 * Helper function for getting formatted price
		 *
		 * @since 1.2.9
		 * @param float $amount Amount.
		 * @return string Formatted price.
		 */
		public static function get_formatted_price( $amount ) {
			$currency          = get_woocommerce_currency_symbol();
			$currentcy_positon = get_option( 'woocommerce_currency_pos' );

			switch ( $currentcy_positon ) {
				case 'left':
					return $currency . $amount;
				case 'left_space':
					return $currency . ' ' . $amount;
				case 'right_space':
					return $amount . ' ' . $currency;
				default:
					return $amount . $currency;
			}
		}


		/**
		 *  Register modules
		 *
		 *  @since 1.3.5
		 */
		public function register_modules() {
			Wt_Smart_Coupon::register_modules( self::$modules, 'wt_sc_admin_modules', plugin_dir_path( __FILE__ ), self::$existing_modules );
		}

		/**
		 *  Check module enabled
		 *
		 *  @since 1.3.5
		 *  @param string $module Module.
		 *  @return bool Module exists.
		 */
		public static function module_exists( $module ) {
			return in_array( $module, self::$existing_modules, true );
		}

		/**
		 * Saving new coupon count
		 *
		 *  @since 1.4.1
		 *
		 *  @param int    $post_id Post ID.
		 *  @param object $post Post object.
		 *  @param bool   $update Update.
		 */
		public function save_created_coupon_count( $post_id, $post, $update ) {
			if ( ! $update && 'shop_coupon' === $post->post_type && 'auto-draft' === $post->post_status ) {
				$auto_draft             = get_option( 'wt_sc_auto_draft_coupons', array() );
				$auto_draft[ $post_id ] = 1;

				update_option( 'wt_sc_auto_draft_coupons', $auto_draft );
			}

			if ( 'shop_coupon' === $post->post_type && 'auto-draft' !== $post->post_status ) {
				$auto_draft = get_option( 'wt_sc_auto_draft_coupons', array() );

				$coupons_created = (int) get_option( 'wt_sc_coupons_created', 0 );

				$is_update_needed = false;

				if ( $update && isset( $auto_draft[ $post_id ] ) ) {
					++$coupons_created;
					$is_update_needed = true;

					unset( $auto_draft[ $post_id ] );
					update_option( 'wt_sc_auto_draft_coupons', $auto_draft );
				}

				if ( ! $update ) {
					++$coupons_created;
					$is_update_needed = true;
				}

				if ( $is_update_needed ) {
					update_option( 'wt_sc_coupons_created', $coupons_created );
				}
			}
		}


		/**
		 *  Alter WP coupon search section to handle `coupons by email` search.
		 *  Search format - email:{email@example.com}
		 *
		 *  @since 1.4.4
		 *  @param object $wp WP object.
		 */
		public function search_coupon_using_email( $wp ) {
			global $pagenow, $wpdb;

			if ( 'edit.php' !== $pagenow || ! isset( $wp->query_vars['s'] ) || 'shop_coupon' !== $wp->query_vars['post_type'] ) {
				return;
			}

			$wp->query_vars['s'] = trim( $wp->query_vars['s'] );

			if ( 'email:' === strtolower( substr( $wp->query_vars['s'], 0, 6 ) ) ) {
				$email = trim( substr( $wp->query_vars['s'], 6 ) );

				if ( ! $email ) {
					return;
				}

				$post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT pm.post_id FROM {$wpdb->postmeta} AS pm LEFT JOIN {$wpdb->posts} AS p ON (p.ID = pm.post_id AND p.post_type = 'shop_coupon') WHERE pm.meta_key = 'customer_email' AND pm.meta_value LIKE %s", '%' . $wpdb->esc_like( $email ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				if ( empty( $post_ids ) ) {
					return;
				}

				unset( $wp->query_vars['s'], $_REQUEST['s'] ); // prevent WP default search.

				$wp->query_vars['post__in'] = $post_ids;
				$wp->query_vars['email']    = $email;
			}
		}


		/**
		 * Registers menu options
		 * Hooked into admin_menu
		 *
		 * @since    1.4.4
		 */
		public function admin_menu() {
			$menu_capabilities = Wt_Smart_Coupon_Security_Helper::menu_items_capability();
			$menus             = array(
				array(
					'menu',
					__( 'General settings', 'thetoppercentile-coupons' ),
					__( 'TheTopPercentile', 'thetoppercentile-coupons' ),
					isset( $menu_capabilities['general_settings'] ) ? $menu_capabilities['general_settings'] : 'manage_woocommerce',
					WT_SC_PLUGIN_NAME,
					array( $this, 'admin_settings_page' ),
					'dashicons-tag',
					59,
				),
				array(
					'submenu',
					WT_SC_PLUGIN_NAME,
					__( 'All coupons', 'thetoppercentile-coupons' ),
					__( 'All coupons', 'thetoppercentile-coupons' ),
					isset( $menu_capabilities['all_coupons'] ) ? $menu_capabilities['all_coupons'] : 'edit_shop_coupons',
					'edit.php?post_type=shop_coupon',
				),
				array(
					'submenu',
					WT_SC_PLUGIN_NAME,
					__( 'Add coupon', 'thetoppercentile-coupons' ),
					__( 'Add coupon', 'thetoppercentile-coupons' ),
					isset( $menu_capabilities['add_coupon'] ) ? $menu_capabilities['add_coupon'] : 'edit_shop_coupons',
					'post-new.php?post_type=shop_coupon',
				),
				array(
					'submenu',
					WT_SC_PLUGIN_NAME,
					__( 'General settings', 'thetoppercentile-coupons' ),
					__( 'General settings', 'thetoppercentile-coupons' ),
					isset( $menu_capabilities['general_settings'] ) ? $menu_capabilities['general_settings'] : 'manage_woocommerce',
					WT_SC_PLUGIN_NAME,
					array( $this, 'admin_settings_page' ),
				),
			);

			/**
			 * Filter the admin menu.
			 *
			 * @since 1.4.4
			 * @param array $menus Menus array.
			 * @return array Filtered menus array.
			 */
			$menus = apply_filters( 'wt_sc_admin_menu', $menus ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			if ( is_array( $menus ) ) {
				foreach ( $menus as $menu ) {
					if ( 'submenu' === $menu[0] ) {
						if ( isset( $menu[6] ) ) {
							add_submenu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6] );
						} else {
							add_submenu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5] );
						}
					} else {
						add_menu_page( $menu[1], $menu[2], $menu[3], $menu[4], $menu[5], $menu[6], $menu[7] );
					}
				}
			}

			if ( function_exists( 'remove_submenu_page' ) ) {
				remove_submenu_page( WT_SC_PLUGIN_NAME, WT_SC_PLUGIN_NAME );
			}
		}

		/**
		 * Admin settings page
		 *
		 * @since    1.4.4
		 */
		public function admin_settings_page() {
			include WT_SMARTCOUPON_MAIN_PATH . 'admin/views/general-settings.php';
		}


		/**
		 * Generate tab head for settings page.
		 *
		 * @since     1.4.4
		 * @param array  $title_arr Title array.
		 * @param string $type Type.
		 */
		public static function generate_settings_tabhead( $title_arr, $type = 'plugin' ) {
			/**
			 * Filter the settings tabhead.
			 *
			 * @since 1.4.4
			 * @param array $title_arr Title array.
			 * @return array Filtered title array.
			 */
			$out_arr = apply_filters( 'wt_sc_' . $type . '_settings_tabhead', $title_arr ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			foreach ( $out_arr as $k => $v ) {
				if ( is_array( $v ) ) {
					$v = ( isset( $v[2] ) ? $v[2] : '' ) . $v[0] . ' ' . ( isset( $v[1] ) ? $v[1] : '' );
				}
				?>
				<a class="nav-tab" href="#<?php echo esc_attr( $k ); ?>"><?php echo wp_kses_post( $v ); ?></a>
				<?php
			}
		}


		/**
		 *  Envelope settings tab content with tab div.
		 *  Relative path is not acceptable for view file
		 *
		 *  @since 1.4.4
		 *  @param string $target_id Target ID.
		 *  @param string $view_file View file.
		 *  @param string $html HTML.
		 *  @param array  $view_params View parameters.
		 *  @param int    $need_submit_btn Need submit button.
		 */
		public static function envelope_settings_tabcontent( $target_id, $view_file = '', $html = '', $view_params = array(), $need_submit_btn = 0 ) {
			?>
				<div class="wt-sc-tab-content" data-id="<?php echo esc_attr( $target_id ); ?>">
					<?php
					if ( '' !== $view_file && file_exists( $view_file ) ) {
						include_once $view_file;
					} else {
						echo wp_kses_post( $html );
					}
					?>
					<?php
					if ( 1 === $need_submit_btn ) {
						self::add_settings_footer();
					}
					?>
				</div>
			<?php
		}


		/**
		 * Smart coupon settings button on coupons page
		 *
		 *  @since 1.4.4
		 *  @since 2.2.8   Moved banners to a separate function.
		 */
		public function coupon_page_settings_button() {
			global $current_screen;
			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			$installed_plugins = get_plugins();
			// Only show banner if SC Pro is not installed and current page is coupons page.
			if ( ! is_object( $current_screen ) || 'shop_coupon' !== $current_screen->post_type || array_key_exists( 'wt-smart-coupon-pro/wt-smart-coupon-pro.php', $installed_plugins ) ) {
				return;
			}
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($){
					jQuery( '.page-title-action' ).after( '<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . WT_SC_PLUGIN_NAME ) ); ?>" class="page-title-action wt_sc_plugin_settings_btn"><?php esc_html_e( 'Smart coupon settings', 'thetoppercentile-coupons' ); ?></a>' );
				});
			</script>
			<?php
		}


		/**
		 *   To save debug settings
		 *
		 *   @since 1.4.5
		 *   @param string $option_name Option name.
		 */
		protected function debug_save_sub( $option_name ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), WT_SC_PLUGIN_NAME ) ) {
				wp_die( esc_html__( 'Security check failed. Please try again.', 'thetoppercentile-coupons' ) );
			}

			$wt_sc_modules = get_option( $option_name );

			if ( false === $wt_sc_modules ) {
				$wt_sc_modules = array();
			}

			if ( isset( $_POST[ $option_name ] ) ) {
				$wt_sc_post = Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_POST[ $option_name ] ), 'text_arr' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Already sanitized with helper function.

				foreach ( $wt_sc_modules as $k => $v ) {
					if ( isset( $wt_sc_post[ $k ] ) && ( 1 === $wt_sc_post[ $k ] || '1' === $wt_sc_post[ $k ] ) ) {
						$wt_sc_modules[ $k ] = 1;
					} else {
						$wt_sc_modules[ $k ] = 0;
					}
				}
			} else {
				foreach ( $wt_sc_modules as $k => $v ) {
					$wt_sc_modules[ $k ] = 0;
				}
			}

			update_option( $option_name, $wt_sc_modules );
		}


		/**
		 *   Form action for debug settings tab
		 *
		 *   @since 1.4.5
		 */
		public function debug_save() {
			if ( isset( $_POST['wt_sc_admin_modules_btn'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! Wt_Smart_Coupon_Security_Helper::check_write_access( 'smart_coupons', WT_SC_PLUGIN_NAME ) ) {
					return;
				}

				$this->debug_save_sub( 'wt_sc_public_modules' );
				$this->debug_save_sub( 'wt_sc_common_modules' );
				$this->debug_save_sub( 'wt_sc_admin_modules' );

				$redirect_url = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : admin_url();
				wp_safe_redirect( $redirect_url );
				exit();
			}

			if ( Wt_Smart_Coupon_Security_Helper::check_role_access( 'smart_coupons' ) ) {
				/**
				 *  Trigger module debug settings saving hook.
				 *
				 *  @since 1.4.5
				 */
				do_action( 'wt_sc_module_save_debug_settings' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			}
		}

		/**
		 *  Shows a progress message while migrating data from post table to lookup table
		 *
		 *  @since 1.4.5
		 */
		public function lookup_table_migration_message() {
			$migration_status = absint( get_option( 'wt_sc_coupon_lookup_updated', 0 ) );
			$last_updated_id  = absint( get_option( 'wt_sc_coupon_lookup_migration_last_id', 0 ) );

			if ( 0 === $migration_status || 0 < $last_updated_id ) {
				?>
				<div class="notice notice-info">
					<p>
						<h3><?php esc_html_e( 'Smart coupon database update in progress', 'thetoppercentile-coupons' ); ?></h3>
						<p><?php esc_html_e( 'The site may experience a slow response for few minutes.', 'thetoppercentile-coupons' ); ?>
						</p>
						<p style="font-weight:bold;">
							<?php
							global $wpdb;
							$row      = $wpdb->get_row( "SELECT COUNT(p.ID) AS total_records FROM {$wpdb->posts} AS p WHERE p.post_type = 'shop_coupon'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$total    = absint( ! empty( $row ) && isset( $row['total_records'] ) ? $row['total_records'] : 0 );
							$migrated = Wt_Smart_Coupon_Common::get_lookup_table_record_count();
							// translators: %1$d: migrated count, %2$d: total count.
							printf( esc_html__( 'Progress: %1$d out of %2$d', 'thetoppercentile-coupons' ), esc_html( $migrated ), esc_html( $total ) );
							?>
						</p>
					</p>
				</div>
				<?php
			}
		}


		/**
		 *   Add setting tab footer
		 *
		 *   @since 1.4.7
		 *  @param string $settings_button_title Title of the settings button.
		 *  @param string $settings_footer_left Left of the settings button.
		 *  @param string $settings_footer_right Right of the settings button.
		 */
		public static function add_settings_footer( $settings_button_title = '', $settings_footer_left = '', $settings_footer_right = '' ) {
			$settings_button_title = (string) $settings_button_title;
			$settings_footer_left  = (string) $settings_footer_left;
			$settings_footer_right = (string) $settings_footer_right;
			include WT_SMARTCOUPON_MAIN_PATH . 'admin/views/admin-settings-save-button.php';
		}


		/**
		 *  Hide promotion banner
		 *
		 *  @since 2.2.1
		 */
		public static function hide_promotion_banner() {

			check_ajax_referer( 'wt_smart_coupons_admin_nonce', '_wpnonce' );

			if ( ! Wt_Smart_Coupon_Security_Helper::check_role_access( 'smart_coupons' ) ) {
				wp_send_json_error( __( 'Access denied', 'thetoppercentile-coupons' ) );
			}

			$hided_banners   = get_option( 'wbte_sc_hidden_promotion_banners', array() );
			$hided_banners[] = isset( $_POST['banner_id'] ) ? sanitize_text_field( wp_unslash( $_POST['banner_id'] ) ) : '';
			update_option( 'wbte_sc_hidden_promotion_banners', $hided_banners );
			wp_send_json_success();
		}

		/**
		 *  Screens to show promotional banner
		 *
		 *  @since 1.5.2
		 *  @param array $screen_ids Array of screen ids.
		 *  @return array Array of screen ids.
		 */
		public function wt_promotion_banner_screens( $screen_ids ) {

			$screen_ids[] = 'shop_coupon';
			$screen_ids[] = 'edit-shop_coupon';
			$screen_ids[] = 'toplevel_page_wt-smart-coupon-for-woo'; // Plugin settings page.

			return $screen_ids;
		}


		/**
		 *  Set tooltip for form fields
		 *
		 *   @since 1.3.5
		 *   @param string $key    Array key for tooltip item.
		 *   @param string $base_id   Module base id.
		 *   @param string $custom_css   Custom CSS for tooltip.
		 *   @return string Tooltip content, empty string if not found.
		 */
		public static function set_tooltip( $key, $base_id = '', $custom_css = '' ) {
			$tooltip_text = self::get_tooltips( $key, $base_id );
			if ( '' !== $tooltip_text ) {
				$tooltip_text = "<span style='display:inline-block; color:#16a7c5; " . ( '' !== $custom_css ? esc_attr( $custom_css ) : 'margin-top:0px; margin-left:2px; position:absolute;' ) . "' class='dashicons dashicons-editor-help wt-sc-tips' data-wt-sc-tip='" . esc_attr( $tooltip_text ) . "'></span>";
			}
			return $tooltip_text;
		}


		/**
		 *   Get tooltip config data for non form field items
		 *
		 *   @since 1.7.0
		 *   @param string $key    Array key for tooltip item.
		 *   @param string $base_id   Module base id.
		 *   @return array 'class': class name to enable tooltip, 'text': tooltip text including data attribute if not empty.
		 */
		public static function get_tooltip_configs( $key, $base_id = '' ) {
			$out  = array(
				'class' => '',
				'text'  => '',
			);
			$text = self::get_tooltips( $key, $base_id );
			if ( '' !== $text ) {
				$out['text']  = " data-wt-sc-tip='" . esc_attr( $text ) . "'";
				$out['class'] = ' wt-sc-tips';
			}
			return $out;
		}


		/**
		 *  This function will take tooltip data from modules
		 *
		 *  @since 1.7.0
		 */
		public function register_tooltips() {

			self::$tooltip_arr = array(
				'main' => array(),
			);

			/**
			 *  Hook for modules to register tooltip
			 *
			 *  @since 1.7.0
			 *  @param array    Tooltip array
			 */
			self::$tooltip_arr = apply_filters( 'wt_sc_alter_tooltip_data', self::$tooltip_arr ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}


		/**
		 *  Get tooltips
		 *
		 *  @since  1.7.0
		 *  @param  string $key    Array key for tooltip item.
		 *  @param  string $base_id   Module base id.
		 *  @return string Tooltip content, empty string if not found.
		 */
		public static function get_tooltips( $key, $base_id = '' ) {
			$arr = ( '' !== $base_id && isset( self::$tooltip_arr[ $base_id ] ) ? self::$tooltip_arr[ $base_id ] : self::$tooltip_arr['main'] );
			return ( isset( $arr[ $key ] ) ? $arr[ $key ] : '' );
		}

		/**
		 *  Load the design system files and initiate it.
		 *
		 *  @since    2.0.0
		 */
		public function include_design_system() {

			include_once plugin_dir_path( __FILE__ ) . 'wt-ds/class-wbte-ds.php';

			if ( class_exists( 'Wbte\Sc\Ds\Wbte_Ds' ) ) {

				// Just initiate it. This is to load the CSS and JS.
				Wbte\Sc\Ds\Wbte_Ds::get_instance( WEBTOFFEE_SMARTCOUPON_VERSION );
			}
		}

		/**
		 * Get WC_DateTime object for a date
		 *
		 * @since 2.0.0
		 *
		 * @param mixed $value The date value to convert to WC_DateTime.
		 * @return WC_DateTime The WC_DateTime object.
		 */
		public static function wt_sc_get_date_prop( $value ) {
			if ( is_int( $value ) ) {
				$timestamp = $value;
			} elseif ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wc_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
			} else {
				$timestamp = wc_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', wc_string_to_timestamp( $value ) ) ) );
			}
			$datetime = new WC_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

			// Set local timezone or offset.
			if ( get_option( 'timezone_string' ) ) {
				$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
			} else {
				$datetime->set_utc_offset( wc_timezone_offset() );
			}

			return $datetime;
		}

		/**
		 *  Trigger 'after_wt_smart_coupon_for_woocommerce_is_activated' hook by comparing version in option
		 *
		 *  @since 2.0.0
		 */
		public static function check_and_trigger_activation_action_hook() {

			if ( ! get_option( 'wbte_sc_basic_activation_hook_version' ) || version_compare( get_option( 'wbte_sc_basic_activation_hook_version' ), WEBTOFFEE_SMARTCOUPON_VERSION, '<' ) ) {
				/**
				 *  Trigger after activation hook.
				 *
				 *  @since 2.0.0
				 */
				do_action( 'after_wt_smart_coupon_for_woocommerce_is_activated' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				update_option( 'wbte_sc_basic_activation_hook_version', WEBTOFFEE_SMARTCOUPON_VERSION );
			}
		}

		/**
		 *  Delete non-existing coupons from the lookup table.
		 *
		 *  @since 2.0.0
		 */
		public function delete_coupon_from_lookup_table() {

			if ( ! get_option( 'wbte_sc_basic_removed_non_existing_coupons_lookup_tb', false ) && self::reset_lookup_table() ) {
                update_option( 'wbte_sc_basic_removed_non_existing_coupons_lookup_tb', 1 );
            }
		}

		/**
		 * To change WordPress footer text to review request link in BOGO page.
		 * If the current page is BOGO edit page, then return empty span, this span will be hidden using css.
		 *
		 * @since 2.1.0
		 * @since 2.2.0 Moved from BOGO module to here.
		 * @param  string $footer_text  Current footer text.
		 * @return string               Modified footer text.
		 */
		public function sc_review_request_footer( $footer_text ) {
			if ( isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), WT_SC_PLUGIN_NAME ) && ! isset( $_GET['wbte_bogo_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$review_url = 'https://wordpress.org/support/plugin/thetoppercentile-coupons/reviews?rate=5#new-post';

				$footer_text = wp_kses_post(
					// Translators: 1: Opening italics tag, 2: Opening a tag, 3: Closing a tag, 4: Closing italics tag.
					sprintf( __( '%1$s If you like TheTopPercentile Coupons please leave us a %2$s ★★★★★ %3$s rating. A huge thanks in advance! %4$s', 'thetoppercentile-coupons' ), '<i class="wbte_sc_bogo_review_request">', '<a href="' . esc_url( $review_url ) . '" target="_blank">', '</a>', '</i>' )
				);
			}
			if ( isset( $_GET['page'] ) && isset( $_GET['wbte_bogo_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$footer_text = '<span class="wbte_sc_bogo_edit_footer"></span>';
			}
			return $footer_text;
		}

		/**
		 *  Change WordPress version text to show plugin version in TheTopPercentile Coupons settings page.
		 *
		 *  @since 2.2.0
		 *  @param string $footer_text Current footer text.
		 *  @return string Modified footer text.
		 */
		public function sc_version_footer( $footer_text ) {
			if ( isset( $_GET['page'] ) && 0 === strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), WT_SC_PLUGIN_NAME ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// translators: %s: plugin version.
				$footer_text = sprintf( esc_html__( 'Version %s', 'thetoppercentile-coupons' ), WEBTOFFEE_SMARTCOUPON_VERSION );
			}
			return $footer_text;
		}

		/**
		 *  Screens to show Black Friday and Cyber Monday Banner.
		 *
		 *  @since 2.2.4
		 *  @param array $screen_ids Array of screen ids.
		 *  @return array            Array of screen ids.
		 */
		public function wt_bfcm_banner_screens( $screen_ids ) {
			$screen_ids[] = 'toplevel_page_wt-smart-coupon-for-woo';
			$screen_ids[] = 'smart-coupons_page_premium_upgrade';
			$screen_ids[] = 'smart-coupons_page_wt-smart-coupon-for-woo_bogo';
			return $screen_ids;
		}

		/**
		 * To Check if the current date is on or between the start and end date of black friday and cyber monday banner for 2024.
		 *
		 * @since 2.2.4
		 */
		public static function is_bfcm_season() {

			$start_date   = new DateTime( '17-NOV-2025, 12:00 AM', new DateTimeZone( 'Asia/Kolkata' ) ); // Start date.
			$current_date = new DateTime( 'now', new DateTimeZone( 'Asia/Kolkata' ) ); // Current date.
			$end_date     = new DateTime( '04-DEC-2025, 11:59 PM', new DateTimeZone( 'Asia/Kolkata' ) ); // End date.

			// Check if the date is on or between the start and end date of black friday and cyber monday banner for 2025.
			if ( $current_date < $start_date || $current_date > $end_date ) {
				return false;
			}
			return true;
		}

		/**
         * Render reset lookup table option in debug tab
         *
         * @since 2.2.8
         */
        public static function render_reset_lookup_table_in_debug_tab() {
            ?>
            <table class="wt-sc-form-table" style="padding-top: 10px; padding-bottom: 10px; border-top: dashed 1px #ccc;">
                <tbody>
                    <tr>
                        <th><?php esc_html_e( 'Reset lookup table', 'thetoppercentile-coupons' ); ?></th>
                        <td style="width: 100%">
                            <button type="button" class="button-primary wbte_sc_reset_lookup_table">
                                <?php esc_html_e( 'Reset now', 'thetoppercentile-coupons' ); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

		/**
         * Reset lookup table ajax
         *
         * @since 2.2.8
         */
		public static function reset_lookup_table_ajax() {
            check_ajax_referer( 'wt_smart_coupons_admin_nonce', '_wpnonce' );

            if ( ! Wt_Smart_Coupon_Security_Helper::check_role_access( 'smart_coupons' ) ) {
                wp_send_json_error( __( 'Access denied', 'thetoppercentile-coupons' ) );
            }

            if ( ! self::reset_lookup_table() ) {
                wp_send_json_error( __( 'Failed to reset lookup table.', 'thetoppercentile-coupons' ) );
            }

            wp_send_json_success( __( 'Lookup table reset successfully.', 'thetoppercentile-coupons' ) );
        }

		/**
         * Reset lookup table
         *
         * @since 2.2.8
         */
		private static function reset_lookup_table() {

            $reset_success = true;
            
            $lookup_tb = Wt_Smart_Coupon::get_lookup_table_name();

            if ( ! Wt_Smart_Coupon::is_table_exists( $lookup_tb ) ) {
                return false;
            }

            global $wpdb;

            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $lookup_table_data = $wpdb->get_results( "SELECT coupon_id, post_status FROM $lookup_tb", ARRAY_A );

            if ( empty( $lookup_table_data ) ) {
                return $reset_success;
            }

            $lookup_table_coupon_ids = array();
            $lookup_status_map       = array();
            foreach ( $lookup_table_data as $lookup_row ) {
				$coupon_id = isset( $lookup_row['coupon_id'] ) ? absint( $lookup_row['coupon_id'] ) : 0;
				if ( $coupon_id ) {
					$lookup_table_coupon_ids[]       = $coupon_id;
					$lookup_status_map[ $coupon_id ] = isset( $lookup_row['post_status'] ) ? $lookup_row['post_status'] : '';
				}
            }

            $placeholders     = implode( ',', array_fill( 0, count( $lookup_table_coupon_ids ), '%d' ) );
            $existing_coupons = $wpdb->get_results(
                $wpdb->prepare( "SELECT ID, post_status FROM {$wpdb->posts} WHERE post_type = %s AND ID IN ($placeholders)", array_merge( array( 'shop_coupon' ), $lookup_table_coupon_ids ) ),
                ARRAY_A
            );

            $existing_coupon_ids = array_map( 'absint', array_column( $existing_coupons, 'ID' ) );

            if ( ! self::remove_non_existent_coupons( $lookup_tb, $lookup_table_coupon_ids, $existing_coupon_ids ) ) {
                $reset_success = false;
            }

            if ( $reset_success && ! empty( $existing_coupons ) && ! self::update_mismatched_statuses( $lookup_tb, $existing_coupons, $lookup_status_map ) ) {
                $reset_success = false;
            }
            // phpcs:enable

            return $reset_success;
        }

		/**
         * Remove non-existent coupons from lookup table.
         *
         * @since 2.2.8
         * @param string $lookup_tb Lookup table name.
         * @param array  $lookup_table_coupon_ids All coupon IDs from lookup table.
         * @param array  $existing_coupon_ids Existing coupon IDs from posts table.
         * @return bool True on success, false on failure.
         */
        private static function remove_non_existent_coupons( $lookup_tb, $lookup_table_coupon_ids, $existing_coupon_ids ) {
            $ids_to_remove = array_diff( $lookup_table_coupon_ids, $existing_coupon_ids );

            if ( empty( $ids_to_remove ) ) {
                return true;
            }

            global $wpdb;
            $placeholders  = implode( ',', array_fill( 0, count( $ids_to_remove ), '%d' ) );
            $deleted_count = $wpdb->query( $wpdb->prepare( "DELETE FROM $lookup_tb WHERE coupon_id IN ($placeholders)", $ids_to_remove ) ); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

            return false !== $deleted_count;
        }

        /**
         * Update mismatched post_status in lookup table.
         *
         * @since 2.2.8
         * @param string $lookup_tb Lookup table name.
         * @param array  $existing_coupons Existing coupons from posts table.
         * @param array  $lookup_status_map Lookup status map.
         * @return bool True on success, false on failure.
         */
        private static function update_mismatched_statuses( $lookup_tb, $existing_coupons, $lookup_status_map ) {
            $posts_status_map = array();
            foreach ( $existing_coupons as $coupon ) {
                $posts_status_map[ absint( $coupon['ID'] ) ] = $coupon['post_status'];
            }

            global $wpdb;
            $update_success = true;

            foreach ( $posts_status_map as $coupon_id => $post_status ) {
                if ( isset( $lookup_status_map[ $coupon_id ] ) && $lookup_status_map[ $coupon_id ] !== $post_status ) {
                    $updated = $wpdb->update( $lookup_tb, array( 'post_status' => $post_status ), array( 'coupon_id' => $coupon_id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

                    if ( false === $updated ) {
                        $update_success = false;
                    }
                }
            }

            return $update_success;
        }

		/**
		 * Render newsletter sidebar
		 * @since 2.2.8
		 */
		public function wbte_newsletter_sidebar() {
			include WT_SMARTCOUPON_MAIN_PATH . '/admin/views/-wbte-newsletter-sidebar.php';
		}

		/**
		 * AJAX handler to hide newsletter banner permanently
		 * @since 2.2.8
		 */
		public function hide_wt_newsletter_banner() {
			check_ajax_referer( 'wt_smart_coupons_admin_nonce', '_wpnonce' );
			
			if ( ! Wt_Smart_Coupon_Security_Helper::check_role_access( 'smart_coupons' ) ) {
				wp_send_json_error( __( 'Access denied', 'thetoppercentile-coupons' ) );
			}
			
			update_option('wt_newsletter_banner_hidden', true);
			
			wp_send_json_success( __( 'Newsletter subscription successful', 'thetoppercentile-coupons' ) );
		}

	}
}
