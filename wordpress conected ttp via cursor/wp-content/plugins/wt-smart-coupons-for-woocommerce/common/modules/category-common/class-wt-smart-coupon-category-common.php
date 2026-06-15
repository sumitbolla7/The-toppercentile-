<?php
/**
 * Coupon category common
 *
 * @link
 * @since 1.3.5
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'Wt_Smart_Coupon_Category_Common' ) ) {

	/**
	 * Coupon category common
	 *
	 * @link
	 * @since 1.3.5
	 *
	 * @package  Wt_Smart_Coupon
	 */
	class Wt_Smart_Coupon_Category_Common {
		/**
		 * Module base
		 *
		 * @var string
		 */
		public $module_base = 'coupon_category';

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
		 * Constructor
		 */
		public function __construct() {
			$this->module_id        = Wt_Smart_Coupon::get_module_id( $this->module_base );
			self::$module_id_static = $this->module_id;

			add_filter( 'init', array( $this, 'register_coupon_category_taxonomy' ) );

			add_action( 'restrict_manage_posts', array( $this, 'add_coupon_category_filter' ) );
			add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'add_coupon_category_column' ) );
			add_filter( 'manage_shop_coupon_posts_custom_column', array( $this, 'add_coupon_category_column_content' ), 10, 2 );

			add_filter( 'woocommerce_screen_ids', array( $this, 'add_to_wc_screens' ), 10, 1 );

			/**
			 *  Add to Smart coupon admin menu
			 *
			 *  @since 1.4.4
			 */
			add_filter( 'wt_sc_admin_menu', array( $this, 'add_admin_menu' ) );

			add_action( 'shop_coupon_cat_pre_add_form', array( $this, 'promo_before_coupon_category_form' ) );

			add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );
		}

		/**
		 * Get Instance
		 *
		 * @since 1.3.5
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new Wt_Smart_Coupon_Category_Common();
			}
			return self::$instance;
		}

		/**
		 * Coupon category column head
		 *
		 * @since 1.3.5
		 * @param array $columns Columns list.
		 */
		public function add_coupon_category_column( $columns ) {
			$columns['coupon_categories'] = __( 'Categories', 'thetoppercentile-coupons' );
			return $columns;
		}

		/**
		 * Coupon category column content
		 *
		 * @since 1.3.5
		 * @param string $column     Column name.
		 * @param int    $coupon_id  Coupon ID.
		 */
		public function add_coupon_category_column_content( $column, $coupon_id ) {
			if ( 'coupon_categories' !== $column ) {
				return;
			}

			$categories = get_the_terms( $coupon_id, 'shop_coupon_cat' );

			if ( is_array( $categories ) && ! empty( $categories ) ) {
				$out             = array();
				$cat_filter_link = admin_url( 'edit.php?post_type=shop_coupon&shop_coupon_cat=' );
				foreach ( $categories as $category ) {
					$out[] = '<a href="' . esc_attr( $cat_filter_link . $category->slug ) . '">' . esc_html( $category->name ) . '</a>';
				}
				echo wp_kses_post( implode( ', ', $out ) );
			} else {
				echo '--';
			}
		}

		/**
		 * Coupon category filter select box in coupon listing page
		 *
		 * @since 1.3.5
		 * @param string $post_type Post type.
		 */
		public function add_coupon_category_filter( $post_type ) {
			if ( 'shop_coupon' !== $post_type ) {
				return;
			}
			$selected_val = ( isset( $_GET['shop_coupon_cat'] ) ? Wt_Smart_Coupon_Security_Helper::sanitize_item( wp_unslash( $_GET['shop_coupon_cat'] ) ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended -- already sanitized with helper function.
			$args         = array(
				'show_count'         => true,
				'hierarchical'       => true,
				'show_uncategorized' => true,
				'pad_counts'         => true,
				'hide_empty'         => false,
				'selected'           => $selected_val,
				'show_option_none'   => __( 'Select category', 'thetoppercentile-coupons' ),
				'option_none_value'  => '',
				'value_field'        => 'slug',
				'taxonomy'           => 'shop_coupon_cat',
				'name'               => 'shop_coupon_cat',
				'orderby'            => 'name',
				'class'              => 'dropdown_shop_coupon_cat',
			);

			wp_dropdown_categories( $args );
		}


		/**
		 * Add coupon category to WC screen IDs
		 *
		 * @since 1.3.5
		 *
		 * @param array $screen_ids Screen IDs.
		 * @return array Screen IDs.
		 */
		public function add_to_wc_screens( $screen_ids ) {
			$screen_ids[] = 'edit-shop_coupon_cat';
			return $screen_ids;
		}


		/**
		 * Register coupon category taxonomy
		 *
		 * @since 1.3.5
		 */
		public function register_coupon_category_taxonomy() {
			$labels = array(
				'name'              => _x( 'Categories', 'Taxonomy General Name', 'thetoppercentile-coupons' ),
				'singular_name'     => _x( 'Category', 'Taxonomy Singular Name', 'thetoppercentile-coupons' ),
				'search_items'      => __( 'Search categories', 'thetoppercentile-coupons' ),
				'all_items'         => __( 'All categories', 'thetoppercentile-coupons' ),
				'parent_item'       => __( 'Parent category', 'thetoppercentile-coupons' ),
				'parent_item_colon' => __( 'Parent category:', 'thetoppercentile-coupons' ),
				'edit_item'         => __( 'Edit category', 'thetoppercentile-coupons' ),
				'update_item'       => __( 'Update category', 'thetoppercentile-coupons' ),
				'add_new_item'      => __( 'Add new category', 'thetoppercentile-coupons' ),
				'new_item_name'     => __( 'New category name', 'thetoppercentile-coupons' ),
				'menu_name'         => __( 'Categories', 'thetoppercentile-coupons' ),
				'view_item'         => __( 'View category', 'thetoppercentile-coupons' ),
				'popular_items'     => __( 'Popular categories', 'thetoppercentile-coupons' ),
				'not_found'         => __( 'Not found', 'thetoppercentile-coupons' ),
				'most_used'         => __( 'Most used', 'thetoppercentile-coupons' ),
			);

			$menu_capabilities     = Wt_Smart_Coupon_Security_Helper::menu_items_capability();
			$coupon_cat_capability = isset( $menu_capabilities['coupon_category'] ) ? $menu_capabilities['coupon_category'] : 'manage_woocommerce';
			$args                  = array(
				'labels'            => $labels,
				'label'             => $labels['singular_name'],
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'show_tagcloud'     => false,
				'show_in_rest'      => true,
				'show_in_menu'      => true,
				'capabilities'      => array(
					'manage_terms' => $coupon_cat_capability,
					'edit_terms'   => $coupon_cat_capability,
					'delete_terms' => $coupon_cat_capability,
					'assign_terms' => $coupon_cat_capability,
				),
			);

			register_taxonomy( 'shop_coupon_cat', array( 'shop_coupon' ), $args );
		}


		/**
		 *  Add to Smart coupon admin menu
		 *
		 *  @since 1.3.6
		 *
		 * @param array $menus Menu list.
		 * @return array Menu list.
		 */
		public function add_admin_menu( $menus ) {
			$menu_capabilities = Wt_Smart_Coupon_Security_Helper::menu_items_capability();
			$out               = array();
			foreach ( $menus as $menu ) {
				$out[] = $menu;
				if ( 'submenu' === $menu[0] && 'post-new.php?post_type=shop_coupon' === $menu[5] ) {
					$out[] = array(
						'submenu',
						WT_SC_PLUGIN_NAME,
						__( 'Coupon category', 'thetoppercentile-coupons' ),
						__( 'Coupon category', 'thetoppercentile-coupons' ),
						isset( $menu_capabilities['coupon_category'] ) ? $menu_capabilities['coupon_category'] : 'manage_woocommerce',
						'edit-tags.php?taxonomy=shop_coupon_cat&post_type=shop_coupon',
					);
				}
			}
			return $out;
		}

		/**
		 *  Add pro CTA to coupon category page.
		 *
		 * @since 2.2.0
		 */
		public function promo_before_coupon_category_form() {
			printf(
				'<div class="wbte_sc_shop_coupon_cat_pro_promo" style="background-color: #FFF7ED; display: flex; align-items: center; border-left: 4px solid #FF8C00; padding: 10px 30px; font-weight: 400; box-sizing: border-box; gap: 10px; margin-top: 40px;"><p>%s</p><a href="%s" target="_blank" style="text-decoration: none; color: #0055FF; font-weight: 500;">%s</a></div>',
				sprintf(
					// translators: 1: span opening tag, 2: span closing tag.
					esc_html__( '%1$s Unlock more ways to reward customers! %2$s Create first order, next order, milestone discounts, and more!', 'thetoppercentile-coupons' ),
					'<span style="font-weight: 600;">',
					'</span>'
				),
				esc_url(
					add_query_arg(
						array(
							'utm_source'   => 'free_plugin_smart_coupon_giveaway',
							'utm_medium'   => 'smart_coupons_basic',
							'utm_campaign' => 'smart_coupons',
							'utm_content'  => WEBTOFFEE_SMARTCOUPON_VERSION,
						),
						'https://www.thetoppercentile.com/product/smart-coupons-for-woocommerce/'
					)
				),
				esc_html__( 'Upgrade to Pro', 'thetoppercentile-coupons' )
			);
		}

		/**
		 *  Change the position of pro CTA on coupon category page.
		 *
		 * @since 2.2.0
		 */
		public function admin_print_footer_scripts() {
			if ( is_admin()
				&& function_exists( 'get_current_screen' )
				&& 'edit-shop_coupon_cat' === get_current_screen()->id
			) {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function($){
							$( '.wbte_sc_shop_coupon_cat_pro_promo' ).detach().insertBefore( ".wp-heading-inline" );
						});
					</script>
				<?php
			}
		}
	}
	Wt_Smart_Coupon_Category_Common::get_instance();
}