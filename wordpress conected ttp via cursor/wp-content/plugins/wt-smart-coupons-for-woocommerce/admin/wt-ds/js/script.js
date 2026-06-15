var wbte_sc_ds = {
	getAsset:function( args ) {
		let params = wbte_sc_ds_js_params;
		let type = args.hasOwnProperty('type') ? args['type'] : '';
		let name = args.hasOwnProperty('name') ? args['name'] : '';

		if ('icon' === type ) {
			return params.icon_base_url + name + '.svg';
		} else if('image' === type) {
			return params.img_base_url + name;
		}
		return '';
	},
	getIconSvg: async function( icon ) {
		return await fetch( wbte_sc_ds_js_params.icon_base_url + icon + '.svg')
		.then(response => response.text())
		.then(svgData => {
			return this.sanitizeSVG(svgData);
		});
	},
	sanitizeSVG:function( svgData ) {
			
		/* Parse the SVG string into an XML Document. */
		const parser = new DOMParser();
		const svgDoc = parser.parseFromString( svgData, 'image/svg+xml' );
	
		/* Start sanitizing from the root <svg> element. */
		const svgRoot = svgDoc.documentElement;
		this.sanitizeNode( svgRoot );
	
		/* Return sanitized SVG as a string */
		const serializer = new XMLSerializer();
		return serializer.serializeToString( svgDoc );
	},
	sanitizeNode:function( node ) {
		let allowedTags= ['svg', 'g', 'path', 'rect', 'circle', 'line', 'polyline', 'polygon', 'text', 'use', 'defs', 'symbol', 'title'];
		let allowedAttrs= ['x', 'y', 'viewBox', 'fill', 'stroke', 'd', 'class', 'id', 'width', 'height', 'cx', 'cy', 'r', 'rx', 'ry', 'xlink:href', 'style', 'transform'];
		
		/* Remove any tag that is not allowed. */
		if ( ! allowedTags.includes( node.tagName ) ) {
			node.remove();
			return;
		}

		/* Loop through attributes and remove those not allowed. */
		for (let i = node.attributes.length - 1; i >= 0; i--) {
			const attr = node.attributes[i].name;
			if ( ! allowedAttrs.includes( attr ) ) {
				node.removeAttribute( attr );
			}
		}

		/* Recursively sanitize child nodes. */
		Array.from(node.children).forEach(wbte_sc_ds.sanitizeNode);
	}
}

var wbte_sc_checkboxes = {
    Set:function(){
        jQuery(document).ready(function(){
            wbte_sc_checkboxes.set_first_state();
            wbte_sc_checkboxes.reg_click();
        });
    },
    set_first_state:function(){
        jQuery('.wbte_sc_checkbox-master').each(function(){
            var group_id = jQuery(this).attr('data-checkbox-group-id');
            wbte_sc_checkboxes.toggle_master_check( group_id );
        });
    },
    reg_click:function(){
        jQuery(document).on('click', '.wbte_sc_checkbox input[type="checkbox"]', function() {
            
            let parent_elm = jQuery(this).parents('.wbte_sc_checkbox');
            let group_id = parent_elm.attr('data-checkbox-group-id');

            if(parent_elm.hasClass('wbte_sc_checkbox-master')){
                if(jQuery(this).is(':checked')){
                    jQuery('.wbte_sc_checkbox[data-checkbox-group-id="'+group_id+'"] input[type="checkbox"]').prop('checked', true).attr('aria-checked', 'true');
                }else{
                    jQuery('.wbte_sc_checkbox[data-checkbox-group-id="'+group_id+'"] input[type="checkbox"]').prop('checked', false).attr('aria-checked', 'false');
                }
            }
            
            wbte_sc_checkboxes.toggle_master_check( group_id );
        });
    },
    toggle_master_check:function( group_id ) {
        let normal_checkboxes = jQuery('.wbte_sc_checkbox-normal[data-checkbox-group-id="'+group_id+'"]');
        let master_checkbox = jQuery('.wbte_sc_checkbox-master[data-checkbox-group-id="'+group_id+'"]');
        let checked = normal_checkboxes.find('input[type="checkbox"]:checked').length;
        let total = normal_checkboxes.find('input[type="checkbox"]').length;
        
        master_checkbox.find('label .checkbox-indicator').hide();
        
        if(checked > 0) {
            master_checkbox.find('input[type="checkbox"]').prop('checked', true).attr('aria-checked', 'true');                  
            
            if ( checked === total ) {   
                master_checkbox.find('label .checkbox-indicator.checked').show();
            }else{
                master_checkbox.find('label .checkbox-indicator.partially-checked').show();
            }
        }else{
            master_checkbox.find('input[type="checkbox"]').prop('checked', false).attr('aria-checked', 'false');
            master_checkbox.find('label .checkbox-indicator.not-checked').show();
        }

        /* Set checked labels */
        if(master_checkbox.find('.total').length){
            master_checkbox.find('.total').text(total);
        }
        if(master_checkbox.find('.selected').length){
            master_checkbox.find('.selected').text(checked);
        }
    }
}

