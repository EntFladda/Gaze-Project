/**
 * ONNX model loader for on-device browser inference.
 */

class ModelLoader {
    constructor() {
        this.session = null;
        this.isLoaded = false;
        this.loadingPromise = null;
    }

    async load() {
        if (this.loadingPromise) return this.loadingPromise;
        this.loadingPromise = this._loadInternal();
        return this.loadingPromise;
    }

    async _loadInternal() {
        try {
            await modelCache.init();
            await focusDatabase.init();

            updateUI('modelStatus', 'Loading ONNX...', 'loading');
            const startTime = performance.now();

            this.session = await modelCache.loadModelOptimized();
            if (!this.session) {
                throw new Error('Failed to load ONNX model');
            }

            await modelCache.warmupModel(this.session);

            const loadTime = performance.now() - startTime;
            this.isLoaded = true;

            updateUI('modelStatus', 'ONNX Ready', 'ready');
            showToast(`ONNX model loaded in ${loadTime.toFixed(0)}ms`, 'success');
            console.log(`[Loader] ONNX model loaded in ${loadTime.toFixed(2)}ms`);

            return this.session;
        } catch (error) {
            console.error('[Loader] Error loading ONNX model:', error);
            updateUI('modelStatus', 'Error!', 'error');
            showToast('Failed to load model: ' + error.message, 'error');
            throw error;
        }
    }

    getModel() {
        if (!this.isLoaded || !this.session) {
            throw new Error('Model not loaded');
        }
        return this.session;
    }

    isModelLoaded() {
        return this.isLoaded && this.session !== null;
    }

    dispose() {
        this.session = null;
        this.isLoaded = false;
        this.loadingPromise = null;
        console.log('[Loader] ONNX session released');
    }

    getModelInfo() {
        if (!this.session) return null;
        return {
            inputNames: this.session.inputNames,
            outputNames: this.session.outputNames,
            format: 'ONNX',
            modelPath: modelCache.MODEL_URL,
        };
    }
}

const modelLoader = new ModelLoader();

function updateUI(elementId, text, className) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = text;
        element.className = `status-value ${className}`;
    }
}

function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.className = `toast show ${type}`;

        setTimeout(() => {
            toast.className = 'toast';
        }, 3000);
    }
}
