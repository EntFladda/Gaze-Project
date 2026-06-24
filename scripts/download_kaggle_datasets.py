#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Download & Setup Kaggle Datasets for Thesis Testing
- Dataset 1: MPIIGaze-Light (brightness robustness)
- Dataset 2: UnityEyes (eye tracking)

Prerequisite: 
  pip install kaggle
  # Place ~/.kaggle/kaggle.json (API key from https://www.kaggle.com/settings)

Usage:
  python scripts/download_kaggle_datasets.py --all
  python scripts/download_kaggle_datasets.py --brightness --eye-tracking
  python scripts/download_kaggle_datasets.py --verify
"""

import os
import sys
import json
import argparse
from pathlib import Path


def setup_kaggle_credentials():
    """Check and setup Kaggle API credentials"""
    kaggle_config = Path.home() / '.kaggle' / 'kaggle.json'
    
    if not kaggle_config.exists():
        print("❌ Kaggle credentials not found!")
        print("📝 Setup instructions:")
        print("   1. Go to: https://www.kaggle.com/settings/account")
        print("   2. Click 'Create New Token' (downloads kaggle.json)")
        print("   3. Move to: ~/.kaggle/kaggle.json")
        print("   4. Run: chmod 600 ~/.kaggle/kaggle.json (on Unix/Mac)")
        return False
    
    print("✅ Kaggle credentials found")
    return True


def download_brightness_dataset():
    """
    Download MPIIGaze dataset (low/high brightness)
    Kaggle dataset: rakibashar/mpiigaze-dataset
    """
    print("\n" + "="*60)
    print("[DATASET 1] Brightness Robustness (MPIIGaze-Light)")
    print("="*60)
    
    output_dir = Path('data/brightness_gaze')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    try:
        import kaggle
    except ImportError:
        print("❌ kaggle package not installed")
        print("   Install: pip install kaggle")
        return False
    
    print(f"📥 Downloading to: {output_dir}")
    print("   Dataset: rakibashar/mpiigaze-dataset")
    print("   Size: ~5GB (may take 10-30 minutes)")
    
    try:
        kaggle.api.dataset_download_files(
            'rakibashar/mpiigaze-dataset',
            path=str(output_dir),
            unzip=True
        )
        print("✅ Download complete!")
        return True
    except Exception as e:
        print(f"❌ Download failed: {e}")
        print("   Manual download: https://www.kaggle.com/datasets/rakibashar/mpiigaze-dataset")
        return False


def download_eye_tracking_dataset():
    """
    Download UnityEyes dataset (eye tracking)
    Kaggle dataset: iamzeeshandev/unityeyes-real-world-eye-tracking-dataset
    """
    print("\n" + "="*60)
    print("[DATASET 2] Eye Tracking (UnityEyes)")
    print("="*60)
    
    output_dir = Path('data/eye_tracking')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    try:
        import kaggle
    except ImportError:
        print("❌ kaggle package not installed")
        print("   Install: pip install kaggle")
        return False
    
    print(f"📥 Downloading to: {output_dir}")
    print("   Dataset: iamzeeshandev/unityeyes-real-world-eye-tracking-dataset")
    print("   Size: ~3GB (may take 5-15 minutes)")
    
    try:
        kaggle.api.dataset_download_files(
            'iamzeeshandev/unityeyes-real-world-eye-tracking-dataset',
            path=str(output_dir),
            unzip=True
        )
        print("✅ Download complete!")
        return True
    except Exception as e:
        print(f"❌ Download failed: {e}")
        print("   Manual download: https://www.kaggle.com/datasets/iamzeeshandev/unityeyes-real-world-eye-tracking-dataset")
        return False
def download_eye_tracking_dataset():
    """
    Download UnityEyes dataset (eye tracking)
    Kaggle dataset: iamzeeshandev/unityeyes-real-world-eye-tracking-dataset
    """
    print("\n" + "="*60)
    print("[DATASET 2] Eye Tracking (UnityEyes)")
    print("="*60)
    
    output_dir = Path('data/eye_tracking')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    try:
        import kaggle
    except ImportError:
        print("❌ kaggle package not installed")
        print("   Install: pip install kaggle")
        return False
    
    print(f"📥 Downloading to: {output_dir}")
    print("   Dataset: iamzeeshandev/unityeyes-real-world-eye-tracking-dataset")
    print("   Size: ~3GB (may take 5-15 minutes)")
    
    try:
        kaggle.api.dataset_download_files(
            'iamzeeshandev/unityeyes-real-world-eye-tracking-dataset',
            path=str(output_dir),
            unzip=True
        )
        print("✅ Download complete!")
        return True
    except Exception as e:
        print(f"❌ Download failed: {e}")
        print("   Manual download: https://www.kaggle.com/datasets/iamzeeshandev/unityeyes-real-world-eye-tracking-dataset")
        return False


def verify_datasets():
    """Verify downloaded datasets"""
    print("\n" + "="*60)
    print("[VERIFY] Dataset Structure")
    print("="*60)
    
    datasets = {
        'data/300W_LP': '300W-LP (existing)',
        'data/MPIIGaze': 'MPIIGaze (existing)',
        'data/brightness_gaze': 'Brightness robustness (new)',
        'data/eye_tracking': 'Eye tracking (new)',
    }
    
    for path, name in datasets.items():
        p = Path(path)
        if p.exists():
            count = sum(1 for _ in p.rglob('*.*'))
            size_mb = sum(f.stat().st_size for f in p.rglob('*.*')) / (1024*1024)
            print(f"✅ {name}")
            print(f"   Path: {path}")
            print(f"   Files: {count}, Size: {size_mb:.1f} MB")
        else:
            print(f"❌ {name}")
            print(f"   Path: {path} (NOT FOUND)")
    
    return True


def create_metadata():
    """Create metadata files for datasets"""
    print("\n" + "="*60)
    print("[METADATA] Creating dataset descriptions")
    print("="*60)
    
    metadata = {
        'brightness_gaze': {
            'description': 'MPIIGaze dataset with low/high brightness variations',
            'source': 'https://www.kaggle.com/datasets/rakibashar/mpiigaze-dataset',
            'samples_expected': 10000,
            'lighting_conditions': ['low_light (20-50 lux)', 'normal (200-500 lux)', 'high_bright (500+ lux)'],
            'annotations': ['gaze_direction', 'head_pose', 'lighting_level'],
        },
        'eye_tracking': {
            'description': 'UnityEyes + real-world hybrid eye tracking dataset',
            'source': 'https://www.kaggle.com/datasets/iamzeeshandev/unityeyes-real-world-eye-tracking-dataset',
            'samples_expected': 15000,
            'tracking_targets': ['pupil_center', 'iris_boundary', 'eyelid_landmarks', 'gaze_vector'],
            'annotations': ['eye_regions', 'pupil_location', 'iris_radius', 'gaze_3d'],
        }
    }
    
    for dataset_name, meta in metadata.items():
        path = Path('data') / dataset_name / 'metadata.json'
        path.parent.mkdir(parents=True, exist_ok=True)
        with open(path, 'w') as f:
            json.dump(meta, f, indent=2)
        print(f"✅ Created: {path}")
    
    return True


def main():
    parser = argparse.ArgumentParser(description='Download Kaggle datasets for thesis testing')
    parser.add_argument('--brightness', action='store_true', help='Download brightness dataset only')
    parser.add_argument('--eye-tracking', action='store_true', help='Download eye tracking dataset only')
    parser.add_argument('--all', action='store_true', help='Download all datasets (default)')
    parser.add_argument('--verify', action='store_true', help='Verify existing datasets')
    
    args = parser.parse_args()
    
    print("="*60)
    print("🎯 KAGGLE DATASET DOWNLOADER")
    print("="*60)
    
    # Check credentials
    if not setup_kaggle_credentials():
        return
    
    # Default to all if no specific option
    if not (args.brightness or args.eye_tracking or args.verify):
        args.all = True
    
    # Download datasets
    success_count = 0
    
    if args.all or args.brightness:
        if download_brightness_dataset():
            success_count += 1
    
    if args.all or args.eye_tracking:
        if download_eye_tracking_dataset():
            success_count += 1
    
    # Create metadata
    create_metadata()
    
    # Verify
    verify_datasets()
    
    # Summary
    print("\n" + "="*60)
    print("[SUMMARY]")
    print("="*60)
    print(f"✅ Downloaded {success_count} datasets")
    print(f"\n📝 Next steps:")
    print(f"   1. python train_unified.py --train --epochs 50")
    print(f"   2. python scripts/export_to_onnx.py")
    print(f"   3. python run_complete_pipeline.py --skip-train --webcam")
    print("="*60)


if __name__ == '__main__':
    main()


def download_gazecapture_dataset(output_dir='data/GazeCapture'):
    """
    Download GazeCapture dataset (Eye tracking with lighting variation)
    https://www.kaggle.com/datasets/search?q=gazecapture
    
    GazeCapture = Eye gaze from mobile devices
    - 1500+ subjects
    - Various lighting conditions
    - iPhone/iPad dataset (low-res to high-res)
    - Good for eye-tracking testing
    """
    print("\n📥 Downloading GazeCapture dataset...")
    
    datasets = [
        'cluelessvagrant/gazecapture',  # Example
        # Add verified dataset IDs here
    ]
    
    output_path = Path(output_dir)
    output_path.mkdir(parents=True, exist_ok=True)
    
    for dataset in datasets:
        try:
            print(f"   Trying: {dataset}")
            subprocess.run(
                ['kaggle', 'datasets', 'download', '-d', dataset, '-p', str(output_path)],
                check=True
            )
            print(f"   ✅ Downloaded: {dataset}")
            
            # Extract
            for zip_file in output_path.glob('*.zip'):
                print(f"   Extracting: {zip_file}")
                subprocess.run(['unzip', '-q', str(zip_file), '-d', str(output_path)], check=True)
                zip_file.unlink()
            
            return True
        except Exception as e:
            print(f"   ⚠️  Failed: {e}")
            continue
    
    print("⚠️  GazeCapture not found (download manually if needed)")
    print("   Official: https://gazecapture.csail.mit.edu/")
    return False


def download_mpiigaze_extended(output_dir='data/MPIIGaze'):
    """
    Verify MPIIGaze is present (usually pre-installed)
    If not, download from Kaggle
    """
    output_path = Path(output_dir)
    
    if (output_path / 'Data').exists():
        print("✅ MPIIGaze already present")
        return True
    
    print("\n📥 Downloading MPIIGaze extended...")
    
    datasets = [
        'asif2174/mpiigaze',  # Example
    ]
    
    output_path.mkdir(parents=True, exist_ok=True)
    
    for dataset in datasets:
        try:
            print(f"   Downloading: {dataset}")
            subprocess.run(
                ['kaggle', 'datasets', 'download', '-d', dataset, '-p', str(output_path)],
                check=True
            )
            print(f"   ✅ Downloaded")
            
            # Extract
            for zip_file in output_path.glob('*.zip'):
                subprocess.run(['unzip', '-q', str(zip_file), '-d', str(output_path)], check=True)
                zip_file.unlink()
            
            return True
        except Exception as e:
            print(f"   ⚠️  Failed: {e}")
            continue
    
    print("❌ Could not download MPIIGaze")
    return False


def verify_datasets():
    """Verify dataset structure"""
    print("\n📊 Verifying dataset structure...")
    
    datasets_check = {
        'data/300W_LP': ['AFW', 'HELEN', 'IBUG', 'LFPW'],
        'data/MPIIGaze': ['Data'],
        'data/FAZE': ['*'],  # Any files
        'data/GazeCapture': ['*'],
    }
    
    for dataset_path, expected_items in datasets_check.items():
        path = Path(dataset_path)
        if path.exists():
            items = [d.name for d in path.iterdir()]
            print(f"✅ {dataset_path}")
            if items and expected_items != ['*']:
                print(f"   Contents: {', '.join(items[:3])}...")
        else:
            print(f"⚠️  {dataset_path} not found")
    
    print("\n✅ Dataset verification complete")


def main():
    parser = argparse.ArgumentParser(description='Download Kaggle Gaze Datasets')
    parser.add_argument('--faze', action='store_true', help='Download FAZE dataset')
    parser.add_argument('--gazecapture', action='store_true', help='Download GazeCapture dataset')
    parser.add_argument('--mpiigaze', action='store_true', help='Verify/Download MPIIGaze')
    parser.add_argument('--all', action='store_true', help='Download all available datasets')
    parser.add_argument('--verify-only', action='store_true', help='Only verify existing datasets')
    
    args = parser.parse_args()
    
    print("=" * 60)
    print("[KAGGLE] Gaze Detection Dataset Downloader")
    print("=" * 60)
    
    if not args.verify_only:
        if not check_kaggle_setup():
            print("\n📝 Manual download options:")
            print("   1. FAZE: https://github.com/crishanli/FAZE")
            print("   2. GazeCapture: https://gazecapture.csail.mit.edu/")
            print("   3. MPIIGaze: http://www.mpiigaze.de/")
            return
    
    if args.all or args.faze:
        download_faze_dataset()
    
    if args.all or args.gazecapture:
        download_gazecapture_dataset()
    
    if args.all or args.mpiigaze:
        download_mpiigaze_extended()
    
    verify_datasets()
    
    print("\n✅ Download process complete!")
    print("📁 Datasets location: data/")
    print("🚀 Ready for training: python run_complete_pipeline.py")


if __name__ == '__main__':
    main()
