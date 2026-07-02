const CACHE_NAME = 'gaze-models-v1';
const MODEL_URL = '/models/head_pose_model.onnx';

self.addEventListener('install', event => {
    self.skipWaiting();
    
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.match(MODEL_URL).then(response => {
                if (!response) {
                    console.log('[SW] Memulai preload ONNX model (99MB) di background...');
                    return fetch(MODEL_URL).then(res => {
                        if (res.ok) {
                            cache.put(MODEL_URL, res.clone());
                            console.log('[SW] Preload sukses! Model siap digunakan secara instan.');
                        }
                    }).catch(err => console.log('[SW] Preload gagal:', err));
                }
            });
        })
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('fetch', event => {
    if (event.request.url.includes(MODEL_URL)) {
        event.respondWith(
            caches.match(event.request).then(response => {
                return response || fetch(event.request);
            })
        );
    }
});
