"""
Usage:
    python run_complete_pipeline.py [--skip-train] [--webcam] [--image PATH] [--fps FPS] [--display-fps DISPLAY_FPS]

Options:
    --skip-train      : Skip training, langsung ke detection (gunakan model sebelumnya)
    --webcam          : Real-time gaze detection dari webcam
    --image PATH      : Gaze detection pada gambar tertentu
    --fps FPS         : Target FPS untuk processing/detection (default: 15 fps)
    --display-fps FPS : Target FPS untuk display preview (default: 60 fps smooth)
"""

import torch
import torch.nn as nn
from torchvision import models, transforms
from torch.utils.data import DataLoader, random_split
import os
import sys
import argparse
import cv2
import numpy as np
from pathlib import Path
from datetime import datetime
import json
from multiprocessing import freeze_support
import time

# Import custom modules
from src.dataset import (
    HeadPoseDataset300WLP,
    HeadPoseDatasetMPIIGaze,
    HeadPoseDatasetLocal,
    CombinedDataset
)
from src.models import ResNet50HeadPose
from src.training import train_model, evaluate_focus
from src.training.lightning_trainer import ImprovedHeadPoseModel  # NEW: Modern model
from src.utils import setup_logger
from src.focus_tracker import FocusTracker, create_focus_tracker
import torch.nn.functional as F  # For sigmoid()


