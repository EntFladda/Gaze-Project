# Gaze Focus On-Device Web

Implementasi focus detection real-time untuk web pembelajaran/gamifikasi. Project ini dibuat untuk skenario skripsi: kamera diproses di perangkat pengguna, model dimuat sebagai aset web, dan sistem tidak perlu mengirim frame wajah ke server/cloud.

## Tujuan

- Mendeteksi fokus pengguna dari webcam secara real-time.
- Menggunakan estimasi head pose/gaze berbasis `pitch`, `yaw`, dan `roll`.
- Menjalankan inference secara lokal/on-device.
- Menyediakan model ONNX yang dapat dicache oleh browser.
- Menyimpan log fokus secara lokal untuk analisis sesi pembelajaran.

## Struktur Utama

```text
.
+-- checkpoints/
|   +-- final/head_pose_best.pt
+-- data/
+-- scripts/
|   +-- export_to_onnx.py
+-- src/
|   +-- dataset/
|   +-- training/
|   +-- inference/
|   +-- focus_tracker.py
+-- tests/
+-- web/
|   +-- index.html
|   +-- sw.js
|   +-- public/models/head_pose_model.onnx
|   +-- src/
+-- run_complete_pipeline.py
+-- train_unified.py
+-- requirements.txt
```

## Dataset yang Digunakan

Proyek ini menggunakan dua dataset utama sesuai dengan proposal penelitian:

1. **MPIIGaze** (`rakibashar/mpiigaze-dataset`): Dataset utama untuk *gaze tracking* dengan fokus pada ketahanan terhadap variasi pencahayaan (kondisi *low*, *normal*, dan *high*).
2. **300W-LP**: Dataset yang berfokus pada *face alignment* dan estimasi *Head Pose* 3D (*pitch*, *yaw*, *roll*) untuk melacak pergerakan arah kepala pengguna.

Untuk mengunduh dataset secara otomatis ke folder `data/`, jalankan:

```bash
python scripts/download_kaggle_datasets.py --all
```
*(Catatan: Membutuhkan package `kaggle` dan token konfigurasi `~/.kaggle/kaggle.json`)*

## Alur Project

```text
Dataset
  -> training dengan train_unified.py
  -> checkpoint PyTorch: checkpoints/final/head_pose_best.pt
  -> export ONNX: scripts/export_to_onnx.py
  -> model web: web/public/models/head_pose_model.onnx
  -> inference browser/on-device: web/index.html
```

## Instalasi

```bash
pip install -r requirements.txt
```

Jika memakai web app, browser modern seperti Chrome/Edge/Firefox direkomendasikan.

## Uji Lokal dengan Webcam

Gunakan ini untuk memastikan model PyTorch dan kamera berjalan:

```bash
python run_complete_pipeline.py --skip-train --webcam
```

Mode ini memakai model dari:

```text
checkpoints/final/head_pose_best.pt
```

## Export Model ke ONNX

Jalankan:

```bash
python scripts/export_to_onnx.py
```

Output default:

```text
web/public/models/head_pose_model.onnx
```

Model ONNX ini adalah model yang dipakai oleh web untuk skenario on-device.

## Menjalankan Web On-Device

```bash
cd web
python -m http.server 8000
```

Buka:

```text
http://localhost:8000
```

Saat pertama kali dibuka, browser akan memuat model ONNX dari `public/models/head_pose_model.onnx`. Service worker di `web/sw.js` menangani cache aset dan model agar load berikutnya lebih cepat.

## Komponen Web

- `web/index.html`: halaman utama aplikasi.
- `web/src/cache/cacheModel.js`: cache dan load model ONNX.
- `web/src/ai/loadModel.js`: inisialisasi ONNX Runtime Web.
- `web/src/camera/webcam.js`: akses kamera dan konversi frame.
- `web/src/gaze/focusLogic.js`: klasifikasi fokus/tidak fokus.
- `web/src/cache/indexedDB.js`: penyimpanan log fokus lokal.
- `web/sw.js`: service worker untuk cache aplikasi dan model.

## Catatan Sesuai Proposal

- Pemrosesan frame dilakukan di perangkat pengguna.
- Server hanya diperlukan untuk menyajikan file statis saat development.
- Frame wajah tidak perlu dikirim ke backend/cloud.
- Model dicache di browser setelah load awal.
- Log fokus dapat disimpan secara lokal sebagai data sesi.

## File Penting

| File | Fungsi |
| --- | --- |
| `train_unified.py` | Training model utama |
| `run_complete_pipeline.py` | Uji lokal webcam dan pipeline deteksi |
| `scripts/export_to_onnx.py` | Export checkpoint PyTorch ke ONNX |
| `src/training/lightning_trainer.py` | Arsitektur model head pose |
| `src/focus_tracker.py` | Logika tracking fokus |
| `web/public/models/head_pose_model.onnx` | Model untuk browser |

## Troubleshooting

### Kamera tidak terbuka

- Pastikan browser memberi izin kamera.
- Jalankan dari `localhost`, bukan langsung membuka file HTML.
- Pastikan kamera tidak sedang dipakai aplikasi lain.

### Model web tidak load

- Pastikan file ini ada:

```text
web/public/models/head_pose_model.onnx
```

- Jika belum ada, jalankan:

```bash
python scripts/export_to_onnx.py
```

### Hasil cache lama masih muncul

Clear cache browser atau buka DevTools lalu unregister service worker untuk halaman `localhost:8000`.

## Ringkas Perintah

```bash
pip install -r requirements.txt
python run_complete_pipeline.py --skip-train --webcam
python scripts/export_to_onnx.py
cd web
python -m http.server 8000
```
