<?php

namespace App\Support;

use App\Models\Challenge;
use App\Models\ChallengeResult;
use App\Models\Student;
use App\Models\StudentAnswer;

class ClassroomInsights
{
    public static function build(?string $class = null): array
    {
        $students = Student::with(['user', 'currentChallenge', 'challengeResults'])
            ->when($class, fn ($query) => $query->where('class', $class))
            ->get();

        $userIds = $students->pluck('user_id');

        $results = ChallengeResult::with('challenge.section')
            ->when($class, fn ($query) => $query->whereIn('user_id', $userIds))
            ->get();
        $answerAttempts = StudentAnswer::with('question.challenge.section')
            ->when($class, fn ($query) => $query->whereIn('user_id', $userIds))
            ->get()
            ->groupBy(fn(StudentAnswer $answer) => implode('-', [
                $answer->user_id,
                $answer->challenge_id,
                $answer->question_id,
                $answer->attempt_number,
            ]))
            ->map(fn($answers) => $answers->first());

        $studentRows = $students->map(function (Student $student) {
            $results = $student->challengeResults;
            $answered = $results->sum(fn(ChallengeResult $result) => $result->correct_answers + $result->wrong_answers);
            $wrong = $results->sum('wrong_answers');
            $correct = $results->sum('correct_answers');
            $accuracy = $answered > 0 ? round(($correct / $answered) * 100) : null;
            $completedMissions = $results->whereNotNull('ended_at')->pluck('challenge_id')->unique()->count();
            $lastActivity = $results->max('updated_at');

            $status = 'Belum mulai';
            if ($answered > 0 && $accuracy !== null && $accuracy < 60) {
                $status = 'Perlu review konsep';
            } elseif ($answered > 0 && $wrong >= 3) {
                $status = 'Sering salah';
            } elseif ($answered > 0 && $completedMissions < 1) {
                $status = 'Belum menyelesaikan mission';
            }

            $priorityScore = 0;
            if ($answered === 0) {
                $priorityScore += 100;
            }

            if ($answered > 0 && $accuracy !== null && $accuracy < 60) {
                $priorityScore += 80 + (60 - $accuracy);
            }

            if ($wrong >= 3) {
                $priorityScore += $wrong * 5;
            }

            if ($answered > 0 && $completedMissions < 1) {
                $priorityScore += 25;
            }

            return (object) [
                'name' => $student->user?->name ?? 'Mahasiswa',
                'current_mission' => $student->currentChallenge?->title ?? '-',
                'answered' => $answered,
                'wrong' => $wrong,
                'accuracy' => $accuracy,
                'completed_missions' => $completedMissions,
                'last_activity' => $lastActivity,
                'status' => $status,
                'priority_score' => $priorityScore,
            ];
        });

        $attentionStudents = $studentRows
            ->filter(fn($row) => $row->priority_score > 0)
            ->sortByDesc('priority_score')
            ->take(5)
            ->values();

        $difficultQuestions = $answerAttempts
            ->groupBy('question_id')
            ->map(function ($attempts) {
                $first = $attempts->first();
                $total = $attempts->count();
                $wrong = $attempts->where('is_correct', false)->count();

                return (object) [
                    'question' => $first->question,
                    'attempts' => $total,
                    'wrong' => $wrong,
                    'wrong_rate' => $total > 0 ? round(($wrong / $total) * 100) : 0,
                ];
            })
            ->filter(fn($row) => $row->wrong > 0 && $row->question)
            ->sortByDesc('wrong_rate')
            ->take(5)
            ->values();

        $difficultMissions = $results
            ->groupBy('challenge_id')
            ->map(function ($missionResults) {
                $first = $missionResults->first();
                $answered = $missionResults->sum(fn(ChallengeResult $result) => $result->correct_answers + $result->wrong_answers);
                $correct = $missionResults->sum('correct_answers');

                return (object) [
                    'challenge' => $first->challenge,
                    'attempts' => $missionResults->count(),
                    'accuracy' => $answered > 0 ? round(($correct / $answered) * 100) : 0,
                ];
            })
            ->filter(fn($row) => $row->challenge && $row->attempts > 0)
            ->sortBy('accuracy')
            ->take(5)
            ->values();

        $totalStudents = $students->count();
        $activeStudents = $studentRows->where('answered', '>', 0)->count();
        $totalAnswered = $studentRows->sum('answered');
        $totalWrong = $studentRows->sum('wrong');
        $averageAccuracy = $totalAnswered > 0 ? round((($totalAnswered - $totalWrong) / $totalAnswered) * 100) : 0;
        $emptyChallenges = Challenge::withCount('questions')->get()->where('questions_count', 0)->count();

        $recommendations = collect();
        if ($emptyChallenges > 0) {
            $recommendations->push("Lengkapi {$emptyChallenges} mission kosong sebelum dipakai mahasiswa.");
        }

        if ($difficultQuestions->isNotEmpty()) {
            $recommendations->push('Bahas ulang soal dengan persentase salah tertinggi di kelas.');
        }

        if ($attentionStudents->where('answered', 0)->isNotEmpty()) {
            $recommendations->push('Cek mahasiswa yang belum mulai, kemungkinan belum login atau belum paham alur mission.');
        }

        if ($averageAccuracy > 0 && $averageAccuracy < 70) {
            $recommendations->push('Akurasi kelas masih rendah, beri contoh langkah berpikir CT sebelum lanjut mission berikutnya.');
        }

        if ($recommendations->isEmpty()) {
            $recommendations->push('Kelas terlihat stabil. Lanjut pantau mission berikutnya dan cek soal baru sebelum dirilis.');
        }

        return [
            'total_students' => $totalStudents,
            'active_students' => $activeStudents,
            'students_not_started' => $studentRows->where('answered', 0)->count(),
            'attention_count' => $attentionStudents->count(),
            'average_accuracy' => $averageAccuracy,
            'empty_challenges' => $emptyChallenges,
            'total_attempts' => $results->count(),
            'difficult_question_count' => $difficultQuestions->count(),
            'difficult_mission_count' => $difficultMissions->count(),
            'attention_students' => $attentionStudents,
            'difficult_questions' => $difficultQuestions,
            'difficult_missions' => $difficultMissions,
            'recommendations' => $recommendations,
        ];
    }
}
