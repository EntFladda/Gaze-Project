"""
Test inference pipeline
"""

import pytest
import torch
from src.models import ResNet50HeadPose
from src.inference import predict_head_pose, batch_predict_head_pose

@pytest.fixture
def model():
    """Create model instance"""
    return ResNet50HeadPose()

def test_single_prediction(model):
    """Test single image prediction"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    model = model.to(device)
    
    # Create random image tensor
    image = torch.randn(3, 224, 224)
    
    # Predict
    pitch, yaw, roll = predict_head_pose(model, image, device)
    
    # Check outputs are scalars
    assert isinstance(pitch, (int, float, np.ndarray))
    assert isinstance(yaw, (int, float, np.ndarray))
    assert isinstance(roll, (int, float, np.ndarray))

def test_batch_prediction(model):
    """Test batch prediction"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    model = model.to(device)
    
    # Create batch
    batch = torch.randn(4, 3, 224, 224)
    
    # Predict
    predictions = batch_predict_head_pose(model, batch, device)
    
    # Check shape
    assert predictions.shape == (4, 3)

if __name__ == '__main__':
    pytest.main([__file__, '-v'])
