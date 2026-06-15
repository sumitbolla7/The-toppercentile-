<?php
/**
 * The Top Percentile — Refined Email Campaigns (Klaviyo triggers + admin).
 *
 * @package TTP_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Klaviyo campaign definitions, admin UI, and WooCommerce event hooks.
 */
class TTP_Klaviyo_Campaigns {

	const OPTION_KEY     = 'ttp_klaviyo_refined_campaigns';
	const CRON_HOOK      = 'ttp_klaviyo_campaigns_cron';
	const SITE_URL       = 'https://thetoppercentile.co.in/';

	/** @var self|null */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 1001 );
		add_action( 'admin_post_ttp_klaviyo_sync_profiles', [ $this, 'handle_sync_all_profiles' ] );
		add_action( 'init', [ $this, 'maybe_schedule_cron' ] );
		add_action( self::CRON_HOOK, [ $this, 'run_scheduled_triggers' ] );

		add_action( 'user_register', [ $this, 'on_user_register' ], 20, 1 );
		add_action( 'woocommerce_single_product_summary', [ $this, 'on_viewed_product' ], 45 );
		add_action( 'woocommerce_add_to_cart', [ $this, 'on_added_to_cart' ], 20, 6 );
		add_action( 'woocommerce_order_status_failed', [ $this, 'on_payment_failed' ], 20, 1 );
		add_action( 'woocommerce_order_status_processing', [ $this, 'on_purchase' ], 20, 1 );
		add_action( 'woocommerce_order_status_completed', [ $this, 'on_purchase' ], 20, 1 );

		if ( function_exists( 'user_registration_after_register_user_action' ) ) {
			add_action( 'user_registration_after_register_user_action', [ $this, 'on_ur_register' ], 20, 3 );
		}
	}

	/**
	 * Default campaign catalog (all live).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_campaign_definitions() {
		return [
			'welcome_lead'       => [
				'title'   => 'Visitor → Lead Capture (Welcome Mail)',
				'trigger' => 'User submits email on website',
				'subject' => 'Your MBA Prep Starts Now',
				'status'  => 'live',
				'metric'  => 'TTP Welcome Lead',
			],
			'viewed_no_action'   => [
				'title'   => 'Viewed Course → No Action',
				'trigger' => 'User visits course page but does not add to cart',
				'subject' => 'Still Thinking About MBA Prep?',
				'status'  => 'live',
				'metric'  => 'TTP Viewed Course',
			],
			'cart_abandoned'     => [
				'title'   => 'Added to Cart → No Purchase',
				'trigger' => 'Cart abandoned',
				'subject' => "You're One Step Away",
				'status'  => 'live',
				'metric'  => 'TTP Added to Cart',
			],
			'payment_failed'     => [
				'title'   => 'Payment Failed',
				'trigger' => 'Payment unsuccessful',
				'subject' => "Your Payment Didn't Go Through",
				'status'  => 'live',
				'metric'  => 'TTP Payment Failed',
			],
			'purchase_confirm'   => [
				'title'   => 'Purchase Confirmation',
				'trigger' => 'Successful purchase',
				'subject' => 'Welcome to The Top Percentile',
				'status'  => 'live',
				'metric'  => 'TTP Purchase Confirmation',
			],
			'not_started'        => [
				'title'   => 'Not Started Course',
				'trigger' => 'No login/activity after purchase (2–3 days)',
				'subject' => "Don't Delay Your MBA Prep",
				'status'  => 'live',
				'metric'  => 'TTP Course Not Started',
			],
			'low_activity'       => [
				'title'   => 'Low Activity Student',
				'trigger' => 'Inactive for 5–7 days',
				'subject' => "You're Falling Behind",
				'status'  => 'live',
				'metric'  => 'TTP Low Activity',
			],
			'mock_push'          => [
				'title'   => 'Mock/Test Push',
				'trigger' => 'Weekly reminder / no mock attempted',
				'subject' => 'Have You Attempted Your Mock Yet?',
				'status'  => 'live',
				'metric'  => 'TTP Mock Reminder',
			],
			'upsell_mentorship'  => [
				'title'   => 'Upsell (Mentorship Upgrade)',
				'trigger' => 'Existing student',
				'subject' => 'Want to Push Towards 99.9%ile?',
				'status'  => 'live',
				'metric'  => 'TTP Mentorship Upsell',
			],
			'urgency_deadline'   => [
				'title'   => 'Urgency / Deadline Push',
				'trigger' => 'Batch closing / price increase',
				'subject' => 'Last Chance to Join This Batch',
				'status'  => 'live',
				'metric'  => 'TTP Batch Urgency',
			],
		];
	}

	/**
	 * Full email body templates for admin reference / Klaviyo flow copy.
	 *
	 * @param string $key Campaign key.
	 * @return string
	 */
	public static function get_email_body( $key ) {
		$bodies = [
			'welcome_lead'      => "Hi [Aspirant Name],\n\nWelcome to The Top Percentile.\n\nYour journey towards colleges like JBIMS, SIMSREE, Welingkar and more starts now.\n\nWhy students choose TTP:\n• Expert-led preparation for MBA entrance exams\n• Mentorship from top scorers & alumni\n• Strategy-focused preparation with mocks & analysis\n\nExplore more here: " . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'viewed_no_action'  => "Hi [Aspirant Name],\n\nWe noticed you checked out our MBA entrance preparation course.\n\nMost students don't miss out because of lack of potential — they start late or stay inconsistent.\n\nWhy join TTP?\n• Structured preparation & strategy\n• Mock tests with analysis\n• Guidance from top MBA mentors\n\nExplore the course here: " . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'cart_abandoned'    => "Hi [Aspirant Name],\n\nYou added the course to your cart but didn't complete your enrollment.\n\nYour preparation can start today — don't lose momentum.\n\nWhy students enroll now:\n• Limited seats for the current batch\n• Current pricing available only for a short time\n• Access to live classes, mocks & mentorship\n\nComplete your enrollment here: " . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'payment_failed'    => "Hi [Aspirant Name],\n\nLooks like your payment could not be completed successfully.\n\nYou can retry your enrollment here:\n" . self::SITE_URL . "\n\nIf you face any issue, simply reply to this email and our team will assist you.\n\nWarm regards,\nTeam Top Percentile",
			'purchase_confirm'  => "Hi [Aspirant Name],\n\nYou're officially enrolled at The Top Percentile.\n\nYou now have access to expert guidance, mocks, mentorship and strategy sessions designed for MBA entrance exams.\n\nStart your preparation today and stay consistent from Day 1.\n\nVisit your dashboard here:\n" . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'not_started'       => "Hi [Aspirant Name],\n\nYou haven't started your preparation yet.\n\nEven 1 hour of focused study today puts you ahead of hundreds of aspirants.\n\nRemember:\n• Consistency matters more than motivation\n• Small daily progress creates huge results\n• Early starters always have an advantage\n\nResume your preparation here:\n" . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'low_activity'      => "Hi [Aspirant Name],\n\nYour preparation activity has slowed down recently.\n\nMBA entrance exams reward consistency — even small daily progress creates a huge difference over time.\n\nGet back on track:\n• Attend your lectures regularly\n• Attempt mocks consistently\n• Analyze your mistakes and improve\n\nContinue your preparation here:\n" . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'mock_push'         => "Hi [Aspirant Name],\n\nMocks are where real improvement happens.\n\nTop scorers don't just study concepts — they practice under pressure and analyze mistakes.\n\nWhy mocks matter:\n• Improve speed & accuracy\n• Build exam temperament\n• Identify strengths & weak areas\n\nAttempt your next mock here:\n" . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'upsell_mentorship' => "Hi [Aspirant Name],\n\nIf you're serious about colleges like JBIMS, SIMSREE and top B-schools, mentorship can make a massive difference.\n\nUpgrade benefits:\n• 1-on-1 mentorship sessions\n• Personalized strategy & mock analysis\n• Direct guidance from top scorers\n\nUpgrade your preparation here:\n" . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
			'urgency_deadline'  => "Hi [Aspirant Name],\n\nEnrollments for the current CAT & CET batch at The Top Percentile are closing soon.\n\nThe next batch will come with revised pricing and limited availability.\n\nWhy join now?\n• Expert-led sessions tailored for MBA entrance exams\n• Proven strategies used by top scorers\n• Limited seats available at current pricing\n\nEnroll Now: " . self::SITE_URL . "\n\nWarm regards,\nTeam Top Percentile",
		];
		return isset( $bodies[ $key ] ) ? $bodies[ $key ] : '';
	}

	/**
	 * @return string
	 */
	private function get_public_api_key() {
		$settings = get_option( 'klaviyo_settings', [] );
		if ( is_array( $settings ) && ! empty( $settings['klaviyo_public_api_key'] ) ) {
			return trim( (string) $settings['klaviyo_public_api_key'] );
		}
		return trim( (string) get_option( 'ttp_klaviyo_public_api_key', '' ) );
	}

	/**
	 * First name for Klaviyo (from registration / user meta).
	 *
	 * @param int $user_id WordPress user ID.
	 * @return array<string, string>
	 */
	public static function profile_properties_for_user_id( $user_id ) {
		$user_id = (int) $user_id;
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return [];
		}

		$first = trim( (string) get_user_meta( $user_id, 'first_name', true ) );
		$last  = trim( (string) get_user_meta( $user_id, 'last_name', true ) );

		if ( $first === '' && ! empty( $user->display_name ) && false === strpos( $user->display_name, '@' ) ) {
			$parts = preg_split( '/\s+/', trim( $user->display_name ), 2 );
			$first = isset( $parts[0] ) ? $parts[0] : '';
			$last  = isset( $parts[1] ) ? $parts[1] : $last;
		}

		$props = [
			'$email' => $user->user_email,
		];
		if ( $first !== '' ) {
			$props['$first_name'] = $first;
			$props['aspirant_name'] = $first;
		}
		if ( $last !== '' ) {
			$props['$last_name'] = $last;
		}
		if ( $first !== '' || $last !== '' ) {
			$props['$name'] = trim( $first . ' ' . $last );
		}

		return $props;
	}

	/**
	 * Profile fields from WooCommerce order billing.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string, string>
	 */
	public static function profile_properties_for_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return [];
		}
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return [];
		}
		$first = trim( (string) $order->get_billing_first_name() );
		$last  = trim( (string) $order->get_billing_last_name() );
		$props = [ '$email' => $email ];
		if ( $first !== '' ) {
			$props['$first_name']   = $first;
			$props['aspirant_name'] = $first;
		}
		if ( $last !== '' ) {
			$props['$last_name'] = $last;
		}
		if ( $first !== '' || $last !== '' ) {
			$props['$name'] = trim( $first . ' ' . $last );
		}
		$uid = $order->get_user_id();
		if ( $uid > 0 ) {
			$user_props = self::profile_properties_for_user_id( $uid );
			foreach ( $user_props as $k => $v ) {
				if ( ! isset( $props[ $k ] ) || $props[ $k ] === '' ) {
					$props[ $k ] = $v;
				}
			}
		}
		return $props;
	}

	/**
	 * Create/update Klaviyo profile with name (so emails can use {{ first_name }}).
	 *
	 * @param array<string, string> $properties Must include $email.
	 */
	public function identify_profile( array $properties ) {
		if ( empty( $properties['$email'] ) || ! is_email( $properties['$email'] ) ) {
			return;
		}
		$token = $this->get_public_api_key();
		if ( $token === '' ) {
			return;
		}
		wp_remote_post(
			'https://a.klaviyo.com/api/identify',
			[
				'timeout' => 8,
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode(
					[
						'token'      => $token,
						'properties' => $properties,
					]
				),
			]
		);
	}

	/**
	 * Track event via Klaviyo legacy track API (uses public site ID).
	 *
	 * @param string               $metric             Event name (must match Klaviyo flow trigger).
	 * @param string               $email              Customer email.
	 * @param array<string, mixed> $props              Event properties.
	 * @param array<string, string> $customer_properties Optional profile fields ($first_name, etc.).
	 */
	public function track_event( $metric, $email, array $props = [], array $customer_properties = [] ) {
		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return;
		}

		$token = $this->get_public_api_key();
		if ( $token === '' ) {
			return;
		}

		$customer = array_merge( [ '$email' => $email ], $customer_properties );

		$payload = [
			'token'               => $token,
			'event'               => $metric,
			'customer_properties' => $customer,
			'properties'          => array_merge(
				[
					'site_url' => self::SITE_URL,
				],
				$props
			),
		];

		wp_remote_post(
			'https://a.klaviyo.com/api/track',
			[
				'timeout' => 8,
				'headers' => [ 'Content-Type' => 'application/json' ],
				'body'    => wp_json_encode( $payload ),
			]
		);
	}

	/**
	 * @param int $user_id User ID.
	 */
	public function on_user_register( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		$profile = self::profile_properties_for_user_id( $user_id );
		$this->identify_profile( $profile );
		$this->track_event( 'TTP Welcome Lead', $user->user_email, [ 'source' => 'wordpress_register' ], $profile );
	}

	/**
	 * @param int   $user_id User ID.
	 * @param array $data    Form data.
	 * @param int   $form_id Form ID.
	 */
	public function on_ur_register( $user_id, $data, $form_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		if ( is_array( $data ) ) {
			foreach ( [ 'first_name', 'user_first', 'billing_first_name' ] as $key ) {
				if ( ! empty( $data[ $key ] ) ) {
					update_user_meta( $user_id, 'first_name', sanitize_text_field( (string) $data[ $key ] ) );
					break;
				}
			}
			foreach ( [ 'last_name', 'user_last', 'billing_last_name' ] as $key ) {
				if ( ! empty( $data[ $key ] ) ) {
					update_user_meta( $user_id, 'last_name', sanitize_text_field( (string) $data[ $key ] ) );
					break;
				}
			}
		}
		$profile = self::profile_properties_for_user_id( $user_id );
		$this->identify_profile( $profile );
		$this->track_event(
			'TTP Welcome Lead',
			$user->user_email,
			[ 'source' => 'user_registration', 'form_id' => (int) $form_id ],
			$profile
		);
	}

	public function on_viewed_product() {
		if ( ! is_product() ) {
			return;
		}
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$email = $this->current_visitor_email();
		if ( ! $email ) {
			return;
		}
		$profile = is_user_logged_in() ? self::profile_properties_for_user_id( get_current_user_id() ) : [ '$email' => $email ];
		$key = 'ttp_view_' . $product->get_id() . '_' . md5( $email );
		if ( get_transient( $key ) ) {
			return;
		}
		set_transient( $key, 1, DAY_IN_SECONDS );
		$this->track_event(
			'TTP Viewed Course',
			$email,
			[
				'product_id'   => $product->get_id(),
				'product_name' => $product->get_name(),
				'product_url'  => get_permalink( $product->get_id() ),
			],
			$profile
		);
	}

	/**
	 * @param string $cart_item_key Cart item key.
	 * @param int    $product_id    Product ID.
	 * @param int    $quantity      Quantity.
	 * @param int    $variation_id  Variation ID.
	 * @param array  $variation     Variation data.
	 * @param array  $cart_item_data Cart item data.
	 */
	public function on_added_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$email = $this->current_visitor_email();
		if ( ! $email ) {
			return;
		}
		$product = wc_get_product( $product_id );
		$profile = is_user_logged_in() ? self::profile_properties_for_user_id( get_current_user_id() ) : [ '$email' => $email ];
		$this->track_event(
			'TTP Added to Cart',
			$email,
			[
				'product_id'   => (int) $product_id,
				'product_name' => $product ? $product->get_name() : '',
				'quantity'     => (int) $quantity,
			],
			$profile
		);
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public function on_payment_failed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}
		$profile = self::profile_properties_for_order( $order );
		$this->identify_profile( $profile );
		$this->track_event( 'TTP Payment Failed', $email, [ 'order_id' => $order_id ], $profile );
	}

	/**
	 * @param int $order_id Order ID.
	 */
	public function on_purchase( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$email = $order->get_billing_email();
		if ( ! is_email( $email ) ) {
			return;
		}
		$flag = $order->get_meta( '_ttp_klaviyo_purchase_tracked' );
		if ( $flag ) {
			return;
		}
		$profile = self::profile_properties_for_order( $order );
		$this->identify_profile( $profile );
		$order->update_meta_data( '_ttp_klaviyo_purchase_tracked', '1' );
		$order->save();
		$this->track_event(
			'TTP Purchase Confirmation',
			$email,
			[
				'order_id' => $order_id,
				'total'    => $order->get_total(),
			],
			$profile
		);
		$order->update_meta_data( '_ttp_purchase_date', (string) time() );
		$order->save();
	}

	/**
	 * @return string
	 */
	private function current_visitor_email() {
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user && is_email( $user->user_email ) ) {
				return $user->user_email;
			}
		}
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$guest = WC()->customer->get_billing_email();
			if ( is_email( $guest ) ) {
				return $guest;
			}
		}
		return '';
	}

	public function maybe_schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public function run_scheduled_triggers() {
		$this->trigger_not_started_course();
		$this->trigger_low_activity();
		if ( (int) gmdate( 'w' ) === 1 ) {
			$this->trigger_mock_push();
			$this->trigger_upsell();
			$this->trigger_urgency();
		}
	}

	private function trigger_not_started_course() {
		$orders = wc_get_orders(
			[
				'status'       => [ 'processing', 'completed' ],
				'date_created' => '>' . ( time() - 4 * DAY_IN_SECONDS ),
				'limit'        => 50,
			]
		);
		foreach ( $orders as $order ) {
			$purchase_ts = (int) $order->get_meta( '_ttp_purchase_date' );
			if ( $purchase_ts < 1 ) {
				continue;
			}
			if ( ( time() - $purchase_ts ) < 2 * DAY_IN_SECONDS ) {
				continue;
			}
			if ( $order->get_meta( '_ttp_klaviyo_not_started_sent' ) ) {
				continue;
			}
			$email = $order->get_billing_email();
			if ( ! is_email( $email ) ) {
				continue;
			}
			$user = get_user_by( 'email', $email );
			if ( $user && get_user_meta( $user->ID, 'last_login', true ) ) {
				continue;
			}
			$this->track_event( 'TTP Course Not Started', $email, [ 'order_id' => $order->get_id() ] );
			$order->update_meta_data( '_ttp_klaviyo_not_started_sent', '1' );
			$order->save();
		}
	}

	private function trigger_low_activity() {
		$users = get_users(
			[
				'role__in' => [ 'customer', 'subscriber', 'student' ],
				'number'   => 100,
			]
		);
		foreach ( $users as $user ) {
			if ( ! is_email( $user->user_email ) ) {
				continue;
			}
			$last = (int) get_user_meta( $user->ID, 'last_login', true );
			if ( $last > 0 && ( time() - $last ) < 6 * DAY_IN_SECONDS ) {
				continue;
			}
			$sent = (int) get_user_meta( $user->ID, '_ttp_klaviyo_low_activity_sent', true );
			if ( $sent && ( time() - $sent ) < 6 * DAY_IN_SECONDS ) {
				continue;
			}
			$this->track_event( 'TTP Low Activity', $user->user_email, [] );
			update_user_meta( $user->ID, '_ttp_klaviyo_low_activity_sent', (string) time() );
		}
	}

	private function trigger_mock_push() {
		$users = get_users( [ 'role__in' => [ 'customer', 'subscriber', 'student' ], 'number' => 200 ] );
		foreach ( $users as $user ) {
			if ( is_email( $user->user_email ) ) {
				$this->track_event( 'TTP Mock Reminder', $user->user_email, [ 'week' => gmdate( 'Y-W' ) ] );
			}
		}
	}

	private function trigger_upsell() {
		$orders = wc_get_orders( [ 'status' => 'completed', 'limit' => 100 ] );
		foreach ( $orders as $order ) {
			$email = $order->get_billing_email();
			if ( ! is_email( $email ) || $order->get_meta( '_ttp_klaviyo_upsell_sent' ) ) {
				continue;
			}
			$this->track_event( 'TTP Mentorship Upsell', $email, [] );
			$order->update_meta_data( '_ttp_klaviyo_upsell_sent', '1' );
			$order->save();
		}
	}

	private function trigger_urgency() {
		$users = get_users( [ 'role__in' => [ 'customer', 'subscriber', 'student' ], 'number' => 200 ] );
		foreach ( $users as $user ) {
			if ( is_email( $user->user_email ) ) {
				$this->track_event( 'TTP Batch Urgency', $user->user_email, [ 'batch' => 'current' ] );
			}
		}
	}

	/**
	 * Push all WordPress users (with email) into Klaviyo profiles.
	 *
	 * @return int Number synced.
	 */
	public function sync_all_wordpress_users_to_klaviyo() {
		$count = 0;
		$page  = 1;
		do {
			$users = get_users(
				[
					'number' => 100,
					'paged'  => $page,
					'fields' => 'ID',
				]
			);
			foreach ( $users as $user_id ) {
				$profile = self::profile_properties_for_user_id( (int) $user_id );
				if ( empty( $profile['$email'] ) ) {
					continue;
				}
				$this->identify_profile( $profile );
				++$count;
			}
			++$page;
		} while ( count( $users ) === 100 );

		return $count;
	}

	/**
	 * Admin: bulk sync form handler.
	 */
	public function handle_sync_all_profiles() {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'ttp-woocommerce' ) );
		}
		check_admin_referer( 'ttp_klaviyo_sync_profiles' );
		$n = $this->sync_all_wordpress_users_to_klaviyo();
		wp_safe_redirect(
			add_query_arg(
				[
					'page'       => 'ttp-klaviyo-campaigns',
					'ttp_synced' => $n,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function register_admin_menu() {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}
		add_submenu_page(
			'ttp-dashboard',
			__( 'The Top Percentile – Refined Email Campaigns', 'ttp-woocommerce' ),
			__( 'Email Campaigns', 'ttp-woocommerce' ),
			'manage_options',
			'ttp-klaviyo-campaigns',
			[ $this, 'render_admin_page' ]
		);
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_submenu_page(
				'woocommerce-marketing',
				__( 'The Top Percentile – Refined Email Campaigns', 'ttp-woocommerce' ),
				__( 'TTP Email Campaigns', 'ttp-woocommerce' ),
				'manage_woocommerce',
				'ttp-klaviyo-campaigns',
				[ $this, 'render_admin_page' ]
			);
		}
	}

	public function render_admin_page() {
		$campaigns = self::get_campaign_definitions();
		$api_ok    = $this->get_public_api_key() !== '';
		$synced    = isset( $_GET['ttp_synced'] ) ? (int) $_GET['ttp_synced'] : -1;
		$user_count = count_users();
		$total_users = isset( $user_count['total_users'] ) ? (int) $user_count['total_users'] : 0;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'The Top Percentile – Refined Email Campaigns', 'ttp-woocommerce' ); ?></h1>
			<p><?php esc_html_e( 'Short, direct, conversion-focused emails. All campaigns are set to automatic (Live).', 'ttp-woocommerce' ); ?></p>

			<div class="card" style="max-width:720px;padding:16px 20px;margin:16px 0;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Where is my full email list?', 'ttp-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'Klaviyo does not use one small “list” for all WooCommerce customers. Every registrant becomes a Profile.', 'ttp-woocommerce' ); ?></p>
				<ol style="margin-left:1.2em;">
					<li><?php esc_html_e( 'Open Klaviyo → Audience → Profiles (this is your full contact database).', 'ttp-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Use Search or filters — not only Lists & segments (a List is optional).', 'ttp-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'If you only see 1 profile, historical users were never synced — use the button below once.', 'ttp-woocommerce' ); ?></li>
				</ol>
				<p><strong><?php echo esc_html( sprintf( /* translators: %d: user count */ __( 'WordPress accounts on this site: %d', 'ttp-woocommerce' ), $total_users ) ); ?></strong></p>
				<?php if ( $api_ok ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'ttp_klaviyo_sync_profiles' ); ?>
						<input type="hidden" name="action" value="ttp_klaviyo_sync_profiles" />
						<?php submit_button( __( 'Sync all WordPress users to Klaviyo now', 'ttp-woocommerce' ), 'primary', 'submit', false ); ?>
					</form>
					<?php if ( $synced >= 0 ) : ?>
						<p class="notice notice-success" style="padding:8px 12px;display:inline-block;">
							<?php echo esc_html( sprintf( __( 'Sent %d profiles to Klaviyo. Check Audience → Profiles in 1–2 minutes.', 'ttp-woocommerce' ), $synced ) ); ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="card" style="max-width:720px;padding:16px 20px;margin:16px 0;">
				<h2 style="margin-top:0;"><?php esc_html_e( 'Personalize [Aspirant Name] in emails', 'ttp-woocommerce' ); ?></h2>
				<p><?php esc_html_e( 'WordPress now sends first name to Klaviyo as $first_name and aspirant_name when someone registers or orders.', 'ttp-woocommerce' ); ?></p>
				<p><?php esc_html_e( 'In each Klaviyo email, replace [Aspirant Name] with this merge tag:', 'ttp-woocommerce' ); ?></p>
				<p><code>{{ first_name|default:"Aspirant" }}</code></p>
				<p><?php esc_html_e( 'Preview the email in Klaviyo — it will show the real name from registration/checkout.', 'ttp-woocommerce' ); ?></p>
			</div>

			<?php if ( ! $api_ok ) : ?>
				<div class="notice notice-warning"><p><?php esc_html_e( 'Connect Klaviyo under WooCommerce → Marketing → Klaviyo and add your Public API key so events can fire automatically.', 'ttp-woocommerce' ); ?></p></div>
			<?php else : ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Klaviyo is connected. WordPress sends metrics below; create matching Live flows in Klaviyo using the same metric names.', 'ttp-woocommerce' ); ?></p></div>
			<?php endif; ?>
			<table class="widefat striped" style="margin-top:16px;">
				<thead>
					<tr>
						<th>#</th>
						<th><?php esc_html_e( 'Campaign', 'ttp-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Trigger', 'ttp-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Subject', 'ttp-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Klaviyo metric', 'ttp-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Status', 'ttp-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
					foreach ( $campaigns as $key => $c ) :
						?>
						<tr>
							<td><?php echo (int) $i++; ?></td>
							<td><strong><?php echo esc_html( $c['title'] ); ?></strong></td>
							<td><?php echo esc_html( $c['trigger'] ); ?></td>
							<td><?php echo esc_html( $c['subject'] ); ?></td>
							<td><code><?php echo esc_html( $c['metric'] ); ?></code></td>
							<td><span style="color:#15803d;font-weight:700;">● <?php esc_html_e( 'Live (Automatic)', 'ttp-woocommerce' ); ?></span></td>
						</tr>
						<tr>
							<td></td>
							<td colspan="5">
								<details>
									<summary><?php esc_html_e( 'Email copy', 'ttp-woocommerce' ); ?></summary>
									<pre style="white-space:pre-wrap;background:#f9fafb;padding:12px;border:1px solid #e5e7eb;margin-top:8px;"><?php echo esc_html( self::get_email_body( $key ) ); ?></pre>
								</details>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p style="margin-top:20px;">
				<?php
				printf(
					/* translators: %s: Klaviyo settings URL */
					esc_html__( 'In Klaviyo: create Flows triggered by the metrics above (e.g. “TTP Welcome Lead”) and paste the email copy. Set each flow to Live.', 'ttp-woocommerce' )
				);
				?>
			</p>
		</div>
		<?php
	}
}

TTP_Klaviyo_Campaigns::instance();
