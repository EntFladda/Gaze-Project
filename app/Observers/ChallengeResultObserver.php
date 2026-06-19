<?php

namespace App\Observers;

use App\Models\Achievement;
use App\Models\ChallengeResult;
use App\Models\Section;
use App\Models\StudentAnswer;

class ChallengeResultObserver
{
    public function saved(ChallengeResult $result)
    {
        if (! $result->ended_at) {
            return;
        }

        $result->loadMissing(['challenge', 'user.student.achievements']);
        $user = $result->user;
        $student = $user?->student;

        if (! $student) {
            return;
        }

        $achievementCodes = [
            'first_mission',
            'three_missions',
            'five_missions',
            'ten_missions',
            'first_section',
            'two_sections',
            'perfect_mission',
            'high_achiever',
            'guided_success',
            'comeback_win',
            'first_essay',
            'three_day_streak',
            'seven_day_streak',
        ];
        $achievements = Achievement::whereIn('code', $achievementCodes)->get()->keyBy('code');
        $unlockedAchievementIds = $student->achievements->pluck('id')->all();

        $unlockIf = function (string $achievementCode, bool $condition) use ($student, $achievements, &$unlockedAchievementIds): void {
            if (! $condition) {
                return;
            }

            $achievement = $achievements->get($achievementCode);
            if ($achievement && ! in_array($achievement->id, $unlockedAchievementIds, true)) {
                $student->achievements()->attach($achievement->id, [
                    'unlocked_at' => now(),
                ]);
                $unlockedAchievementIds[] = $achievement->id;
            }
        };

        $completedChallengeIds = ChallengeResult::where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->distinct()
            ->pluck('challenge_id');

        $firstTime = $completedChallengeIds->count() >= 1;
        $unlockIf('first_mission', $firstTime);

        $unlockIf('three_missions', $completedChallengeIds->count() >= 3);
        $unlockIf('five_missions', $completedChallengeIds->count() >= 5);
        $unlockIf('ten_missions', $completedChallengeIds->count() >= 10);

        $sections = Section::with('challenges')->get();
        $completedSections = $sections->filter(function ($section) use ($completedChallengeIds) {
            return $section->challenges->isNotEmpty()
                && $section->challenges->every(fn($challenge) => $completedChallengeIds->contains($challenge->id));
        });
        $unlockIf('first_section', $completedSections->count() >= 1);
        $unlockIf('two_sections', $completedSections->count() >= 2);

        $isPerfect = $result->total_score === $result->challenge?->total_score && $result->wrong_answers === 0;
        $unlockIf('perfect_mission', $isPerfect);
        $unlockIf('high_achiever', $result->challenge?->total_score > 0
            && $result->total_score >= ($result->challenge->total_score * 0.8));

        $guidedSuccess = StudentAnswer::where('user_id', $user->id)
            ->where('challenge_id', $result->challenge_id)
            ->where('attempt_number', $result->attempt_number)
            ->where('used_help', true)
            ->where('is_correct', true)
            ->exists();
        $unlockIf('guided_success', $guidedSuccess);
        $unlockIf('comeback_win', $result->wrong_answers > 0 && $result->correct_answers > $result->wrong_answers);

        $firstEssay = StudentAnswer::query()
            ->where('user_id', $user->id)
            ->where('challenge_id', $result->challenge_id)
            ->where('attempt_number', $result->attempt_number)
            ->where('is_correct', true)
            ->whereHas('question', fn($query) => $query->where('type', 'essay'))
            ->exists();
        $unlockIf('first_essay', $firstEssay);

        $unlockIf('three_day_streak', (int) $student->streak >= 3);
        $unlockIf('seven_day_streak', (int) $student->streak >= 7);
    }
}
