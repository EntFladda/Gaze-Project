<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use App\Models\ChallengeResult;
use App\Models\StudentAnswer;
use App\Models\Challenge;
use App\Support\ClassroomInsights;
use App\Support\QuestionQualityAuditor;

class LecturerStudentController extends Controller
{
    public function index(Request $request)
    {
        $selectedClass = $request->query('class');
        $selectedStudentId = $request->query('student');

        $classes = Student::query()
            ->whereNotNull('class')
            ->where('class', '<>', '')
            ->distinct()
            ->orderBy('class')
            ->pluck('class');

        $baseQuery = Student::with(['user', 'ranks', 'currentChallenge', 'challengeResults.challenge'])
            ->when($selectedClass, fn ($query) => $query->where('class', $selectedClass));

        $totalActiveMissions = Challenge::has('questions')->count();

        $studentIds = (clone $baseQuery)->pluck('user_id');

        $monitoringStats = [
            'total_students' => (clone $baseQuery)->count(),
            'average_score' => round((float) (clone $baseQuery)->avg('total_score'), 1),
            'completed_missions' => ChallengeResult::query()
                ->whereIn('user_id', $studentIds)
                ->whereNotNull('ended_at')
                ->count(),
            'average_exp' => round((float) (clone $baseQuery)->avg('exp')),
            'total_active_missions' => $totalActiveMissions,
        ];

        /** @var LengthAwarePaginator $students */
        $students = (clone $baseQuery)
            ->orderByDesc('total_score')
            ->paginate(10)
            ->withQueryString();

        $students->getCollection()->transform(function (Student $student) use ($totalActiveMissions) {
            $results = $student->challengeResults;
            $answered = $results->sum(fn (ChallengeResult $result) => $result->correct_answers + $result->wrong_answers);
            $correct = $results->sum('correct_answers');
            $wrong = $results->sum('wrong_answers');
            $completed = $results->whereNotNull('ended_at')->pluck('challenge_id')->unique()->count();
            $attempts = $results->count();
            $accuracy = $answered > 0 ? round(($correct / $answered) * 100) : 0;
            $progress = $totalActiveMissions > 0 ? round(($completed / $totalActiveMissions) * 100) : 0;

            $student->setAttribute('monitoring', (object) [
                'answered' => $answered,
                'correct' => $correct,
                'wrong' => $wrong,
                'completed_missions' => $completed,
                'attempts' => $attempts,
                'accuracy' => $accuracy,
                'progress' => $progress,
                'last_mission' => $results->sortByDesc('updated_at')->first()?->challenge?->title
                    ?? $student->currentChallenge?->title
                    ?? '-',
                'history' => $results->pluck('challenge.title')->filter()->unique()->take(4)->join(', '),
            ]);

            return $student;
        });

        $selectedStudent = $students->getCollection()->firstWhere('id', (int) $selectedStudentId)
            ?? $students->getCollection()->first();

        $classInsights = ClassroomInsights::build($selectedClass);
        $qualitySummary = QuestionQualityAuditor::summary(Challenge::withCount('questions')->get());

        return view('lecturer.students.index', compact(
            'students',
            'classes',
            'selectedClass',
            'selectedStudent',
            'monitoringStats',
            'classInsights',
            'qualitySummary'
        ));
    }

    public function create()
    {
        return view('lecturer.students.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nim' => 'required|string|unique:students,nim',
            'exp' => 'required|integer|min:0',
            'prodi' => 'nullable|string|max:50',
            'semester' => 'nullable|integer|min:1|max:14',
            'class' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Nama mahasiswa wajib diisi.',
            'email.required' => 'Email mahasiswa wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email sudah digunakan.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah digunakan.',
            'exp.required' => 'EXP wajib diisi.',
            'exp.integer' => 'EXP harus berupa angka.',
        ]);

        $user = User::create([
            'name' => trim($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->nim)
        ]);
        $user->assignRole('student');

        $student = Student::create([
            'user_id' => $user->id,
            'nim' => $request->nim,
            'exp' => $request->exp,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'class' => $request->class,
        ]);
        $student->load('ranks');
        $student->updateRank();

        return redirect()->route('lecturer.students.index')->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    public function show(Student $student)
    {
        $student->load(['user', 'ranks', 'currentChallenge', 'currentSection']);

        $results = ChallengeResult::where('user_id', $student->user_id)
            ->with('challenge')
            ->orderBy('attempt_number')
            ->get();

        return view('lecturer.students.show', compact('student', 'results'));
    }

    public function detailResult(Student $student, Challenge $challenge, $attempt)
    {
        $answers = StudentAnswer::with(['question', 'selectedAnswer'])
            ->where('user_id', $student->user_id)
            ->where('challenge_id', $challenge->id)
            ->where('attempt_number', $attempt)
            ->get()
            ->groupBy('question_id');

        $result = ChallengeResult::where('user_id', $student->user_id)
            ->where('challenge_id', $challenge->id)
            ->where('attempt_number', $attempt)
            ->first();

        return view('lecturer.students.detail_result', compact('student', 'challenge', 'attempt', 'result', 'answers'));
    }
    public function edit(Student $student)
    {
        return view('lecturer.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'nim' => 'required|string|unique:students,nim,' . $student->id,
            'exp' => 'required|integer|min:0',
            'prodi' => 'nullable|string|max:50',
            'semester' => 'nullable|integer|min:1|max:14',
            'class' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Nama mahasiswa wajib diisi.',
            'email.required' => 'Email mahasiswa wajib diisi.',
            'email.email' => 'Format email belum benar.',
            'email.unique' => 'Email sudah digunakan.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah digunakan.',
            'exp.required' => 'EXP wajib diisi.',
            'exp.integer' => 'EXP harus berupa angka.',
        ]);

        $student->user->update([
            'name' => trim($request->name),
            'email' => $request->email,
        ]);

        $student->update([
            'nim' => $request->nim,
            'exp' => $request->exp,
            'prodi' => $request->prodi,
            'semester' => $request->semester,
            'class' => $request->class,
        ]);
        $student->load('ranks');
        $student->updateRank();

        return redirect()->route('lecturer.students.index')->with('success', 'Mahasiswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->user->delete();
        $student->delete();

        return redirect()->route('lecturer.students.index')->with('success', 'Mahasiswa berhasil dihapus.');
    }
}
