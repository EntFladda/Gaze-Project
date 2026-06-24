/**
 * ONNX model cache for browser-side inference.
 * The server only serves static files; the model is fetched and cached locally.
 */

class ModelCacheManager {
    constructor() {
        this.cache = null;
        this.CACHE_NAME = 'gaze-focus-models-v1';
        this.MODEL_URL = 'public/models/head_pose_model.onnx';
    }

    async init() {
        try {
            this.cache = await caches.open(this.CACHE_NAME);
            console.log('[Cache] ONNX model cache initialized');
            return true;
        } catch (error) {
            console.error('[Cache] Error initializing cache:', error);
            return false;
        }
    }

    async isModelCached() {
        if (!this.cache) return false;
        const response = await this.cache.match(this.MODEL_URL);
        return response !== undefined;
    }

    async loadModelOptimized() {
        if (!window.ort) {
            throw new Error('ONNX Runtime Web is not loaded');
        }

        const cached = await this.isModelCached();
        if (cached) {
            console.log('[Cache] Loading ONNX model from browser cache');
            return this.loadFromCache();
        }

        console.log('[Cache] Downloading ONNX model from static assets');
        return this.loadFromNetwork();
    }

    async loadFromCache() {
        const response = await this.cache.match(this.MODEL_URL);
        if (!response) return null;

        const buffer = await response.arrayBuffer();
        return this.createSession(buffer);
    }

    async loadFromNetwork() {
        const response = await fetch(this.MODEL_URL);
        if (!response.ok) {
            throw new Error(`Model request failed: ${response.status}`);
        }

        const buffer = await response.arrayBuffer();

        if (!this.cache) await this.init();
        await this.cache.put(this.MODEL_URL, new Response(buffer.slice(0), {
            headers: { 'Content-Type': 'application/octet-stream' }
        }));

        console.log('[Cache] ONNX model cached successfully');
        return this.createSession(buffer);
    }

    async createSession(buffer) {
        const options = {
            executionProviders: ['wasm'],
            graphOptimizationLevel: 'all',
        };

        return ort.InferenceSession.create(new Uint8Array(buffer), options);
    }

    async warmupModel(session) {
        try {
            const data = new Float32Array(1 * 3 * 224 * 224);
            const input = new ort.Tensor('float32', data, [1, 3, 224, 224]);
            await session.run({ input });
            console.log('[Cache] ONNX model warmup complete');
            return true;
        } catch (error) {
            console.warn('[Cache] ONNX warmup skipped:', error);
            return false;
        }
    }

    async getCacheStats() {
        const storageEst = navigator.storage?.estimate
            ? await navigator.storage.estimate()
            : null;

        return {
            modelCached: await this.isModelCached(),
            storageUsed: storageEst?.usage || 0,
            storageQuota: storageEst?.quota || 0,
            storagePercentage: storageEst?.quota
                ? (storageEst.usage / storageEst.quota) * 100
                : 0,
        };
    }

    async clearCache() {
        const cacheNames = await caches.keys();
        await Promise.all(
            cacheNames
                .filter(name => name.includes('gaze-focus'))
                .map(name => caches.delete(name))
        );
        console.log('[Cache] Model/app caches cleared');
    }
}

const modelCache = new ModelCacheManager();
