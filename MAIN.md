# Gaze Focus Project

Project ini disiapkan untuk skripsi: implementasi focus detection real-time yang berjalan di perangkat pengguna dan dapat dipakai pada web pembelajaran.

## Jalur Utama

```text
Dataset
  -> train_unified.py
  -> checkpoints/final/head_pose_best.pt
  -> scripts/export_to_onnx.py
  -> web/public/models/head_pose_model.onnx
  -> web/index.html
```

## File Penting

- `train_unified.py` untuk training model.
- `run_complete_pipeline.py` untuk uji lokal dengan webcam.
- `scripts/export_to_onnx.py` untuk export checkpoint PyTorch ke ONNX.
- `web/` untuk implementasi on-device browser.
- `web/public/models/head_pose_model.onnx` untuk model web yang dicache browser.
- `src/training/lightning_trainer.py` untuk arsitektur model utama.
- `src/focus_tracker.py` untuk logika pelacakan fokus.

## Menjalankan Uji Lokal

```bash
python run_complete_pipeline.py --skip-train --webcam
```

## Menjalankan Web On-Device

```bash
cd web
python -m http.server 8000
```

Buka `http://localhost:8000`.

## Catatan Proposal

- Kamera diproses lokal di browser/perangkat pengguna.
- Model ONNX disajikan sebagai aset statis dan dicache oleh service worker.
- Log fokus dapat disimpan lokal via IndexedDB.
- Tidak perlu endpoint backend untuk mengirim frame wajah ke server.
