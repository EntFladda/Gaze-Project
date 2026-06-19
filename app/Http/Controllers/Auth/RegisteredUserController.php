<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'nim' => 'required|string|unique:students',
            'address' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'religion' => 'nullable|string|in:Islam,Protestan,Katolik,Hindu,Buddha,Konghucu,Lainnya',
            'gender' => 'nullable|string|in:Laki-laki,Perempuan',
            'phone_number' => 'nullable|string|max:15',
            'prodi' => 'nullable|string|in:Teknik Informatika,Sistem Informasi Bisnis',
            'semester' => 'nullable|string|in:1,2,3,4,5,6,7,8',
            'class' => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'profile_photo' => 'profile_photos/default-3d.svg',
            'password' => Hash::make($request->password),
        ]);

        Student::create([
            'user_id' => $user->id,
            'nim' => $request->input('nim'),
            'address' => $request->input('address'),
            'birth_date' => $request->input('birth_date'),
            'religion' => $request->input('religion'),
            'gender' => $request->input('gender'),
            'phone_number' => $request->input('phone_number'),
            'prodi' => $request->input('prodi'),
            'semester' => $request->input('semester'),
            'class' => $request->input('class'),
            'streak' => 0,
            'exp' => 0,
            'weekly_score' => 0,
            'total_score' => 0,
        ]);

        $user->assignRole('student');

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('redirect.after.login', absolute: false));
    }
}
