from __future__ import annotations

import json
import re
from pathlib import Path


PDF_KEYS = [
    "siaga2016",
    "penggalang2016",
    "penegak2016",
    "siaga2017",
    "penggalang2017",
    "penegak2017",
    "siaga2018",
    "penggalang2018",
    "penegak2018",
    "siaga2019",
    "penggalang2019",
    "penegak2019",
    "sikecil2020",
    "siaga2020",
    "penggalang2020",
    "penegak2020",
]
CATEGORIES = {
    "siaga2016": "Siaga",
    "penggalang2016": "Penggalang",
    "penegak2016": "Penegak",
    "siaga2017": "Siaga",
    "penggalang2017": "Penggalang",
    "penegak2017": "Penegak",
    "siaga2018": "Siaga",
    "penggalang2018": "Penggalang",
    "penegak2018": "Penegak",
    "siaga2019": "Siaga",
    "penggalang2019": "Penggalang",
    "penegak2019": "Penegak",
    "sikecil2020": "SiKecil",
    "siaga2020": "Siaga",
    "penggalang2020": "Penggalang",
    "penegak2020": "Penegak",
}


def normalize(text: str) -> str:
    replacements = {
        "â€“": "-",
        "â€”": "-",
        "â€œ": '"',
        "â€": '"',
        "â€˜": "'",
        "â€™": "'",
        "â€¢": "-",
        "â—": "-",
        "Â©": "©",
    }
    for old, new in replacements.items():
        text = text.replace(old, new)
    return text


def is_stop_line(line: str) -> bool:
    return (
        line.startswith("Kata Penting")
        or line.startswith("Kata kunci")
        or line.startswith("Kata Kunci")
        or line.startswith("Authorship")
        or line.startswith("License")
        or line.startswith("Lisensi")
    )


def collect_until(lines: list[str], start: int, stop_prefixes: tuple[str, ...]) -> list[str]:
    collected: list[str] = []

    for line in lines[start:]:
        if line.startswith(stop_prefixes) or is_stop_line(line):
            break

        collected.append(line)

    return collected


def split_answer_and_explanation(lines: list[str]) -> tuple[str, str]:
    answer = ""
    explanation = ""

    answer_index = next(
        (
            i
            for i, line in enumerate(lines)
            if line.startswith("Jawaban:")
            or line.startswith("Jawaban yang tepat")
            or line.startswith("Jawaban yang benar")
            or line == "Jawaban"
        ),
        None,
    )

    if answer_index is not None:
        answer_lines = collect_until(
            lines,
            answer_index + 1,
            ("Penjelasan:", "Penjelasan", "Ini Informatika!", "Ini Informatika"),
        )
        inline_answer = re.sub(r"^Jawaban(?: yang tepat| yang benar)?\s*:?\s*", "", lines[answer_index]).strip()
        answer = " ".join(([inline_answer] if inline_answer else []) + answer_lines).strip()

    explanation_index = next(
        (
            i
            for i, line in enumerate(lines)
            if line.startswith("Penjelasan:") or line == "Penjelasan"
        ),
        None,
    )
    if explanation_index is not None:
        explanation_lines = collect_until(
            lines,
            explanation_index + 1,
            ("Ini Informatika!", "Ini Informatika",),
        )
        explanation = " ".join(explanation_lines).strip()

    if not explanation:
        informatics_index = next(
            (
                i
                for i, line in enumerate(lines)
                if line.startswith("Ini Informatika!") or line == "Ini Informatika"
            ),
            None,
        )
        if informatics_index is not None:
            explanation_lines = collect_until(lines, informatics_index + 1, ())
            explanation = " ".join(explanation_lines).strip()

    return answer, explanation


def extract_task(page_text: str) -> dict[str, str] | None:
    text = normalize(page_text)
    lines = [line.strip() for line in text.splitlines() if line.strip()]

    challenge_index = next((i for i, line in enumerate(lines) if line.startswith("Tantangan:")), None)
    if challenge_index is None:
        return None

    title = None
    level = None
    code = None

    for i, line in enumerate(lines[:challenge_index]):
        if re.fullmatch(r"(SIKECIL|SIAGA|PENGGALANG|PENEGAK)(\s*\(.*\))?", line, re.IGNORECASE):
            level = line
            if i > 0:
                title = lines[i - 1]
            if i + 1 < len(lines):
                code = lines[i + 1]
            break

    if not title:
        return None

    question_block: list[str] = []
    for line in lines[challenge_index + 1 :]:
        if (
            line.startswith("Pilihan Jawaban:")
            or line.startswith("Jawaban:")
            or line.startswith("Jawaban yang tepat")
            or line.startswith("Jawaban yang benar")
        ):
            break
        question_block.append(line)

    answer, explanation = split_answer_and_explanation(lines)

    description_lines = []
    for line in lines[: max(0, challenge_index - 2)]:
        if line == title or line == level or line == code:
            continue
        if line.startswith("Tantangan Bebras Indonesia"):
            continue
        if re.fullmatch(r"[ivxlcdm]+|\d+", line, re.IGNORECASE):
            continue
        description_lines.append(line)

    return {
        "title": title,
        "level": level or "",
        "code": code or "",
        "description": " ".join(description_lines).strip(),
        "question": " ".join(question_block).strip(),
        "answer": answer,
        "explanation": explanation,
    }


