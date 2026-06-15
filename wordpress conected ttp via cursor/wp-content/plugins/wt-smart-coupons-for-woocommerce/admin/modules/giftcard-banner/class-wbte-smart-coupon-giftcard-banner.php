<?php
/**
 * Gift Card promotion banner
 *
 * @package    Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for handling gift card promotion banner
 *
 * @package    Wt_Smart_Coupon
 */
class WBTE_Smart_Coupon_Giftcard_Banner {

	/**
	 * The plugin prefix.
	 *
	 * @since    2.1.0
	 * @var      string    $plugin_prefix    The prefix for the plugin.
	 */
	private static $plugin_prefix = 'wt_smart_coupon';

	/**
	 * The option name for storing banner state.
	 *
	 * @since    2.1.0
	 * @var      string    $option_name    Option name in wp_options table.
	 */
	private static $option_name = 'wbte_sc_gc_banner_state';

	/**
	 * The gift card basic plugin file path.
	 *
	 * @since    2.1.0
	 * @var      string    $basic_gc_file    The path to the main plugin file.
	 */
	private static $basic_gc_file = 'wt-gift-cards-woocommerce/wt-gift-cards-woocommerce.php';

	/**
	 * The gift card pro plugin file path.
	 *
	 * @since    2.1.0
	 * @var      string    $pro_gc_file    The path to the main plugin file.
	 */
	private static $pro_gc_file = 'wt-woocommerce-gift-cards/wt-woocommerce-gift-cards.php';

	/**
	 * Constructor function of the class
	 *
	 * @since    2.1.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'check_existing_plugin' ) );
		add_action( 'admin_notices', array( $this, 'show_banner_notice' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_wbte_sc_gc_dismiss_banner', array( $this, 'handle_dismiss_banner' ) );
		add_action( 'wp_ajax_wbte_sc_gc_install_plugin', array( $this, 'install_plugin' ) );
	}

	/**
	 * Handle setting the flag when user clicks "Not Interested"
	 *
	 * @since    2.1.0
	 */
	public static function handle_dismiss_banner() {
		check_ajax_referer( self::$plugin_prefix, '_wpnonce' );
		if ( ! get_option( self::$option_name ) ) {
			update_option( self::$option_name, true );
		}
		wp_send_json_success();
	}

	/**
	 * Check and update the option if the plugin is already installed
	 *
	 * @since    2.1.0
	 */
	public static function check_existing_plugin() {
		if ( ( file_exists( WP_PLUGIN_DIR . '/' . self::$basic_gc_file ) || file_exists( WP_PLUGIN_DIR . '/' . self::$pro_gc_file ) ) && ! get_option( self::$option_name ) ) {
			update_option( self::$option_name, true );
		}
	}

