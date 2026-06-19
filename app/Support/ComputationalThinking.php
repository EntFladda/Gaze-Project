<?php

namespace App\Support;

class ComputationalThinking
{
    public static function infer(?string $text): array
    {
        $haystack = self::normalize((string) $text);

        $concepts = [
            'pattern' => [
                'name' => 'Pengenalan Pola',
                'short' => 'Pola',
                'description' => 'Mencari kemiripan, urutan, atau pola berulang untuk menebak langkah berikutnya.',
                'keywords' => ['pola', 'urutan', 'susunan', 'pattern', 'sequence', 'seri', 'berulang'],
                'steps' => [
                    'Cari bagian yang berulang atau berubah teratur.',
                    'Bandingkan contoh satu dengan contoh berikutnya.',
                    'Gunakan pola itu untuk menentukan jawaban.',
                ],
            ],
            'algorithm' => [
                'name' => 'Berpikir Algoritmik',
                'short' => 'Algoritma',
                'description' => 'Menyusun aturan dan langkah berurutan agar masalah bisa diselesaikan dengan konsisten.',
                'keywords' => ['algoritma', 'instruksi', 'langkah', 'flow', 'route', 'rute', 'maze', 'robot', 'proses', 'step', 'jalur'],
                'steps' => [
                    'Baca aturan gerak atau instruksi dengan teliti.',
                    'Jalankan langkahnya satu per satu tanpa melompat.',
                    'Cek hasil akhir setelah semua aturan diterapkan.',
                ],
            ],
            'logic' => [
                'name' => 'Logika dan Kondisi',
                'short' => 'Logika',
                'description' => 'Memakai aturan benar/salah, sebab-akibat, dan kondisi untuk memilih jawaban yang tepat.',
                'keywords' => ['logika', 'benar', 'salah', 'kondisi', 'aturan', 'jika', 'maka', 'filter', 'penyaringan'],
                'steps' => [
                    'Pisahkan informasi yang benar-benar memenuhi aturan.',
                    'Uji setiap pilihan dengan kondisi pada soal.',
                    'Buang pilihan yang melanggar aturan.',
                ],
            ],
            'data' => [
                'name' => 'Representasi Data',
                'short' => 'Data',
                'description' => 'Membaca simbol, kode, tabel, atau data visual lalu menerjemahkannya menjadi informasi.',
                'keywords' => ['data', 'simbol', 'kode', 'encoding', 'decoding', 'sinyal', 'tabel', 'huruf', 'angka', 'digit'],
                'steps' => [
                    'Kenali arti setiap simbol atau kode.',
                    'Terjemahkan data sesuai aturan yang diberikan.',
                    'Bandingkan hasil terjemahan dengan pilihan jawaban.',
                ],
            ],
            'abstraction' => [
                'name' => 'Abstraksi dan Dekomposisi',
                'short' => 'Abstraksi',
                'description' => 'Memilih informasi penting, mengabaikan detail yang tidak perlu, lalu memecah masalah menjadi bagian kecil.',
                'keywords' => ['abstraksi', 'komponen', 'component', 'sistem', 'system', 'model', 'map', 'struktur', 'bagian'],
                'steps' => [
                    'Tentukan informasi yang paling penting.',
                    'Pecah masalah menjadi beberapa bagian kecil.',
                    'Gabungkan hasil tiap bagian untuk mengambil keputusan.',
                ],
            ],
        ];

        foreach ($concepts as $key => $concept) {
            foreach ($concept['keywords'] as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    $concept['key'] = $key;

                    return $concept;
                }
            }
        }

        return [
            'key' => 'problem_solving',
            'name' => 'Pemecahan Masalah CT',
            'short' => 'CT',
            'description' => 'Menggabungkan pola, logika, data, dan langkah sistematis untuk menyelesaikan soal.',
            'steps' => [
                'Baca konteks dan pertanyaan sampai jelas.',
                'Cari aturan atau informasi yang paling menentukan.',
                'Uji pilihan jawaban dengan alasan yang runtut.',
            ],
        ];
    }

    public static function feedback(array $concept, bool $isCorrect, bool $isLastQuestion = false): array
    {
        $conceptName = $concept['name'] ?? 'Computational Thinking';
        $steps = $concept['steps'] ?? [];

        return [
            'concept_name' => $conceptName,
            'concept_short' => $concept['short'] ?? 'CT',
            'concept_description' => $concept['description'] ?? '',
            'title' => $isCorrect ? 'Benar, bagus!' : 'Belum tepat.',
            'message' => $isCorrect
                ? ($isLastQuestion
                    ? "Kamu sudah memakai {$conceptName} dan menyelesaikan soal terakhir."
                    : "Kamu sudah memakai {$conceptName}. Pertahankan cara berpikir seperti ini untuk soal berikutnya.")
                : "Coba cek lagi dengan konsep {$conceptName}. Fokus ke aturan penting, lalu uji jawabanmu pelan-pelan.",
            'next_step' => $isCorrect
                ? ($isLastQuestion ? 'Lihat hasil dan review pembahasannya.' : 'Lanjutkan ke soal berikutnya.')
                : 'Gunakan petunjuk bila tersedia, lalu coba jawab ulang.',
            'thinking_steps' => array_values($steps),
        ];
    }

    protected static function normalize(string $text): string
    {
        return strtolower(strip_tags($text));
    }
}