class GazeDetector:
    """Real-time gaze detection on webcam/images with focus detection"""
    
    def __init__(self, model, device, output_dir='results'):
        self.model = model
        self.device = device
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        
        self.transform = transforms.Compose([
            transforms.Resize((224, 224)),
            transforms.ToTensor(),
        ])
        
        # Temporal smoothing untuk reduce jitter (moving average across 5 frames)
        self.angle_history = []  # Store last 5 angles
        self.history_size = 5
        self.smoothing_enabled = True
        
        # Try MediaPipe first, fallback to Haar Cascade
        self.use_mediapipe = False
        self.face_detector = None
        self.mp_face_detection = None
        
        try:
            import mediapipe as mp
            if hasattr(mp, 'solutions'):
                self.mp_face_detection = mp.solutions.face_detection
                self.face_detector = self.mp_face_detection.FaceDetection(
                    model_selection=0,
                    min_detection_confidence=0.5
                )
                self.use_mediapipe = True
                print("[DETECT] Using MediaPipe for face detection")
            else:
                print("[DETECT] MediaPipe import failed, using Haar Cascade")
                raise ImportError("MediaPipe solutions not available")
        except Exception as e:
            print(f"[DETECT] MediaPipe error: {e}, using Haar Cascade")
            self.face_cascade = cv2.CascadeClassifier(
                cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
            )
    
    def calculate_focus_percentage(self, pitch, yaw, confidence=1.0):
        """
        Calculate focus percentage based on gaze angles + confidence
        - 100% = wajah lurus (pitch, yaw ≈ 0°)
        - 0% = wajah menoleh (pitch, yaw > 35°)
        - Confidence: upweight/downweight based on model confidence
        
        Threshold (KETAT untuk avoid menoleh false positive):
        - <= 15°: FOKUS (100%) if confidence > 0.5
        - 15-35°: PARSIAL FOKUS (0-100%) adjusted by confidence
        - >= 35°: TIDAK FOKUS (0%)
        """
        # Hitung magnitude angle dari pitch dan yaw
        angle_magnitude = np.sqrt(pitch**2 + yaw**2)
        
        # Threshold fokus dengan confidence adjustment
        base_focus_threshold = 15       # Ketat: batas wajah dianggap fokus
        base_blur_threshold = 35        # Ketat: batas wajah dianggap tidak fokus (dari 40 → 35)
        
        # Adjust thresholds berdasarkan confidence
        confidence_factor = 0.8 + (confidence * 0.4)  # Range [0.8, 1.2]
        focus_threshold = base_focus_threshold * confidence_factor
        blur_threshold = base_blur_threshold * confidence_factor
        
        if angle_magnitude <= focus_threshold:
            # Wajah lurus/semi lurus - FOKUS 100%
            focus_pct = 100
        elif angle_magnitude >= blur_threshold:
            # Wajah menoleh sangat jauh - TIDAK FOKUS 0%
            focus_pct = 0
        else:
            # Wajah semi menoleh - FOKUS PARSIAL
            focus_pct = int(100 * (1 - (angle_magnitude - focus_threshold) / (blur_threshold - focus_threshold)))
        
        return max(0, min(100, focus_pct))
    
    def get_focus_color(self, focus_pct):
        """Get color based on focus percentage"""
        if focus_pct >= 80:
            return (0, 255, 0)  # Green - focused
        elif focus_pct >= 50:
            return (0, 165, 255)  # Orange - partially focused
        else:
            return (0, 0, 255)  # Red - not focused
    
    def smooth_angles(self, pitch, yaw):
        """Apply temporal smoothing to reduce jitter (moving average)"""
        if not self.smoothing_enabled:
            return pitch, yaw
        
        # Add current frame to history
        self.angle_history.append((pitch, yaw))
        
        # Keep only last N frames
        if len(self.angle_history) > self.history_size:
            self.angle_history.pop(0)
        
        # Calculate moving average
        avg_pitch = np.mean([p for p, y in self.angle_history])
        avg_yaw = np.mean([y for p, y in self.angle_history])
        
        return avg_pitch, avg_yaw
    
    def detect_faces_mediapipe(self, frame):
        """Detect faces using MediaPipe"""
        if not self.use_mediapipe:
            return None
        
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
        results = self.face_detector.process(rgb_frame)
        
        faces = []
        if results.detections:
            h, w, _ = frame.shape
            for detection in results.detections:
                bbox = detection.location_data.relative_bounding_box
                x = int(bbox.xmin * w)
                y = int(bbox.ymin * h)
                width = int(bbox.width * w)
                height = int(bbox.height * h)
                
                # Ensure coordinates are within frame
                x = max(0, x)
                y = max(0, y)
                width = min(width, w - x)
                height = min(height, h - y)
                
                faces.append((x, y, width, height))
        
        return faces if faces else None
    
    def detect_faces_haar(self, frame):
        """Detect faces using Haar Cascade - RELAXED untuk better detection"""
        gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
        # Relax parameters untuk better detection: scaleFactor 1.05 (lebih sensitive), minNeighbors 3 (relaxed)
        faces = self.face_cascade.detectMultiScale(gray, scaleFactor=1.05, minNeighbors=3, minSize=(30, 30))
        
        if len(faces) == 0:
            return None
        
        # FILTER: Keep only largest face (main person) untuk avoid multiple detections
        if len(faces) > 1:
            faces = [max(faces, key=lambda f: f[2] * f[3])]  # Sort by area (width * height)
        
        return faces if len(faces) > 0 else None
    
    def predict_single(self, image_path):
        """Predict gaze dari single image"""
        img = cv2.imread(str(image_path))
        if img is None:
            print(f" Tidak bisa buka image: {image_path}")
            return None
        
        return self._process_frame(img, image_path)
    
    def _process_frame(self, frame, name="frame"):
        """Process frame dan return gaze prediction with focus detection"""
        # Try MediaPipe first, then Haar Cascade
        faces = None
        if self.use_mediapipe:
            try:
                faces = self.detect_faces_mediapipe(frame)
            except Exception as e:
                print(f"[DEBUG] MediaPipe error: {e}")
                faces = None
        
        if faces is None or len(faces) == 0:
            try:
                faces = self.detect_faces_haar(frame)
            except Exception as e:
                print(f"[DEBUG] Haar Cascade error: {e}")
                faces = None
        
        if faces is None or len(faces) == 0:
            print(f"[DEBUG] No faces detected in frame")
            return frame, []  # Return frame without detections
        
        results = []
        for (x, y, w, h) in faces:
            # Ensure crop is valid
            y_start = max(0, y)
            y_end = min(frame.shape[0], y + h)
            x_start = max(0, x)
            x_end = min(frame.shape[1], x + w)
            
            if y_end <= y_start or x_end <= x_start:
                continue
            
            # Crop face region
            face_roi = frame[y_start:y_end, x_start:x_end]
            
            try:
                # Convert to PIL and apply transform
                from PIL import Image
                face_pil = Image.fromarray(cv2.cvtColor(face_roi, cv2.COLOR_BGR2RGB))
                face_tensor = self.transform(face_pil).unsqueeze(0).to(self.device)
                
                # Predict (NEW: 3 separate outputs from ImprovedHeadPoseModel)
                with torch.no_grad():
                    pose, confidence_logits, roll_pred = self.model(face_tensor)
                
                # Denormalize [-1, 1] ke [-90, 90] degrees
                pitch = float(pose[0, 0].cpu()) * 90
                yaw = float(pose[0, 1].cpu()) * 90
                roll = float(roll_pred[0, 0].cpu()) * 90
                
                # Apply temporal smoothing untuk reduce jitter
                pitch, yaw = self.smooth_angles(pitch, yaw)
                
                # Convert confidence logits to probability [0, 1]
                confidence = float(torch.sigmoid(confidence_logits[0, 0]).cpu())
                
                # Calculate focus percentage with confidence weighting
                focus_pct = self.calculate_focus_percentage(pitch, yaw, confidence)
                focus_color = self.get_focus_color(focus_pct)
                
                results.append({
                    'bbox': (x_start, y_start, x_end - x_start, y_end - y_start),
                    'pitch': pitch,
                    'yaw': yaw,
                    'roll': roll,
                    'focus': focus_pct
                })
                
                # Draw bounding box dengan warna berdasarkan fokus
                cv2.rectangle(frame, (x_start, y_start), (x_end, y_end), focus_color, 2)
                
                # Draw gaze info
                text1 = f"Pitch: {pitch:.1f}° Yaw: {yaw:.1f}°"
                text2 = f"Focus: {focus_pct}%"
                
                cv2.putText(frame, text1, (x_start, y_start-30), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.6, focus_color, 2)
                cv2.putText(frame, text2, (x_start, y_start-10), 
                           cv2.FONT_HERSHEY_SIMPLEX, 0.7, focus_color, 2)
                
            except Exception as e:
                print(f"[DEBUG] Face processing error: {e}")
                import traceback
                traceback.print_exc()
                continue
        
        return frame, results
    
    def run_webcam(self, duration=30, target_fps=15, display_fps=60):
        """
        Real-time gaze detection dari webcam dengan dual FPS
        
        Args:
            duration: Duration in seconds
            target_fps: Target FPS untuk processing/detection (default 15 fps untuk akurasi baik)
            display_fps: Target FPS untuk display preview (default 60 fps untuk smooth)
        """
        print(f"\n[DETECT] Opening webcam for {duration} seconds...")
        print(f"[DETECT] Display FPS: {display_fps} (smooth preview)")
        print(f"[DETECT] Processing FPS: {target_fps} (akurat detection)")
        print("[DETECT] Press Q to exit, S to save screenshot")
        
        cap = cv2.VideoCapture(0)
        if not cap.isOpened():
            print("[DETECT] ERROR: Cannot open webcam!")
            return
        
        # Setup video writer dengan codec yang lebih compatible
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        output_video = self.output_dir / f"gaze_detection_{timestamp}.avi"
        
        frame_width = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
        frame_height = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))
        fps = int(cap.get(cv2.CAP_PROP_FPS)) or 30  # Default to 30 if 0
        
        # Use MJPEG codec (more compatible)
        fourcc = cv2.VideoWriter_fourcc(*'MJPG')
        out = cv2.VideoWriter(str(output_video), fourcc, fps, (frame_width, frame_height))
        
        if not out.isOpened():
            print("[DETECT] Video writer failed, trying XVID codec...")
            # Fallback to XVID
            fourcc = cv2.VideoWriter_fourcc(*'XVID')
            output_video = self.output_dir / f"gaze_detection_{timestamp}.avi"
            out = cv2.VideoWriter(str(output_video), fourcc, fps, (frame_width, frame_height))
        
        start_time = datetime.now()
        frame_count = 0
        process_count = 0  # Track processed frames
        focus_tracker = create_focus_tracker(
            focus_threshold_high=80,    # Focus >= 80% = FOCUSED
            focus_threshold_low=50,     # Focus < 50% = NOT_FOCUSED
            duration_threshold=0.3      # Minimal 0.3 detik untuk period yang meaningful
        )
        last_result_frame = None  # Cache last processed frame
        last_detections = None  # Cache last detections
        
        # FPS timing setup
        display_delay = 1.0 / display_fps  # Display delay (~16ms for 60fps)
        process_delay = 1.0 / target_fps   # Process delay (~66ms for 15fps)
        
        last_display_time = time.time()
        last_process_time = time.time()
        
        try:
            while True:
                ret, frame = cap.read()
                if not ret:
                    break
                
                # Resize untuk performa dan display
                frame_display = cv2.resize(frame, (1024, 768))
                current_time = time.time()
                
                # ===== PROCESSING (15 fps) =====
                if (current_time - last_process_time) >= process_delay:
                    # Process frame untuk deteksi
                    result_frame, detections = self._process_frame(frame_display, f"frame_{process_count}")
                    last_result_frame = result_frame.copy()
                    last_detections = detections
                    
                    # Track focus dengan advanced tracker
                    if detections:
                        for det in detections:
                            focus_tracker.add_frame(
                                focus_percentage=det['focus'],
                                pitch=det['pitch'],
                                yaw=det['yaw'],
                                face_detected=True,
                                timestamp=datetime.now()
                            )
                    else:
                        # No face detected
                        focus_tracker.add_frame(
                            focus_percentage=0,
                            pitch=0,
                            yaw=0,
                            face_detected=False,
                            timestamp=datetime.now()
                        )
                    
                    # Write to video
                    try:
                        out.write(result_frame)
                    except Exception as e:
                        print(f"[DETECT] Video write error (frame {process_count}): {str(e)[:50]}")
                    
                    process_count += 1
                    last_process_time = current_time
                
                # ===== DISPLAY (60 fps) =====
                if (current_time - last_display_time) >= display_delay:
                    # Display frame (gunakan last result jika belum diproses)
                    display_frame = last_result_frame if last_result_frame is not None else frame_display
                    cv2.imshow('Gaze Detection - Press Q to exit, S to screenshot', display_frame)
                    last_display_time = current_time
                
                frame_count += 1
                
                # Check time
                elapsed = (datetime.now() - start_time).total_seconds()
                if elapsed > duration:
                    break
                
                # Non-blocking key check dengan waitKey 1ms untuk display refresh
                key = cv2.waitKey(1) & 0xFF
                if key == ord('q'):
                    break
                elif key == ord('s'):
                    if last_result_frame is not None:
                        screenshot_path = self.output_dir / f"screenshot_{timestamp}_{process_count}.png"
                        cv2.imwrite(str(screenshot_path), last_result_frame)
                        print(f"[DETECT] Screenshot saved: {screenshot_path}")
        
        finally:
            cap.release()
            out.release()
            cv2.destroyAllWindows()
        
        # Calculate statistics dengan advanced focus tracking
        report = focus_tracker.get_final_report()
        
        print(focus_tracker.get_summary_string())
        focus_tracker.print_detailed_periods()
        
        print(f"\n[DETECT] Video saved: {output_video}")
        print(f"[DETECT] Total frames captured: {frame_count}")
        print(f"[DETECT] Total frames processed: {process_count}")
        print(f"[DETECT] Display FPS avg: {frame_count / ((datetime.now() - start_time).total_seconds() or 1):.1f}")
        print(f"[DETECT] Processing FPS avg: {process_count / ((datetime.now() - start_time).total_seconds() or 1):.1f}")
        
        # Save detailed report to JSON
        report_path = self.output_dir / f"focus_report_{timestamp}.json"
        with open(report_path, 'w') as f:
            json.dump(report, f, indent=2)
        print(f"\n[DETECT] Detailed report saved: {report_path}")



