<?php
/**
 * Coupon style common section
 *
 * @link
 * @since 1.4.7
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coupon style common section
 *
 * @link
 * @since 1.4.7
 *
 * @package  Wt_Smart_Coupon
 */
class Wt_Smart_Coupon_Style {
	/**
	 * Module base
	 *
	 * @var string
	 */
	public $module_base = 'coupon_style';

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
	 * To hide CSS
	 *
	 * @var string
	 */
	const TO_HIDE_CSS = 'wt_sc_hidden';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
		self::$module_id_static = $this->module_id;

		add_filter( 'wt_sc_module_default_settings', array( $this, 'default_settings' ), 10, 2 );

		add_action( 'after_wt_smart_coupon_for_woocommerce_is_activated', array( $this, 'adjust_coupon_style_settings' ) );
	}


	/**
	 *  Get Instance
	 *
	 *  @since 1.4.7
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Wt_Smart_Coupon_Style();
		}
		return self::$instance;
	}


	/**
	 *  Default settings
	 *
	 *  @since 1.4.7
	 *  @param array  $settings      Settings array.
	 *  @param string $base_id       Module Id.
	 *  @return array    Default settings of current module.
	 */
	public static function default_settings( $settings, $base_id ) {
		if ( $base_id !== self::$module_id_static ) {
			return $settings;
		}

		return array(
			'available_coupon' => array(
				'style' => 'minimal_outline',
				'color' => array( '#FFFFFF', '#4D5562', '#D9622B' ),
			),
			'used_coupon'      => array(
				'style' => 'stitched_padding',
				'color' => array( '#eeeeee', '#000000', '#000000' ),
			),
			'expired_coupon'   => array(
				'style' => 'stitched_padding',
				'color' => array( '#f3dfdf', '#eccaca', '#eccaca' ),
			),
		);
	}


	/**
	 *  Migrate old settings, If exists
	 *  Settings before 1.4.7
	 *
	 *  @since 1.4.7
	 */
	protected static function migrate_settings() {
		$smart_coupon_option = get_option( 'wt_smart_coupon_options' );

		if ( isset( $smart_coupon_option['wt_active_coupon_bg_color'] )
			|| isset( $smart_coupon_option['wt_active_coupon_border_color'] )
			|| isset( $smart_coupon_option['wt_used_coupon_bg_color'] )
			|| isset( $smart_coupon_option['wt_used_coupon_border_color'] )
			|| isset( $smart_coupon_option['wt_expired_coupon_bg_color'] )
			|| isset( $smart_coupon_option['wt_expired_coupon_border_color'] )
		) {
			$default_settings = self::default_settings( array(), self::$module_id_static );

			$default_settings['available_coupon']['color'][0] = isset( $smart_coupon_option['wt_active_coupon_bg_color'] ) ? $smart_coupon_option['wt_active_coupon_bg_color'] : $default_settings['available_coupon']['color'][0];
			$default_settings['available_coupon']['color'][1] = isset( $smart_coupon_option['wt_active_coupon_border_color'] ) ? $smart_coupon_option['wt_active_coupon_border_color'] : $default_settings['available_coupon']['color'][1];
			$default_settings['available_coupon']['color'][2] = $default_settings['available_coupon']['color'][1];

			$default_settings['used_coupon']['color'][0] = isset( $smart_coupon_option['wt_used_coupon_bg_color'] ) ? $smart_coupon_option['wt_used_coupon_bg_color'] : $default_settings['used_coupon']['color'][0];
			$default_settings['used_coupon']['color'][1] = isset( $smart_coupon_option['wt_used_coupon_border_color'] ) ? $smart_coupon_option['wt_used_coupon_border_color'] : $default_settings['used_coupon']['color'][1];
			$default_settings['used_coupon']['color'][2] = $default_settings['used_coupon']['color'][1];

			$default_settings['expired_coupon']['color'][0] = isset( $smart_coupon_option['wt_expired_coupon_bg_color'] ) ? $smart_coupon_option['wt_expired_coupon_bg_color'] : $default_settings['expired_coupon']['color'][0];
			$default_settings['expired_coupon']['color'][1] = isset( $smart_coupon_option['wt_expired_coupon_border_color'] ) ? $smart_coupon_option['wt_expired_coupon_border_color'] : $default_settings['expired_coupon']['color'][1];
			$default_settings['expired_coupon']['color'][2] = $default_settings['expired_coupon']['color'][1];

			Wt_Smart_Coupon::update_settings( $default_settings, self::$module_id_static );

			// remove old option.
			unset( $smart_coupon_option['wt_active_coupon_bg_color'], $smart_coupon_option['wt_active_coupon_border_color'], $smart_coupon_option['wt_used_coupon_bg_color'], $smart_coupon_option['wt_used_coupon_border_color'], $smart_coupon_option['wt_expired_coupon_bg_color'], $smart_coupon_option['wt_expired_coupon_border_color'] );
			update_option( 'wt_smart_coupon_options', $smart_coupon_option );
		}
	}


	/**
	 *  Get current coupon styles
	 *  Format:
	 *      array(
	 *          'coupon_type' => array(
	 *              'style' => stitched_padding,
	 *              'color' => array(color1, color2, color3),
	 *          )
	 *      )
	 *
	 *  @since 1.4.7
	 *  @return array  Current coupon settings array
	 */
	public static function get_current_coupon_style() {
		self::migrate_settings(); /* migrate old settings. If exists */

		return Wt_Smart_Coupon::get_settings( self::$module_id_static );
	}


	/**
	 *  Get Coupon types
	 *
	 *  @since 1.4.7
	 *  @return array  Array of coupon types
	 */
	public static function get_coupon_types() {
		$coupon_types = array(
			'available_coupon' => esc_html__( 'Active coupon', 'thetoppercentile-coupons' ),
			'used_coupon'      => esc_html__( 'Redeemed coupon', 'thetoppercentile-coupons' ),
			'expired_coupon'   => esc_html__( 'Expired coupon', 'thetoppercentile-coupons' ),
		);

		/**
		 * Filter to modify coupon types.
		 *
		 * @since 1.4.7
		 * @param array $coupon_types Array of coupon types.
		 */
		return apply_filters( 'wt_coupon_types', $coupon_types ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}


	/**
	 * Preset coupon styles
	 * Format:
	 *     array(
	 *         template_slug => array(
	 *             'name'  => Display name of the template,
	 *             'html'  => Template HTML,
	 *             'color' => Color array for the template,
	 *         )
	 *     )
	 *
	 * @since 1.4.7
	 * @return array Array of coupon styles
	 */
	public static function coupon_styles() {
		$coupon_styles = array(
			'minimal_outline'  => array(
				'name'  => __( 'Style 1', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'minimal-outline' ),
				'color' => array(
					array( '#FFFFFF', '#4D5562', '#D9622B' ),
					array( '#2F866D', '#94E7CF', '#FFFFFF' ),
					array( '#ECE9FC', '#4D5562', '#743EE4' ),
				),
			),
			'classic_ticket'   => array(
				'name'  => __( 'Style 2', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'classic-ticket' ),
				'color' => array(
					array( '#D63638', '#832122', '#FFFFFF' ),
					array( '#ABE7D6', '#FFFFFF', '#005345' ),
					array( '#6F9FDA', '#143155', '#FFFFFF' ),
				),
			),
			'stitched_padding' => array(
				'name'  => __( 'Style 3', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'stitched-padding' ),
				'color' => array( '#2890a8', '#ffffff', '#ffffff' ),
			),
			'stitched_edge'    => array(
				'name'  => __( 'Style 4', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'stitched-edge' ),
				'color' => array( '#f7f7f7', '#e9e9eb', '#000000' ),
			),
			'ticket_style'     => array(
				'name'  => __( 'Style 5', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'ticket-style' ),
				'color' => array( '#fffae6', '#fc7400', '#000000' ),
			),
			'plain_coupon'     => array(
				'name'  => __( 'Style 6', 'thetoppercentile-coupons' ),
				'html'  => self::get_template_html_from_template_file( 'plain-coupon' ),
				'color' => array( '#c8f1c0', '#30900c' ),
			),
		);

		/**
		 * Filter to modify coupon styles.
		 *
		 * @since 1.4.7
		 * @param array $coupon_styles Array of coupon styles.
		 */
		return apply_filters( 'wt_smart_coupon_styles', $coupon_styles ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}


	/**
	 *  This function will take HTML from template file
	 *  Template file format: data.template_{template_slug}.php
	 *
	 * @since  1.4.7
	 * @param  string $template_slug Slug of the template.
	 * @return string Template HTML from the file, empty string if file doesn't exist.
	 */
	public static function get_template_html_from_template_file( $template_slug ) {
		$html = '';

		$slug_mapping = array(
			'minimal_outline'  => 'minimal-outline',
			'classic_ticket'   => 'classic-ticket',
			'stitched_padding' => 'stitched-padding',
			'stitched_edge'    => 'stitched-edge',
			'ticket_style'     => 'ticket-style',
			'plain_coupon'     => 'plain-coupon',
		);

		$template_slug = isset( $slug_mapping[ $template_slug ] ) ? $slug_mapping[ $template_slug ] : 'minimal-outline';

		$file = plugin_dir_path( __FILE__ ) . 'data/data-template-' . $template_slug . '.php';

		/**
		 * Filter to override the template file path.
		 *
		 * @since 1.4.7
		 * @param string $file          Path to the template file.
		 * @param string $template_slug Slug of the template.
		 */
		$file = apply_filters( 'wt_sc_alter_coupon_template_file_path', $file, $template_slug ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( ! file_exists( $file ) ) {
			$file = plugin_dir_path( __FILE__ ) . 'data/data-template-minimal-outline.php';
		}

		if ( file_exists( $file ) ) {
			ob_start();
			include $file;
			$html = ob_get_clean();
		}

		return $html;
	}

	/**
	 *  Prepare coupon HTML
	 *
	 *  @since  1.4.7
	 *  @param  WC_Coupon $coupon         Coupon object, Null for coupon preview.
	 *  @param  array     $coupon_data    Coupon info array.
	 *  @param  string    $coupon_type    Coupon type. Eg: available_coupon, expired etc. Default: available_coupon.
	 *  @param  bool      $include_css    Include CSS along with HTML. Default: false.
	 *  @param  array     $style_data     To print coupon HTML of a specific template. This will be using in admin preview section. Default: empty array.
	 *  @return  string     $html           Prepared HTML for printing.
	 */
	public static function prepare_coupon_html( $coupon, $coupon_data, $coupon_type = 'available_coupon', $include_css = false, $style_data = array() ) {
		$current_coupon_style = self::get_current_coupon_style();

		if ( empty( $style_data ) ) {
			$coupon_style = ( isset( $current_coupon_style[ $coupon_type ] ) ? $current_coupon_style[ $coupon_type ] : $current_coupon_style['available_coupon'] );
		} else {
			$coupon_style = $style_data;
		}

		$style_name = $coupon_style['style'];
		$colors     = $coupon_style['color'];

		// take template html.
		$html = self::get_template_html_from_template_file( $style_name );

		/**
		 * Filter to alter coupon template HTML.
		 *
		 * @since 1.4.7
		 * @param string $html HTML.
		 * @param array $coupon_style Coupon style.
		 * @param string $coupon_type Coupon type.
		 * @param WC_Coupon $coupon Coupon object.
		 */
		$html = apply_filters( 'wt_sc_alter_coupon_template_html', $html, $coupon_style, $coupon_type, $coupon ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		if ( '' !== $html ) {
			// Add translation for translatable strings.
			$html = preg_replace_callback( '/__\[(.*?)\]__/s', array( __CLASS__, 'add_translation_for_coupon_html' ), $html );

			/**
			 *  Placeholder data array
			 *  Format: array([placeholder] => value)
			 */
			$find_replace = array();

			// prepare single coupon CSS class, and title.
			$find_replace['[wt_sc_single_coupon_class]'] = '';
			$find_replace['[wt_sc_single_coupon_title]'] = '';

			switch ( $coupon_type ) {
				case 'expired_coupon':
					$find_replace['[wt_sc_single_coupon_class]'] = ' used-coupon expired';
					break;
				case 'used_coupon':
					$find_replace['[wt_sc_single_coupon_class]'] = ' used-coupon';
					break;
				default:
					$find_replace['[wt_sc_single_coupon_class]'] = 'active-coupon';
					$find_replace['[wt_sc_single_coupon_title]'] = esc_attr__( 'Click to apply coupon', 'thetoppercentile-coupons' );
			}
			$find_replace['[wt_sc_single_coupon_class]'] .= ( isset( $coupon_data['display_on_page'] ) ? ' ' . esc_attr( $coupon_data['display_on_page'] ) : '' ); // for apply on click.

			// add colors.
			foreach ( $colors as $color_k => $color_v ) {
				$find_replace[ '[wt_sc_color_' . $color_k . ']' ] = esc_attr( $color_v );
			}

			// add coupon id.
			$coupon_id                         = ( isset( $coupon_data['coupon_id'] ) ? $coupon_data['coupon_id'] : 0 );
			$find_replace['[wt_sc_coupon_id]'] = esc_attr( $coupon_id );

			$is_preview_mode = ( isset( $coupon_data['preview_mode'] ) && $coupon_data['preview_mode'] );

			if ( $is_preview_mode ) {
				// dummy texts for preview mode.
				$find_replace['[wt_sc_coupon_start]'] = wp_kses_post( isset( $coupon_data['coupon_start'] ) ? $coupon_data['coupon_start'] : '' );

				$has_expiry = isset( $coupon_data['coupon_expiry'] ) && 0 < $coupon_data['coupon_expiry'];

				$find_replace['[wt_sc_credit_history]']      = '';
				$find_replace['[wt_sc_credit_history_url]']  = '';
				$find_replace['[wt_sc_coupon_desc]']         = wp_kses_post( isset( $coupon_data['coupon_desc'] ) ? $coupon_data['coupon_desc'] : '' );
				$find_replace['[wt_sc_coupon_desc_wrapper]'] = ( '' !== $find_replace['[wt_sc_coupon_desc]'] ? '&nbsp;' : '' ); // to toggle description box.
				$find_replace['[wt_sc_coupon_amount]']       = Wt_Smart_Coupon_Admin::get_formatted_price( $coupon_data['coupon_amount'] ?? 10 );
				$find_replace['[wt_sc_coupon_type]']         = wp_kses_post( isset( $coupon_data['coupon_type'] ) ? $coupon_data['coupon_type'] : __( 'Cart discount', 'thetoppercentile-coupons' ) );
				$find_replace['[wt_sc_coupon_code]']         = esc_html( isset( $coupon_data['coupon_code'] ) ? $coupon_data['coupon_code'] : 'XXX-XXX-XXX' );

				if ( 'expired_coupon' === $coupon_type ) {
					$find_replace['[wt_sc_coupon_exp_overlay_visibility]'] = 'visible';
					$find_replace['[wt_sc_coupon_exp_clock_icon]']         = 'none';
					$find_replace['[wt_sc_coupon_expiry]']                 = '';
					$find_replace['[wt_sc_coupon_expiry_ctm]']             = '';
				} else {
					$find_replace['[wt_sc_coupon_exp_overlay_visibility]'] = 'hidden';
					$find_replace['[wt_sc_coupon_exp_clock_icon]']         = 'flex';

					$find_replace['[wt_sc_coupon_expiry]']     = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts( $coupon_data['coupon_expiry'], 'expiry_date' );
					$find_replace['[wt_sc_coupon_expiry_ctm]'] = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts( $coupon_data['coupon_expiry'], 'expiry_date' );
				}
			} else {

				// Start date.
				$find_replace['[wt_sc_coupon_start]'] = ( isset( $coupon_data['start_date'] ) && 0 < $coupon_data['start_date'] ) ? Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts( $coupon_data['start_date'] ) : '';

				// Expiry date.

				$empty_expiry = array(
					'[wt_sc_coupon_expiry]'         => '',
					'[wt_sc_coupon_expiry_ctm]'     => '',
					'[wt_sc_coupon_exp_clock_icon]' => 'none',
					'[wt_sc_coupon_exp_overlay_visibility]' => 'hidden',
				);
				$find_replace = array_merge( $find_replace, $empty_expiry );

				$has_expiry = isset( $coupon_data['coupon_expires'] ) && 0 < $coupon_data['coupon_expires'];

				if ( $has_expiry ) {

					$current_time_local = strtotime( current_time( 'mysql' ) );

					if ( $current_time_local > $coupon_data['coupon_expires'] ) { // Expired.
						$find_replace['[wt_sc_coupon_exp_overlay_visibility]'] = 'visible';
					} else {
						$find_replace['[wt_sc_coupon_exp_clock_icon]'] = 'flex';
						$find_replace['[wt_sc_coupon_expiry]']         = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts( $coupon_data['coupon_expires'], 'expiry_date' );
						$find_replace['[wt_sc_coupon_expiry_ctm]']     = Wt_Smart_Coupon_Public::get_coupon_start_expiry_date_texts( $coupon_data['coupon_expires'], 'expiry_date' );
					}
				}

				// Usage limit.
				$usage_limit          = $coupon->get_usage_limit();
				$usage_count          = $coupon->get_usage_count();
				$usage_limit_per_user = $coupon->get_usage_limit_per_user();

				$usage_limit_reached = 0 < $usage_limit && $usage_count >= $usage_limit;
				$user_id             = get_current_user_id();
				if ( 0 < $user_id && ! $usage_limit_reached && 0 < $usage_limit_per_user ) {
					$data_store          = $coupon->get_data_store();
					$used_by_user        = (int) $data_store->get_usage_by_user_id( $coupon, $user_id );
					$usage_limit_reached = 0 < $usage_limit_per_user && $used_by_user >= $usage_limit_per_user;
				}

				if ( $usage_limit_reached ) {
					$find_replace['[wt_sc_coupon_exp_overlay_visibility]'] = 'block';
					$find_replace['[wt_sc_coupon_expiry]']                 = '';
					$find_replace['[wt_sc_coupon_expiry_ctm]']             = '';
					$find_replace['[wt_sc_coupon_exp_clock_icon]']         = 'none';
				}

				// Description.
				$find_replace['[wt_sc_coupon_desc]']         = wp_kses_post( $coupon->get_description() );
				$find_replace['[wt_sc_coupon_desc_wrapper]'] = ( '' !== $find_replace['[wt_sc_coupon_desc]'] ? '&nbsp;' : '' ); // to toggle the visibility.

				// Code.
				$find_replace['[wt_sc_coupon_code]'] = esc_html( $coupon->get_code() );

				if ( class_exists( 'Wbte_Smart_Coupon_Bogo_Common' )
					&& method_exists( 'Wbte_Smart_Coupon_Bogo_Common', 'is_bogo' )
					&& method_exists( 'Wbte_Smart_Coupon_Bogo_Common', 'is_auto_bogo' )
					&& Wbte_Smart_Coupon_Bogo_Common::is_bogo( $coupon_id )
				) {
					$bogo_code_title = Wbte_Smart_Coupon_Bogo_Common::is_auto_bogo( $coupon_id ) ? get_post_meta( $coupon_id, 'wbte_sc_bogo_coupon_name', true ) : $coupon->get_code();

					$find_replace['[wt_sc_coupon_code]'] = esc_html( $bogo_code_title );

				}

				// Coupon type.
				$find_replace['[wt_sc_coupon_type]'] = esc_html( $coupon_data['coupon_type'] );

				// Amount.
				$find_replace['[wt_sc_coupon_amount]'] = esc_html( $coupon_data['coupon_amount'] );

				$find_replace['[wt_sc_credit_history_url]'] = '';
				$find_replace['[wt_sc_credit_history]']     = ''; // to hide credit history block.
			}

			/**
			 *  To remove `click to apply` title and pointer cursor from coupon block in email.
			 *
			 *  @since 1.6.0
			 */
			if ( 'email_coupon' === $coupon_type ) {
				$find_replace['[wt_sc_single_coupon_title]'] = '';
				$find_replace['[wt_sc_single_coupon_class]'] = '';
			}

			/** Add coupon code for attribute. @since 1.6.0  */
			$find_replace['[wt_sc_coupon_code_attr]'] = esc_attr( $coupon->get_code() );

			/**
			 * Filter to alter/add placeholder values.
			 *
			 * @since 1.4.7
			 * @param array $find_replace Placeholder and corresponding values.
			 * @param WC_Coupon $coupon Coupon object.
			 * @param string $coupon_type Coupon type.
			 */
			$find_replace = apply_filters( 'wt_sc_alter_coupon_html_placeholder_values', $find_replace, $coupon, $coupon_type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

			$html = self::hide_empty_elements( $find_replace, $html, $coupon_type );

			$refer_html = ''; // applicable for preview mode only.

			if ( $is_preview_mode ) {
				$refer_find_replace = $find_replace;

				foreach ( $colors as $color_k => $color_v ) {
					unset( $refer_find_replace[ '[wt_sc_color_' . $color_k . ']' ] );
				}

				/* replace the placeholder with real values. Except color placeholders */
				$refer_html = '<div class="wt_sc_template_refer" data-color-config="' . esc_attr( implode( '|', $colors ) ) . '">' . str_replace( array_keys( $refer_find_replace ), array_values( $refer_find_replace ), $html ) . '</div>';

				// remove style blocks, because style blocks are already available along with main HTML.
				$style_blocks = self::get_style_blocks( $refer_html );
				$refer_html   = self::remove_style_blocks( $refer_html, $style_blocks );

				$refer_html = str_replace( '[wbte_sc_wbte_sc_svg_random_id]', 'wbte_sc_svg_random_id-' . uniqid() . '-end', $refer_html );
			}

			$find_replace['[wbte_sc_wbte_sc_svg_random_id]'] = 'wbte_sc_svg_random_id-' . uniqid() . '-end';

			/* replace the placeholder with real values */
			$html = str_replace( array_keys( $find_replace ), array_values( $find_replace ), $html );

			if ( ! $include_css ) {
				$style_blocks = self::get_style_blocks( $html );
				$html         = self::remove_style_blocks( $html, $style_blocks );
			}

			$html .= $refer_html; // applicable for preview mode only.
		}

		/**
		 * Filter to alter final coupon template HTML.
		 *
		 * @since 1.4.7
		 * @param string $html HTML.
		 * @param array $coupon_style Coupon style.
		 * @param string $coupon_type Coupon type.
		 * @param WC_Coupon $coupon Coupon object.
		 */
		return apply_filters( 'wt_sc_alter_final_coupon_template_html', $html, $coupon_style, $coupon_type, $coupon ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}


	/**
	 *  Hide empty(without value) elements in coupon HTML.
	 *  This function will add a CSS class with `display:none` property to the element. Class name: wt_sc_hidden
	 *
	 *  @since  1.4.7
	 *  @param  array  $find_replace   Placeholder and corresponding values.
	 *  @param  string $html           Target HTML.
	 *  @param  string $coupon_type    Coupon type. Eg: available_coupon, expired etc. Default: available_coupon.
	 *  @return  string     $html           Processed HTML.
	 */
	private static function hide_empty_elements( $find_replace, $html, $coupon_type ) {
		$elements_to_hide = array(
			'wt_sc_coupon_start',
			'wt_sc_coupon_expiry',
			'wt_sc_credit_history',
			'wt_sc_coupon_desc_wrapper',
		);

		/**
		 * Alter the element list.
		 * CSS class name of the elements to be hidden. The class name and placeholder name must be same.
		 *
		 * @since 1.4.7
		 * @param array $elements_to_hide Elements to hide.
		 * @param string $coupon_type Coupon type. Eg: available_coupon, expired etc. Default: available_coupon.
		 * @return array Elements to hide.
		 */
		$elements_to_hide = apply_filters( 'wt_sc_alter_hide_empty_elements', $elements_to_hide, $coupon_type ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		foreach ( $elements_to_hide as $key => $value ) {
			$css_class = self::sanitize_css_class_name( $value );

			if ( isset( $find_replace[ '[' . $value . ']' ] ) ) {
				if ( '' === $find_replace[ '[' . $value . ']' ] ) {
					$html = self::add_css_class( $css_class, $html, self::TO_HIDE_CSS );
				}
			} else {
				$find_replace[ '[' . $value . ']' ] = '';
				$html                               = self::add_css_class( $css_class, $html, self::TO_HIDE_CSS );
			}
		}

		return $html;
	}


	/**
	 *  Add CSS class to an HTML dom element
	 *
	 *  @since  1.4.7
	 *  @param  string $elm_class      Existing class name of the element for selecting element.
	 *  @param  string $html           Target HTML.
	 *  @param  string $new_class      New CSS class to append.
	 *  @return  string     $html           Processed HTML
	 */
	public static function add_css_class( $elm_class, $html, $new_class ) {
		$match = self::get_element_by_class( $elm_class, $html );

		if ( $match ) {
			$elm_class     = $match[1] . $elm_class . $match[2];
			$new_class_arr = self::filter_css_classes( $elm_class . ' ' . $new_class );
			$new_class     = implode( ' ', $new_class_arr );

			return str_replace( $elm_class, $new_class, $html );
		}

		return $html;
	}

	/**
	 *  Take the class attribute code section of an HTML element by class name.
	 *
	 *  @since  1.4.7
	 *  @param  string $elm_class      Existing class name of the element for selecting element.
	 *  @param  string $html           Target HTML.
	 *  @return  array|bool     If found, then return an array of matches otherwise false.
	 */
	public static function get_element_by_class( $elm_class, $html ) {
		$matches = array();
		$re      = '/<[^>]*class\s*=\s*["\']([^"\']*)' . $elm_class . '(.*?[^"\']*)["\'][^>]*>/m';

		if ( preg_match( $re, $html, $matches ) ) {
			if ( 0 < strlen( $matches[1] ) && ' ' !== substr( $matches[1], -1 ) ) {
				return false;
			}

			if ( 0 < strlen( $matches[2] ) && ' ' !== substr( $matches[2], 0, 1 ) ) {
				return false;
			}

			return $matches;

		} else {
			return false;
		}
	}


	/**
	 *  Remove duplicate and empty class names from class attribute of an element
	 *
	 *  @since  1.4.7
	 *  @param  string $classes      Class attribute value of the element.
	 *  @return  array     Array of unique class names
	 */
	private static function filter_css_classes( $classes ) {
		$class_arr = explode( ' ', $classes );

		return array_unique( array_filter( $class_arr ) );
	}

	/**
	 *  Sanitize CSS class name string.
	 *
	 *  @since  1.4.7
	 *  @param  string $str      Class name.
	 *  @return  string     Sanitized class name.
	 */
	private static function sanitize_css_class_name( $str ) {
		return preg_replace( '/[^\-_a-zA-Z0-9]+/', '', $str );
	}

	/**
	 *  Get style block from HTML
	 *
	 *  @since    1.4.7
	 *  @param    string $html      template html.
	 *  @param    bool   $inner_content   Inner content.
	 *  @return   array  $style_arr  style blocks.
	 */
	private static function get_style_blocks( $html, $inner_content = false ) {
		$re = '/<style.*?>(.*?)<\/style>/sm';

		if ( preg_match_all( $re, $html, $style_arr ) ) {
			$style_arr = ( $inner_content ? $style_arr[1] : $style_arr[0] );
		} else {
			$style_arr = array();
		}

		return $style_arr;
	}


	/**
	 *  Remove style block from HTML
	 *
	 *  @since    1.4.7
	 *  @param  string $html       template html.
	 *  @param  array  $style_arr  style blocks.
	 *  @return string  $html       style removed html.
	 */
	public static function remove_style_blocks( $html, $style_arr ) {
		return str_replace( $style_arr, '', $html );
	}


	/**
	 *  Translatable strings are written as __[string]__ in templates, so we need to process those strings.
	 *  This function act as a calback for translation conversion code
	 *
	 *  @since    1.4.7
	 *  @param  array $matches       array of match, when searching for translatable strings in the template HTML.
	 *  @return string  Translation compatible string.
	 */
	private static function add_translation_for_coupon_html( $matches ) {
		return ( is_array( $matches ) && isset( $matches[1] ) && '' !== trim( $matches[1] ) ? $matches[1] : '' );
	}


	/**
	 *  This function will print CSS for default coupon code block
	 *
	 *  @since    1.4.7
	 */
	public static function get_coupon_default_css() {
		$coupon_css = '.wt_sc_single_coupon{ display:inline-block; width:300px; max-width:350px; height:auto; padding:5px; text-align:center; background:#2890a8; color:#ffffff; position:relative; box-sizing: border-box; direction: ltr; }
        .wt_sc_single_coupon .wt_sc_hidden{ display:none; }
        .wt_sc_single_coupon.active-coupon{ cursor:pointer; }
        .wt_sc_coupon_amount{ font-size:30px; margin-right:5px; line-height:22px; font-weight:500; }
        .wt_sc_coupon_type{ font-size:20px;  font-weight:500; line-height:22px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .wt_sc_coupon_code{ float:left; width:100%; font-size:19px; line-height:22px; }
        .wt_sc_coupon_code code{ background:none; font-size:15px; opacity:0.7; display:inline-block; line-height:22px; max-width:100%; word-wrap:break-word; }
        .wt_sc_coupon_content{ padding:10px 0px; float:left; width:100%; }   
        .wt_sc_coupon_desc_wrapper:hover .wt_sc_coupon_desc{ display:block}
        .wt_sc_coupon_desc{position:absolute; top:-18px; background:#333; color:#fff; text-shadow:none; text-align:left; font-size:12px; width:200px; right:-220px; padding:10px 20px; z-index:100; border-radius:8px; display:none; }
        .wt_sc_coupon_desc ul{margin:0!important;text-align:left;list-style-type:disc}
        .wt_sc_coupon_desc ul li{padding:0;margin:0;width:100%;height:auto;min-height:auto;list-style-type:disc!important}
        .wt_sc_coupon_desc_wrapper i.info{position:absolute; top:6px; right:10px; font-size:13px; font-weight:700; border-radius:100%; width:20px; height:20px; background:#fff; text-shadow:none; color:#2890a8; font-style:normal; cursor:pointer; line-height:20px; box-shadow:1px 1px 4px #333; }

        .wt_sc_credit_history_url{font-size:13px;font-weight:700;border-radius:100%;width:20px;height:20px;text-shadow:none;font-style:normal;cursor:pointer;position:absolute;right:12px;bottom:10px;text-align:center;line-height:20px;text-decoration:none!important;background:#fff}
        .wt_sc_credit_history_url span{font:bold 14px/1 dashicons}
        a.wt_sc_credit_history_url span:before{ line-height:20px; color:#2890a8; }
        .wbte_sc_coupon_layout_expired_text{ color: #D63638; font-size: 13px; font-weight: 500; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; } 
        .wbte_sc_coupon_layout_expired_overlay{ position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: #c5c5c580; z-index: 9; }

        @media only screen and (max-width: 700px)  {
          .wt_sc_coupon_content{z-index: 5; }
          .wt_sc_single_coupon .wt_sc_coupon_desc{ z-index: 100; right:auto; top:30px; left:0px; }
        }';

		/**
		 * Filter to alter coupon default CSS.
		 *
		 * @since 1.4.7
		 * @param string $coupon_css Coupon default CSS.
		 */
		return apply_filters( 'wt_sc_alter_coupon_default_css', $coupon_css ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}


	/**
	 *  Change selected style if it is not in the list of available styles
	 *
	 *  @since    2.2.0
	 */
	public function adjust_coupon_style_settings() {
		$coupon_style_settings = get_option( self::$module_id_static, array() );
		if ( ! empty( $coupon_style_settings ) ) {
			$available_coupon_styles = array( 'minimal_outline', 'classic_ticket', 'stitched_padding', 'stitched_edge', 'ticket_style', 'plain_coupon' );
			foreach ( $coupon_style_settings as $coupon_type => $coupon_style_arr ) {
				if ( ! in_array( $coupon_style_arr['style'], $available_coupon_styles, true ) ) {
					$coupon_style_settings[ $coupon_type ]['style'] = 'minimal_outline';
				}
			}
			update_option( self::$module_id_static, $coupon_style_settings );
		}
	}
}

Wt_Smart_Coupon_Style::get_instance();