wbte_sc_checkboxes.Set();

/**
 *  Help widget
 */
var wbte_sc_help_widget={
	Set:function() {
		jQuery(document).on('click', function (e) {
			var widget = jQuery('.wbte_sc_help-widget');
			var checkbox = jQuery('#wbte_sc_help-widget_hidden_checkbox');

			if (checkbox.is(':checked') && !widget.has(e.target).length && !widget.is(e.target)) {
			  checkbox.prop('checked', false);
			}
		});
	}
};

wbte_sc_help_widget.Set();

/**
 *  Popup creator
 */
var wbte_sc_popup={
	Set:function() {	
		jQuery(function() {
			if( jQuery('.wbte_sc_blanket').length ) {
				jQuery('.wbte_sc_blanket').prependTo('body');
			} else {	
				jQuery('body').prepend('<div class="wbte_sc_blanket"></div>');
			}

			wbte_sc_popup.regPopupOpen();
			wbte_sc_popup.regPopupClose();
		});
	},
	regPopupOpen:function() {
		jQuery(document).on('click', '[data-wbte_sc_popup]', function(){
			var elm_id=jQuery(this).attr('data-wbte_sc_popup');
			var elm=jQuery('[data-id="'+elm_id+'"]');
			if ( elm.length ) {

				/* Trigger a custom event after the popup trigger was clicked. */
				let popup_trigger_clicked = jQuery.Event('wbte_sc_popup_trigger_clicked', {
			        detail: {
			          trigger_element: jQuery(this),
			          target_element: elm,
			          target_id: elm_id,
			        },
			    });
			    jQuery(document).trigger(popup_trigger_clicked);

				wbte_sc_popup.showPopup(elm);
			}
		});
	},
	showPopup:function( popup_elm ) {
		popup_elm.show();
        if ( popup_elm.attr('data-overlay') === '1' ) {
		    jQuery('.wbte_sc_blanket').show();
        }
	},
	hidePopup:function() {
		jQuery('.wbte_sc_popup-close').trigger('click');
	},
	regPopupClose:function( popup_elm ) {
		jQuery(document).on('keyup', function( e ) {
			if ( 'Escape' === e.key ) {
				wbte_sc_popup.hidePopup();
			}
		});
		jQuery(document).on('click', '.wbte_sc_popup-close, .wbte_sc_popup-cancel, .wbte_sc_blanket', function(){
			jQuery('.wbte_sc_blanket, .wbte_sc_popup').hide();
		});
	}
}

wbte_sc_popup.Set();

