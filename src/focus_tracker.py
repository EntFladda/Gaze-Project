"""
🎯 Advanced Focus Tracking System
Tracks focus state with duration, activity detection, and averaging
"""

import numpy as np
from datetime import datetime, timedelta
from enum import Enum
from dataclasses import dataclass
from typing import List, Dict, Optional
import cv2


class FocusState(Enum):
    """States for focus tracking"""
    FOCUSED = "focused"           # Mata lurus, fokus sempurna
    PARTIALLY_FOCUSED = "partial" # Mata sedikit terputar
    NOT_FOCUSED = "unfocused"     # Mata jauh menoleh
    IGNORED = "ignored"           # Aktivitas diabaikan (minum, dll)
    NO_FACE = "no_face"           # Wajah tidak terdeteksi


class ActivityType(Enum):
    """Types of activities to detect and ignore"""
    NORMAL = "normal"
    DRINKING = "drinking"         # Minum
    EATING = "eating"             # Makan
    MOVING = "moving"             # Bergerak besar (searching, berdiri)
    OCCLUSION = "occlusion"       # Wajah terhalangi (tangan, dsb)


@dataclass
class FocusFrame:
    """Data untuk setiap frame yang ditrack"""
    timestamp: datetime
    focus_percentage: float
    pitch: float
    yaw: float
    state: FocusState
    activity: ActivityType = ActivityType.NORMAL
    face_detected: bool = True


@dataclass
class FocusPeriod:
    """Periode kontinyu dengan state yang sama"""
    start_time: datetime
    end_time: Optional[datetime]
    state: FocusState
    activity: ActivityType
    duration_seconds: float
    frames: List[FocusFrame]


