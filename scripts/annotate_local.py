#!/usr/bin/env python3
"""
Annotation helper for Local gaze dataset (Dika, Tono, etc.)

This script helps create CSV annotations for local video frame sequences.
Users can manually label gaze positions or use automatic head pose detection.

Usage:
    python scripts/annotate_local.py --person Dika --method manual
    python scripts/annotate_local.py --person Tono --method auto

CSV output format:
    image,pitch,yaw,roll
    VID_20260424_123354_0001.jpg,0.1,0.2,-0.1
    VID_20260424_123354_0002.jpg,0.1,0.25,-0.1
    ...
"""

import os
import csv
import argparse
import numpy as np
from pathlib import Path
from PIL import Image
import json

# For automatic head pose detection (optional)
try:
    import cv2
    from mediapipe import solutions
    MEDIAPIPE_AVAILABLE = True
except ImportError:
    MEDIAPIPE_AVAILABLE = False


def create_empty_annotations(person_dir, output_file):
    """Create a CSV with default (zero) annotations for all images."""
    
    image_files = sorted([
        f for f in os.listdir(person_dir)
        if f.lower().endswith(('.jpg', '.png', '.jpeg'))
    ])
    
    if not image_files:
        print(f"❌ No images found in {person_dir}")
        return
    
    print(f"📝 Creating annotations for {len(image_files)} images...")
    
    with open(output_file, 'w', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=['image', 'pitch', 'yaw', 'roll'])
        writer.writeheader()
        
        for img_name in image_files:
            writer.writerow({
                'image': img_name,
                'pitch': 0.0,
                'yaw': 0.0,
                'roll': 0.0
            })
    
    print(f"✅ Created {output_file}")
    print(f"   {len(image_files)} rows with default values (0, 0, 0)")
    print(f"   Edit the file to add actual gaze annotations")


def auto_detect_head_pose(person_dir, output_file):
    """Automatically detect head pose using MediaPipe."""
    
    if not MEDIAPIPE_AVAILABLE:
        print("❌ MediaPipe not available. Install with: pip install mediapipe opencv-python")
        return
    
    image_files = sorted([
        f for f in os.listdir(person_dir)
        if f.lower().endswith(('.jpg', '.png', '.jpeg'))
    ])
    
    if not image_files:
        print(f"❌ No images found in {person_dir}")
        return
    
    print(f"🔍 Auto-detecting head pose for {len(image_files)} images...")
    
    # Initialize MediaPipe Face Detection and Mesh
    mp_face_mesh = solutions.face_mesh
    face_mesh = mp_face_mesh.FaceMesh(
        static_image_mode=True,
        max_num_faces=1,
        min_detection_confidence=0.5
    )
    
    annotations = []
    success_count = 0
    
    for i, img_name in enumerate(image_files):
        try:
            img_path = os.path.join(person_dir, img_name)
            image = cv2.imread(img_path)
            
            if image is None:
                print(f"   ⚠️ [{i+1}/{len(image_files)}] Skipped: {img_name}")
                annotations.append({'image': img_name, 'pitch': 0.0, 'yaw': 0.0, 'roll': 0.0})
                continue
            
            # Convert to RGB for MediaPipe
            image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            
            # Get face mesh
            results = face_mesh.process(image_rgb)
            
            if results.multi_face_landmarks:
                landmarks = results.multi_face_landmarks[0]
                
                # Extract head pose estimate from landmarks
                # This is a simplified approach - landmarks at different positions
                # indicate head rotation
                
                # Get position of nose (landmark 1) and other key points
                nose = np.array([l.x for l in [landmarks.landmark[1]]])
                
                # Simple heuristic: use facial landmarks to estimate rotation
                # In production, use more sophisticated PnP pose estimation
                left_eye = np.array([landmarks.landmark[33].x, landmarks.landmark[33].y])
                right_eye = np.array([landmarks.landmark[263].x, landmarks.landmark[263].y])
                nose_pos = np.array([landmarks.landmark[1].x, landmarks.landmark[1].y])
                
                # Rough estimation of yaw (horizontal) based on eye-nose positions
                eye_dist = np.abs(right_eye[0] - left_eye[0])
                nose_to_center = np.abs(nose_pos[0] - 0.5)
                
                yaw = (nose_to_center - 0.1) * 2  # Rough scaling
                yaw = np.clip(yaw, -1, 1)
                
                # Pitch estimation from nose position
                pitch = (nose_pos[1] - 0.3) * 2
                pitch = np.clip(pitch, -1, 1)
                
                annotations.append({
                    'image': img_name,
                    'pitch': float(pitch),
                    'yaw': float(yaw),
                    'roll': 0.0
                })
                
                success_count += 1
                print(f"   ✅ [{i+1}/{len(image_files)}] {img_name}: pitch={pitch:.2f}, yaw={yaw:.2f}")
            
            else:
                print(f"   ⚠️ [{i+1}/{len(image_files)}] No face detected: {img_name}")
                annotations.append({'image': img_name, 'pitch': 0.0, 'yaw': 0.0, 'roll': 0.0})
        
        except Exception as e:
            print(f"   ⚠️ [{i+1}/{len(image_files)}] Error: {img_name} - {e}")
            annotations.append({'image': img_name, 'pitch': 0.0, 'yaw': 0.0, 'roll': 0.0})
    
    # Write CSV
    with open(output_file, 'w', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=['image', 'pitch', 'yaw', 'roll'])
        writer.writeheader()
        writer.writerows(annotations)
    
    print(f"\n✅ Created {output_file}")
    print(f"   Total: {len(annotations)} annotations")
    print(f"   Success: {success_count} detected, {len(annotations) - success_count} default")
    
    face_mesh.close()


