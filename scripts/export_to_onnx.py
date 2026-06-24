#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Export ImprovedHeadPoseModel to ONNX format for web deployment
Smaller, faster, and more portable than PyTorch checkpoints
"""

import torch
import argparse
from pathlib import Path
import sys

# Add parent to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from src.training.lightning_trainer import ImprovedHeadPoseModel


def export_to_onnx(checkpoint_path, output_dir='web/public/models'):
    """
    Export PyTorch model to ONNX format
    
    Args:
        checkpoint_path: Path to .pt checkpoint
        output_dir: Output directory for ONNX model
    
    Returns:
        Path to exported ONNX model
    """
    output_path = Path(output_dir)
    output_path.mkdir(parents=True, exist_ok=True)
    
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
    
    print(f"📦 Loading model from: {checkpoint_path}")
    # Load model
    model = ImprovedHeadPoseModel(backbone='resnet50').to(device)
    model.load_state_dict(torch.load(checkpoint_path, map_location=device, weights_only=False))
    model.eval()
    
    print(f"✅ Model loaded successfully")
    
    # Create dummy input
    dummy_input = torch.randn(1, 3, 224, 224).to(device)
    
    # Export to ONNX
    onnx_path = output_path / 'head_pose_model.onnx'
    
    print(f"🔄 Exporting to ONNX: {onnx_path}")
    torch.onnx.export(
        model,
        dummy_input,
        str(onnx_path),
        input_names=['input'],
        output_names=['pose', 'confidence', 'roll'],
        opset_version=14,
        dynamic_axes={'input': {0: 'batch_size'}},
        verbose=False
    )
    
    onnx_size_mb = onnx_path.stat().st_size / (1024 * 1024)
    print(f"✅ ONNX model exported: {onnx_path}")
    print(f"   Size: {onnx_size_mb:.2f} MB")
    print(f"   Outputs: pose (batch, 2), confidence (batch, 1), roll (batch, 1)")
    
    return str(onnx_path)


def main():
    parser = argparse.ArgumentParser(description='Export PyTorch Model to ONNX')
    parser.add_argument('--checkpoint', type=str, default='checkpoints/final/head_pose_best.pt',
                       help='Path to PyTorch checkpoint')
    parser.add_argument('--output', type=str, default='web/public/models',
                       help='Output directory for ONNX model')
    
    args = parser.parse_args()
    
    checkpoint_path = Path(args.checkpoint)
    if not checkpoint_path.exists():
        print(f"❌ Checkpoint not found: {checkpoint_path}")
        sys.exit(1)
    
    try:
        onnx_path = export_to_onnx(str(checkpoint_path), args.output)
        print(f"\n✅ SUCCESS - Model exported to: {onnx_path}")
        print(f"\n📝 Next steps for web deployment:")
        print(f"   1. Start static web server from web/")
        print(f"   2. Browser loads and caches public/models/head_pose_model.onnx")
        print(f"   3. No webcam frame upload is needed for on-device mode")
        
    except Exception as e:
        print(f"❌ Export failed: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()
