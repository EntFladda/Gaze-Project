<?php

$sections = require __DIR__ . '/../database/seeders/data/bebras_mission_bank.php';

function question_image_path(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $normalizedPath = str_replace('\\', '/', trim($value));
    if ($normalizedPath === '') {
        return null;
    }

    if (str_starts_with($normalizedPath, 'questions/')) {
        return $normalizedPath;
    }

    $extension = pathinfo($normalizedPath, PATHINFO_EXTENSION) ?: 'png';
    $filename = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($normalizedPath, PATHINFO_FILENAME)) ?: 'question-image';
    $directory = preg_replace('/[^A-Za-z0-9_-]+/', '-', dirname($normalizedPath));
    $directory = trim((string) $directory, '-.');
    $targetName = $directory === '' || $directory === '.'
        ? $filename . '.' . $extension
        : $directory . '-' . $filename . '.' . $extension;

    return 'questions/' . $targetName;
}

$manifest = [];

foreach ($sections as $section) {
    foreach ($section['missions'] as $mission) {
        foreach ($mission['questions'] as $question) {
            $source = $question['question_image'] ?? null;
            if (! is_string($source) || trim($source) === '') {
                continue;
            }

            $normalizedSource = str_replace('\\', '/', trim($source));
            $target = question_image_path($normalizedSource);

            $manifest[$target] = [
                'source' => $normalizedSource,
                'target' => $target,
            ];
        }
    }
}

ksort($manifest);

echo json_encode(array_values($manifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
