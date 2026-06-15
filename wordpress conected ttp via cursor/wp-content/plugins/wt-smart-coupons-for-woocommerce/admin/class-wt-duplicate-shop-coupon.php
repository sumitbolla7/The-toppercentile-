<?php
/**
 * Duplicate coupon
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WT_Duplicate_Shop_Coupon' ) ) {

	/**
	 * Duplicate coupon class.
	 */
	class WT_Duplicate_Shop_Coupon {

		/**
		 *  Class instance
		 *
		 *  @var null|object $instance instance of class or null
		 */
		private static $instance = null;

		/**
		 * Constructor function of the class
		 */
		public function __construct() {
			add_action( 'admin_action_wt_duplicate_post_as_draft', array( $this, 'wt_duplicate_post_as_draft' ) );
			add_filter( 'post_row_actions', array( $this, 'wt_duplicate_post_link' ), 10, 2 );
		}

		/**
		 * Get Instance
		 *
		 * @since 2.0.0
		 * @return object Class instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new WT_Duplicate_Shop_Coupon();
			}
			return self::$instance;
		}

		/**
		 * Ajax action to duplicate coupon from coupon list page.
		 */
		public function wt_duplicate_post_as_draft() {

			if ( ! class_exists( 'Wt_Smart_Coupon_Security_Helper' ) || ! method_exists( 'Wt_Smart_Coupon_Security_Helper', 'check_user_has_capability' ) || ! Wt_Smart_Coupon_Security_Helper::check_user_has_capability() ) {
				wp_die( esc_html__( 'You do not have sufficient permission to perform this operation', 'thetoppercentile-coupons' ) );
			}
			if (
				! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) )
				|| ! isset( $_REQUEST['action'] )
				|| 'wt_duplicate_post_as_draft' !== $_REQUEST['action']
			) {
				wp_die( esc_html__( 'No post to duplicate has been supplied!', 'thetoppercentile-coupons' ) );
			}

			if ( ! isset( $_GET['duplicate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), basename( __FILE__ ) ) ) {
				wp_die( esc_html__( 'Invalid nonce', 'thetoppercentile-coupons' ) );
			}

			$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : absint( $_POST['post'] );

			$post = get_post( $post_id );

			if ( isset( $post ) && null !== $post ) {
				$new_post_id = $this->clone_coupon( $post );
				wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
				exit;
			} else {
				// translators: %s is the post ID.
				wp_die( sprintf( esc_html__( 'Post creation failed, could not find original post: %s', 'thetoppercentile-coupons' ), intval( $post_id ) ) );
			}
		}

		/**
		 * Add the duplicate link to action list for post_row_actions
		 *
		 * @param  array  $actions   Array of row actions.
		 * @param  object $post      Post object.
		 * @return array             Array of row actions with duplicate text button.
		 */
		public function wt_duplicate_post_link( $actions, $post ) {

			if ( current_user_can( 'edit_posts' ) ) {
				if ( isset( $_GET['post_type'] ) && ( 'shop_coupon' === $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$href_text            = __( 'Duplicate', 'thetoppercentile-coupons' );
					$href_title           = __( 'Duplicate this item', 'thetoppercentile-coupons' );
					$actions['duplicate'] = '<a href="' . wp_nonce_url( 'admin.php?action=wt_duplicate_post_as_draft&post=' . $post->ID, basename( __FILE__ ), 'duplicate_nonce' ) . '" title="' . $href_title . '" rel="permalink">' . $href_text . '</a>';
				}
			}
			return $actions;
		}

		/**
		 * Clone coupon
		 *
		 * @since 2.0.0 Moved from 'wt_duplicate_post_as_draft' to separate function. Also change to 'update_post_meta' instead of 'insert_post_meta' because data not saving correctly in lookup table.
		 * @param  int|object $post     Post ID or Post object.
		 * @param  string     $p_title      New coupon title. If empty, it will be generated automatically.
		 * @return int                  New coupon ID.
		 */
		public static function clone_coupon( $post, $p_title = '' ) {

			global $wpdb;
			$current_user    = wp_get_current_user();
			$new_post_author = $current_user->ID;

			if ( is_int( $post ) ) {
				$post = get_post( $post );
			}
			$post_id = $post->ID;

			if ( empty( $p_title ) ) {
				$maybe_post_title = $post->post_title;
				$p_title          = $maybe_post_title;
				$counter          = 1;

				while ( post_exists( $p_title ) ) {
					$p_title = $maybe_post_title . $counter;
					++$counter;
				}
			}

			/*
			* new post data array
			*/
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				/**
				 * Filter the default duplicate coupon status.
				 *
				 * @since 1.0.0
				 * @param string $default_duplicate_coupon_status Default duplicate coupon status.
				 */
				'post_status'    => apply_filters( 'wt_smartcoupon_default_duplicate_coupon_status', 'publish' ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				'post_title'     => $p_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order,
			);

			$new_post_id = wp_insert_post( $args );

			$taxonomies = get_object_taxonomies( $post->post_type ); // returns array of taxonomy names for post type, ex array("category", "post_tag");.
			foreach ( $taxonomies as $taxonomy ) {
				$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
				wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
			}

			$post_meta_data = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d", $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! empty( $post_meta_data ) ) {

				/**
				 * Filter the meta keys that should not be cloned.
				 *
				 * @since 1.0.0
				 * @param array $meta_no_need_to_clone Meta keys that should not be cloned.
				 */
				$meta_no_need_to_clone = apply_filters( 'wt_smart_coupon_meta_no_need_to_clone', array( '_wp_old_slug', 'wt_credit_history', '_wt_smart_coupon_initial_credit' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

				foreach ( $post_meta_data as $meta_info ) {
					/**
					 * [Bug fix] null value metas converted to string
					 *
					 *  @since 1.4.1
					 */
					if ( is_null( $meta_info->meta_value ) ) {
						continue;
					}

					$meta_key = $meta_info->meta_key;

					if ( ! in_array( $meta_key, $meta_no_need_to_clone, true ) ) {
						$meta_value = addslashes( is_null( $meta_info->meta_value ) ? '' : $meta_info->meta_value );

						if ( is_serialized( $meta_value ) ) {
							$meta_value = Wt_Smart_Coupon_Security_Helper::wt_unserialize_safe( stripslashes( $meta_value ) );
						}

						if ( '_wbte_sc_auto_coupon_priority' === $meta_key ) {
							$meta_value = $new_post_id;
						} elseif ( 'usage_count' === $meta_key || '_used_by' === $meta_key ) {
							$meta_value = 0;
						}

						update_post_meta( $new_post_id, $meta_key, $meta_value );
					}
				}
			}

			return $new_post_id;
		}
	}
	WT_Duplicate_Shop_Coupon::get_instance();
}
