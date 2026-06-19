<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->query('perPage', 10);
        $roleFilter = $request->query('role', 'all');
        $userStats = [
            'total' => User::count(),
            'lecturers' => User::role('lecturer')->count(),
            'students' => User::role('student')->count(),
        ];
        $adminCount = User::role('admin')->count();

        $users = User::with(['roles', 'student'])
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->when(in_array($roleFilter, ['admin', 'lecturer', 'student'], true), function ($query) use ($roleFilter) {
                $query->where('roles.name', $roleFilter);
            })
            ->orderByRaw("
                CASE
                    WHEN roles.name = 'admin' THEN 1
                    WHEN roles.name = 'lecturer' THEN 2
                    WHEN roles.name = 'student' THEN 3
                    ELSE 4
                END
            ")
            ->select('users.*')
            ->paginate($perPage === 'all' ? User::count() : (int) $perPage)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'perPage', 'userStats', 'roleFilter', 'adminCount'));
    }

    public function show($id)
    {
        $user = User::with(['roles', 'student'])->findOrFail($id);
        $student = $user->hasRole('student') ? $user->student : null;

        return view('admin.users.show', compact('user', 'student'));
    }

    public function create()
    {
        $roles = Role::all();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'password' => 'required_unless:role,student|nullable|string|min:6|confirmed',
            'role' => 'required|in:admin,lecturer,student',
            'nim' => 'required_if:role,student|nullable|string|unique:students,nim',
            'address' => 'required_if:role,student|nullable|string|max:255',
            'birth_date' => 'required_if:role,student|nullable|date',
            'religion' => 'required_if:role,student|nullable|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'gender' => 'required_if:role,student|nullable|string|in:Laki-laki,Perempuan',
            'phone_number' => 'required_if:role,student|nullable|string|max:15',
            'prodi' => 'required_if:role,student|nullable|string|in:Sistem Informasi Bisnis,Teknik Informatika',
            'semester' => 'required_if:role,student|nullable|integer|min:1|max:8',
            'class' => 'required_if:role,student|nullable|string|max:50',
        ]);

        $profilePhotoPath = null;
        if ($request->hasFile('profile_photo')) {
            $profilePhotoPath = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user = User::create([
            'name' => trim($request->name),
            'email' => $request->email,
            'password' => Hash::make($request->filled('password') ? $request->password : $request->nim),
            'profile_photo' => $profilePhotoPath,
        ]);

        $user->assignRole($request->role);

        if ($request->role === 'student') {
            $student = Student::create([
                'user_id' => $user->id,
                'nim' => $request->nim,
                'address' => $request->address,
                'birth_date' => $request->birth_date,
                'religion' => $request->religion,
                'gender' => $request->gender,
                'phone_number' => $request->phone_number,
                'prodi' => $request->prodi,
                'semester' => $request->semester,
                'class' => $request->class,
                'streak' => 0,
                'exp' => 0,
                'weekly_score' => 0,
                'total_score' => 0,
            ]);

            $student->load('ranks');
            $student->updateRank();
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $user->loadMissing(['roles', 'student']);

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,lecturer,student',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $updateData = [];
        if ($request->filled('name')) {
            $updateData['name'] = trim($request->name);
        }
        if ($request->filled('email')) {
            $updateData['email'] = $request->email;
        }
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        if ($request->delete_photo === '1') {
            if ($user->profile_photo && $user->profile_photo !== 'profile_photos/default-3d.svg') {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->update(['profile_photo' => 'profile_photos/default-3d.svg']);
        } elseif ($request->hasFile('profile_photo')) {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo) && $user->profile_photo !== 'profile_photos/default-3d.svg') {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $photoPath = $request->file('profile_photo')->store('profile_photos', 'public');
            $user->update(['profile_photo' => $photoPath]);
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        $user->syncRoles([$request->role]);

        if ($request->role === 'student') {
            $studentData = $request->validate([
                'nim' => 'nullable|string|unique:students,nim,' . optional($user->student)->id,
                'birth_date' => 'nullable|date',
                'religion' => 'nullable|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
                'gender' => 'nullable|string|in:Laki-laki,Perempuan',
                'phone_number' => 'nullable|string|max:15',
                'address' => 'nullable|string|max:255',
                'prodi' => 'nullable|string|in:Sistem Informasi Bisnis,Teknik Informatika',
                'semester' => 'nullable|integer|min:1|max:8',
                'class' => 'nullable|string|max:10',
            ]);

            if ($user->student) {
                $user->student->update($studentData);
                $user->student->load('ranks');
                $user->student->updateRank();
            } else {
                $student = $user->student()->create($studentData);
                $student->load('ranks');
                $student->updateRank();
            }
        } elseif ($user->student) {
            $user->student->delete();
        }

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Akun admin utama tidak bisa dihapus karena hanya tersisa satu admin.');
        }

        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
