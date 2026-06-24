"""
Evaluation Script
Evaluate trained model on test dataset
"""

import argparse
import torch
from torchvision import transforms
from torch.utils.data import DataLoader, random_split
from pathlib import Path

from src.dataset import HeadPoseDataset300WLP
from src.models import ResNet50HeadPose
from src.training import evaluate_focus
from src.utils import setup_logger, FINAL_MODELS_DIR

def main():
    parser = argparse.ArgumentParser(description='Evaluate Head Pose Model')
    parser.add_argument('--model_path', type=str, default='models/final/model.pth',
                       help='Path to trained model')
    parser.add_argument('--dataset_path', type=str, default='data/300W_LP',
                       help='Dataset path')
    parser.add_argument('--batch_size', type=int, default=32, help='Batch size')
    parser.add_argument('--threshold', type=float, default=15, help='Focus threshold in degrees')
    parser.add_argument('--test_split', type=float, default=0.1, help='Test set ratio')
    
    args = parser.parse_args()
    
    # Setup logger
    logger = setup_logger('evaluate')
    
    # Device
    device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
    logger.info(f"Device: {device}")
    
    # Model
    logger.info("Loading model...")
    model = ResNet50HeadPose().to(device)
    model.load_state_dict(torch.load(args.model_path, map_location=device))
    
    # Transforms
    transform = transforms.Compose([
        transforms.Resize((224, 224)),
        transforms.ToTensor(),
        transforms.Normalize(mean=[0.485, 0.456, 0.406],
                           std=[0.229, 0.224, 0.225])
    ])
    
    # Load dataset
    logger.info("Loading dataset...")
    dataset = HeadPoseDataset300WLP(
        root_dir=args.dataset_path,
        transform=transform,
        cache_metadata=True
    )
    
    # Create test split
    test_size = int(args.test_split * len(dataset))
    other_size = len(dataset) - test_size
    test_ds, _ = random_split(dataset, [test_size, other_size])
    
    test_loader = DataLoader(
        test_ds,
        batch_size=args.batch_size,
        shuffle=False,
        num_workers=4,
        pin_memory=torch.cuda.is_available()
    )
    
    # Evaluate
    logger.info(f"Evaluating with threshold: {args.threshold} degrees...")
    evaluate_focus(model, test_loader, threshold_deg=args.threshold, device=device)

if __name__ == '__main__':
    main()
