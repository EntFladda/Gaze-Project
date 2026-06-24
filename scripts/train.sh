#!/bin/bash

# Gaze Focus Project - Training Script
# Usage: ./train.sh [epochs] [batch_size]

EPOCHS=${1:-50}
BATCH_SIZE=${2:-32}

echo "================================"
echo "🚀 Starting Training Pipeline"
echo "================================"
echo "Epochs: $EPOCHS"
echo "Batch Size: $BATCH_SIZE"
echo "================================"

python scripts/train_runner.py --epochs $EPOCHS --batch_size $BATCH_SIZE

if [ $? -eq 0 ]; then
    echo "✅ Training completed successfully"
else
    echo "❌ Training failed"
    exit 1
fi
