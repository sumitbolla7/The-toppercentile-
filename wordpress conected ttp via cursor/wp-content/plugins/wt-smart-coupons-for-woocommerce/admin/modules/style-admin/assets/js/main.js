/**
 *  Javascript section of coupon styling
 * 	@since 1.4.7
 */	
(function( $ ) {
	//'use strict';
	$(function() { 
		
		/* initiate color picker */
		$('.wt_sc_coupon_colors .wt_sc_color_picker').wpColorPicker({
			'change':function(event, ui) { 
				const selected_color = ui.color.toString();			
				
				const target_elm=$(event.target);
					target_elm.val(selected_color);

				const coupon_type=target_elm.attr('data-coupon_type');
				
				wt_sc_update_color(coupon_type);
			 }
		});

		/* update color on page load */
		$('.wt_sc_coupon_colors').each(function(){
			const coupon_type=$(this).attr('data-coupon_type');
			wt_sc_update_color(coupon_type);
		});

		/** change the popup */
		$('.wt_sc_coupon_change_theme_link').on('click', function(){
			const coupon_type=$(this).attr('data-coupon_type');
			const style_key=$(this).parents('.wt_sc_sub_tab_content').find('.wt_sc_selected_coupon_style_input').val();
			$('.wt_sc_coupon_templates .wt_sc_single_template_box[data-style_key="'+style_key+'"]').css({'box-shadow':'0px 0px 5px rgb(81, 159, 242)'});
			$('.wt_sc_coupon_templates .wt_sc_single_template_box').attr('data-coupon_type', coupon_type);
			wt_sc_popup.showPopup($('.wt_sc_coupon_templates'));
		});

		/* Choose template from preset */
		$('.wt_sc_coupon_templates .wt_sc_single_template_box').on('click', function(){
			
			const coupon_type = $(this).attr('data-coupon_type');	
			const hasPalettes = true === $(this).data('has-palettes') ? true : false;

			let template_box_inner = $(this).find('.wt_sc_single_template_box_inner').html();
			let random_id = Math.random().toString(36).substring(2);
			template_box_inner = template_box_inner.replace(/wbte_sc_svg_random_id-[a-z0-9]+-end/g, 'wbte_sc_svg_random_id-' + random_id + '-end');
			
			$('.wt_sc_coupon_preview[data-coupon_type="'+coupon_type+'"]').html(template_box_inner);

			if( 'expired_coupon' === coupon_type ) {
				const $preview = $('.wt_sc_coupon_preview[data-coupon_type="'+coupon_type+'"]');
				$preview.find('.wbte_sc_coupon_layout_expired_text, .wbte_sc_coupon_layout_expired_overlay').css('visibility', 'visible');
				$preview.find('.wt_sc_coupon_expiry, .wbte_sc_coupon_layout_expiry_ctm').hide();
			}

			/* update current style */
			const style_key = $(this).attr('data-style_key');
			$('input[name="wt_coupon_styles['+coupon_type+'][style]"]').val(style_key);

			const color_config = $('.wt_sc_coupon_preview[data-coupon_type="'+coupon_type+'"] .wt_sc_template_refer').attr('data-color-config');
			const color_config_arr = color_config.split('|');

			const color_form_elm = $('.wt_sc_coupon_colors[data-coupon_type="'+coupon_type+'"] .wt_sc_coupon_color_form_element');
			color_form_elm.addClass('hide');
			
			/* update color pickers */
			for(let ee = 0; ee < color_config_arr.length; ee++)
			{
				color_form_elm.eq(ee).removeClass('hide');
				color_form_elm.eq(ee).find('input.wt_sc_color_picker').val(color_config_arr[ee]).attr('data-style_type', style_key).iris('color', color_config_arr[ee]);
			}

			/** Toggle color picker visibility based on whether template has palettes */
			const colorPicker = $('.wt_sc_coupon_colors[data-coupon_type="'+coupon_type+'"]');
			if(hasPalettes) {
				colorPicker.addClass('closed');
			} else {
				colorPicker.removeClass('closed');
			}

			wbte_sc_update_palette_visibility( $(this) );

			wt_sc_popup.hidePopup();
			$('.wt_sc_coupon_templates .wt_sc_single_template_box').css({'box-shadow':'none'});
		});


		/** submit form */
		$('.wt_sc_coupon_style_form').on('submit', function(e){
			e.preventDefault();
			
			const data=$(this).serialize();

			const submit_btn=$(this).find('input[type="submit"]');
			const spinner=submit_btn.siblings('.spinner');
			spinner.css({'visibility':'visible'});
			submit_btn.css({'opacity':'.5','cursor':'default'}).prop('disabled',true);

			$.ajax({
				url:wt_sc_customizer_params.ajax_url,
				type:'POST',
				dataType:'json',
				data:data,
				success:function(data)
				{
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
					if(data.status)
					{
						wbte_sc_notify_msg.success(data.msg);
					}else
					{
						wbte_sc_notify_msg.error(data.msg);
					}
				},
				error:function () 
				{
					spinner.css({'visibility':'hidden'});
					submit_btn.css({'opacity':'1','cursor':'pointer'}).prop('disabled',false);
					wbte_sc_notify_msg.error(wt_sc_customizer_params.msgs.settings_error, false);
				}

			});
		});

		$('.wt_sc_coupon_colors').on('click', function(e) {
			if($(e.target).is('.wt_sc_coupon_colors, .wbte_sc_coupon_clr_picker_header, .wbte_sc_coupon_clr_picker_header_txt, .wbte_sc_coupon_clr_picker_header_arr')) {
				$(this).toggleClass('closed');
				$(this).closest( '.wt_sc_color_container' ).find( '.wt_sc_palette_preview' ).removeClass( 'selected' );
			}
		});

		function wbte_sc_update_palette_visibility(templateBox) {
			const hasPalettes = true === templateBox.data('has-palettes') ? true : false;
			const palettes = templateBox.data('palettes');
			const couponType = templateBox.attr('data-coupon_type');
			const paletteContainer = $(`.wt_sc_sub_tab_content[data-id="${couponType}"] .wt_sc_palette_options`);
			
			if ( hasPalettes && palettes ) {
				const newPaletteHtml = `
					<div class="wt_sc_palette_options">
						${palettes.map(palette => `
							<div class="wt_sc_palette_preview" data-palette='${JSON.stringify(palette)}'>
								${palette.map(color => `
									<span class="wt_sc_color_swatch" style="background-color: ${color}"></span>
								`).join('')}
							</div>
						`).join('')}
					</div>
				`;
				
				paletteContainer.remove();
				$(`.wt_sc_sub_tab_content[data-id="${couponType}"] .wt_sc_color_container`).prepend(newPaletteHtml);
				$(`.wt_sc_sub_tab_content[data-id="${couponType}"] .wt_sc_palette_preview`).first().addClass('selected');
			} else {
				paletteContainer.remove();	
			}
		}

		function wt_sc_update_color(coupon_type)
		{
			const preview_elm = $('.wt_sc_coupon_preview[data-coupon_type="'+coupon_type+'"]');
			let reference_elm_html = preview_elm.find('.wt_sc_template_refer').html();
			const color_picker_elm = $('.wt_sc_coupon_colors[data-coupon_type="'+coupon_type+'"] .wt_sc_color_picker');
			
			color_picker_elm.each(function(index){
						
				let find_str = '[wt_sc_color_'+index+']';
				find_str = find_str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
				const regexp = new RegExp(find_str, 'g');

				const color = $(this).val();
				reference_elm_html = reference_elm_html.replace(regexp, color);
			});

			preview_elm.children('.wt_sc_single_coupon').replaceWith(reference_elm_html);
		}

		/** Handle palette selection */
		$(document).on('click', '.wt_sc_palette_preview', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $this = $(this);
			const palette = $this.data('palette');
			const coupon_type = $this.closest('.wt_sc_sub_tab_content').data('id');
			
			/** Force remove and add class */
			$this.removeClass('selected');
			$this.attr('class', 'wt_sc_palette_preview selected');
			
			/** Update each color picker with palette colors */
			palette.forEach(function(color, index) {
				const colorPicker = $('.wt_sc_coupon_colors[data-coupon_type="'+coupon_type+'"] .wt_sc_color_picker').eq(index);
				colorPicker.val(color).trigger('change');
			});
			
			wt_sc_update_color(coupon_type);
			$('.wt_sc_coupon_colors[data-coupon_type="'+coupon_type+'"]').addClass('closed');
			
			if (!$this.hasClass('selected')) {
				$this.addClass('selected');
			}
		});

		/** Remove selected state when custom colors are used */
		$('.wt_sc_color_picker').on('change', function() {
			const coupon_type = $(this).data('coupon_type');
			$('.wt_sc_sub_tab_content[data-id="'+coupon_type+'"] .wt_sc_palette_preview').removeClass('selected');
		});

		/** Initialize palettes on page load */
		$('.wt_sc_sub_tab_content').each(function() {
			const $content = $(this);
			const templateBox = $('.wt_sc_single_template_box[data-style_key="' + $content.find('.wt_sc_selected_coupon_style_input').val() + '"]');
			
			if(templateBox.length) {
				wbte_sc_update_palette_visibility(templateBox);
				
				/** Get current colors from color pickers */
				const currentColors = [];
				$content.find('.wt_sc_color_picker').each(function() {
					currentColors.push($(this).val());
				});
				
				// Find and select matching palette
				if(templateBox.data('has-palettes')) {
					const palettes = templateBox.data('palettes');
					const matchingPalette = palettes.find(palette => 
						JSON.stringify(palette).toLowerCase() === JSON.stringify(currentColors).toLowerCase()
					);
					
					if(matchingPalette) {
						$content.find('.wt_sc_palette_preview').each(function() {
							if(JSON.stringify($(this).data('palette')) === JSON.stringify(matchingPalette)) {
								$(this).addClass('selected');
								$content.find('.wt_sc_coupon_colors').addClass('closed');
							}
						});
					}
				} else {
					// If template doesn't have palettes, ensure color picker is open
					$content.find('.wt_sc_coupon_colors').removeClass('closed');
				}
			}
		});

	});
})( jQuery );