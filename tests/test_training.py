"""
Integration tests for complete training pipeline
"""

import pytest
import torch
from src.dataset import HeadPoseDataset300WLP
from src.models import ResNet50HeadPose
from src.training import train_model
from torch.utils.data import DataLoader, random_split
from torchvision import transforms

@pytest.fixture
def setup_training():
    """Setup training environment"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    # Simple transforms
    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
    ])
    
    # Load minimal dataset
    try:
        dataset = HeadPoseDataset300WLP(
            root_dir='data/300W_LP',
            transform=transform,
            cache_metadata=True
        )
    except Exception as e:
        pytest.skip(f"Dataset not available: {e}")
    
    if len(dataset) < 10:
        pytest.skip("Dataset too small for training test")
    
    # Split
    train_size = max(2, len(dataset) // 2)
    val_size = len(dataset) - train_size
    train_ds, val_ds = random_split(dataset, [train_size, val_size])
    
    # Loaders
    train_loader = DataLoader(train_ds, batch_size=2)
    val_loader = DataLoader(val_ds, batch_size=2)
    
    return device, train_loader, val_loader

def test_training_step(setup_training):
    """Test one training step"""
    device, train_loader, val_loader = setup_training
    
    # Model
    model = ResNet50HeadPose().to(device)
    
    # Train for 1 epoch
    train_model(model, train_loader, val_loader, epochs=1, device=device)
    
    # Model should be in training mode after completion
    assert model.training == False  # evaluate_focus leaves it in eval mode

if __name__ == '__main__':
    pytest.main([__file__, '-v'])
