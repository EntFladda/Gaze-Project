/**
 * IndexedDB Management for Focus Logs
 * Stores local focus session data
 */

const DB_NAME = 'GazeFocusDB';
const DB_VERSION = 1;
const STORE_NAME = 'focusLogs';

class FocusDatabase {
    constructor() {
        this.db = null;
        this.isReady = false;
    }

    /**
     * Initialize IndexedDB
     */
    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => {
                console.error('[DB] Error opening database');
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                this.isReady = true;
                console.log('[DB] Database initialized');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { 
                        keyPath: 'id', 
                        autoIncrement: true 
                    });
                    
                    // Create indexes for efficient queries
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                    store.createIndex('sessionId', 'sessionId', { unique: false });
                    store.createIndex('focusStatus', 'focusStatus', { unique: false });
                    
                    console.log('[DB] Object store created with indexes');
                }
            };
        });
    }

    /**
     * Save focus log entry
     */
    async saveFocusLog(data) {
        if (!this.isReady) {
            console.warn('[DB] Database not ready');
            return null;
        }

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);
            
            const logEntry = {
                timestamp: Date.now(),
                sessionId: data.sessionId || 'default',
                focusStatus: data.focusStatus,
                pitch: data.pitch,
                yaw: data.yaw,
                roll: data.roll,
                confidence: data.confidence || 0,
                duration: data.duration || 0,
            };

            const request = store.add(logEntry);

            request.onerror = () => {
                console.error('[DB] Error saving log:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                resolve(logEntry);
            };
        });
    }

    /**
     * Get logs by date range
     */
    async getLogsByDateRange(startTime, endTime) {
        if (!this.isReady) return [];

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const index = store.index('timestamp');
            
            const range = IDBKeyRange.bound(startTime, endTime);
            const request = index.getAll(range);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
        });
    }

    /**
     * Get all logs for a session
     */
    async getSessionLogs(sessionId) {
        if (!this.isReady) return [];

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_NAME], 'readonly');
            const store = transaction.objectStore(STORE_NAME);
            const index = store.index('sessionId');
            
            const request = index.getAll(sessionId);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result);
        });
    }

    /**
     * Get focus statistics
     */
    async getFocusStats(sessionId) {
        const logs = await this.getSessionLogs(sessionId);
        
        if (logs.length === 0) {
            return {
                totalSamples: 0,
                focusCount: 0,
                focusPercentage: 0,
                averagePitch: 0,
                averageYaw: 0,
                averageRoll: 0,
                maxYaw: 0,
            };
        }

        const focusedLogs = logs.filter(log => log.focusStatus === true);
        const pitchValues = logs.map(log => log.pitch);
        const yawValues = logs.map(log => log.yaw);
        const rollValues = logs.map(log => log.roll);

        return {
            totalSamples: logs.length,
            focusCount: focusedLogs.length,
            focusPercentage: (focusedLogs.length / logs.length) * 100,
            averagePitch: pitchValues.reduce((a, b) => a + b, 0) / pitchValues.length,
            averageYaw: yawValues.reduce((a, b) => a + b, 0) / yawValues.length,
            averageRoll: rollValues.reduce((a, b) => a + b, 0) / rollValues.length,
            maxYaw: Math.max(...yawValues.map(Math.abs)),
        };
    }

    /**
     * Clear all logs
     */
    async clearLogs(sessionId = null) {
        if (!this.isReady) return;

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([STORE_NAME], 'readwrite');
            const store = transaction.objectStore(STORE_NAME);

            let request;
            if (sessionId) {
                const index = store.index('sessionId');
                request = index.openCursor(IDBKeyRange.only(sessionId));
                
                request.onsuccess = (event) => {
                    const cursor = event.target.result;
                    if (cursor) {
                        store.delete(cursor.primaryKey);
                        cursor.continue();
                    } else {
                        resolve();
                    }
                };
            } else {
                request = store.clear();
                request.onsuccess = () => resolve();
            }

            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Export logs as JSON
     */
    async exportLogs(sessionId) {
        const logs = await this.getSessionLogs(sessionId);
        const stats = await this.getFocusStats(sessionId);

        return {
            metadata: {
                exportedAt: new Date().toISOString(),
                appVersion: '1.0.0',
                sessionId: sessionId,
            },
            statistics: stats,
            logs: logs,
        };
    }

    /**
     * Get database size
     */
    async getDatabaseSize() {
        if (!navigator.storage || !navigator.storage.estimate) {
            return null;
        }

        const estimate = await navigator.storage.estimate();
        return {
            usedBytes: estimate.usage,
            totalBytes: estimate.quota,
            usagePercentage: (estimate.usage / estimate.quota) * 100,
        };
    }
}

// Export singleton instance
const focusDatabase = new FocusDatabase();