def load_model(checkpoint_path=None, device='cpu'):
    """Load model dari checkpoint atau create new"""
    # Use NEW ImprovedHeadPoseModel untuk support confidence mechanism
    model = ImprovedHeadPoseModel(backbone='resnet50').to(device)
    
    if checkpoint_path and os.path.exists(checkpoint_path):
        print(f"[MODEL] Loading checkpoint: {checkpoint_path}")
        try:
            model.load_state_dict(torch.load(checkpoint_path, map_location=device, weights_only=False))
            print("[MODEL] Model loaded successfully (ImprovedHeadPoseModel)")
        except Exception as e:
            print(f"[MODEL] ERROR loading model: {e}")
            raise
    else:
        print("[MODEL] WARNING: Checkpoint not found, using untrained model")
    
    # CRITICAL: Set model to eval mode for inference (BatchNorm uses running stats, not batch stats)
    model.eval()
    print("[MODEL] Model set to eval mode for inference")
    
    return model


def setup_datasets(transform):
    """Setup dan load semua datasets"""
    
    USE_300W_LP = True
    USE_MPIIGAZE = True   # Enabled: menggunakan semua dataset
    USE_LOCAL = False     # Disabled: dihapus sesuai permintaan
    
    print("[LOAD] Scanning dataset folders...")
    print("[LOAD] 300W-LP: ENABLED")
    print("[LOAD] MPIIGaze: ENABLED")
    print("[LOAD] Local: DISABLED")
    
    datasets = []
    
    # Load 300W-LP
    if USE_300W_LP:
        try:
            print(f"\n[LOAD] Loading 300W-LP dataset...")
            ds_300w = HeadPoseDataset300WLP(
                root_dir='data/300W_LP',
                transform=transform,
                cache_metadata=True
            )
            if len(ds_300w) > 0:
                datasets.append(ds_300w)
                print(f"[LOAD] 300W-LP loaded: {len(ds_300w)} samples OK")
        except Exception as e:
            print(f"[LOAD] 300W-LP ERROR: {str(e)[:60]}")
    
    # Load MPIIGaze
    if USE_MPIIGAZE:
        try:
            print(f"\n[LOAD] Loading MPIIGaze dataset...")
            ds_mpiigaze = HeadPoseDatasetMPIIGaze(
                root_dir='data/MPIIGaze',
                use_normalized=True,
                transform=transform,
                cache_metadata=True
            )
            if len(ds_mpiigaze) > 0:
                datasets.append(ds_mpiigaze)
                print(f"[LOAD] MPIIGaze loaded: {len(ds_mpiigaze)} samples OK")
            else:
                print(f"[LOAD] MPIIGaze EMPTY: 0 samples (likely no Gaze field in .mat files)")
        except Exception as e:
            print(f"[LOAD] MPIIGaze ERROR: {str(e)[:60]}")
    
    # Load Local
    if USE_LOCAL:
        try:
            print(f"\n[LOAD] Loading Local dataset...")
            ds_local = HeadPoseDatasetLocal(
                root_dir='data/Local',
                transform=transform,
                cache_metadata=True
            )
            if len(ds_local) > 0:
                datasets.append(ds_local)
                print(f"[LOAD] Local loaded: {len(ds_local)} samples OK")
        except Exception as e:
            print(f"[LOAD] Local ERROR: {str(e)[:60]}")
    
    if not datasets:
        raise ValueError("[LOAD] ERROR: No datasets loaded successfully!")
    
    # Combine
    if len(datasets) > 1:
        dataset = CombinedDataset(datasets)
        print(f"\n[LOAD] Combined {len(datasets)} datasets")
    else:
        dataset = datasets[0]
        print(f"\n[LOAD] Using single dataset")
    
    return dataset


