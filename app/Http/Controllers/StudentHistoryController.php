<?php

namespace App\Http\Controllers;

use App\Models\ChallengeResult;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class StudentHistoryController extends Controller
{
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $student = $user->student;

        $resultsQuery = ChallengeResult::with(['challenge' => function ($query) {
            $query->withCount('questions')->with('section');
        }])
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at')
            ->orderByDesc('attempt_number');

        /** @var LengthAwarePaginator $results */
        $results = $resultsQuery->paginate(10);
        $results->withQueryString();

        $bestResults = ChallengeResult::with(['challenge' => function ($query) {
            $query->withCount('questions')->with('section');
        }])
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereNotExists(function ($query) use ($user) {
                $query->selectRaw('1')
                    ->from('challenge_results as better_results')
                    ->whereColumn('better_results.challenge_id', 'challenge_results.challenge_id')
                    ->where('better_results.user_id', $user->id)
                    ->whereNotNull('better_results.ended_at')
                    ->where(function ($query) {
                        $query->whereColumn('better_results.total_score', '>', 'challenge_results.total_score')
                            ->orWhere(function ($query) {
                                $query->whereColumn('better_results.total_score', 'challenge_results.total_score')
                                    ->whereColumn('better_results.total_exp', '>', 'challenge_results.total_exp');
                            })
                            ->orWhere(function ($query) {
                                $query->whereColumn('better_results.total_score', 'challenge_results.total_score')
                                    ->whereColumn('better_results.total_exp', 'challenge_results.total_exp')
                                    ->whereColumn('better_results.id', '>', 'challenge_results.id');
                            });
                    });
            })
            ->orderByDesc('total_score')
            ->orderByDesc('total_exp')
            ->get();

        $summaryRow = ChallengeResult::query()
            ->where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->selectRaw('COUNT(*) as total_attempts, COUNT(DISTINCT challenge_id) as completed_missions')
            ->first();

        $summary = [
            'completed_missions' => (int) ($summaryRow->completed_missions ?? 0),
            'total_attempts' => (int) ($summaryRow->total_attempts ?? 0),
            'best_score' => $bestResults->max('total_score') ?? 0,
            'average_score' => round($bestResults->avg('total_score') ?? 0, 1),
        ];

        return view('student.history.index', compact('student', 'results', 'bestResults', 'summary'));
    }
}
