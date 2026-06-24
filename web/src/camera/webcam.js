/**
 * Webcam Management
 * Handles video stream capture and frame processing
 */

class WebcamManager {
    constructor() {
        this.video = document.getElementById('videoInput');
        this.canvas = document.getElementById('canvas');
        this.ctx = this.canvas.getContext('2d');

        this.stream = null;
        this.isRunning = false;
    }

    /**
     * Request camera permission and start stream
     */
    async start() {
        try {
            console.log('[Camera] Requesting camera access...');

            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                },
                audio: false
            });

            this.video.srcObject = this.stream;

            // Wait until video metadata is loaded
            await new Promise((resolve) => {
                this.video.onloadedmetadata = resolve;
            });

            // Force play video
            await this.video.play();

            // Match canvas size to video
            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;

            console.log(
                `[Camera] Video size: ${this.video.videoWidth}x${this.video.videoHeight}`
            );

            console.log(
                '[Camera] ReadyState:',
                this.video.readyState
            );

            console.log(
                '[Camera] Tracks:',
                this.stream.getVideoTracks()
            );

            this.isRunning = true;

            console.log('[Camera] ✅ Camera started');

            updateUI('cameraStatus', 'Active ✓', 'ready');

            return true;

        } catch (error) {
            console.error('[Camera] Error accessing camera:', error);

            let errorMsg = 'Camera access failed';

            switch (error.name) {
                case 'NotAllowedError':
                    errorMsg = 'Camera permission denied';
                    break;

                case 'NotFoundError':
                    errorMsg = 'No camera found';
                    break;

                case 'NotReadableError':
                    errorMsg = 'Camera already in use';
                    break;

                case 'OverconstrainedError':
                    errorMsg = 'Camera constraints not supported';
                    break;
            }

            updateUI(
                'cameraStatus',
                'Error',
                'error'
            );

            showToast(errorMsg, 'error');

            return false;
        }
    }

    /**
     * Stop camera stream
     */
    async stop() {
        try {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
            }

            this.video.pause();
            this.video.srcObject = null;

            this.isRunning = false;
            this.stream = null;

            console.log('[Camera] Camera stopped');

            updateUI(
                'cameraStatus',
                'Stopped',
                'idle'
            );

        } catch (error) {
            console.error('[Camera] Stop error:', error);
        }
    }

    /**
     * Capture current video frame
     */
    getFrame() {
        if (
            !this.isRunning ||
            !this.video ||
            this.video.videoWidth === 0
        ) {
            return null;
        }

        this.ctx.drawImage(
            this.video,
            0,
            0,
            this.canvas.width,
            this.canvas.height
        );

        return {
            canvas: this.canvas,
            ctx: this.ctx,
            width: this.canvas.width,
            height: this.canvas.height
        };
    }

    /**
     * Convert current frame to NCHW Float32Array for ONNX Runtime
     */
    frameToOnnxInput(width = 224, height = 224) {
        const scratch = document.createElement('canvas');

        scratch.width = width;
        scratch.height = height;

        const scratchCtx = scratch.getContext(
            '2d',
            { willReadFrequently: true }
        );

        scratchCtx.drawImage(
            this.canvas,
            0,
            0,
            width,
            height
        );

        const pixels = scratchCtx.getImageData(
            0,
            0,
            width,
            height
        ).data;

        const channelSize = width * height;

        const input = new Float32Array(
            3 * channelSize
        );

        for (let i = 0; i < channelSize; i++) {
            const pixelOffset = i * 4;

            input[i] =
                pixels[pixelOffset] / 255.0;

            input[channelSize + i] =
                pixels[pixelOffset + 1] / 255.0;

            input[(channelSize * 2) + i] =
                pixels[pixelOffset + 2] / 255.0;
        }

        return input;
    }

    /**
     * Get camera settings
     */
    getConstraints() {
        if (!this.stream) return null;

        return this.stream
            .getVideoTracks()[0]
            .getSettings();
    }

    /**
     * List available cameras
     */
    static async listCameras() {
        try {
            const devices =
                await navigator.mediaDevices.enumerateDevices();

            const cameras = devices.filter(
                d => d.kind === 'videoinput'
            );

            console.log(
                '[Camera] Available cameras:',
                cameras
            );

            return cameras;

        } catch (error) {
            console.error(
                '[Camera] Error listing cameras:',
                error
            );

            return [];
        }
    }

    /**
     * Switch camera
     */
    async switchCamera(deviceId) {
        try {
            await this.stop();

            this.stream =
                await navigator.mediaDevices.getUserMedia({
                    video: {
                        deviceId: {
                            exact: deviceId
                        }
                    },
                    audio: false
                });

            this.video.srcObject = this.stream;

            await new Promise(resolve => {
                this.video.onloadedmetadata = resolve;
            });

            await this.video.play();

            this.isRunning = true;

            console.log(
                '[Camera] Switched camera:',
                deviceId
            );

            return true;

        } catch (error) {
            console.error(
                '[Camera] Error switching camera:',
                error
            );

            return false;
        }
    }

    /**
     * Check camera state
     */
    isActive() {
        return (
            this.isRunning &&
            this.stream !== null
        );
    }
}

// Export singleton instance
const webcam = new WebcamManager();