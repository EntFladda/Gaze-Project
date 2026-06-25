import os
from pathlib import Path

# Project root
PROJECT_ROOT = Path(__file__).parent.parent.parent

# Data directories
DATA_DIR = PROJECT_ROOT / "data"
RAW_DATA_DIR = DATA_DIR / "raw"
PROCESSED_DATA_DIR = DATA_DIR / "processed"
SPLITS_DIR = DATA_DIR / "splits"
CACHE_DIR = DATA_DIR / "cache"

# Model directories
MODELS_DIR = PROJECT_ROOT / "models"
CHECKPOINTS_DIR = MODELS_DIR / "checkpoints"
FINAL_MODELS_DIR = MODELS_DIR / "final"
ONNX_MODELS_DIR = MODELS_DIR / "onnx"

# Training config
DEFAULT_BATCH_SIZE = 32
DEFAULT_EPOCHS = 50
DEFAULT_LEARNING_RATE = 1e-4
DEFAULT_TRAIN_SPLIT = 0.8
DEFAULT_VAL_SPLIT = 0.1

# Model config
MODEL_INPUT_SIZE = (224, 224)
MODEL_OUTPUTS = 3  # pitch, yaw, roll

# Ensure directories exist
for directory in [RAW_DATA_DIR, PROCESSED_DATA_DIR, SPLITS_DIR, CACHE_DIR, CHECKPOINTS_DIR, FINAL_MODELS_DIR, ONNX_MODELS_DIR]:
    directory.mkdir(parents=True, exist_ok=True)
