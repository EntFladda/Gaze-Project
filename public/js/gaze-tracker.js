/**
 * Gaze Focus Tracker - Integrated Web Script
 * Real-time head pose estimation and focus monitoring for CT-Gamification.
 * Processes camera frames on-device, estimates pitch/yaw/roll, detects looking away,
 * displays a warning modal, and logs focus statistics locally.
 */

// ==========================================
// 1. Model Cache Manager (Local ONNX Cache)
// ==========================================
class ModelCacheManager {
    constructor() {
        this.cache = null;
        this.CACHE_NAME = 'gaze-focus-models-v1';
        this.MODEL_URL = '/models/head_pose_model.onnx';
    }

    async init() {
        try {
            this.cache = await caches.open(this.CACHE_NAME);
            console.log('[GazeCache] ONNX model cache storage initialized');
            return true;
        } catch (error) {
            console.warn('[GazeCache] Error initializing cache storage:', error);
            return false;
        }
    }

    async isModelCached() {
        if (!this.cache) return false;
        try {
            const response = await this.cache.match(this.MODEL_URL);
            return response !== undefined;
        } catch (e) {
            return false;
        }
    }

    async loadModelOptimized() {
        if (!window.ort) {
            console.warn('[GazeCache] ONNX Runtime Web is not loaded, skipping ONNX loading');
            return null;
        }

        const cached = await this.isModelCached();
        if (cached) {
            console.log('[GazeCache] Loading ONNX model from browser Cache Storage');
            return this.loadFromCache();
        }

        console.log('[GazeCache] Downloading ONNX model from assets...');
        return this.loadFromNetwork();
    }

    async loadFromCache() {
        try {
            const response = await this.cache.match(this.MODEL_URL);
            if (!response) return null;
            const buffer = await response.arrayBuffer();
            return this.createSession(buffer);
        } catch (e) {
            console.error('[GazeCache] Failed to load from cache, falling back to network', e);
            return this.loadFromNetwork();
        }
    }

    async loadFromNetwork() {
        try {
            const response = await fetch(this.MODEL_URL);
            if (!response.ok) {
                throw new Error(`Model fetch failed: ${response.status}`);
            }
            const buffer = await response.arrayBuffer();
            if (this.cache) {
                await this.cache.put(this.MODEL_URL, new Response(buffer.slice(0), {
                    headers: { 'Content-Type': 'application/octet-stream' }
                }));
                console.log('[GazeCache] ONNX model cached successfully');
            }
            return this.createSession(buffer);
        } catch (e) {
            console.warn('[GazeCache] Failed to download ONNX model:', e);
            return null;
        }
    }

    async createSession(buffer) {
        if (!window.ort) return null;
        try {
            const options = {
                executionProviders: ['wasm'],
                graphOptimizationLevel: 'all',
            };
            return await ort.InferenceSession.create(new Uint8Array(buffer), options);
        } catch (e) {
            console.error('[GazeCache] Error creating ONNX session:', e);
            return null;
        }
    }

    async warmupModel(session) {
        if (!session || !window.ort) return false;
        try {
            const data = new Float32Array(1 * 3 * 224 * 224);
            const input = new ort.Tensor('float32', data, [1, 3, 224, 224]);
            await session.run({ input });
            console.log('[GazeCache] ONNX model warmup complete');
            return true;
        } catch (error) {
            console.warn('[GazeCache] ONNX warmup failed/skipped:', error);
            return false;
        }
    }
}

// ==========================================
// 2. Head Pose Calculator (Geometrical)
// ==========================================
class HeadPoseCalculator {
    constructor() {
        this.calibrationFrames = 0;
        this.calibrationTarget = 30; // Quick calibration (30 frames)
        this.pitchAccumulator = 0;
        this.yawAccumulator = 0;
        this.pitchOffset = 0;
        this.yawOffset = 0;
    }

