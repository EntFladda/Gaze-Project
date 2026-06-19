<?php

namespace App\Http\Controllers;

use App\Models\Rank;
use App\Models\Student;

class LecturerDashboardController extends Controller
{
    public function index()
    {
        $ranks = Rank::orderBy('min_exp')->get();
        $hasStudentProgress = Student::query()
            ->where('exp', '>', 0)
            ->orWhere('streak', '>', 0)
            ->orWhere('weekly_score', '>', 0)
            ->orWhere('total_score', '>', 0)
            ->exists();
        $totalStudents = Student::count();
        $activeStudents = Student::query()
            ->where('exp', '>', 0)
            ->orWhere('streak', '>', 0)
            ->orWhere('weekly_score', '>', 0)
            ->orWhere('total_score', '>', 0)
            ->count();

        $rankStats = $hasStudentProgress ? $ranks->map(function (Rank $rank) {
            return (object) [
                'name' => $rank->name,
                'students_count' => Student::whereBetween('exp', [$rank->min_exp, $rank->max_exp])->count(),
            ];
        }) : collect();

        $streakStats = $hasStudentProgress ? Student::selectRaw('streak, COUNT(*) as total')
            ->groupBy('streak')
            ->orderBy('streak')
            ->get() : collect();

        $topStudents = $hasStudentProgress ? Student::with('user')
            ->orderByDesc('weekly_score')
            ->orderByDesc('total_score')
            ->orderByDesc('exp')
            ->take(5)
            ->get() : collect();

        $topStudents->each(function (Student $student) use ($ranks) {
            $currentRank = $ranks->first(function (Rank $rank) use ($student) {
                return $student->exp >= $rank->min_exp && $student->exp <= $rank->max_exp;
            });

            $student->setAttribute('dashboard_rank_name', $currentRank?->name ?? '-');
        });

        return view('lecturer.dashboard', compact(
            'rankStats',
            'streakStats',
            'topStudents',
            'hasStudentProgress',
            'totalStudents',
            'activeStudents'
        ));
    }

}
