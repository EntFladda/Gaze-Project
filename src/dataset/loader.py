import os
import math
import torch
import scipy.io as sio
from PIL import Image
import numpy as np
from torch.utils.data import Dataset
import pickle
from pathlib import Path

class HeadPoseDataset300WLP(Dataset):
    def __init__(self, root_dir, transform=None, require_images=False, cache_metadata=True):
        self.root_dir = root_dir
        self.transform = transform
        self.require_images = require_images
        self.cache_metadata = cache_metadata
        self.samples = []
        self.metadata_cache = {}  # In-memory cache for pose data

        self.sub_folders = [
            'AFW','AFW_Flip','HELEN','HELEN_Flip',
            'IBUG','IBUG_Flip','LFPW','LFPW_Flip'
        ]

        cache_file = os.path.join(root_dir, '.dataset_cache.pkl')
        
        print("[LOAD] Indexing 300W-LP dataset...")

        # Try loading from cache
        if cache_metadata and os.path.exists(cache_file):
            print("[LOAD] Loading from cache...")
            try:
                with open(cache_file, 'rb') as f:
                    self.samples, self.metadata_cache = pickle.load(f)
                print(f"[LOAD] Loaded {len(self.samples)} samples from cache")
                return
            except Exception as e:
                print(f"[LOAD] Cache load failed: {e}, rebuilding...")

        for sub in self.sub_folders:
            path = os.path.join(root_dir, sub)
            if not os.path.exists(path):
                continue

            files = os.listdir(path)
            mat_files = [f for f in files if f.endswith('.mat')]
            print(f"[LOAD] Scanning {sub}: {len(mat_files)} files")

            for mat_f in mat_files:
                img_f = mat_f.replace('.mat', '.jpg')
                img_path = os.path.join(path, img_f)
                mat_path = os.path.join(path, mat_f)
                
                # If require_images is True, only add if image exists
                if require_images and img_f not in files:
                    continue
                
                # Pre-load and cache pose data
                try:
                    mat = sio.loadmat(mat_path)
                    pose = mat['Pose_Para'][0]
                    pitch, yaw, roll = pose[:3]
                    # Normalize [-1,1]
                    pitch = (pitch * 180 / math.pi) / 90.0
                    yaw = (yaw * 180 / math.pi) / 90.0
                    roll = (roll * 180 / math.pi) / 90.0
                    self.metadata_cache[mat_path] = (pitch, yaw, roll)
                except Exception as e:
                    print(f"[LOAD] Warning - Failed to cache {mat_f}: {e}")
                    continue
                    
                self.samples.append((mat_path, img_path))

        # Save cache for future runs
        if cache_metadata and self.samples:
            try:
                with open(cache_file, 'wb') as f:
                    pickle.dump((self.samples, self.metadata_cache), f)
                print(f"[LOAD] Cached metadata: {len(self.samples)} samples")
            except Exception as e:
                print(f"[LOAD] Warning - Could not save cache: {e}")

        print(f"[LOAD] 300W-LP Total: {len(self.samples)} samples")

    def __len__(self):
        return len(self.samples)

    def __getitem__(self, idx):
        mat_path, img_path = self.samples[idx]

        try:
            # Load or create image
            if os.path.exists(img_path):
                image = Image.open(img_path).convert('RGB')
            else:
                # Create a placeholder image if file doesn't exist
                image = Image.new('RGB', (224, 224), color=(128, 128, 128))
            
            # Use cached metadata instead of reloading .mat file
            if mat_path in self.metadata_cache:
                pitch, yaw, roll = self.metadata_cache[mat_path]
            else:
                # Fallback: load if not cached (shouldn't happen with cache_metadata=True)
                mat = sio.loadmat(mat_path)
                pose = mat['Pose_Para'][0]
                pitch, yaw, roll = pose[:3]
                pitch = (pitch * 180 / math.pi) / 90.0
                yaw = (yaw * 180 / math.pi) / 90.0
                roll = (roll * 180 / math.pi) / 90.0

            label = torch.tensor([pitch, yaw, roll], dtype=torch.float32)

            if self.transform:
                image = self.transform(image)

            return image, label

        except Exception as e:
            print(f"Error loading {mat_path}: {e}")
            return self.__getitem__((idx + 1) % len(self))