    calculatePoseFromLandmarks(landmarks) {
        if (!landmarks || landmarks.length < 468) return null;

        try {
            const nose = landmarks[4];
            const leftEye = landmarks[33];
            const rightEye = landmarks[263];
            const leftCheek = landmarks[234];
            const rightCheek = landmarks[454];
            const chin = landmarks[152];

            let pitch = this.calculatePitch(nose, chin);
            let yaw = this.calculateYaw(nose, leftCheek, rightCheek);
            let roll = this.calculateRoll(leftEye, rightEye);

            // Mirror compensation (for mirrored camera)
            yaw *= -1;

            // Auto-calibration during the first few frames
            if (this.calibrationFrames < this.calibrationTarget) {
                this.pitchAccumulator += pitch;
                this.yawAccumulator += yaw;
                this.calibrationFrames++;

                this.pitchOffset = this.pitchAccumulator / this.calibrationFrames;
                this.yawOffset = this.yawAccumulator / this.calibrationFrames;
            }

            pitch -= this.pitchOffset;
            yaw -= this.yawOffset;

            return { pitch, yaw, roll, confidence: 0.9 };
        } catch (e) {
            console.error('[HeadPose] Error calculating pose:', e);
            return null;
        }
    }

    calculatePitch(nose, chin) {
        if (!nose || !chin) return 0;
        const dy = chin.y - nose.y;
        const dz = chin.z - nose.z;
        let pitch = Math.atan2(dz, dy) * (180 / Math.PI);
        pitch *= 2.5; // Sensitivity modifier
        return Math.max(-90, Math.min(90, pitch));
    }

    calculateYaw(nose, leftCheek, rightCheek) {
        if (!nose || !leftCheek || !rightCheek) return 0;
        const leftDistance = Math.abs(nose.x - leftCheek.x);
        const rightDistance = Math.abs(rightCheek.x - nose.x);
        const ratio = (rightDistance - leftDistance) / (rightDistance + leftDistance);
        return ratio * 120; // Sensitivity scale
    }

    calculateRoll(leftEye, rightEye) {
        if (!leftEye || !rightEye) return 0;
        const dy = rightEye.y - leftEye.y;
        const dx = rightEye.x - leftEye.x;
        return Math.atan2(dy, dx) * (180 / Math.PI);
    }

    recalibrate() {
        this.calibrationFrames = 0;
        this.pitchAccumulator = 0;
        this.yawAccumulator = 0;
        this.pitchOffset = 0;
        this.yawOffset = 0;
    }
}

// ==========================================
// 3. Temporal Smoothing Filter (Kalman)
// ==========================================
class SmoothingFilter {
    constructor(windowSize = 5) {
        this.windowSize = windowSize;
        this.pitchBuffer = [];
        this.yawBuffer = [];
        this.rollBuffer = [];
    }

    smooth(pose) {
        if (!pose) return null;
        
        const processNoise = 0.01;
        const measurementNoise = 0.1;

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

        const gain = measurementNoise / (measurementNoise + processNoise);

        const smoothedPitch = prediction.pitch + gain * (pose.pitch - prediction.pitch);
        const smoothedYaw = prediction.yaw + gain * (pose.yaw - prediction.yaw);
        const smoothedRoll = prediction.roll + gain * (pose.roll - prediction.roll);

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
            confidence: pose.confidence,
        };
    }

    reset() {
        this.pitchBuffer = [];
        this.yawBuffer = [];
        this.rollBuffer = [];
    }
}

// ==========================================
// 4. Focus Logic (Threshold Classifiers)
// ==========================================
class FocusLogic {
    constructor() {
        this.yawThreshold = 15;   // Degrees looking left/right
        this.pitchThreshold = 15; // Degrees looking up/down
        this.rollThreshold = 15;  // Degrees head tilting
        
        this.isFocused = true;
        this.unfocusStartTime = null;
        this.focusHistory = [];
        this.maxHistoryLength = 15; // Grace period (0.5 detik) sebelum berbunyi "tit"
    }

