(function ($) {
    'use strict';

    var TOAST_DURATION = (window.TTPN && TTPN.toastDuration) ? parseInt(TTPN.toastDuration, 10) : 5500;
    var POLL_INTERVAL = (window.TTPN && TTPN.pollInterval) ? parseInt(TTPN.pollInterval, 10) : 45000;
    var SESSION_KEY = 'ttpn_toast_shown_v1';
    var shownIds = {};
    var activeToastTimer = null;
    var pollTimer = null;
    var booted = false;

    function isUnread(item) {
        return item && (item.is_read === false || item.is_read === 0 || item.is_read === '0');
    }

    function getSessionShownIds() {
        try {
            var raw = window.sessionStorage.getItem(SESSION_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (e) {
            return {};
        }
    }

    function markSessionShown(id) {
        if (!id) {
            return;
        }

        var ids = getSessionShownIds();
        ids[id] = 1;

        try {
            window.sessionStorage.setItem(SESSION_KEY, JSON.stringify(ids));
        } catch (e) {
            // Ignore storage errors (private mode, quota, etc.).
        }
    }

    function wasShownThisSession(id) {
        return !!getSessionShownIds()[id];
    }

    function fetchNotifications() {
        if (!window.TTPN || !TTPN.ajaxUrl) {
            return $.Deferred().reject().promise();
        }

        return $.post(TTPN.ajaxUrl, {
            action: 'ttpn_get_notifications',
            nonce: TTPN.nonce
        });
    }

    function ensureToastStack() {
        var $stack = $('#ttpn-toast-stack');
        if (!$stack.length) {
            $stack = $('<div id="ttpn-toast-stack" class="ttpn-toast-stack" aria-live="polite"></div>');
            $('body').append($stack);
        }
        return $stack;
    }

    function clearToasts() {
        if (activeToastTimer) {
            window.clearTimeout(activeToastTimer);
            activeToastTimer = null;
        }
        $('#ttpn-toast-stack .ttpn-toast').remove();
    }

    function markNotificationRead(id) {
        if (!id || !window.TTPN || !TTPN.ajaxUrl) {
            return;
        }

        $.post(TTPN.ajaxUrl, {
            action: 'ttpn_mark_notification_read',
            nonce: TTPN.nonce,
            id: id
        });
    }

    function dismissToast($toast, id, markRead) {
        if (!$toast || !$toast.length) {
            return;
        }

        $toast.addClass('is-hiding');
        window.setTimeout(function () {
            $toast.remove();
        }, 350);

        if (markRead && id) {
            markNotificationRead(id);
        }
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function resolveToastDuration(item, options) {
        if (options && options.duration) {
            return options.duration;
        }

        if (item && item.meta && item.meta.toast_duration_seconds) {
            var seconds = parseInt(item.meta.toast_duration_seconds, 10);
            if (!isNaN(seconds) && seconds >= 3) {
                return seconds * 1000;
            }
        }

        return TOAST_DURATION;
    }

    function showToast(item, options) {
        options = options || {};

        if (!item || !item.id || shownIds[item.id] || wasShownThisSession(item.id)) {
            return;
        }

        ensureToastStack();
        clearToasts();
        shownIds[item.id] = true;
        markSessionShown(item.id);

        if (!options.skipMarkRead) {
            markNotificationRead(item.id);
        }

        var linkHtml = item.link
            ? '<a class="ttpn-toast-link" href="' + escapeHtml(item.link) + '">View details</a>'
            : '';

        var $toast = $(
            '<div class="ttpn-toast" data-id="' + item.id + '">' +
                '<button type="button" class="ttpn-toast-close" aria-label="Dismiss">&times;</button>' +
                '<p class="ttpn-toast-title">' + escapeHtml(item.title) + '</p>' +
                '<p class="ttpn-toast-message">' + escapeHtml($('<div>').html(item.message).text()) + '</p>' +
                linkHtml +
            '</div>'
        );

        ensureToastStack().append($toast);

        window.requestAnimationFrame(function () {
            $toast.addClass('is-visible');
        });

        activeToastTimer = window.setTimeout(function () {
            dismissToast($toast, item.id, false);
            activeToastTimer = null;
        }, resolveToastDuration(item, options));

        $toast.on('click', '.ttpn-toast-close', function (e) {
            e.preventDefault();
            if (activeToastTimer) {
                window.clearTimeout(activeToastTimer);
                activeToastTimer = null;
            }
            dismissToast($toast, item.id, true);
        });
    }

    function showLatestUnreadToast(items) {
        if (!items || !items.length) {
            return;
        }

        var unread = items.filter(function (item) {
            return isUnread(item) && !wasShownThisSession(item.id);
        });

        if (!unread.length) {
            return;
        }

        showToast(unread[0]);
    }

    function handleNotificationPayload(res) {
        if (!res || !res.success) {
            return;
        }

        showLatestUnreadToast(res.data.items || []);
    }

    function startPolling() {
        if (pollTimer || POLL_INTERVAL <= 0) {
            return;
        }

        pollTimer = window.setInterval(function () {
            fetchNotifications().done(handleNotificationPayload);
        }, POLL_INTERVAL);
    }

    function bootNotifications() {
        if (booted) {
            return;
        }
        booted = true;

        ensureToastStack();
        fetchNotifications().done(handleNotificationPayload);
        startPolling();
    }

    $(document).on('click', '.ttpn-bell-toggle', function () {
        fetchNotifications().done(function (res) {
            if (!res.success) {
                return;
            }

            var items = res.data.items || [];
            if (items.length) {
                delete shownIds[items[0].id];
                showToast(items[0], { skipMarkRead: true });
            }
        });
    });

    $(bootNotifications);
})(jQuery);
