<?php

$basePath = static function (string $path = ''): string {
    $root = function_exists('base_path') ? base_path() : dirname(__DIR__, 3);

    if ($path === '') {
        return $root;
    }

    return $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
};

$catalogPath = $basePath('tmp/bebras_extract/task_catalog.json');
if (! file_exists($catalogPath)) {
    $catalogPath = $basePath('database/seeders/data/bebras_task_catalog.json');
}

$catalog = json_decode(file_get_contents($catalogPath), true, 512, JSON_THROW_ON_ERROR);

$compositeManifestPath = $basePath('tmp/bebras_extract/composite_manifest.json');
if (! file_exists($compositeManifestPath)) {
    $compositeManifestPath = $basePath('database/seeders/data/bebras_composite_manifest.json');
}

$compositeManifest = file_exists($compositeManifestPath)
    ? json_decode(file_get_contents($compositeManifestPath), true, 512, JSON_THROW_ON_ERROR)
    : [];
$sourceMap = require $basePath('database/seeders/data/bebras_official_source_map.php');

$sectionOrder = [
    'Logic Playground' => 1,
    'Pattern Lab' => 2,
    'Algorithm Flow' => 3,
    'Data Quest' => 4,
    'System Mapping' => 5,
    'Decision Engine' => 6,
    'Innovation Sprint' => 7,
];

$sectionChips = [
    'Logic Playground' => ['pola', 'instruksi', 'logika', 'dasar'],
    'Pattern Lab' => ['pola', 'simbol', 'rotasi', 'filter'],
    'Algorithm Flow' => ['alur', 'langkah', 'rute', 'simulasi'],
    'Data Quest' => ['kode', 'data', 'urutan', 'sinyal'],
    'System Mapping' => ['graf', 'jaringan', 'komponen', 'cakupan'],
    'Decision Engine' => ['aturan', 'optimasi', 'pilihan', 'strategi'],
    'Innovation Sprint' => ['gabungan', 'desain', 'uji coba', 'solusi'],
];

$missionDescriptions = [
    'Mission 1 - Pola dan Urutan' => 'Mengenali pola visual, substitusi, dan urutan transformasi.',
    'Mission 2 - Instruksi dan Algoritma' => 'Menjalankan instruksi secara runtut sampai kondisi akhir tercapai.',
    'Mission 3 - Logika dan Informasi' => 'Memakai fakta, kondisi, dan informasi relevan untuk menyaring jawaban.',
    'Mission 1 - Jejak dan Rute' => 'Membaca pola posisi, rotasi, dan jejak pergerakan dari gambar.',
    'Mission 2 - Data dan Simbol' => 'Menerjemahkan simbol, biner, lampu, dan pencacah menjadi informasi.',
    'Mission 3 - Penyaringan Logika' => 'Mencocokkan aturan dengan keluaran untuk membuang pilihan yang salah.',
    'Mission 1 - Flow Control' => 'Mengikuti alur kontrol, automata, dan langkah mundur atau maju.',
    'Mission 2 - Step Builder' => 'Menyusun proses kerja, antrian, dan jadwal langkah demi langkah.',
    'Mission 3 - Logic Route' => 'Memilih rute dengan batasan biaya, waktu, atau kunjungan.',
    'Mission 1 - Data Signals' => 'Membaca kode visual, pemindaian, dan representasi data.',
    'Mission 2 - Data Sorting' => 'Mengurutkan string, kejadian, atau rangkaian data berdasarkan aturan.',
    'Mission 3 - Insight Finder' => 'Menemukan informasi penting dari susunan, pergerakan, dan kombinasi.',
    'Mission 1 - Map the Process' => 'Memetakan proses pada graf, simpul, dan relasi antarbagian.',
    'Mission 2 - Component Link' => 'Melihat keterhubungan komponen dan dampaknya terhadap sistem.',
    'Mission 3 - System View' => 'Mengevaluasi sistem besar melalui cakupan, kapasitas, dan ketahanan.',
    'Mission 1 - Rule Matcher' => 'Mencocokkan pilihan dengan batasan dan aturan yang berlaku.',
    'Mission 2 - Option Filter' => 'Menyaring opsi dengan strategi pencarian dan optimasi.',
    'Mission 3 - Smart Choice' => 'Memilih keputusan terbaik setelah mensimulasikan konsekuensi.',
    'Mission 1 - Idea Trigger' => 'Menggabungkan pola, kombinasi, dan optimasi sebagai pemantik solusi.',
    'Mission 2 - Prototype Path' => 'Menguji rancangan proses lewat simulasi dan penjadwalan.',
    'Mission 3 - Final Sprint' => 'Menyelesaikan persoalan besar dengan strategi gabungan lintas konsep.',
];

