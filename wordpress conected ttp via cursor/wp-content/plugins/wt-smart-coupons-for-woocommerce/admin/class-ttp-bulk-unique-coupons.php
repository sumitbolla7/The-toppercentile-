<?php
/**
 * Bulk unique one-time-use WooCommerce coupons (TheTopPercentile Coupons).
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TTP_SC_Bulk_Unique_Coupons' ) ) {

	/**
	 * Admin tool: generate N unique coupons, each usable once.
	 */
	class TTP_SC_Bulk_Unique_Coupons {

		const PAGE_SLUG = 'ttp-sc-bulk-unique-coupons';
		const NONCE     = 'ttp_sc_bulk_unique_coupons';

		/**
		 * Boot hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_filter( 'wt_sc_admin_menu', array( __CLASS__, 'register_menu' ) );
			add_action( 'admin_post_ttp_sc_bulk_generate_coupons', array( __CLASS__, 'handle_generate' ) );
			add_action( 'admin_post_ttp_sc_bulk_download_csv', array( __CLASS__, 'handle_download_csv' ) );
		}

		/**
		 * @param array $menus Existing menus.
		 * @return array
		 */
		public static function register_menu( $menus ) {
			if ( ! defined( 'WT_SC_PLUGIN_NAME' ) ) {
				return $menus;
			}

			$menus[] = array(
				'submenu',
				WT_SC_PLUGIN_NAME,
				__( 'Bulk unique coupons', 'thetoppercentile-coupons' ),
				__( 'Bulk unique coupons', 'thetoppercentile-coupons' ),
				'manage_woocommerce',
				self::PAGE_SLUG,
				array( __CLASS__, 'render_page' ),
			);

			return $menus;
		}

		/**
		 * Admin page UI.
		 *
		 * @return void
		 */
		public static function render_page() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'thetoppercentile-coupons' ) );
			}

			$last_batch = isset( $_GET['batch'] ) ? sanitize_text_field( wp_unslash( $_GET['batch'] ) ) : '';
			$created    = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0;
			$codes      = $last_batch ? get_transient( 'ttp_sc_bulk_codes_' . $last_batch ) : false;

			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Bulk unique coupons', 'thetoppercentile-coupons' ); ?></h1>
				<p><?php esc_html_e( 'Create many unique coupon codes at once. Each code can be used only one time — ideal for giving one code per person.', 'thetoppercentile-coupons' ); ?></p>

				<?php if ( $created > 0 && is_array( $codes ) ) : ?>
					<div class="notice notice-success is-dismissible">
						<p>
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of coupons */
									__( 'Successfully created %d unique coupon(s).', 'thetoppercentile-coupons' ),
									$created
								)
							);
							?>
						</p>
					</div>
				<?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:640px;background:#fff;border:1px solid #ccd0d4;padding:20px;margin:16px 0;">
					<?php wp_nonce_field( self::NONCE, '_ttp_sc_bulk_nonce' ); ?>
					<input type="hidden" name="action" value="ttp_sc_bulk_generate_coupons" />

					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label for="ttp_sc_count"><?php esc_html_e( 'How many coupons?', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<input type="number" name="ttp_sc_count" id="ttp_sc_count" value="100" min="1" max="500" class="small-text" required />
								<p class="description"><?php esc_html_e( 'Maximum 500 per batch.', 'thetoppercentile-coupons' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ttp_sc_discount_type"><?php esc_html_e( 'Discount type', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<select name="ttp_sc_discount_type" id="ttp_sc_discount_type">
									<option value="percent"><?php esc_html_e( 'Percentage discount', 'thetoppercentile-coupons' ); ?></option>
									<option value="fixed_cart"><?php esc_html_e( 'Fixed cart discount (amount)', 'thetoppercentile-coupons' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ttp_sc_amount"><?php esc_html_e( 'Discount amount', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<input type="number" name="ttp_sc_amount" id="ttp_sc_amount" value="10" min="0.01" step="0.01" class="regular-text" required />
								<p class="description"><?php esc_html_e( 'For percentage: enter 10 for 10%. For fixed: enter amount in store currency (e.g. 500).', 'thetoppercentile-coupons' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ttp_sc_prefix"><?php esc_html_e( 'Code prefix (optional)', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<input type="text" name="ttp_sc_prefix" id="ttp_sc_prefix" value="TTP-" maxlength="20" class="regular-text" />
								<p class="description"><?php esc_html_e( 'Example: TTP- → TTP-X7K2M9AB', 'thetoppercentile-coupons' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ttp_sc_code_length"><?php esc_html_e( 'Random characters', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<input type="number" name="ttp_sc_code_length" id="ttp_sc_code_length" value="8" min="4" max="16" class="small-text" />
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="ttp_sc_expiry"><?php esc_html_e( 'Expiry date (optional)', 'thetoppercentile-coupons' ); ?></label></th>
							<td>
								<input type="date" name="ttp_sc_expiry" id="ttp_sc_expiry" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Usage limits', 'thetoppercentile-coupons' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ttp_sc_individual_use" value="1" />
									<?php esc_html_e( 'Individual use only (cannot combine with other coupons)', 'thetoppercentile-coupons' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Each generated coupon is always limited to 1 use total and 1 use per customer.', 'thetoppercentile-coupons' ); ?></p>
							</td>
						</tr>
					</table>

					<?php submit_button( __( 'Generate unique coupons', 'thetoppercentile-coupons' ), 'primary', 'submit', false ); ?>
				</form>

				<?php if ( is_array( $codes ) && ! empty( $codes ) ) : ?>
					<h2><?php esc_html_e( 'Generated codes', 'thetoppercentile-coupons' ); ?></h2>
					<p>
						<a class="button" href="<?php echo esc_url( self::download_url( $last_batch ) ); ?>"><?php esc_html_e( 'Download CSV', 'thetoppercentile-coupons' ); ?></a>
						<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=shop_coupon' ) ); ?>"><?php esc_html_e( 'View all coupons', 'thetoppercentile-coupons' ); ?></a>
					</p>
					<table class="widefat striped" style="max-width:640px;">
						<thead>
							<tr>
								<th><?php esc_html_e( '#', 'thetoppercentile-coupons' ); ?></th>
								<th><?php esc_html_e( 'Coupon code', 'thetoppercentile-coupons' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( array_slice( $codes, 0, 50 ) as $i => $code ) : ?>
								<tr>
									<td><?php echo esc_html( (string) ( $i + 1 ) ); ?></td>
									<td><code><?php echo esc_html( $code ); ?></code></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( count( $codes ) > 50 ) : ?>
						<p class="description"><?php esc_html_e( 'Showing first 50 codes. Download CSV for the full list.', 'thetoppercentile-coupons' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Process bulk generation form.
		 *
		 * @return void
		 */
		public static function handle_generate() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'thetoppercentile-coupons' ) );
			}

			check_admin_referer( self::NONCE, '_ttp_sc_bulk_nonce' );

			if ( ! class_exists( 'WC_Coupon' ) ) {
				wp_die( esc_html__( 'WooCommerce is required.', 'thetoppercentile-coupons' ) );
			}

			$count         = min( 500, max( 1, absint( $_POST['ttp_sc_count'] ?? 100 ) ) );
			$discount_type = sanitize_key( wp_unslash( $_POST['ttp_sc_discount_type'] ?? 'percent' ) );
			$amount        = (float) wc_format_decimal( wp_unslash( $_POST['ttp_sc_amount'] ?? 0 ) );
			$prefix        = preg_replace( '/[^a-zA-Z0-9\-_]/', '', (string) wp_unslash( $_POST['ttp_sc_prefix'] ?? '' ) );
			$code_length   = min( 16, max( 4, absint( $_POST['ttp_sc_code_length'] ?? 8 ) ) );
			$expiry        = isset( $_POST['ttp_sc_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['ttp_sc_expiry'] ) ) : '';
			$individual    = ! empty( $_POST['ttp_sc_individual_use'] );

			if ( ! in_array( $discount_type, array( 'percent', 'fixed_cart' ), true ) ) {
				$discount_type = 'percent';
			}

			if ( $amount <= 0 ) {
				wp_die( esc_html__( 'Discount amount must be greater than zero.', 'thetoppercentile-coupons' ) );
			}

			if ( 'percent' === $discount_type && $amount > 100 ) {
				$amount = 100;
			}

			$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . wp_generate_password( 6, false, false );
			$created  = 0;
			$codes    = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$code = self::generate_unique_code( $prefix, $code_length );
				if ( '' === $code ) {
					continue;
				}

				$coupon = new WC_Coupon();
				$coupon->set_code( $code );
				$coupon->set_description(
					sprintf(
						/* translators: %s: batch id */
						__( 'Bulk unique coupon (%s)', 'thetoppercentile-coupons' ),
						$batch_id
					)
				);
				$coupon->set_discount_type( $discount_type );
				$coupon->set_amount( $amount );
				$coupon->set_usage_limit( 1 );
				$coupon->set_usage_limit_per_user( 1 );
				$coupon->set_individual_use( $individual );

				if ( '' !== $expiry ) {
					$coupon->set_date_expires( strtotime( $expiry . ' 23:59:59' ) );
				}

				$coupon_id = $coupon->save();

				if ( $coupon_id > 0 ) {
					update_post_meta( $coupon_id, '_ttp_sc_bulk_batch_id', $batch_id );
					$codes[] = $code;
					++$created;
				}
			}

			set_transient( 'ttp_sc_bulk_codes_' . $batch_id, $codes, DAY_IN_SECONDS );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => self::PAGE_SLUG,
						'batch'   => $batch_id,
						'created' => $created,
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		/**
		 * CSV download for a batch.
		 *
		 * @return void
		 */
		public static function handle_download_csv() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Permission denied.', 'thetoppercentile-coupons' ) );
			}

			$batch = isset( $_GET['batch'] ) ? sanitize_text_field( wp_unslash( $_GET['batch'] ) ) : '';
			check_admin_referer( 'ttp_sc_download_' . $batch );

			$codes = get_transient( 'ttp_sc_bulk_codes_' . $batch );
			if ( ! is_array( $codes ) ) {
				wp_die( esc_html__( 'Batch not found or expired. Generate coupons again.', 'thetoppercentile-coupons' ) );
			}

			header( 'Content-Type: text/csv; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="ttp-coupons-' . $batch . '.csv"' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$out = fopen( 'php://output', 'w' );
			fputcsv( $out, array( 'coupon_code' ) );
			foreach ( $codes as $code ) {
				fputcsv( $out, array( $code ) );
			}
			fclose( $out );
			exit;
		}

		/**
		 * @param string $batch Batch id.
		 * @return string
		 */
		private static function download_url( $batch ) {
			return wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'ttp_sc_bulk_download_csv',
						'batch'  => $batch,
					),
					admin_url( 'admin-post.php' )
				),
				'ttp_sc_download_' . $batch
			);
		}

		/**
		 * Generate a unique WooCommerce coupon code.
		 *
		 * @param string $prefix     Prefix.
		 * @param int    $length     Random length.
		 * @return string
		 */
		private static function generate_unique_code( $prefix, $length ) {
			$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
			$max   = strlen( $chars ) - 1;

			for ( $attempt = 0; $attempt < 30; $attempt++ ) {
				$random = '';
				for ( $i = 0; $i < $length; $i++ ) {
					$random .= $chars[ wp_rand( 0, $max ) ];
				}
				$code = strtoupper( $prefix . $random );

				if ( function_exists( 'wc_get_coupon_id_by_code' ) && wc_get_coupon_id_by_code( $code ) ) {
					continue;
				}

				return $code;
			}

			return '';
		}
	}

	TTP_SC_Bulk_Unique_Coupons::init();
}
