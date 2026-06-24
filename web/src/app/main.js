/**
 * Main Application Initialization
 * Orchestrates all modules and event listeners
 */

class GazeFocusApp {
    constructor() {
        this.isInitialized = false;
        this.sessionId = this.generateSessionId();
    }

    /**
     * Generate unique session ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Initialize application
     */
    async initialize() {
        try {
            console.log('[App] Initializing Gaze Focus...');

            // Register service worker
            await this.registerServiceWorker();

            // Initialize databases
            await focusDatabase.init();
            console.log('[App] Database initialized');

            // Initialize inference pipeline
            const success = await pipeline.initialize();
            if (!success) {
                throw new Error('Failed to initialize inference pipeline');
            }

            // Setup event listeners
            this.setupEventListeners();

            // Update cache status
            await this.updateCacheStatus();

            // Check permissions
            await this.checkCameraPermission();

            this.isInitialized = true;
            console.log('[App] ✅ Initialization complete');
            showToast('App ready!', 'success');

            return true;

        } catch (error) {
            console.error('[App] Error during initialization:', error);
            showToast('Initialization failed: ' + error.message, 'error');
            return false;
        }
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        if (!navigator.serviceWorker) {
            console.warn('[App] Service Workers not supported');
            return false;
        }

        try {
            const registration = await navigator.serviceWorker.register('sw.js');
            console.log('[App] ✅ Service Worker registered');
            return true;
        } catch (error) {
            console.warn('[App] Service Worker registration failed:', error);
            return false;
        }
    }

    /**
     * Check camera permission
     */
    async checkCameraPermission() {
        try {
            const permissions = await navigator.permissions.query({ name: 'camera' });
            console.log('[App] Camera permission status:', permissions.state);
        } catch (error) {
            console.warn('[App] Could not check camera permission:', error);
        }
    }

    /**
     * Setup all event listeners
     */
    setupEventListeners() {
        // Start button
        document.getElementById('startBtn').addEventListener('click', () => this.startSession());

        // Stop button
        document.getElementById('stopBtn').addEventListener('click', () => this.stopSession());

        // Reset button
        document.getElementById('resetBtn').addEventListener('click', () => this.resetSession());

        // Threshold slider
        document.getElementById('thresholdSlider').addEventListener('input', (e) => {
            const value = parseFloat(e.target.value);
            focusLogic.setThreshold(value);
            document.getElementById('thresholdValue').textContent = value.toFixed(0);
        });

        // Smoothing slider
        document.getElementById('smoothingSlider').addEventListener('input', (e) => {
            const value = parseInt(e.target.value);
            smoothingFilter.setWindowSize(value);
            document.getElementById('smoothingValue').textContent = value;
        });

        // Window visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('[App] App hidden');
                if (pipeline.isRunning) {
                    this.stopSession();
                }
            }
        });

        // Unload
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });

        console.log('[App] Event listeners setup');
    }

    /**
     * Start inference session
     */
    async startSession() {
        try {
            if (!pipeline.isRunning) {
                // Start camera
                const cameraStarted = await webcam.start();
                if (!cameraStarted) {
                    throw new Error('Failed to start camera');
                }

                // Reset statistics
                focusLogic.resetStats();
                pipeline.resetMetrics();
                smoothingFilter.reset();

                // Start inference
                pipeline.start();

                // Update UI
                document.getElementById('startBtn').disabled = true;
                document.getElementById('stopBtn').disabled = false;
                document.getElementById('resetBtn').disabled = false;

                showToast('Session started', 'success');
                console.log('[App] Session started');
            }
        } catch (error) {
            console.error('[App] Error starting session:', error);
            showToast('Failed to start session: ' + error.message, 'error');
        }
    }

    /**
     * Stop inference session
     */
    async stopSession() {
        try {
            // Stop inference
            pipeline.stop();

            // Stop camera
            await webcam.stop();

            // Generate report
            const report = focusLogic.generateReport();
            console.log('[App] Session report:', report);

            // Update UI
            document.getElementById('startBtn').disabled = false;
            document.getElementById('stopBtn').disabled = true;

            showToast(`Session ended. Focus: ${report.focusPercentage.toFixed(1)}%`, 'info');
            console.log('[App] Session stopped');

        } catch (error) {
            console.error('[App] Error stopping session:', error);
        }
    }

    /**
     * Reset session data
     */
    async resetSession() {
        try {
            // Clear logs
            await focusDatabase.clearLogs(this.sessionId);

            // Reset statistics
            focusLogic.resetStats();
            pipeline.resetMetrics();

            // Update UI
            document.getElementById('totalSamples').textContent = '0';
            document.getElementById('focusTime').textContent = '0s';
            document.getElementById('unfocusTime').textContent = '0s';

            showToast('Session data cleared', 'info');
            console.log('[App] Session reset');

        } catch (error) {
            console.error('[App] Error resetting session:', error);
            showToast('Failed to reset: ' + error.message, 'error');
        }
    }

    /**
     * Update cache status display
     */
    async updateCacheStatus() {
        try {
            const stats = await modelCache.getCacheStats();
            if (stats && stats.modelCached) {
                document.getElementById('cacheStatusValue').textContent = '✓ Cached';
            } else {
                document.getElementById('cacheStatusValue').textContent = '⊗ Not cached';
            }
        } catch (error) {
            console.warn('[App] Error getting cache status:', error);
        }
    }

    /**
     * Cleanup before unload
     */
    cleanup() {
        if (pipeline.isRunning) {
            pipeline.stop();
        }

        if (webcam.isActive()) {
            webcam.stop();
        }

        console.log('[App] Cleanup complete');
    }

    /**
     * Export session data
     */
    async exportSessionData() {
        try {
            const data = await focusDatabase.exportLogs(this.sessionId);
            const jsonStr = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonStr], { type: 'application/json' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = url;
            link.download = `focus_session_${this.sessionId}.json`;
            link.click();

            URL.revokeObjectURL(url);
            showToast('Data exported successfully', 'success');

        } catch (error) {
            console.error('[App] Error exporting data:', error);
            showToast('Export failed: ' + error.message, 'error');
        }
    }
}

// Global app instance
let app = null;

/**
 * Initialize app on DOM ready
 */
document.addEventListener('DOMContentLoaded', async () => {
    console.log('[Boot] DOM loaded, initializing app...');

    app = new GazeFocusApp();
    const success = await app.initialize();

    if (!success) {
        console.error('[Boot] App initialization failed');
    }
});

/**
 * Show toast notification (utility function)
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.className = `toast show ${type}`;

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
}
