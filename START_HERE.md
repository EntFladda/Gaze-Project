# Start Here

## 1. Install Dependencies

```bash
pip install -r requirements.txt
```

## 2. Test Model Lokal

```bash
python run_complete_pipeline.py --skip-train --webcam
```

## 3. Export Model ke ONNX

```bash
python scripts/export_to_onnx.py
```

Pastikan file ini ada:

```text
web/public/models/head_pose_model.onnx
```

## 4. Jalankan Web On-Device

```bash
cd web
python -m http.server 8000
```

Buka:

```text
http://localhost:8000
```

## Fokus Struktur

- Training: `train_unified.py`
- Uji lokal: `run_complete_pipeline.py`
- Web proposal: `web/`
- Model final: `checkpoints/final/head_pose_best.pt`
- Model web: `web/public/models/head_pose_model.onnx`
