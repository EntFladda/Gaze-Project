<?php

namespace Database\Seeders;

use App\Models\Rank;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('storage/app/seeds/students.csv');

        if (! File::exists($csvPath)) {
            $this->command?->warn("StudentSeeder dilewati: file students.csv tidak ditemukan di {$csvPath}");
            return;
        }

        $csv = Reader::createFromPath($csvPath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'profile_photo' => 'profile_photos/default-3d.svg',
                    'password' => Hash::make($row['nim']),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('student')) {
                $user->assignRole('student');
            }

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nim' => $row['nim'],
                    'address' => $row['address'],
                    'birth_date' => $row['birth_date'],
                    'religion' => $row['religion'],
                    'gender' => $row['gender'],
                    'phone_number' => $row['phone_number'],
                    'prodi' => $row['prodi'],
                    'semester' => $row['semester'],
                    'class' => $row['class'],
                    'exp' => 0,
                ]
            );

            $rank = Rank::where('min_exp', '<=', $student->exp)
                ->where('max_exp', '>=', $student->exp)
                ->first() ?? Rank::orderBy('min_exp')->first();

            if ($rank && ! $student->ranks()->where('rank_id', $rank->id)->exists()) {
                $student->ranks()->attach($rank->id, ['received_at' => now()]);
            }
        }
    }
}
