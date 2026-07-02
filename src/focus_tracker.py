"""
Sistem pelacakan fokus lanjutan.
Melacak durasi, aktivitas, rata-rata.
"""

import numpy as np
from datetime import datetime, timedelta
from enum import Enum
from dataclasses import dataclass
from typing import List, Dict, Optional
import cv2


class FocusState(Enum):
    """Status pelacakan fokus."""
    FOCUSED = "focused"           # Fokus sempurna.
    PARTIALLY_FOCUSED = "partial" # Fokus sebagian.
    NOT_FOCUSED = "unfocused"     # Tidak fokus.
    IGNORED = "ignored"           # Abaikan aktivitas.
    NO_FACE = "no_face"           # Wajah tidak terdeteksi.


class ActivityType(Enum):
    """Types of activities to detect and ignore"""
    NORMAL = "normal"
    DRINKING = "drinking"         # Sedang minum.
    EATING = "eating"             # Sedang makan.
    MOVING = "moving"             # Pergerakan besar.
    OCCLUSION = "occlusion"       # Wajah terhalang.


@dataclass
class FocusFrame:
    """Data pelacakan setiap frame."""
    timestamp: datetime
    focus_percentage: float
    pitch: float
    yaw: float
    state: FocusState
    activity: ActivityType = ActivityType.NORMAL
    face_detected: bool = True


@dataclass
class FocusPeriod:
    """Periode kontinyu status sama."""
    start_time: datetime
    end_time: Optional[datetime]
    state: FocusState
    activity: ActivityType
    duration_seconds: float
    frames: List[FocusFrame]


class FocusTracker:
    """
    Pelacakan fokus durasi aktivitas.
    """
    
    def __init__(self, 
                 focus_threshold_high=80,    # Batas fokus tinggi.
                 focus_threshold_low=50,     # Batas fokus rendah.
                 duration_threshold=0.5,     # Durasi minimal dihitung.
                 face_loss_threshold=3):     # Batas kehilangan wajah.
        
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
        Tambah frame ke pelacakan.
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
        
        # Deteksi status fokus.
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
        
        # Deteksi aktivitas.
        activity = self._detect_activity(focus_percentage, pitch, yaw, frame_rgb)
        
        # Abaikan aktivitas non-normal.
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
        """Deteksi aktivitas frame."""
        # TODO: Integrate dengan mouth detection, hand detection
        # Untuk sekarang, simple heuristics
        
        # Gerakan kepala besar.
        angle_magnitude = np.sqrt(pitch**2 + yaw**2)
        if angle_magnitude > 70 and focus_percentage < 30:
            return ActivityType.MOVING
        
        # Kemungkinan sedang minum.
        if pitch > 30 and focus_percentage < 40:
            return ActivityType.DRINKING
        
        return ActivityType.NORMAL
    
    def _finalize_current_period(self):
        """Selesaikan periode saat ini."""
        if not self.frames:
            return
        
        last_frame = self.frames[-1]
        
        # Cari frame awal.
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
        
        # Lacak periode signifikan.
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
        """Grupkan ulang periode."""
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
                # Simpan periode berjalan.
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
                
                # Mulai periode baru.
                current_state = frame.state
                current_activity = frame.activity
                current_period_frames = [frame]
        
        # Simpan periode terakhir.
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
        """Hitung laporan akhir fokus."""
        # Selesaikan periode terakhir.
        if self.frames and (not self.periods or self.periods[-1].end_time != self.frames[-1].timestamp):
            # Grupkan ke periode.
            if not self.periods or self.frames[-1].state != self.periods[-1].state:
                # Buat periode baru.
                self._finalize_current_period()
        
        # Grupkan ulang periode kontinyu.
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
                # Fokus parsial dihitung parsial.
                focused_duration += period.duration_seconds * 0.5
            elif period.state == FocusState.NOT_FOCUSED:
                unfocused_duration += period.duration_seconds
            elif period.state == FocusState.IGNORED:
                ignored_duration += period.duration_seconds
            elif period.state == FocusState.NO_FACE:
                no_face_duration += period.duration_seconds
        
        # Hitung persentase fokus.
        # Hanya hitung durasi valid.
        valid_duration = focused_duration + unfocused_duration
        
        if valid_duration > 0:
            focus_percentage = (focused_duration / valid_duration) * 100
        else:
            focus_percentage = 0
        
        # Buat laporan periode.
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
        """Dapatkan ringkasan singkat."""
        report = self.get_final_report()
        
        summary = f"""
╔════════════════════════════════════════════════╗
║        FOCUS ANALYSIS REPORT                 ║
╠════════════════════════════════════════════════╣
║ Total Duration         : {report['total_duration_seconds']:>6.1f} s │
║ Valid Duration*        : {report['valid_duration_seconds']:>6.1f} s │
║                                                ║
║  Focused             : {report['focused_duration_seconds']:>6.1f} s │
║  Partially Focused   : {report['partially_focused_duration_seconds']:>6.1f} s │
║  Not Focused         : {report['unfocused_duration_seconds']:>6.1f} s │
║  Ignored (drink/etc) : {report['ignored_duration_seconds']:>6.1f} s │
║  No Face Detected    : {report['no_face_duration_seconds']:>6.1f} s │
║                                                ║
║  FOCUS SCORE         : {report['focus_percentage']:>6.1f} % │
║ (fokus / (fokus+unfokus) × 100)               ║
║                                                ║
║ Total Frames           : {report['frame_count']:>6.0f}   │
║ Period Segments        : {report['periods_count']:>6.0f}   │
╚════════════════════════════════════════════════╝

* Valid = Focused + Not Focused (excludes ignored activities)
"""
        return summary
    
    def print_detailed_periods(self):
        """Cetak rincian periode."""
        report = self.get_final_report()
        
        print("\n DETAILED PERIOD BREAKDOWN:")
        print("=" * 90)
        print(f"{'#':<4} {'State':<12} {'Activity':<12} {'Duration':<12} {'Frames':<8} {'Avg Focus':<10} {'Pitch/Yaw':<15}")
        print("-" * 90)
        
        for p in report['periods']:
            pitch_yaw = f"{p['avg_pitch']:+.1f}°/{p['avg_yaw']:+.1f}°"
            print(f"{p['period_number']:<4} {p['state']:<12} {p['activity']:<12} "
                  f"{p['duration_seconds']:<12.2f} {p['frame_count']:<8} "
                  f"{p['avg_focus']:<10.1f} {pitch_yaw:<15}")
        
        print("=" * 90)


# Fungsi bantuan integrator.

def create_focus_tracker(focus_threshold_high=80,
                        focus_threshold_low=50,
                        duration_threshold=0.5) -> FocusTracker:
    """Buat pelacak fokus baru."""
    return FocusTracker(
        focus_threshold_high=focus_threshold_high,
        focus_threshold_low=focus_threshold_low,
        duration_threshold=duration_threshold
    )
