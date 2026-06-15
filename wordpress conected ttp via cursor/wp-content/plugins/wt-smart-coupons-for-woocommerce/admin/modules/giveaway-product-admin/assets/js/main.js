(function( $ ) {
	'use strict';

	const wt_sc_giveaway_admin=
	{
		Set:function()
		{
			this.give_away_tab_switch();

			/* linking default giveaway select field to bogo select fields in the table. So updating BOGO fields will also update here too */
			$('.wt_sc_bogo_products_fieldset').data('parent-select', $('[name="_wt_free_product_ids[]"]'));
			
			$('[name="_wt_sc_bogo_customer_gets"] option:not([value="specific_product"])').attr("disabled", "disabled");
			
		},
		give_away_tab_switch:function()
		{
			$('#discount_type').on('change', function(){
				const type = $(this).val();
				if(type == wt_sc_giveaway_params.bogo_coupon_type)
				{
					
					$('.wt_sc_normal_coupon_giveaway_tab_content').hide(); /* hide default giveaway tab */
					$('.wt_sc_bogo_coupon_giveaway_tab_content').show(); /* show bogo giveaway tab */
					$('.coupon_amount_field').hide(); /* hide coupon amount field */
					$('._wt_sc_bogo_apply_frequency_field').show(); /* Show `Coupon apply frequency` option */

					$( '.wt_sc_coupon_category_restriction_fields' ).hide();

					$( '._wt_product_condition_field [name="_wt_product_condition"][value="or"]' ).attr( 'disabled', true );

					/** To add 'premium' tag after product condition radio button 'Any from below selection' */
					if( 0 === $( '._wt_product_condition_field [name="_wt_product_condition"][value="or"]' ).closest( 'label' ).next( 'span' ).length ){
						$( '._wt_product_condition_field [name="_wt_product_condition"][value="or"]' ).closest( 'label' ).after( '<span> ' + wt_sc_giveaway_params.msgs.premium + '</span>' );
					}

					$('._wt_product_condition_field [name="_wt_product_condition"][value="and"]').trigger('click');
					
					$('._wt_category_condition_field [name="_wt_category_condition"][value="and"]').trigger('click');	
					$('.wt_sc_coupon_category_restriction_fields select#product_categories').val(null).trigger('change');
					$('.wt_sc_coupon_category_restriction_fields .wt_sc_meta_item_tb_delete_row').trigger('click');

					wt_sc_coupon_edit_meta_item_table.set_val_to_parent_elm($('.wt_sc_bogo_products_fieldset').find('.wt_sc_select2:eq(0)'));

				}else
				{
					
					$('.wt_sc_normal_coupon_giveaway_tab_content').show(); /* show default giveaway tab */
					$('.wt_sc_bogo_coupon_giveaway_tab_content').hide(); /* hide bogo giveaway tab */
					$('.coupon_amount_field').show(); /* hide coupon amount field */
					$('._wt_sc_bogo_apply_frequency_field').hide(); /* Hide `Coupon apply frequency` option */

					if( $('.wt_enable_product_category_restriction').is(':checked') ){
						$( '.wt_sc_coupon_category_restriction_fields' ).show();
					}

					/** To remove 'premium' tag after product condition radio button 'Any from below selection' */
					$( '._wt_product_condition_field [name="_wt_product_condition"][value="or"]' ).closest( 'label' ).next( 'span' ).remove();

					$( '._wt_product_condition_field [name="_wt_product_condition"][value="or"]' ).attr( 'disabled', false );

					$('[name="_wt_free_product_ids[]"] option:gt(0)').remove();
					
				}
			});
			$('#discount_type').trigger('change'); /* toggle visibility on page load */
		}
	};

	$(document).ready(function(){
		wt_sc_giveaway_admin.Set();
	});

})( jQuery );