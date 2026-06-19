<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\ChallengeResult;
use App\Models\Rank;
use App\Models\Section;
use Illuminate\Http\Request;

class MissionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $student = $user->student;
        $student->loadMissing('ranks');

        $rank = $student->current_rank?->name ?? 'Unranked';
        $sections = Section::with(['challenges' => function ($query) {
            $query->withCount('questions')->orderBy('id');
        }])->orderBy('order')->get();

        $completedChallengeIds = ChallengeResult::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->pluck('challenge_id')
            ->unique()
            ->values()
            ->all();

        $previousSectionCompleted = true;

        $sections->transform(function ($section) use (&$previousSectionCompleted, $completedChallengeIds) {
            $section->is_unlocked = $previousSectionCompleted;

            $previousChallengeCompleted = $section->is_unlocked;
            $allChallengesCompleted = true;
            $playableChallenges = 0;

            $section->challenges->transform(function ($challenge) use (&$previousChallengeCompleted, &$allChallengesCompleted, &$playableChallenges, $completedChallengeIds) {
                $challenge->has_questions = $challenge->questions_count > 0;

                if (! $challenge->has_questions) {
                    $challenge->is_completed = false;
                    $challenge->is_unlocked = false;
                    return $challenge;
                }

                $playableChallenges++;
                $challenge->is_completed = in_array($challenge->id, $completedChallengeIds, true);
                $challenge->is_unlocked = $previousChallengeCompleted;

                if (! $challenge->is_completed) {
                    $allChallengesCompleted = false;
                }

                $previousChallengeCompleted = $challenge->is_completed;

                return $challenge;
            });

            $section->is_completed = $playableChallenges === 0 || $allChallengesCompleted;
            $previousSectionCompleted = $section->is_completed;

            return $section;
        });

        $allRanks = Rank::orderBy('min_exp')->get();

        return view('student.mission.index', compact('student', 'rank', 'sections', 'allRanks'));
    }

    public function show($id)
    {
        $user = auth()->user();
        $challenge = Challenge::withCount('questions')->findOrFail($id);

        if ($challenge->questions_count < 1) {
            return response()->json([
                'message' => 'Mission ini belum punya soal.',
            ], 422);
        }

        if (! $this->isChallengeUnlockedForUser($user->id, $challenge)) {
            return response()->json([
                'message' => 'Mission ini masih terkunci. Selesaikan mission sebelumnya terlebih dahulu.',
            ], 403);
        }

        $latestAttemptNumber = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $id)
            ->max('attempt_number');

        $latestResult = null;
        $isPerfect = false;

        if ($latestAttemptNumber) {
            $latestResult = ChallengeResult::where('user_id', $user->id)
                ->where('challenge_id', $id)
                ->where('attempt_number', $latestAttemptNumber)
                ->first();

            $isPerfect = $latestResult && $latestResult->correct_answers == $challenge->questions_count;
        }

        return response()->json([
            'id' => $challenge->id,
            'title' => $challenge->title,
            'competency' => $challenge->ct_competency,
            'question_count' => $challenge->questions_count,
            'exp' => $challenge->total_exp,
            'score' => $challenge->total_score,
            'attempt_number' => $latestAttemptNumber ?? 0,
            'is_perfect' => $isPerfect,
        ]);
    }

    public function showChallenge($id)
    {
        return $this->show($id);
    }

    public function startChallenge(Request $request)
    {
        $user = auth()->user();
        $student = $user->student;
        $challengeId = $request->input('challenge_id');

        if (! $student || ! $challengeId) {
            return response()->json(['message' => 'Mission belum dipilih dengan benar.'], 400);
        }

        $challenge = Challenge::withCount('questions')->find($challengeId);
        if (! $challenge) {
            return response()->json(['message' => 'Mission tidak ditemukan.'], 404);
        }

        if ($challenge->questions_count < 1) {
            return response()->json(['message' => 'Mission ini belum punya soal.'], 422);
        }

        if (! $this->isChallengeUnlockedForUser($user->id, $challenge)) {
            return response()->json([
                'message' => 'Mission ini masih terkunci. Selesaikan mission sebelumnya terlebih dahulu.',
            ], 403);
        }

        $today = now()->toDateString();
        $lastStreakDate = $student->last_played;

        if ($lastStreakDate === $today) {
        } elseif ($lastStreakDate === now()->subDay()->toDateString()) {
            $student->increment('streak');
        } else {
            $student->streak = 1;
        }

        $student->last_played = $today;
        $student->current_challenge_id = $challenge->id;
        $student->current_section_id = $challenge->section_id;
        $student->save();

        return response()->json([
            'message' => 'Mission siap dimulai.',
            'streak' => $student->streak,
        ]);
    }

    protected function isChallengeUnlockedForUser(int $userId, Challenge $challenge): bool
    {
        $completedChallengeIds = $this->completedChallengeIdsForUser($userId);
        $section = Section::with(['challenges' => function ($query) {
            $query->withCount('questions')->orderBy('id');
        }])->findOrFail($challenge->section_id);

        if (! $this->isSectionUnlockedForUser($userId, $section->id, $completedChallengeIds)) {
            return false;
        }

        $previousChallenge = $section->challenges
            ->where('id', '<', $challenge->id)
            ->where('questions_count', '>', 0)
            ->sortByDesc('id')
            ->first();

        if (! $previousChallenge) {
            return true;
        }

        return in_array($previousChallenge->id, $completedChallengeIds, true);
    }

    protected function isSectionUnlockedForUser(int $userId, int $sectionId, ?array $completedChallengeIds = null): bool
    {
        $completedChallengeIds ??= $this->completedChallengeIdsForUser($userId);
        $section = Section::orderBy('order')->findOrFail($sectionId);
        $previousSection = Section::with(['challenges' => function ($query) {
            $query->withCount('questions')->orderBy('id');
        }])->where('order', '<', $section->order)
            ->orderByDesc('order')
            ->first();

        if (! $previousSection) {
            return true;
        }

        $playableChallenges = $previousSection->challenges->where('questions_count', '>', 0);

        if ($playableChallenges->isEmpty()) {
            return true;
        }

        return $playableChallenges->every(
            fn($challenge) => in_array($challenge->id, $completedChallengeIds, true)
        );
    }

    protected function completedChallengeIdsForUser(int $userId): array
    {
        return ChallengeResult::where('user_id', $userId)
            ->whereNotNull('ended_at')
            ->pluck('challenge_id')
            ->unique()
            ->values()
            ->all();
    }
}