def train_pipeline(device, epochs=10, subset_percent=25):
    """Run training pipeline dengan multiple epochs menggunakan dataset subset untuk kecepatan
    
    Args:
        epochs: Jumlah epochs per iterasi (default 10 untuk convergence lebih baik)
        subset_percent: Persentase dataset yang digunakan (default 25% untuk epoch cepat)
    """
    
    print("\n" + "="*70)
    print("[TRAIN] TRAINING PIPELINE STARTING")
    print("="*70)
    
    # ========== STEP 1: SETUP ==========
    print(f"\n[TRAIN STEP 1/5] Setting up training environment...")
    NUM_WORKERS = 0  # Windows: synchronous loading is fastest
    print(f"[TRAIN] Using {NUM_WORKERS} workers (Windows optimal)")
    
    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
    ])
    
    # ========== STEP 2: LOAD DATASETS ==========
    print(f"\n[TRAIN STEP 2/5] Loading datasets...")
    dataset = setup_datasets(transform)
    print(f"[TRAIN] Total dataset size: {len(dataset)} samples")
    
    # ========== STEP 3: PREPARE DATALOADERS ==========
    print(f"\n[TRAIN STEP 3/5] Preparing data loaders...")
    
    # Use subset untuk faster epochs
    if subset_percent < 100:
        subset_size = int(len(dataset) * subset_percent / 100)
        subset_indices = torch.randperm(len(dataset))[:subset_size]
        dataset_subset = torch.utils.data.Subset(dataset, subset_indices)
        print(f"[TRAIN] Using {subset_percent}% subset: {subset_size} samples (dari {len(dataset)}）")
    else:
        dataset_subset = dataset
        print(f"[TRAIN] Using full dataset: {len(dataset)} samples")
    
    # Split
    train_size = int(0.8 * len(dataset_subset))
    val_size = len(dataset_subset) - train_size
    train_ds, val_ds = random_split(dataset_subset, [train_size, val_size])
    
    # DataLoader - Gunakan batch size lebih besar untuk durasi singkat per epoch
    if torch.cuda.is_available():
        batch_size = 32  # Larger batches = faster epochs, lebih efisien
        pin_memory = True
    else:
        batch_size = 16
        pin_memory = False
    
    train_loader = DataLoader(
        train_ds, batch_size=batch_size, shuffle=True,
        num_workers=NUM_WORKERS, pin_memory=pin_memory,
        persistent_workers=True if NUM_WORKERS > 0 else False,
        prefetch_factor=4 if NUM_WORKERS > 0 else None
    )
    
    val_loader = DataLoader(
        val_ds, batch_size=batch_size,
        num_workers=NUM_WORKERS, pin_memory=pin_memory,
        persistent_workers=True if NUM_WORKERS > 0 else False,
        prefetch_factor=4 if NUM_WORKERS > 0 else None
    )
    
    print(f"[TRAIN] Train samples: {len(train_ds)}")
    print(f"[TRAIN] Val samples: {len(val_ds)}")
    print(f"[TRAIN] Batch Size: {batch_size}")
    print(f"[TRAIN] Workers: {NUM_WORKERS}")
    print(f"[TRAIN] Total batches per epoch: {len(train_loader)}")
    
    # ========== STEP 4: TRAIN MODEL (LOOP DENGAN 1 EPOCH PER ITERASI) ==========
    print(f"\n[TRAIN STEP 4/5] Training model dengan 1 epoch per iterasi...")
    print(f"[TRAIN] Looping otomatis jika akurasi < 80%")
    print(f"[TRAIN] Target 1: Focus Accuracy >= 80% (STOP)")
    print(f"[TRAIN] Target 2: Focus Accuracy >= 90% (IDEAL - jika tercapai akan berhenti)")
    print(f"{'='*70}")
    
    # Training loop: terus latih sampai mencapai 80% fokus accuracy
    training_iteration = 0
    best_overall_accuracy = 0
    best_model_state = None
    min_target_accuracy = 80.0
    ideal_target_accuracy = 90.0
    
    while True:
        training_iteration += 1
        print(f"\n[TRAIN] ===== TRAINING ITERATION {training_iteration} =====")
        print(f"[TRAIN] Min Target: {min_target_accuracy}% | Ideal: {ideal_target_accuracy}% | Current Best: {best_overall_accuracy:.1f}%")
        
        print(f"[TRAIN] Creating ResNet50 model...")
        model = ResNet50HeadPose().to(device)
        print(f"[TRAIN] Model created and moved to device")
        print(f"[TRAIN] Starting training loop...")
        
        # Train dan dapatkan best accuracy dari training ini
        try:
            epoch_best_accuracy = train_model(model, train_loader, val_loader, epochs=epochs, device=device)
        except KeyboardInterrupt:
            print(f"\n[TRAIN] ️  Training dihentikan oleh user (Ctrl+C)")
            print(f"[TRAIN] Accuracy saat ini: {best_overall_accuracy:.1f}%")
            if best_overall_accuracy >= min_target_accuracy:
                print(f"[TRAIN] Saving model dengan accuracy: {best_overall_accuracy:.1f}%")
                break
            else:
                print(f"[TRAIN] Accuracy belum mencapai target {min_target_accuracy}%")
                response = input("[TRAIN] Lanjutkan training? (y/n): ").strip().lower()
                if response == 'y':
                    continue
                else:
                    break
        
        print(f"[TRAIN] Training iteration {training_iteration} completed")
        print(f"[TRAIN] Accuracy dari iteration ini: {epoch_best_accuracy:.1f}%")
        
        # Update best overall accuracy
        if epoch_best_accuracy > best_overall_accuracy:
            best_overall_accuracy = epoch_best_accuracy
            best_model_state = model.state_dict().copy()
            print(f" NEW BEST: {best_overall_accuracy:.1f}%")
        
        # Check apakah sudah mencapai target 80% atau lebih
        if epoch_best_accuracy >= min_target_accuracy:
            print(f"\n{'='*70}")
            if epoch_best_accuracy >= ideal_target_accuracy:
                print(f" IDEAL TARGET TERCAPAI! Focus Accuracy: {epoch_best_accuracy:.1f}% >= {ideal_target_accuracy}%")
                print(f"[TRAIN] Menghentikan training loop (TARGET IDEAL TERPENUHI)")
            else:
                print(f" MINIMUM TARGET TERCAPAI! Focus Accuracy: {epoch_best_accuracy:.1f}% >= {min_target_accuracy}%")
                print(f"[TRAIN] Menghentikan training loop")
            print(f"{'='*70}")
            break
        else:
            print(f" Belum mencapai target minimum ({epoch_best_accuracy:.1f}% < {min_target_accuracy}%)")
            print(f"[TRAIN] Training iteration berikutnya...")
            print(f"{'='*70}")
    
    # Load best model state
    if best_model_state is not None:
        model.load_state_dict(best_model_state)
        print(f"[TRAIN] Loaded best model state dengan accuracy: {best_overall_accuracy:.1f}%")
    
    print(f"{'='*70}")
    print(f"[TRAIN] Training completed setelah {training_iteration} iterasi dengan Best Accuracy: {best_overall_accuracy:.1f}%")
    
    # ========== STEP 5: EVALUATE & SAVE ==========
    print(f"\n[TRAIN STEP 5/5] Evaluating and saving model...")
    
    evaluate_focus(model, val_loader, device=device)
    
    # Save model
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    checkpoint_dir = Path('checkpoints')
    checkpoint_dir.mkdir(parents=True, exist_ok=True)
    checkpoint_path = checkpoint_dir / f"gaze_model_{timestamp}.pt"
    
    torch.save(model.state_dict(), checkpoint_path)
    print(f"[TRAIN] Model checkpoint saved: {checkpoint_path}")
    print(f"[TRAIN] Checkpoint size: {os.path.getsize(checkpoint_path) / 1e6:.1f} MB")
    
    print(f"\n[TRAIN] SUCCESS - Training pipeline completed")
    print(f"{'='*70}")
    
    return model, checkpoint_path


