/**
 * Real-Time Inference Pipeline
 * Main inference loop combining model prediction, pose calculation, and focus detection
 */

class InferencePipeline {
    constructor() {
        this.model = null;
        this.faceMesh = null;
        this.isRunning = false;
        this.frameCount = 0;
        this.fps = 0;
        this.latency = 0;
        this.inferenceMetrics = {
            totalFrames: 0,
            totalLatency: 0,
            minLatency: Infinity,
            maxLatency: 0,
        };
    }

    /**
     * Initialize inference pipeline
     */
    async initialize() {
        try {
            console.log('[Inference] Initializing pipeline...');

            // Load model
            this.model = await modelLoader.load();
            
            // Initialize MediaPipe FaceMesh
            this.faceMesh = new window.FaceMesh({
                locateFile: (file) => {
                    return `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`;
                }
            });

            this.faceMesh.setOptions({
                maxNumFaces: 1,
                refineLandmarks: true,
                minDetectionConfidence: 0.5,
                minTrackingConfidence: 0.5,
            });

            this.faceMesh.onResults(this.handleFaceMeshResults.bind(this));

            console.log('[Inference] ✅ Pipeline ready');
            return true;

        } catch (error) {
            console.error('[Inference] Error initializing:', error);
            showToast('Failed to initialize inference: ' + error.message, 'error');
            return false;
        }
    }

    /**
     * Start inference loop
     */
    start() {
        if (this.isRunning) {
            console.warn('[Inference] Already running');
            return;
        }

        this.isRunning = true;
        this.frameCount = 0;
        this.lastFrameTime = performance.now();
        
        console.log('[Inference] Starting inference loop...');
        this.inferenceLoop();
    }

    /**
     * Stop inference loop
     */
    stop() {
        this.isRunning = false;
        console.log('[Inference] Stopped');
    }

    /**
     * Main inference loop
     */
    async inferenceLoop() {
        if (!this.isRunning) return;

        const startTime = performance.now();

        try {
            // Get video frame
            const frame = webcam.getFrame();
            if (!frame) {
                requestAnimationFrame(() => this.inferenceLoop());
                return;
            }

            // Process with MediaPipe
            if (this.faceMesh && webcam.isActive()) {
                await this.faceMesh.send({ image: frame.canvas });
            }

            // Update FPS counter
            this.frameCount++;
            const elapsedTime = startTime - this.lastFrameTime;
            if (elapsedTime >= 1000) { // Update every second
                this.fps = (this.frameCount * 1000) / elapsedTime;
                document.getElementById('fpsCounter').textContent = this.fps.toFixed(1);
                this.frameCount = 0;
                this.lastFrameTime = startTime;
            }

        } catch (error) {
            console.error('[Inference] Error in loop:', error);
        }

        // Schedule next frame
        requestAnimationFrame(() => this.inferenceLoop());
    }

    /**
     * Handle MediaPipe face mesh results
     */
    handleFaceMeshResults(results) {
        if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
            // No face detected
            return;
        }

        const landmarks = results.multiFaceLandmarks[0];

        // Calculate head pose from landmarks
        let pose = headPose.calculatePoseFromLandmarks(landmarks);

        if (pose) {
            // Apply smoothing filter
            pose = smoothingFilter.smooth(pose);

            // Update focus status
            focusLogic.updateFocusStatus(pose);

            // Save to database
            this.saveFocusData(pose);

            // Update UI
            updatePoseUI(pose);
            updateFocusUI(focusLogic.isFocused, focusLogic.getCurrentFocusDuration());
        }

        // Draw visualization
        const canvas = document.getElementById('canvas');
        this.drawVisualization(canvas, landmarks, pose, results);

        // Update latency
        this.latency = performance.now() - this.inferenceTime;
        document.getElementById('latencyCounter').textContent = this.latency.toFixed(1) + 'ms';
        
        // Track metrics
        this.inferenceMetrics.totalFrames++;
        this.inferenceMetrics.totalLatency += this.latency;
        this.inferenceMetrics.minLatency = Math.min(this.inferenceMetrics.minLatency, this.latency);
        this.inferenceMetrics.maxLatency = Math.max(this.inferenceMetrics.maxLatency, this.latency);

