# Training modules (consolidated in train_unified.py)
from ..train import train_model
from .validate import evaluate_focus
from .lightning_trainer import ImprovedHeadPoseModel, train_with_lightning

__all__ = ['train_model', 'evaluate_focus', 'ImprovedHeadPoseModel', 'train_with_lightning']
