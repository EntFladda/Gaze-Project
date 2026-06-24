import torch.nn as nn
from torchvision import models

class ResNet50HeadPose(nn.Module):
    def __init__(self):
        super().__init__()
        self.model = models.resnet50(weights=models.ResNet50_Weights.IMAGENET1K_V1)
        self.model.fc = nn.Linear(self.model.fc.in_features, 3)

    def forward(self, x):
        return self.model(x)