def detection_pipeline(model, device, args):
    """Run gaze detection"""
    
    print("\n" + "="*70)
    print("[DETECT] DETECTION PIPELINE STARTING")
    print("="*70)
    
    detector = GazeDetector(model, device)
    
    if args.webcam:
        print(f"\n[DETECT] Mode: WEBCAM")
        duration = args.duration if hasattr(args, 'duration') else 30
        target_fps = args.fps if hasattr(args, 'fps') else 15
        display_fps = args.display_fps if hasattr(args, 'display_fps') else 60
        print(f"[DETECT] Duration: {duration} seconds")
        print(f"[DETECT] Processing FPS: {target_fps}")
        print(f"[DETECT] Display FPS: {display_fps}")
        print(f"[DETECT] Press Q to exit, S to screenshot")
        
        try:
            detector.run_webcam(duration=duration, target_fps=target_fps, display_fps=display_fps)
            print(f"[DETECT] SUCCESS - Webcam detection completed")
        except Exception as e:
            print(f"[DETECT] ERROR - {e}")
    
    elif args.image:
        print(f"\n[DETECT] Mode: IMAGE")
        print(f"[DETECT] Processing: {args.image}")
        
        try:
            result = detector.predict_single(args.image)
            
            if result:
                frame, detections = result
                
                # Save
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                output_path = Path('results') / f"gaze_detection_{timestamp}.png"
                output_path.parent.mkdir(parents=True, exist_ok=True)
                cv2.imwrite(str(output_path), frame)
                
                print(f"\n[DETECT] Results:")
                print(f"[DETECT] Faces detected: {len(detections)}")
                for i, det in enumerate(detections):
                    print(f"[DETECT]   Face {i+1}:")
                    print(f"[DETECT]     Pitch: {det['pitch']:.2f}°")
                    print(f"[DETECT]     Yaw: {det['yaw']:.2f}°")
                    print(f"[DETECT]     Focus: {det['focus']}%")
                print(f"[DETECT] Image saved: {output_path}")
        except Exception as e:
            print(f"[DETECT] ERROR - {e}")
    else:
        # Default: demo mode dengan gambar random dari validation set
        print("\n[DETECT] Mode: DEMO (using random images from dataset)")
        print("[DETECT] Loading test set for demo...")
        
        # Setup datasets untuk demo
        transform = transforms.Compose([
            transforms.Resize((224, 224)),
            transforms.ToTensor(),
        ])
        
        try:
            dataset = setup_datasets(transform)
            train_size = int(0.8 * len(dataset))
            val_size = len(dataset) - train_size
            _, val_ds = random_split(dataset, [train_size, val_size])
            
            # Ambil 5 gambar random
            import random
            indices = random.sample(range(len(val_ds)), min(5, len(val_ds)))
            
            for idx in indices:
                image, label = val_ds[idx]
                
                # Predict
                image_input = image.unsqueeze(0).to(device)
                with torch.no_grad():
                    output = model(image_input)
                
                pitch = float(output[0, 0].cpu()) * 90
                yaw = float(output[0, 1].cpu()) * 90
                roll = float(output[0, 2].cpu()) * 90
                
                gt_pitch = float(label[0].cpu()) * 90
                gt_yaw = float(label[1].cpu()) * 90
                gt_roll = float(label[2].cpu()) * 90
                
                print(f"\n[DEMO] Sample {idx}:")
                print(f"[DEMO] Predicted: Pitch={pitch:.1f}° Yaw={yaw:.1f}° Roll={roll:.1f}°")
                print(f"[DEMO] Ground Truth: Pitch={gt_pitch:.1f}° Yaw={gt_yaw:.1f}° Roll={gt_roll:.1f}°")
        
        except Exception as e:
            print(f"[DEMO] ERROR: {e}")