$scoreBands = [
    1 => [[10, 8], [10, 8], [10, 8], [12, 9], [8, 6]],
    2 => [[11, 9], [11, 9], [11, 9], [13, 10], [9, 7]],
    3 => [[12, 10], [12, 10], [12, 10], [14, 11], [10, 8]],
    4 => [[13, 11], [13, 11], [13, 11], [15, 12], [11, 9]],
    5 => [[14, 12], [14, 12], [14, 12], [16, 13], [12, 10]],
    6 => [[15, 13], [15, 13], [15, 13], [17, 14], [13, 11]],
    7 => [[16, 14], [16, 14], [16, 14], [18, 15], [14, 12]],
];

$typePattern = ['multiple_choice', 'multiple_choice', 'multiple_choice', 'essay', 'true_false'];

$findTask = static function (string $book, string $title) use ($catalog): array {
    foreach ($catalog[$book] ?? [] as $task) {
        if (($task['title'] ?? null) === $title) {
            return $task;
        }
    }

    throw new RuntimeException("Task not found for {$book} / {$title}");
};

$plainLength = static function (string $text): int {
    return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
};

$plainSubstr = static function (string $text, int $start, int $length) {
    return function_exists('mb_substr') ? mb_substr($text, $start, $length) : substr($text, $start, $length);
};

$clean = static function (?string $text): string {
    $text = (string) $text;
    $replacements = [
        'Ã¢â‚¬â€œ' => '-',
        'Ã¢â‚¬â€' => '-',
        'Ã¢â‚¬Å“' => '"',
        'Ã¢â‚¬Â' => '"',
        'Ã¢â‚¬Ëœ' => "'",
        'Ã¢â‚¬â„¢' => "'",
        'Ã¢â‚¬Â¢' => '-',
        'Ã¯â€šÂ·' => '-',
        'Ã¯ÂÂ' => '-',
        'Ã‚Â©' => '(c)',
        'â€“' => '-',
        'â€”' => '-',
        '“' => '"',
        '”' => '"',
        '‘' => "'",
        '’' => "'",
    ];

    $text = str_replace(array_keys($replacements), array_values($replacements), $text);
    $text = str_replace(["ï‚·", "ï"], "\n- ", $text);
    $text = preg_replace('/\s*[\x{F04F}\x{F0A7}\x{F0B7}\x{F0FC}\x{2022}\x{25A1}\x{2610}]\s*/u', "\n- ", $text) ?? $text;
    $text = preg_replace('/Tantangan\s+Bebras\s+Indonesia\s+\d{4}.*?(?:Halaman\s*\d+\s*(?:dari\s*\d+)?)?/iu', ' ', $text) ?? $text;
    $text = preg_replace('/Bebras\s+Indonesia\s*[-–—â€“]*\s*Kelompok\s+(?:Siaga|Penggalang|Penegak).*?(?:Halaman\s*\d+\s*(?:dari\s*\d+)?)?/iu', ' ', $text) ?? $text;
    $text = preg_replace('/Bebras\s+Indonesia.*?(?:Halaman\s*\d+\s*(?:dari\s*\d+)?)?/iu', ' ', $text) ?? $text;
    $text = preg_replace('/Kelompok\s+(?:Siaga|Penggalang|Penegak)\s+Halaman\s*\d+\s*(?:dari\s*\d+)?/iu', ' ', $text) ?? $text;
    $text = preg_replace('/Halaman\s+\d+\s+dari\s+\d+/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bPENEGAK\s*\(SMA\)\b/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bAspek\s+Informatika\b[:\s]*/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bIni\s+Informatika!?/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bKata\s+kunci\b.*$/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bWebsite\b.*$/iu', ' ', $text) ?? $text;
    $text = preg_replace('/\bBebras\s+si\s+berang-berang\b/iu', 'berang-berang', $text) ?? $text;
    $text = preg_replace('/\bBebras\b/iu', 'berang-berang', $text) ?? $text;
    $text = preg_replace('/https?:\/\/\S+/i', '', $text) ?? $text;
    $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
    $text = preg_replace('/ *\n+ */', "\n", $text) ?? $text;
    $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

    return trim($text);
};

