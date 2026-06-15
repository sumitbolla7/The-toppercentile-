(function () {
    'use strict';

    var cfg = window.anhPromoPopup || {};
    var SESSION_KEY = 'anh_promo_popup_shown';

    function wasShownThisSession() {
        try {
            return window.sessionStorage.getItem(SESSION_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function markShownThisSession() {
        try {
            window.sessionStorage.setItem(SESSION_KEY, '1');
        } catch (e) {
            // ignore
        }
    }

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updateCountdown(root, endIso) {
        if (!root || !endIso) {
            return;
        }

        var end = new Date(endIso).getTime();
        if (!end) {
            return;
        }

        function tick() {
            var now = Date.now();
            var diff = end - now;

            if (diff <= 0) {
                root.querySelectorAll('.anh-promo-countdown-num').forEach(function (el) {
                    el.textContent = '00';
                });
                return;
            }

            var days = Math.floor(diff / 86400000);
            diff -= days * 86400000;
            var hours = Math.floor(diff / 3600000);
            diff -= hours * 3600000;
            var minutes = Math.floor(diff / 60000);
            diff -= minutes * 60000;
            var seconds = Math.floor(diff / 1000);

            var map = {
                days: pad(days),
                hours: pad(hours),
                minutes: pad(minutes),
                seconds: pad(seconds),
            };

            root.querySelectorAll('.anh-promo-countdown-num').forEach(function (el) {
                var unit = el.getAttribute('data-unit');
                if (map[unit]) {
                    el.textContent = map[unit];
                }
            });

            window.setTimeout(tick, 1000);
        }

        tick();
    }

    function openPopup(popup) {
        popup.hidden = false;
        popup.setAttribute('aria-hidden', 'false');
        popup.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
        markShownThisSession();
    }

    function closePopup(popup) {
        popup.classList.remove('is-visible');
        popup.hidden = true;
        popup.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function removePopupArtifacts(popup) {
        if (popup && popup.parentNode) {
            popup.parentNode.removeChild(popup);
        }
        document.querySelectorAll('link[href*="promo-popup.css"],script[src*="promo-popup.js"]').forEach(function (el) {
            el.parentNode.removeChild(el);
        });
        document.body.style.overflow = '';
    }

    function isEnabledRemote(callback) {
        if (!cfg.statusUrl) {
            callback(true);
            return;
        }

        fetch(cfg.statusUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                callback(Boolean(data && data.enabled));
            })
            .catch(function () {
                callback(true);
            });
    }

    function bindCloseHandlers(popup) {
        popup.querySelectorAll('[data-anh-promo-close]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closePopup(popup);
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && popup.classList.contains('is-visible')) {
                closePopup(popup);
            }
        });
    }

    function scheduleOpen(popup) {
        if (cfg.showOnceSession && wasShownThisSession()) {
            return;
        }

        if (cfg.showCountdown && cfg.countdownEnd) {
            var countdown = document.getElementById('anh-promo-countdown');
            updateCountdown(countdown, cfg.countdownEnd);
        }

        var delay = parseInt(cfg.delay, 10);
        if (isNaN(delay) || delay < 0) {
            delay = 0;
        }

        window.setTimeout(function () {
            isEnabledRemote(function (enabled) {
                if (!enabled || !document.getElementById('anh-promo-popup')) {
                    removePopupArtifacts(popup);
                    return;
                }
                openPopup(popup);
            });
        }, delay * 1000);
    }

    function init() {
        var popup = document.getElementById('anh-promo-popup');
        if (!popup) {
            return;
        }

        isEnabledRemote(function (enabled) {
            if (!enabled) {
                removePopupArtifacts(popup);
                return;
            }

            bindCloseHandlers(popup);
            scheduleOpen(popup);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
