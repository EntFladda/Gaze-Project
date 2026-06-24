/**
 * Service Worker for Gaze Focus App
 * Handles offline support, caching, and model persistence
 */

const CACHE_NAME = 'gaze-focus-v1';
const RUNTIME_CACHE = 'gaze-focus-runtime-v1';
const MODEL_CACHE = 'gaze-focus-models-v1';

// Files to cache immediately
const CACHE_URLS = [
    '/',
    '/index.html',
    '/manifest.json',
    '/src/app/main.js',
    '/src/camera/webcam.js',
    '/src/gaze/headPose.js',
    '/src/gaze/smoothing.js',
    '/src/gaze/focusLogic.js',
    '/src/ai/inference.js',
    '/src/ai/loadModel.js',
    '/src/cache/indexedDB.js',
    '/src/cache/cacheModel.js',
    '/src/ui/overlay.js',
    '/src/ui/indicator.js',
    '/src/ui/style.css',
];

// Install event - cache essential files
self.addEventListener('install', (event) => {
    console.log('[SW] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[SW] Caching essential files');
            return cache.addAll(CACHE_URLS).catch((err) => {
                console.warn('[SW] Cache addAll error:', err);
                // Don't fail install if some resources are unavailable
            });
        }).then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME && 
                        cacheName !== RUNTIME_CACHE && 
                        cacheName !== MODEL_CACHE) {
                        console.log('[SW] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Don't cache non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Cache strategy for different resource types
    if (url.pathname.startsWith('/public/models/')) {
        // Model files - cache first, fallback to network
        event.respondWith(cacheModelFile(request));
    } else if (request.destination === 'script' || 
               request.destination === 'style' ||
               request.destination === 'font') {
        // App resources - cache first
        event.respondWith(cacheFirst(request));
    } else {
        // Default - network first, fallback to cache
        event.respondWith(networkFirst(request));
    }
});

/**
 * Cache first strategy - use cached version if available
 */
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[SW] Fetch error:', error);
        return new Response('Offline - Resource not available', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

/**
 * Network first strategy - try network, fallback to cache
 */
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(RUNTIME_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cache = await caches.open(RUNTIME_CACHE);
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        console.warn('[SW] Network and cache failed for:', request.url);
        return new Response('Offline - Resource not available', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

/**
 * Model file caching - critical for on-device inference
 */
async function cacheModelFile(request) {
    const cache = await caches.open(MODEL_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        console.log('[SW] Serving model from cache:', request.url);
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
            console.log('[SW] Cached model file:', request.url);
        }
        return response;
    } catch (error) {
        console.error('[SW] Model fetch failed:', error);
        return new Response('Model not available', {
            status: 503,
            statusText: 'Service Unavailable',
        });
    }
}

// Background sync for logging (future enhancement)
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-focus-logs') {
        event.waitUntil(syncFocusLogs());
    }
});

async function syncFocusLogs() {
    console.log('[SW] Syncing focus logs...');
}

// Message handling from client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CLEAR_CACHE') {
        caches.delete(RUNTIME_CACHE).then(() => {
            console.log('[SW] Cache cleared');
        });
    }
});
