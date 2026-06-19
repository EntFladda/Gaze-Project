<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\ChallengeResult;
use App\Models\StudentAnswer;

class StudentReviewController extends Controller
{
    public function show($challenge_id, $attempt_number)
    {
        $user = auth()->user();

        $result = ChallengeResult::where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->where('attempt_number', $attempt_number)
            ->firstOrFail();

        $answers = StudentAnswer::with(['question.answers', 'question.blocks', 'question.explanationImages', 'question.explanationBlocks', 'selectedAnswer'])
            ->where('user_id', $user->id)
            ->where('challenge_id', $challenge_id)
            ->where('attempt_number', $attempt_number)
            ->orderBy('question_id')
            ->orderBy('result_id')
            ->get()
            ->groupBy('question_id');

        $student = $user->student()->with('achievements')->first();
        $achievement = Achievement::where('code', 'review_reader')->first();
        if ($achievement && ! $student->achievements->contains($achievement->id)) {
            $student->achievements()->attach($achievement->id, [
                'unlocked_at' => now(),
            ]);
        }

        return view('student.review', compact('result', 'answers'));
    }
}
