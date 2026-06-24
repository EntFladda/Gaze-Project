#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import torch
import torch.nn as nn
import torch.optim as optim
from torch.optim.lr_scheduler import ReduceLROnPlateau, CosineAnnealingLR
import time
import numpy as np
from tqdm import tqdm

def compute_angular_error(pred, target):
    """
    Compute angular error between predicted dan target gaze direction
    
    Args:
        pred: (B, 3) normalized angles
        target: (B, 3) normalized angles
    
    Returns:
        Angular error in degrees
    """
    # Denormalize dari [-1, 1] ke radians
    pred_rad = pred * np.pi / 2  # assuming [-1, 1] represents [-π/2, π/2]
    target_rad = target * np.pi / 2
    
    # Compute difference
    diff = torch.abs(pred_rad - target_rad)
    
    # Average angular error
    mae = diff.mean()
    
    return mae * 180 / np.pi  # Convert to degrees

def train_model(model, train_loader, val_loader, epochs=10, device=None, 
                model_name="improved_gaze_model", checkpoint_dir="checkpoints"):
    """
    Enhanced training function dengan:
    - Better learning rate scheduling
    - Early stopping
    - Gradient clipping
    - Mixed precision training (optional)
    """
    import os
    
    print(f"[TRAIN] Entering train_model function...", flush=True)
    if device is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    print(f"[TRAIN] Device: {device}", flush=True)
    if torch.cuda.is_available():
        print("[TRAIN] GPU-Accelerated Training Mode", flush=True)
        print(f"[TRAIN] GPU: {torch.cuda.get_device_name(0)}", flush=True)
        print(f"[TRAIN] GPU Memory: {torch.cuda.get_device_properties(0).total_memory / 1e9:.1f} GB", flush=True)
    else:
        print("[TRAIN] CPU Training Mode", flush=True)
        print(f"[TRAIN] Threads: {torch.get_num_threads()}", flush=True)

    model = model.to(device)
    
    # Loss function dengan balancing
    criterion = nn.MSELoss()
    
    # Optimizer dengan weight decay untuk regularization
    optimizer = optim.AdamW(model.parameters(), lr=1e-4, weight_decay=1e-5)
    
    # Learning rate scheduler
    scheduler = CosineAnnealingLR(optimizer, T_max=epochs, eta_min=1e-6)
    
    # Early stopping parameters
    best_val_loss = float('inf')
    patience = 5
    patience_counter = 0
    
    # Create checkpoint directory
    os.makedirs(checkpoint_dir, exist_ok=True)
    
    print(f"\n[TRAIN] Training Configuration:")
    print(f"  - Model: {model_name}")
    print(f"  - Epochs: {epochs}")
    print(f"  - Loss: MSELoss")
    print(f"  - Optimizer: AdamW (lr=1e-4, weight_decay=1e-5)")
    print(f"  - LR Scheduler: CosineAnnealing")
    print(f"  - Checkpoint dir: {checkpoint_dir}")
    print()

    for epoch in range(epochs):
        print(f"\n{'='*60}")
        print(f"Epoch {epoch+1}/{epochs}")
        print(f"{'='*60}")
        
        # ==================== TRAINING ====================
        model.train()
        running_loss = 0
        batch_count = 0
        epoch_start = time.time()
        total_batches = len(train_loader)
        
        pbar = tqdm(enumerate(train_loader), total=total_batches, desc="Training")
        
        for batch_idx, (x, y) in pbar:
            x, y = x.to(device), y.to(device)
            
            # Forward pass
            optimizer.zero_grad()
            out = model(x)
            loss = criterion(out, y)
            
            # Backward pass dengan gradient clipping
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), max_norm=1.0)
            optimizer.step()
            
            running_loss += loss.item()
            batch_count += 1
            
            # Update progress bar
            avg_loss = running_loss / batch_count
            pbar.set_postfix({'loss': f'{avg_loss:.4f}'})

        avg_train_loss = running_loss / batch_count
        
        # ==================== VALIDATION ====================
        print(f"[TRAIN] Starting validation...")
        model.eval()
        val_loss = 0
        val_count = 0
        val_angular_errors = []
        
        with torch.no_grad():
            pbar_val = tqdm(val_loader, desc="Validation")
            for x, y in pbar_val:
                x, y = x.to(device), y.to(device)
                out = model(x)
                loss = criterion(out, y)
                val_loss += loss.item()
                val_count += 1
                
                # Compute angular error
                angular_err = compute_angular_error(out, y)
                val_angular_errors.append(angular_err.item())

        avg_val_loss = val_loss / val_count
        avg_angular_error = np.mean(val_angular_errors)
        elapsed = time.time() - epoch_start
        
        # Print epoch results
        print(f"\n[TRAIN] Results:")
        print(f"  Train Loss: {avg_train_loss:.6f}")
        print(f"  Val Loss:   {avg_val_loss:.6f}")
        print(f"  Angular Error: {avg_angular_error:.2f}°")
        print(f"  Time: {elapsed:.1f}s")
        
        # ==================== LR SCHEDULING ====================
        scheduler.step()
        current_lr = optimizer.param_groups[0]['lr']
        print(f"  LR: {current_lr:.2e}")
        
        # ==================== CHECKPOINT SAVING ====================
        if avg_val_loss < best_val_loss:
            best_val_loss = avg_val_loss
            patience_counter = 0
            
            # Save best model
            checkpoint_path = f"{checkpoint_dir}/{model_name}_best.pt"
            torch.save({
                'epoch': epoch + 1,
                'model_state_dict': model.state_dict(),
                'optimizer_state_dict': optimizer.state_dict(),
                'loss': avg_val_loss,
                'angular_error': avg_angular_error,
                'config': {
                    'model_name': model_name,
                    'input_size': 224,
                }
            }, checkpoint_path)
            print(f"  ✅ Best model saved: {checkpoint_path}")
        else:
            patience_counter += 1
            print(f"  No improvement (patience: {patience_counter}/{patience})")
        
        # Early stopping
        if patience_counter >= patience:
            print(f"\n[TRAIN] Early stopping triggered after {epoch+1} epochs")
            break
    
    print(f"\n{'='*60}")
    print(f"Training completed!")
    print(f"Best Val Loss: {best_val_loss:.6f}")
    print(f"{'='*60}\n")
    
    return model


def evaluate_model(model, val_loader, device=None):
    """
    Evaluate model pada validation set
    """
    if device is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    model = model.to(device)
    model.eval()
    
    criterion = nn.MSELoss()
    total_loss = 0
    angular_errors = []
    
    print("[EVAL] Starting evaluation...")
    with torch.no_grad():
        for x, y in tqdm(val_loader, desc="Evaluation"):
            x, y = x.to(device), y.to(device)
            out = model(x)
            loss = criterion(out, y)
            total_loss += loss.item()
            
            angular_err = compute_angular_error(out, y)
            angular_errors.append(angular_err.item())
    
    avg_loss = total_loss / len(val_loader)
    avg_angular_error = np.mean(angular_errors)
    
    print(f"\n[EVAL] Results:")
    print(f"  Average Loss: {avg_loss:.6f}")
    print(f"  Average Angular Error: {avg_angular_error:.2f}°")
    print(f"  Median Angular Error: {np.median(angular_errors):.2f}°")
    print(f"  Max Angular Error: {np.max(angular_errors):.2f}°")
    
    return {
        'loss': avg_loss,
        'angular_error': avg_angular_error,
        'angular_errors': angular_errors
    }


# Untuk backward compatibility
def evaluate_focus(*args, **kwargs):
    """Placeholder untuk backward compatibility"""
    pass
