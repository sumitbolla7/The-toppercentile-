(function ($) {
    'use strict';

    $(function () {
        $('#ttpn-select-all').on('change', function () {
            $('.ttpn-row-check').prop('checked', this.checked);
        });

        $(document).on('change', '.ttpn-row-check', function () {
            var $rows = $('.ttpn-row-check');
            var $all  = $('#ttpn-select-all');
            if (!$all.length || !$rows.length) {
                return;
            }
            $all.prop('checked', $rows.length === $rows.filter(':checked').length);
        });
    });
})(jQuery);
