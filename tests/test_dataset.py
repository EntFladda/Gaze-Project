"""
Unit tests for dataset loading
"""

import pytest
import torch
from src.dataset import HeadPoseDataset300WLP
from torchvision import transforms

@pytest.fixture
def dataset():
    """Create test dataset"""
    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
    ])
    return HeadPoseDataset300WLP(
        root_dir='data/300W_LP',
        transform=transform,
        cache_metadata=True
    )

def test_dataset_length(dataset):
    """Test dataset length"""
    assert len(dataset) > 0, "Dataset should not be empty"

def test_dataset_sample(dataset):
    """Test dataset sample output"""
    image, angles = dataset[0]
    
    # Check shapes
    assert image.shape == (3, 224, 224), f"Image shape mismatch: {image.shape}"
    assert angles.shape == (3,), f"Angles shape mismatch: {angles.shape}"
    
    # Check types
    assert isinstance(image, torch.Tensor)
    assert isinstance(angles, torch.Tensor)
    
    # Check value ranges
    assert image.min() >= 0.0 and image.max() <= 1.0
    assert angles.min() >= -1.0 and angles.max() <= 1.0

def test_dataset_caching(dataset):
    """Test metadata caching"""
    assert len(dataset.metadata_cache) > 0, "Metadata cache should not be empty"

if __name__ == '__main__':
    pytest.main([__file__, '-v'])
