/**
 * Temporal Smoothing Filter
 * Smooth noisy pose estimates using various filtering techniques
 */

class SmoothingFilter {
    constructor(windowSize = 5) {
        this.windowSize = windowSize;
        this.pitchBuffer = [];
        this.yawBuffer = [];
        this.rollBuffer = [];
        this.method = 'kalman'; // 'kalman', 'moving_average', 'exponential'
    }

    /**
     * Set smoothing window size
     */
    setWindowSize(size) {
        this.windowSize = Math.max(1, Math.min(15, size));
    }

    /**
     * Set smoothing method
     */
    setMethod(method) {
        if (['kalman', 'moving_average', 'exponential'].includes(method)) {
            this.method = method;
        }
    }

    /**
     * Apply smoothing filter
     */
    smooth(pose) {
        if (!pose) return null;

        switch (this.method) {
            case 'kalman':
                return this.kalmanFilter(pose);
            case 'moving_average':
                return this.movingAverage(pose);
            case 'exponential':
                return this.exponentialFilter(pose);
            default:
                return pose;
        }
    }

    /**
     * Moving Average Filter
     */
    movingAverage(pose) {
        this.pitchBuffer.push(pose.pitch);
        this.yawBuffer.push(pose.yaw);
        this.rollBuffer.push(pose.roll);

        if (this.pitchBuffer.length > this.windowSize) {
            this.pitchBuffer.shift();
            this.yawBuffer.shift();
            this.rollBuffer.shift();
        }

        return {
            pitch: this.average(this.pitchBuffer),
            yaw: this.average(this.yawBuffer),
            roll: this.average(this.rollBuffer),
            confidence: pose.confidence || 1.0,
        };
    }

    /**
     * Exponential Moving Average Filter
     */
    exponentialFilter(pose) {
        const alpha = 2 / (this.windowSize + 1);

        if (this.pitchBuffer.length === 0) {
            this.pitchBuffer = [pose.pitch];
            this.yawBuffer = [pose.yaw];
            this.rollBuffer = [pose.roll];
            return pose;
        }

        const lastPitch = this.pitchBuffer[this.pitchBuffer.length - 1];
        const lastYaw = this.yawBuffer[this.yawBuffer.length - 1];
        const lastRoll = this.rollBuffer[this.rollBuffer.length - 1];

        const smoothedPitch = alpha * pose.pitch + (1 - alpha) * lastPitch;
        const smoothedYaw = alpha * pose.yaw + (1 - alpha) * lastYaw;
        const smoothedRoll = alpha * pose.roll + (1 - alpha) * lastRoll;

        this.pitchBuffer.push(smoothedPitch);
        this.yawBuffer.push(smoothedYaw);
        this.rollBuffer.push(smoothedRoll);

        if (this.pitchBuffer.length > this.windowSize) {
            this.pitchBuffer.shift();
            this.yawBuffer.shift();
            this.rollBuffer.shift();
        }

        return {
            pitch: smoothedPitch,
            yaw: smoothedYaw,
            roll: smoothedRoll,
            confidence: pose.confidence || 1.0,
        };
    }

    /**
     * Simplified Kalman Filter
     * Helps with noisy measurements
     */
    kalmanFilter(pose) {
        const processNoise = 0.01; // How much we trust the model
        const measurementNoise = 0.1; // How much noise in measurements

        if (this.pitchBuffer.length === 0) {
            this.pitchBuffer = [pose.pitch];
            this.yawBuffer = [pose.yaw];
            this.rollBuffer = [pose.roll];
            return pose;
        }

        const prediction = {
            pitch: this.pitchBuffer[this.pitchBuffer.length - 1],
            yaw: this.yawBuffer[this.yawBuffer.length - 1],
            roll: this.rollBuffer[this.rollBuffer.length - 1],
        };

        // Kalman gain
        const gainPitch = measurementNoise / (measurementNoise + processNoise);
        const gainYaw = measurementNoise / (measurementNoise + processNoise);
        const gainRoll = measurementNoise / (measurementNoise + processNoise);

        // Update with measurement
        const smoothedPitch = prediction.pitch + gainPitch * (pose.pitch - prediction.pitch);
        const smoothedYaw = prediction.yaw + gainYaw * (pose.yaw - prediction.yaw);
        const smoothedRoll = prediction.roll + gainRoll * (pose.roll - prediction.roll);

        this.pitchBuffer.push(smoothedPitch);
        this.yawBuffer.push(smoothedYaw);
        this.rollBuffer.push(smoothedRoll);

        if (this.pitchBuffer.length > this.windowSize) {
            this.pitchBuffer.shift();
            this.yawBuffer.shift();
            this.rollBuffer.shift();
        }

        return {
            pitch: smoothedPitch,
            yaw: smoothedYaw,
            roll: smoothedRoll,
            confidence: pose.confidence || 1.0,
        };
    }

    /**
     * Calculate average of array
     */
    average(array) {
        if (array.length === 0) return 0;
        return array.reduce((a, b) => a + b, 0) / array.length;
    }

    /**
     * Calculate standard deviation
     */
    standardDeviation(array) {
        if (array.length === 0) return 0;
        const avg = this.average(array);
        const squaredDiffs = array.map(val => (val - avg) ** 2);
        return Math.sqrt(this.average(squaredDiffs));
    }

    /**
     * Reset filter buffers
     */
    reset() {
        this.pitchBuffer = [];
        this.yawBuffer = [];
        this.rollBuffer = [];
    }

    /**
     * Get filter statistics
     */
    getStats() {
        return {
            pitchStd: this.standardDeviation(this.pitchBuffer),
            yawStd: this.standardDeviation(this.yawBuffer),
            rollStd: this.standardDeviation(this.rollBuffer),
            bufferSize: this.pitchBuffer.length,
        };
    }
}

// Export singleton instance
const smoothingFilter = new SmoothingFilter(5);
