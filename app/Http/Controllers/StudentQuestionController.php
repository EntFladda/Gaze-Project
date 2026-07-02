<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\ChallengeResult;
use App\Models\Question;
use App\Models\StudentAnswer;
use App\Support\ComputationalThinking;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class StudentQuestionController extends Controller
{
    protected const MAX_WRONG_ATTEMPTS_PENALIZED = 2;
    protected const SCORE_PENALTY_PER_WRONG_ATTEMPT = 1;
    protected const SCORE_PENALTY_FOR_HELP = 1;

    public function show($challenge_id)
    {
        $user = Auth::user();

        $activeAttempt = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->whereNull('ended_at')
            ->orderByDesc('attempt_number')
            ->first();

        $lastAttempt = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->max('attempt_number');

        $newAttemptNumber = $activeAttempt?->attempt_number ?? ($lastAttempt ? $lastAttempt + 1 : 1);

        if (! $activeAttempt) {
            ChallengeResult::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge_id,
                'attempt_number' => $newAttemptNumber,
                'total_score' => 0,
                'total_exp' => 0,
                'correct_answers' => 0,
                'wrong_answers' => 0,
            ]);
        }

        $questions = Question::where('challenge_id', $challenge_id)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        session([
            'challenge_questions' => $questions,
            'current_question_index' => 0,
            'current_displayed_question_index' => null,
            'current_attempt' => $newAttemptNumber,
            'question_attempt_state' => [],
        ]);

        return redirect()->route('student.next.question', ['challenge_id' => $challenge_id]);
    }

    public function resumeQuestion($challenge_id)
    {
        return redirect()->route('student.next.question', ['challenge_id' => $challenge_id]);
    }

    public function checkAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_answer' => 'required',
        ]);

        $question = Question::with(['answers', 'challenge.section'])->findOrFail($request->question_id);
        $selectedAnswer = $request->selected_answer;

        if ($question->type === 'true_false') {
            $correctAnswer = $question->answers->firstWhere('is_correct', true);
            $isCorrect = $correctAnswer
                && strcasecmp(trim((string) $selectedAnswer), trim((string) $correctAnswer->answer)) === 0;

            $answerId = $question->answers
                ->firstWhere('answer', strtolower($selectedAnswer) === 'true' ? 'True' : 'False')
                ?->id;
        } else {
            $answer = $question->answers->firstWhere('id', (int) $selectedAnswer);
            $answerId = $answer?->id;
            $isCorrect = (bool) $answer?->is_correct;
        }

        $usedHelp = $this->resetExistingQuestionAttempt($question);
        $this->trackQuestionAttemptState($question->id, ! $isCorrect, $usedHelp);

        $challengeResult = $this->recordSingleAnswer($question, $isCorrect, [
            'answer_id' => $answerId,
            'selected_answer' => (string) $selectedAnswer,
            'answer_text' => null,
            'used_help' => $usedHelp,
        ]);

        return response()->json($this->buildAnswerPayload($question, $challengeResult, $isCorrect));
    }

    public function checkMultiple(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'selected_answers' => 'required|array|min:1',
        ]);

        $question = Question::with(['answers', 'challenge.section'])->findOrFail($request->question_id);
        $selectedAnswers = collect($request->selected_answers)->map(fn($id) => (int) $id)->sort()->values();
        $correctAnswers = $question->answers->where('is_correct', true)->pluck('id')->sort()->values();
        $isCorrect = $selectedAnswers->toArray() === $correctAnswers->toArray();

        $usedHelp = $this->resetExistingQuestionAttempt($question);
        $this->trackQuestionAttemptState($question->id, ! $isCorrect, $usedHelp);
        $challengeResult = null;

        foreach ($selectedAnswers as $answerId) {
            $challengeResult = $this->recordSingleAnswer($question, $isCorrect, [
                'answer_id' => $answerId,
                'selected_answer' => (string) $answerId,
                'answer_text' => null,
                'used_help' => $usedHelp,
            ], $challengeResult === null);
        }

        return response()->json($this->buildAnswerPayload($question, $challengeResult, $isCorrect));
    }

    public function checkEssayAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer' => 'required|string',
        ]);

        $user = Auth::user();
        $question = Question::with(['answers', 'challenge.section'])->findOrFail($request->question_id);
        $attemptNumber = session('current_attempt');

        $correctAnswers = $question->answers->where('is_correct', true);

        if ($correctAnswers->isEmpty()) {
            return response()->json([
                'error' => 'Kunci jawaban belum tersedia untuk soal ini.',
            ], 400);
        }

        $submittedAnswer = $this->normalizeText($request->answer);
        $isCorrect = $correctAnswers->contains(function ($answer) use ($submittedAnswer): bool {
            $correctAnswerText = $this->normalizeText($answer->answer);

            return $submittedAnswer === $correctAnswerText
                || levenshtein($submittedAnswer, $correctAnswerText) <= 2;
        });
        $usedHelp = $this->resetExistingQuestionAttempt($question);
        $this->trackQuestionAttemptState($question->id, ! $isCorrect, $usedHelp);
        $questionState = $this->currentQuestionAttemptState($question->id);

        StudentAnswer::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'challenge_id' => $question->challenge_id,
            'attempt_number' => $attemptNumber,
            'selected_answer' => $request->answer,
            'answer_text' => $request->answer,
            'is_correct' => $isCorrect,
            'used_help' => $usedHelp,
            'help_requested_at' => $usedHelp ? now() : null,
            'wrong_attempts' => (int) ($questionState['wrong_attempts'] ?? 0),
        ]);

        $challengeResult = $this->updateChallengeResult($question, $isCorrect);

        return response()->json($this->buildAnswerPayload($question, $challengeResult, $isCorrect));
    }

    public function requestHelp(Request $request)
    {
        $request->validate([
            'question_id' => 'required|exists:questions,id',
        ]);

        $user = Auth::user();
        $question = Question::with('challenge.section')->findOrFail($request->question_id);
        $attemptNumber = session('current_attempt');

        $answerQuery = StudentAnswer::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->where('attempt_number', $attemptNumber);

        $answerQuery->where('challenge_id', $question->challenge_id);

        $answerQuery->update([
            'used_help' => true,
            'help_requested_at' => now(),
        ]);

        $attemptState = session('question_attempt_state', []);
        $questionState = $attemptState[$question->id] ?? [
            'wrong_attempts' => 0,
            'used_help' => false,
        ];
        $questionState['used_help'] = true;
        $attemptState[$question->id] = $questionState;
        session()->put('question_attempt_state', $attemptState);

        return response()->json([
            'help_text' => $question->help_text ?: 'Belum ada bantuan khusus untuk soal ini.',
        ]);
    }

    public function nextQuestion(Request $request, $challenge_id)
    {
        $questions = session('challenge_questions', []);
        $index = session('current_question_index', 0);
        $displayedIndex = session('current_displayed_question_index');
        $attemptNumber = session('current_attempt');
        $user = Auth::user();
        $student = $user->student;
        $shouldAdvance = $displayedIndex === null;

        if (empty($questions) || ! $attemptNumber) {
            return redirect()->route('student.start.question', ['challenge_id' => $challenge_id]);
        }

        if ($request->boolean('advance') && $displayedIndex !== null && isset($questions[$displayedIndex])) {
            $hasAnsweredCurrentQuestion = StudentAnswer::query()
                ->where('user_id', $user->id)
                ->where('challenge_id', $challenge_id)
                ->where('question_id', $questions[$displayedIndex])
                ->where('attempt_number', $attemptNumber)
                ->exists();

            $shouldAdvance = $hasAnsweredCurrentQuestion;
        }

        if ($shouldAdvance) {
            $displayedIndex = $index;
            session([
                'current_displayed_question_index' => $displayedIndex,
                'current_question_index' => $displayedIndex + 1,
            ]);
        }

        if ($displayedIndex >= count($questions)) {
            $student->load('ranks');

            $challengeResult = ChallengeResult::where('user_id', $user->id)
                ->where('challenge_id', $challenge_id)
                ->where('attempt_number', $attemptNumber)
                ->first();

            if ($challengeResult) {
                $challengeResult->ended_at = now();
                if ($request->has('focus_percentage')) {
                    $challengeResult->focus_percentage = $request->input('focus_percentage');
                    $challengeResult->unfocused_count = $request->input('unfocused_count');
                    $challengeResult->focused_duration = $request->input('focused_duration');
                    $challengeResult->unfocused_duration = $request->input('unfocused_duration');
                }
                $challengeResult->save();

                $previousBestResult = ChallengeResult::where('user_id', $user->id)
                    ->where('challenge_id', $challenge_id)
                    ->where('attempt_number', '<', $attemptNumber)
                    ->orderByDesc('total_score')
                    ->orderByDesc('total_exp')
                    ->first();

                $previousHighestScore = $previousBestResult?->total_score ?? 0;
                $previousHighestExp = $previousBestResult?->total_exp ?? 0;

                if ($challengeResult->total_score > $previousHighestScore || $challengeResult->total_exp > $previousHighestExp) {
                    $scoreDelta = max(0, $challengeResult->total_score - $previousHighestScore);
                    $expDelta = max(0, $challengeResult->total_exp - $previousHighestExp);

                    $student->increment('weekly_score', $scoreDelta);
                    $student->increment('total_score', $scoreDelta);

                    if ($expDelta > 0) {
                        $student->increment('exp', $expDelta);
                    }
                }

                $rankUpdate = $student->updateRank();
                if ($rankUpdate['rank_changed']) {
                    $achievement = Achievement::where('code', 'rank_up')->first();
                    if ($achievement && ! $student->achievements()->where('achievement_id', $achievement->id)->exists()) {
                        $student->achievements()->attach($achievement->id, [
                            'unlocked_at' => now(),
                        ]);
                    }

                    if ($request->boolean('ajax')) {
                        return response()->json([
                            'redirect_url' => route('student.rank.up', [
                                'challenge_id' => $challenge_id,
                                'attempt_number' => $attemptNumber,
                            ]),
                        ]);
                    }

                    return redirect()->route('student.rank.up', [
                        'challenge_id' => $challenge_id,
                        'attempt_number' => $attemptNumber,
                    ]);
                }
            }

            if ($request->boolean('ajax')) {
                return response()->json([
                    'redirect_url' => route('student.challenge.summary', [
                        'challenge_id' => $challenge_id,
                        'attempt_number' => $attemptNumber,
                    ]),
                ]);
            }

            return redirect()->route('student.challenge.summary', [
                'challenge_id' => $challenge_id,
                'attempt_number' => $attemptNumber,
            ]);
        }

        $questionNumber = $displayedIndex + 1;
        $totalQuestions = count($questions);
        $question = Question::with(['answers', 'blocks', 'explanationImages', 'explanationBlocks', 'challenge.section'])->findOrFail($questions[$displayedIndex]);
        $this->ensureQuestionAssetsPubliclyAvailable($question);

        $progress = ($questionNumber / max($totalQuestions, 1)) * 100;

        if ($request->boolean('ajax')) {
            return response()->json([
                'html' => view('student.partials.question_session', compact('question', 'progress', 'questionNumber', 'totalQuestions', 'attemptNumber'))->render(),
            ]);
        }

        return view('student.question', compact('question', 'progress', 'questionNumber', 'totalQuestions', 'attemptNumber'));
    }

    public function challengeSummary($challenge_id, $attempt_number)
    {
        $totalQuestions = Question::where('challenge_id', $challenge_id)->count();

        $motivasiBenar = [
            'Mantap, kamu makin paham pola berpikirnya.',
            'Kerja bagus, ritme belajarmu sudah bagus.',
            'Bagus, pertahankan fokus seperti ini.',
            'Keren, logikamu mulai konsisten.',
        ];

        $motivasiSalah = [
            'Tidak apa-apa, lanjut review pembahasannya lalu coba lagi.',
            'Bagian yang sulit justru yang paling bagus untuk dipelajari.',
            'Tenang, lihat bantuan dan pembahasan supaya langkahnya makin jelas.',
            'Salah sekali bukan masalah, yang penting paham cara berpikirnya.',
        ];

        $user = Auth::user();
        $challengeResult = ChallengeResult::with('challenge.section')
            ->where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->where('attempt_number', $attempt_number)
            ->firstOrFail();

        $answersByQuestion = StudentAnswer::with('question')
            ->where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->where('attempt_number', $attempt_number)
            ->orderBy('question_id')
            ->orderBy('result_id')
            ->get()
            ->groupBy('question_id');

        $attemptState = session('question_attempt_state', []);
        $questionSummaries = Question::where('challenge_id', $challenge_id)
            ->orderBy('id')
            ->get()
            ->map(function (Question $question, int $index) use ($answersByQuestion, $attemptState) {
                $answers = $answersByQuestion->get($question->id, collect());
                $finalAnswer = $answers->first();
                $sessionUsedHelp = (bool) ($attemptState[$question->id]['used_help'] ?? false);
                $usedHelp = $sessionUsedHelp || $answers->contains(fn(StudentAnswer $answer) => (bool) $answer->used_help);
                $storedWrongAttempts = (int) ($answers->max('wrong_attempts') ?? 0);
                $wrongAttempts = max((int) ($attemptState[$question->id]['wrong_attempts'] ?? 0), $storedWrongAttempts);

                return [
                    'number' => $index + 1,
                    'question_id' => $question->id,
                    'is_correct' => (bool) ($finalAnswer?->is_correct),
                    'used_help' => $usedHelp,
                    'wrong_attempts' => $wrongAttempts,
                    'answered' => $answers->isNotEmpty(),
                ];
            });

        $usedHelpCount = $questionSummaries->where('used_help', true)->count();
        $wrongAnswerAttemptCount = $questionSummaries->sum('wrong_attempts');
        $attemptCount = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->count();

        $isPerfect = $challengeResult->correct_answers == $totalQuestions;
        $user->student->updateRank();

        return view('student.challenge_summary', compact(
            'challengeResult',
            'motivasiBenar',
            'motivasiSalah',
            'isPerfect',
            'totalQuestions',
            'questionSummaries',
            'usedHelpCount',
            'wrongAnswerAttemptCount',
            'attemptCount'
        ));
    }

    public function exitChallenge(Request $request)
    {
        $user = Auth::user();
        $challenge_id = $request->challenge_id;
        $sessionAttempt = session('current_attempt');

        $baseAttemptQuery = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->whereNull('ended_at');

        $challengeResult = null;

        if ($sessionAttempt) {
            $challengeResult = (clone $baseAttemptQuery)
                ->where('attempt_number', $sessionAttempt)
                ->first();
        }

        $challengeResult ??= $baseAttemptQuery->orderByDesc('attempt_number')->first();

        if (! $challengeResult) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada pengerjaan aktif yang bisa dibatalkan. Riwayat yang sudah selesai tetap disimpan.',
            ]);
        }

        $attemptNumber = $challengeResult->attempt_number;
        $challengeResult->delete();

        StudentAnswer::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->where('attempt_number', $attemptNumber)
            ->delete();

        $todayAttemptCount = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($todayAttemptCount < 1) {
            $student = $user->student;
            $student->streak = max(0, $student->streak - 1);
            $student->save();
        }

        if ((int) $sessionAttempt === (int) $attemptNumber) {
            session()->forget([
                'challenge_questions',
                'current_question_index',
                'current_displayed_question_index',
                'current_attempt',
                'question_attempt_state',
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Pengerjaan dibatalkan.']);
    }

    protected function recordSingleAnswer(Question $question, bool $isCorrect, array $payload, bool $updateResult = true)
    {
        $user = Auth::user();
        $attemptNumber = session('current_attempt');
        $questionState = $this->currentQuestionAttemptState($question->id);

        StudentAnswer::create([
            'user_id' => $user->id,
            'question_id' => $question->id,
            'challenge_id' => $question->challenge_id,
            'attempt_number' => $attemptNumber,
            'answer_id' => $payload['answer_id'] ?? null,
            'selected_answer' => $payload['selected_answer'] ?? null,
            'answer_text' => $payload['answer_text'] ?? null,
            'is_correct' => $isCorrect,
            'used_help' => $payload['used_help'] ?? false,
            'help_requested_at' => ($payload['used_help'] ?? false) ? now() : null,
            'wrong_attempts' => (int) ($questionState['wrong_attempts'] ?? 0),
        ]);

        if (! $updateResult) {
            return ChallengeResult::where('user_id', $user->id)
                ->where('challenge_id', $question->challenge_id)
                ->where('attempt_number', $attemptNumber)
                ->firstOrFail();
        }

        return $this->updateChallengeResult($question, $isCorrect);
    }

    protected function updateChallengeResult(Question $question, bool $isCorrect)
    {
        $user = Auth::user();
        $challengeResult = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $question->challenge_id)
            ->where('attempt_number', session('current_attempt'))
            ->firstOrFail();

        $this->recalculateChallengeAttempt($challengeResult);

        $challengeResult->save();

        return $challengeResult;
    }

    protected function buildAnswerPayload(Question $question, $challengeResult, bool $isCorrect): array
    {
        $concept = $this->inferQuestionConcept($question);

        return [
            'is_correct' => $isCorrect,
            'correct' => $isCorrect,
            'is_assessment' => false,
            'total_score' => $challengeResult->total_score,
            'total_exp' => $challengeResult->total_exp,
            'correct_answers' => $challengeResult->correct_answers,
            'wrong_answers' => $challengeResult->wrong_answers,
            'has_help' => ! blank($question->help_text),
            'feedback' => ComputationalThinking::feedback($concept, $isCorrect, $this->isLastQuestion()),
        ];
    }

    protected function inferQuestionConcept(Question $question): array
    {
        $question->loadMissing('challenge.section');

        return ComputationalThinking::infer(implode(' ', [
            $question->challenge?->section?->name,
            $question->challenge?->title,
            $question->question_text,
            $question->description,
            $question->help_text,
            $question->explanation_text,
        ]));
    }

    protected function isLastQuestion(): bool
    {
        $questions = session('challenge_questions', []);
        $displayedIndex = session('current_displayed_question_index', 0);

        return $displayedIndex !== null && ((int) $displayedIndex + 1) >= count($questions);
    }

    protected function normalizeText(string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($text));
    }

    protected function resetExistingQuestionAttempt(Question $question): bool
    {
        $user = Auth::user();
        $attemptNumber = session('current_attempt');

        $existingAnswersQuery = StudentAnswer::where('user_id', $user->id)
            ->where('question_id', $question->id)
            ->where('attempt_number', $attemptNumber);

        $existingAnswersQuery->where('challenge_id', $question->challenge_id);

        $existingAnswers = $existingAnswersQuery->get();

        $attemptState = session('question_attempt_state', []);
        $questionState = $attemptState[$question->id] ?? null;
        $usedHelp = (bool) ($questionState['used_help'] ?? false)
            || $existingAnswers->contains(fn($answer) => $answer->used_help);

        if ($existingAnswers->isEmpty()) {
            return $usedHelp;
        }

        StudentAnswer::whereIn('result_id', $existingAnswers->pluck('result_id'))->delete();

        return $usedHelp;
    }

    protected function recalculateChallengeAttempt(ChallengeResult $challengeResult): void
    {
        $answers = StudentAnswer::query()
            ->where('user_id', $challengeResult->user_id)
            ->where('challenge_id', $challengeResult->challenge_id)
            ->where('attempt_number', $challengeResult->attempt_number)
            ->get()
            ->groupBy('question_id');

        $finalAnswers = $answers
            ->map(fn(Collection $questionAnswers) => $questionAnswers->first());

        $attemptState = session('question_attempt_state', []);
        $correctQuestionIds = $finalAnswers
            ->filter(fn(StudentAnswer $answer) => (bool) $answer->is_correct)
            ->keys()
            ->map(fn($id) => (int) $id)
            ->all();

        $questionWeights = Question::query()
            ->whereIn('id', $finalAnswers->keys())
            ->get()
            ->keyBy('id');

        $challengeResult->correct_answers = count($correctQuestionIds);
        $challengeResult->wrong_answers = max(0, $finalAnswers->count() - $challengeResult->correct_answers);
        $challengeResult->total_score = collect($correctQuestionIds)
            ->sum(function (int $questionId) use ($answers, $questionWeights, $attemptState) {
                $question = $questionWeights[$questionId] ?? null;

                if (! $question) {
                    return 0;
                }

                $state = $this->questionStateForScoring($questionId, $answers->get($questionId, collect()), $attemptState);

                return $this->calculateQuestionScore((int) $question->score, $state);
            });
        $challengeResult->total_exp = collect($correctQuestionIds)
            ->sum(function (int $questionId) use ($answers, $questionWeights, $attemptState) {
                $question = $questionWeights[$questionId] ?? null;

                if (! $question) {
                    return 0;
                }

                $state = $this->questionStateForScoring($questionId, $answers->get($questionId, collect()), $attemptState);
                $state['base_score'] = (int) $question->score;

                return $this->calculateQuestionExp((int) $question->exp, $state);
            });
    }

    protected function trackQuestionAttemptState(int $questionId, bool $wasWrong, bool $usedHelp): void
    {
        $attemptState = session('question_attempt_state', []);
        $questionState = $attemptState[$questionId] ?? [
            'wrong_attempts' => 0,
            'used_help' => false,
        ];

        if ($wasWrong) {
            $questionState['wrong_attempts'] += 1;
        }

        if ($usedHelp) {
            $questionState['used_help'] = true;
        }

        $attemptState[$questionId] = $questionState;
        session()->put('question_attempt_state', $attemptState);
    }

    protected function currentQuestionAttemptState(int $questionId): array
    {
        $attemptState = session('question_attempt_state', []);

        return $attemptState[$questionId] ?? [
            'wrong_attempts' => 0,
            'used_help' => false,
        ];
    }

    protected function questionStateForScoring(int $questionId, Collection $answers, array $attemptState): array
    {
        $sessionState = $attemptState[$questionId] ?? [
            'wrong_attempts' => 0,
            'used_help' => false,
        ];

        return [
            'wrong_attempts' => max(
                (int) ($sessionState['wrong_attempts'] ?? 0),
                (int) ($answers->max('wrong_attempts') ?? 0)
            ),
            'used_help' => (bool) ($sessionState['used_help'] ?? false)
                || $answers->contains(fn(StudentAnswer $answer) => (bool) $answer->used_help),
        ];
    }

    protected function calculateQuestionScore(int $baseScore, array $state): int
    {
        $wrongAttempts = (int) ($state['wrong_attempts'] ?? 0);
        $usedHelp = (bool) ($state['used_help'] ?? false);

        $wrongPenalty = min($wrongAttempts, self::MAX_WRONG_ATTEMPTS_PENALIZED)
            * self::SCORE_PENALTY_PER_WRONG_ATTEMPT;
        $helpPenalty = $usedHelp ? self::SCORE_PENALTY_FOR_HELP : 0;

        return max(0, $baseScore - $wrongPenalty - $helpPenalty);
    }

    protected function calculateQuestionExp(int $baseExp, array $state): int
    {
        $baseScore = (int) ($state['base_score'] ?? 0);

        if ($baseScore <= 0) {
            return $baseExp;
        }

        $earnedScore = $this->calculateQuestionScore($baseScore, $state);
        $scoreRatio = $earnedScore / max($baseScore, 1);

        return (int) round($baseExp * $scoreRatio);
    }

    protected function ensureQuestionAssetsPubliclyAvailable(Question $question): void
    {
        if ($question->question_image) {
            $this->syncPublicStorageFile($question->question_image);
        }

        if ($question->explanation_image) {
            $this->syncPublicStorageFile($question->explanation_image);
        }

        $question->explanationImages->each(function ($image): void {
            if ($image->image_path) {
                $this->syncPublicStorageFile($image->image_path);
            }
        });

        $question->explanationBlocks->each(function ($block): void {
            if ($block->image_path) {
                $this->syncPublicStorageFile($block->image_path);
            }
        });

        $question->blocks->each(function ($block): void {
            if ($block->image_path) {
                $this->syncPublicStorageFile($block->image_path);
            }
        });

        $question->answers->each(function ($answer): void {
            if ($answer->answer_image) {
                $this->syncPublicStorageFile($answer->answer_image);
            }
        });
    }

    protected function syncPublicStorageFile(?string $path): void
    {
        if (blank($path)) {
            return;
        }

        $storagePath = storage_path('app/public/' . $path);
        $publicPath = public_path('storage/' . $path);

        if (! file_exists($storagePath) || file_exists($publicPath)) {
            return;
        }

        $directory = dirname($publicPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        @copy($storagePath, $publicPath);
    }
}
