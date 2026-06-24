#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Optimized Data Pipeline untuk Head Pose Detection
- Faster dataloading
- Better augmentation
- Memory efficient
"""

import torch
from torch.utils.data import Dataset, DataLoader
from torchvision import transforms
from PIL import Image
import numpy as np
from pathlib import Path
import cv2
from torch.utils.data import distributed


class OptimizedHeadPoseDataset(Dataset):
    """
    Optimized dataset loader dengan:
    - Lazy loading (load on demand, not all at once)
    - Smart caching untuk frequently accessed items
    - Augmentation yang meaningful untuk head pose
    """
    
    def __init__(self, image_paths, targets, augment=True, cache_size=1000):
        """
        Args:
            image_paths: List of image file paths
            targets: Array of shape (N, 3) -> [pitch, yaw, roll]
            augment: Apply augmentations
            cache_size: Cache size untuk in-memory items
        """
        self.image_paths = image_paths
        self.targets = targets
        self.augment = augment
        self.cache = {}
        self.cache_size = cache_size
        
        # Augmentation transforms
        self.transforms = transforms.Compose([
            transforms.ToTensor(),
            transforms.Normalize(
                mean=[0.485, 0.456, 0.406],
                std=[0.229, 0.224, 0.225]
            )
        ])
        
        # Augmentation for training
        self.augment_transforms = transforms.Compose([
            transforms.ColorJitter(
                brightness=0.2,
                contrast=0.2,
                saturation=0.1,
                hue=0.05
            ),
            transforms.GaussianBlur(kernel_size=3, sigma=(0.1, 0.5)),
            transforms.RandomRotation(5),  # Small rotation untuk natural variation
            transforms.RandomAffine(
                degrees=0,
                translate=(0.05, 0.05),  # 5% translation
                scale=(0.95, 1.05)  # 5% scale
            ),
            transforms.ToTensor(),
            transforms.Normalize(
                mean=[0.485, 0.456, 0.406],
                std=[0.229, 0.224, 0.225]
            ),
            transforms.RandomErasing(p=0.1, scale=(0.02, 0.1))  # Random masking
        ])
    
    def __len__(self):
        return len(self.image_paths)
    
    def __getitem__(self, idx):
        # Try cache first
        if idx in self.cache:
            img = self.cache[idx]
        else:
            # Load image
            img_path = self.image_paths[idx]
            
            try:
                # Try PIL first (faster)
                img = Image.open(img_path).convert('RGB')
            except:
                # Fallback to OpenCV
                img_cv = cv2.imread(str(img_path))
                if img_cv is None:
                    # Create dummy image if loading fails
                    img = Image.new('RGB', (224, 224), color='gray')
                else:
                    img = Image.fromarray(cv2.cvtColor(img_cv, cv2.COLOR_BGR2RGB))
            
            # Resize to standard size
            img = img.resize((224, 224), Image.Resampling.BILINEAR)
            
            # Cache jika cache belum penuh
            if len(self.cache) < self.cache_size:
                self.cache[idx] = img
        
        # Get target
        target = torch.tensor(self.targets[idx], dtype=torch.float32)
        
        # Apply transforms
        if self.augment:
            img = self.augment_transforms(img)
        else:
            img = self.transforms(img)
        
        return img, target
    
    def clear_cache(self):
        """Clear cache to free memory"""
        self.cache.clear()


def create_dataloaders(
    dataset_paths,
    batch_size=32,
    num_workers=4,
    pin_memory=True,
    shuffle=True,
    prefetch_factor=2
):
    """
    Create optimized dataloaders with distributed support
    
    Args:
        dataset_paths: Dict with 'train', 'val' paths
        batch_size: Batch size
        num_workers: Number of worker processes
        pin_memory: Pin memory untuk faster GPU transfer
        shuffle: Shuffle training data
        prefetch_factor: Prefetch factor untuk DataLoader
    
    Returns:
        train_loader, val_loader
    """
    
    train_images = dataset_paths.get('train_images', [])
    train_targets = dataset_paths.get('train_targets', None)
    val_images = dataset_paths.get('val_images', [])
    val_targets = dataset_paths.get('val_targets', None)
    
    # Create datasets
    train_dataset = OptimizedHeadPoseDataset(
        train_images, train_targets,
        augment=True, cache_size=2000
    )
    
    val_dataset = OptimizedHeadPoseDataset(
        val_images, val_targets,
        augment=False, cache_size=1000
    )
    
    # Distributed sampler (untuk DDP)
    train_sampler = torch.utils.data.distributed.DistributedSampler(
        train_dataset,
        num_replicas=torch.cuda.device_count() if torch.cuda.is_available() else 1,
        rank=0,
        shuffle=shuffle
    ) if torch.cuda.is_available() and torch.cuda.device_count() > 1 else None
    
    # DataLoaders
    train_loader = DataLoader(
        train_dataset,
        batch_size=batch_size,
        sampler=train_sampler if train_sampler else None,
        shuffle=(shuffle and train_sampler is None),
        num_workers=num_workers,
        pin_memory=pin_memory,
        prefetch_factor=prefetch_factor,
        persistent_workers=True if num_workers > 0 else False,
    )
    
    val_loader = DataLoader(
        val_dataset,
        batch_size=batch_size,
        num_workers=num_workers,
        pin_memory=pin_memory,
        prefetch_factor=prefetch_factor,
        persistent_workers=True if num_workers > 0 else False,
    )
    
    return train_loader, val_loader
