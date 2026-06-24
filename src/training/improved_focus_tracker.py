#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
🎯 Improved Focus Tracker dengan Adaptive Thresholds
Solusi untuk masalah detection "menoleh masih dihitung fokus"
"""

import numpy as np
from datetime import datetime, timedelta
from enum import Enum
from dataclasses import dataclass
from typing import List, Dict, Optional, Tuple
import cv2


class FocusState(Enum):
    """States for focus tracking"""
    FOCUSED = "focused"           # Mata lurus, fokus sempurna
    PARTIALLY_FOCUSED = "partial" # Mata sedikit terputar
    NOT_FOCUSED = "unfocused"     # Mata jauh menoleh
    IGNORED = "ignored"           # Aktivitas diabaikan (minum, dll)
    NO_FACE = "no_face"           # Wajah tidak terdeteksi


@dataclass
class FocusFrame:
    """Data untuk setiap frame"""
    timestamp: datetime
    focus_percentage: float
    pitch: float
    yaw: float
    confidence: float  # NEW! Model confidence (0-1)
    state: FocusState
    activity_type: str = "normal"
    face_detected: bool = True
    
    @property
    def angle_magnitude(self) -> float:
        """Compute angle magnitude on the fly"""
        return np.sqrt(self.pitch**2 + self.yaw**2)


class ImprovedFocusTracker:
    """
    Improved focus tracking dengan:
    1. Adaptive thresholds berdasarkan confidence
    2. Lebih strict untuk detection menoleh
    3. Historical averaging untuk smooth results
    4. Multi-cue detection (pitch, yaw, magnitude, velocity)
    """
    
    def __init__(self,
                 focus_threshold_high=70,      # Stricter dari 80%
                 focus_threshold_low=40,       # Stricter dari 50%
                 min_focus_angle=20,           # Derajat untuk fokus sempurna
                 max_focus_angle=40,           # Derajat untuk partial fokus
                 max_unfocus_angle=70,         # Derajat untuk tidak fokus
                 confidence_threshold=0.5,     # Min confidence untuk dihitung
                 use_adaptive_thresholds=True):
        
        self.focus_threshold_high = focus_threshold_high
        self.focus_threshold_low = focus_threshold_low
        self.min_focus_angle = min_focus_angle
        self.max_focus_angle = max_focus_angle
        self.max_unfocus_angle = max_unfocus_angle
        self.confidence_threshold = confidence_threshold
        self.use_adaptive_thresholds = use_adaptive_thresholds
        
        self.frames: List[FocusFrame] = []
        self.start_time: Optional[datetime] = None
        self.last_frame_time: Optional[datetime] = None
        
        # Untuk adaptive thresholds
        self.confidence_history = []
        self.angle_magnitude_history = []
    
    def add_frame_with_confidence(self,
                                 focus_percentage: float,
                                 pitch: float,
                                 yaw: float,
                                 confidence: float,  # NEW! Model confidence
                                 face_detected: bool = True,
                                 timestamp: Optional[datetime] = None):
        """
        Add frame dengan model confidence
        
        Args:
            focus_percentage: Model's predicted focus % (deprecated, using angle now)
            pitch: Head pitch angle (derajat)
            yaw: Head yaw angle (derajat)
            confidence: Model confidence - raw logits from model (will be sigmoid'd)
            face_detected: Apakah wajah terdeteksi
            timestamp: Frame timestamp
        """
        # Apply sigmoid to convert logits to 0-1 range
        # (BCEWithLogitsLoss outputs raw logits, not sigmoid)
        confidence = 1.0 / (1.0 + np.exp(-confidence))  # Sigmoid
        confidence = np.clip(confidence, 0, 1)  # Clamp to [0, 1]
        if timestamp is None:
            if self.last_frame_time is None:
                timestamp = datetime.now()
            else:
                timestamp = self.last_frame_time + timedelta(seconds=1/15)
        
        if self.start_time is None:
            self.start_time = timestamp
        
        self.last_frame_time = timestamp
        
        # Calculate angle magnitude dari pitch dan yaw
        angle_magnitude = np.sqrt(pitch**2 + yaw**2)
        
        # Determine state berdasarkan ANGLE, bukan focus%
        state = self._determine_state_from_angles(
            angle_magnitude, pitch, yaw, confidence
        )
        
        # Track confidence untuk adaptive thresholds
        self.confidence_history.append(confidence)
        self.angle_magnitude_history.append(angle_magnitude)
        
        frame = FocusFrame(
            timestamp=timestamp,
            focus_percentage=focus_percentage,  # Tetap disimpan untuk compatibility
            pitch=pitch,
            yaw=yaw,
            confidence=confidence,
            state=state,
            face_detected=face_detected
        )
        
        self.frames.append(frame)
    
    def _determine_state_from_angles(self,
                                    angle_magnitude: float,
                                    pitch: float,
                                    yaw: float,
                                    confidence: float) -> FocusState:
        """
        Determine focus state berdasarkan angles + confidence
        BUKAN berdasarkan focus_percentage yang error
        
        Key insight: Jika angle besar (menoleh) tapi confidence tinggi,
        model sedang confident dia menoleh, jadi NOT_FOCUSED bukan FOCUSED!
        """
        
        if not self.confidence_history:
            # First frame - just use angle
            if angle_magnitude <= self.min_focus_angle:
                return FocusState.FOCUSED
            elif angle_magnitude <= self.max_focus_angle:
                return FocusState.PARTIALLY_FOCUSED
            else:
                return FocusState.NOT_FOCUSED
        
        # Get adaptive thresholds based on confidence history
        if self.use_adaptive_thresholds:
            avg_confidence = np.mean(self.confidence_history[-30:])  # Last 30 frames
        else:
            avg_confidence = 1.0
        
        # Confidence adjustment: lebih strict kalau confidence tinggi
        confidence_factor = max(0.8, confidence)  # Min 0.8x strictness
        adjusted_min_focus = self.min_focus_angle * confidence_factor
        adjusted_max_focus = self.max_focus_angle * confidence_factor
        adjusted_max_unfocus = self.max_unfocus_angle * confidence_factor
        
        # Decision logic berdasarkan angle magnitude
        if angle_magnitude <= adjusted_min_focus:
            state = FocusState.FOCUSED
        elif angle_magnitude <= adjusted_max_focus:
            state = FocusState.PARTIALLY_FOCUSED
        else:
            state = FocusState.NOT_FOCUSED
        
        # Double-check dengan velocity (sudden movement = suspicious)
        if len(self.angle_magnitude_history) > 1:
            velocity = abs(angle_magnitude - self.angle_magnitude_history[-1])
            if velocity > 5.0:  # >5° per frame = sudden movement
                # Downgrade fokus state if moving
                if state == FocusState.FOCUSED:
                    state = FocusState.PARTIALLY_FOCUSED
                elif state == FocusState.PARTIALLY_FOCUSED and angle_magnitude > 30:
                    state = FocusState.NOT_FOCUSED
        
        return state
    
    def get_summary_report(self) -> Dict:
        """Get comprehensive focus report"""
        if not self.frames:
            return {}
        
        total_duration = (self.last_frame_time - self.start_time).total_seconds()
        
        focused_count = sum(1 for f in self.frames if f.state == FocusState.FOCUSED)
        partial_count = sum(1 for f in self.frames if f.state == FocusState.PARTIALLY_FOCUSED)
        unfocused_count = sum(1 for f in self.frames if f.state == FocusState.NOT_FOCUSED)
        ignored_count = sum(1 for f in self.frames if f.state == FocusState.IGNORED)
        no_face_count = sum(1 for f in self.frames if f.state == FocusState.NO_FACE)
        
        total_counted = focused_count + partial_count + unfocused_count
        
        if total_counted > 0:
            focus_score = (focused_count + partial_count * 0.5) / total_counted * 100
        else:
            focus_score = 0
        
        # Angle statistics
        valid_angles = [f.angle_magnitude for f in self.frames if f.face_detected]
        if valid_angles:
            avg_angle = np.mean(valid_angles)
            max_angle = np.max(valid_angles)
            min_angle = np.min(valid_angles)
        else:
            avg_angle = max_angle = min_angle = 0
        
        # Confidence statistics
        valid_confidence = [f.confidence for f in self.frames if f.face_detected]
        if valid_confidence:
            avg_confidence = np.mean(valid_confidence)
            min_confidence = np.min(valid_confidence)
        else:
            avg_confidence = min_confidence = 0
        
        return {
            'total_duration_seconds': round(total_duration, 2),
            'total_frames': len(self.frames),
            'focused_frames': focused_count,
            'partial_frames': partial_count,
            'unfocused_frames': unfocused_count,
            'ignored_frames': ignored_count,
            'no_face_frames': no_face_count,
            'focus_score': round(focus_score, 1),
            'avg_head_angle': round(avg_angle, 2),
            'max_head_angle': round(max_angle, 2),
            'min_head_angle': round(min_angle, 2),
            'avg_confidence': round(avg_confidence, 3),
            'min_confidence': round(min_confidence, 3),
        }
    
    def print_report(self):
        """Print human-readable report"""
        report = self.get_summary_report()
        
        print(f"""
╔════════════════════════════════════════════════╗
║     📊 IMPROVED FOCUS ANALYSIS REPORT          ║
╠════════════════════════════════════════════════╣
║ Total Duration           : {report.get('total_duration_seconds', 0):>6.1f} s │
║ Total Frames             : {report.get('total_frames', 0):>6.0f}   │
║                                                ║
║ 🟢 Focused               : {report.get('focused_frames', 0):>6.0f}   │
║ 🟡 Partially Focused     : {report.get('partial_frames', 0):>6.0f}   │
║ 🔴 Not Focused           : {report.get('unfocused_frames', 0):>6.0f}   │
║ ⚪ Ignored               : {report.get('ignored_frames', 0):>6.0f}   │
║ ❌ No Face               : {report.get('no_face_frames', 0):>6.0f}   │
║                                                ║
║ ✅ FOCUS SCORE           : {report.get('focus_score', 0):>6.1f} % │
║                                                ║
║ Head Angle Stats:                              ║
║   Average                : {report.get('avg_head_angle', 0):>6.1f}°  │
║   Maximum                : {report.get('max_head_angle', 0):>6.1f}°  │
║   Minimum                : {report.get('min_head_angle', 0):>6.1f}°  │
║                                                ║
║ Model Confidence:                              ║
║   Average                : {report.get('avg_confidence', 0):>6.3f}   │
║   Minimum                : {report.get('min_confidence', 0):>6.3f}   │
╚════════════════════════════════════════════════╝
""")
    
    # Helper property untuk compatibility
    @property
    def frames_as_list(self) -> List[Dict]:
        return [
            {
                'timestamp': f.timestamp.isoformat(),
                'pitch': f.pitch,
                'yaw': f.yaw,
                'confidence': f.confidence,
                'state': f.state.value,
                'face_detected': f.face_detected
            }
            for f in self.frames
        ]
