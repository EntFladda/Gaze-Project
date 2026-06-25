import torch
import torch.nn as nn
import torch.nn.functional as F
import pytorch_lightning as pl
from pytorch_lightning.callbacks import EarlyStopping, ModelCheckpoint
from pytorch_lightning.strategies import DDPStrategy
from torch.utils.data import DataLoader
from torchvision import models
import numpy as np
from pathlib import Path

# OPTIONAL: WandB untuk experiment tracking
# try:
#     import wandb
#     from pytorch_lightning.loggers import WandbLogger
#     WANDB_AVAILABLE = True
# except ImportError:
WANDB_AVAILABLE = False
# print("[TRAIN] WandB not available - install: pip install wandb")


class ImprovedHeadPoseModel(nn.Module):
    """
    Improved head pose model dengan:
    - Better feature extraction
    - Multi-task learning (pitch, yaw, confidence)
    - Attention mechanism
    """
    def __init__(self, backbone='resnet50'):
        super().__init__()
        
        # Backbone
        if backbone == 'resnet50':
            resnet = models.resnet50(weights=models.ResNet50_Weights.IMAGENET1K_V1)
            in_features = 2048
        elif backbone == 'efficientnet_b2':
            resnet = models.efficientnet_b2(weights=models.EfficientNet_B2_Weights.IMAGENET1K_V1)
            in_features = 1408
        else:
            raise ValueError(f"Unknown backbone: {backbone}")
        
        # Remove classification head
        if backbone == 'resnet50':
            self.backbone = nn.Sequential(*list(resnet.children())[:-1])
        else:
            self.backbone = nn.Sequential(*list(resnet.children())[:-1])
        
        # Spatial attention
        self.spatial_attention = nn.Sequential(
            nn.Conv2d(in_features, 64, 1),
            nn.ReLU(),
            nn.Conv2d(64, 1, 1),
            nn.Sigmoid()
        )
        
        # Feature fusion
        self.fc_features = nn.Sequential(
            nn.Linear(in_features, 512),
            nn.BatchNorm1d(512),
            nn.ReLU(inplace=True),
            nn.Dropout(0.5),
            nn.Linear(512, 256),
            nn.BatchNorm1d(256),
            nn.ReLU(inplace=True),
            nn.Dropout(0.3),
        )
        
        # Output heads
        # Head 1: Pitch & Yaw (main outputs)
        self.head_pose = nn.Sequential(
            nn.Linear(256, 128),
            nn.ReLU(),
            nn.Dropout(0.2),
            nn.Linear(128, 2)  # pitch, yaw
        )
        
        # Head 2: Confidence (how confident we are about prediction)
        # NOTE: NO sigmoid here! BCEWithLogitsLoss applies it internally
        self.head_confidence = nn.Sequential(
            nn.Linear(256, 64),
            nn.ReLU(),
            nn.Linear(64, 1)
            # Sigmoid removed for BCEWithLogitsLoss compatibility
        )
        
        # Head 3: Roll (bonus)
        self.head_roll = nn.Sequential(
            nn.Linear(256, 64),
            nn.ReLU(),
            nn.Linear(64, 1)
        )
    
    def forward(self, x):
        # Backbone
        features = self.backbone(x)  # (B, C, H, W)
        
        # Spatial attention
        attention = self.spatial_attention(features)
        features = features * attention
        
        # Global average pooling
        features = F.adaptive_avg_pool2d(features, 1).flatten(1)
        
        # FC layers
        features = self.fc_features(features)
        
        # Multi-head outputs
        pose = self.head_pose(features)  # (B, 2) - pitch, yaw
        confidence = self.head_confidence(features)  # (B, 1)
        roll = self.head_roll(features)  # (B, 1)
        
        return pose, confidence, roll


