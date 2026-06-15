(function ($) {
    'use strict';

    $(function () {
        if ($.fn.wpColorPicker) {
            $('.anh-color-picker').wpColorPicker();
        }

        var frame;
        $('.anh-promo-upload-image').on('click', function (e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: 'Select promo image',
                button: { text: 'Use this image' },
                multiple: false,
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $('.anh-promo-image-url').val(attachment.url);
            });

            frame.open();
        });
    });
})(jQuery);
