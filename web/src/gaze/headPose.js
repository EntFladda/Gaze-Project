/**
 * Head Pose Calculation
 * Improved Version
 */

class HeadPoseCalculator {
    constructor() {
        this.calibrationFrames = 0;
        this.calibrationTarget = 60;
    
        this.pitchAccumulator = 0;
        this.yawAccumulator = 0;
    
        this.pitchOffset = 0;
        this.yawOffset = 0;
    
        this.isCalibrated = false;
    
        // smoothing
        this.previousPose = null;
    }

    calculatePoseFromLandmarks(landmarks) {
        if (!landmarks || landmarks.length < 468) {
            return null;
        }

        try {
            const nose = landmarks[4];

            const leftEye = landmarks[33];
            const rightEye = landmarks[263];

            const leftCheek = landmarks[234];
            const rightCheek = landmarks[454];

            const chin = landmarks[152];

            let pitch = this.calculatePitch(
                nose,
                chin
            );

            let yaw = this.calculateYaw(
                nose,
                leftCheek,
                rightCheek
            );

            let roll = this.calculateRoll(
                leftEye,
                rightEye
            );

            // Mirror compensation
            yaw *= -1;

            // Auto calibration
            if (
                this.calibrationFrames <
                this.calibrationTarget
            ) {
                this.pitchAccumulator += pitch;
                this.yawAccumulator += yaw;

                this.calibrationFrames++;

                this.pitchOffset =
                    this.pitchAccumulator /
                    this.calibrationFrames;

                this.yawOffset =
                    this.yawAccumulator /
                    this.calibrationFrames;

                if (
                    this.calibrationFrames ===
                    this.calibrationTarget
                ) {
                    console.log(
                        '[Calibration Complete]',
                        {
                            pitchOffset:
                                this.pitchOffset,
                            yawOffset:
                                this.yawOffset
                        }
                    );
                }
            }

            pitch -= this.pitchOffset;
            yaw -= this.yawOffset;

            let pose = {
                pitch,
                yaw,
                roll,
                confidence: 0.8
            };
            pose = this.smoothPose(
                pose,
                this.previousPose
            );
            this.previousPose = pose;
            return pose;

        } catch (error) {
            console.error(
                '[HeadPose]',
                error
            );

            return null;
        }
    }

    /**
     * Pitch
     * Down = Negative
     * Up = Positive
     */
    calculatePitch(
        nose,
        chin
    ) {
        if (!nose || !chin) {
            return 0;
        }
    
        const dy =
            chin.y - nose.y;
    
        const dz =
            chin.z - nose.z;
    
        let pitch =
            Math.atan2(
                dz,
                dy
            ) *
            (180 / Math.PI);
        
        // lebih natural
        pitch *= 1.3;
        
        // dead zone
        if (
            Math.abs(pitch) < 5
        ) {
            pitch = 0;
        }
    
        pitch = Math.max(
            -90,
            Math.min(
                90,
                pitch
            )
        );
    
        return pitch;
    }

    /**
     * Yaw
     * Right = Positive
     * Left = Negative
     */
    calculateYaw(
        nose,
        leftCheek,
        rightCheek
    ) {
        if (
            !nose ||
            !leftCheek ||
            !rightCheek
        ) {
            return 0;
        }

        const leftDistance =
            Math.abs(
                nose.x -
                leftCheek.x
            );

        const rightDistance =
            Math.abs(
                rightCheek.x -
                nose.x
            );

        const ratio =
            (
                rightDistance -
                leftDistance
            ) /
            (
                rightDistance +
                leftDistance
            );

        return ratio * 80;
    }

    /**
     * Roll
     */
    calculateRoll(
        leftEye,
        rightEye
    ) {
        if (
            !leftEye ||
            !rightEye
        ) {
            return 0;
        }

        const dy =
            rightEye.y -
            leftEye.y;

        const dx =
            rightEye.x -
            leftEye.x;

        return (
            Math.atan2(
                dy,
                dx
            ) *
            (180 / Math.PI)
        );
    }

    recalibrate() {
        this.calibrationFrames = 0;

        this.pitchAccumulator = 0;
        this.yawAccumulator = 0;

        this.pitchOffset = 0;
        this.yawOffset = 0;

        this.isCalibrated = false;

        console.log(
            '[Calibration Reset]'
        );
    }

    smoothPose(
        currentPose,
        previousPose
    ) {

        if (!previousPose) {
            return currentPose;
        }

        return {

            // Pitch dibuat lebih lambat
            pitch:
                0.15 *
                    currentPose.pitch +
                0.85 *
                    previousPose.pitch,

            // Yaw tetap responsif
            yaw:
                0.3 *
                    currentPose.yaw +
                0.7 *
                    previousPose.yaw,

            // Roll sedang
            roll:
                0.25 *
                    currentPose.roll +
                0.75 *
                    previousPose.roll,

            confidence:
                currentPose.confidence
        };
    }
}

const headPose =
    new HeadPoseCalculator();