class HeadPoseDatasetMPIIGaze(Dataset):
    """
    MPIIGaze dataset loader dengan annotation.txt files.
    
    Dataset structure:
    - data/MPIIGaze/Data/Original/p00/day01/
      - 0001.jpg, 0002.jpg, ... (images)
      - annotation.txt (annotations)
    
    Annotation format (41 values per line):
    - 1-24: Eye landmarks (12 points × 2 coordinates)
    - 25-26: On-screen gaze (screen coordinates)
    - 27-29: 3D gaze direction (x, y, z)
    - 30-35: 3D head pose (rotation_x, rotation_y, rotation_z, translation_x, translation_y, translation_z)
    - 36-38: 3D right eye center
    - 39-41: 3D left eye center
    
    Kami menggunakan nilai 27-29 (3D gaze direction) untuk pitch/yaw angles.
    """
    
    def __init__(self, root_dir, use_normalized=False, transform=None, cache_metadata=True):
        self.root_dir = root_dir
        self.transform = transform
        self.cache_metadata = cache_metadata
        self.samples = []
        self.metadata_cache = {}
        
        # MPIIGaze menggunakan Original folder (bukan Normalized)
        # Karena Original punya annotation.txt dengan data lengkap
        data_dir = os.path.join(root_dir, 'Data', 'Original')
        
        cache_file = os.path.join(root_dir, '.mpiigaze_cache_original.pkl')
        
        print("[LOAD] Indexing MPIIGaze dataset...")
        
        # Try loading from cache
        if cache_metadata and os.path.exists(cache_file):
            print(f"[LOAD] Loading cache (Original)...")
            try:
                with open(cache_file, 'rb') as f:
                    cached_samples, cached_metadata = pickle.load(f)
                    if cached_samples and len(cached_samples) > 0:
                        self.samples = cached_samples
                        self.metadata_cache = cached_metadata
                        print(f"[LOAD] Loaded {len(self.samples)} samples from cache")
                        return
            except Exception as e:
                print(f"[LOAD] Cache load failed: {e}, rebuilding...")
        
        if not os.path.exists(data_dir):
            print(f"[LOAD] MPIIGaze data directory not found: {data_dir}")
            return
        
        # Iterate through person folders (p00, p01, ..., p14)
        person_folders = sorted([d for d in os.listdir(data_dir) 
                                if os.path.isdir(os.path.join(data_dir, d)) and d.startswith('p')])
        
        print(f"[LOAD] Found {len(person_folders)} person folders")
        samples_loaded = 0
        
        for person in person_folders:
            person_path = os.path.join(data_dir, person)
            day_folders = sorted([d for d in os.listdir(person_path) 
                                 if os.path.isdir(os.path.join(person_path, d)) and d.startswith('day')])
            
            for day in day_folders:
                day_path = os.path.join(person_path, day)
                annotation_file = os.path.join(day_path, 'annotation.txt')
                
                if not os.path.exists(annotation_file):
                    continue
                
                try:
                    # Read annotation file
                    with open(annotation_file, 'r') as f:
                        lines = f.readlines()
                    
                    for img_idx, line in enumerate(lines, 1):
                        values = line.strip().split()
                        if len(values) < 29:  # Need at least 29 values for 3D gaze
                            continue
                        
                        img_num = f"{img_idx:04d}.jpg"
                        img_path = os.path.join(day_path, img_num)
                        
                        if not os.path.exists(img_path):
                            continue
                        
                        try:
                            # Extract 3D gaze direction (values 27-29, indices 26-28)
                            # Format: gaze_x, gaze_y, gaze_z
                            gaze_x = float(values[26])
                            gaze_y = float(values[27])
                            gaze_z = float(values[28])
                            
                            # Convert 3D gaze vector to pitch/yaw angles
                            # theta = asin(-y), phi = atan2(-x, -z)
                            pitch = float(np.arcsin(np.clip(-gaze_y, -1, 1)))  
                            yaw = float(np.arctan2(-gaze_x, -gaze_z))
                            
                            # Normalize to [-1, 1] range (±π/2 for pitch, ±π for yaw)
                            pitch = pitch / (np.pi / 2)
                            yaw = yaw / np.pi
                            
                            # Clip to [-1, 1]
                            pitch = np.clip(pitch, -1, 1)
                            yaw = np.clip(yaw, -1, 1)
                            
                            cache_key = img_path
                            self.metadata_cache[cache_key] = (pitch, yaw, 0.0)
                            self.samples.append(img_path)
                            samples_loaded += 1
                        
                        except (ValueError, IndexError) as e:
                            continue
                
                except Exception as e:
                    print(f"[LOAD] Warning - Error reading {annotation_file}: {str(e)[:60]}")
                    continue
        
        # Save cache
        if cache_metadata and self.samples:
            try:
                with open(cache_file, 'wb') as f:
                    pickle.dump((self.samples, self.metadata_cache), f)
                print(f"[LOAD] Cached {len(self.samples)} MPIIGaze samples")
            except Exception as e:
                pass
        
        print(f"[LOAD] MPIIGaze Total: {len(self.samples)} samples")

    
    def __len__(self):
        return len(self.samples)
    
    def __getitem__(self, idx):
        img_path = self.samples[idx]
        
        try:
            # Load image
            image = Image.open(img_path).convert('RGB')
            
            # Get cached gaze label
            if img_path in self.metadata_cache:
                pitch, yaw, roll = self.metadata_cache[img_path]
            else:
                pitch, yaw, roll = 0.0, 0.0, 0.0
            
            label = torch.tensor([pitch, yaw, roll], dtype=torch.float32)
            
            if self.transform:
                image = self.transform(image)
            
            return image, label
        
        except Exception as e:
            print(f"Error loading {img_path}: {e}")
            return self.__getitem__((idx + 1) % len(self))