$limitText = static function (string $text, int $limit) use ($plainLength, $plainSubstr): string {
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

    if ($plainLength($text) <= $limit) {
        return $text;
    }

    return rtrim($plainSubstr($text, 0, $limit - 3)) . '...';
};

$normalizeComparable = static function (string $text) use ($clean): string {
    $text = strtolower($clean($text));
    $text = preg_replace('/^[a-d]\s*[\.)-]\s*/i', '', $text) ?? $text;
    $text = preg_replace('/[^a-z0-9]+/i', '', $text) ?? $text;

    return $text;
};

$normalizeAnswerValue = static function (string $raw) use ($clean): array {
    $raw = $clean($raw);
    $raw = trim($raw, " \t\n\r\0\x0B.,:;\"'");
    $raw = preg_replace('/^(?:jawab(?:an)?\s+yang\s+(?:benar|tepat|paling\s+tepat)\s+(?:adalah|yaitu)|jawab(?:an)?\s+(?:benar|tepat)|adalah|yaitu)\s*:?\s*/iu', '', $raw) ?? $raw;
    $raw = trim($raw, " \t\n\r\0\x0B.,:;\"'");

    if (preg_match('/^(?:gelas|pilihan|opsi|sel)\s+([A-D])\b/iu', $raw, $matches)) {
        return [strtoupper($matches[1])];
    }

    if (preg_match('/^\(?([A-D])\)?(?:\.|\)|\s|$)/iu', $raw, $matches)) {
        return [strtoupper($matches[1])];
    }

    if (preg_match('/^(\d{1,2}:\d{2})(?:\.|\s|$)/u', $raw, $matches)) {
        return [$matches[1]];
    }

    if (preg_match('/^(\d{1,5})\s+dan\s+(\d{1,5})(?:\.|\s|$)/u', $raw, $matches)) {
        return [$matches[1], $matches[2]];
    }

    if (preg_match('/^(\d{1,5})(?:\.|\s|$)/u', $raw, $matches)) {
        return [$matches[1]];
    }

    if (preg_match('/^([A-Z0-9-]{2,12})\s+dan\s+([A-Z0-9-]{2,12})(?:\.|\s|$)/iu', $raw, $matches)) {
        return [strtoupper($matches[1]), strtoupper($matches[2])];
    }

    if (preg_match('/^([A-Z0-9-]{2,12})(?:\.|\s|,|$)/u', strtoupper($raw), $matches)) {
        if (! in_array($matches[1], ['JAWABAN', 'PEMBERI', 'PENERIMA', 'SEBUAH', 'KARENA'], true)) {
            return [strtoupper($matches[1])];
        }
    }

    return [];
};