        this.inferenceTime = performance.now();
    }

    /**
     * Save focus data to database
     */
    async saveFocusData(pose) {
        if (!document.getElementById('recordLogs').checked) {
            return;
        }

        try {
            const focusData = {
                sessionId: 'default',
                focusStatus: focusLogic.isFocused,
                pitch: pose.pitch,
                yaw: pose.yaw,
                roll: pose.roll,
                confidence: pose.confidence,
                duration: focusLogic.getCurrentFocusDuration(),
            };

            await focusDatabase.saveFocusLog(focusData);

        } catch (error) {
            console.warn('[Inference] Error saving focus data:', error);
        }
    }

    /**
     * Draw visualization on canvas
     */
    drawVisualization(canvas, landmarks, pose, results) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );
        const width = canvas.width;
        const height = canvas.height;
        if (
            document.getElementById('showFaceMesh').checked &&
            landmarks
        ) {
            this.drawFaceMesh(
                ctx,
                landmarks,
                width,
                height
            );
    }

    if (
        document.getElementById('showAngleLabels').checked &&
        pose
    ) {
        this.drawAngleLabels(
            ctx,
            pose,
            width,
            height
        );
    }

    this.drawFocusIndicator(
        ctx,
        focusLogic.isFocused,
        width,
        height
    );
}

    /**
     * Draw face mesh landmarks
     */
    drawFaceMesh(ctx, landmarks, width, height) {
        // Draw face contour points
        ctx.fillStyle = 'rgba(0, 255, 0, 0.3)';
        
        landmarks.forEach(landmark => {
            const x = landmark.x * width;
            const y = landmark.y * height;
            
            ctx.beginPath();
            ctx.arc(x, y, 2, 0, 2 * Math.PI);
            ctx.fill();
        });

        // Draw key landmark connections
        const connections = [
            [33, 263],  // eyes
            [4, 152],   // nose to chin
            [234, 454], // cheeks
        ];

        ctx.strokeStyle = 'rgba(0, 255, 0, 0.5)';
        ctx.lineWidth = 2;

        connections.forEach(([start, end]) => {
            if (start < landmarks.length && end < landmarks.length) {
                const p1 = landmarks[start];
                const p2 = landmarks[end];
                
                ctx.beginPath();
                ctx.moveTo(p1.x * width, p1.y * height);
                ctx.lineTo(p2.x * width, p2.y * height);
                ctx.stroke();
            }
        });
    }

    /**
     * Draw angle labels on canvas
     */
    drawAngleLabels(ctx, pose, width, height) {
        ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
        ctx.font = 'bold 16px Arial';
        ctx.textAlign = 'right';

        const x = width - 20;
        const y = 30;

        ctx.fillText(`Pitch: ${pose.pitch.toFixed(1)}°`, x, y);
        ctx.fillText(`Yaw: ${pose.yaw.toFixed(1)}°`, x, y + 25);
        ctx.fillText(`Roll: ${pose.roll.toFixed(1)}°`, x, y + 50);
    }

    /**
     * Draw focus indicator
     */
    drawFocusIndicator(ctx, isFocused, width, height) {
        if (isFocused) {
            ctx.strokeStyle = 'rgba(0, 255, 0, 0.8)';
            ctx.lineWidth = 4;
        } else {
            ctx.strokeStyle = 'rgba(255, 0, 0, 0.8)';
            ctx.lineWidth = 4;
        }

        ctx.strokeRect(10, 10, width - 20, height - 20);
    }

    /**
     * Get inference statistics
     */
    getStats() {
        const avgLatency = this.inferenceMetrics.totalFrames > 0
            ? this.inferenceMetrics.totalLatency / this.inferenceMetrics.totalFrames
            : 0;

        return {
            totalFrames: this.inferenceMetrics.totalFrames,
            fps: this.fps,
            averageLatency: avgLatency,
            minLatency: this.inferenceMetrics.minLatency === Infinity ? 0 : this.inferenceMetrics.minLatency,
            maxLatency: this.inferenceMetrics.maxLatency,
        };
    }

    /**
     * Reset metrics
     */
    resetMetrics() {
        this.inferenceMetrics = {
            totalFrames: 0,
            totalLatency: 0,
            minLatency: Infinity,
            maxLatency: 0,
        };
    }
}

// Export singleton instance
const pipeline = new InferencePipeline();

/**
 * Update UI with pose values
 */
function updatePoseUI(pose) {
    if (!pose) return;

    document.getElementById('pitchValue').textContent = pose.pitch.toFixed(2) + '°';
    document.getElementById('yawValue').textContent = pose.yaw.toFixed(2) + '°';
    document.getElementById('rollValue').textContent = pose.roll.toFixed(2) + '°';

    // Update progress bars (0-90 degrees mapped to 0-100%)
    const pitchPercent = ((pose.pitch + 90) / 180) * 100;
    const yawPercent = ((pose.yaw + 90) / 180) * 100;
    const rollPercent = ((pose.roll + 90) / 180) * 100;

    document.getElementById('pitchBar').style.width = pitchPercent + '%';
    document.getElementById('yawBar').style.width = yawPercent + '%';
    document.getElementById('rollBar').style.width = rollPercent + '%';

    // Update statistics
    const report = focusLogic.generateReport();
    document.getElementById('totalSamples').textContent = report.totalSamples;
    document.getElementById('focusTime').textContent = report.totalFocusTime.toFixed(1);
    document.getElementById('unfocusTime').textContent = report.totalUnfocusTime.toFixed(1);
}

/**
 * Update UI with focus status
 */
function updateFocusUI(isFocused, duration) {
    const indicator = document.getElementById('focusIndicator');
    const focusDurationEl = document.getElementById('focusDuration');

    focusDurationEl.textContent = duration.toFixed(1) + 's';

    if (isFocused) {
        indicator.className = 'focus-indicator focused';
        indicator.querySelector('.indicator-label').textContent = '✓ FOCUSED';
    } else {
        indicator.className = 'focus-indicator unfocused';
        indicator.querySelector('.indicator-label').textContent = '⊗ UNFOCUSED';
    }
}