    checkPoseFocus(pose) {
        if (!pose) return false;

        const pitch = pose.pitch;
        const yaw = pose.yaw;
        const roll = pose.roll;

        const absPitch = Math.abs(pitch);
        const absYaw = Math.abs(yaw);
        const absRoll = Math.abs(roll);

        // Strict boundaries for focus (15 degrees)
        if (pitch < -15) return false; // looking down
        if (pitch > 15) return false;  // looking up
        if (absYaw > this.yawThreshold) return false; // looking away sideways
        if (absRoll > this.rollThreshold) return false; // head miring too much

        return true;
    }

    update(pose) {
        if (!pose) {
            return false;
        }

        const isCurrentlyFocused = this.checkPoseFocus(pose);
        
        if (isCurrentlyFocused) {
            // INSTANT RECOVERY (Real-time tanpa delay saat kembali melihat layar)
            this.focusHistory = Array(this.maxHistoryLength).fill(true);
        } else {
            // Masukkan status tidak fokus ke dalam history
            this.focusHistory.push(false);
            if (this.focusHistory.length > this.maxHistoryLength) {
                this.focusHistory.shift();
            }
        }

        // Hanya dianggap tidak fokus jika SELURUH history (0.5 detik terakhir) adalah false
        const nextFocusedState = this.focusHistory.some(f => f === true);

        if (this.isFocused && !nextFocusedState) {
            // transitioned to unfocused
            this.isFocused = false;
            this.unfocusStartTime = Date.now();
        } else if (!this.isFocused && nextFocusedState) {
            // transitioned to focused
            this.isFocused = true;
            this.unfocusStartTime = null;
        }

        return this.isFocused;
    }

    getUnfocusDuration() {
        if (this.isFocused || !this.unfocusStartTime) return 0;
        return (Date.now() - this.unfocusStartTime) / 1000;
    }

    reset() {
        this.isFocused = true;
        this.unfocusStartTime = null;
        this.focusHistory = [];
    }
}

// ==========================================
// 5. Gaze Focus Tracker Main Controller
// ==========================================
class GazeTracker {
    constructor(config = {}) {
        this.challengeId = config.challengeId || '0';
        this.attemptNumber = config.attemptNumber || '1';
        this.storageKey = `gaze_session_${this.challengeId}_attempt_${this.attemptNumber}`;

        this.videoEl = document.getElementById(config.videoId || 'videoInput');
        this.canvasEl = document.getElementById(config.canvasId || 'canvas');
        this.statusBadgeEl = document.getElementById(config.statusBadgeId || 'focusStatusBadge');
        this.unfocusCountEl = document.getElementById(config.unfocusCountId || 'unfocusCountVal');
        this.focusPercentageEl = document.getElementById(config.focusPercentageId || 'focusPercentageVal');

        this.pitchValEl = document.getElementById('pitchVal');
        this.yawValEl = document.getElementById('yawVal');
        this.rollValEl = document.getElementById('rollVal');
        
        this.pitchBarEl = document.getElementById('pitchBar');
        this.yawBarEl = document.getElementById('yawBar');
        this.rollBarEl = document.getElementById('rollBar');

        this.unfocusThreshold = config.unfocusThreshold || 3.0; // seconds before trigger alert
        this.isModalOpen = false;
        this.isRunning = false;

        this.cacheManager = new ModelCacheManager();
        this.poseCalculator = new HeadPoseCalculator();
        this.filter = new SmoothingFilter();
        this.focusLogic = new FocusLogic();
        
        this.stream = null;
        this.faceMesh = null;
        this.camera = null;

        // Statistics
        this.stats = {
            startTime: Date.now(),
            totalSamples: 0,
            focusedFrames: 0,
            unfocusedCount: 0,
            totalUnfocusDuration: 0,
            lastStatusChange: Date.now(),
        };

        this.initLocalStorage();
    }

