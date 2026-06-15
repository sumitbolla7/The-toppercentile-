(function ($) {
    'use strict';

    function getAdminConfig() {
        return window.anhAdmin || {};
    }

    function initAjaxUserSearch(select) {
        var $select = $(select);
        var cfg = getAdminConfig();
        var ajaxUrl = cfg.ajaxUrl || (typeof ajaxurl !== 'undefined' ? ajaxurl : '');
        var nonce = cfg.nonce || '';
        var affiliatePage = cfg.affiliatePage || '';

        var baseConfig = {
            width: '100%',
            placeholder: $select.data('search-placeholder') || $select.find('option:first').text(),
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function () {
                    return 'Type at least 2 characters to search…';
                },
                searching: function () {
                    return 'Searching…';
                },
                noResults: function () {
                    return 'No users found';
                },
            },
        };

        if (ajaxUrl && nonce) {
            baseConfig.ajax = {
                url: ajaxUrl,
                dataType: 'json',
                delay: 300,
                cache: true,
                data: function (params) {
                    return {
                        action: 'anh_search_users',
                        nonce: nonce,
                        q: params.term || '',
                    };
                },
                processResults: function (response) {
                    if (!response || !response.success || !response.data || !response.data.results) {
                        return { results: [] };
                    }
                    return { results: response.data.results };
                },
            };
        }

        var $filter = $select.closest('td, .anh-user-select-field').find('.anh-user-filter-input').first();
        if ($filter.length && (ajaxUrl && nonce)) {
            $filter.hide();
        }

        function bindSelectChange($el) {
            $el.on('change', function () {
                var userId = $(this).val();
                if (!userId) {
                    return;
                }

                if ($select.data('auto-load') && affiliatePage) {
                    window.location.href = affiliatePage + '&user_id=' + encodeURIComponent(userId);
                    return;
                }

                $('#anh-affiliate-access-form').find('input[name="user_id"]').remove();
                $('#anh-affiliate-access-form').append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'user_id',
                        value: userId,
                    })
                );
            });
        }

        if ($.fn.selectWoo) {
            $select.selectWoo(baseConfig);
            bindSelectChange($select);
            return;
        }

        if ($.fn.select2) {
            $select.select2(baseConfig);
            bindSelectChange($select);
            return;
        }

        initNativeUserFilter($select);
    }

    function initNativeUserFilter($select) {
        var $filter = $select.closest('td, .anh-user-select-field').find('.anh-user-filter-input').first();

        function filterOptions() {
            var q = ($filter.val() || '').toLowerCase().trim();
            var visibleCount = 0;
            var firstVisible = null;

            $select.find('option').each(function () {
                var $opt = $(this);
                if (!$opt.val()) {
                    $opt.prop('hidden', false).prop('disabled', false);
                    return;
                }

                var visible = q === '' || $opt.text().toLowerCase().indexOf(q) !== -1;
                $opt.prop('hidden', !visible).prop('disabled', !visible);
                if (visible) {
                    visibleCount++;
                    if (!firstVisible) {
                        firstVisible = $opt;
                    }
                }
            });

            if (q !== '' && visibleCount === 1 && firstVisible) {
                $select.val(firstVisible.val()).trigger('change');
            }
        }

        if ($filter.length) {
            $filter.show();
            $filter.on('input', filterOptions);
        }
    }

    function initUserSearch(select) {
        initAjaxUserSearch(select);
    }

    function toggleTargetRows() {
        var target = document.getElementById('anh-target');
        var singleRow = document.querySelector('.anh-target-single');
        var csvRow = document.querySelector('.anh-target-csv');
        if (!target) {
            return;
        }

        if (singleRow) {
            singleRow.style.display = target.value === 'single' ? '' : 'none';
        }
        if (csvRow) {
            csvRow.style.display = target.value === 'csv' ? '' : 'none';
        }
    }

    function toggleInfluencerAccess() {
        var $influencer = $('input[name="grant_influencer"]');
        var $enabled = $('input[name="affiliate_enabled"]');
        if (!$influencer.length || !$enabled.length) {
            return;
        }

        function sync() {
            if ($influencer.is(':checked')) {
                $enabled.prop('checked', true).prop('disabled', true);
            } else {
                $enabled.prop('disabled', false);
            }
        }

        $influencer.on('change', sync);
        sync();
    }

    function initCopyButtons() {
        $(document).on('click', '.anh-copy-link', function () {
            var text = $(this).data('copy') || '';
            if (!text) {
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            } else {
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
            }

            var $btn = $(this);
            var original = $btn.text();
            $btn.text('Copied!');
            window.setTimeout(function () {
                $btn.text(original);
            }, 1500);
        });
    }

    $(function () {
        $('select.anh-user-search').each(function () {
            initUserSearch(this);
        });
        toggleTargetRows();
        toggleInfluencerAccess();
        initCopyButtons();
        $('#anh-target').on('change', toggleTargetRows);
    });
})(jQuery);