var wbte_sc_segments = {
	Set:function(){
		jQuery(document).ready(function() {
			wbte_sc_segments.register_click();
			wbte_sc_segments.load_segment_state();
		});
	},
	register_click:function(){
		/* Create a custom event to trigger after segment was clicked. */
		let segment_clicked_event = jQuery.Event('wbte_sc_segment_clicked', {
	        detail: {
	          element: null,
	          segment_target_id: '',
	          segment_item_target_id: '',
	        },
	    });

		jQuery(document).on('click', '.wbte_sc_segment', function(e){
			let elm = jQuery(this);

			/* Set segment state */
			let segment_parent = elm.parent('.wbte_sc_segments');
			segment_parent.find('.wbte_sc_segment').removeClass('active');
			elm.addClass('active');


			/* Display the content. */
			let segment_target_id = typeof segment_parent.attr('data-target-id') === 'undefined' ? '' : segment_parent.attr('data-target-id');
			if( '' === segment_target_id || ( segment_target_id && ! jQuery( '.wbte_sc_segment_content_main[data-id="' + segment_target_id + '"]' ).length ) ) { /* Segment target element id not found, or element not exists. */
				return;
			}

			let segment_target_elm = jQuery( '.wbte_sc_segment_content_main[data-id="' + segment_target_id + '"]' );
			segment_target_elm.find('.wbte_sc_segment_content').hide(); /* Hide all containers first. */

			let segment_item_target_id = typeof elm.attr('data-target-id') === 'undefined' ? '' : elm.attr('data-target-id');
			if( '' === segment_item_target_id ) { /* Segment target element id not found */
				return;
			}
			segment_target_elm.find( '.wbte_sc_segment_content[data-id="' + segment_item_target_id + '"]' ).fadeIn();


			/* Trigger a custom event. */
			segment_clicked_event.element = elm;
			segment_clicked_event.segment_target_id = segment_target_id;
			segment_clicked_event.segment_item_target_id = segment_item_target_id;						
			jQuery(document).trigger(segment_clicked_event);
		});
	},
	load_segment_state:function(){
		jQuery('.wbte_sc_segment.active').each(function(e){
			jQuery(this).trigger('click');
		});
	}
}
wbte_sc_segments.Set();

var wbte_sc_notify_msg = {
	error:function( message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var er_elm=jQuery('<div class="wbte_sc_notify_msg wbte_sc_notify_msg_error">' + this.get_icon('error-icon') + message + '</div>');				
		this.set_notify(er_elm, auto_close);
	},
	success:function( message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var suss_elm = jQuery('<div class="wbte_sc_notify_msg wbte_sc_notify_msg_success">' + this.get_icon('success-icon') + message + '</div>');				
		this.set_notify(suss_elm, auto_close);
	},
	warning:function( message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var suss_elm = jQuery('<div class="wbte_sc_notify_msg wbte_sc_notify_msg_warning">' + this.get_icon('warn-icon') + message + '</div>');				
		this.set_notify(suss_elm, auto_close);
	},
	info:function( message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		var info_elm = jQuery('<div class="wbte_sc_notify_msg wbte_sc_notify_msg_info">' + this.get_icon('info-icon') + message + '</div>');				
		this.set_notify(info_elm, auto_close);
	},
	progress:function( message ) {
		var prog_elm = jQuery('<div class="wbte_sc_notify_msg wbte_sc_notify_msg_progress"><span class="spinner"></span> ' + message + '</div>');				
		this.set_notify(prog_elm, false, true);
		return prog_elm;
	},
	progress_complete:function( elm, message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		elm.removeClass('wbte_sc_notify_msg_progress').addClass('wbte_sc_notify_msg_success');
		elm.html( this.get_icon('success-icon') +  message );				
		this.set_notify(elm, auto_close);
	},
	progress_error:function( elm, message, auto_close ) {
		var auto_close = (auto_close!== undefined ? auto_close : true);
		elm.removeClass('wbte_sc_notify_msg_progress').addClass('wbte_sc_notify_msg_error');
		elm.html( this.get_icon('error-icon') + message );				
		this.set_notify(elm, auto_close);
	},
    get_icon:function( type ) {
		return '<img class="wbte_sc_notify_msg_icon" src="' + wbte_sc_ds.getAsset( { 'name': type, 'type': 'icon' } ) + '" />';
	},
	set_notify:function( elm, auto_close, is_static ) {
		jQuery('body').append(elm);
		elm.stop(true, true).animate({'opacity':1, 'top':'50px'}, 1000);
		if(is_static) { return; }
		
		elm.on('click',function(){
			wbte_sc_notify_msg.fade_out(elm);
		});
		
		if(auto_close) {
			setTimeout(function(){
				wbte_sc_notify_msg.fade_out(elm);
			},5000);
		}else{
			jQuery('body').on('click',function(){
				wbte_sc_notify_msg.fade_out(elm);
			});
		}
	},
	fade_out:function(elm) {
		elm.animate({'opacity':0,'top':'100px'}, 1000, function(){
			elm.remove();
		});
	}
}