    initLocalStorage() {
        const saved = localStorage.getItem(this.storageKey);
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                this.stats.unfocusedCount = parsed.unfocusedCount || 0;
                this.stats.totalUnfocusDuration = parsed.totalUnfocusDuration || 0;
                this.stats.focusedFrames = parsed.focusedFrames || 0;
                this.stats.totalSamples = parsed.totalSamples || 0;
            } catch (e) {
                console.warn('[GazeTracker] Resetting corrupted stats from localStorage');
            }
        }
        this.saveStatsToStorage();
    }

    saveStatsToStorage() {
        localStorage.setItem(this.storageKey, JSON.stringify({
            challengeId: this.challengeId,
            attemptNumber: this.attemptNumber,
            unfocusedCount: this.stats.unfocusedCount,
            totalSamples: this.stats.totalSamples,
            focusedFrames: this.stats.focusedFrames,
            focusPercentage: this.calculateFocusPercentage(),
            totalUnfocusDuration: Math.round(this.stats.totalUnfocusDuration),
            updatedAt: Date.now()
        }));
    }

    calculateFocusPercentage() {
        if (this.stats.totalSamples <= 0) return 100;
        return Math.round((this.stats.focusedFrames / this.stats.totalSamples) * 100);
    }

    async initialize() {
        try {
            console.log('[GazeTracker] Initializing...');

            // Initialize cache
            await this.cacheManager.init();
            
            // Trigger background model loading to local Cache Storage
            this.cacheManager.loadModelOptimized().then(session => {
                if (session) {
                    this.cacheManager.warmupModel(session);
                    const cacheBadge = document.getElementById('cacheBadge');
                    if (cacheBadge) {
                        cacheBadge.textContent = 'ONNX Ready ✓';
                        cacheBadge.className = 'px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 text-xs font-semibold';
                    }
                }
            });

            // Initialize MediaPipe FaceMesh
            this.faceMesh = new FaceMesh({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/${file}`
            });

            this.faceMesh.setOptions({
                maxNumFaces: 1,
                refineLandmarks: true,
                minDetectionConfidence: 0.5,
                minTrackingConfidence: 0.5,
            });

            this.faceMesh.onResults((results) => this.onResults(results));

            console.log('[GazeTracker] MediaPipe FaceMesh configured');
            return true;
        } catch (e) {
            console.error('[GazeTracker] Initialization error:', e);
            return false;
        }
    }

    async start() {
        if (this.isRunning) return;

        try {
            console.log('[GazeTracker] Requesting camera access...');
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                },
                audio: false
            });

            if (this.videoEl) {
                this.videoEl.srcObject = this.stream;
                
                await new Promise((resolve) => {
                    this.videoEl.onloadedmetadata = resolve;
                });
                await this.videoEl.play();
                
                if (this.canvasEl) {
                    this.canvasEl.width = this.videoEl.videoWidth;
                    this.canvasEl.height = this.videoEl.videoHeight;
                }
            }

            // Start camera utils helper from MediaPipe
            this.camera = new Camera(this.videoEl, {
                onFrame: async () => {
                    if (this.isRunning && !this.isModalOpen && this.faceMesh) {
                        await this.faceMesh.send({ image: this.videoEl });
                    }
                },
                width: 640,
                height: 480
            });

            this.isRunning = true;
            this.focusLogic.reset();
            this.filter.reset();
            this.stats.startTime = Date.now();
            this.camera.start();

            console.log('[GazeTracker] Camera and face monitoring active');
            this.updateCameraStatusUI(true);
        } catch (e) {
            console.error('[GazeTracker] Failed to start camera:', e);
            this.updateCameraStatusUI(false);
            alert('Izin kamera ditolak atau kamera tidak ditemukan. Anda wajib mengizinkan akses kamera untuk mengerjakan misi ini.\nSistem akan mengembalikan Anda ke halaman daftar misi.');
            window.location.href = '/student/mission';
        }
    }

    async stop() {
        this.isRunning = false;
        
        if (this.camera) {
            try {
                await this.camera.stop();
            } catch (e) {}
            this.camera = null;
        }

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.videoEl) {
            this.videoEl.pause();
            this.videoEl.srcObject = null;
        }

        this.updateCameraStatusUI(false);
        console.log('[GazeTracker] Camera stopped');
    }

    updateCameraStatusUI(active) {
        const indicator = document.getElementById('cameraActiveIndicator');
        if (indicator) {
            const innerDot = indicator.nextElementSibling;
            if (active) {
                indicator.className = 'animate-pulse absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
                if (innerDot) innerDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500';
            } else {
                indicator.className = 'animate-pulse absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75';
                if (innerDot) innerDot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500';
            }
        }
    }

    onResults(results) {
        if (!this.isRunning || this.isModalOpen) return;

        const ctx = this.canvasEl ? this.canvasEl.getContext('2d') : null;
        if (ctx) {
            ctx.clearRect(0, 0, this.canvasEl.width, this.canvasEl.height);
        }

        if (!results.multiFaceLandmarks || results.multiFaceLandmarks.length === 0) {
            // No face detected, treat as unfocused (user looks away completely or walks away)
            this.handleUnfocusedStateFrame();
            return;
        }

        const landmarks = results.multiFaceLandmarks[0];
        let pose = this.poseCalculator.calculatePoseFromLandmarks(landmarks);

        if (pose) {
            pose = this.filter.smooth(pose);
            const isCurrentlyFocused = this.focusLogic.update(pose);

            this.stats.totalSamples++;
            if (isCurrentlyFocused) {
                this.stats.focusedFrames++;
            }

            // Update pose values in UI
            this.updatePoseUI(pose, isCurrentlyFocused);

            const wasFocused = this.lastFocusedState ?? true;
            if (wasFocused && !isCurrentlyFocused) {
                this.playBeep(300, 800, 'sine', 1.5); // "tit" sound (louder and longer)
            }
            this.lastFocusedState = isCurrentlyFocused;

            // Check if unfocus duration exceeds threshold
            if (!isCurrentlyFocused) {
                const duration = this.focusLogic.getUnfocusDuration();
                this.stats.totalUnfocusDuration += 1 / 30; // Approx frame time

                if (duration >= this.unfocusThreshold) {
                    this.triggerUnfocusAlert();
                }
            }

            // Draw Face Mesh
            if (ctx && document.getElementById('showCameraToggle')?.checked !== false) {
                this.drawFaceMesh(ctx, landmarks, isCurrentlyFocused);
            }
        } else {
            this.handleUnfocusedStateFrame();
        }

        this.saveStatsToStorage();
    }

    handleUnfocusedStateFrame() {
        this.stats.totalSamples++;
        this.stats.totalUnfocusDuration += 1 / 30;

        // Force unfocused in tracker logic
        const isCurrentlyFocused = this.focusLogic.update(null);

        const wasFocused = this.lastFocusedState ?? true;
        if (wasFocused) {
            this.playBeep(300, 800, 'sine', 1.5); // "tit" sound (louder and longer)
        }
        this.lastFocusedState = false;
        this.updatePoseUI({ pitch: 0, yaw: 0, roll: 0 }, false);

        const duration = this.focusLogic.getUnfocusDuration();
        if (duration >= this.unfocusThreshold) {
            this.triggerUnfocusAlert();
        }

        this.saveStatsToStorage();
    }

    playBeep(durationMs, freq, type = 'sine', volume = 0.3) {
        try {
            const ctx = window.sharedAudioCtx || new (window.AudioContext || window.webkitAudioContext)();
            window.sharedAudioCtx = ctx;
            if (ctx.state === 'suspended') ctx.resume();
            
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            
            osc.type = type;
            osc.frequency.value = freq;
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            osc.start();
            gain.gain.setValueAtTime(0, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(volume, ctx.currentTime + 0.02);
            gain.gain.setValueAtTime(volume, ctx.currentTime + (durationMs/1000) - 0.05);
            gain.gain.linearRampToValueAtTime(0, ctx.currentTime + (durationMs/1000));
            
            setTimeout(() => osc.stop(), durationMs);
        } catch(e) {}
    }

    updatePoseUI(pose, isFocused) {
        if (this.statusBadgeEl) {
            if (isFocused) {
                this.statusBadgeEl.textContent = 'FOKUS';
                this.statusBadgeEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse';
            } else {
                this.statusBadgeEl.textContent = 'TIDAK FOKUS!';
                this.statusBadgeEl.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-400 border border-red-500/30 animate-bounce';
            }
        }

        if (this.unfocusCountEl) {
            this.unfocusCountEl.textContent = `${this.stats.unfocusedCount} kali`;
        }

        if (this.focusPercentageEl) {
            this.focusPercentageEl.textContent = `${this.calculateFocusPercentage()}%`;
        }

        // Angles and sliders
        if (this.pitchValEl) this.pitchValEl.textContent = `${pose.pitch.toFixed(1)}°`;
        if (this.yawValEl) this.yawValEl.textContent = `${pose.yaw.toFixed(1)}°`;
        if (this.rollValEl) this.rollValEl.textContent = `${pose.roll.toFixed(1)}°`;

        // UI sliders width (mapping -45..45 degrees to 0..100%)
        const mapToPercent = (val, maxDeg = 45) => {
            const clamped = Math.max(-maxDeg, Math.min(maxDeg, val));
            return ((clamped + maxDeg) / (maxDeg * 2)) * 100;
        };

        if (this.pitchBarEl) this.pitchBarEl.style.width = `${mapToPercent(pose.pitch)}%`;
        if (this.yawBarEl) this.yawBarEl.style.width = `${mapToPercent(pose.yaw)}%`;
        if (this.rollBarEl) this.rollBarEl.style.width = `${mapToPercent(pose.roll)}%`;
    }

    triggerUnfocusAlert() {
        if (this.isModalOpen) return;
        this.isModalOpen = true;

        this.stats.unfocusedCount++;
        this.saveStatsToStorage();

        // Play long warning sound (tiiiiiiittt)
        this.playBeep(1500, 500, 'square');

        // Open warning modal
        const modal = document.getElementById('unfocus-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }

        console.log('[GazeTracker] ⚠️ Warning: User unfocused limit violated!');
    }

    resumeFromAlert() {
        this.isModalOpen = false;
        this.focusLogic.reset();

        const modal = document.getElementById('unfocus-modal');
        if (modal) {
            modal.classList.add('hidden');
        }

        // Recalibrate head pose to current state immediately
        this.poseCalculator.recalibrate();
        
        console.log('[GazeTracker] Focus alert dismissed. Monitoring resumed.');
    }

    drawFaceMesh(ctx, landmarks, isFocused) {
        const width = this.canvasEl.width;
        const height = this.canvasEl.height;

        ctx.fillStyle = isFocused ? 'rgba(16, 185, 129, 0.4)' : 'rgba(239, 68, 68, 0.4)';
        
        // Draw main landmark dots (simplified for performance, draw every 5th dot)
        for (let i = 0; i < landmarks.length; i += 4) {
            const pt = landmarks[i];
            ctx.beginPath();
            ctx.arc(pt.x * width, pt.y * height, 1.5, 0, 2 * Math.PI);
            ctx.fill();
        }

        // Highlight nose & eyes key markers
        const keys = [4, 33, 263, 152];
        ctx.fillStyle = isFocused ? '#10B981' : '#EF4444';
        keys.forEach(idx => {
            if (idx < landmarks.length) {
                const pt = landmarks[idx];
                ctx.beginPath();
                ctx.arc(pt.x * width, pt.y * height, 3, 0, 2 * Math.PI);
                ctx.fill();
            }
        });

        // Draw boundary box around canvas
        ctx.strokeStyle = isFocused ? 'rgba(16, 185, 129, 0.6)' : 'rgba(239, 68, 68, 0.6)';
        ctx.lineWidth = 3;
        ctx.strokeRect(4, 4, width - 8, height - 8);
    }
}

// Global reference
window.GazeTracker = GazeTracker;