def interactive_annotate(person_dir, output_file):
    """Interactive annotation tool (show image, ask for gaze)."""
    
    try:
        import matplotlib.pyplot as plt
        from matplotlib.widgets import Slider
    except ImportError:
        print("❌ matplotlib not available. Install with: pip install matplotlib")
        return
    
    image_files = sorted([
        f for f in os.listdir(person_dir)
        if f.lower().endswith(('.jpg', '.png', '.jpeg'))
    ])
    
    if not image_files:
        print(f"❌ No images found in {person_dir}")
        return
    
    print(f"🎨 Interactive annotation for {len(image_files)} images")
    print("   Use sliders to set pitch, yaw, roll")
    print("   Press SAVE to save, NEXT to continue, Q to quit")
    
    annotations = []
    current_idx = [0]  # Use list to allow modification in nested function
    
    fig, (ax, ax_sliders) = plt.subplots(1, 2, figsize=(14, 6))
    
    ax_pitch = plt.axes([0.2, 0.7, 0.6, 0.03])
    ax_yaw = plt.axes([0.2, 0.5, 0.6, 0.03])
    ax_roll = plt.axes([0.2, 0.3, 0.6, 0.03])
    
    slider_pitch = Slider(ax_pitch, 'Pitch', -1, 1, valinit=0, color='red')
    slider_yaw = Slider(ax_yaw, 'Yaw', -1, 1, valinit=0, color='green')
    slider_roll = Slider(ax_roll, 'Roll', -1, 1, valinit=0, color='blue')
    
    def show_image():
        idx = current_idx[0]
        img_path = os.path.join(person_dir, image_files[idx])
        img = Image.open(img_path)
        
        ax.clear()
        ax.imshow(img)
        ax.set_title(f"[{idx+1}/{len(image_files)}] {image_files[idx]}")
        ax.axis('off')
    
    def on_key(event):
        if event.key == 'n':  # Next
            idx = current_idx[0]
            annotations.append({
                'image': image_files[idx],
                'pitch': slider_pitch.val,
                'yaw': slider_yaw.val,
                'roll': slider_roll.val
            })
            
            current_idx[0] += 1
            if current_idx[0] < len(image_files):
                slider_pitch.reset()
                slider_yaw.reset()
                slider_roll.reset()
                show_image()
                fig.canvas.draw_idle()
            else:
                print("✅ All images annotated!")
                plt.close()
                
                # Save CSV
                with open(output_file, 'w', newline='') as f:
                    writer = csv.DictWriter(f, fieldnames=['image', 'pitch', 'yaw', 'roll'])
                    writer.writeheader()
                    writer.writerows(annotations)
                print(f"✅ Saved to {output_file}")
        
        elif event.key == 'q':  # Quit
            plt.close()
            print("❌ Cancelled")
    
    fig.canvas.mpl_connect('key_press_event', on_key)
    show_image()
    plt.suptitle("Press N to save and next, Q to quit")
    plt.show()


def main():
    parser = argparse.ArgumentParser(description='Annotate local gaze dataset')
    parser.add_argument('--person', type=str, required=True,
                       help='Person folder name (e.g., Dika, Tono)')
    parser.add_argument('--method', type=str, choices=['manual', 'auto', 'interactive'],
                       default='manual',
                       help='Annotation method')
    parser.add_argument('--input-dir', type=str, default='data/Local',
                       help='Input directory')
    parser.add_argument('--output-csv', type=str, default=None,
                       help='Output CSV file')
    
    args = parser.parse_args()
    
    # Determine paths
    person_dir = os.path.join(args.input_dir, args.person)
    
    if not os.path.exists(person_dir):
        print(f"❌ Directory not found: {person_dir}")
        return
    
    output_csv = args.output_csv or os.path.join(person_dir, 'annotations.csv')
    
    # Check if annotations already exist
    if os.path.exists(output_csv):
        print(f"⚠️ Annotations already exist: {output_csv}")
        response = input("Overwrite? (y/n): ").strip().lower()
        if response != 'y':
            print("Cancelled")
            return
    
    # Run annotation method
    print(f"\n📂 Person: {args.person}")
    print(f"📍 Directory: {person_dir}")
    print(f"💾 Output: {output_csv}")
    print(f"🔧 Method: {args.method}")
    print()
    
    if args.method == 'manual':
        create_empty_annotations(person_dir, output_csv)
        print("\n📝 Manual mode: Edit the CSV file to add gaze values")
        print("   Fields: image, pitch, yaw, roll")
        print("   Example: VID_001.jpg,0.1,0.2,-0.05")
    
    elif args.method == 'auto':
        auto_detect_head_pose(person_dir, output_csv)
        print("\n⚠️ Auto-detected values may be inaccurate")
        print("   Consider manual refinement for better results")
    
    elif args.method == 'interactive':
        interactive_annotate(person_dir, output_csv)


if __name__ == '__main__':
    main()