$officialAnswers = static function (array $task) use ($clean, $normalizeAnswerValue): array {
    $texts = [
        $clean($task['answer'] ?? ''),
        $clean($task['question'] ?? ''),
        $clean($task['explanation'] ?? ''),
    ];

    foreach ($texts as $text) {
        if ($text === '') {
            continue;
        }

        if (preg_match('/Ada\s+dua\s+jawaban\s+yang\s+benar\s*:?\s*([A-Z0-9-]{1,12})\s+dan\s+([A-Z0-9-]{1,12})/iu', $text, $matches)) {
            return array_values(array_unique([strtoupper($matches[1]), strtoupper($matches[2])]));
        }

        if (preg_match_all('/XX-\d{2}/iu', $text, $matches) && count($matches[0]) >= 2) {
            return array_values(array_unique(array_map('strtoupper', $matches[0])));
        }

        $patterns = [
            '/Jawab(?:an)?\s+yang\s+(?:benar|tepat|paling\s+tepat)\s+(?:adalah|yaitu)\s*:?\s*(.+?)(?:\.|$)/iu',
            '/Jawab(?:an)?\s+yang\s+(?:benar|tepat|paling\s+tepat)\s*:?\s*(.+?)(?:\.|$)/iu',
            '/Jawab(?:an)?\s+(?:benar|tepat)\s*:?\s*(.+?)(?:\.|$)/iu',
            '/adalah\s*:?\s*([a-d]\)|[A-D]\b|\d{1,5}\b|[A-Z0-9-]{2,12}\b)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $matches)) {
                continue;
            }

            $answers = $normalizeAnswerValue($matches[1]);
            if (! empty($answers)) {
                return array_values(array_unique($answers));
            }
        }

        $direct = $normalizeAnswerValue($text);
        if (! empty($direct)) {
            return array_values(array_unique($direct));
        }
    }

    return [];
};

$stripChoiceTail = static function (string $text) use ($clean): string {
    $text = $clean($text);
    $text = preg_replace('/\s+(?:Pilih(?:lah)?\s+salah\s+satu|Pilih\s+satu|Pilihan\s+jawaban)\s*:?.*$/ius', '', $text) ?? $text;
    $text = preg_replace('/\s+Pilihan\s+jawaban\s*:?.*$/ius', '', $text) ?? $text;
    $text = preg_replace('/\s+Pilih(?:lah)?\s+(?:salah\s+satu|satu\s+jawaban|jawaban).*$/ius', '', $text) ?? $text;
    $text = preg_replace('/\s*\(?[a-d]\)?\s*$/iu', '', $text) ?? $text;
    $text = preg_replace('/\s+([A-D]\s*){2,}$/u', '', $text) ?? $text;
    $text = preg_replace('/\s+:/', ':', $text) ?? $text;

    return trim($text);
};

$extractValueOptions = static function (string $questionText) use ($clean): array {
    $text = $clean($questionText);

    if (! preg_match('/(?:Pilih(?:lah)?\s+salah\s+satu|Pilih\s+satu|Pilihan\s+jawaban)\s*:?\s*(.+)$/ius', $text, $matches)) {
        return [];
    }

    $tail = trim($matches[1]);
    if (preg_match_all('/(?:^|\s)(?:o|O|○|●|□|☐|-)\s*([A-Za-z0-9:-]+)/u', $tail, $valueMatches)) {
        return array_values(array_unique(array_map('trim', $valueMatches[1])));
    }

    if (preg_match_all('/\b([A-Z0-9][A-Z0-9:-]{0,8})\b/u', $tail, $valueMatches)) {
        return array_values(array_unique(array_map('trim', $valueMatches[1])));
    }

    return [];
};

$extractLetterOptions = static function (string $questionText) use ($clean, $stripChoiceTail): array {
    $text = $clean($questionText);
    $options = [];

    if (preg_match_all('/(?:^|\s)([A-Da-d])[\.)]\s+(.+?)(?=\s+[A-Da-d][\.)]\s+|$)/u', $text, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $index => $letterMatch) {
            $letter = strtoupper($letterMatch[0]);
            $label = trim($matches[2][$index][0]);
            $label = preg_replace('/\s+Pilih(?:lah)?\s+(?:salah\s+satu|satu).*$/iu', '', $label) ?? $label;

            if ($label !== '' && ! preg_match('/^[A-D]$/i', $label)) {
                $options[$letter] = $clean($label);
            }
        }

        if (count($options) >= 2) {
            $firstOffset = $matches[0][0][1];
            $prompt = trim(substr($text, 0, $firstOffset));

            return [
                'prompt' => $stripChoiceTail($prompt),
                'options' => $options,
            ];
        }
    }

    return [
        'prompt' => $stripChoiceTail($text),
        'options' => [],
    ];
};

