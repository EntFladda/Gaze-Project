import torch
import torchvision.transforms as transforms
import torchvision.transforms.functional as TF
import numpy as np
from PIL import Image, ImageEnhance
import cv2

class AdaptiveImageAugmentation:
    """Augmentasi untuk ketahanan pencahayaan."""
    
    @staticmethod
    def apply_brightness_shift(image, brightness_range=(-0.3, 0.3)):
        """Simulasi kondisi gelap atau terang."""
        if isinstance(image, torch.Tensor):
            image = TF.to_pil_image(image)
        
        enhancer = ImageEnhance.Brightness(image)
        shift = np.random.uniform(*brightness_range)
        # Ensure value stays in range [0.1, 1.9]
        factor = np.clip(1.0 + shift, 0.1, 1.9)
        return enhancer.enhance(factor)
    
    @staticmethod
    def apply_contrast_adjustment(image, contrast_range=(0.7, 1.4)):
        """Sesuaikan kontras berbagai kondisi."""
        if isinstance(image, torch.Tensor):
            image = TF.to_pil_image(image)
        
        enhancer = ImageEnhance.Contrast(image)
        factor = np.random.uniform(*contrast_range)
        return enhancer.enhance(factor)
    
    @staticmethod
    def apply_color_shift(image, color_shift_range=(-0.1, 0.1)):
        """Ubah warna simulasi pencahayaan."""
        if isinstance(image, torch.Tensor):
            image = np.array(TF.to_pil_image(image))
        else:
            image = np.array(image)
        
        # HSV adjustment
        image_hsv = cv2.cvtColor(image, cv2.COLOR_RGB2HSV).astype(np.float32)
        
        # Simulasi pencahayaan hangat atau dingin.
        shift = np.random.uniform(*color_shift_range)
        image_hsv[:, :, 0] = np.clip(image_hsv[:, :, 0] + shift * 180, 0, 180)
        
        # Shift saturation
        saturation_shift = np.random.uniform(-0.1, 0.2)
        image_hsv[:, :, 1] = np.clip(image_hsv[:, :, 1] * (1 + saturation_shift), 0, 255)
        
        image_hsv = image_hsv.astype(np.uint8)
        image = cv2.cvtColor(image_hsv, cv2.COLOR_HSV2RGB)
        
        return Image.fromarray(image)
    
    @staticmethod
    def apply_gaussian_blur(image, kernel_range=(1, 3)):
        """Beri efek blur untuk low-resolution."""
        if isinstance(image, torch.Tensor):
            image = TF.to_pil_image(image)
        
        kernel_size = np.random.randint(*kernel_range) * 2 + 1
        return image.filter(Image.BLUR)
    
    @staticmethod
    def apply_shadow_effect(image, intensity=0.3):
        """Simulasi oklusi dengan bayangan."""
        if isinstance(image, torch.Tensor):
            image = np.array(TF.to_pil_image(image))
        else:
            image = np.array(image)
        
        h, w = image.shape[:2]
        
        # Random shadow region
        x1, y1 = np.random.randint(0, w//2), np.random.randint(0, h//2)
        x2, y2 = np.random.randint(w//2, w), np.random.randint(h//2, h)
        
        # Apply darkening
        shadow_mask = np.zeros((h, w), dtype=np.float32)
        shadow_mask[y1:y2, x1:x2] = intensity
        
        for c in range(3):
            image[:, :, c] = (image[:, :, c] * (1 - shadow_mask)).astype(np.uint8)
        
        return Image.fromarray(image)
    
    @staticmethod
    def apply_highlight_effect(image, intensity=0.2):
        """Simulasi pantulan cahaya terang."""
        if isinstance(image, torch.Tensor):
            image = np.array(TF.to_pil_image(image))
        else:
            image = np.array(image)
        
        h, w = image.shape[:2]
        
        # Area highlight secara acak.
        size = max(h, w) // 6
        x = np.random.randint(0, w - size)
        y = np.random.randint(0, h - size)
        
        # Terangkan menggunakan gradien.
        for c in range(3):
            highlight = image[y:y+size, x:x+size, c].astype(np.float32)
            highlight = np.clip(highlight * (1 + intensity), 0, 255)
            image[y:y+size, x:x+size, c] = highlight.astype(np.uint8)
        
        return Image.fromarray(image)


class LightingRobustAugmentation:
    """Pipeline augmentasi ketahanan pencahayaan."""
    
    def __init__(self, p_brightness=0.5, p_contrast=0.5, p_color=0.4, p_shadow=0.2, p_highlight=0.2):
        self.p_brightness = p_brightness
        self.p_contrast = p_contrast
        self.p_color = p_color
        self.p_shadow = p_shadow
        self.p_highlight = p_highlight
        self.augment = AdaptiveImageAugmentation()
        
        # Transformasi dasar tanpa pencahayaan.
        self.basic_transforms = transforms.Compose([
            transforms.RandomHorizontalFlip(p=0.5),
            transforms.RandomRotation(degrees=15),
            transforms.RandomAffine(degrees=0, translate=(0.1, 0.1)),
        ])
    
    def __call__(self, image):
        """Apply augmentation pipeline"""
        
        # Basic geometric augmentation
        image = self.basic_transforms(image)
        
        # Lighting augmentations
        if np.random.random() < self.p_brightness:
            image = self.augment.apply_brightness_shift(image)
        
        if np.random.random() < self.p_contrast:
            image = self.augment.apply_contrast_adjustment(image)
        
        if np.random.random() < self.p_color:
            image = self.augment.apply_color_shift(image)
        
        if np.random.random() < self.p_shadow:
            image = self.augment.apply_shadow_effect(image, intensity=np.random.uniform(0.1, 0.4))
        
        if np.random.random() < self.p_highlight:
            image = self.augment.apply_highlight_effect(image, intensity=np.random.uniform(0.1, 0.3))
        
        return image


def get_augmented_transform(input_size=224, augmentation_strength='medium'):
    """Dapatkan pipeline transformasi augmentasi."""
    
    if augmentation_strength == 'weak':
        p_brightness = 0.3
        p_contrast = 0.3
        p_color = 0.2
        p_shadow = 0.1
        p_highlight = 0.1
    elif augmentation_strength == 'medium':
        p_brightness = 0.5
        p_contrast = 0.5
        p_color = 0.4
        p_shadow = 0.2
        p_highlight = 0.2
    else:  # strong
        p_brightness = 0.7
        p_contrast = 0.7
        p_color = 0.6
        p_shadow = 0.4
        p_highlight = 0.4
    
    return transforms.Compose([
        transforms.Resize((input_size, input_size)),
        LightingRobustAugmentation(
            p_brightness=p_brightness,
            p_contrast=p_contrast,
            p_color=p_color,
            p_shadow=p_shadow,
            p_highlight=p_highlight
        ),
        transforms.ToTensor(),
        transforms.Normalize(
            mean=[0.485, 0.456, 0.406],
            std=[0.229, 0.224, 0.225]
        )
    ])


def get_val_transform(input_size=224):
    """Transformasi validasi tanpa augmentasi."""
    return transforms.Compose([
        transforms.Resize((input_size, input_size)),
        transforms.ToTensor(),
        transforms.Normalize(
            mean=[0.485, 0.456, 0.406],
            std=[0.229, 0.224, 0.225]
        )
    ])