def is_2016_task_start(lines: list[str]) -> bool:
    return "Nomor dan Judul Soal" in lines and "Deskripsi Soal" in lines


def extract_2016_task(book_root: Path, start_page: int, end_page: int) -> dict[str, object] | None:
    chunk_lines: list[str] = []
    for page_no in range(start_page, end_page + 1):
        text_path = book_root / f"page_{page_no:03d}.txt"
        if text_path.exists():
            chunk_lines.extend(
                line.strip()
                for line in normalize(text_path.read_text(encoding="utf-8")).splitlines()
                if line.strip()
            )

    if not chunk_lines or not is_2016_task_start(chunk_lines):
        return None

    header_index = chunk_lines.index("Nomor dan Judul Soal")
    code_header_index = next(
        (i for i in range(header_index + 1, min(header_index + 8, len(chunk_lines))) if chunk_lines[i] == "Kode Soal"),
        None,
    )
    if code_header_index is None:
        return None

    after_code_header = code_header_index + 1
    title_lines: list[str] = []
    code = ""

    for line in chunk_lines[after_code_header : after_code_header + 8]:
        if re.match(r"^(I-)?20\d{2}[-A-Z0-9]+", line):
            code = line.replace(";", "").strip()
            break
        title_lines.append(line)

    if not title_lines:
        return None

    title = " ".join(title_lines)
    title = re.sub(r"^\d+\.\s*", "", title).strip()
    title = re.sub(r"\s+", " ", title)

    description_index = next((i for i, line in enumerate(chunk_lines) if line == "Deskripsi Soal"), None)
    question_index = next((i for i, line in enumerate(chunk_lines) if line == "Pertanyaan"), None)
    answer_index = next((i for i, line in enumerate(chunk_lines) if line == "Jawaban"), None)

    if description_index is None or question_index is None:
        return None

    description = " ".join(chunk_lines[description_index + 1 : question_index]).strip()
    question_end = answer_index if answer_index is not None else len(chunk_lines)
    question = " ".join(chunk_lines[question_index + 1 : question_end]).strip()
    answer, explanation = split_answer_and_explanation(chunk_lines)

    return {
        "title": title,
        "level": "PENEGAK (SMA)",
        "code": code,
        "description": description,
        "question": question,
        "answer": answer,
        "explanation": explanation,
        "page": start_page,
        "category": "Penegak",
    }


def extract_2016_tasks(book_root: Path, manifest: dict[str, list[dict[str, object]]], key: str) -> list[dict[str, object]]:
    starts: list[int] = []

    for text_path in sorted(book_root.glob("page_*.txt")):
        page_no = int(text_path.stem.split("_")[1])
        lines = [
            line.strip()
            for line in normalize(text_path.read_text(encoding="utf-8")).splitlines()
            if line.strip()
        ]
        if is_2016_task_start(lines):
            starts.append(page_no)

    tasks: list[dict[str, object]] = []
    pages = manifest.get(key, [])

    for index, start_page in enumerate(starts):
        end_page = (starts[index + 1] - 1) if index + 1 < len(starts) else max(
            int(page.get("page", 0)) for page in pages
        )
        task = extract_2016_task(book_root, start_page, end_page)
        if not task:
            continue

        root = book_root.parents[2]
        image_paths: list[str] = []
        for page_no in range(start_page, end_page + 1):
            image_paths.extend(
                sorted(str(path.relative_to(root)).replace("\\", "/") for path in book_root.glob(f"page_{page_no:03d}_img_*"))
            )

        preview_image = next(
            (
                page.get("preview_image")
                for page in pages
                if int(page.get("page", 0)) == start_page
            ),
            None,
        )
        task["preview_image"] = preview_image
        task["images"] = image_paths
        tasks.append(task)

    return tasks


def main() -> None:
    root = Path(__file__).resolve().parents[1]
    extract_root = root / "tmp" / "bebras_extract"
    manifest = json.loads((extract_root / "manifest.json").read_text(encoding="utf-8"))
    catalog: dict[str, list[dict[str, object]]] = {}

    for key in PDF_KEYS:
        book_root = extract_root / key
        if key.endswith("2016"):
            catalog[key] = extract_2016_tasks(book_root, manifest, key)
            continue

        tasks: list[dict[str, object]] = []
        for text_path in sorted(book_root.glob("page_*.txt")):
            page_no = int(text_path.stem.split("_")[1])
            task = extract_task(text_path.read_text(encoding="utf-8"))
            if not task:
                continue

            image_paths = sorted(str(path.relative_to(root)).replace("\\", "/") for path in book_root.glob(f"page_{page_no:03d}_img_*"))
            preview_image = next(
                (
                    page.get("preview_image")
                    for page in manifest.get(key, [])
                    if int(page.get("page", 0)) == page_no
                ),
                None,
            )
            task["page"] = page_no
            task["category"] = CATEGORIES[key]
            task["preview_image"] = preview_image
            task["images"] = image_paths
            tasks.append(task)

        catalog[key] = tasks

    out_path = extract_root / "task_catalog.json"
    out_path.write_text(json.dumps(catalog, ensure_ascii=False, indent=2), encoding="utf-8")
    print(out_path)


if __name__ == "__main__":
    main()
