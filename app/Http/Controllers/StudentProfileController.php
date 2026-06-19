<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Challenge;
use App\Models\ChallengeResult;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentProfileController extends Controller
{
    protected const PRESET_AVATARS = [
        'profile_photos/default-3d.svg',
        'profile_photos/avatar-ai-core.svg',
        'profile_photos/avatar-terminal-nova.svg',
        'profile_photos/avatar-data-wave.svg',
        'profile_photos/avatar-cloud-link.svg',
        'profile_photos/avatar-code-spark.svg',
        'profile_photos/avatar-cyber-orbit.svg',
        'profile_photos/avatar-neon-pixel.svg',
        'profile_photos/avatar-server-guard.svg',
        'profile_photos/avatar-network-pulse.svg',
        'profile_photos/avatar-quantum-node.svg',
    ];

    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $student = Student::with(['user', 'ranks', 'currentSection', 'achievements'])
            ->where('user_id', $user->id)
            ->firstOrFail();
        $this->ensureProfilePhotoPubliclyAvailable($user);

        $currentChallenge = $student->current_challenge_id
            ? Challenge::with('section')->find($student->current_challenge_id)
            : null;
        $allAchievements = Achievement::all();
        $unlockedAchievementIds = $student->achievements->pluck('id')->toArray();
        $activeChallengeIds = Challenge::has('questions')->pluck('id');
        $completedChallengeIds = ChallengeResult::where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->whereIn('challenge_id', $activeChallengeIds)
            ->distinct()
            ->pluck('challenge_id');
        $completedChallengesCount = $completedChallengeIds->count();
        $totalChallengesCount = $activeChallengeIds->count();
        $orderedSections = Section::with('challenges')->orderBy('order')->get();
        $completedSectionsCount = $orderedSections->filter(function ($section) use ($completedChallengeIds) {
            return $section->challenges->isNotEmpty()
                && $section->challenges->every(fn($challenge) => $completedChallengeIds->contains($challenge->id));
        })->count();

        $unlockedSectionsCount = $orderedSections->isNotEmpty() ? 1 : 0;
        foreach ($orderedSections as $section) {
            if ($section->order === 1) {
                continue;
            }

            $previousSection = $orderedSections->firstWhere('order', $section->order - 1);
            if (! $previousSection || $previousSection->challenges->isEmpty()) {
                $unlockedSectionsCount++;
                continue;
            }

            $allPreviousCompleted = $previousSection->challenges->every(
                fn($challenge) => $completedChallengeIds->contains($challenge->id)
            );

            if ($allPreviousCompleted) {
                $unlockedSectionsCount++;
            } else {
                break;
            }
        }

        $leaderboard = Student::join('users', 'students.user_id', '=', 'users.id')
            ->where('students.weekly_score', '>', 0)
            ->orderByDesc('students.weekly_score')
            ->orderBy('users.name')
            ->select('students.user_id', 'students.weekly_score', 'users.name', 'users.profile_photo')
            ->limit(10)
            ->get();

        $weeklyRank = (int) $student->weekly_score > 0
            ? Student::where('weekly_score', '>', (int) $student->weekly_score)->count() + 1
            : null;

        $currentRank = $student->current_rank;
        $minExp = $currentRank?->min_exp ?? 0;
        $maxExp = $currentRank?->max_exp ?? 100;
        $expRange = max($maxExp - $minExp, 1);
        $expProgress = min(100, max(0, (($student->exp - $minExp) / $expRange) * 100));

        return view('student.profile.index', compact(
            'user',
            'student',
            'currentChallenge',
            'leaderboard',
            'weeklyRank',
            'currentRank',
            'expProgress',
            'allAchievements',
            'unlockedAchievementIds',
            'completedChallengesCount',
            'totalChallengesCount',
            'completedSectionsCount',
            'unlockedSectionsCount'
        ));
    }

    public function detail()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $student = $user->student;
        $this->ensureProfilePhotoPubliclyAvailable($user);

        return view('student.profile.detail', compact('user', 'student'));
    }

    public function edit()
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $student = $user->student;
        $this->ensureProfilePhotoPubliclyAvailable($user);
        $presetAvatars = self::PRESET_AVATARS;

        return view('student.profile.edit', compact('user', 'student', 'presetAvatars'));
    }

    public function update(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $student = $user->student;

        $validatedData = $request->validate([
            'address' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'gender' => 'nullable|string|in:Laki-laki,Perempuan',
            'phone_number' => 'nullable|string|max:20',
            'prodi' => 'nullable|string|in:Sistem Informasi Bisnis,Teknik Informatika',
            'semester' => 'nullable|integer|min:1|max:8',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'avatar_choice' => 'nullable|string|in:' . implode(',', self::PRESET_AVATARS),
        ]);

        $studentData = collect($validatedData)->except(['profile_photo', 'avatar_choice'])->all();
        $selectedAvatar = $validatedData['avatar_choice'] ?? null;

        if ($request->input('delete_photo') === '1') {
            $this->deleteCustomProfilePhoto($user->profile_photo);
            $user->profile_photo = 'profile_photos/default-3d.svg';
        } elseif ($request->hasFile('profile_photo')) {
            $this->deleteCustomProfilePhoto($user->profile_photo);

            $file = $request->file('profile_photo');
            $filename = Str::random(40) . '.' . $file->getClientOriginalExtension();
            $path = 'profile_photos/' . $filename;

            Storage::disk('public')->putFileAs('profile_photos', $file, $filename);

            $publicDirectory = public_path('storage/profile_photos');
            if (! is_dir($publicDirectory)) {
                mkdir($publicDirectory, 0777, true);
            }

            copy(storage_path('app/public/' . $path), $publicDirectory . DIRECTORY_SEPARATOR . $filename);
            $user->profile_photo = $path;
        } elseif ($selectedAvatar) {
            if ($user->profile_photo !== $selectedAvatar) {
                $this->deleteCustomProfilePhoto($user->profile_photo);
                $user->profile_photo = $selectedAvatar;
            }
        }

        $user->save();
        $student->update($studentData);

        return redirect()->route('student.profile.index')->with('success', 'Profil berhasil diperbarui.');
    }

    protected function deleteCustomProfilePhoto(?string $photoPath): void
    {
        if (blank($photoPath) || in_array($photoPath, self::PRESET_AVATARS, true)) {
            return;
        }

        Storage::disk('public')->delete($photoPath);

        $publicPhotoPath = public_path('storage/' . $photoPath);
        if (file_exists($publicPhotoPath)) {
            @unlink($publicPhotoPath);
        }
    }

    protected function ensureProfilePhotoPubliclyAvailable($user): void
    {
        $photoPath = $user->profile_photo;

        if (blank($photoPath) || in_array($photoPath, self::PRESET_AVATARS, true)) {
            return;
        }

        $storagePath = storage_path('app/public/' . $photoPath);
        $publicPath = public_path('storage/' . $photoPath);

        if (file_exists($publicPath) || ! file_exists($storagePath)) {
            return;
        }

        $directory = dirname($publicPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        copy($storagePath, $publicPath);
    }
}
