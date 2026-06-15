<?php
/**
 * Webtoffee Security Library
 * Includes Data sanitization, Access checking
 *
 * @package Wt_Smart_Coupon
 */

if ( ! class_exists( 'Wt_Security_Helper' ) ) {

	/**
	 * Webtoffee Security Library
	 *
	 * Includes Data sanitization, Access checking
	 */
	class Wt_Smart_Coupon_Security_Helper {


		/**
		 *   Data sanitization function.
		 *
		 *   @param mixed  $val value to sanitize.
		 *   @param string $key array key in the validation rule.
		 *   @param array  $validation_rule array of validation rules. Eg: array('field_key' => array('type' => 'textarea')).
		 *   @return mixed sanitized value.
		 */
		public static function sanitize_data( $val, $key, $validation_rule = array() ) {
			if ( isset( $validation_rule[ $key ] ) && is_array( $validation_rule[ $key ] ) ) {
				if ( isset( $validation_rule[ $key ]['type'] ) ) {
					$val = self::sanitize_item( $val, $validation_rule[ $key ]['type'] );
				}
			} else // if no rule is specified then it will be treated as text.
			{
				$val = self::sanitize_item( $val, 'text' );
			}
			return $val;
		}


		/**
		 *   Sanitize individual data item
		 *
		 *   @param mixed  $val value to sanitize.
		 *   @param string $type value type.
		 *   @return mixed sanitized value.
		 */
		public static function sanitize_item( $val, $type = '' ) {
			switch ( $type ) {
				case 'text':
					$val = sanitize_text_field( $val );
					break;
				case 'text_arr':
					$val = self::sanitize_arr( $val );
					break;
				case 'url':
					$val = esc_url_raw( $val );
					break;
				case 'url_arr':
					$val = self::sanitize_arr( $val, 'url' );
					break;
				case 'textarea':
					$val = sanitize_textarea_field( $val );
					break;
				case 'int':
					$val = intval( $val );
					break;
				case 'int_arr':
					$val = self::sanitize_arr( $val, 'int' );
					break;
				case 'absint':
					$val = absint( $val );
					break;
				case 'absint_arr':
					$val = self::sanitize_arr( $val, 'absint' );
					break;
				case 'float':
					$val = floatval( $val );
					break;
				case 'post_content':
					$val = wp_kses_post( $val );
					break;
				case 'hex':
					$val = sanitize_hex_color( $val );
					break;
				case 'skip': /* skip the validation */
					$val = $val;
					break;
				case 'file_name':
					$val = sanitize_file_name( $val );
					break;
				case 'email':
					$val = sanitize_email( $val );
					break;
				case 'email_arr':
					$val = self::sanitize_arr( $val, 'email' );
					break;
				default:
					$val = sanitize_text_field( $val );
			}

			return $val;
		}

		/**
		 *   Recursive array sanitization function
		 *
		 *   @param mixed  $arr value to sanitize.
		 *   @param string $type value type.
		 *   @return mixed sanitized value.
		 */
		public static function sanitize_arr( $arr, $type = 'text' ) {
			if ( is_array( $arr ) ) {
				$out = array();
				foreach ( $arr as $k => $arrv ) {
					if ( is_array( $arrv ) ) {
						$out[ $k ] = self::sanitize_arr( $arrv, $type );
					} else {
						$out[ $k ] = self::sanitize_item( $arrv, $type );
					}
				}
				return $out;
			} else {
				return self::sanitize_item( $arr, $type );
			}
		}

		/**
		 *   User accessibility. Function checks user logged in status, nonce and role access.
		 *
		 *   @param string $plugin_id unique plugin id. Note: This id is used as an identifier in filter name so please use characters allowed in filters.
		 *   @param string $nonce_id Nonce id. If not specified then uses plugin id.
		 *   @return boolean if user allowed or not.
		 */
		public static function check_write_access( $plugin_id, $nonce_id = '' ) {
			$er = true;

			if ( ! is_user_logged_in() ) {
				$er = false;
			}

			if ( true === $er ) {
				$nonce    = ( isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '' );
				$nonce    = ( is_array( $nonce ) ? $nonce[0] : $nonce ); // in some cases multiple nonces are declared.
				$nonce_id = ( '' === $nonce_id ? $plugin_id : $nonce_id ); // if nonce id not provided then uses plugin id as nonce id.

				if ( ! ( wp_verify_nonce( $nonce, $nonce_id ) ) ) {
					$er = false;
				} elseif ( ! self::check_role_access( $plugin_id ) ) {
					$er = false;
				}
			}
			return $er;
		}


		/**
		 *   Checks if user role has access
		 *
		 *   @since 1.0.0
		 *   @param string $plugin_id unique plugin id. Note: This id is used as an identifier in filter name so please use characters allowed in filters.
		 *   @return boolean if user allowed or not.
		 */
		public static function check_role_access( $plugin_id ) {
			$roles = array( 'manage_options' );
			/**
			 * Dynamic filter hook based on plugin id to alter user role access.
			 *
			 * @since 1.0.0
			 *
			 * @param array $roles List of roles.
			 */
			$roles      = apply_filters( 'wt_' . $plugin_id . '_alter_role_access', $roles ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			$roles      = ( isset( $roles ) && is_array( $roles ) ) ? $roles : array();
			$is_allowed = false;

			foreach ( $roles as $role ) {
				if ( current_user_can( $role ) ) {
					$is_allowed = true;
					break;
				}
			}
			return $is_allowed;
		}

		/**
		 * Checks if the user has capability.
		 * Default capability is 'manage_woocommerce'.
		 * By using filter 'wbte_sc_alter_user_capabilities_checking_list' we can alter the capability list.
		 *
		 * @since 1.8.3
		 *
		 * @return bool true if user has capability else false.
		 */
		public static function check_user_has_capability() {

			$capabilities = array( 'manage_woocommerce' );
			/**
			 * Filter hook to alter user capabilities checking list.
			 *
			 * @since 1.8.3
			 *
			 * @param array $capabilities List of capabilities.
			 */
			$capabilities = apply_filters( 'wbte_sc_alter_user_capabilities_checking_list', $capabilities );
			$capabilities = ( isset( $capabilities ) && is_array( $capabilities ) ) ? $capabilities : array();
			$is_allowed   = false;

			foreach ( $capabilities as $capability ) {
				if ( current_user_can( $capability ) ) {
					$is_allowed = true;
					break;
				}
			}
			return $is_allowed;
		}

		/**
		 * Get the menu items capability.
		 *
		 * @since 2.2.4
		 *
		 * @return array List of menu items capability.
		 */
		public static function menu_items_capability() {

			$menu_capabilities = array(
				'all_coupons'      => 'edit_shop_coupons',
				'add_coupon'       => 'edit_shop_coupons',
				'general_settings' => 'manage_woocommerce',
				'bogo'             => 'edit_shop_coupons',
				'coupon_category'  => 'manage_woocommerce',
			);

			/**
			 * Filter the menu items capability.
			 *
			 * @since 2.2.4
			 *
			 * @param array $menu_capabilities List of menu items capability.
			 */
			$menu_capabilities = apply_filters( 'wbte_sc_alter_menu_items_capability', $menu_capabilities );

			return $menu_capabilities;
		}

		/**
		 * Safe custom unserialize function that handles only basic types
		 *
		 * @since 2.2.4
		 * @param string $data Serialized data.
		 * @return mixed Unserialized data (only int, string, bool, array).
		 */
		public static function wt_unserialize_safe( $data ) {
			if ( empty( $data ) ) {
				return false;
			}

			$offset     = 0;
			$references = array();

			$unserialize_value = function ( &$offset ) use ( $data, &$unserialize_value, &$references ) {
				if ( ! isset( $data[ $offset ] ) ) {
					return false;
				}

				$type = $data[ $offset ];
				++$offset;

				switch ( $type ) {
					case 's': // String.
						if ( ! preg_match( '/:(\d+):"/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$length       = (int) $matches[1];
						$offset      += strlen( $matches[0] );
						$value        = substr( $data, $offset, $length );
						$offset      += $length + 2;
						$references[] = $value;
						return $value;

					case 'U': // Unicode string (like string).
						if ( ! preg_match( '/:(\d+):"/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$length       = (int) $matches[1];
						$offset      += strlen( $matches[0] );
						$value        = mb_substr( $data, $offset, $length, 'UTF-8' );
						$offset      += $length + 2;
						$references[] = $value;
						return $value;

					case 'i': // Integer.
						if ( ! preg_match( '/:(-?\d+);/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$offset      += strlen( $matches[0] );
						$value        = (int) $matches[1];
						$references[] = $value;
						return $value;

					case 'd': // Double.
						if ( ! preg_match( '/:(-?\d+(\.\d+)?);/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$offset      += strlen( $matches[0] );
						$value        = (float) $matches[1];
						$references[] = $value;
						return $value;

					case 'b': // Boolean.
						if ( ! preg_match( '/:(\d);/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$offset      += strlen( $matches[0] );
						$value        = (bool) $matches[1];
						$references[] = $value;
						return $value;

					case 'N': // NULL.
						++$offset;
						$references[] = null;
						return null;

					case 'a': // Array.
						if ( ! preg_match( '/:(\d+):{/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$num_elements = (int) $matches[1];
						$offset      += strlen( $matches[0] );
						$result       = array();
						$references[] = &$result;

						for ( $i = 0; $i < $num_elements; $i++ ) {
							$key            = $unserialize_value( $offset );
							$value          = $unserialize_value( $offset );
							$result[ $key ] = $value;
						}

						++$offset; // Skip '}'.
						return $result;

					case 'O': // Object (as array).
						if ( ! preg_match( '/:(\d+):"([^"]+)":(\d+):{/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$num_properties = (int) $matches[3];
						$offset        += strlen( $matches[0] );
						$result         = array();
						$references[]   = &$result;

						for ( $i = 0; $i < $num_properties; $i++ ) {
							$key            = $unserialize_value( $offset );
							$value          = $unserialize_value( $offset );
							$result[ $key ] = $value;
						}

						++$offset; // Skip '}'.
						return $result;

					case 'r': // Reference.
						if ( ! preg_match( '/:(\d+);/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$offset += strlen( $matches[0] );
						$ref_id  = (int) $matches[1] - 1;
						return isset( $references[ $ref_id ] ) ? $references[ $ref_id ] : null;

					case 'R': // Object reference (rare).
						if ( ! preg_match( '/:(\d+);/', $data, $matches, 0, $offset ) ) {
							return false;
						}
						$offset += strlen( $matches[0] );
						$ref_id  = (int) $matches[1] - 1;
						return isset( $references[ $ref_id ] ) ? $references[ $ref_id ] : null;

					case 'C': // Custom-serialized object => UNSAFE.
						// Skip entirely — executing unserialize() on custom class is unsafe.
						return false;

					default:
						return false;
				}
			};

			return $unserialize_value( $offset );
		}
	}
}