class HeadPoseDatasetLocal(Dataset):
    """
    Local dataset loader for custom recordings (Dika, Tono).
    
    Dataset structure:
    - data/Local/Dika/*.jpg
    - data/Local/Tono/*.jpg
    - data/Local/annotations.csv (or person/annotations.csv)
    
    CSV format: image_name, pitch, yaw, roll
    """
    
    def __init__(self, root_dir, transform=None, cache_metadata=True):
        self.root_dir = root_dir
        self.transform = transform
        self.cache_metadata = cache_metadata
        self.samples = []
        self.metadata_cache = {}
        
        cache_file = os.path.join(root_dir, '.local_cache.pkl')
        
        print("[LOAD] Indexing Local dataset...")
        
        if cache_metadata and os.path.exists(cache_file):
            print("[LOAD] Loading from cache...")
            try:
                with open(cache_file, 'rb') as f:
                    self.samples, self.metadata_cache = pickle.load(f)
                print(f"[LOAD] Loaded {len(self.samples)} samples from cache")
                return
            except Exception as e:
                print(f"[LOAD] Cache load failed: {e}, rebuilding...")
        
        if not os.path.exists(root_dir):
            print(f"[LOAD] Local data directory not found: {root_dir}")
            return
        
        # Scan for person folders (Dika, Tono, etc.)
        person_folders = [d for d in os.listdir(root_dir)
                         if os.path.isdir(os.path.join(root_dir, d)) 
                         and d not in ['.', '..']]
        
        print(f"[LOAD] Found {len(person_folders)} person folders")
        
        for person in sorted(person_folders):
            person_path = os.path.join(root_dir, person)
            
            # Look for images
            image_files = sorted([f for f in os.listdir(person_path) 
                                if f.lower().endswith(('.jpg', '.png', '.jpeg'))])
            
            print(f"[LOAD] Scanning {person}: {len(image_files)} images")
            
            # Try to load annotations CSV
            csv_file = os.path.join(person_path, 'annotations.csv')
            annotations = {}
            
            if os.path.exists(csv_file):
                print(f"[LOAD]    Loading annotations from {csv_file}")
                try:
                    import csv as csv_module
                    with open(csv_file, 'r') as f:
                        reader = csv_module.DictReader(f)
                        for row in reader:
                            img_name = row.get('image', row.get('filename', ''))
                            pitch = float(row.get('pitch', 0.0))
                            yaw = float(row.get('yaw', 0.0))
                            roll = float(row.get('roll', 0.0))
                            annotations[img_name] = (pitch, yaw, roll)
                except Exception as e:
                    print(f"      Warning: Could not load CSV: {e}")
            
            # Add image files to samples
            for img_name in image_files:
                img_path = os.path.join(person_path, img_name)
                
                # Get gaze labels from annotations or use defaults
                if img_name in annotations:
                    pitch, yaw, roll = annotations[img_name]
                else:
                    # Default: assume looking at camera (0, 0, 0)
                    pitch, yaw, roll = 0.0, 0.0, 0.0
                
                self.metadata_cache[img_path] = (pitch, yaw, roll)
                self.samples.append((img_path, person))
        
        # Save cache
        if cache_metadata and self.samples:
            try:
                with open(cache_file, 'wb') as f:
                    pickle.dump((self.samples, self.metadata_cache), f)
                print(f"[LOAD] Cached metadata: {len(self.samples)} samples")
            except Exception as e:
                print(f"[LOAD] Warning - Could not save cache: {e}")
        
        print(f"[LOAD] Local Total: {len(self.samples)} samples")
    
    def __len__(self):
        return len(self.samples)
    
    def __getitem__(self, idx):
        img_path, person = self.samples[idx]
        
        try:
            # Load image
            image = Image.open(img_path).convert('RGB')
            
            # Get cached gaze label
            if img_path in self.metadata_cache:
                pitch, yaw, roll = self.metadata_cache[img_path]
            else:
                pitch, yaw, roll = 0.0, 0.0, 0.0
            
            label = torch.tensor([pitch, yaw, roll], dtype=torch.float32)
            
            if self.transform:
                image = self.transform(image)
            
            return image, label
        
        except Exception as e:
            print(f"Error loading {img_path}: {e}")
            return self.__getitem__((idx + 1) % len(self))