class HeadPoseLightningModule(pl.LightningModule):
    """PyTorch Lightning wrapper untuk agile training"""
    
    def __init__(self, backbone='resnet50', lr=1e-3, 
                 pose_weight=1.0, conf_weight=0.1, roll_weight=0.1):
        super().__init__()
        
        self.model = ImprovedHeadPoseModel(backbone=backbone)
        self.lr = lr
        self.pose_weight = pose_weight
        self.conf_weight = conf_weight
        self.roll_weight = roll_weight
        
        # Loss functions
        self.pose_loss_fn = nn.MSELoss()
        # Use BCEWithLogitsLoss instead of BCELoss for mixed precision safety
        self.conf_loss_fn = nn.BCEWithLogitsLoss()
        
        self.save_hyperparameters()
    
    def forward(self, x):
        return self.model(x)
    
    def training_step(self, batch, batch_idx):
        images, targets = batch
        
        # targets shape: (B, 3) -> pitch, yaw, roll
        pitch_yaw_target = targets[:, :2]
        roll_target = targets[:, 2:3]
        
        pose, confidence, roll = self(images)
        
        # Compute losses
        pose_loss = self.pose_loss_fn(pose, pitch_yaw_target)
        roll_loss = self.pose_loss_fn(roll, roll_target)
        
        # Confidence loss: high when prediction is accurate
        errors = torch.abs(pose - pitch_yaw_target).mean(dim=1, keepdim=True)
        # Confidence should be high (1) when error is low, low (0) when error is high
        target_conf = torch.clamp(1.0 - errors / 90.0, 0, 1)
        conf_loss = self.conf_loss_fn(confidence, target_conf)
        
        # Total loss
        total_loss = (self.pose_weight * pose_loss + 
                      self.conf_weight * conf_loss + 
                      self.roll_weight * roll_loss)
        
        self.log('train_pose_loss', pose_loss, prog_bar=True)
        self.log('train_conf_loss', conf_loss, prog_bar=True)
        self.log('train_loss', total_loss, prog_bar=True)
        
        return total_loss
    
    def validation_step(self, batch, batch_idx):
        images, targets = batch
        
        pitch_yaw_target = targets[:, :2]
        roll_target = targets[:, 2:3]
        
        pose, confidence, roll = self(images)
        
        # Losses
        pose_loss = self.pose_loss_fn(pose, pitch_yaw_target)
        roll_loss = self.pose_loss_fn(roll, roll_target)
        errors = torch.abs(pose - pitch_yaw_target).mean(dim=1, keepdim=True)
        target_conf = torch.clamp(1.0 - errors / 90.0, 0, 1)
        conf_loss = self.conf_loss_fn(confidence, target_conf)
        
        total_loss = (self.pose_weight * pose_loss + 
                      self.conf_weight * conf_loss + 
                      self.roll_weight * roll_loss)
        
        # Compute metrics
        pose_mae = torch.abs(pose - pitch_yaw_target).mean()  # Mean Absolute Error
        
        # Count "accurate" predictions (within 10 degrees)
        accurate = (torch.abs(pose - pitch_yaw_target) <= 10.0).float().mean()
        
        self.log('val_pose_loss', pose_loss, prog_bar=True)
        self.log('val_loss', total_loss, prog_bar=True)
        self.log('val_pose_mae', pose_mae, prog_bar=True)
        self.log('val_accuracy_10deg', accurate, prog_bar=True)
        
        return total_loss
    
    def configure_optimizers(self):
        optimizer = torch.optim.AdamW(
            self.parameters(), 
            lr=self.lr,
            weight_decay=1e-4,
            betas=(0.9, 0.999)
        )
        
        # Learning rate scheduler
        scheduler = torch.optim.lr_scheduler.OneCycleLR(
            optimizer,
            max_lr=self.lr,
            total_steps=self.trainer.estimated_stepping_batches,
            pct_start=0.3,
            anneal_strategy='cos',
            cycle_momentum=True,
            base_momentum=0.85,
            max_momentum=0.95
        )
        
        return {
            'optimizer': optimizer,
            'lr_scheduler': {
                'scheduler': scheduler,
                'interval': 'step'
            }
        }


