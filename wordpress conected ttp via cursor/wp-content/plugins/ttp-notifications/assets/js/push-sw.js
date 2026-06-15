self.addEventListener('push', function (event) {
    var data = { title: 'Notification', body: '', url: '/' };
    try {
        if (event.data) {
            data = event.data.json();
        }
    } catch (e) {}

    event.waitUntil(
        self.registration.showNotification(data.title || 'Notification', {
            body: data.body || '',
            tag: data.tag || 'ttpn',
            data: { url: data.url || '/' }
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(clients.openWindow(url));
});
