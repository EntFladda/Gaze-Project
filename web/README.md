# Web On-Device Focus Detection

Web app ini adalah target implementasi sesuai proposal: kamera berjalan di browser, model disediakan sebagai aset statis, dan hasil fokus disimpan sebagai log lokal.

## Struktur

```text
web/
+-- index.html
+-- sw.js
+-- public/models/head_pose_model.onnx
+-- src/
    +-- ai/
    +-- cache/
    +-- camera/
    +-- gaze/
    +-- ui/
```

## Menjalankan

```bash
cd web
python -m http.server 8000
```

Buka `http://localhost:8000`.

## Catatan Implementasi

- `public/models/head_pose_model.onnx` adalah model web utama.
- `sw.js` menangani cache aset dan model agar dapat dimuat ulang lebih cepat.
- `src/cache/indexedDB.js` menyimpan log fokus secara lokal.
- Tidak ada frame wajah yang perlu dikirim ke cloud untuk skenario proposal.
