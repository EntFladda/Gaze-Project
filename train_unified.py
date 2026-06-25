#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
 UNIFIED TRAINING PIPELINE
Consolidated training for Head Pose + Eye Tracking + Brightness Robustness
Supports: 300W-LP, MPIIGaze, UnityEyes (via Kaggle)

Usage:
    # Download eye tracking dataset from Kaggle
    python train_unified.py --download-kaggle unitygaze

    # Train on all available datasets
    python train_unified.py --train --epochs 50

    # Quick test (overfit single batch)
    python train_unified.py --test-overfit

    # Resume training
    python train_unified.py --train --resume
"""

import torch
import torch.nn as nn
from torch.utils.data import DataLoader, ConcatDataset, random_split
import os
import sys
import argparse
import cv2
import numpy as np
from pathlib import Path
from datetime import datetime
import json
from torchvision import transforms
from PIL import Image

# Add src to path
sys.path.insert(0, str(Path(__file__).parent))

from src.training.lightning_trainer import (
    ImprovedHeadPoseModel,
    train_with_lightning,
)
from src.dataset import HeadPoseDataset300WLP, HeadPoseDatasetMPIIGaze


class KaggleEyeTrackingDataset(torch.utils.data.Dataset):
    """Load eye tracking data from Kaggle UnityEyes or similar"""
    
    def __init__(self, root_dir='data/eye_tracking', transform=None):
        self.root_dir = Path(root_dir)
        self.transform = transform
        self.samples = []
        
        if self.root_dir.exists():
            # Load metadata
            metadata_file = self.root_dir / 'metadata.json'
            if metadata_file.exists():
                with open(metadata_file) as f:
                    self.metadata = json.load(f)
                    self.samples = self.metadata.get('samples', [])
            else:
                print(f"[DATA] No metadata found in {self.root_dir}")
    
    def __len__(self):
        return len(self.samples)
    
    def __getitem__(self, idx):
        if len(self.samples) == 0:
            raise RuntimeError("No eye tracking samples loaded")
        
        sample = self.samples[idx]
        img_path = self.root_dir / sample['image']
        
        # Load image
        img = cv2.imread(str(img_path))
        if img is None:
            raise RuntimeError(f"Failed to load {img_path}")
        
        img = cv2.cvtColor(img, cv2.COLOR_BGR2RGB)
        
        # Head pose (pitch, yaw in normalized [-1, 1])
        pitch = np.array([sample.get('pitch', 0)], dtype=np.float32)
        yaw = np.array([sample.get('yaw', 0)], dtype=np.float32)
        
        # Eye tracking (if available)
        eye_x = np.array([sample.get('eye_x', 0)], dtype=np.float32)
        eye_y = np.array([sample.get('eye_y', 0)], dtype=np.float32)
        
        # Brightness label (0=dark, 1=normal, 2=bright)
        brightness = sample.get('brightness', 1)
        
        if self.transform:
            img = self.transform(Image.fromarray(img))
        
        return {
            'image': img,
            'pitch': pitch,
            'yaw': yaw,
            'eye_x': eye_x,
            'eye_y': eye_y,
            'brightness': brightness
        }


class BrightnessAugmentation:
    """Augment images with varying brightness for robustness"""
    
    def __init__(self, brightness_range=(-0.3, 0.3)):
        self.brightness_range = brightness_range
    
    def __call__(self, img):
        """Apply random brightness adjustment"""
        if isinstance(img, Image.Image):
            img = np.array(img)
        
        brightness_factor = np.random.uniform(*self.brightness_range)
        brightness_img = np.clip(img * (1 + brightness_factor), 0, 255).astype(np.uint8)
        
        return Image.fromarray(brightness_img)


def download_kaggle_dataset(dataset_name='unitygaze'):
    """
    Download eye tracking dataset from Kaggle
    
    Supported datasets:
    - unitygaze: Unity-rendered eyes with pose/gaze labels
    - mpiigaze-extended: MPIIGaze+ with more subjects
    """
    print(f"\n[KAGGLE] Downloading {dataset_name}...")
    
    try:
        import kaggle
    except ImportError:
        print("[ERROR] kaggle package not installed")
        print("  Install: pip install kaggle")
        print("  Setup: Place kaggle.json in ~/.kaggle/")
        return False
    
    output_dir = Path('data/eye_tracking')
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # Map dataset names to Kaggle dataset IDs
    datasets = {
        'unitygaze': 'peterchou/unity-eye',
        'mpiigaze-extended': 'anguyen8/mpiigaze',
        'eyediap': 'anguyen8/eyediap'
    }
    
    if dataset_name not in datasets:
        print(f"[ERROR] Unknown dataset: {dataset_name}")
        print(f"  Available: {list(datasets.keys())}")
        return False
    
    kaggle_id = datasets[dataset_name]
    
    try:
        # Download using kaggle CLI
        cmd = f"kaggle datasets download -d {kaggle_id} -p {output_dir} --unzip"
        ret = os.system(cmd)
        
        if ret == 0:
            print(f"[SUCCESS] Downloaded {dataset_name} to {output_dir}")
            return True
        else:
            print(f"[ERROR] Failed to download {dataset_name}")
            return False
            
    except Exception as e:
        print(f"[ERROR] Download failed: {e}")
        return False


def create_unified_dataloader(batch_size=32, num_workers=4):
    """
    Create combined dataloader from all available datasets
    Consolidates: 300W-LP, MPIIGaze, Eye Tracking
    """
    print("\n[DATA] Creating unified dataloader...")
    
    transform = transforms.Compose([
        BrightnessAugmentation(brightness_range=(-0.3, 0.3)),
        transforms.Resize((224, 224)),
        transforms.RandomHorizontalFlip(p=0.5),
        transforms.RandomRotation(15),
        transforms.ColorJitter(brightness=0.2, contrast=0.2),
        transforms.ToTensor(),
        transforms.Normalize(
            mean=[0.485, 0.456, 0.406],
            std=[0.229, 0.224, 0.225]
        )
    ])
    
    datasets = []
    total_samples = 0
    
    # 1. Load 300W-LP dataset
    try:
        print("[DATA] Loading 300W-LP...")
        dataset_300w = HeadPoseDataset300WLP(
            root_dir='data/300W_LP',
            transform=transform,
            normalize_angles=True
        )
        datasets.append(dataset_300w)
        total_samples += len(dataset_300w)
        print(f"   300W-LP: {len(dataset_300w)} samples")
    except Exception as e:
        print(f"  ️  300W-LP failed: {e}")
    
    # 2. Load MPIIGaze dataset
    try:
        print("[DATA] Loading MPIIGaze...")
        dataset_mpiigaze = HeadPoseDatasetMPIIGaze(
            root_dir='data/MPIIGaze',
            transform=transform,
            normalize_angles=True
        )
        datasets.append(dataset_mpiigaze)
        total_samples += len(dataset_mpiigaze)
        print(f"   MPIIGaze: {len(dataset_mpiigaze)} samples")
    except Exception as e:
        print(f"  ️  MPIIGaze failed: {e}")
    
    # 3. Load Kaggle eye tracking dataset (if available)
    try:
        print("[DATA] Loading Kaggle Eye Tracking...")
        dataset_kaggle = KaggleEyeTrackingDataset(
            root_dir='data/eye_tracking',
            transform=transform
        )
        if len(dataset_kaggle) > 0:
            datasets.append(dataset_kaggle)
            total_samples += len(dataset_kaggle)
            print(f"   Kaggle Eye Tracking: {len(dataset_kaggle)} samples")
        else:
            print(f"  ℹ️  Kaggle dataset empty (optional)")
    except Exception as e:
        print(f"  ℹ️  Kaggle Eye Tracking not available: {e}")
    
    if not datasets:
        raise RuntimeError("No datasets found! Please download at least one dataset.")
    
    # Combine datasets
    combined_dataset = ConcatDataset(datasets)
    print(f"\n[DATA] Combined dataset: {total_samples} total samples")
    
    # Split: 80% train, 10% val, 10% test
    train_size = int(0.8 * total_samples)
    val_size = int(0.1 * total_samples)
    test_size = total_samples - train_size - val_size
    
    train_set, val_set, test_set = random_split(
        combined_dataset,
        [train_size, val_size, test_size],
        generator=torch.Generator().manual_seed(42)
    )
    
    print(f"  Train: {len(train_set)} | Val: {len(val_set)} | Test: {len(test_set)}")
    
    # Create dataloaders
    train_loader = DataLoader(
        train_set,
        batch_size=batch_size,
        shuffle=True,
        num_workers=num_workers,
        pin_memory=True
    )
    
    val_loader = DataLoader(
        val_set,
        batch_size=batch_size,
        shuffle=False,
        num_workers=num_workers,
        pin_memory=True
    )
    
    test_loader = DataLoader(
        test_set,
        batch_size=batch_size,
        shuffle=False,
        num_workers=num_workers,
        pin_memory=True
    )
    
    return train_loader, val_loader, test_loader


def train_unified(args):
    """Main training function"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    print(f"[SYSTEM] Device: {device}")
    
    # Create dataloaders
    train_loader, val_loader, test_loader = create_unified_dataloader(
        batch_size=args.batch_size,
        num_workers=args.num_workers
    )
    
    # Create model
    print("\n[MODEL] Creating ImprovedHeadPoseModel...")
    model = ImprovedHeadPoseModel(backbone='resnet50').to(device)
    
    # Train using Lightning
    print(f"\n[TRAIN] Starting training ({args.epochs} epochs)...")
    trained_model = train_with_lightning(
        model=model,
        train_loader=train_loader,
        val_loader=val_loader,
        test_loader=test_loader,
        epochs=args.epochs,
        device=device,
        checkpoint_dir='checkpoints/final',
        use_mixed_precision=True,
        learning_rate=args.learning_rate
    )
    
    print(f"\n Training complete!")
    print(f" Model saved to: checkpoints/final/head_pose_best.pt")


