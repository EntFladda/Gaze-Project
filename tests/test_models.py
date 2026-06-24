"""
Unit tests for model architecture
"""

import pytest
import torch
from src.models import ResNet50HeadPose

@pytest.fixture
def model():
    """Create model instance"""
    return ResNet50HeadPose()

def test_model_creation(model):
    """Test model instantiation"""
    assert isinstance(model, ResNet50HeadPose)
    assert model is not None

def test_model_forward_pass(model):
    """Test forward pass"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    model = model.to(device)
    
    # Create random input
    batch_size = 2
    input_tensor = torch.randn(batch_size, 3, 224, 224).to(device)
    
    # Forward pass
    with torch.no_grad():
        output = model(input_tensor)
    
    # Check output shape
    assert output.shape == (batch_size, 3), f"Output shape mismatch: {output.shape}"

def test_model_output_range(model):
    """Test output value ranges"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    model = model.to(device)
    model.eval()
    
    # Create random input
    input_tensor = torch.randn(1, 3, 224, 224).to(device)
    
    # Forward pass
    with torch.no_grad():
        output = model(input_tensor)
    
    # Note: Output is not constrained to [-1, 1] by default
    # Add sigmoid/tanh if needed
    print(f"Output range: [{output.min():.2f}, {output.max():.2f}]")

def test_model_gradients(model):
    """Test gradient computation"""
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    model = model.to(device)
    
    # Create input
    input_tensor = torch.randn(1, 3, 224, 224, requires_grad=True, device=device)
    output = model(input_tensor)
    
    # Backward pass
    loss = output.mean()
    loss.backward()
    
    # Check gradients
    assert input_tensor.grad is not None
    assert input_tensor.grad.shape == input_tensor.shape

if __name__ == '__main__':
    pytest.main([__file__, '-v'])
