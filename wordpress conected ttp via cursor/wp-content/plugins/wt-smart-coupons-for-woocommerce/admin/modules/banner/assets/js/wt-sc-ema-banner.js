jQuery(document).ready(function($) {

    /** Handle dismiss button click using event delegation */
    jQuery(document).on('click', '.wt_sc_ema_dismiss', function(e) {
        e.preventDefault();
   
        const $banner = jQuery(this).closest('.wt_sc_gift_card_ema_banner');
        const $button = jQuery(this);
        $banner.css('opacity', '0.5');
        $button.prop('disabled', true); /** Disable button to prevent multiple clicks  */
        
        $.ajax({
            url: wt_sc_ema_banner_params.ajaxurl,
            type: 'POST',
            data: {
                action: 'wt_sc_dismiss_ema_banner',
                nonce: wt_sc_ema_banner_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    $banner.slideUp();
                }else{
                    $banner.css('opacity', '1');
                    $button.prop('disabled', false);
                }
            },
            error: function() {
                $banner.css('opacity', '1');
                $button.prop('disabled', false);
            }
        });
    });

    /** Function to check path and show/hide banner */
    function checkPathAndToggleBanner() {
        const urlParams = new URLSearchParams(window.location.search);
        const pathParam = urlParams.get('path') || '';
        const $banner = $('.wt_sc_gift_card_ema_banner');
        
        if (!$banner.length) {
            return; 
        }
        
        if ( pathParam.includes('/analytics/coupons') || pathParam.includes('/analytics/revenue') ) {
            $banner.removeClass('hide');
        } else {
            $banner.addClass('hide');
        }
    }
    
    setTimeout(checkPathAndToggleBanner, 1500);
    
    const originalPushState = history.pushState;
    history.pushState = function() {
        originalPushState.apply(history, arguments);
        setTimeout(checkPathAndToggleBanner, 100);
    };
    
    const originalReplaceState = history.replaceState;
    history.replaceState = function() {
        originalReplaceState.apply(history, arguments);
        setTimeout(checkPathAndToggleBanner, 100);
    };
    
    $(window).on('popstate', function() {
        setTimeout(checkPathAndToggleBanner, 100);
    });

});