	/**
	 * Check if the banner should be displayed
	 * True if not an edit page and more than 10 days since the plugin was installed and the banner is not dismissed.
	 *
	 * @since    2.1.0
	 * @return   bool    True if banner should be shown, false otherwise.
	 */
	private static function check_condition() {
		$days_since_sc_start = floor( ( time() - get_option( 'wt_smart_coupon_start_date', time() ) ) / DAY_IN_SECONDS );

		$is_edit = ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'edit', 'new' ), true ) ) || ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'post-new.php' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return ! get_option( self::$option_name ) && $days_since_sc_start > 10 && ! $is_edit;
	}

	/**
	 * Display the giftcard banner
	 *
	 * @since    2.1.0
	 */
	public static function show_banner_notice() {
		if ( ! self::check_condition() ) {
			return;
		}

		$plugin_modal_link = admin_url( 'plugin-install.php?tab=plugin-information&plugin=wt-gift-cards-woocommerce&TB_iframe=true' );
		?>
		<div id="wbte_sc_gc_promo_banner" class="notice notice-info">
			<p style="font-size: 14px; font-weight: 700;">
				<?php
				printf(
					/* translators: 1: a tag opening, 2: a tag closing */
					esc_html__( '🎁 Drive More Sales with the %1$s Gift Card %2$s plugin!', 'thetoppercentile-coupons' ),
					'<a href="' . esc_url( $plugin_modal_link ) . '" class="thickbox" style="text-decoration: none;">',
					'</a>'
				);
				?>
			</p>
			<p>
				<?php
				printf(
					/* translators: 1: strong tag opening, 2: strong tag closing */
					esc_html__( 'Turn one-time buyers into loyal customers! Create, sell, and manage customizable gift cards with TheTopPercentile\'s %1$s free %2$s plugin!', 'thetoppercentile-coupons' ),
					'<strong>',
					'</strong>'
				);
				?>
			</p>

			<label style="display: flex; align-items: end; gap: 5px;">
				<input type="checkbox" id="wbte_sc_gc_need_to_activate_checkbox" checked> 
				<?php esc_html_e( 'Activate after installation', 'thetoppercentile-coupons' ); ?>
			</label>

			<p>
				<button class="button button-primary" id="wbte_sc_gc_banner_install_btn">
					<span class="dashicons dashicons-update wbte_sc_gc_banner_install_btn_loader"></span>
					<?php esc_html_e( 'Download', 'thetoppercentile-coupons' ); ?>
				</button>
				<a href="#" id="wbte_sc_gc_banner_dismiss_btn" style="margin-left: 15px; color: #888; text-decoration: underline;">
					<?php esc_html_e( 'Not Interested', 'thetoppercentile-coupons' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle plugin installation
	 *
	 * @since    2.1.0
	 */
	public static function install_plugin() {
		try {
			check_ajax_referer( self::$plugin_prefix, '_wpnonce' );

			$response = array(
				'status'  => false,
				'message' => esc_html__( 'Something went wrong. Please try again.', 'thetoppercentile-coupons' ),
			);

			if ( ! current_user_can( 'install_plugins' ) ) {
				$response['message'] = esc_html__( 'You do not have sufficient permissions to install plugins.', 'thetoppercentile-coupons' );
				wp_send_json( $response );
				return;
			}

			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			$plugin_slug = 'wt-gift-cards-woocommerce';
			$api         = plugins_api( 'plugin_information', array( 'slug' => $plugin_slug ) );

			if ( is_wp_error( $api ) ) {
				$response['message'] = $api->get_error_message();
				wp_send_json( $response );
				return;
			}

			if ( ! isset( $api->download_link ) ) {
				$response['message'] = esc_html__( 'Plugin download link not found.', 'thetoppercentile-coupons' );
				wp_send_json( $response );
				return;
			}

			$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );

			if ( is_wp_error( $result ) ) {
				$response['message'] = $result->get_error_message();
				wp_send_json( $response );
				return;
			}

			if ( $result ) {
				if ( isset( $_POST['activate'] ) && 'true' === $_POST['activate'] ) {
					$activate_response = self::activate_plugin();
					if ( $activate_response['status'] ) {
						$response['status'] = true;
					} else {
						$response['message'] = $activate_response['msg'];
					}
				} else {
					$response['status'] = true;
				}
			}

			wp_send_json( $response );
		} catch ( Exception $e ) {
			wp_send_json(
				array(
					'status'  => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * Handle plugin activation
	 *
	 * @since    2.1.0
	 */
	public static function activate_plugin() {
		$result = array(
			'status' => false,
			'msg'    => '',
		);

		if ( ! current_user_can( 'activate_plugins' ) ) {
			$result['msg'] = esc_html__( 'You do not have sufficient permissions to activate plugins.', 'thetoppercentile-coupons' );
			return $result;
		}

		$plugin_file = self::$basic_gc_file;

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			$activation_result = activate_plugin( $plugin_file );
			if ( is_wp_error( $activation_result ) ) {
				$result['status'] = false;
				$result['msg']    = esc_html__( 'Activation failed. Please try again.', 'thetoppercentile-coupons' );
			} else {
				$result['status'] = true;
			}
		}

		return $result;
	}

	/**
	 * Enqueue admin footer scripts
	 *
	 * @since    2.1.0
	 */
	public static function enqueue_admin_scripts() {
		$ajax_url  = admin_url( 'admin-ajax.php' );
		$nonce     = wp_create_nonce( self::$plugin_prefix );
		$error_msg = esc_js( __( 'Something went wrong. Please try again.', 'thetoppercentile-coupons' ) );
		?>
		<style>
			.wbte_sc_gc_banner_install_btn_loader {
				animation: rotation 2s infinite linear;
				display: none;
				margin: 3px 5px 0 0;
				vertical-align: top;
			}
			@keyframes rotation {
				from {
					transform: rotate(0deg);
				}
				to {
					transform: rotate(359deg);
				}
			}
		</style>
		<script>
		( function( $ ) {
			'use strict';
			
			$( document ).ready( function() {
				const dismissBtn = $( '#wbte_sc_gc_banner_dismiss_btn' );
				const installBtn = $( '#wbte_sc_gc_banner_install_btn' );
				const activateCheckbox = $( '#wbte_sc_gc_need_to_activate_checkbox' );
				const banner = $( '#wbte_sc_gc_promo_banner' );
				const originalBtnText = installBtn.text();

				function setButtonLoading( isLoading ) {
					if ( isLoading ) {
						$( '.wbte_sc_gc_banner_install_btn_loader' ).css( 'display', 'inline-block' );
						installBtn.prop( 'disabled', true );
					} else {
						$( '.wbte_sc_gc_banner_install_btn_loader' ).css( 'display', 'none' );
						installBtn.prop( 'disabled', false );
					}
				}

				dismissBtn.on( 'click', function( event ) {
					event.preventDefault();
					$.ajax( {
						url: '<?php echo esc_url( $ajax_url ); ?>',
						type: 'POST',
						data: {
							action: 'wbte_sc_gc_dismiss_banner',
							_wpnonce: '<?php echo esc_js( $nonce ); ?>'
						},
						success: function( response ) {
							banner.hide();
						},
						error: function() {
							wbte_sc_notify_msg.error( '<?php echo esc_js( $error_msg ); ?>' );
						}
					} );
				} );

				installBtn.on( 'click', function( event ) {
					event.preventDefault();
					
					const shouldActivate = activateCheckbox.is( ':checked' );
					setButtonLoading( true );
					
					$.ajax( {
						url: '<?php echo esc_url( $ajax_url ); ?>',
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'wbte_sc_gc_install_plugin',
							activate: shouldActivate,
							_wpnonce: '<?php echo esc_js( $nonce ); ?>'
						},
						success: function( response ) {
							if ( response.status ) {
								location.reload();
							} else {
								setButtonLoading( false );
								wbte_sc_notify_msg.error( response.message );
							}
						},
						error: function() {
							setButtonLoading( false );
							wbte_sc_notify_msg.error( '<?php echo esc_js( $error_msg ); ?>' );
						}
					} );
				} );
			} );
		} )( jQuery );
		</script>
		<?php
	}
}

new WBTE_Smart_Coupon_Giftcard_Banner();