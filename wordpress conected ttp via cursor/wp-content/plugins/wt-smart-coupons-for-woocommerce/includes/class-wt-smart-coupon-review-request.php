<?php
/**
 * Review request
 *
 * @package  Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * To display review request banner in admin.
 */
class Wt_Smart_Coupon_Review_Request {

	/**
	 * Plugin review URL
	 *
	 * @var string $review_url URL to the review page
	 */
	private $review_url = 'https://wordpress.org/support/plugin/thetoppercentile-coupons/reviews/#new-post';

	/**
	 * Plugin prefix
	 *
	 * @var string $plugin_prefix Plugin prefix
	 */
	private $plugin_prefix = 'wt_smart_coupon';

	/**
	 * Array of cooldown days for each stage
	 *
	 * @var array $stage_cooldowns Array of cooldown days for each stage
	 */
	private $stage_cooldowns = array( 0, 7, 14 );

	/**
	 * Minimum number of coupons required to trigger review prompt
	 *
	 * @var int $min_coupons Minimum number of coupons required to trigger review prompt
	 */
	private $min_coupons = 10;

	/**
	 * Minimum number of days required to trigger review prompt
	 *
	 * @var int $min_days Minimum number of days required to trigger review prompt
	 */
	private $min_days = 7;

	/**
	 * Banner text to choose
	 *
	 * @var string $banner_to_choose Banner to choose
	 */
	private $banner_to_choose = 'coupon_count';

	/**
	 * Array of day increments for each stage
	 *
	 * @var array $stage_day_increments Array of day increments for each stage
	 */
	private $stage_day_increments = array( 0, 30, 90 );

	/**
	 * Banner state option name
	 *
	 * @var string $banner_state_option_name Banner state option name
	 */
	private $banner_state_option_name = '';

	/**
	 * Start date option name
	 *
	 * @var string $start_date_option_name Start date option name
	 */
	private $start_date_option_name = '';

	/**
	 * Base coupon count option name
	 *
	 * @var string $base_coupon_count_option_name Base coupon count option name
	 */
	private $base_coupon_count_option_name = '';

	/**
	 * Base days count option name
	 *
	 * @var string $base_days_count_option_name Base days count option name
	 */
	private $base_days_count_option_name = '';

	/**
	 * Current stage option name
	 *
	 * @var string $current_stage_option_name Current stage option name
	 */
	private $current_stage_option_name = '';

	/**
	 * Last remind date option name
	 *
	 * @var string $last_remind_date_option_name Last remind date option name
	 */
	private $last_remind_date_option_name = '';

	/**
	 * Current banner state
	 *
	 * @var int $current_banner_state Current banner state
	 */
	private $current_banner_state = 2;

	/**
	 * Current stage
	 *
	 * @var int $current_stage Current stage
	 */
	private $current_stage = 0;

	/**
	 * Start date
	 *
	 * @var int $start_date Start date
	 */
	private $start_date = 0;

	/**
	 * Base coupon count
	 *
	 * @var int $base_coupon_count Base coupon count
	 */
	private $base_coupon_count = 0;

	/**
	 * Base days count
	 *
	 * @var int $base_days_count Base days count
	 */
	private $base_days_count = 0;

	/**
	 * Last remind date
	 *
	 * @var int $last_remind_date Last remind date
	 */
	private $last_remind_date = 0;

	/**
	 * Banner CSS class
	 *
	 * @var string $banner_css_class Banner CSS class
	 */
	private $banner_css_class = '';

	/**
	 * Banner message
	 *
	 * @var string $banner_message Banner message
	 */
	private $banner_message = '';

	/**
	 * Ajax action name
	 *
	 * @var string $ajax_action_name Ajax action name
	 */
	private $ajax_action_name = '';

	/**
	 * Allowed action type array
	 *
	 * @var array $allowed_action_type_arr Allowed action type array
	 */
	private $allowed_action_type_arr = array(
		'later',
		'review',
		'closed',
		'already',
	);

	/**
	 * Created count
	 *
	 * @var int $created_count Created count
	 */
	private $created_count = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_vars();

