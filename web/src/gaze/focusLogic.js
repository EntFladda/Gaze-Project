/**
 * Focus Detection Logic
 * Improved Academic Focus Detection
 */

class FocusLogic {
    constructor() {
        // Pitch & Yaw threshold
        this.threshold = 60;

        // Roll threshold fixed
        this.rollThreshold = 60;

        // Faster response
        this.focusHistory = [];
        this.maxHistoryLength = 10;

        this.isFocused = false;

        this.focusStartTime = null;
        this.focusDuration = 0;

        this.sessionStats = this.initStats();
    }

    initStats() {
        return {
            totalSamples: 0,
            focusCount: 0,
            unfocusCount: 0,

            focusDuration: 0,
            unfocusDuration: 0,

            startTime: Date.now(),
            lastUpdateTime: Date.now()
        };
    }

    setThreshold(degrees) {
        this.threshold = Math.max(
            60,
            Math.min(90, degrees)
        );

        console.log(
            `[Focus] Threshold set to ${this.threshold}°`
        );
    }

    /**
     * Focus Detection Rules
     */
    isFocused_sync(pose) {
        if (!pose) {
            return false;
        }

        const pitch = pose.pitch;
        const yaw = pose.yaw;
        const roll = pose.roll;

        const absPitch = Math.abs(pitch);
        const absYaw = Math.abs(yaw);
        const absRoll = Math.abs(roll);

        // Menunduk
        if (pitch < -15) {
            return false;
        }

        // Mendongak
        if (pitch > 15) {
            return false;
        }

        // Noleh kiri / kanan
        if (absYaw > this.threshold) {
            return false;
        }

        // Pitch terlalu besar
        if (absPitch > this.threshold) {
            return false;
        }

        // Kepala miring
        if (absRoll > this.rollThreshold) {
            return false;
        }

        return true;
    }

    updateFocusStatus(pose) {

        if (!pose) {
            return this.isFocused;
        }

        const currentFocus =
            this.isFocused_sync(pose);

        this.focusHistory.push(
            currentFocus
        );

        if (
            this.focusHistory.length >
            this.maxHistoryLength
        ) {
            this.focusHistory.shift();
        }

        this.updateStats(
            currentFocus
        );

        const focusedFrames =
            this.focusHistory.filter(
                v => v
            ).length;

        const focusRatio =
            focusedFrames /
            this.focusHistory.length;

        // Minimal 70% frame fokus
        const shouldFocus =
            focusRatio >= 0.7;

        if (
            shouldFocus !==
            this.isFocused
        ) {

            this.isFocused =
                shouldFocus;

            if (
                this.isFocused
            ) {

                this.focusStartTime =
                    Date.now();

                console.log(
                    '[Focus] START'
                );

            } else {

                if (
                    this.focusStartTime
                ) {

                    this.focusDuration =
                        (
                            Date.now() -
                            this.focusStartTime
                        ) / 1000;
                }

                console.log(
                    '[Focus] END'
                );
            }
        }

        return this.isFocused;
    }

    updateStats(isFocused) {
        const now = Date.now();

        const delta =
            (
                now -
                this.sessionStats
                    .lastUpdateTime
            ) / 1000;

        this.sessionStats.totalSamples++;

        if (isFocused) {
            this.sessionStats.focusCount++;

            this.sessionStats.focusDuration +=
                delta;
        } else {
            this.sessionStats.unfocusCount++;

            this.sessionStats.unfocusDuration +=
                delta;
        }

        this.sessionStats.lastUpdateTime =
            now;
    }

    getCurrentFocusDuration() {
        if (
            !this.isFocused ||
            !this.focusStartTime
        ) {
            return this.focusDuration;
        }

        return (
            Date.now() -
            this.focusStartTime
        ) / 1000;
    }

    getFocusPercentage() {
        const total =
            this.sessionStats.focusDuration +
            this.sessionStats.unfocusDuration;

        if (total <= 0) {
            return 0;
        }

        return (
            this.sessionStats.focusDuration /
            total
        ) * 100;
    }

    generateReport() {
        const now =
            Date.now();

        const sessionDuration =
            (
                now -
                this.sessionStats.startTime
            ) / 1000;

        return {
            sessionDuration,

            totalSamples:
                this.sessionStats.totalSamples,

            focusCount:
                this.sessionStats.focusCount,

            unfocusCount:
                this.sessionStats.unfocusCount,

            focusPercentage:
                this.getFocusPercentage(),

            totalFocusTime:
                this.sessionStats.focusDuration,

            totalUnfocusTime:
                this.sessionStats.unfocusDuration,

            averageFrameRate:
                this.sessionStats.totalSamples /
                Math.max(sessionDuration, 1),

            currentStatus:
                this.isFocused
                    ? 'FOCUSED'
                    : 'UNFOCUSED',

            currentDuration:
                this.getCurrentFocusDuration()
        };
    }

    resetStats() {
        this.sessionStats =
            this.initStats();

        this.focusHistory = [];

        this.isFocused = false;

        this.focusStartTime = null;
        this.focusDuration = 0;

        console.log(
            '[Focus] Reset'
        );
    }

    getFocusConfidence() {
        if (
            this.focusHistory.length === 0
        ) {
            return 0;
        }

        const focused =
            this.focusHistory.filter(
                v => v
            ).length;

        return (
            focused /
            this.focusHistory.length
        );
    }

    exportFocusData(pose) {
        return {
            timestamp: Date.now(),

            pose: {
                pitch: pose.pitch,
                yaw: pose.yaw,
                roll: pose.roll
            },

            focusStatus:
                this.isFocused,

            focusDuration:
                this.getCurrentFocusDuration(),

            confidence:
                this.getFocusConfidence(),

            stats:
                this.generateReport()
        };
    }
}

const focusLogic = new FocusLogic();