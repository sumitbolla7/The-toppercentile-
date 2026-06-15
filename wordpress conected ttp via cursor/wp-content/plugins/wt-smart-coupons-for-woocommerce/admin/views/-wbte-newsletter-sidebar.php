<?php
/**
 * Webtoffee Newsletter sidebar
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$wbte_newsletter_banner_hidden = get_option( 'wt_newsletter_banner_hidden', false );

if ( ! $wbte_newsletter_banner_hidden ) {
	?>
	<div class="wt_sc_newsletter_subscription_box">
		<div class="wt_sc_newsletter_header">
			<svg width="38" height="38" viewBox="0 0 38 38" fill="none" xmlns="http://www.w3.org/2000/svg">
				<mask id="mask0_11201_2391" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="38" height="38">
					<path d="M38 0H0V38H38V0Z" fill="white"/>
				</mask>
				<g mask="url(#mask0_11201_2391)">
					<path d="M36.4832 16.3331L21.6513 1.50126C21.1882 1.03814 20.5113 1.10939 20.0482 1.57251L1.54695 19.8125C1.08382 20.2756 1.06007 21.2137 1.5232 21.6769L16.3076 36.4612C16.7707 36.9244 17.5307 36.9362 17.9938 36.4731L36.4713 17.9956C36.9345 17.5325 36.9463 16.7962 36.4832 16.3331Z" fill="#FFC44D"/>
					<path d="M28.501 32.0684H36.8135M34.4385 27.3184H36.8135M7.12598 21.381H20.1885C20.819 21.381 21.376 20.862 21.376 20.1935V8.31843M16.626 26.1309V29.6934M29.6885 16.6309H26.126M36.4806 16.3329C36.9449 16.7972 36.9307 17.5346 36.4664 17.9978L17.9936 36.4717C17.5293 36.936 16.7729 36.9313 16.3097 36.4669L1.52181 21.679C1.0575 21.2147 1.086 20.2766 1.55031 19.8134L20.0456 1.57699C20.5099 1.11386 21.1821 1.0343 21.6464 1.49861L36.4806 16.3329Z" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</g>
			</svg>

			<p class="wt_sc_newsletter_title_text"><?php esc_html_e( 'Subscribe to our newsletter for exclusive offers & updates', 'thetoppercentile-coupons' ); ?></p>
		</div>
			
		<div id="mc_embed_shell">
			<div id="mc_embed_signup">
				<form action="https://list-manage.us5.list-manage.com/subscribe/post?u=10e843cdec17dd1d2e769ead6&amp;id=d9d25110b9&amp;f_id=0020b8edf0" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank">
					<div class="mc-field-group wbte-sc-newsletter-email">
						<input type="email" name="EMAIL" class="required email" id="mce-EMAIL" required="" value="" placeholder="<?php esc_attr_e( 'Enter your email address', 'thetoppercentile-coupons' ); ?>" aria-label="<?php esc_attr_e( 'Enter your email address', 'thetoppercentile-coupons' ); ?>" autocomplete="email">
					</div>
					<div class="consent-checkbox wbte-newsletter-consent-checkbox">
						<input type="checkbox" id="consent-checkbox" name="CONSENT" class="required checkbox" required>
						<label for="consent-checkbox">
						<?php
							printf(
								// translators: %1$s: Privacy Policy link, %2$s: a tag closing.
								esc_html__( 'I consent to receive newsletters and exclusive offers from TheTopPercentile and agree to the %1$s Privacy Policy %2$s.', 'thetoppercentile-coupons' ),
								'<a href="https://www.thetoppercentile.com/privacy-policy/" target="_blank">',
								'</a>'
							);
						?>
						</label>
					</div>
					<div hidden="">
						<input type="hidden" name="tags" value="4546286">
					</div>
					<div id="mce-responses" class="clear">
						<div class="response" id="mce-error-response" style="display: none;"></div>
						<div class="response" id="mce-success-response" style="display: none;"></div>
					</div>
					<div aria-hidden="true" style="position: absolute; left: -5000px;">
						<input type="text" name="b_10e843cdec17dd1d2e769ead6_d9d25110b9" tabindex="-1" value="">
					</div>
					<button type="submit" name="subscribe" id="mc-embedded-subscribe" class="clear wbte-sc-newsletter-subscribe-button">
						<span class="button-text"><?php esc_attr_e( 'Subscribe', 'thetoppercentile-coupons' ); ?></span>
						<span class="button-spinner"></span>
					</button>
				</form>
			</div>
			<?php // phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript, PluginCheck.CodeAnalysis.Offloading.OffloadedContent ?>
			<script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/mc-validate.js"></script><script type="text/javascript">(function($) {window.fnames = new Array(); window.ftypes = new Array();fnames[0]='EMAIL';ftypes[0]='email';fnames[1]='FNAME';ftypes[1]='text';fnames[2]='LNAME';ftypes[2]='text';fnames[3]='ADDRESS';ftypes[3]='address';fnames[4]='PHONE';ftypes[4]='phone';fnames[5]='BIRTHDAY';ftypes[5]='birthday';fnames[6]='MMERGE6';ftypes[6]='text';fnames[7]='IS_BOARD';ftypes[7]='text';fnames[8]='IS_CONF';ftypes[8]='text';fnames[9]='IS_CONT';ftypes[9]='text';}(jQuery));var $mcj = jQuery.noConflict(true);</script>
			<?php // phpcs:enable WordPress.WP.EnqueuedResources.NonEnqueuedScript, PluginCheck.CodeAnalysis.Offloading.OffloadedContent ?>
		</div>
	</div>
	<?php
}