		register_activation_hook( WT_SMARTCOUPON_FILE_NAME, array( $this, 'on_activate' ) );
		register_deactivation_hook( WT_SMARTCOUPON_FILE_NAME, array( $this, 'on_deactivate' ) );

		add_action( 'admin_init', array( $this, 'init' ) );
	}

	/**
	 * Set configuration variables and retrieve stored options
	 */
	public function set_vars() {
		$this->ajax_action_name              = $this->plugin_prefix . '_process_user_review_action';
		$this->banner_state_option_name      = $this->plugin_prefix . '_review_request';
		$this->start_date_option_name        = $this->plugin_prefix . '_start_date';
		$this->base_coupon_count_option_name = $this->plugin_prefix . '_base_coupon_count';
		$this->base_days_count_option_name   = $this->plugin_prefix . '_base_days_count';
		$this->current_stage_option_name     = $this->plugin_prefix . '_current_stage';
		$this->last_remind_date_option_name  = $this->plugin_prefix . '_last_remind_date';
		$this->banner_css_class              = $this->plugin_prefix . '_review_request';

		$this->start_date           = absint( get_option( $this->start_date_option_name ) );
		$this->current_banner_state = absint( get_option( $this->banner_state_option_name, 2 ) );
		$this->current_stage        = absint( get_option( $this->current_stage_option_name, 0 ) );
		$this->base_coupon_count    = absint( get_option( $this->base_coupon_count_option_name, 0 ) );
		$this->base_days_count      = absint( get_option( $this->base_days_count_option_name, 0 ) );
		$this->last_remind_date     = absint( get_option( $this->last_remind_date_option_name, 0 ) );

		$this->created_count = (int) get_option( 'wt_sc_coupons_created', 0 );
	}

	/**
	 * Initialize on admin
	 */
	public function init() {
		if ( $this->check_condition() ) {
			$this->set_banner_message();
			add_action( 'admin_notices', array( $this, 'show_banner' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'add_banner_scripts' ) );
			add_action( 'wp_ajax_' . $this->ajax_action_name, array( $this, 'process_user_action' ) );
		}
	}

	/**
	 * Set banner message based on current stage
	 */
	private function set_banner_message() {
		if ( 'coupon_count' === $this->banner_to_choose ) {
			$this->banner_message = sprintf(
				// translators: 1: number of coupons created, 2: opening bold tag, 3: closing bold tag.
				esc_html__( 'We’re excited to see you’ve created %1$s coupons using the %2$s TheTopPercentile Coupons %3$s plugin! We hope boosting your store’s success and making discounts easier to manage.', 'thetoppercentile-coupons' ),
				absint( $this->created_count ),
				'<b>',
				'</b>'
			);
		} else {
			$days_since_start = floor( ( time() - $this->start_date ) / DAY_IN_SECONDS );
			$duration_text    = esc_html__( 'a week', 'thetoppercentile-coupons' );
			if ( 29 < $days_since_start ) {
				$months        = floor( $days_since_start / 30 );
				$duration_text = 1 < $months ? $months . ' ' . esc_html__( 'months', 'thetoppercentile-coupons' ) : sprintf( esc_html__( 'a month', 'thetoppercentile-coupons' ) );
			}

			$this->banner_message = sprintf(
				// translators: 1: opening bold tag, 2: closing bold tag, 3: duration text.
				esc_html__( 'Wow! You have been using %1$s TheTopPercentile Coupons %2$s plugin for over %3$s now, and we hope it’s been helping you save time and simplify your workflow!', 'thetoppercentile-coupons' ),
				'<b>',
				'</b>',
				esc_html( $duration_text )
			);
		}
	}

	/**
	 * Check conditions to determine if review banner should be shown
	 *
	 * @return bool True if conditions are met to show banner, false otherwise
	 */
	private function check_condition() {
		if ( in_array( $this->current_banner_state, array( 4, 6 ), true ) ) {
			return false; // User reviewed or not interested.
		}

		$days_since_start = floor( ( time() - $this->start_date ) / DAY_IN_SECONDS );

		if ( 0 === $this->current_stage ) {
			return ( $this->created_count > $this->min_coupons && $days_since_start > $this->min_days );
		}

		if ( $this->last_remind_date > 0 ) {
			$cooldown_complete = ( time() - $this->last_remind_date ) > ( $this->stage_cooldowns[ $this->current_stage ] * DAY_IN_SECONDS );
			if ( ! $cooldown_complete ) {
				return false;
			}
		}

		$next_coupon_target = $this->calculate_next_coupon_target();
		$next_days_target   = $this->calculate_next_days_target();

		if ( $this->created_count >= $next_coupon_target ) {
			return true;
		}

		if ( $days_since_start >= $next_days_target ) {
			$this->banner_to_choose = 'days_count';
			return true;
		}
		return false;
	}

	/**
	 * Calculate the next target number of coupons needed for review prompt
	 *
	 * @return int Next coupon target count
	 */
	private function calculate_next_coupon_target() {
		if ( 0 === $this->current_stage || 0 === $this->base_coupon_count ) {
			return $this->min_coupons;
		}

		$increase    = $this->base_coupon_count * 0.5;
		$next_target = $this->base_coupon_count + $increase;
		return ceil( $next_target / 10 ) * 10; // Round to next 10.
	}

	/**
	 * Calculate the next target number of days needed for review prompt
	 *
	 * @return int Next day target count
	 */
	private function calculate_next_days_target() {
		if ( 0 === $this->current_stage || 0 === $this->base_days_count ) {
			return $this->min_days;
		}

		return $this->base_days_count + $this->stage_day_increments[ $this->current_stage ];
	}

	/**
	 * Display the review request banner in admin
	 */
	public function show_banner() {
		$this->update_banner_state( 1 ); // Set to active.
		?>
		<div class="<?php echo esc_attr( $this->banner_css_class ); ?> notice-info notice is-dismissible">
			<p class="wbte-sc-review-title">
				<?php
				// translators: 1: opening span tag, 2: closing span tag.
				printf( esc_html__( '🌟 Loving %1$s TheTopPercentile Coupon for WooCommerce plugin? %2$s Share Your Feedback!', 'thetoppercentile-coupons' ), '<span>', '</span>' );
				?>
			</p>
			<?php
			$current_user = wp_get_current_user();
			$first_name   = ! empty( $current_user->first_name ) ? $current_user->first_name : __( 'there', 'thetoppercentile-coupons' );
			?>
			<p>
				<?php
				// translators: 1: first name.
				printf( esc_html__( 'Hey %s!', 'thetoppercentile-coupons' ), esc_html( $first_name ) );
				?>
			</p>
			<p><?php echo wp_kses_post( $this->banner_message ); ?></p>
			<p><?php esc_html_e( 'If you found the plugin helpful, please leave us a quick 5-star review. It would mean the world to us.', 'thetoppercentile-coupons' ); ?></p>
			<p>
			<?php
			// translators: 1: break tag.
			printf( esc_html__( 'Warm regards, %s Team TheTopPercentile', 'thetoppercentile-coupons' ), '<br>' );
			?>
			</p>
			<p>
				<a class="button button-primary" data-type="review"><?php esc_html_e( 'You deserve it', 'thetoppercentile-coupons' ); ?></a>
				<a class="button button-secondary" data-type="later"><?php esc_html_e( 'Nope, maybe later', 'thetoppercentile-coupons' ); ?></a>
				<a class="button button-secondary" data-type="already"><?php esc_html_e( 'I already did', 'thetoppercentile-coupons' ); ?></a>
			</p>
			<div class="wt-smart-coupon-review-footer" style="position:absolute;right:0px; bottom:0px;">
				<span class="wt-smart-coupon-footer-icon">
					<img src="<?php echo esc_url( WT_SMARTCOUPON_MAIN_URL . 'admin/images/review_banner_bg.svg' ); ?>" style="max-height:85px; margin-bottom:0px; float:right;">
				</span>
			</div>
		</div>
		<?php
	}

	/**
	 * Add banner scripts
	 */
	public function add_banner_scripts() {
		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( $this->plugin_prefix );
		?>
		<style type="text/css">
			.wt_smart_coupon_review_request{ padding: 20px; border: none; }
			.wt_smart_coupon_review_request p{ font-size: 14px; }
			.wt_smart_coupon_review_request .button{ height: 37px; line-height: 35px; text-align: center; padding: 0 15px; font-weight: 500; }
			.wt_smart_coupon_review_request .button-primary{ background: #2860F4; border: 1px solid #2860F4; color:#fff; }
			.wt_smart_coupon_review_request .button-secondary{ background: #FEFEFE; border: 1px solid #C3C4C7; color: #2A3646; }
			p.wbte-sc-review-title{ font-size: 20px; font-weight: 400; margin-top: 0px; }
			.wbte-sc-review-title span{ font-weight: 600; }
		</style>
		<script type="text/javascript">
			(function($) {
				"use strict";

				var data_obj = {
					_wpnonce: '<?php echo esc_js( $nonce ); ?>',
					action: '<?php echo esc_js( $this->ajax_action_name ); ?>',
					wt_review_action_type: ''
				};

				$(document).on('click', '.<?php echo esc_js( $this->banner_css_class ); ?> a.button', function(e) {
					e.preventDefault();
					var elm = $(this);
					var btn_type = elm.attr('data-type');
					if ('review' === btn_type) {
						window.open('<?php echo esc_url( $this->review_url ); ?>');
					}
					elm.parents('.<?php echo esc_js( $this->banner_css_class ); ?>').hide();

					data_obj['wt_review_action_type'] = btn_type;
					$.ajax({
						url: '<?php echo esc_url( $ajax_url ); ?>',
						data: data_obj,
						type: 'POST'
					});

				}).on('click', '.<?php echo esc_js( $this->banner_css_class ); ?> .notice-dismiss', function(e) {
					e.preventDefault();
					data_obj['wt_review_action_type'] = 'closed';
					$.ajax({
						url: '<?php echo esc_url( $ajax_url ); ?>',
						data: data_obj,
						type: 'POST'
					});
				});
			})(jQuery)
		</script>
		<?php
	}

	/**
	 * Process user action on banner
	 */
	public function process_user_action() {
		check_ajax_referer( $this->plugin_prefix );

		if ( isset( $_POST['wt_review_action_type'] ) ) {
			$action_type = sanitize_text_field( wp_unslash( $_POST['wt_review_action_type'] ) );

			if ( in_array( $action_type, $this->allowed_action_type_arr, true ) ) {
				if ( 'later' === $action_type ) {
					$days_since_start = floor( ( time() - $this->start_date ) / DAY_IN_SECONDS );
					update_option( $this->base_coupon_count_option_name, $this->created_count );
					update_option( $this->base_days_count_option_name, $days_since_start );
					update_option( $this->last_remind_date_option_name, time() );
					update_option( $this->current_stage_option_name, $this->current_stage + 1 );
					$this->update_banner_state( 5 );
				} elseif ( 'review' === $action_type ) {
					$this->update_banner_state( 4 );
				} elseif ( 'closed' === $action_type ) {
					$this->update_banner_state( 6 );
				} elseif ( 'already' === $action_type ) {
					$this->update_banner_state( 4 );
				}
			}
		}
		exit();
	}

	/**
	 * Update the banner state in database
	 *
	 * @param int $val New banner state value.
	 */
	private function update_banner_state( $val ) {
		update_option( $this->banner_state_option_name, $val );
	}

	/**
	 * Actions to perform on plugin activation
	 */
	public function on_activate() {
		if ( 0 === $this->start_date ) {
			update_option( $this->start_date_option_name, time() );
		}
	}

	/**
	 * Actions to perform on plugin deactivation
	 */
	public function on_deactivate() {
		delete_option( $this->start_date_option_name );
	}
}

new Wt_Smart_Coupon_Review_Request();