def train_with_lightning(
    train_loader,
    val_loader,
    backbone='resnet50',
    max_epochs=50,
    batch_size=32,
    lr=1e-3,
    use_wandb=True,
    use_ddp=False,
    mixed_precision='16-mixed',
    checkpoint_dir='checkpoints/final/lightning'
):
    """
    Train head pose model dengan PyTorch Lightning
    
    Args:
        train_loader: Training DataLoader
        val_loader: Validation DataLoader
        backbone: Model backbone ('resnet50', 'efficientnet_b2')
        max_epochs: Maximum epochs
        batch_size: Batch size
        lr: Learning rate
        use_wandb: Use Weights & Biases logging
        use_ddp: Use Distributed Data Parallel
        mixed_precision: '16-mixed' for half precision, '32' for full
        checkpoint_dir: Directory untuk save checkpoints
    """
    
    # Create checkpoint dir
    Path(checkpoint_dir).mkdir(parents=True, exist_ok=True)
    
    # Initialize logger
    logger = None
    # OPTIONAL: Enable WandB experiment tracking
    # if use_wandb and WANDB_AVAILABLE:
    #     logger = WandbLogger(
    #         project="gaze-detection",
    #         name=f"head-pose-{backbone}-{max_epochs}ep",
    #         log_model=True
    #     )
    
    # Callbacks
    callbacks = [
        # Early stopping
        EarlyStopping(
            monitor='val_loss',
            patience=10,
            min_delta=0.001,
            mode='min',
            verbose=True
        ),
        # Model checkpointing
        ModelCheckpoint(
            dirpath=checkpoint_dir,
            filename='head-pose-{epoch:02d}-{val_loss:.3f}',
            monitor='val_loss',
            mode='min',
            save_top_k=3,
            verbose=True
        )
    ]
    
    # Strategy
    strategy = 'auto'
    # OPTIONAL: Enable DDP for multi-GPU training
    # if use_ddp and torch.cuda.device_count() > 1:
    #     strategy = DDPStrategy(find_unused_parameters=False)
    
    # Trainer
    trainer = pl.Trainer(
        max_epochs=max_epochs,
        accelerator='gpu' if torch.cuda.is_available() else 'cpu',
        devices='auto',
        strategy=strategy,
        precision=mixed_precision,
        logger=logger,
        callbacks=callbacks,
        log_every_n_steps=10,
        enable_progress_bar=True,
        deterministic=True,
        gradient_clip_val=1.0,
    )
    
    # Model
    model = HeadPoseLightningModule(backbone=backbone, lr=lr)
    
    # Train
    print(f"\n[TRAIN] Starting training with PyTorch Lightning")
    print(f"[TRAIN] Backbone: {backbone}")
    print(f"[TRAIN] Max epochs: {max_epochs}")
    print(f"[TRAIN] Batch size: {batch_size}")
    print(f"[TRAIN] Learning rate: {lr}")
    print(f"[TRAIN] Mixed precision: {mixed_precision}")
    print(f"[TRAIN] DDP: {use_ddp}")
    
    trainer.fit(
        model,
        train_dataloaders=train_loader,
        val_dataloaders=val_loader
    )
    
    return model, trainer


def test_single_batch_overfit(
    train_loader,
    backbone='resnet50',
    epochs=100,
    lr=1e-2
):
    """
    Test if model can overfit a single batch
    
    This is a sanity check: if model cannot achieve near-zero loss
    on a single batch, something is wrong with architecture/code
    """
    print("\n[DEBUG] Testing single batch overfitting...")
    print("[DEBUG] This should reach loss ~0.001 within 100 epochs if model is OK\n")
    
    # Get single batch
    batch_images, batch_targets = next(iter(train_loader))
    
    model = HeadPoseLightningModule(backbone=backbone, lr=lr)
    
    trainer = pl.Trainer(
        max_epochs=epochs,
        accelerator='gpu' if torch.cuda.is_available() else 'cpu',
        logger=False,
        enable_progress_bar=True,
        enable_checkpointing=False,
    )
    
    # Create single-batch dataloader (must return the batch, not wrap it again)
    # batch_images shape: [batch_size, 3, 224, 224] - already a batch!
    # We create a simple dataset that returns the same batch every time
    class SingleBatchDataset(torch.utils.data.Dataset):
        def __init__(self, images, targets):
            self.images = images
            self.targets = targets
        
        def __len__(self):
            return len(self.images)
        
        def __getitem__(self, idx):
            return self.images[idx], self.targets[idx]
    
    dataset = SingleBatchDataset(batch_images, batch_targets)
    single_batch_loader = DataLoader(
        dataset,
        batch_size=len(batch_images),
        shuffle=False
    )
    
    trainer.fit(
        model,
        train_dataloaders=single_batch_loader,
        val_dataloaders=single_batch_loader
    )
    
    return model


def get_lightning_model(checkpoint_path=None, backbone='resnet50'):
    """Load trained model from checkpoint"""
    if checkpoint_path:
        return HeadPoseLightningModule.load_from_checkpoint(checkpoint_path)
    else:
        return HeadPoseLightningModule(backbone=backbone)
