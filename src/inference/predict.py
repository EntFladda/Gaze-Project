import torch
import torch.nn.functional as F

def predict_head_pose(model, image_tensor, device=None):
    """
    Predict head pose from a single image.
    
    Args:
        model: Trained head pose model
        image_tensor: Preprocessed image tensor (C, H, W)
        device: torch device
        
    Returns:
        pitch, yaw, roll: Head pose angles (normalized -1 to 1)
    """
    if device is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    model.eval()
    model.to(device)
    
    with torch.no_grad():
        # Add batch dimension if needed
        if image_tensor.dim() == 3:
            image_tensor = image_tensor.unsqueeze(0)
        
        image_tensor = image_tensor.to(device)
        output = model(image_tensor)
        pitch, yaw, roll = output[0].cpu().numpy()
    
    return pitch, yaw, roll

def batch_predict_head_pose(model, image_batch, device=None):
    """
    Predict head pose from a batch of images.
    
    Args:
        model: Trained head pose model
        image_batch: Batch of preprocessed image tensors (B, C, H, W)
        device: torch device
        
    Returns:
        predictions: Array of shape (B, 3) with pitch, yaw, roll
    """
    if device is None:
        device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    
    model.eval()
    model.to(device)
    
    with torch.no_grad():
        image_batch = image_batch.to(device)
        predictions = model(image_batch).cpu().numpy()
    
    return predictions
