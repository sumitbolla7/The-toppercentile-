(function ($) {
    'use strict';
    $(document).on('click', '.ttpa-copy-link', function () {
        var text = $(this).data('copy') || $('#ttpa-ref-link').val();
        if (navigator.clipboard && text) {
            navigator.clipboard.writeText(text);
        } else if (text) {
            var $input = $('#ttpa-ref-link');
            $input[0].select();
            document.execCommand('copy');
        }
        $(this).text('Copied!');
    });
})(jQuery);
