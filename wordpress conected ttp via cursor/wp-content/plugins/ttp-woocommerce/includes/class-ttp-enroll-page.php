<?php
/**
 * Enroll / exam landing — premium white grid marketplace UI.
 *
 * Shortcodes:
 *   [ttp_enroll_page]                 — hero + Choose Your Plan + 3 top + 2 bottom cards
 *   [ttp_enroll_page show_hero="0"]   — plans only
 *   [ttp_enroll_row row="top|bottom"]
 *   [ttp_enroll_card slug="cet-elite"]
 *
 * @package TTP_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TTP_Enroll_Page {

	/** @var bool */
	private static $styles_enqueued = false;

	/** Canonical catalog order (original UI). */
	private const TOP_SLUGS = [
		'cet-nmat-snap-elite-with-1-on-1-mentorship',
		'cet-nmat-snap-elite',
		'cet-elite-with-1-on-1-mentorship',
	];

	private const BOTTOM_SLUGS = [
		'cet-elite',
		'cet-solo-self-study',
	];

	public function __construct() {
		add_action( 'init', [ $this, 'register_shortcodes' ], 999 );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue_exam_landing_css' ], 20 );
		add_filter( 'body_class', [ $this, 'enroll_landing_body_class' ] );
		add_action( 'admin_menu', [ $this, 'register_elementor_help_page' ], 99 );
	}

	/**
	 * @return bool
	 */
	public static function is_enroll_landing_request() {
		$slugs = apply_filters( 'ttp_exam_landing_page_slugs', [ 'exam', 'enrol-now', 'enroll-now' ] );
		if ( is_array( $slugs ) && ! empty( $slugs ) && is_page( $slugs ) ) {
			return true;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		return (bool) preg_match( '#/(exam|enrol-now|enroll-now)(/|$|\?)#i', $uri );
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public function enroll_landing_body_class( $classes ) {
		if ( self::is_enroll_landing_request() ) {
			$classes[] = 'ttp-enroll-landing';
			$classes[] = 'ttp-enroll-premium';
		}
		return $classes;
	}

	/**
	 * @return string[]
	 */
	public static function get_shortcode_tags() {
		return [ 'ttp_enroll_page', 'ttp_enroll_row', 'ttp_enroll_card' ];
	}

	public function maybe_enqueue_exam_landing_css() {
		if ( self::is_enroll_landing_request() ) {
			self::enqueue_enroll_styles();
		}
	}

	/**
	 * Register styles once.
	 */
	public static function enqueue_enroll_styles() {
		if ( self::$styles_enqueued ) {
			return;
		}
		self::$styles_enqueued = true;

		$css_path = TTP_DIR . 'assets/css/ttp-enroll-page.css';
		$ver      = TTP_VERSION;
		if ( is_readable( $css_path ) ) {
			$ver .= '.' . filemtime( $css_path );
		}

		wp_enqueue_style(
			'ttp-enroll-fonts',
			'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap',
			[],
			null
		);
		wp_enqueue_style( 'ttp-enroll-page', TTP_URL . 'assets/css/ttp-enroll-page.css', [ 'ttp-enroll-fonts' ], $ver );

	}

	public function register_shortcodes() {
		add_shortcode( 'ttp_enroll_page', [ $this, 'render_shortcode' ] );
		add_shortcode( 'ttp_enroll_row', [ $this, 'render_row_shortcode' ] );
		add_shortcode( 'ttp_enroll_card', [ $this, 'render_card_shortcode' ] );
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		self::enqueue_enroll_styles();

		$atts = shortcode_atts(
			[ 'show_hero' => '1' ],
			is_array( $atts ) ? $atts : [],
			'ttp_enroll_page'
		);

		$GLOBALS['ttp_enroll_show_hero'] = ! in_array( strtolower( (string) $atts['show_hero'] ), [ '0', 'no', 'false' ], true );

		ob_start();
		require TTP_DIR . 'templates/enroll-page.php';
		unset( $GLOBALS['ttp_enroll_show_hero'] );

		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public function render_row_shortcode( $atts ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$atts    = shortcode_atts( [ 'row' => 'top' ], is_array( $atts ) ? $atts : [], 'ttp_enroll_row' );
		$row_key = strtolower( sanitize_key( (string) $atts['row'] ) );

		$slugs     = in_array( $row_key, [ 'bottom', '2', 'b' ], true ) ? self::BOTTOM_SLUGS : self::TOP_SLUGS;
		$row_class = in_array( $row_key, [ 'bottom', '2', 'b' ], true ) ? 'ttp-plans-row--bottom' : 'ttp-plans-row--top';
		$rows      = self::get_catalog_rows_by_slugs( $slugs );

		if ( empty( $rows ) ) {
			return '';
		}

		self::enqueue_enroll_styles();

		ob_start();
		echo '<div class="ttp-enroll-page ttp-enroll-page--marketplace ttp-enroll-page--light ttp-enroll-page--grid ttp-enroll-page--embed">';
		echo '<div class="ttp-plans-section"><div class="ttp-plans-layout">';
		printf( '<div class="ttp-plans-row %s">', esc_attr( $row_class ) );
		foreach ( $rows as $row ) {
			self::render_card_from_row( $row );
		}
		echo '</div></div></div></div>';
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public function render_card_shortcode( $atts ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			[ 'slug' => '', 'popular' => '' ],
			is_array( $atts ) ? $atts : [],
			'ttp_enroll_card'
		);

		$slug = sanitize_title( (string) $atts['slug'] );
		if ( $slug === '' ) {
			return '';
		}

		$rows = self::get_catalog_rows_by_slugs( [ $slug ] );
		if ( empty( $rows ) ) {
			return '';
		}

		self::enqueue_enroll_styles();

		$row = $rows[0];
		if ( $atts['popular'] !== '' ) {
			$row['_force_popular'] = in_array( strtolower( (string) $atts['popular'] ), [ '1', 'yes', 'true' ], true );
		}

		ob_start();
		echo '<div class="ttp-enroll-page ttp-enroll-page--marketplace ttp-enroll-page--light ttp-enroll-page--grid ttp-enroll-page--embed">';
		echo '<div class="ttp-enroll-card-slot">';
		self::render_card_from_row( $row );
		echo '</div></div>';
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $row Row data.
	 */
	public static function render_card_from_row( $row ) {
		if ( empty( $row['product'] ) || ! $row['product'] instanceof WC_Product ) {
			return;
		}

		$product               = $row['product'];
		$badge                 = isset( $row['badge'] ) ? $row['badge'] : 'COURSE';
		$discount_text         = isset( $row['discount_text'] ) ? $row['discount_text'] : '';
		$features_html         = isset( $row['features_html'] ) ? $row['features_html'] : '';
		$legacy_features       = [];
		$ttp_enroll_is_popular = isset( $row['_force_popular'] )
			? (bool) $row['_force_popular']
			: ( 'cet-nmat-snap-elite-with-1-on-1-mentorship' === sanitize_title( $product->get_slug() ) );

		$ttp_enroll_capsules     = function_exists( 'ttp_enroll_capsules_for_product' )
			? ttp_enroll_capsules_for_product( $product )
			: ( function_exists( 'ttp_enroll_capsules_for_slug' ) ? ttp_enroll_capsules_for_slug( $product->get_slug() ) : [] );
		$ttp_enroll_banner_title = function_exists( 'ttp_enroll_banner_title_for_slug' ) ? ttp_enroll_banner_title_for_slug( $product->get_slug() ) : '';

		include TTP_DIR . 'templates/enroll-page-part-card.php';
	}

	/**
	 * All catalog rows from seed or WooCommerce category (internal).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function load_catalog_source_rows() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$cache = [];
		if ( function_exists( 'ttp_enroll_rows_from_seed_slugs' ) ) {
			$rows = ttp_enroll_rows_from_seed_slugs();
			if ( ! empty( $rows ) ) {
				$cache = $rows;
				return $cache;
			}
		}

		$cat = apply_filters( 'ttp_enroll_catalog_category_slug', 'mba-cet-2027' );
		if ( function_exists( 'ttp_enroll_rows_from_catalog_category' ) ) {
			$cache = ttp_enroll_rows_from_catalog_category( $cat );
		}

		return $cache;
	}

	/**
	 * Full exam catalog: 5 priority courses first, then any other published products.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_exam_catalog_rows() {
		$all = function_exists( 'ttp_enroll_get_all_catalog_rows' )
			? ttp_enroll_get_all_catalog_rows()
			: self::load_catalog_source_rows();

		$by_slug = [];
		foreach ( $all as $row ) {
			if ( empty( $row['product'] ) || ! $row['product'] instanceof WC_Product ) {
				continue;
			}
			$key = sanitize_title( $row['product']->get_slug() );
			if ( $key ) {
				$by_slug[ $key ] = $row;
			}
		}

		$ordered = [];
		foreach ( array_merge( self::TOP_SLUGS, self::BOTTOM_SLUGS ) as $slug ) {
			$slug = sanitize_title( $slug );
			if ( $slug && isset( $by_slug[ $slug ] ) ) {
				$ordered[] = $by_slug[ $slug ];
				unset( $by_slug[ $slug ] );
			}
		}
		foreach ( $by_slug as $row ) {
			$ordered[] = $row;
		}

		return apply_filters( 'ttp_enroll_exam_catalog_rows', $ordered, $all );
	}

	/**
	 * @deprecated Use get_exam_catalog_rows().
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_canonical_catalog_rows() {
		return self::get_exam_catalog_rows();
	}

	/**
	 * @param string[] $slugs Product slugs in order.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_catalog_rows_by_slugs( array $slugs ) {
		$all = self::load_catalog_source_rows();
		$by_slug = [];
		foreach ( $all as $row ) {
			if ( empty( $row['product'] ) || ! $row['product'] instanceof WC_Product ) {
				continue;
			}
			$key = sanitize_title( $row['product']->get_slug() );
			if ( $key ) {
				$by_slug[ $key ] = $row;
			}
		}
		$out = [];
		foreach ( $slugs as $slug ) {
			$slug = sanitize_title( $slug );
			if ( $slug && isset( $by_slug[ $slug ] ) ) {
				$out[] = $by_slug[ $slug ];
			}
		}
		return $out;
	}

	public function register_elementor_help_page() {
		add_submenu_page(
			'ttp-dashboard',
			__( 'Exam Page (Elementor)', 'ttp-woocommerce' ),
			__( 'Exam Page (Elementor)', 'ttp-woocommerce' ),
			'manage_options',
			'ttp-exam-elementor',
			[ $this, 'render_elementor_help_page' ]
		);
	}

	public function render_elementor_help_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Exam / Enrol Page', 'ttp-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'The /exam/ page should use ONE shortcode only:', 'ttp-woocommerce' ); ?></p>
			<pre style="background:#1e1e1e;color:#fff;padding:16px;border-radius:8px;">[ttp_enroll_page]</pre>
			<p><?php esc_html_e( 'New products appear on Enrol Now automatically. Upload “Enrol Now card image” on each product (Product data → General) — same image is used on Details. Hide from Enrol with _ttp_hide_from_enroll_page = 1.', 'ttp-woocommerce' ); ?></p>
			<p><?php printf( esc_html__( 'Plugin version: %s', 'ttp-woocommerce' ), esc_html( TTP_VERSION ) ); ?></p>
		</div>
		<?php
	}
}
