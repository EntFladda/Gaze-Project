<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\Challenge;
use App\Models\Question;

class QuestionQualityAuditor
{
    public static function summary($challenges = null): array
    {
        $challenges ??= Challenge::withCount('questions')->get();
        $questions = Question::with(['answers', 'blocks', 'explanationImages', 'explanationBlocks'])->get();
        $readyQuestions = 0;
        $needsReview = 0;

        $questions->each(function (Question $question) use (&$readyQuestions, &$needsReview): void {
            if (empty(self::issues($question))) {
                $readyQuestions++;

                return;
            }

            $needsReview++;
        });

        return [
            'total_questions' => $questions->count(),
            'ready_questions' => $readyQuestions,
            'needs_review' => $needsReview,
            'empty_challenges' => $challenges->where('questions_count', 0)->count(),
        ];
    }

    public static function issues(Question $question): array
    {
        $question->loadMissing(['answers', 'blocks', 'explanationImages', 'explanationBlocks']);

        $issues = [];
        $answers = $question->answers;
        $contextText = trim((string) ($question->description ?: $question->blocks->where('type', 'text')->pluck('content')->implode("\n")));

        if (blank($question->question_text)) {
            $issues[] = 'Teks pertanyaan belum diisi.';
        }

        if ((int) $question->score <= 0) {
            $issues[] = 'Poin masih 0.';
        }

        if ((int) $question->exp <= 0) {
            $issues[] = 'EXP masih 0.';
        }

        if (blank($question->help_text)) {
            $issues[] = 'Petunjuk saat salah belum diisi.';
        }

        if (blank($question->explanation_text) && blank($question->explanation_image) && $question->explanationImages->isEmpty() && $question->explanationBlocks->isEmpty()) {
            $issues[] = 'Pembahasan jawaban belum diisi.';
        }

        if (strlen(strip_tags($contextText)) > 1200) {
            $issues[] = 'Materi soal terlalu panjang, sebaiknya dipisah menjadi beberapa bagian.';
        }

        if (strlen(strip_tags((string) $question->question_text)) > 420) {
            $issues[] = 'Pertanyaan cukup panjang, pastikan mudah dibaca mahasiswa.';
        }

        if ($question->type === 'multiple_choice') {
            $filledAnswers = $answers->filter(fn(Answer $answer) => filled($answer->answer) || filled($answer->answer_image));

            if ($filledAnswers->count() < 2) {
                $issues[] = 'Pilihan ganda minimal punya 2 jawaban.';
            }

            if ($filledAnswers->where('is_correct', true)->isEmpty()) {
                $issues[] = 'Belum ada jawaban benar.';
            }
        }

        if ($question->type === 'true_false') {
            $answerLabels = $answers->pluck('answer')->map(fn($answer) => strtolower(trim((string) $answer)))->all();

            if (! in_array('true', $answerLabels, true) || ! in_array('false', $answerLabels, true)) {
                $issues[] = 'Jawaban benar/salah belum lengkap.';
            }

            if ($answers->where('is_correct', true)->count() !== 1) {
                $issues[] = 'Benar/salah harus punya tepat 1 kunci.';
            }
        }

        if ($question->type === 'essay') {
            $essayAnswer = $answers->firstWhere('is_correct', true);

            if (! $essayAnswer || blank($essayAnswer->answer)) {
                $issues[] = 'Kunci jawaban esai belum diisi.';
            }
        }

        foreach (self::imagePaths($question) as $label => $path) {
            if (! self::storedAssetExists($path)) {
                $issues[] = "{$label} tidak ditemukan.";
            }
        }

        return $issues;
    }

    protected static function imagePaths(Question $question): array
    {
        $paths = [];

        if ($question->question_image) {
            $paths['Gambar pertanyaan'] = $question->question_image;
        }

        if ($question->explanationBlocks->isEmpty() && $question->explanation_image) {
            $paths['Gambar pembahasan'] = $question->explanation_image;
        }

        if ($question->explanationBlocks->isEmpty()) {
            foreach ($question->explanationImages as $index => $image) {
                if ($image->image_path) {
                    $paths['Gambar pembahasan ' . ($index + 1)] = $image->image_path;
                }
            }
        }

        foreach ($question->explanationBlocks as $index => $block) {
            if ($block->image_path) {
                $paths['Gambar blok pembahasan ' . ($index + 1)] = $block->image_path;
            }
        }

        foreach ($question->blocks as $index => $block) {
            if ($block->image_path) {
                $paths['Gambar susunan soal ' . ($index + 1)] = $block->image_path;
            }
        }

        foreach ($question->answers as $index => $answer) {
            if ($answer->answer_image) {
                $paths['Gambar jawaban ' . ($index + 1)] = $answer->answer_image;
            }
        }

        return $paths;
    }

    protected static function storedAssetExists(?string $path): bool
    {
        if (blank($path)) {
            return true;
        }

        return file_exists(storage_path('app/public/' . $path))
            || file_exists(public_path('storage/' . $path));
    }
}
