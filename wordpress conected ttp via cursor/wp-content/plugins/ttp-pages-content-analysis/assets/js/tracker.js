(function () {
	'use strict';
	if (typeof ttpPcaTrack === 'undefined' || !ttpPcaTrack.endpoint) {
		return;
	}

	var key = 'ttp_pca_sid';
	var sid = '';
	try {
		sid = sessionStorage.getItem(key) || '';
		if (!sid) {
			sid = Math.random().toString(36).slice(2) + Date.now().toString(36);
			sessionStorage.setItem(key, sid);
		}
	} catch (e) {
		sid = 'nosession';
	}

	var device = window.innerWidth < 768 ? 'mobile' : window.innerWidth < 1024 ? 'tablet' : 'desktop';

	var payload = {
		post_id: parseInt(ttpPcaTrack.postId, 10) || 0,
		post_type: ttpPcaTrack.postType || '',
		url: ttpPcaTrack.url || window.location.href,
		title: ttpPcaTrack.title || document.title,
		referrer: document.referrer || '',
		session: sid,
		device: device
	};

	function send() {
		var body = JSON.stringify(payload);
		if (navigator.sendBeacon) {
			try {
				var blob = new Blob([body], { type: 'application/json' });
				navigator.sendBeacon(ttpPcaTrack.endpoint + '?_wpnonce=' + encodeURIComponent(ttpPcaTrack.nonce), blob);
				return;
			} catch (err) { /* fall through */ }
		}
		fetch(ttpPcaTrack.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': ttpPcaTrack.nonce
			},
			body: body,
			credentials: 'same-origin',
			keepalive: true
		}).catch(function () {});
	}

	if (document.readyState === 'complete') {
		send();
	} else {
		window.addEventListener('load', send, { once: true });
	}
})();
