from __future__ import annotations

import json
from pathlib import Path

import fitz


PDFS = [
    ("siaga2016", "tmp/bebras_docs/Bebras-Challenge-2016_Siaga.pdf"),
    ("penggalang2016", "tmp/bebras_docs/Bebras-Challenge-2016_Penggalang.pdf"),
    ("penegak2016", "tmp/bebras_docs/Bebras-Challenge-2016_Penegak.pdf"),
    ("siaga2017", "tmp/bebras_docs/BukuBebras2017_SD.pdf"),
    ("penggalang2017", "tmp/bebras_docs/BukuBebras2017_SMP.pdf"),
    ("penegak2017", "tmp/bebras_docs/BukuBebras2017_SMA.pdf"),
    ("siaga2018", "tmp/bebras_docs/BukuBebras2018%20SD%20v.5%20rev-1.pdf"),
    ("penggalang2018", "tmp/bebras_docs/BukuBebras2018%20SMP%20v.5.pdf"),
    ("penegak2018", "tmp/bebras_docs/BukuBebras2018%20SMA%20v.5.pdf"),
    ("siaga2019", "tmp/bebras_docs/Bebras-Indonesia-Book-2019-SD-v.Okt_.2024.pdf"),
    ("penggalang2019", "tmp/bebras_docs/Bebras-Indonesia-Book-2019-SMP-v.Okt_.2024.pdf"),
    ("penegak2019", "tmp/bebras_docs/Bebras-Indonesia-Book-2019-SMA-v.Okt_.2024.pdf"),
    ("sikecil2020", "tmp/bebras_docs/Bebras-Indonesia-Book-2020-SiKecil-OK-Okt2024.pdf"),
    ("siaga2020", "tmp/bebras_docs/Bebras-Indonesia-Book-2020-SD-OK-Okt2024.pdf"),
    ("penggalang2020", "tmp/bebras_docs/Bebras-Indonesia-Book-2020-SMP-OK-Okt2024.pdf"),
    ("penegak2020", "tmp/bebras_docs/Bebras-Indonesia-Book-2020-SMA-OK-Okt2024.pdf"),
]


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    out_root = root / "tmp" / "bebras_extract"
    out_root.mkdir(parents=True, exist_ok=True)

    manifest: dict[str, list[dict[str, object]]] = {}

    for key, rel_path in PDFS:
        pdf_path = root / rel_path
        doc = fitz.open(pdf_path)
        pdf_out = out_root / key
        pdf_out.mkdir(parents=True, exist_ok=True)
        pages: list[dict[str, object]] = []

        for page_index in range(len(doc)):
            page = doc.load_page(page_index)
            text = page.get_text("text")
            text_path = pdf_out / f"page_{page_index + 1:03d}.txt"
            text_path.write_text(text, encoding="utf-8")
            preview_path = pdf_out / f"page_{page_index + 1:03d}_full.png"
            pix = page.get_pixmap(matrix=fitz.Matrix(1.7, 1.7), alpha=False)
            pix.save(preview_path)

            image_paths: list[str] = []
            for image_index, image_info in enumerate(page.get_images(full=True), start=1):
                xref = image_info[0]
                base_image = doc.extract_image(xref)
                ext = base_image.get("ext", "png")
                image_bytes = base_image["image"]
                image_path = pdf_out / f"page_{page_index + 1:03d}_img_{image_index:02d}.{ext}"
                image_path.write_bytes(image_bytes)
                image_paths.append(str(image_path.relative_to(root)).replace("\\", "/"))

            pages.append(
                {
                    "page": page_index + 1,
                    "text_file": str(text_path.relative_to(root)).replace("\\", "/"),
                    "preview_image": str(preview_path.relative_to(root)).replace("\\", "/"),
                    "images": image_paths,
                }
            )

        manifest[key] = pages

    manifest_path = out_root / "manifest.json"
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2), encoding="utf-8")
    print(manifest_path)


if __name__ == "__main__":
    main()