$answerLabel = static function (array $answers, array $letterOptions = []) use ($clean): string {
    $labels = array_map(function (string $answer) use ($letterOptions, $clean): string {
        $answer = strtoupper(trim($answer));

        if (isset($letterOptions[$answer])) {
            return "{$answer}. " . $clean($letterOptions[$answer]);
        }

        return $answer;
    }, array_values(array_unique($answers)));

    return implode(' / ', $labels);
};

$discussion = static function (array $task) use ($clean): string {
    $parts = array_filter([
        $clean($task['answer'] ?? ''),
        $clean($task['explanation'] ?? ''),
    ]);
    $text = implode(' ', $parts);
    $text = preg_replace('/^(?:[a-d]\)\s*){2,}/iu', '', $text) ?? $text;
    $text = preg_replace('/^[A-D]\s+(?=\S)/u', '', $text) ?? $text;
    $text = preg_replace('/Jawab(?:an)?\s+yang\s+(?:benar|tepat|paling\s+tepat)\s+(?:adalah|yaitu)\s*:?\s*[^.]+\.?/iu', '', $text) ?? $text;
    $text = preg_replace('/Jawab(?:an)?\s+(?:benar|tepat)\s*:?\s*[^.]+\.?/iu', '', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    $text = trim($text);

    return $text !== ''
        ? $text
        : 'Jawaban diperoleh dengan menelusuri aturan pada soal secara bertahap, lalu mencocokkan hasil akhir dengan pilihan yang tersedia.';
};

$shiftCode = static function (string $answer): string {
    $chars = str_split($answer);
    if ($chars === []) {
        return $answer . 'X';
    }

    if (ctype_digit($chars[0])) {
        $chars[0] = (string) (((int) $chars[0] + 1) % 10);
    } elseif (ctype_alpha($chars[0])) {
        $chars[0] = $chars[0] === 'Z' ? 'Y' : chr(ord($chars[0]) + 1);
    }

    return implode('', $chars);
};

$buildValueOptions = static function (array $answers) use ($shiftCode): array {
    $answers = array_values(array_unique(array_filter(array_map('strval', $answers))));
    $answer = trim((string) ($answers[0] ?? ''));

    if (preg_match('/^[A-Z]$/', $answer)) {
        return array_map(
            fn (string $option): array => [
                'answer' => $option,
                'is_correct' => in_array($option, $answers, true),
            ],
            ['A', 'B', 'C', 'D']
        );
    }

    if (preg_match('/^\d+$/', $answer) && ! (strlen($answer) > 1 && str_starts_with($answer, '0'))) {
        $value = (int) $answer;
        $options = array_values(array_unique(array_merge(
            $answers,
            [(string) max(0, $value - 1), (string) ($value + 1), (string) ($value + 2)]
        )));

        while (count($options) < 4) {
            $options[] = (string) ($value + count($options) + 1);
        }

        return array_map(
            fn (string $option): array => ['answer' => $option, 'is_correct' => in_array($option, $answers, true)],
            array_slice($options, 0, 4)
        );
    }

    if (preg_match('/^\d{1,2}:\d{2}$/', $answer)) {
        [$hour, $minute] = array_map('intval', explode(':', $answer));
        $options = [
            sprintf('%02d:%02d', $hour, $minute),
            sprintf('%02d:%02d', $hour, max(0, $minute - 1)),
            sprintf('%02d:%02d', $hour, min(59, $minute + 1)),
            sprintf('%02d:%02d', $hour, min(59, $minute + 5)),
        ];

        return array_map(
            fn (string $option): array => ['answer' => $option, 'is_correct' => in_array($option, $answers, true)],
            array_values(array_unique($options))
        );
    }

    if (preg_match('/^[A-Z0-9-]{2,24}$/', $answer)) {
        $swapped = strlen($answer) > 1 ? $answer[1] . $answer[0] . substr($answer, 2) : $answer . 'X';
        $options = array_values(array_unique([
            $answer,
            strrev($answer),
            $shiftCode($answer),
            $swapped,
        ]));

        while (count($options) < 4) {
            $options[] = $answer . count($options);
        }

        return array_map(
            fn (string $option): array => ['answer' => $option, 'is_correct' => in_array($option, $answers, true)],
            array_slice($options, 0, 4)
        );
    }

    return [
        ['answer' => $answer ?: 'Jawaban sesuai hasil akhir', 'is_correct' => true],
        ['answer' => 'Pilihan lain pada gambar', 'is_correct' => false],
        ['answer' => 'Hasil sebelum aturan selesai diterapkan', 'is_correct' => false],
        ['answer' => 'Hasil yang tidak memenuhi syarat soal', 'is_correct' => false],
    ];
};

$buildMultipleChoiceOptions = static function (array $answers, array $letterOptions, array $valueOptions = []) use ($buildValueOptions, $normalizeComparable): array {
    if (count($letterOptions) >= 2) {
        $answerKeys = array_map(fn (string $answer): string => strtoupper(trim($answer)), $answers);
        $answerComparable = array_map($normalizeComparable, $answers);
        $options = [];

        foreach ($letterOptions as $letter => $label) {
            $cleanLabel = trim((string) $label);
            $isCorrect = in_array($letter, $answerKeys, true)
                || in_array($normalizeComparable($cleanLabel), $answerComparable, true);

            $options[] = [
                'answer' => "{$letter}. {$cleanLabel}",
                'is_correct' => $isCorrect,
            ];
        }

        $hasCorrect = false;
        foreach ($options as $option) {
            if ($option['is_correct']) {
                $hasCorrect = true;
                break;
            }
        }

        if ($hasCorrect) {
            return $options;
        }
    }

    if (count($valueOptions) >= 2) {
        $answerComparable = array_map($normalizeComparable, $answers);
        $options = [];

        foreach ($valueOptions as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $options[] = [
                'answer' => $value,
                'is_correct' => in_array($normalizeComparable($value), $answerComparable, true),
            ];
        }

        $hasCorrect = false;
        foreach ($options as $option) {
            if ($option['is_correct']) {
                $hasCorrect = true;
                break;
            }
        }

        if ($hasCorrect && count($options) >= 2) {
            return $options;
        }
    }

    return $buildValueOptions($answers);
};

$essayAnswers = static function (array $answers, array $letterOptions) use ($clean): array {
    $aliases = [];

    foreach ($answers as $answer) {
        $answer = strtoupper(trim((string) $answer));
        $aliases[] = $answer;

        if (isset($letterOptions[$answer])) {
            $aliases[] = $clean($letterOptions[$answer]);
            $aliases[] = "{$answer}. " . $clean($letterOptions[$answer]);
        }
    }

    return array_map(
        fn (string $answer): array => ['answer' => $answer, 'is_correct' => true],
        array_values(array_unique(array_filter($aliases)))
    );
};

$imageCandidates = static function (array $task) use ($basePath, $compositeManifest): array {
    $compositeKey = ($task['book'] ?? '') . '::' . ($task['title'] ?? '');
    if (isset($compositeManifest[$compositeKey]) && is_string($compositeManifest[$compositeKey])) {
        return [$compositeManifest[$compositeKey]];
    }

    $images = [];
    $fallbackImages = [];
    foreach (($task['images'] ?? []) as $imagePath) {
        if (! is_string($imagePath) || trim($imagePath) === '') {
            continue;
        }

        $fallbackImages[] = $imagePath;
        $absolutePath = $basePath($imagePath);
        if (! is_file($absolutePath)) {
            continue;
        }

        $size = @getimagesize($absolutePath);
        if ($size === false) {
            continue;
        }

        [$width, $height] = $size;
        $fileSize = filesize($absolutePath) ?: 0;

        if ($width < 45 || $height < 25 || $fileSize < 1000) {
            continue;
        }

        $images[] = $imagePath;
    }

    $images = array_values(array_unique($images));
    if (! empty($images)) {
        return array_slice($images, 0, 16);
    }

    return array_slice(array_values(array_unique($fallbackImages)), 0, 16);
};

$buildTaskQuestion = static function (array $task, int $sectionOrderValue, string $missionTitle, int $index) use (
    $answerLabel,
    $buildMultipleChoiceOptions,
    $clean,
    $discussion,
    $essayAnswers,
    $extractLetterOptions,
    $extractValueOptions,
    $imageCandidates,
    $limitText,
    $officialAnswers,
    $scoreBands,
    $stripChoiceTail,
    $typePattern
): array {
    $answers = $officialAnswers($task);
    if (empty($answers)) {
        throw new RuntimeException('Missing official answer for ' . ($task['book'] ?? 'unknown book') . ' / ' . ($task['title'] ?? 'unknown task'));
    }

    $context = $clean($task['description'] ?? '');
    if ($context === '') {
        $context = 'Amati gambar dan baca aturan soal dengan teliti.';
    }

    $parsedQuestion = $extractLetterOptions($task['question'] ?? '');
    $valueOptions = $extractValueOptions($task['question'] ?? '');
    $prompt = $parsedQuestion['prompt'] ?: 'Tentukan jawaban akhir berdasarkan konteks dan gambar.';
    $letterOptions = $parsedQuestion['options'];
    $type = $typePattern[$index] ?? 'multiple_choice';
    $answerText = $answerLabel($answers, $letterOptions);
    $discussionText = $discussion($task);
    [$score, $exp] = $scoreBands[$sectionOrderValue][$index];

    if ($type === 'multiple_choice') {
        $questionText = $prompt;
        $answersData = $buildMultipleChoiceOptions($answers, $letterOptions, $valueOptions);
    } elseif ($type === 'essay') {
        $questionText = $stripChoiceTail($prompt);
        if (strlen($questionText) < 24) {
            $questionText = 'Tentukan jawaban akhir dari soal pada gambar.';
        }

        $questionText = $questionText !== ''
            ? $questionText . ' Tuliskan jawaban akhirnya secara singkat.'
            : 'Tuliskan jawaban akhir soal ini secara singkat.';
        $answersData = $essayAnswers($answers, $letterOptions);
    } else {
        $shortAnswer = $limitText($answerText, 120);
        $questionText = "Benar atau salah: jawaban akhir soal ini adalah {$shortAnswer}.";
        $answersData = [
            ['answer' => 'True', 'is_correct' => true],
            ['answer' => 'False', 'is_correct' => false],
        ];
    }

    return [
        'source_key' => ($task['book'] ?? 'source') . '-' . ($task['title'] ?? 'task'),
        'source_images' => $imageCandidates($task),
        'type' => $type,
        'description' => $context,
        'question_text' => $questionText,
        'answers' => $answersData,
        'help_text' => "Cermati aturan yang tertulis pada konteks soal.\nCocokkan aturan itu dengan gambar atau data secara berurutan.\nPeriksa lagi apakah hasil akhirmu memenuhi semua syarat.",
        'explanation_text' => "Jawaban: {$answerText}\n\nPenjelasan: {$discussionText}",
        'question_image' => null,
        'score' => $score,
        'exp' => $exp,
    ];
};

$sections = [];

foreach ($sourceMap as $sectionName => $missions) {
    $order = $sectionOrder[$sectionName];
    $sectionData = [
        'name' => $sectionName,
        'order' => $order,
        'missions' => [],
    ];

    foreach ($missions as $missionTitle => $sources) {
        $questions = [];

        foreach (array_values($sources) as $index => $source) {
            $task = $findTask($source['book'], $source['title']);
            $task['book'] = $source['book'];
            $questions[] = $buildTaskQuestion($task, $order, $missionTitle, $index);
        }

        $sectionData['missions'][] = [
            'title' => $missionTitle,
            'visual' => [
                'slug' => strtolower(preg_replace('/[^a-z0-9]+/', '-', $sectionName . '-' . $missionTitle) ?? 'mission'),
                'headline' => $missionTitle,
                'subheadline' => $missionDescriptions[$missionTitle] ?? 'Latihan berpikir komputasional.',
                'chips' => $sectionChips[$sectionName],
            ],
            'questions' => $questions,
        ];
    }

    $sections[] = $sectionData;
}

return $sections;