def main():
    parser = argparse.ArgumentParser(
        description='Complete Gaze Focus Pipeline: Training + Detection',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Training dengan 10 epochs, 25% subset (cepat)
  python run_complete_pipeline.py
  
  # Training dengan 20 epochs, 50% subset (balanced)
  python run_complete_pipeline.py --epochs 20 --subset 50
  
  # Training dengan 5 epochs, 100% full dataset (slow but accurate)
  python run_complete_pipeline.py --epochs 5 --subset 100
  
  # Skip training, langsung webcam (15 fps processing, 60 fps display)
  python run_complete_pipeline.py --skip-train --webcam
  
  # Webcam dengan processing 15 fps, display 60 fps (default)
  python run_complete_pipeline.py --skip-train --webcam --fps 15 --display-fps 60
  
  # Detect gaze pada image tertentu
  python run_complete_pipeline.py --skip-train --image test.jpg
        """
    )
    
    parser.add_argument('--skip-train', action='store_true',
                       help='Skip training, gunakan model sebelumnya')
    parser.add_argument('--webcam', action='store_true',
                       help='Real-time detection dari webcam')
    parser.add_argument('--image', type=str,
                       help='Detect gaze pada image path')
    parser.add_argument('--duration', type=int, default=30,
                       help='Durasi webcam detection (detik)')
    parser.add_argument('--fps', type=int, default=15,
                       help='Target FPS untuk processing/detection (default: 15 fps)')
    parser.add_argument('--display-fps', type=int, default=60,
                       help='Target FPS untuk display preview (default: 60 fps smooth)')
    parser.add_argument('--epochs', type=int, default=10,
                       help='Jumlah epochs per iterasi training (default: 10 untuk convergence lebih baik)')
    parser.add_argument('--subset', type=int, default=25,
                       help='Persentase dataset untuk training (1-100, default: 25%% = epoch cepat)')
    parser.add_argument('--checkpoint', type=str,
                       help='Path ke model checkpoint')
    
    args = parser.parse_args()
    
    # Setup
    print("="*70)
    print("[PIPELINE] GAZE FOCUS COMPLETE TRAINING & DETECTION")
    print("="*70)
    
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    print(f"\n[SYSTEM INFO]")
    if torch.cuda.is_available():
        print(f"  [GPU] {torch.cuda.get_device_name(0)}")
        print(f"  [CUDA] {torch.version.cuda}")
        print(f"  [MEMORY] {torch.cuda.get_device_properties(0).total_memory / 1e9:.1f} GB")
    else:
        print(f"  [MODE] CPU")
    print(f"  [CORES] {os.cpu_count()}")
    
    # ========== STEP 1: TRAINING ==========
    print(f"\n{'='*70}")
    print("[STEP 1] TRAINING PHASE")
    print(f"{'='*70}")
    
    if not args.skip_train:
        try:
            print(f"[TRAIN] Starting training dengan {args.epochs} epochs, subset {args.subset}%...")
            model, checkpoint_path = train_pipeline(device, epochs=args.epochs, subset_percent=args.subset)
            print(f"[TRAIN] SUCCESS - Model saved to {checkpoint_path}")
        except Exception as e:
            print(f"[TRAIN] ERROR - {e}")
            print("[TRAIN] Attempting to load previous model...")
            model = load_model(args.checkpoint, device)
    else:
        print("[TRAIN] SKIPPED - Using previous model")
        # Cek model baru dari train_head_pose_modern.py terlebih dahulu
        if args.checkpoint:
            checkpoint_path = args.checkpoint
        elif os.path.exists('checkpoints/final/head_pose_best.pt'):
            checkpoint_path = 'checkpoints/final/head_pose_best.pt'
            print("[TRAIN] Found new model: checkpoints/final/head_pose_best.pt")
        else:
            checkpoint_path = 'checkpoints/gaze_model_latest.pt'
        model = load_model(checkpoint_path, device)
    
    # ========== STEP 2: DETECTION ==========
    print(f"\n{'='*70}")
    print("[STEP 2] DETECTION PHASE")
    print(f"{'='*70}")
    
    try:
        print("[DETECT] Starting real-time gaze detection...")
        detection_pipeline(model, device, args)
        print("[DETECT] SUCCESS - Detection completed")
    except Exception as e:
        print(f"[DETECT] ERROR - {e}")
        import traceback
        traceback.print_exc()
    
    print(f"\n{'='*70}")
    print("[RESULT] PIPELINE COMPLETED SUCCESSFULLY")
    print(f"{'='*70}")


if __name__ == '__main__':
    freeze_support()
    main()
