<?php
/**
 *  Compatability to WPML
 *
 * @since 1.4.5
 *
 * @package  Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Wt_Smart_Coupon_Multilanguage' ) ) {
	/**
	 * Compatability to WPML
	 *
	 *  @since 1.4.5
	 *
	 *  Webtoffee TheTopPercentile Coupon Multi Language Class
	 */
	class Wt_Smart_Coupon_Multilanguage {

		/**
		 * Instance
		 *
		 * @var object|null
		 */
		private static $instance;

		/**
		 * Get Instance
		 *
		 * @return object|null
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Check if multilanguage plugin is active
		 *
		 * @return bool True if multilanguage plugin is active, false otherwise.
		 */
		public function is_multilanguage_plugin_active() {
			$status = false;

			if ( defined( 'ICL_LANGUAGE_CODE' ) || defined( 'POLYLANG_FILE' ) ) {
				$status = true;
			}

			return $status;
		}

		/**
		 * Get all the id's of translation
		 *
		 * @param string $product_id Product ID.
		 * @param string $post_type Post type.
		 * @return array Array of translated product IDs.
		 */
		public function get_all_translations( $product_id, $post_type = 'post' ) {
			global $sitepress;
			$translated_products = array();
			if ( ! empty( $product_id ) ) {
				if ( $this->is_multilanguage_plugin_active() ) {
					// Polylang.
					if ( function_exists( 'icl_object_id' ) && $sitepress ) {
						$trid         = $sitepress->get_element_trid( $product_id, $post_type );
						$translations = $sitepress->get_element_translations( $trid, $post_type );
						foreach ( $translations as $key => $translation ) {
							$translated_products[] = $translation->element_id;
						}
					}
				}
			}
			return $translated_products;
		}
	}
}