def test_overfit(args):
    """Quick overfit test on single batch"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    train_loader, val_loader, test_loader = create_unified_dataloader(
        batch_size=4,
        num_workers=0
    )
    
    # Get single batch
    batch = next(iter(train_loader))
    print(f"[TEST] Batch shape: {batch['image'].shape}")
    
    # Create & test model
    model = ImprovedHeadPoseModel(backbone='resnet50').to(device)
    
    # Forward pass
    x = batch['image'].to(device)
    with torch.no_grad():
        pose, conf, roll = model(x)
    
    print(f"[TEST] Outputs:")
    print(f"  Pose: {pose.shape} → {pose[0]}")
    print(f"  Confidence: {conf.shape}")
    print(f"  Roll: {roll.shape}")
    print(f" Model works!")


def main():
    parser = argparse.ArgumentParser(
        description='Unified Training Pipeline for Head Pose + Eye Tracking'
    )
    parser.add_argument('--download-kaggle', type=str,
                       help='Download dataset from Kaggle (unitygaze, mpiigaze-extended, eyediap)')
    parser.add_argument('--train', action='store_true',
                       help='Train the model')
    parser.add_argument('--test-overfit', action='store_true',
                       help='Quick overfit test')
    parser.add_argument('--epochs', type=int, default=50,
                       help='Number of training epochs')
    parser.add_argument('--batch-size', type=int, default=32,
                       help='Batch size')
    parser.add_argument('--num-workers', type=int, default=4,
                       help='Number of data loading workers')
    parser.add_argument('--learning-rate', type=float, default=1e-4,
                       help='Learning rate')
    parser.add_argument('--resume', action='store_true',
                       help='Resume from last checkpoint')
    
    args = parser.parse_args()
    
    print("=" * 70)
    print("[UNIFIED] HEAD POSE + EYE TRACKING TRAINING PIPELINE")
    print("=" * 70)
    
    # Download from Kaggle if requested
    if args.download_kaggle:
        download_kaggle_dataset(args.download_kaggle)
        return
    
    # Quick overfit test
    if args.test_overfit:
        print("[MODE] Testing model (overfit single batch)")
        test_overfit(args)
        return
    
    # Full training
    if args.train:
        print("[MODE] Training on all available datasets")
        train_unified(args)
        return
    
    # Default: show help
    parser.print_help()


if __name__ == '__main__':
    main()