class CombinedDataset(Dataset):
    """
    Combine multiple head pose datasets into one.
    
    Usage:
        dataset = CombinedDataset([
            HeadPoseDataset300WLP('data/300W_LP', transform=transform),
            HeadPoseDatasetMPIIGaze('data/MPIIGaze', transform=transform),
            HeadPoseDatasetLocal('data/Local', transform=transform),
        ])
    """
    
    def __init__(self, datasets):
        self.datasets = datasets
        self.cumulative_sizes = np.cumsum([len(d) for d in datasets])
        self.total_samples = self.cumulative_sizes[-1]
        
        print(f"\n[LOAD] Combined Dataset Summary:")
        for i, dataset in enumerate(datasets):
            print(f"[LOAD] Dataset {i+1}: {len(dataset)} samples")
        print(f"[LOAD] Total: {self.total_samples} samples\n")
    
    def __len__(self):
        return self.total_samples
    
    def __getitem__(self, idx):
        # Find which dataset this index belongs to
        dataset_idx = np.searchsorted(self.cumulative_sizes, idx, side='right')
        
        # Get the index within that dataset
        if dataset_idx == 0:
            sample_idx = idx
        else:
            sample_idx = idx - self.cumulative_sizes[dataset_idx - 1]
        
        return self.datasets[dataset_idx][sample_idx]
