/**
 * BOGO admin side JS file.
 *
 * @since 2.0.0
 * @package Wt_Smart_Coupon
 */

( function ( $ ) {
	'use strict';

	let wbte_sc_bogo_form_submitted  = false;
	let wbte_sc_bogo_user_interacted = false;

	$( document ).ready(
		function () {

			/**
			 * 	Switch to new BOGO.
			 */
			$( '.wbte_sc_bogo_switching_btn' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					const old_bogo_count = $( this ).attr( 'data-old-bogo-count' );
					
					if ( 0 < old_bogo_count ) {
						if ( !confirm( wbte_sc_bogo_params.text.continue_confirm ) ) {
							return;
						}
					}

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							data: {
								'action'	: 'wbte_sc_switch_to_new_bogo',
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function () {
								window.location.reload();
							},
							error:function () {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			$( '.woocommerce-help-tip' ).tipTip(
				{
					'attribute'	: 'data-tip',
					'fadeIn'	: 50,
					'fadeOut'	: 50,
					'delay'		: 200
				}
			);

			$( '.wbte_sc_new_campaign_box, .wbte_sc_bogo_add_new_popup_predefined p' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					$( this ).addClass( 'wbte_sc_new_campaign_box_selected' );
					$( '.wbte_sc_new_campaign_box, .wbte_sc_bogo_add_new_popup_predefined p' ).not( this ).removeClass( 'wbte_sc_new_campaign_box_selected' );
					$( '#wbte_sc_bogo_campaign_selected_default' ).val( $( this ).attr( 'data-default-btn' ) );

					if ( $( '.wbte_sc_bogo_add_new_popup_form' ).length ) {
						$( '.wbte_sc_bogo_add_new_popup_form' ).css( 'display', 'block' );
						$( this ).css( { 'font-weight' : '700', 'border-color' : '#CCE3FF', 'background-color' : '#F1F8FE' } );
						$( '.wbte_sc_bogo_add_new_popup_predefined p' ).not( this ).css( { 'border-color' : '#EAEBED', 'font-weight' : 'normal', 'background-color' : 'white' } );
					}

					$( '.wbte_sc_bogo_campaign_submit, .wbte_sc_bogo_add_new_continue' ).css( { 'background-color' : '#3157A6', 'cursor' : 'pointer', 'pointer-events' : 'auto' } );
					$( '.wbte_sc_bogo_text_input' ).css( { 'pointer-events' : 'auto', 'border' : '15px solid Ff0000 !important' } );
					$( '.wbte_sc_new_campaign_form_contents' ).css( { 'cursor' : 'pointer', 'color' : '#2A3646' } );
				}
			);

			$( '.wbte_sc_new_campaign_box.default, .wbte_sc_bogo_add_new_popup_predefined p:not( .custom )' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					if ( $( this ).find( 'p' ).length ) {
						$( '#wbte_sc_bogo_coupon_name' ).val( $( this ).find( 'p' ).text().trim() );
					} else {
						$( '#wbte_sc_bogo_coupon_name' ).val( $( this ).text().trim() );
						$( '#wbte_sc_bogo_campaign_description' ).val( $( this ).attr( 'data-desc' ) );
					}
					if ( $( this ).parent().find( '.wbte_sc_new_campaign_box_default_tooltip' ).length ) {
						$( '#wbte_sc_bogo_campaign_description' ).val( $( this ).parent().find( '.wbte_sc_new_campaign_box_default_tooltip' ).text().trim() );
					}

					$( '.wbte_sc_bogo_campaign_custom_radio' ).hide();
					$( '.wbte_sc_cheap_exp_promo_div' ).hide();
					$( '.wbte_sc_new_campaign_form_fields' ).show();
				}
			);

			$( '.wbte_sc_new_campaign_box:not( .default ), .wbte_sc_bogo_add_new_popup_predefined p.custom' ).on(
				'click',
				function ( e ) {
					e.preventDefault();

					$( '#wbte_sc_bogo_coupon_name' ).val( '' );
					$( '#wbte_sc_bogo_campaign_description' ).val( '' );
					$( '.wbte_sc_bogo_campaign_custom_radio' ).show();

					if( 'wbte_sc_bogo_cheap_expensive' === $( 'input[type = radio][name = wbte_sc_bogo_type]:checked' ).val() ) {
						$( '.wbte_sc_cheap_exp_promo_div' ).show();
						$( '.wbte_sc_new_campaign_form_fields' ).hide();
						$( '.wbte_sc_bogo_add_new_popup_form' ).hide();
					}
				}
			);

			/** BOGO add popup radio */
			$( 'input[type = radio][name = wbte_sc_bogo_type]' ).on(
				'change',
				function () {
					if ( 'wbte_sc_bogo_cheap_expensive' === $(this).val() ) {
						$( '.wbte_sc_cheap_exp_promo_div' ).show();
						
						$( '.wbte_sc_new_campaign_form_fields' ).hide();
						$( '.wbte_sc_bogo_custom_bogo_img' ).hide();
						$( '.wbte_sc_bogo_add_new_popup_form' ).hide();
					} else {
						$( '.wbte_sc_new_campaign_form_fields' ).show();
						$( '.wbte_sc_bogo_custom_bogo_img' ).show();
						$( '.wbte_sc_bogo_add_new_popup_form' ).show();

						$( '.wbte_sc_cheap_exp_promo_div' ).hide();
						
					}
				}
			);

			/** BOGO add new campaign form submit. */
			wbte_sc_bogo_add_new();

			/** Bogo general settings click */
			$( document ).on(
				'click',
				'.wbte_sc_bogo_general_settings_button',
				function (e) {
					e.preventDefault();
					$( '#wbte_sc_bogo_general_settings' ).addClass( 'is-open' );
					wbte_sc_bogo_show_overlay();
				}
			);

			$( document ).on(
				'click',
				'.wbte_sc_bogo_general_settings_close',
				function (e) {
					e.preventDefault();
					$( '#wbte_sc_bogo_general_settings' ).removeClass( 'is-open' );
					wbte_sc_bogo_remove_overlay();
				}
			);

			/** On placeholder click in bogo general settings */
			$( '.wbte_sc_bogo_placeholder' ).on(
				'click',
				function () {
					const parent_input = $( this ).attr( 'data-parent-input' );
					const inputElement = $( '#' + parent_input );
					const startPos     = inputElement[0].selectionStart;
					const endPos       = inputElement[0].selectionEnd;
					const inputValue   = inputElement.val();
					const selectedText = inputValue.substring( startPos, endPos );
					const newText      = $( this ).attr( 'id' );

					inputElement.val( inputValue.substring( 0, startPos ) + newText + selectedText + inputValue.substring( endPos ) );
					inputElement[0].setSelectionRange( startPos + newText.length, startPos + newText.length );

					inputElement.trigger( 'focus' );
				}
			);

			/** On blanket click */
			$( document ).on(
				'click', '.wbte_sc_blanket',
				function () {
					$( '#wbte_sc_bogo_general_settings' ).removeClass( 'is-open' );
					$( '.wbte_sc_bogo_add_new_popup' ).hide();
					wbte_sc_bogo_remove_overlay();
				}
			);

			wbte_sc_submit_general_settings();

			/**Add new BOGO click */
			$( '.wbte_sc_add_new_bogo' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_bogo_add_new_popup' ).show();
					wbte_sc_bogo_show_overlay();
				}
			);

			/** BOGO 'add new popup' close */
			$( '.wbte_sc_bogo_add_new_cancel' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_bogo_add_new_popup' ).hide();
					wbte_sc_bogo_remove_overlay();
				}
			);

			/** Delete bogo coupons from listing, ajax action */
			$( '.wbte_sc_bogo_listing_single_delete' ).on(
				'click',
				function (e) {

					e.preventDefault();
					const coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								'action'	: 'wbte_sc_bogo_delete_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								window.location.reload();
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);

				}
			);

			/** Duplicate bogo coupons from listing, ajax action. After duplicating it will redirect to coupon edit page */
			$( '.wbte_sc_bogo_listing_single_duplicate' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					const $coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );
					jQuery.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_single_duplicate',
								'coupon_id'	  : $coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status && 0 !== data.id && data.url ) {
									window.location.href = data.url;
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_bogo_remove_overlay();
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Redirect to BOGO edit page when clicking on a BOGO coupon row in the listing */
			$( '.wbte_sc_bogo_listing_table tbody tr' ).on(
				'click',
				function ( e ) {
					if ( 
						0 < $( e.target ).closest( '.wbte_sc_bogo_listing_actions_content' ).length 
						|| 0 < $( e.target ).closest( '.wbte_sc_bogo_listing_trash_actions_content' ).length 
						|| $( e.target ).hasClass( 'wbte_sc_bogo_listing_checkbox' )
						|| 0 < $( e.target ).closest( '.wbte_sc_bogo_listing_checkbox_td' ).length
					) {
						return;
					}
					window.location.href = $( this ).attr( 'data-edit-url' );
				}
			);

			wbte_sc_bogo_listing_status_toggle();

			/** Bogo listing select all */
			$( '#wbte_sc_bogo_listing_check_all' ).on(
				'change',
				function () {
					wbte_sc_bogo_display_list_selected();
				}
			);

			/** Bogo listing select individual */
			$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).on(
				'change',
				function () {
					wbte_sc_bogo_display_list_selected();
				}
			);

			/** Enable multiple coupons at a time ( draft to publish ). */
			$( '.wbte_sc_bogo_listing_selected_enable' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					const checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_multiple_enable',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status ) {
									data.changed_arrs.forEach(
										function (couponId) {
											const row        = $( `tr[data-coupon_id = "${couponId}"]` );
											const row_status = row.find( '.wbte_sc_bogo_listing_table_status span' );
											const toggle     = row.find( '.wbte_sc_toggle-checkbox' );
											toggle.prop( 'checked', true );
											row_status.removeClass();
											row_status.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
										}
									);
									$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).each(
										function () {
											$( this ).prop( 'checked', false );
										}
									);
									$( '.wbte_sc_bogo_listing_selected_div' ).hide();
									$( '#wbte_sc_bogo_listing_check_all' ).prop( 'checked', false );
									wbte_sc_bogo_remove_overlay();
									if ( data.changed_arrs.length > 0 ) {
										wbte_sc_notify_msg.success( data.msg );
									}
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Disable multiple coupons at a time ( publish to draft ). */
			$( '.wbte_sc_bogo_listing_selected_disable' ).on(
				'click',
				function (e) {
					e.preventDefault();
					wbte_sc_bogo_show_overlay();
					const checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							dataType: 'json',
							data: {
								'action'      : 'wbte_sc_bogo_multiple_disable',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data.status ) {
									data.changed_arrs.forEach(
										function (couponId) {
											const row        = $( `tr[data-coupon_id = "${couponId}"]` );
											const row_status = row.find( '.wbte_sc_bogo_listing_table_status span' );
											const toggle     = row.find( '.wbte_sc_toggle-checkbox' );
											toggle.prop( 'checked', false );
											row_status.removeClass();
											row_status.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
										}
									);
									$( 'input[name="wbte_sc_bogo_listing_check_ind"]' ).each(
										function () {
											$( this ).prop( 'checked', false );
										}
									);
									$( '.wbte_sc_bogo_listing_selected_div' ).hide();
									$( '#wbte_sc_bogo_listing_check_all' ).prop( 'checked', false );
									wbte_sc_bogo_remove_overlay();
									if ( data.changed_arrs.length > 0 ) {
										wbte_sc_notify_msg.success( data.msg );
									}
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Delete multiple coupons at a time ( to trash ). */
			$( '.wbte_sc_bogo_multiple_trash' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_delete_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {

									const currentUrl = new URL( window.location.href );
									/** Preserve existing query parameters */
									const params = new URLSearchParams( currentUrl.search );

									const keys       = [...params.keys()]
									const retainList = ['page']
									for (const key of keys) {
										if ( ! retainList.includes( key )) {
											params.delete( key )
										}
									};

									/** Construct the new URL */
									const newUrl = currentUrl.origin + currentUrl.pathname + '?' + params.toString();

									window.location.href = newUrl;
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Bogo listing trash screen function */

			/** Hide BOGO delete popup when click on cancel button */
			$( '.wbte_sc_delete_bogo_cancel' ).on(
				'click',
				function (e) {
					e.preventDefault();
					$( '.wbte_sc_blanket, .wbte_sc_popup' ).hide();
				}
			);

			/** Restore single coupon from trash */
			$( '.wbte_sc_bogo_listing_single_restore' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const coupon_id = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );

					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								'action'	: 'wbte_sc_bogo_restore_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												const currentUrl = window.location.href;

												const url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Delete permanently single coupon from trash, adding coupon id to popup */
			$( '.wbte_sc_bogo_single_perm_dlt_listing' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const parent_tr    = $( this ).closest( 'tr' );
					const coupon_id    = parent_tr.attr( 'data-coupon_id' );
					const coupon_title = parent_tr.find( '.wbte_sc_bogo_listing_table_title h3' ).text();
					$( '.wbte_sc_bogo_single_dlt_title' ).text( coupon_title );
					$( '.wbte_sc_popup[data-id="wbte_sc_bogo_delete_popup_single"]' ).attr( 'data-coupon_id', coupon_id );

				}
			);

			/** Action on delete button click on permanent delete popup */
			$( '.wbte_sc_bogo_single_perm_delete' ).on(
				'click',
				function (e) {

					e.preventDefault();
					const coupon_id = $( this ).closest( '.wbte_sc_popup' ).attr( 'data-coupon_id' );
					jQuery.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							data: {
								'action'	: 'wbte_sc_bogo_perm_dlt_on_listing',
								'coupon_id' : coupon_id,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success: function (data) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												const currentUrl = window.location.href;

												const url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
										}
									);
									window.location.reload();
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Multiple bogo coupons restore from trash */
			$( '.wbte_sc_bogo_listing_selected_restore' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_restore_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												const currentUrl = window.location.href;

												const url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			/** Multiple bogo coupons delete permanently from trash */
			$( '.wbte_sc_delete_perm_bogo_multiple' ).on(
				'click',
				function (e) {
					e.preventDefault();
					const checked_coupons = wbte_sc_bogo_get_selected_coupons_ids();

					$.ajax(
						{
							url:wbte_sc_bogo_params.ajaxurl,
							type:'POST',
							data: {
								'action'      : 'wbte_sc_bogo_perm_dlt_multiple',
								'coupon_ids'  : checked_coupons,
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
							},
							success:function ( data ) {
								if ( data ) {
									wbte_sc_trash_bogo_count().done(
										function (offer_count) {
											if ( 'undefined' !== typeof( offer_count ) && 0 === offer_count ) {
												const currentUrl = window.location.href;

												const url = new URL( currentUrl );

												if (url.searchParams.has( 'listing_status' )) {
													url.searchParams.delete( 'listing_status' );

													window.location.href = url.toString();
													return;
												}
											}
											window.location.reload();
										}
									);
								}
								else{
									window.location.reload();
								}
							},
							error:function ( data ) {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
							}
						}
					);
				}
			);

			$( '.wbte_sc_bogo_status_filtering' ).on(
				'click',
				function () {
					if ( $( '.wbte_sc_bogo_listing_status_filter_dropdown' ).is( ':visible' ) ) {
						$( '.wbte_sc_bogo_listing_status_filter_dropdown' ).hide();
					} else {
						$( '.wbte_sc_bogo_listing_status_filter_dropdown' ).css( 'display', 'flex' );
					}
				}
			);

			$( document ).on(
				'change',
				'input[ type=checkbox ][ name^=wbte_sc_bogo_listing_filters ]',
				function () {
					let selected_filters = [];
					$( 'input[ type=checkbox ][ name^=wbte_sc_bogo_listing_filters ]:checked' ).each(
						function () {
							selected_filters.push( $( this ).val() );
						}
					);

					const currentUrl = new URL( window.location.href );

					/** Preserve existing query parameters */
					let params       = new URLSearchParams( currentUrl.search );
					const pagenumValue = params.get( 'pagenum' );
					params.delete( 'pagenum' );

					const keys       = [...params.keys()]
					const retainList = ['page', 'listing_status', 'search']
					for (const key of keys) {
						if ( ! retainList.includes( key )) {
							params.delete( key )
						}
					};

					if ( 3 !== selected_filters.length ) {
						selected_filters.forEach(
							function (filter) {
								params.append( 'listing_filters[]', filter );
							}
						);
					}

					/** Append 'pagenum' at the end of the params, pagination purpose. */
					if (pagenumValue) {
						params.append( 'pagenum', pagenumValue );
					}

					const newUrl = currentUrl.origin + currentUrl.pathname + '?' + params.toString();

					window.location.href = newUrl;
				}
			);

			/** Add search element to current URL and reload the page */
			$( '#wbte_bogo_search' ).on(
				'keydown',
				function ( event ) {
					if ( 13 === event.keyCode || 'Enter' === event.key ) {
						event.preventDefault();

						wbte_sc_bogo_search_listing();
					}
				}
			);

			$( '.wbte_bogo_search_icon' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					wbte_sc_bogo_search_listing();
				}
			);

			if ( 0 < $( '.wbte_sc_bogo_edit_step' ).length || 0 < $( '#wbte_sc_bulk_bogo_container' ).length ) {
				/** Bogo edit page */
				wbte_sc_bogo_edit_page();
				const side_bar_width       = $( '#adminmenuwrap' ).width();
				const is_sidebar_collapsed = 0 < $( '.folded #adminmenu' ).length;
				const positionAdjust 	   = wbte_sc_bogo_params.is_rtl ? 'right' : 'left';
				
				$( '.wbte_sc_header' ).css(
					{
						'position'		 : window.innerWidth > 600 ? 'fixed' : 'relative',
						'z-index'		 : '5',
						'top'			 : window.innerWidth > 600 ? '32px' : '-70px',
						[positionAdjust] : window.innerWidth > 600 ? (is_sidebar_collapsed ? '0' : side_bar_width + 'px') : 'auto',
						'width'			 : window.innerWidth > 600 ? (is_sidebar_collapsed ? '100%' : 'calc(100% - ' + side_bar_width + 'px)') : '100%'
					}
				);
				
				// Add resize listener to handle responsive behavior
				$(window).on('resize', function() {
					$( '.wbte_sc_header' ).css(
						{
							'position'		 : window.innerWidth > 600 ? 'fixed' : 'relative',
							'z-index'		 : '5', 
							'top'			 : window.innerWidth > 600 ? '32px' : '-70px',
							[positionAdjust] : window.innerWidth > 600 ? (is_sidebar_collapsed ? '0' : side_bar_width + 'px') : 'auto',
							'width'			 : window.innerWidth > 600 ? (is_sidebar_collapsed ? '100%' : 'calc(100% - ' + side_bar_width + 'px)') : '100%'
						}
					);
				});

				// Adjust width for some elements when the sidebar is collapsed.
				$( document ).on( 
					'wp-collapse-menu', 
					function() { 

						const side_bar_width       = $( '#adminmenuwrap' ).width();
						const is_sidebar_collapsed = 0 < $( '.folded #adminmenu' ).length;
						const positionAdjust 	   = wbte_sc_bogo_params.is_rtl ? 'right' : 'left';

						if( wbte_sc_bogo_params.is_rtl ){

							if ( is_sidebar_collapsed ) {
								$( '.wbte_sc_bogo_edit_general' ).css( 'right', '37px' );
								$( '.wbte_sc_bogo_edit_save_buttons' ).css( 'right', '37px' );
							}else{
								$( '.wbte_sc_bogo_edit_general' ).css( 'right', '163px' );
								$( '.wbte_sc_bogo_edit_save_buttons' ).css( 'right', '163px' );
							}
						}

						$( '.wbte_sc_header' ).css({
							[positionAdjust] : is_sidebar_collapsed ? '0' : side_bar_width + 'px',
							'width'			 : is_sidebar_collapsed ? '100%' : 'calc(100% - ' + side_bar_width + 'px)'
						});
					} 
				);

			}

		}
	);

	function wbte_sc_bogo_show_overlay(){
		$( '.wbte_sc_blanket' ).show();
		$( 'html, body' ).css( { overflow: 'hidden', height: '100%' } );
	}

	/** BOGO main general settings form submit. */
	function wbte_sc_submit_general_settings(){
		$( '#wbte_sc_bogo_general_settings_form' ).on(
			'submit',
			function (e) {
				e.preventDefault();

				const data = $( this ).serialize();
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' 	: 'wbte_sc_bogo_general_settings',
							'data'	 	: data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function ( data ) {
							if ( data.status ) {
								$( '.wbte_sc_bogo_general_settings' ).removeClass( 'is-open' );
								wbte_sc_bogo_remove_overlay();
								wbte_sc_notify_msg.success( data.msg );
							}
							else{
								window.location.reload();
							}
						},
						error:function () {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	/** BOGO add new campaign form submit. */
	function wbte_sc_bogo_add_new(){
		$( '#wbte_sc_new_bogo_coupon' ).on(
			'submit',
			function (e) {
				e.preventDefault();
				wbte_sc_bogo_show_overlay();
				const data = $( this ).serialize();
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' 	: 'wbte_sc_bogo_add_new',
							'data'	 	: data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function (data) {
							if ( data.status && 0 !== data.id && data.url ) {
								window.location.href = data.url;
							}else{
								window.location.reload();
							}
						},
						error:function () {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	function wbte_sc_bogo_edit_page(){

		/** Set boolean to check if user has interacted with the form */
		$( '#wbte_sc_bogo_coupon_save' ).on(
			'input change',
			function () {
				wbte_sc_bogo_user_interacted = true; /** User has interacted with the form */
			}
		);

		/** Show confirmation message when user tries to leave the page without saving the form */
		$( window ).on(
			'beforeunload',
			function (e) {
				if ( wbte_sc_bogo_user_interacted && ! wbte_sc_bogo_form_submitted ) {
					const confirmationMessage = wbte_sc_bogo_params.err_msgs.browser_leaving;
					e.preventDefault();
					e.returnValue = confirmationMessage;
					return confirmationMessage;
				}
			}
		);

		/** Add arrow icon base on open/closed state */
		$( '.wbte_sc_bogo_edit_step' ).each(
			function () {
				if ( $( this ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) {
						$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-up-alt2' );
				} else {
					$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-down-alt2' );
				}
			}
		);

		/** Action to trigger on clicking step container */
		$( '.wbte_sc_bogo_edit_step' ).on(
			'click',
			function () {
				if ( ! $( this ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) { /** Closed state */
					$( this ).addClass( 'wbte_sc_bogo_step_container_opened' );
					$( this ).find( 'span.wbte_sc_bogo_step_arrow' ).removeClass( 'dashicons-arrow-down-alt2' ).addClass( 'dashicons-arrow-up-alt2' );
					$( '.wbte_sc_bogo_edit_step' ).not( $( this ).closest( '.wbte_sc_bogo_edit_step' ) ).removeClass( 'wbte_sc_bogo_step_container_opened' );
					$( '.wbte_sc_bogo_step_arrow' ).not( $( this ).find( 'span.wbte_sc_bogo_step_arrow' ) ).removeClass( 'dashicons-arrow-up-alt2' ).addClass( 'dashicons-arrow-down-alt2' );
				}
				wbte_sc_bogo_step2_add_conditions_summary();
			}
		);

		/** Close step container on clicking header div in step container */
		$( '.wbte_sc_bogo_edit_step_head, .wbte_sc_bogo_step_arrow' ).on(
			'click',
			function () {
				if ( $( this ).closest( '.wbte_sc_bogo_edit_step' ).hasClass( 'wbte_sc_bogo_step_container_opened' ) ) { /** Opened state */
					setTimeout(
						() => {
							$( this ).closest( '.wbte_sc_bogo_edit_step' ).find( 'span.wbte_sc_bogo_step_arrow' ).addClass( 'dashicons-arrow-down-alt2' ).removeClass( 'dashicons-arrow-up-alt2' );
							$( this ).closest( '.wbte_sc_bogo_edit_step' ).removeClass( 'wbte_sc_bogo_step_container_opened' );
						},
						10
					);
				}
			}
		);

		$( 'input[ type=text ][ name=_wbte_sc_bogo_min_qty ], input[ type=text ][ name=_wbte_sc_bogo_max_qty ], input[ type=text ][ name=_wbte_sc_bogo_min_amount ], input[ type=text ][ name=_wbte_sc_bogo_max_amount ], input[ type=radio ][ name=wbte_sc_bogo_triggers_when ], input[ type=radio ][ name=wbte_sc_bogo_apply_offer ], input[ type=text ][ name=wbte_sc_bogo_customer_gets_qty ]' ).on(
			'change, input',
			function () {

				wbte_sc_bogo_repeatedly_once_sum();
				wbte_sc_bogo_repeatedly_sum_list();
				wbte_sc_bogo_step2_individual_summary();
				wbte_sc_bogo_step3_individual_summary();
			}
		);

		/**
		 *  Only allow numbers with decimal in some fields
		 */
		$( document ).on(
			'input',
			'.wbte_sc_admin_input_only_numbers_with_decimal',
			function () {
				const vl  = $( this ).val();
				const reg = /^[0-9]*\.?[0-9]*$/;

				if ( ! reg.test( vl ) ) {
					let new_vl        = '';
					vl                = String( vl );
					const val_length    = vl.length;
					let decimal_found = false;
					for ( let i = 0; i < val_length; i++ ) {
						if ( vl[i] === '.' ) {
							if ( decimal_found ) {
								continue;
							} else {
								decimal_found = true;
							}
						}
						if ( reg.test( vl[i] ) || (vl[i] === '.' && ! decimal_found) ) {
							new_vl += vl[i];
						}
					}
					$( this ).val( new_vl );
				}
			}
		);

		/**
		 *  Only allow numbers
		 */
		$( document ).on(
			'input',
			'.wbte_sc_admin_input_only_number',
			function () {
				const vl  = $( this ).val();
				const reg = /^[0-9]*$/;

				if ( ! reg.test( vl ) ) {
					let new_vl     = '';
					vl             = String( vl );
					const val_length = vl.length;
					for ( let i = 0; i < val_length; i++ ) {
						if ( reg.test( vl[i] ) ) {
							new_vl += vl[i];
						}
					}
					$( this ).val( new_vl );
				}
			}
		);

		/** Change in hidden/visible and disable/enable dropdown on clicking grouped dropdown item */
		$('.wbte_sc_bogo_edit_custom_drop_down p')
			.not('.wbte_sc_bogo_dropdown_menu_item_head, .wbte_sc_bogo_disabled_drop_down_btn')
			.on('click', function () {
				const group = $(this).attr( 'data-group' );

				if( group ){
					const currentOneRow = $(this).attr( 'data-row' );
					$( '.wbte_sc_bogo_edit_custom_drop_down p[data-group="' + group + '"]' ).each( function () {
						const dataRow = $( this ).attr( 'data-row' );
						if( dataRow && dataRow !== currentOneRow ){
							$(`tr[data-row="${dataRow}"]`).addClass('wbte_sc_bogo_conditional_hidden');
						}
						$( this ).removeClass( 'wbte_sc_disabled' );
						$( this ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
					});
				}
			}
		);

		$('.wbte_sc_bogo_edit_custom_drop_down p')
			.not('.wbte_sc_bogo_dropdown_menu_item_head, .wbte_sc_bogo_disabled_drop_down_btn')
			.on('click', function () {
				$( this ).addClass( 'wbte_sc_disabled' );
				
				if( ! $( this ).hasClass( 'wbte_sc_bogo_excl_sel_icn' ) ){
					const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
					$( this ).append( selectedImg );

					/** Show elements of the selected class */
					const rowToDisplay = $(this).attr( 'data-row' );
					if( rowToDisplay ){
						const rowToDisplayContent = $(`tr[data-row="${rowToDisplay}"]`);
						
						if( 0 < rowToDisplayContent.closest( '.wbte_sc_bogo_additional_fields_contents' ).length ){
							$( '.wbte_sc_bogo_additional_fields_contents' ).append( rowToDisplayContent );
						}
						rowToDisplayContent.removeClass('wbte_sc_bogo_conditional_hidden');
					}
				}

				$('.wbte_sc_bogo_edit_custom_drop_down').hide();
			}
		);

		$( '.wbte_sc_bogo_edit_custom_drop_down_head input[type="hidden"]' ).each( function () {
			const hiddenInput = $( this );
			const hiddenInputVal = hiddenInput.val();
			if ( hiddenInputVal ) {
				hiddenInput.closest( '.wbte_sc_bogo_edit_custom_drop_down_head' ).find( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).each( function () {
					if ( hiddenInputVal === $( this ).attr( 'data-val' ) ) {
						$( this ).addClass( 'wbte_sc_disabled' );

						if( ! $( this ).hasClass( 'wbte_sc_bogo_excl_sel_icn' ) ){
							const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
							$( this ).append( selectedImg );
						}
					}
				});
			}
		});

		/** Change dropdown text and hide dropdown on clicking sub button */
		$( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).on(
			'click',
			function () {
				const dropdownHead = $( this ).closest( '.wbte_sc_bogo_edit_custom_drop_down_head' );
				dropdownHead.find( '.wbte_sc_bogo_edit_custom_drop_down_btn p' ).html( $( this ).text() );

				const hiddenInput = dropdownHead.find( 'input[type="hidden"]' );

				if ( 0 < hiddenInput.length ) {
					hiddenInput.val( $( this ).attr( 'data-val' ) ).trigger( 'change' );

					dropdownHead.find( '.wbte_sc_bogo_edit_custom_drop_down_sub_btn' ).removeClass( 'wbte_sc_disabled' );
					dropdownHead.find( '.wbte_sc_dropdown_selected_icon' ).remove();
				}
			}
		);

		/** Step 1 */
		wbte_sc_bogo_step1();

		/** Step 1 */
		wbte_sc_bogo_step2();

		/** Step 3 */
		wbte_sc_bogo_step3();

		$( document ).on(
			'click',
			function ( e ) {
				if( 
					0 < $( e.target ).closest( 'tr' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).length
					|| 0 < $( e.target ).closest( '.wbte_sc_bogo_edit_custom_drop_down_btn' ).length
				){
					return;
				}
				$( '.wbte_sc_bogo_edit_custom_drop_down' ).fadeOut();
			}
		);

		$( '.wbte_sc_bogo_edit_custom_drop_down_btn' ).on(
			'click',
			function () {
				if ( $( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).is( ':visible' ) ) {
					$( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).fadeOut();
				} else {
					$( this ).parent( 'div' ).find( '.wbte_sc_bogo_edit_custom_drop_down' ).fadeIn();
				}
			}
		);

		wbte_sc_bogo_search_tile_alter();

		$( '.wc-product-search, .wc-enhanced-select' ).on(
			'change',
			function (e) {
				wbte_sc_bogo_search_tile_alter();
			}
		);

		/** Removing tab when clicks on delete icon */
		$( '.wbte_sc_bogo_edit_trash' ).on(
			'click',
			function () {
				$( this ).closest( 'tr' ).addClass( 'wbte_sc_bogo_conditional_hidden' );

				const trDataRow = $( this ).closest( 'tr' ).attr( 'data-row' );
				if ( trDataRow ) {
					$( '.wbte_sc_bogo_edit_custom_drop_down p' ).each( function () { 
						if ( trDataRow === $( this ).attr( 'data-row' ) ) {
							$( this ).removeClass( 'wbte_sc_disabled' );
							$( this ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
						}
					 } );
				}
				wbte_sc_bogo_show_prod_cat_default();
			}
		);

		wbte_sc_bogo_edit_general_settings();

		wbte_sc_bogo_realtime_validation();

		wbte_sc_bogo_coupon_save();
	}

	function wbte_sc_bogo_step1(){

		$('.wbte_sc_bogo_customer_gets_dropdown').find('.wbte_sc_radio-label:first').addClass('wbte_sc_disabled');
		$('.wbte_sc_bogo_customer_gets_dropdown').find('.wbte_sc_radio-label:not(:first)').addClass('wbte_sc_bogo_disabled_drop_down_btn');

		if ( 1 < $( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search option:selected' ).length ) {
			$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
		} else {
			$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
		}

		wbte_sc_bogo_step1_summary_change();
		wbte_sc_bogo_customer_gets_discount_type();

		/**  Display or hide fields of 'Free', 'Percentage' and 'Fixed amount' of radio 'wbte_sc_bogo_customer_gets_discount_type' on radio change.*/
		$( 'input[name="wbte_sc_bogo_customer_gets_discount_type"]' ).on(
			'change',
			function () {
				wbte_sc_bogo_customer_gets_discount_type();
				wbte_sc_bogo_step1_summary_change();
			}
		);

		/** Change values in Step 1 summary on changes in input */
		$( 'input[ type=text ][ name=wbte_sc_bogo_customer_gets_discount_perc ], input[ type=text ][ name=wbte_sc_bogo_customer_gets_discount_price ], #wbte_sc_bogo_customer_gets_qty' ).on(
			'change',
			function () {
				wbte_sc_bogo_step1_summary_change();
			}
		);

		$( '.wbte_sc_bogo_customer_gets_specific_prod_row select.wc-product-search' ).on(
			'change',
			function () {
				const selectedOptionsCount = $( this ).find( 'option:selected' ).length;
				if ( 1 < selectedOptionsCount ) {
					$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				} else {
					$( '.wbte_sc_bogo_customer_gets_product_condition_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( 'input[name="wbte_sc_bogo_gets_product_condition"][value="all"]' ).prop( 'checked', true );
					$( 'input[name="wbte_sc_bogo_gets_product_condition"][value="any"]' ).prop( 'checked', false );
				}
			}
		);

		/** Free shipping warning */
		if( $( '#free_shipping' ).is( ':checked' ) && '1' !== $( '.wbte_sc_bogo_free_shipping_warning' ).attr( 'data-free-shipp-enabled' ) ){
			$( '.wbte_sc_bogo_free_shipping_warning' ).css( 'display', 'flex' );
		}

		$( '#free_shipping' ).on( 'change', function(){
			if( $( this ).is( ':checked' ) && '1' !== $( '.wbte_sc_bogo_free_shipping_warning' ).attr( 'data-free-shipp-enabled' ) ){
				$( '.wbte_sc_bogo_free_shipping_warning' ).css( 'display', 'flex' );
			}else{
				$( '.wbte_sc_bogo_free_shipping_warning' ).hide();
			}
		});

	}

	function wbte_sc_bogo_step2(){

		const $triggerRadioButtons = $('input[type=radio][name=wbte_sc_bogo_triggers_when]');
		const $minmaxQty = $('.wbte_sc_bogo_edit_minmax_qty');
		const $minmaxAmount = $('.wbte_sc_bogo_edit_minmax_amount');
		const $customDropdown = $('.wbte_sc_bogo_edit_custom_drop_down');
		const $adtlQtyRow = $('tr[data-row="wbte_sc_bogo_qty_row"]');
		const $adtlQtyRowDropdownHead = $customDropdown.find('p[data-row="wbte_sc_bogo_qty_row"]');
		
		const $summaryCustomerAction = $('.wbte_sc_bogo_step2_summary_customer_action');

		/** Step2: Switch qty and amount field by change in radio */
		$triggerRadioButtons.on('change', function () {
			const isQtyTrigger = 'wbte_sc_bogo_triggers_qty' === this.value;
	
			if ( isQtyTrigger ) {
				/** Hide and disable relevant elements */
				$minmaxAmount.add($adtlQtyRow).add($adtlQtyRowDropdownHead).addClass('wbte_sc_bogo_conditional_hidden');
				$minmaxQty.removeClass('wbte_sc_bogo_conditional_hidden');
				$summaryCustomerAction.html(wbte_sc_bogo_params.text.buys);
			} else {
				/** Show and enable relevant elements */
				$minmaxAmount.add($adtlQtyRowDropdownHead).removeClass('wbte_sc_bogo_conditional_hidden');
				$minmaxQty.addClass('wbte_sc_bogo_conditional_hidden');
				$summaryCustomerAction.html(wbte_sc_bogo_params.text.spends);
				$adtlQtyRowDropdownHead.removeClass( 'wbte_sc_disabled wbte_sc_bogo_conditional_hidden' ).find( '.wbte_sc_dropdown_selected_icon' ).remove();
			}
		});

		wbte_sc_bogo_step2_individual_summary();

		/** Step2: Switch qty and amount field by change in radio */
		$( 'input[ type=radio ][ name=wbte_sc_bogo_triggers_when ]' ).on(
			'change',
			function () {
				if ( 'wbte_sc_bogo_triggers_qty' === this.value ) {
					$( '.wbte_sc_bogo_edit_minmax_amount' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_edit_minmax_qty' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.buys );
				} else {
					$( '.wbte_sc_bogo_edit_minmax_amount' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_edit_minmax_qty' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.spends );
				}
			}
		);

		if ( wbte_sc_bogo_is_spends() ) {
			$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.spends );
		} else {
			$( '.wbte_sc_bogo_step2_summary_customer_action' ).html( wbte_sc_bogo_params.text.buys );
			$( 'tr[data-row="wbte_sc_bogo_qty_row"], .wbte_sc_bogo_edit_custom_drop_down p[data-row="wbte_sc_bogo_qty_row"]' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
		}

		/** Customer buys, product-cat include exclude */
		$( '.wbte_sc_bogo_edit_add_customer_buys' ).on(
			'click',
			function ( e ) {
				e.preventDefault();
				if ( $( '.wbte_sc_bogo_edit_customer_buys_select' ).is( ':visible' ) ) {
					$( '.wbte_sc_bogo_edit_customer_buys_select' ).fadeOut();
				} else {
					$( '.wbte_sc_bogo_edit_customer_buys_select' ).fadeIn();
				}
			}
		);

		/**Customer buys select */
		$( '.wbte_sc_bogo_edit_customer_buys_select p' ).not( '.wbte_sc_bogo_edit_custom_select_head' ).on(
			'click',
			function () {
				wbte_sc_bogo_show_prod_cat_default();
			}
		);

		/** Product/Category restriction */
		$( '.wbte_sc_bogo_prod_cat_restriction_sub_btn' ).on(
			'click',
			function () {
				$( this ).closest( 'td' ).find( '.wbte_sc_bogo_edit_custom_drop_down_btn p' ).html( $( this ).text() );

				if ( $( this ).hasClass( 'wbte_sc_bogo_edit_specific_prod_btn' ) ) {
					$( '.wbte_sc_bogo_edit_excluded_products_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_edit_specific_products_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );

					/** Remove products selected in excluded products */
					$( '#wbte_sc_bogo_excluded_products' ).val( null ).trigger( 'change' );
				} else if ( $( this ).hasClass( 'wbte_sc_bogo_edit_excluded_prod_btn' ) ) {
					$( '.wbte_sc_bogo_edit_specific_products_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '.wbte_sc_bogo_edit_excluded_products_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );

					/** Remove products selected in selected products */
					$( '#wbte_sc_bogo_specific_products' ).val( null ).trigger( 'change' );
				} 

				$( '.wbte_sc_bogo_edit_custom_drop_down' ).hide();
			}
		);

		/** Additional conditions */
		$( '.wbte_sc_bogo_edit_addition_conditions' ).on(
			'click',
			function ( e ) {
				if ( $( '.wbte_sc_bogo_edit_additional_condition_select' ).is( ':visible' ) ) {
					$( '.wbte_sc_bogo_edit_additional_condition_select' ).fadeOut();
				} else {
					$( '.wbte_sc_bogo_edit_additional_condition_select' ).fadeIn();
				}
			}
		);

		$('.wbte_sc_bogo_edit_custom_drop_down label')
			.not('.wbte_sc_bogo_disabled_drop_down_btn')
			.on('click', function () {
				$( this ).addClass( 'wbte_sc_disabled' )
			}
		);

		$( '.wbte_sc_bogo_edit_custom_drop_down p' ).each( function () {
			const dataRow = $( this ).attr( 'data-row' );
			if( dataRow && ! $( `tr[data-row=${dataRow}]` ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ){
				$( this ).addClass( 'wbte_sc_disabled' );
				const selectedImg = `<img class="wbte_sc_dropdown_selected_icon" src="${wbte_sc_bogo_params.urls.image_path}selected_grey.svg" >`;
				$( this ).append( selectedImg );
			}
		});

		wbte_sc_bogo_email_select.Set();

		wbte_sc_bogo_step2_add_conditions_summary();
	}

	function wbte_sc_bogo_step3(){

		/**  Display or hide fields of 'Apply offer' of radio 'wbte_sc_bogo_apply_offer' on radio change.*/
		$( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]' ).on(
			'change',
			function () {
				wbte_sc_bogo_apply_offer_times();
				$( '.wbte_sc_bogo_apply_repeatedly_short' ).text( $( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' ).parent().text().trim() );
				wbte_sc_bogo_step3_individual_summary();
			}
		);

		/** Once, Repeatedly */
		$( '.wbte_sc_bogo_apply_repeatedly_short' ).text( $( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' ).parent().text().trim() );

		wbte_sc_bogo_apply_offer_times();

		wbte_sc_bogo_repeatedly_sum_list();

		wbte_sc_bogo_repeatedly_once_sum();

		wbte_sc_bogo_step3_individual_summary();
	}

	/** Function to handle 'wbte_sc_bogo_customer_gets_discount_type' radio */
	function wbte_sc_bogo_customer_gets_discount_type() {
		switch ($( "input[name='wbte_sc_bogo_customer_gets_discount_type']:checked" ).val()) {
			case 'wbte_sc_bogo_customer_gets_free':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
			case 'wbte_sc_bogo_customer_gets_with_perc_discount':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
			case 'wbte_sc_bogo_customer_gets_with_fixed_discount':
				$( '.wbte_sc_bogo_customer_gets_discount_type_fixed_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_customer_gets_discount_type_perc_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				break;
		}
	}

	/** Change selected product/category cross button from left to right */
	function wbte_sc_bogo_search_tile_alter(){
		setTimeout(
			function () {
				$( '.select2-selection__choice__remove' ).each(
					function () {
						$( this ).parent().append( $( this ) );
						$( this ).css( { 'font-size': '18px', 'font-weight' : 'normal', 'margin' : '0 2px' } );
					}
				);
			},
			10
		);
	}

	/** Show or hide 'Any product (default)' based on product category restriction */
	function wbte_sc_bogo_show_prod_cat_default(){
		setTimeout(
			() => {
				if ( 0 < $( '.wbte_sc_bogo_edit_products_row:visible' ).length ) {
					$( '.wbte_sc_bogo_prod_default_row' ).fadeOut();
				} else {
					$( '.wbte_sc_bogo_prod_default_row' ).fadeIn();
				}
				wbte_sc_bogo_step2_individual_summary();
			},
			500
		);
	}

	/** Function to handle 'wbte_sc_bogo_apply_offer' radio. */
	function wbte_sc_bogo_apply_offer_times() {
		const selectedValue = $( 'input[ type=radio ][ name=wbte_sc_bogo_apply_offer ]:checked' ).val();

		switch ( selectedValue ) {
			case 'wbte_sc_bogo_apply_once':
				$( '.wbte_sc_bogo_apply_repeatedly_row' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_once_row' ).show();
				break;
			case 'wbte_sc_bogo_apply_repeatedly':
				$( '.wbte_sc_bogo_apply_repeatedly_row' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
				$( '.wbte_sc_bogo_apply_once_row' ).hide();
				break;
		}
	}

	function wbte_sc_bogo_is_spends(){
		return 'wbte_sc_bogo_triggers_subtotal' === $( 'input[ type="radio" ][ name="wbte_sc_bogo_triggers_when" ]:checked' ).val();
	}

	function wbte_sc_bogo_repeatedly_once_sum(){
		const isSpends     = wbte_sc_bogo_is_spends();
		const minVal 	   = $( isSpends ? '#_wbte_sc_bogo_min_amount' : '#_wbte_sc_bogo_min_qty' ).val() || '-';
		const maxVal 	   = $( isSpends ? '#_wbte_sc_bogo_max_amount' : '#_wbte_sc_bogo_max_qty' ).val();
		const giveawayQty  = $( '#wbte_sc_bogo_customer_gets_qty' ).val() || '-';
		const repeatelyMsg = wbte_sc_bogo_params.summary_text[ isSpends ? 'once_spends' : 'once_buys' ];
		$( '.wbte_sc_bogo_repeatedly_once_msg' ).html( repeatelyMsg );
		if ( minVal ) {
			$( '.wbte_sc_bogo_custom_min_sum' ).html( minVal );
		}
		if ( maxVal ) {
			$( '.wbte_sc_bogo_custom_max_sum' ).html( maxVal );
		} else {
			$( '.wbte_sc_bogo_custom_max_sum' ).html( '\u221E' );
		}
		$( '.wbte_sc_bogo_free_count_sum' ).html( giveawayQty );
		return $( '.wbte_sc_bogo_repeatedly_once_msg' ).html();
	}

	function wbte_sc_bogo_repeatedly_sum_list() {
		const isSpends     = wbte_sc_bogo_is_spends();
		const minVal 	   = $( isSpends ? '#_wbte_sc_bogo_min_amount' : '#_wbte_sc_bogo_min_qty' ).val();
		const giveawayQty  = $( '#wbte_sc_bogo_customer_gets_qty' ).val();
		const repeatelyMsg = wbte_sc_bogo_params.summary_text[ isSpends ? 'repeatedly_spends' : 'repeatedly_buys' ];
		let repeatedlySum  = '<ul>';

		for ( let i = 0; i < 5; i++ ) {
			if ( [2, 3, 4].includes( i ) ) {
				repeatedlySum += '<li class="wbte_sc_bogo_repeatedly_dot">.</li>';
				continue;
			}
			let _repeatedlyMsg = repeatelyMsg
				.replace( '{buy_spend_val}', minVal * (i + 1) )
				.replace( '{repeatedly_free_count}', giveawayQty * (i + 1) );
			repeatedlySum     += ` <li> ${_repeatedlyMsg} </li> `;
		}
		repeatedlySum += ` <li> ${wbte_sc_bogo_params.text.and_so_on} </li> `;

		repeatedlySum += '</ul>';

		$( '.wbte_sc_bogo_repeatedly_msg' ).html( repeatedlySum );
		return $( '.wbte_sc_bogo_repeatedly_msg' ).html();
	}

	/** Function to handle Step 1 summary on change in inputs */
	function wbte_sc_bogo_step1_summary_change() {

		const selected_discount_type      = $( 'input[type=radio][name=wbte_sc_bogo_customer_gets_discount_type]:checked' ).val();
		let textToShow, discount_amount;
		const customer_gets_qty = $( '#wbte_sc_bogo_customer_gets_qty' ).val() || '-';

		if ( 'wbte_sc_bogo_customer_gets_free' === selected_discount_type ) {
			textToShow = wbte_sc_bogo_params.summary_text.discount_free;
		} else {
			textToShow = wbte_sc_bogo_params.summary_text.discount_perc_fixed;
			if ( 'wbte_sc_bogo_customer_gets_with_perc_discount' === selected_discount_type ) {
				discount_amount = $( '#wbte_sc_bogo_customer_gets_discount_perc' ).val() + '%';
			} else {
				discount_amount = wbte_sc_bogo_params.text.currency_symbol + $( '#wbte_sc_bogo_customer_gets_discount_price' ).val();
			}
		}

		$( '.wbte_sc_bogo_step1_short_description p' ).html( textToShow );
		$( '.wbte_sc_bogo_step1_summary_qty' ).html( customer_gets_qty );
		$( '.wbte_sc_bogo_s2_summary_discount_amount' ).html( discount_amount );

	}

	/** Function to handle Step 2 summary on change in inputs */
	function wbte_sc_bogo_step2_individual_summary(){
		let min_val, max_val, textToShow, conditionKeyPrefix;
		const is_selected_products = $( '.wbte_sc_bogo_edit_products_row' ).not( '.wbte_sc_bogo_conditional_hidden' ).length > 0;

		if (wbte_sc_bogo_is_spends()) {
			min_val            = $( '#_wbte_sc_bogo_min_amount' ).val() ? wbte_sc_bogo_params.text.currency_symbol + $( '#_wbte_sc_bogo_min_amount' ).val() : '';
			max_val            = $( '#_wbte_sc_bogo_max_amount' ).val() ? wbte_sc_bogo_params.text.currency_symbol + $( '#_wbte_sc_bogo_max_amount' ).val() : '';
			conditionKeyPrefix = 'spends';
		} else {
			min_val            = $( '#_wbte_sc_bogo_min_qty' ).val();
			max_val            = $( '#_wbte_sc_bogo_max_qty' ).val();
			conditionKeyPrefix = 'buys';
		}

		if (min_val && max_val) {
			textToShow = is_selected_products
				? wbte_sc_bogo_params.summary_text[`${conditionKeyPrefix}_between_selected`]
				: wbte_sc_bogo_params.summary_text[`${conditionKeyPrefix}_between_any`];
			

		} else {
			textToShow = is_selected_products
				? wbte_sc_bogo_params.summary_text[`${conditionKeyPrefix}_atleast_selected`]
				: wbte_sc_bogo_params.summary_text[`${conditionKeyPrefix}_atleast_any`];
		}

		min_val = min_val || '-';
		max_val = max_val || '-';
		textToShow = textToShow.replace('{min}', min_val).replace('{max}', max_val);
		$( '.wbte_sc_bogo_step2_short_description p' ).html( textToShow );
	}

	/** Function to handle Step 3 summary on change in inputs */
	function wbte_sc_bogo_step3_individual_summary(){
		const repeatedlyMode = $( 'input[ type="radio" ][ name="wbte_sc_bogo_apply_offer" ]:checked' ).val();
		let summary          = '';

		if ( 'wbte_sc_bogo_apply_once' === repeatedlyMode ) {
			const once_summary = wbte_sc_bogo_repeatedly_once_sum();
			$( '.wbte_sc_bogo_repeatedly_additional_summary' ).html( ` <p style = "margin-top:15px;" > ${once_summary} </p> ` );
		} else if ( 'wbte_sc_bogo_apply_repeatedly' === repeatedlyMode ) {
			const repeatLimit = $( '#wbte_sc_bogo_repeatedly_times' ).val();
			if ( repeatLimit ) {
				summary = wbte_sc_bogo_params.short_summary_text.repeatedly;
			}
			const repeatedly_summary = wbte_sc_bogo_repeatedly_sum_list();
			$( '.wbte_sc_bogo_repeatedly_additional_summary' ).html( summary + repeatedly_summary );
			$( '.wbte_sc_bogo_repeatedly_sum' ).html( repeatLimit );
		} 
	}

	/** Function to handle edit page general settings */
	function wbte_sc_bogo_edit_general_settings(){

		const element = $('.wbte_sc_bogo_code_cond_help_txt').detach();
        $('input[name="wbte_sc_bogo_code_condition"][value="wbte_sc_bogo_code_auto"]').parent().after(element);

		/** Coupon code automatic or manual */
		$( 'input[ type=radio ][ name=wbte_sc_bogo_code_condition ]' ).on(
			'change',
			function () {
				if ( 'wbte_sc_bogo_code_auto' === this.value ) {
					$( '#wbte_sc_bogo_coupon_code' ).parent( 'div' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
				} else {
					$( '#wbte_sc_bogo_coupon_code' ).parent( 'div' ).removeClass( 'wbte_sc_bogo_conditional_hidden' );
					$( '#wbte_sc_bogo_coupon_code' ).val( $( '#wbte_sc_bogo_coupon_code' ).data( 'coupon-code' ) );
				}
			}
		);

		$( '.wbte_sc_bogo_code_copy' ).on(
			'click',
			function () {
				const couponName = $( '#wbte_sc_bogo_coupon_name' ).val() || '';
				const couponId	= $( '#wt_sc_bogo_coupon_id' ).val() || 0 ;
				const couponCode = $( '#wbte_sc_bogo_coupon_code' ).val();
				let timeoutId;

				if( '' === couponCode ) {
					$.ajax(
						{
							url: wbte_sc_bogo_params.ajaxurl,
							type: 'POST',
							dataType: 'json',
							data: {
								'action'	: 'wbte_sc_get_auto_offer_code',
								'_wpnonce'	: wbte_sc_bogo_params.admin_nonce,
								'coupon_name' : couponName,
								'coupon_id'	: couponId
							},
							success: function ( data ) {
								if ( data.status && '' !== data.coupon_code ) {
									wbte_sc_copy_to_clipboard(
										data.coupon_code,
										function() {
											const newToolTip = wbte_sc_bogo_params.text.success_copy.replace( '{coupon_code}', data.coupon_code );
											$( '.wbte_sc_hidden_tooltip' ).html( newToolTip );
											
											if ( timeoutId ) clearTimeout( timeoutId );
											timeoutId = setTimeout( () => {
												$( '.wbte_sc_hidden_tooltip' ).html( wbte_sc_bogo_params.text.coupon_copy_tooltip ); 
											}, 1000 );
										},
										function() {
											wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.failed_copy );
										}
									);
								} 
							},
							error:function () {
								wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.failed_copy );
							}
						}
					);
				} else {
					wbte_sc_copy_to_clipboard(
						couponCode,
						function() {
							const newToolTip = wbte_sc_bogo_params.text.success_copy.replace( '{coupon_code}', couponCode );
							$( '.wbte_sc_hidden_tooltip' ).html( newToolTip );
							
							if ( timeoutId ) clearTimeout( timeoutId );
							timeoutId = setTimeout( () => {
								$( '.wbte_sc_hidden_tooltip' ).html( wbte_sc_bogo_params.text.coupon_copy_tooltip ); 
							}, 1000 );
						}
					);
				}
			}
		);

		$( '#wbte_sc_bogo_coupon_code' ).on(
			'input',
			function () {
				const errorSpan = $( '.wbte_sc_bogo_coupon_code_error_span' );
				errorSpan.find( '.wbte_sc_bogo_edit_error_txt' ).prev( 'br' ).remove();
				errorSpan.find( '.wbte_sc_bogo_edit_error_txt' ).remove();

				if ( ! wbte_sc_bogo_coupon_code_validation() ) {
					errorSpan.append( '<br><span class="wbte_sc_bogo_edit_error_txt">' + wbte_sc_bogo_params.err_msgs.coupon_code_error + '</span>' );
				} else {
					errorSpan.empty();
				}
			}
		);

		/** Display coupon display checkbox on edit or add */
		$( '.wbte_sc_bogo_coupon_display_add_btn, .wbte_sc_bogo_display_div img' ).on(
			'click',
			function () {
				$( '.wbte_sc_bogo_display_div .wbte_sc_checkbox' ).fadeIn();
				$( '.wbte_sc_bogo_selected_display_span, .wbte_sc_bogo_coupon_display_add_btn' ).addClass( 'wbte_sc_bogo_conditional_hidden' );
			}
		);

		/** Add or remove display coupon tag on change in display coupon selection */
		$( 'input[type="checkbox"][name="_wc_make_coupon_available[]"]' ).on(
			'change',
			function () {

				const checkboxId = $( this ).attr( 'id' );
				if ( ! this.checked ) {
					$( '.wbte_sc_bogo_selected_display.' + checkboxId ).remove();
				} else {

					const labelText = $( 'label[for="' + checkboxId + '"]' ).text().trim();
					const newSpan   = ` <span class = "wbte_sc_bogo_selected_display ${checkboxId}" > ${labelText} </span> `;

					$( '.wbte_sc_bogo_selected_display_span' ).prepend( newSpan );
				}
			}
		);

		/** Display or hide start/enddate fields on clicking schedule checkbox  */
		$( '#wbte_sc_bogo_schedule' ).on(
			'change',
			function () {
				if ( $( this ).is( ':checked' ) ) {
					$( '#wbte_sc_bogo_schedule_content' ).fadeIn();
				} else {
					$( '#wbte_sc_bogo_schedule_content' ).fadeOut();
				}
			}
		);

		/** Change color of datepicker placeholder and value. */
		$( '.wbte_sc_bogo_date_picker' ).on(
			'change',
			function () {
				'' === $( this ).val() ? $( this ).attr( 'style', 'color: #9DA3AA !important' ) : $( this ).attr( 'style', 'color: #2A3646 !important' );
				if ( '' !== $( this ).val() ) {
					const parentTr = $( '#wbte_sc_bogo_schedule_content' );
					parentTr.find( '.wbte_sc_bogo_edit_error_txt' ).remove();
					parentTr.find( 'img[src$="exclamation_red.svg"]' ).prev( 'br' ).remove();
					parentTr.find( 'img[src$="exclamation_red.svg"]' ).remove();
				}
			}
		);

		$( '.wbte_sc_bogo_date_picker' ).each(
			function () {
				'' === $( this ).val() ? $( this ).attr( 'style', 'color: #9DA3AA !important' ) : $( this ).attr( 'style', 'color: #2A3646 !important' );
			}
		);

		/** Show warning if expiry date is less than current date on page load */
		wbte_sc_bogo_show_expiry_warning();

		/** Show warning if expiry date is less than current date */
		$( '.wbte_sc_schedule_expiry_field_row input' ).on( 'change input', function () {
			wbte_sc_bogo_show_expiry_warning();
		});
	}

	/** Function to handle Step 2 additional condition short summary */
	function wbte_sc_bogo_step2_add_conditions_summary(){

		const minEachQty 			= $( '#_wbte_sc_min_qty_each' ).val();
		const maxEachQty			= $( '#_wbte_sc_max_qty_each' ).val();
		const minQty 				= $( '#_wbte_sc_bogo_min_qty_add' ).val();
		const maxQty 				= $( '#_wbte_sc_bogo_max_qty_add' ).val();
		const usageLimitPerUser 	= $( '#usage_limit_per_user' ).val();
		const usageLimitPerCoupon = $( '#usage_limit' ).val();

		/** Check if the closest tr is not hidden */
		function isVisible(element) {
			return ! $( element ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' );
		}

		const summaryTextMap = {
			qty_each		: minEachQty && maxEachQty && isVisible( '#_wbte_sc_min_qty_each' ),
			min_qty_each	: minEachQty && ! maxEachQty && isVisible( '#_wbte_sc_min_qty_each' ),
			qty				: minQty && maxQty && isVisible( '#_wbte_sc_bogo_min_qty_add' ),
			min_qty			: minQty && ! maxQty && isVisible( '#_wbte_sc_bogo_min_qty_add' ),
			limit_per_user	: usageLimitPerUser && isVisible( '#usage_limit_per_user' ),
			limit_per_coupon: usageLimitPerCoupon && isVisible( '#usage_limit' )
		};

		let summary = '';
		$.each(
			summaryTextMap,
			function (key, condition) {
				if (condition) {
					summary += ` <li> ${wbte_sc_bogo_params.short_summary_text[key]} </li> `;
				}
			}
		);

		if ( isVisible( '#customer_email' ) ) {

			let emailSummary     = ` <li> ${wbte_sc_bogo_params.short_summary_text.email}`;
			let email_text_added = false;
			$( '.wbte_sc_bogo_email_select_inner span' ).each(
				function ( index ) {
					if ( index > 1 ) {
							emailSummary += ' ...';
							return false;
					}
					const email = $( this ).clone().find('b').remove().end().text().trim();
					emailSummary    += ` <span> ${email} </span>&nbsp; `;
					email_text_added = true;
				}
			);
			emailSummary += '</li>';
			if ( email_text_added ) {
				summary += emailSummary;
			}
		}

		if ( '' !== summary ) {
			summary = ` <p style = 'font-size:14px; font-weight:500;' > ${wbte_sc_bogo_params.short_summary_text.add_conditions} </p> <ul> ${summary} </ul> `;
		}

		$( '.wbte_sc_bogo_step_add_desc' ).html( summary );

		const _add_summary_spans = {
			'wbte_sc_bogo_add_per_user_sum'		: usageLimitPerUser,
			'wbte_sc_bogo_add_per_coupon_sum'	: usageLimitPerCoupon,
			'wbte_sc_bogo_add_qty_min_sum'		: minQty ,
			'wbte_sc_bogo_add_qty_max_sum'		: maxQty ,
			'wbte_sc_bogo_add_qty_each_min_sum'	: minEachQty ,
			'wbte_sc_bogo_add_qty_each_max_sum'	: maxEachQty
		};

		$.each(
			_add_summary_spans,
			function (key, value) {
				if (value) {
					$( '.' + key ).html( value );
				}
			}
		);
	}

	function wbte_sc_bogo_show_expiry_warning() {
		const expiryDateInput = $('#expiry_date').val();
	
		/** Parse the expiry date and time */
		const expiryDate = new Date(expiryDateInput);
		expiryDate.setSeconds(0); // set seconds to 0 for precise comparison.
	
		const timezone = wbte_sc_bogo_params.timezone || 'UTC';
	
		try {
			
			const currentDate = new Date();
			const currentDateInWPTimeZone = new Date(
				currentDate.toLocaleString( 'en-US', { timeZone: timezone } )
			);
			
			if ( currentDateInWPTimeZone > expiryDate ) {
				$( '.wbte_sc_bogo_end_date_warning' ).css( 'display', 'flex' );
			} else {
				$( '.wbte_sc_bogo_end_date_warning' ).hide();
			}
		} catch ( error ) {
			$( '.wbte_sc_bogo_end_date_warning' ).hide(); 
		}
	}

	function wbte_sc_bogo_coupon_save(){
		$( '#wbte_sc_bogo_coupon_save' ).on(
			'submit',
			function (e) {
				e.preventDefault();
				wbte_sc_bogo_show_overlay();
				
				const data = wbte_sc_bogo_data_processing( e );
				
				if( ! data ) {
					return false;
				}
				
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action' 	: 'wbte_sc_bogo_coupon_save',
							'data'		: data,
							'_wpnonce'	: wbte_sc_bogo_params.admin_nonce
						},
						success:function ( data ) {
							if ( data.status ) {
								wbte_sc_notify_msg.success( data.msg );

								wbte_sc_bogo_form_submitted = true;

								/** Remove get param newly_created from URL. Which is used to hide or show status selection */
								let currentUrl = window.location.href;
								let url        = new URL( currentUrl );
								if ( url.searchParams.has( 'newly_created' ) ) {
									url.searchParams.delete( 'newly_created' );
									window.history.replaceState( null, '', url.toString() );
									if ( 'publish' === data.bogo_sts ) {
										$( '#_wbte_sc_bogo_selected_sts_publish' ).prop( 'checked', true );
									} else {
										$( '#_wbte_sc_bogo_selected_sts_draft' ).prop( 'checked', true );
									}
									$( '.wbte_sc_bogo_save_and_activate' ).hide();
									$( '.wbte_sc_bogo_edit_gnrl_sts_radio' ).removeClass( 'hide' );
								}
							} else {
								wbte_sc_notify_msg.error( data.msg );
							}
							wbte_sc_bogo_remove_overlay();
						},
						error:function ( data ) {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	/** Bogo coupon status toggle button action in listing page, ajax action. Switch between status publish and draft. */
	function wbte_sc_bogo_listing_status_toggle(){

		$( 'input[name="wbte_sc_bogo_listing_actions_toggle"]' ).on(
			'change',
			function (e) {
				const coupon_id      = $( this ).closest( 'tr' ).attr( 'data-coupon_id' );
				const is_checked     = $( this ).is( ':checked' );
				const status_element = $( this ).closest( 'tr' ).find( '.wbte_sc_bogo_listing_table_status span' );
				jQuery.ajax(
					{
						url:wbte_sc_bogo_params.ajaxurl,
						type:'POST',
						dataType: 'json',
						data: {
							'action'      : 'wbte_sc_bogo_listing_update_status_on_toggle',
							'data'		  : {
								'coupon_id'	  : coupon_id,
								'is_checked'  : is_checked
							},
							'_wpnonce'	  : wbte_sc_bogo_params.admin_nonce
						},
						success:function ( data ) {
							if ( data.status ) {
								status_element.removeClass();
								status_element.addClass( 'wbte_sc_label ' + data.transition_to_class ).html( data.transition_to );
								wbte_sc_notify_msg.success( data.msg );
							}
							else{
								window.location.reload();
							}
						},
						error:function ( data ) {
							wbte_sc_notify_msg.error( wbte_sc_bogo_params.text.error );
						}
					}
				);
			}
		);
	}

	function wbte_sc_bogo_display_list_selected(){
		if ( 0 < wbte_sc_bogo_selected_count() ) {
			const new_text = '(' + wbte_sc_bogo_selected_count() + ' ' + wbte_sc_bogo_params.text.selected + ')';
			$( '.wbte_sc_bogo_listing_selected_div' ).css( 'display', 'flex' );
			$( '.wbte_sc_bogo_listing_selected_div_select_count' ).text( new_text );
		} else {
			$( '.wbte_sc_bogo_listing_selected_div' ).hide();
			$( '.wbte_sc_bogo_listing_selected_div_select_count' ).text( wbte_sc_bogo_params.text.selected );
		}
	}

	function wbte_sc_bogo_selected_count(){
		return $( 'input[name="wbte_sc_bogo_listing_check_ind"]:checked' ).length;
	}

	function wbte_sc_bogo_get_selected_coupons_ids(){
		let selected_coupons = [];
		$( 'input[name="wbte_sc_bogo_listing_check_ind"]:checked' ).each(
			function () {
				selected_coupons.push( $( this ).closest( 'tr' ).attr( 'data-coupon_id' ) );
			}
		);
		return selected_coupons;
	}

	/** Returns bogo coupon counts on ajax success. */
	function wbte_sc_trash_bogo_count() {
		const deferred = $.Deferred();

		$.ajax(
			{
				type: "POST",
				dataType: 'json',
				url: wbte_sc_bogo_params.ajaxurl,
				data: {
					'action': 'wbte_sc_trash_bogo_count_ajax'
				},
				success: function (data) {
					deferred.resolve( data.count );
				},
				error: function () {
					deferred.reject();
				}
			}
		);
		return deferred.promise();
	}

	function wbte_sc_bogo_search_listing(){
		const inputValue = $( '#wbte_bogo_search' ).val();
		const currentUrl = window.location.href;
		const url        = new URL( currentUrl );

		if (inputValue.trim() === "") {
			url.searchParams.delete( 'search' );
		} else {
			url.searchParams.set( 'search', inputValue );
		}

		window.location.href = url.toString();
	}

	function wbte_sc_bogo_realtime_validation(){
		const err_fields = wbte_sc_bogo_get_error_fields();

		for (const [key, value] of Object.entries( err_fields )) {

			const continueArr = ['wbte_sc_bogo_emails', 'wbte_sc_bogo_schedule_content' ];
			if ( continueArr.includes( key ) ) {
				continue;
			}
			/** Only add listeners for fields that are not 'select' elements */
			if ( 'select' !== value.type ) {
				$( '#' + key ).on(
					'input',
					function () {
						wbte_sc_bogo_validate_fields( key, value );
					}
				);
			} else {
				$( '#' + key ).on(
					'change',
					function () {
						wbte_sc_bogo_validate_fields( key, value );
					}
				);
			}
		}

	}

	function wbte_sc_bogo_validate_fields( fieldId, fieldConfig ){
		const val1 = $( '#' + fieldId ).val();
		let val2 = fieldConfig.restriction.constructor === Array ? fieldConfig.restriction[0] : fieldConfig.restriction;

		if ( ! fieldConfig.strict && '' === val1 ) {
			wbte_sc_bogo_remove_validation_msg( fieldId );
		}

		if (  'special' === fieldConfig.condition && 'undefined' !== typeof( fieldConfig.func_name ) ) {
			if ( eval( fieldConfig.func_name + '()' ) ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		}

		/** Handle ID-based restriction */
		if ( 'string' === typeof( val2 ) && val2.startsWith( '#' ) ) {
			val2 = $( val2 ).val();
		}

		/** Remove validation message if value is valid */
		if ( fieldConfig.multiple_condition ) { /** For multiple conditions. */
			let err_flag = true;
			fieldConfig.condition.forEach(
				function ( condition, index ) {
					if ( wbte_sc_bogo_validation_arithmetic( val1, fieldConfig.restriction[index], condition ) ) {
							err_flag = false;
					}
					err_flag = wbte_sc_bogo_validation_arithmetic( val1, fieldConfig.restriction[index], condition ) ? false : true;
				}
			);
			if ( ! err_flag ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		} else {
			if ( wbte_sc_bogo_validation_arithmetic( val1, val2, fieldConfig.condition ) ) {
				wbte_sc_bogo_remove_validation_msg( fieldId );
			}
		}

	}

	function wbte_sc_bogo_remove_validation_msg( id, is_name = false ) {
		const elm       = $( '#' + id );
		const parentElm = elm.closest( 'td' );
		if ( is_name ) {
			elm       = $( 'input[name="' + id + '"]' );
			parentElm = elm.closest( 'tr' );
		}
		if ( ! parentElm.length ) {
			parentElm = elm.closest( 'div' );
		}
		parentElm.find( '.wbte_sc_bogo_edit_error_txt_container' ).remove();

		parentElm.find( '.wbte_sc_bogo_error_border' ).removeClass( 'wbte_sc_bogo_error_border' );
	}

} )( jQuery );

/**
 * 	Email search multi select box
 *
 * 	@since 2.0.0
 */
const wbte_sc_bogo_email_select =
{
	doingSelectAll:false,
	Set:function () {
		this.regMultiSelect();
		this.regPaste();
		this.regKeyPress();
		this.regBtnRemove();
		this.regEditable(); /* editable on double click */
		this.regBlur();
	},
	regMultiSelect:function () {
		jQuery( '.wbte_sc_bogo_email_search' ).each(
			function () {

				/**
				 * 	 Prepare the HTML
				 */
				const parent_elm = jQuery( this ).addClass( 'wbte_sc_bogo_email_select_input_sele' ).wrap( '<div class="wbte_sc_bogo_email_select"></div>' ).parent( '.wbte_sc_bogo_email_select' );
				parent_elm.append( '<div class="wbte_sc_bogo_email_select_inner"></div><input type="text" class="wbte_sc_bogo_email_select_input_txt" id="customer_email">' );

				/**
				 *  Load the values
				 */
				const emails = jQuery( this ).val() || [];

				if (emails.length) { /* default value exists */
					let input_txt_elm = parent_elm.find( '.wbte_sc_bogo_email_select_input_txt' );
					input_txt_elm.val( emails.join( ',' ) );
					jQuery( this ).html( '' ); /* clear it, otherwise below function will not add new values */
					wbte_sc_bogo_email_select.prepareEmailBlocks( input_txt_elm );
				} else {
					/* show the placeholder, if exists */
					const placeholder = jQuery( this ).attr( 'data-placeholder' );

					if (undefined !== typeof placeholder) {
						parent_elm.find( '.wbte_sc_bogo_email_select_input_txt' ).attr( 'placeholder', placeholder );
					}
				}

			}
		);
	},
	regBtnRemove:function () {
		jQuery( document ).on(
			'click',
			'.wbte_sc_bogo_email_select_inner span b',
			function () {
				setTimeout(
					() => {
                    const elm      = jQuery( this );
                    const elm_span = elm.parent( 'span' );
                    elm.remove();
                    const txt = elm_span.text().trim();
                    wbte_sc_bogo_email_select.removeVal( elm_span, txt );
                    elm_span.remove();
					},
					10
				);
			}
		);
	},
	regEditable:function () {
		jQuery( document ).on(
			'dblclick',
			'.wbte_sc_bogo_email_select_inner span',
			function (e) {

				e.stopPropagation();
				wbte_sc_bogo_email_select.makeEditable( jQuery( this ) );

			}
		);
	},
	regPaste:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'input',
			function (e) {

				if ('insertFromPaste' === e.originalEvent.inputType) {
					wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ) );
				}
				const value    = jQuery( this ).val().trim();
				const parentTr = jQuery( this ).closest( 'tr' );
				if ( value && wbte_sc_bogo_email_select.validateEmail( value ) ) {
					parentTr.find( '.wbte_sc_bogo_edit_error_txt_container' ).remove();
				}
			}
		);
	},
	regBlur:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'blur',
			function (e) {
				wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ), true );
			}
		);
	},
	regKeyPress:function () {
		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'focus click',
			function (e) {
				wbte_sc_bogo_email_select.removeFocus( jQuery( this ) );
			}
		);

		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'keyup',
			function (e) {

				if (' ' === e.key || ',' === e.key || 'Enter' === e.key) {
					wbte_sc_bogo_email_select.prepareEmailBlocks( jQuery( this ) );

				} else if (('Backspace' === e.key || 'Delete' === e.key) && "" === jQuery( this ).val().trim()) {
					if (wbte_sc_bogo_email_select.isAllSelected( jQuery( this ) )) {
						jQuery( this ).parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span b' ).trigger( 'click' );
					} else {
						if ('Backspace' === e.key) { /* only for backspace */
							let span_elm = jQuery( this ).parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' );

							if (span_elm.length) {
								if (span_elm.last().hasClass( 'focused' )) {
									wbte_sc_bogo_email_select.makeEditable( span_elm.last() );
									wbte_sc_bogo_email_select.removeFocus( jQuery( this ) ); /* maybe in select all state */
								} else {
									span_elm.last().addClass( 'focused' ).trigger( 'focus' );
								}
							}
						}
					}

				} else {
					if ( ! wbte_sc_bogo_email_select.doingSelectAll) {
						wbte_sc_bogo_email_select.removeFocus( jQuery( this ) );
					}
				}

			}
		);

		jQuery( '.wbte_sc_bogo_email_select_input_txt' ).on(
			'keydown',
			function (e) {

				if ('Enter' === e.key) {
					return false;
				}

				if ("" === jQuery( this ).val().trim() && (e.ctrlKey || e.metaKey) && 'a' === e.key.toLowerCase()) {
					wbte_sc_bogo_email_select.doingSelectAll = true;
					wbte_sc_bogo_email_select.addFocus( jQuery( this ) );

				} else {
					wbte_sc_bogo_email_select.doingSelectAll = false;
				}
			}
		);
	},
	addFocus:function (elm) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' ).addClass( 'focused' );
	},
	removeFocus:function (elm) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' ).removeClass( 'focused' );
	},
	isAllSelected:function (elm) {
		let span_elm = elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner span' );
		return span_elm.length > 1 && ! span_elm.not( '.focused' ).length; /* first condition is greater than 1 because to avoid if there is only one single item */
	},
	makeEditable:function (elm) {
		/**
		 * 	Take the email address
		 */
		let temp_elm = jQuery( '<div>' ).html( elm.html() );
		temp_elm.find( 'b' ).remove();
		let email = temp_elm.text().trim();

		/**
		 * Add the email as input text value
		 */
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_txt' ).val( email );

		elm.find( 'b' ).trigger( 'click' ); /* remove the email block */

	},
	getExistingVal:function (elm) {
		return elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele' ).val();
	},
	setVal:function (elm, vals) {
		let sele_option_html = '';

		jQuery.each(
			vals,
			function (index, email) {
				sele_option_html += '<option value="' + email + '" selected="selected">' + email + '</option>';
			}
		);

		elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele' ).html( sele_option_html );

	},
	removeVal:function (elm, val) {
		elm.parents( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_input_sele option[value="' + val + '"]' ).remove();
	},
	prepareEmailBlocks:function (elm, valid_only) {
		let txt = elm.val().trim();

		if ("" === txt) {
			return;
		}

		let emails             = txt.split( /[\s,]+/ );
		let email_block_elm    = elm.parent( '.wbte_sc_bogo_email_select' ).find( '.wbte_sc_bogo_email_select_inner' );
		let email_html         = email_block_elm.html();
		let existing_val       = wbte_sc_bogo_email_select.getExistingVal( elm ) || [];
		let valid_emails_found = false; /* applicable only when `valid only` enabled */

		if (email_block_elm.find( 'span.focused' ).length) {
			email_block_elm.find( 'span.focused' ).removeClass( 'focused' );
		}

		jQuery.each(
			emails,
			function (index, email) {

				if ("" !== email.trim() && -1 === jQuery.inArray( email, existing_val )) {
					if ( ! valid_only) {
						let class_attr = ! wbte_sc_bogo_email_select.validateEmail( email ) ? ' class="invalid"' : '';
						email_html    += '<span' + class_attr + '>' + email + ' <b>x</b></span>';
						existing_val.push( email );
					} else {
						if ( wbte_sc_bogo_email_select.validateEmail( email ) ) { /* valid only */
							email_html += '<span>' + email + ' <b>x</b></span>';
							existing_val.push( email );
							valid_emails_found = true;
						}
					}
				}

			}
		);

		email_block_elm.html( email_html );

		if ( ! valid_only || valid_emails_found) {
			elm.val( '' );
		}

		this.setVal( elm, existing_val );
	},
	validateEmail:function (email) {
		const mailformat = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
		return email.toLowerCase().match( mailformat );
	}
}

const wbte_sc_bogo_data_processing = (function($) {

	return function(e){
		const $form = $(e.target);
		const clicked_button = $( e.originalEvent.submitter ).attr( 'data-btn-id' );
		let allowed_emails   = [];

		$( 'input[type="text"]' ).each(
			function () {
				if ( $( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
						$( this ).val( '' ); /** Make text field value empty if field is in hidden state */
				}
			}
		);

		$( 'input[type="checkbox"]' ).each(
			function () {
				if ( $( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
						$( this ).prop( 'checked', false );
				}
			}
		);

		$( 'select' ).each(
			function () {
				if ( $( this ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
						$( this ).val( null ).trigger( 'change' ); /** Clear selected values if hidden */
				}
			}
		);

		if ( ! $( '#wbte_sc_bogo_schedule' ).is( ":checked" ) ) {
			$( '#_wt_coupon_start_date, #expiry_date' ).val( '' );
		}
		let fieldValues = {};
		$form.find( ":input" ).each(
			function () {
				const input = $( this );
				const name  = input.attr( 'name' );
				const value = input.val();

				if (input.is( ':radio' )) {
					/** Only store the value if the radio button is checked */
					if (input.is( ':checked' )) {
						fieldValues[name] = value;
					}
				} else if (input.is( ':checkbox' )) {
					/** Only store the value if the checkbox is checked */
					if (input.is( ':checked' )) {
						if ( ! (name in fieldValues)) {
							fieldValues[name] = [];
						}
						fieldValues[name].push( value );
					}
				} else {
					/** For other input types, store the value if it's not empty or if the field hasn't been seen before */
					if (value !== '' || ! (name in fieldValues)) {
						fieldValues[name] = value;
					}
				}
			}
		);
		if ( ! wbte_sc_bogo_form_submit_validation() ) {
			wbte_sc_bogo_remove_overlay();
			return;
		}
		wbte_sc_bogo_remove_all_validation_msg(); /** If here means validation passed. So remove all validation messages if any. */

		let data = $.param( fieldValues );

		/** Add allowed emails to data */
		let emails = $( '[name="wbte_sc_bogo_emails[]"]' ).val();
		$.each(
			emails,
			function ( index, email ) {
				if ( wbte_sc_bogo_email_select.validateEmail( email ) ) {
					allowed_emails.push( email );
				}
			}
		);
		$( '.wbte_sc_bogo_email_select_inner span.invalid' ).remove();

		data += '&customer_email=' + allowed_emails + '&clicked_button=' + clicked_button;

		return data;
	}
})(jQuery);

const wbte_sc_bogo_form_submit_validation = (function($) {
	return function() {
		const err_fields = wbte_sc_bogo_get_error_fields();

		for ( const [key, value] of Object.entries( err_fields ) ) {

			if ( ! $( '#' + key ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {

				const val1       = $( '#' + key ).val();
				let val2       = value.restriction.constructor === Array ? value.restriction[0] : value.restriction;
				const isSelect = value.type && 'select' === value.type;
				let parentLoc  = typeof( value.parent_loc ) !== undefined ? value.parent_loc : 'td';

				if ( isSelect && $( '#' + key ).closest( 'div' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
					continue;
				}

				if ( 'special' === value.condition && typeof( value.func_name ) !== 'undefined' ) {
					const specialFields = ['wbte_sc_bogo_emails' ];
					if ( specialFields.includes( key ) ) {
						if ( ! eval( value.func_name + '()' ) ) {
							return false;
						}
					} else if ( ! eval( value.func_name + '()' ) ) {
						wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg ], isSelect, parentLoc );
						return false;
					}
					continue;
				}

				if ( '' === val1 ) {
					if ( value.strict ) {
						wbte_sc_bogo_show_validation_msg(
							key,
							value.err_msg.constructor === Array ? wbte_sc_bogo_params.err_msgs[ value.err_msg[0] ] : wbte_sc_bogo_params.err_msgs[ value.err_msg ],
							isSelect,
							parentLoc
						);
						return false;
					} else {
						continue;
					}
				}

				if ( ( 'string' === typeof( val2 ) && val2.startsWith( '#' ) ) ) { /** ID given. */
					val2 = $( val2 ).val();
				}

				if ( value.multiple_condition ) {
					let err_flag = false;
					value.condition.forEach(
						function ( condition, index ) {
							if ( ! wbte_sc_bogo_validation_arithmetic( val1, value.restriction[index], condition ) ) {
									wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg[index] ], isSelect, parentLoc );
									err_flag = true;
									return false;
							}
						}
					);
					if ( err_flag ) {
						return false;
					}
				} else {
					if ( ! wbte_sc_bogo_validation_arithmetic( val1, val2, value.condition ) ) {
						wbte_sc_bogo_show_validation_msg( key, wbte_sc_bogo_params.err_msgs[ value.err_msg ], isSelect, parentLoc );
						return false;
					}
				}
			}
		}

		return true;
	}
})(jQuery);

const wbte_sc_bogo_get_error_fields = (function($) {
	return function() {
		let untilOptl = {
			/** Step 1. */
			'wbte_sc_bogo_free_product_ids' : {
				'err_msg'	  : 'atleast_1_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_customer_gets_qty' : {
				'err_msg'	  : 'gre_equal_1',
				'condition'	  : '>=',
				'restriction' : 1,
				'strict'	  : true,
			},
			'wbte_sc_bogo_customer_gets_discount_perc' : {
				'err_msg'	  		 : [ 'gre_0', 'perc_less_eq_100' ],
				'restriction' 		 : [ 0, 100 ],
				'condition'	  		 : [ '>', '<=' ],
				'strict'	  		 : true,
				'multiple_condition' : true
			},
			'wbte_sc_bogo_customer_gets_discount_price' : {
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			/** Step 2 */
			'_wbte_sc_bogo_min_amount' : {
				'err_msg'	  : 'gre_0',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_amount' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_amount',
				'condition'	  : '>='
			},
			'_wbte_sc_bogo_min_qty' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_qty' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_qty',
				'condition'	  : '>=',
				'parent_loc'  : 'td'
			},
			/** Step 2 prod/cat fields. */
			'wbte_sc_bogo_specific_products' : {
				'err_msg'	  : 'atleast_1_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
			'wbte_sc_bogo_excluded_products' :{
				'err_msg'	  : 'atleast_1_ex_prod',
				'restriction' : 0,
				'condition'	  : '>',
				'type'		  : 'select',
				'strict'	  : true
			},
		}

		const optlCondIdList = [ '_wbte_sc_bogo_min_qty_add', '_wbte_sc_bogo_max_qty_add', '_wbte_sc_min_qty_each', '_wbte_sc_max_qty_each', 'wbte_sc_bogo_emails', 'usage_limit', 'usage_limit_per_user' ];

		let optlCondition =  {
			/** Step 2 additional fields. */
			'_wbte_sc_bogo_min_qty_add' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_bogo_max_qty_add' : {
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_bogo_min_qty_add',
				'condition'	  : '>='
			},
			'_wbte_sc_min_qty_each' :{
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 0,
				'condition'	  : '>',
				'strict'	  : true
			},
			'_wbte_sc_max_qty_each' :{
				'err_msg'	  : 'gre_min',
				'restriction' : '#_wbte_sc_min_qty_each',
				'condition'	  : '>='
			},
			'usage_limit' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'usage_limit_per_user' : {
				'err_msg'	  : 'gre_equal_1',
				'restriction' : 1,
				'condition'	  : '>=',
				'strict'	  : true
			},
			'wbte_sc_bogo_emails' : {
				'err_msg'	  : 'email_error',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_email_validation',
			},
			
		}

		let sortedOptlCondIdList = [];
		let newOptlCondIdList = {};

		$( '.wbte_sc_bogo_additional_fields_contents tr' ).each( function () {
			$( this ).find('[name]').each(function() {
				const $trName = $(this).attr('name').replace(/\[\]$/, '');
				if( optlCondIdList.includes( $trName ) ){
					sortedOptlCondIdList.push( $trName );
				}
			});
		} );

		sortedOptlCondIdList.forEach( function ( id ) {
			newOptlCondIdList[ id ] = optlCondition[ id ];
		} );

		let afterOptl = {
			/** General settings. */
			'wbte_sc_bogo_coupon_name' : {
				'err_msg'	  : 'no_camp_title',
				'restriction' : '',
				'condition'	  : '!=',
				'strict'	  : true,
				'parent_loc'  : 'div'
			},
			'wbte_sc_bogo_coupon_code' : {
				'err_msg'	  : 'coupon_code_error',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_coupon_code_validation',
				'strict'	  : true,
				'parent_loc'  : 'div'
			},
			'wbte_sc_bogo_schedule_content' : {
				'err_msg'	  : 'empty_schedule',
				'restriction' : '',
				'condition'	  : 'special',
				'func_name'	  : 'wbte_sc_bogo_schedule_empty_check',
				'strict'	  : true,
				'parent_loc'  : 'div',
				'type'		  : 'select',
			},
		}

		return $.extend( untilOptl, newOptlCondIdList, afterOptl );
	}
})(jQuery);

function wbte_sc_bogo_validation_arithmetic( val1, val2, operator ){

	val1 = parseFloat( val1 );
	val2 = parseFloat( val2 );

	switch ( operator ) {
		case '>':
			return val1 > val2;
		case '>=':
			return val1 >= val2;
		case '<':
			return val1 < val2;
		case '<=':
			return val1 <= val2;
		case '==':
			return val1 == val2;
		case '===':
			return val1 === val2;
		case '!=':
			return val1 != val2;
		default:
			return false;
	}
}

/** Function call is done by dynamically, dont remove it. */
const wbte_sc_bogo_email_validation = ( function($){
	return function () { 
		if ( ! $( '#customer_email' ).closest( 'tr' ).hasClass( 'wbte_sc_bogo_conditional_hidden' ) ) {
			let emails             = jQuery( '[name="wbte_sc_bogo_emails[]"]' ).val();
			let valid_emails_count = 0;
	
			jQuery.each(
				emails,
				function ( index, email ) {
					if ( wbte_sc_bogo_email_select.validateEmail( email ) ) {
						valid_emails_count++;
					}
				}
			);
	
			if ( 0 === valid_emails_count ) {
				const elm       = $( '#customer_email' );
				const parentElm = elm.closest( 'td' );
				const err_icon  = '<img style="vertical-align:middle; width:16px; display:inline-block;" src="' + wbte_sc_bogo_params.urls.image_path + 'exclamation_red.svg">';
				if ( 0 === parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).length ) {
					parentElm.append( `<span class="wbte_sc_bogo_edit_error_txt_container"><br><span class="wbte_sc_bogo_edit_error_txt">${ err_icon }&nbsp;${ wbte_sc_bogo_params.err_msgs.email_error }</span></span>` );
				}
				elm.parent( '.wbte_sc_bogo_email_field' ).addClass( 'wbte_sc_bogo_error_border' );
				const stepContainer = elm.closest( '.wbte_sc_bogo_edit_step' );
				if ( ! stepContainer.hasClass( 'wbte_sc_bogo_step_container_opened' )) {
					stepContainer.trigger( 'click' );
				}
				$( '.wbte_sc_bogo_email_select' )[0].scrollIntoView( { behavior: 'smooth', block: 'center' } );
				$( '.wbte_sc_bogo_email_select' ).show().trigger( 'focus' );
				setTimeout(
					function () {
						const offset = $( '.wbte_sc_bogo_email_select' ).offset().top - ($( window ).height() / 2);
						$( 'html, body' ).animate( { scrollTop: offset }, 500 );
					},
					100
				);
	
				return false;
			}
		}
		return true;
	}
} )(jQuery);

const wbte_sc_bogo_coupon_code_validation = ( function($){
	return function(){

		/** If auto coupon code is selected then no need to validate. */
		if ( 'wbte_sc_bogo_code_auto' === $( 'input[ name="wbte_sc_bogo_code_condition" ]:checked' ).val() ) {
			return true;
		}

		/** Allow letters, numbers, spaces, underscore, and hyphen in input. */
		const validPattern = /^[a-zA-Z0-9_\-\s]+$/;
		const inputVal 	 = $( '#wbte_sc_bogo_coupon_code' ).val();

		return validPattern.test( inputVal );
	}
})(jQuery);

/** Function call is done by dynamically, dont remove it. */
const wbte_sc_bogo_schedule_empty_check = ( function($){
	return function(){
		if (
			$( '#wbte_sc_bogo_schedule' ).is( ':checked' )
			&& '' === $( '#_wt_coupon_start_date' ).val()
		&& '' === $( '#expiry_date' ).val()
		) {
			return false;
		} else {
			return true;
		}
	}
})(jQuery);

function wbte_sc_bogo_remove_all_validation_msg(){
	jQuery( '.wbte_sc_bogo_edit_error_txt_container' ).remove();
	jQuery( '.wbte_sc_bogo_error_border' ).removeClass( 'wbte_sc_bogo_error_border' );
}

const wbte_sc_bogo_show_validation_msg = ( function($){
	return function( id, msg, is_select, parentLoc = 'td' ){
		let elm        = $( '#' + id );
		const parentElm  = elm.closest( parentLoc );
		let breakFront = '';
		let breakEnd   = '<br>';
		const err_icon   = '<img style="vertical-align:middle; width:16px; display:inline-block;" src="' + wbte_sc_bogo_params.urls.image_path + 'exclamation_red.svg">';
	
		/** Handle 'select2' elements */
		if ( is_select ) {
			if ( 0 < elm.closest( 'div' ).find( 'span.select2-selection' ).length ) {
				elm = elm.closest( 'div' ).find( 'span.select2-selection' );
			}
			breakFront = '<br>';
			breakEnd   = '';
		}
	
		/** Add error text if not already present */
		if ( 0 === parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).length ) {
			parentElm.append( `<span class="wbte_sc_bogo_edit_error_txt_container">${breakFront}<span class="wbte_sc_bogo_edit_error_txt">${breakEnd}${msg}</span></span>` );
		}
	
		/** Handle input fields with icons */
		if ( elm.closest( 'div' ).hasClass( 'wbte_sc_bogo_icon_input' ) ) {
			elm = elm.closest( 'div' );
		}
	
		/** Append error icon */
		if ( parentElm.find( 'img[src$="exclamation_red.svg"]' ).length === 0 ) {
			if ( ! is_select) {
				elm.after( `<span class="wbte_sc_bogo_edit_error_txt_container">&nbsp;${ err_icon }</span>` );
			} else {
				parentElm.find( '.wbte_sc_bogo_edit_error_txt' ).prepend( err_icon + '&nbsp;' );
			}
		}
	
		/** Add error class to input */
		elm.addClass( 'wbte_sc_bogo_error_border' );
	
		/** Open step container if it's not already opened */
		const stepContainer = elm.closest( '.wbte_sc_bogo_edit_step' );
		if( 0 < stepContainer.length ){ /** For BOGO single coupon */
			if ( ! stepContainer.hasClass( 'wbte_sc_bogo_step_container_opened' )) {
				stepContainer.trigger( 'click' );
			}
		}else{ /** For BOGO bulk coupon */
			stepContainer = elm.closest( '.wbte_sc_admin_vrtl_nav_content_section' );
			if ( ! stepContainer.hasClass( 'active' ) ) {
				const section = stepContainer.data( 'section' );
				$( `.wbte_sc_admin_vrtl_nav_item[data-section="${section}"]` ).trigger( 'click' );
			}
		}
	
		/** Trigger focus on the input or select field */
		const focusElem = elm.hasClass( 'wbte_sc_bogo_icon_input' ) ? elm.find( 'input' ) : elm;
		focusElem.trigger( 'focus' );
		setTimeout(
			() => {
			focusElem[0].scrollIntoView( { behavior: 'smooth', block: 'center' } );
			},
			10
		);
	}
 } )(jQuery)

function wbte_sc_bogo_remove_overlay(){
	jQuery( '.wbte_sc_blanket' ).hide();
	jQuery( 'html, body' ).css( { overflow: 'auto', height: 'auto' } );
}