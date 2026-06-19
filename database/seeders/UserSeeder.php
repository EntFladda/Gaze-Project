<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'profile_photo' => 'profile_photos/default-3d.svg',
                'email' => 'admin@ct.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
            [
                'name' => 'Lecturer One',
                'profile_photo' => 'profile_photos/default-3d.svg',
                'email' => 'lecturer1@ct.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'lecturer',
            ],
            [
                'name' => 'Lecturer Two',
                'profile_photo' => 'profile_photos/default-3d.svg',
                'email' => 'lecturer2@ct.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'lecturer',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                $data
            );

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}
