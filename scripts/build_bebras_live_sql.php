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

function sql_string(?string $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return "'" . str_replace(
        ["\\", "\0", "\n", "\r", "\x1a", "'"],
        ["\\\\", "\\0", "\\n", "\\r", "\\Z", "\\'"],
        $value
    ) . "'";
}

$sectionNames = array_map(fn (array $section): string => $section['name'], $sections);
$lines = [
    'SET NAMES utf8mb4;',
    'START TRANSACTION;',
];

foreach ($sections as $section) {
    $lines[] = sprintf(
        "INSERT INTO sections (`name`, `order`, created_at, updated_at)\nSELECT %s, %d, NOW(), NOW()\nWHERE NOT EXISTS (\n  SELECT 1 FROM sections WHERE `name` = %s\n);",
        sql_string($section['name']),
        (int) $section['order'],
        sql_string($section['name'])
    );

    $lines[] = sprintf(
        "UPDATE sections SET `order` = %d, updated_at = NOW() WHERE `name` = %s;",
        (int) $section['order'],
        sql_string($section['name'])
    );

    foreach ($section['missions'] as $mission) {
        $lines[] = sprintf(
            "INSERT INTO challenges (section_id, title, total_exp, total_score, created_at, updated_at)\nSELECT s.id, %s, 0, 0, NOW(), NOW()\nFROM sections s\nWHERE s.`name` = %s\n  AND NOT EXISTS (\n    SELECT 1 FROM challenges c\n    WHERE c.section_id = s.id AND c.title = %s\n  );",
            sql_string($mission['title']),
            sql_string($section['name']),
            sql_string($mission['title'])
        );
    }
}

$sectionList = implode(', ', array_map('sql_string', $sectionNames));

$lines[] = <<<SQL
DELETE a
FROM answers a
JOIN questions q ON q.id = a.question_id
JOIN challenges c ON c.id = q.challenge_id
JOIN sections s ON s.id = c.section_id
WHERE s.`name` IN ($sectionList);
SQL;

$lines[] = <<<SQL
DELETE q
FROM questions q
JOIN challenges c ON c.id = q.challenge_id
JOIN sections s ON s.id = c.section_id
WHERE s.`name` IN ($sectionList);
SQL;

foreach ($sections as $section) {
    foreach ($section['missions'] as $mission) {
        foreach ($mission['questions'] as $question) {
            $lines[] = sprintf(
                "INSERT INTO questions (challenge_id, type, description, question_text, help_text, explanation_text, question_image, score, exp, created_at, updated_at)\nSELECT c.id, %s, %s, %s, %s, %s, %s, %d, %d, NOW(), NOW()\nFROM challenges c\nJOIN sections s ON s.id = c.section_id\nWHERE s.`name` = %s AND c.title = %s;",
                sql_string($question['type']),
                sql_string($question['description']),
                sql_string($question['question_text']),
                sql_string($question['help_text']),
                sql_string($question['explanation_text']),
                sql_string(question_image_path($question['question_image'] ?? null)),
                (int) $question['score'],
                (int) $question['exp'],
                sql_string($section['name']),
                sql_string($mission['title'])
            );

            foreach ($question['answers'] as $answer) {
                $lines[] = sprintf(
                    "INSERT INTO answers (question_id, answer, answer_image, is_correct, created_at, updated_at)\nSELECT q.id, %s, NULL, %d, NOW(), NOW()\nFROM questions q\nJOIN challenges c ON c.id = q.challenge_id\nJOIN sections s ON s.id = c.section_id\nWHERE s.`name` = %s\n  AND c.title = %s\n  AND q.question_text = %s;",
                    sql_string($answer['answer']),
                    ! empty($answer['is_correct']) ? 1 : 0,
                    sql_string($section['name']),
                    sql_string($mission['title']),
                    sql_string($question['question_text'])
                );
            }
        }
    }
}

$lines[] = <<<SQL
UPDATE challenges c
JOIN sections s ON s.id = c.section_id
LEFT JOIN (
  SELECT challenge_id, COALESCE(SUM(score), 0) AS total_score, COALESCE(SUM(exp), 0) AS total_exp
  FROM questions
  GROUP BY challenge_id
) q ON q.challenge_id = c.id
SET c.total_score = COALESCE(q.total_score, 0),
    c.total_exp = COALESCE(q.total_exp, 0),
    c.updated_at = NOW()
WHERE s.`name` IN ($sectionList);
SQL;

$lines[] = 'COMMIT;';

echo implode("\n\n", $lines) . "\n";
