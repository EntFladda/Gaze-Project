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

$jobs = [
    'setup' => [],
    'clear_answers' => null,
    'clear' => null,
    'missions' => [],
    'finalize' => null,
];

$sectionNames = array_map(fn (array $section): string => $section['name'], $sections);
$sectionList = implode(', ', array_map('sql_string', $sectionNames));

foreach ($sections as $section) {
    $jobs['setup'][] = sprintf(
        "INSERT INTO sections (`name`, `order`, created_at, updated_at)\nSELECT %s, %d, NOW(), NOW()\nWHERE NOT EXISTS (\n  SELECT 1 FROM sections WHERE `name` = %s\n)",
        sql_string($section['name']),
        (int) $section['order'],
        sql_string($section['name'])
    );

    $jobs['setup'][] = sprintf(
        "UPDATE sections SET `order` = %d, updated_at = NOW() WHERE `name` = %s",
        (int) $section['order'],
        sql_string($section['name'])
    );

    foreach ($section['missions'] as $mission) {
        $jobs['setup'][] = sprintf(
            "INSERT INTO challenges (section_id, title, total_exp, total_score, created_at, updated_at)\nSELECT s.id, %s, 0, 0, NOW(), NOW()\nFROM sections s\nWHERE s.`name` = %s\n  AND NOT EXISTS (\n    SELECT 1 FROM challenges c\n    WHERE c.section_id = s.id AND c.title = %s\n  )",
            sql_string($mission['title']),
            sql_string($section['name']),
            sql_string($mission['title'])
        );

        $questionSelects = [];
        $answerSelects = [];

        foreach ($mission['questions'] as $question) {
            $questionSelects[] = sprintf(
                "SELECT c.id AS challenge_id, %s AS type, %s AS description, %s AS question_text, %s AS help_text, %s AS explanation_text, %s AS question_image, %d AS score, %d AS exp, NOW() AS created_at, NOW() AS updated_at\nFROM challenges c\nJOIN sections s ON s.id = c.section_id\nWHERE s.`name` = %s AND c.title = %s",
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
                $answerSelects[] = sprintf(
                    "SELECT q.id AS question_id, %s AS answer, NULL AS answer_image, %d AS is_correct, NOW() AS created_at, NOW() AS updated_at\nFROM questions q\nJOIN challenges c ON c.id = q.challenge_id\nJOIN sections s ON s.id = c.section_id\nWHERE s.`name` = %s AND c.title = %s AND q.question_text = %s",
                    sql_string($answer['answer']),
                    ! empty($answer['is_correct']) ? 1 : 0,
                    sql_string($section['name']),
                    sql_string($mission['title']),
                    sql_string($question['question_text'])
                );
            }
        }

        $jobs['missions'][] = [
            'section' => $section['name'],
            'title' => $mission['title'],
            'insert_questions' => "INSERT INTO questions (challenge_id, type, description, question_text, help_text, explanation_text, question_image, score, exp, created_at, updated_at)\n" . implode("\nUNION ALL\n", $questionSelects),
            'insert_answers' => "INSERT INTO answers (question_id, answer, answer_image, is_correct, created_at, updated_at)\n" . implode("\nUNION ALL\n", $answerSelects),
        ];
    }
}

$jobs['clear_answers'] = <<<SQL
DELETE a
FROM answers a
JOIN questions q ON q.id = a.question_id
JOIN challenges c ON c.id = q.challenge_id
JOIN sections s ON s.id = c.section_id
WHERE s.`name` IN ($sectionList)
SQL;

$jobs['clear'] = <<<SQL
DELETE q
FROM questions q
JOIN challenges c ON c.id = q.challenge_id
JOIN sections s ON s.id = c.section_id
WHERE s.`name` IN ($sectionList)
SQL;

$jobs['finalize'] = <<<SQL
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
WHERE s.`name` IN ($sectionList)
SQL;

echo json_encode($jobs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