class FocusTracker:
    """
    Advanced focus tracking dengan duration dan activity detection
    
    Sistem:
    1. Track setiap frame dengan focus%, pitch, yaw
    2. Deteksi activity (minum, bergerak, dll) dan mark sebagai "ignore"
    3. Group frames yang kontinyu dalam state yang sama
    4. Hitung final average: 
        fokus_time / (fokus_time + tidak_fokus_time) * 100%
       (waktu "ignore" tidak dihitung)
    """
    
    def __init__(self, 
                 focus_threshold_high=80,    # Focus >= 80% = FOCUSED
                 focus_threshold_low=50,     # Focus < 50% = NOT_FOCUSED
                 duration_threshold=0.5,     # Minimal durasi untuk dihitung (seconds)
                 face_loss_threshold=3):     # Frame tanpa wajah sebelum mark "no_face"
        
        self.focus_threshold_high = focus_threshold_high
        self.focus_threshold_low = focus_threshold_low
        self.duration_threshold = duration_threshold
        self.face_loss_threshold = face_loss_threshold
        
        self.frames: List[FocusFrame] = []
        self.periods: List[FocusPeriod] = []
        
        self.start_time: Optional[datetime] = None
        self.last_frame_time: Optional[datetime] = None
        
        self.current_state: FocusState = FocusState.NO_FACE
        self.current_activity: ActivityType = ActivityType.NORMAL
        self.no_face_count = 0
        
    def add_frame(self, 
                focus_percentage: float,
                pitch: float,
                yaw: float,
                face_detected: bool = True,
                frame_rgb: Optional[np.ndarray] = None,
                timestamp: Optional[datetime] = None):
        """
        Add frame ke tracking
        
        Args:
            focus_percentage: Focus % (0-100)
            pitch: Head pitch angle (derajat)
            yaw: Head yaw angle (derajat)
            face_detected: Apakah wajah terdeteksi
            frame_rgb: Frame BGR untuk activity detection (optional)
            timestamp: Waktu frame (auto-generate jika None)
        """
        if timestamp is None:
            if self.last_frame_time is None:
                timestamp = datetime.now()
            else:
                # Simulate ~15 FPS frame rate (1/15 = 0.0667 seconds)
                timestamp = self.last_frame_time + timedelta(seconds=1/15)
        
        if self.start_time is None:
            self.start_time = timestamp
        
        self.last_frame_time = timestamp
        
        # Detect state dari focus %
        if not face_detected:
            state = FocusState.NO_FACE
            self.no_face_count += 1
        else:
            self.no_face_count = 0
            if focus_percentage >= self.focus_threshold_high:
                state = FocusState.FOCUSED
            elif focus_percentage >= self.focus_threshold_low:
                state = FocusState.PARTIALLY_FOCUSED
            else:
                state = FocusState.NOT_FOCUSED
        
        # Detect activity
        activity = self._detect_activity(focus_percentage, pitch, yaw, frame_rgb)
        
        # Jika activity diabaikan, mark state sebagai IGNORED
        if activity != ActivityType.NORMAL:
            state = FocusState.IGNORED
        
        frame_data = FocusFrame(
            timestamp=timestamp,
            focus_percentage=focus_percentage,
            pitch=pitch,
            yaw=yaw,
            state=state,
            activity=activity,
            face_detected=face_detected
        )
        
        self.frames.append(frame_data)
        self.current_state = state
        self.current_activity = activity
    
    def _detect_activity(self, 
                        focus_percentage: float,
                        pitch: float,
                        yaw: float,
                        frame_rgb: Optional[np.ndarray] = None) -> ActivityType:
        """
        Detect activity dari frame
        
        Rules:
        - Pitch berubah drastic + fokus jatuh → MOVING/searching
        - Mulut terdeteksi terbuka + head gerak ke bawah → DRINKING
        - Occlusion (tangan di depan wajah) → OCCLUSION
        """
        # TODO: Integrate dengan mouth detection, hand detection
        # Untuk sekarang, simple heuristics
        
        # Large head movement → MOVING
        angle_magnitude = np.sqrt(pitch**2 + yaw**2)
        if angle_magnitude > 70 and focus_percentage < 30:
            return ActivityType.MOVING
        
        # Head down + focus jatuh → possibly DRINKING
        if pitch > 30 and focus_percentage < 40:
            return ActivityType.DRINKING
        
        return ActivityType.NORMAL
    
    def _finalize_current_period(self):
        """
        Finalize periode saat ini dan tambah ke periods list
        Hanya add jika duration >= threshold
        """
        if not self.frames:
            return
        
        last_frame = self.frames[-1]
        
        # Cari frame pertama dengan state ini
        period_frames = []
        for frame in reversed(self.frames):
            if frame.state == self.current_state:
                period_frames.insert(0, frame)
            else:
                break
        
        if not period_frames:
            return
        
        start_frame = period_frames[0]
        end_frame = period_frames[-1]
        
        duration = (end_frame.timestamp - start_frame.timestamp).total_seconds()
        
        # Hanya track periode yang meaningful (>= duration_threshold)
        if duration >= self.duration_threshold or self.current_state == FocusState.NO_FACE:
            period = FocusPeriod(
                start_time=start_frame.timestamp,
                end_time=end_frame.timestamp,
                state=self.current_state,
                activity=self.current_activity,
                duration_seconds=duration,
                frames=period_frames
            )
            self.periods.append(period)
    
    def _regroup_periods(self):
        """
        Regroup all frames into continuous periods based on state changes
        This ensures accurate period boundaries
        """
        if not self.frames:
            return
        
        self.periods = []
        current_period_frames = []
        current_state = None
        current_activity = None
        
        for frame in self.frames:
            if current_state is None:
                current_state = frame.state
                current_activity = frame.activity
                current_period_frames = [frame]
            elif frame.state == current_state:
                current_period_frames.append(frame)
            else:
                # State changed, save current period
                if current_period_frames:
                    start = current_period_frames[0].timestamp
                    end = current_period_frames[-1].timestamp
                    duration = (end - start).total_seconds()
                    
                    if duration >= self.duration_threshold:
                        period = FocusPeriod(
                            start_time=start,
                            end_time=end,
                            state=current_state,
                            activity=current_activity,
                            duration_seconds=duration,
                            frames=current_period_frames.copy()
                        )
                        self.periods.append(period)
                
                # Start new period
                current_state = frame.state
                current_activity = frame.activity
                current_period_frames = [frame]
        
        # Don't forget last period
        if current_period_frames:
            start = current_period_frames[0].timestamp
            end = current_period_frames[-1].timestamp
            duration = (end - start).total_seconds()
            
            if duration >= self.duration_threshold:
                period = FocusPeriod(
                    start_time=start,
                    end_time=end,
                    state=current_state,
                    activity=current_activity,
                    duration_seconds=duration,
                    frames=current_period_frames.copy()
                )
                self.periods.append(period)
    
    def get_final_report(self) -> Dict:
        """
        Calculate final focus report
        
        Returns:
            {
                'total_duration': float,  # Total seconds (including ignored)
                'valid_duration': float,  # Valid seconds (excluding ignored)
                'focused_duration': float,
                'unfocused_duration': float,
                'ignored_duration': float,
                'focus_percentage': float,  # (fokus / (fokus + unfokus)) * 100
                'periods': List[Dict],  # Detail setiap periode
            }
        """
        # Finalize periode terakhir jika belum
        if self.frames and (not self.periods or self.periods[-1].end_time != self.frames[-1].timestamp):
            # Group frames into periods
            if not self.periods or self.frames[-1].state != self.periods[-1].state:
                # Need to create a new period or extend existing
                self._finalize_current_period()
        
        # Regroup all frames into continuous periods
        self._regroup_periods()
        
        total_duration = (self.last_frame_time - self.start_time).total_seconds() \
            if self.last_frame_time and self.start_time else 0
        
        focused_duration = 0
        unfocused_duration = 0
        ignored_duration = 0
        no_face_duration = 0
        
        for period in self.periods:
            if period.state == FocusState.FOCUSED:
                focused_duration += period.duration_seconds
            elif period.state == FocusState.PARTIALLY_FOCUSED:
                # PARTIALLY_FOCUSED dihitung sebagai partial
                focused_duration += period.duration_seconds * 0.5
            elif period.state == FocusState.NOT_FOCUSED:
                unfocused_duration += period.duration_seconds
            elif period.state == FocusState.IGNORED:
                ignored_duration += period.duration_seconds
            elif period.state == FocusState.NO_FACE:
                no_face_duration += period.duration_seconds
        
        # Calculate focus percentage
        # Hanya hitung dari focused + unfocused (ignored tidak dihitung)
        valid_duration = focused_duration + unfocused_duration
        
        if valid_duration > 0:
            focus_percentage = (focused_duration / valid_duration) * 100
        else:
            focus_percentage = 0
        
        # Build periods report
        periods_report = []
        for i, period in enumerate(self.periods):
            periods_report.append({
                'period_number': i + 1,
                'state': period.state.value,
                'activity': period.activity.value,
                'start_time': period.start_time.isoformat(),
                'end_time': period.end_time.isoformat() if period.end_time else None,
                'duration_seconds': round(period.duration_seconds, 2),
                'frame_count': len(period.frames),
                'avg_focus': round(np.mean([f.focus_percentage for f in period.frames]), 1),
                'avg_pitch': round(np.mean([f.pitch for f in period.frames]), 2),
                'avg_yaw': round(np.mean([f.yaw for f in period.frames]), 2),
            })
        
        return {
            'total_duration_seconds': round(total_duration, 2),
            'valid_duration_seconds': round(valid_duration, 2),
            'focused_duration_seconds': round(focused_duration, 2),
            'partially_focused_duration_seconds': round(
                sum(p.duration_seconds for p in self.periods 
                    if p.state == FocusState.PARTIALLY_FOCUSED), 2),
            'unfocused_duration_seconds': round(unfocused_duration, 2),
            'ignored_duration_seconds': round(ignored_duration, 2),
            'no_face_duration_seconds': round(no_face_duration, 2),
            'focus_percentage': round(focus_percentage, 1),
            'frame_count': len(self.frames),
            'periods_count': len(self.periods),
            'periods': periods_report,
        }
    
    def get_summary_string(self) -> str:
        """Get human-readable summary"""
        report = self.get_final_report()
        
        summary = f"""
╔════════════════════════════════════════════════╗
║       📊 FOCUS ANALYSIS REPORT                 ║
╠════════════════════════════════════════════════╣
║ Total Duration         : {report['total_duration_seconds']:>6.1f} s │
║ Valid Duration*        : {report['valid_duration_seconds']:>6.1f} s │
║                                                ║
║ 🟢 Focused             : {report['focused_duration_seconds']:>6.1f} s │
║ 🟡 Partially Focused   : {report['partially_focused_duration_seconds']:>6.1f} s │
║ 🔴 Not Focused         : {report['unfocused_duration_seconds']:>6.1f} s │
║ ⚪ Ignored (drink/etc) : {report['ignored_duration_seconds']:>6.1f} s │
║ ❌ No Face Detected    : {report['no_face_duration_seconds']:>6.1f} s │
║                                                ║
║ ✅ FOCUS SCORE         : {report['focus_percentage']:>6.1f} % │
║ (fokus / (fokus+unfokus) × 100)               ║
║                                                ║
║ Total Frames           : {report['frame_count']:>6.0f}   │
║ Period Segments        : {report['periods_count']:>6.0f}   │
╚════════════════════════════════════════════════╝

* Valid = Focused + Not Focused (excludes ignored activities)
"""
        return summary
    
    def print_detailed_periods(self):
        """Print detailed breakdown of each period"""
        report = self.get_final_report()
        
        print("\n📋 DETAILED PERIOD BREAKDOWN:")
        print("=" * 90)
        print(f"{'#':<4} {'State':<12} {'Activity':<12} {'Duration':<12} {'Frames':<8} {'Avg Focus':<10} {'Pitch/Yaw':<15}")
        print("-" * 90)
        
        for p in report['periods']:
            pitch_yaw = f"{p['avg_pitch']:+.1f}°/{p['avg_yaw']:+.1f}°"
            print(f"{p['period_number']:<4} {p['state']:<12} {p['activity']:<12} "
                  f"{p['duration_seconds']:<12.2f} {p['frame_count']:<8} "
                  f"{p['avg_focus']:<10.1f} {pitch_yaw:<15}")
        
        print("=" * 90)


# Helper functions untuk integrate dengan GazeDetector

def create_focus_tracker(focus_threshold_high=80,
                        focus_threshold_low=50,
                        duration_threshold=0.5) -> FocusTracker:
    """Create new focus tracker dengan custom thresholds"""
    return FocusTracker(
        focus_threshold_high=focus_threshold_high,
        focus_threshold_low=focus_threshold_low,
        duration_threshold=duration_threshold
    )
