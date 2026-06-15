<?php
/**
 * Class for catch Feedback on uninstall
 *
 * @package Wt_Smart_Coupon
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'WT_SmartCoupon_Uninstall_Feedback' ) ) :

	/**
	 * Class for catch Feedback on uninstall
	 */
	class WT_SmartCoupon_Uninstall_Feedback {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'admin_footer', array( $this, 'deactivate_scripts' ) );
			add_action( 'wp_ajax_smartcoupon_submit_uninstall_reason', array( $this, 'send_uninstall_reason' ) );
		}

		/**
		 * Get uninstall reasons
		 *
		 * @return array
		 */
		private function get_uninstall_reasons() {

			return array(
				array(
					'id'          => 'used-it',
					'text'        => __( 'Used it successfully. Don\'t need anymore.', 'thetoppercentile-coupons' ),
					'type'        => 'reviewhtml',
					'placeholder' => __( 'Have used it successfully and aint in need of it anymore', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'could-not-understand',
					'text'        => __( 'I couldn\'t understand how to make it work', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => __( 'Would you like us to assist you?', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'found-better-plugin',
					'text'        => __( 'I found a better plugin', 'thetoppercentile-coupons' ),
					'type'        => 'text',
					'placeholder' => __( 'Which plugin?', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'not-have-that-feature',
					'text'        => __( 'The plugin is great, but I need specific feature that you don\'t support', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => __( 'Could you tell us more about that feature?', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'is-not-working',
					'text'        => __( 'The plugin is not working', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => __( 'Could you tell us a bit more whats not working?', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'looking-for-other',
					'text'        => __( 'It\'s not what I was looking for', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => 'Could you tell us a bit more?',
				),
				array(
					'id'          => 'did-not-work-as-expected',
					'text'        => __( 'The plugin didn\'t work as expected', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => __( 'What did you expect?', 'thetoppercentile-coupons' ),
				),
				array(
					'id'          => 'upgraded-to-pro',
					'text'        => __( 'Upgraded to premium version', 'thetoppercentile-coupons' ),
					'type'        => '', // No need extra details.
					'placeholder' => '',
				),
				array(
					'id'          => 'other',
					'text'        => __( 'Other', 'thetoppercentile-coupons' ),
					'type'        => 'textarea',
					'placeholder' => __( 'Could you tell us a bit more?', 'thetoppercentile-coupons' ),
				),
			);
		}

		/**
		 * Deactivate scripts
		 */
		public function deactivate_scripts() {

			global $pagenow;
			if ( 'plugins.php' !== $pagenow ) {
				return;
			}
			$reasons = $this->get_uninstall_reasons();
			$reasons = ( isset( $reasons ) && is_array( $reasons ) ) ? $reasons : array();
			?>
			<div class="smartcoupon-modal" id="smartcoupon-smartcoupon-modal">
				<div class="smartcoupon-modal-wrap">
					<div class="smartcoupon-modal-header">
						<h3><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating:', 'thetoppercentile-coupons' ); ?></h3>
					</div>
					<div class="smartcoupon-modal-body">
						<ul class="reasons">
							<?php foreach ( $reasons as $reason ) { ?>
								<li data-type="<?php echo esc_attr( $reason['type'] ); ?>" data-placeholder="<?php echo esc_attr( $reason['placeholder'] ); ?>">
									<label><input type="radio" name="selected-reason" value="<?php echo esc_attr( $reason['id'] ); ?>"> <?php echo esc_html( $reason['text'] ); ?></label>
								</li>
							<?php } ?>
						</ul>
						<div class="wt_sc_policy_infobox">
							<?php esc_html_e( "We do not collect any personal data when you submit this form. It's your feedback that we value.", 'thetoppercentile-coupons' ); ?>
							<a href="https://www.thetoppercentile.com/privacy-policy/" target="_blank"><?php esc_html_e( 'Privacy Policy', 'thetoppercentile-coupons' ); ?></a>
						</div>
					</div>
					<div class="smartcoupon-modal-footer">
						<a href="#" class="dont-bother-me"><?php esc_html_e( 'I rather wouldn\'t say', 'thetoppercentile-coupons' ); ?></a>
						<a href="https://wordpress.org/support/plugin/thetoppercentile-coupons/#bbp_topic_title" target="_blank" class="button-primary smartcoupon-model-submit"><span class="dashicons dashicons-external" style="margin-top:3px;"></span> <?php esc_html_e( 'Contact Support', 'thetoppercentile-coupons' ); ?></a>
						<button class="button-primary smartcoupon-model-submit"><?php esc_html_e( 'Submit & Deactivate', 'thetoppercentile-coupons' ); ?></button>
						<button class="button-secondary smartcoupon-model-cancel"><?php esc_html_e( 'Cancel', 'thetoppercentile-coupons' ); ?></button>
					</div>
				</div>
			</div>

			<style type="text/css">
				.smartcoupon-modal {
					position: fixed;
					z-index: 99999;
					top: 0;
					right: 0;
					bottom: 0;
					left: 0;
					background: rgba(0,0,0,0.5);
					display: none;
				}
				.smartcoupon-modal.modal-active {display: block;}
				.smartcoupon-modal-wrap {
					width: 50%;
					position: relative;
					margin: 10% auto;
					background: #fff;
				}
				.smartcoupon-modal-header {
					border-bottom: 1px solid #eee;
					padding: 8px 20px;
				}
				.smartcoupon-modal-header h3 {
					line-height: 150%;
					margin: 0;
				}
				.smartcoupon-modal-body {padding: 5px 20px 20px 20px;}
				.smartcoupon-modal-body .input-text,.smartcoupon-modal-body textarea {width:75%;}
				.smartcoupon-modal-body .reason-input {
					margin-top: 5px;
					margin-left: 20px;
				}
				.smartcoupon-modal-footer {
					border-top: 1px solid #eee;
					padding: 12px 20px;
					text-align: right;
				}
				.reviewlink, .support_link{
						padding:10px 0px 0px 35px !important;
						font-size: 15px;
					}
				.review-and-deactivate, .reach-via-support{
						padding:5px;
					}
				.wt_sc_policy_infobox{font-style:italic; text-align:left; font-size:12px; color:#aaa; line-height:14px; margin-top:35px;}
				.wt_sc_policy_infobox a{ font-size:11px; color:#4b9cc3; text-decoration-color: #99c3d7; }
			</style>
			<script type="text/javascript">
				(function ($) {
					$(function () {
						let modal = $('#smartcoupon-smartcoupon-modal');
						let deactivateLink = '';
						$('#the-list').on('click', 'a.smartcoupon-deactivate-link', function (e) {
							e.preventDefault();
							modal.addClass('modal-active');
							deactivateLink = $(this).attr('href');
							modal.find('input[type="radio"]:checked').prop('checked', false);
							modal.find('a.dont-bother-me').attr('href', deactivateLink).css('float', 'left');
						});
						
						$('#smartcoupon-smartcoupon-modal').on('click', 'a.review-and-deactivate', function (e) {
								e.preventDefault();
								window.open("https://wordpress.org/support/plugin/thetoppercentile-coupons/reviews/");
								window.location.href = deactivateLink;
							});
						
						modal.on('click', 'button.smartcoupon-model-cancel', function (e) {
							e.preventDefault();
							modal.removeClass('modal-active');
						});
						modal.on('click', 'input[type="radio"]', function () {
							let parent = $(this).parents('li:first');
							modal.find('.reason-block').remove();
							let inputType = parent.data('type'), inputPlaceholder = parent.data('placeholder');
									
							let reasonInputHtml = '';
							if ('reviewhtml' === inputType) {
								reasonInputHtml = '<div class="reviewlink reason-block"><a href="#" target="_blank" class="review-and-deactivate"><?php esc_html_e( 'Deactivate and leave a review', 'thetoppercentile-coupons' ); ?> <span class="wt-smartcoupon-rating-link"> &#9733;&#9733;&#9733;&#9733;&#9733; </span></a></div>';
							} else if( 'supportlink' === inputType ) {
								reasonInputHtml = '<div class="support_link reason-block"> <a href="https://wordpress.org/support/plugin/thetoppercentile-coupons/#bbp_topic_title" target="_blank" class="reach-via-support"><?php esc_html_e( 'Let our support team help you', 'thetoppercentile-coupons' ); ?> </a>' + '</div>';
							} else {
								reasonInputHtml = '<div class="reason-input reason-block">' + (('text' === inputType) ? '<input type="text" class="input-text" size="40" />' : '<textarea rows="5" cols="45"></textarea>') + '</div>';
							}

							if (inputType !== '') {
								parent.append($(reasonInputHtml));
								parent.find('input, textarea').attr('placeholder', inputPlaceholder).trigger('focus');
							}
						});

						modal.on('click', 'button.smartcoupon-model-submit', function (e) {
							e.preventDefault();
							let button = $(this);
							if (button.hasClass('disabled')) {
								return;
							}
							let $radio = $('input[type="radio"]:checked', modal);
							let $selected_reason = $radio.parents('li:first'),
									$input = $selected_reason.find('textarea, input[type="text"]');
									$reason_info = (0 !== $input.length) ? $input.val().trim() : '';
									$reason_id = (0 === $radio.length) ? 'none' : $radio.val()
							

							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									action: 'smartcoupon_submit_uninstall_reason',
									reason_id: $reason_id,
									reason_info: $reason_info,
									_wpnonce: '<?php echo esc_js( wp_create_nonce( 'wbte_sc_uninstall_feedback_nonce' ) ); ?>'
								},
								beforeSend: function () {
									button.addClass('disabled');
									button.text('Processing...');
								},
								complete: function () {
									window.location.href = deactivateLink;
								}
							});
						});
					});
				}(jQuery));
			</script>
			<?php
		}

		/**
		 * Send uninstall reason
		 */
		public function send_uninstall_reason() {

			check_ajax_referer( 'wbte_sc_uninstall_feedback_nonce', '_wpnonce' );

			if ( ! isset( $_POST['reason_id'] ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error();
			}

			global $wpdb;

			$data = array(
				'reason_id'           => sanitize_text_field( wp_unslash( $_POST['reason_id'] ) ),
				'plugin'              => 'smartcoupon',
				'auth'                => 'smartcoupon_uninstall_1234#',
				'date'                => gmdate( 'M d, Y h:i:s A' ),
				'url'                 => '',
				'user_email'          => '',
				'reason_info'         => isset( $_REQUEST['reason_info'] ) ? trim( stripslashes( sanitize_text_field( wp_unslash( $_REQUEST['reason_info'] ) ) ) ) : '',
				'software'            => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
				'php_version'         => phpversion(),
				'mysql_version'       => $wpdb->db_version(),
				'wp_version'          => get_bloginfo( 'version' ),
				'wc_version'          => ( ! defined( 'WC_VERSION' ) ) ? '' : WC_VERSION,
				'locale'              => get_locale(),
				'languages'           => implode( ',', get_available_languages() ),
				'theme'               => wp_get_theme()->get( 'Name' ),
				'multisite'           => is_multisite() ? 'Yes' : 'No',
				'smartcoupon_version' => WEBTOFFEE_SMARTCOUPON_VERSION,
			);
			// Write an action/hook here in webtoffe to recieve the data.
			$resp = wp_remote_post(
				'https://feedback.thetoppercentile.com/wp-json/smartcoupon/v1/uninstall',
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => false,
					'body'        => $data,
					'cookies'     => array(),
				)
			);

			wp_send_json_success();
		}
	}
	/* Uninstall survey disabled for The Top Percentile build. */

endif;