<?php
/**
 * Go to Pro page.
 *
 * @package Wt_Smart_Coupon
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
?>
<style>
/* hide default sidebar */
.wt-sc-tab-container{ width: 73%; }
.wt_smart_coupon_admin_form_right_box{ width:calc(27% - 25px); }

.wt_smcpn_upgrade_to_premium_hd_block{ float:left; width:100%; padding:25px; box-sizing:border-box; background:rgba(233, 245, 255, 0.67); }
.wt_smcpn_upgrade_to_premium_hd_block img{ float:left; width:40px; margin-right:10px; }
.wt_smcpn_upgrade_to_premium_hd_block h3{ float:left; width:calc(100% - 50px); color:#3A6E9B; margin:0px; display:flex; flex-direction:column; justify-content:center; height:40px; }


.wt_smcpn_freevs_pro{  width:100%;  border-collapse:collapse; border-spacing:0px; }
.wt_smcpn_freevs_pro td{ text-align:center; vertical-align:top; padding:15px 20px; line-height:22px;}
.wt_smcpn_freevs_pro tr td:first-child{ text-align:left; vertical-align:middle; padding-left: 50px; font-weight: 500; font-size: 16px;}
.wt_smcpn_freevs_pro tr td:not(:first-child){ padding-left:45px; font-size: 15px; font-weight: 500;}
.wt_sc_free_vs_pro_sub_info{ display:inline-block; margin-bottom:5px; margin-left:-22px; }
.wt_sc_free_vs_pro_feature_info{ margin-left:-22px; }
.wt_smcpn_freevs_pro tr:first-child td{ font-weight:600; font-size: 18px; }
.wt_smcpn_freevs_pro tr { border-top:solid 1px #CACACA; border-top:solid 1px #CACACA; }
.wt_smcpn_freevs_pro .wt_sc_freevspro_table_hd_tr{ background: #F0F0F1; font-weight: 500; font-size: 16px; cursor: pointer;}


.wt_smcpn_tab_container{ float:left; box-sizing:border-box; width:100%; height:auto; }
.wt_smcpn_settings_left{ width:100%; float:left; margin-bottom:5px; }


@media screen and (max-width:1210px) {
	.wt_smcpn_settings_left{ width:100%;}
}

@media (max-width:1200px) {
	.wt-sc-tab-container{ width:65%; }
}

@media (max-width:768px) {
	.wt-sc-tab-container, .wt_smart_coupon_admin_form_right_box{ width:100%; }
}

html[dir="rtl"] .wt_smcpn_settings_left{ float:right; }

</style>
<script type="text/javascript">
	function wt_sc_freevspro_sidebar_switch(href)
	{
		jQuery('.wt-sc-tab-container').attr('data-tab', href.replace('#', ''));
		if ('#wt-sc-freevspro' === href ) {
			jQuery('.wt_smart_coupon_pro_features, .wt_sc_newsletter_subscription_box').hide();

		} else {
			jQuery('.wt_smart_coupon_pro_features, .wt_sc_newsletter_subscription_box').show();
		}

		if ( '#wt-sc-help' === href ) {
			jQuery('.wt_smart_coupon_admin_form_right_box').css({'border-top':'1px solid #c3c4c7'});
		} else {
			jQuery('.wt_smart_coupon_admin_form_right_box').css({'border-top':'none'});
		}


		/**
		 *  Show setup video only in Help guide tab
		 *  
		 *  @since 1.4.7
		 */
		if('#wt-sc-help' === href)
		{
			jQuery('.wt_smart_coupon_setup_video').show();

		}else
		{
			jQuery('.wt_smart_coupon_setup_video').hide();
		}


	}
	jQuery(document).ready(function(){
		
		wt_sc_freevspro_sidebar_switch(jQuery('.wbte_sc_header_nav a.active').attr('href'));

		jQuery('.wbte_sc_header_nav a').on('click', function(){
			wt_sc_freevspro_sidebar_switch(jQuery(this).attr('href'));
		});

		jQuery('.wt_sc_freevspro_table_hd_tr').on('click', function () {
			data_index = jQuery(this).data('index');
			data_state = jQuery(this).data('state');
			if('hidden' === data_state){
				jQuery('.wt_sc_freevspro_table_details_body' + data_index).fadeIn('slow');
				jQuery(this).data('state', 'visible');
				jQuery(".wt_sc_freevspro_table_hd_tr_dashicon" + data_index).removeClass("dashicons-arrow-down-alt2");
				jQuery(".wt_sc_freevspro_table_hd_tr_dashicon" + data_index).addClass("dashicons-arrow-up-alt2");
			}else{
				jQuery('.wt_sc_freevspro_table_details_body' + data_index).fadeOut('slow');
				jQuery(this).data('state', 'hidden');
				jQuery(".wt_sc_freevspro_table_hd_tr_dashicon" + data_index).removeClass("dashicons-arrow-up-alt2");
				jQuery(".wt_sc_freevspro_table_hd_tr_dashicon" + data_index).addClass("dashicons-arrow-down-alt2");
			}
		});
	});
</script>
<div class="wt_smcpn_settings_left">
	<div class="wt_smcpn_tab_container">
		<?php
		require plugin_dir_path( __FILE__ ) . 'comparison-table.php';
		?>
	</div> 
</div>