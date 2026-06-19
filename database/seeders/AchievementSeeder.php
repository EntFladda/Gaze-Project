<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $achievements = [
            [
                'code' => 'first_mission',
                'name' => 'Langkah Pertama',
                'description' => 'Menyelesaikan mission pertama.',
                'icon' => 'icons/first_challenge.png',
            ],
            [
                'code' => 'three_missions',
                'name' => 'Terus Bertumbuh',
                'description' => 'Menyelesaikan setidaknya 3 mission.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'five_missions',
                'name' => 'On a Roll',
                'description' => 'Menyelesaikan setidaknya 5 mission.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'ten_missions',
                'name' => 'Mission Marathon',
                'description' => 'Menyelesaikan setidaknya 10 mission.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'first_section',
                'name' => 'Penjelajah Level',
                'description' => 'Menuntaskan 1 section pembelajaran.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'two_sections',
                'name' => 'Cross Section',
                'description' => 'Menuntaskan 2 section pembelajaran.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'perfect_mission',
                'name' => 'Jawaban Tepat',
                'description' => 'Menyelesaikan 1 mission tanpa jawaban salah.',
                'icon' => 'icons/perfect_score.png',
            ],
            [
                'code' => 'high_achiever',
                'name' => 'High Achiever',
                'description' => 'Menyelesaikan mission dengan minimal 80% score.',
                'icon' => 'icons/perfect_score.png',
            ],
            [
                'code' => 'guided_success',
                'name' => 'Belajar dari Bantuan',
                'description' => 'Menggunakan bantuan lalu berhasil menjawab dengan benar.',
                'icon' => 'icons/first_challenge.png',
            ],
            [
                'code' => 'comeback_win',
                'name' => 'Comeback Win',
                'description' => 'Pernah salah, lalu tetap menyelesaikan mission dengan hasil benar dominan.',
                'icon' => 'icons/first_challenge.png',
            ],
            [
                'code' => 'first_essay',
                'name' => 'Essay Starter',
                'description' => 'Menjawab benar soal essay pertama.',
                'icon' => 'icons/first_challenge.png',
            ],
            [
                'code' => 'three_day_streak',
                'name' => 'Konsisten Belajar',
                'description' => 'Mencapai streak belajar 3 hari.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'seven_day_streak',
                'name' => 'Weekly Warrior',
                'description' => 'Mencapai streak belajar 7 hari.',
                'icon' => 'icons/five_challenges.png',
            ],
            [
                'code' => 'review_reader',
                'name' => 'Suka Merefleksi',
                'description' => 'Membuka halaman review pembahasan setelah challenge selesai.',
                'icon' => 'icons/perfect_score.png',
            ],
            [
                'code' => 'rank_up',
                'name' => 'Naik Peringkat',
                'description' => 'Mencapai rank baru pertama kali.',
                'icon' => 'icons/perfect_score.png',
            ],
        ];

        DB::table('achievements')
            ->whereIn('code', ['first_challenge', 'five_challenges', 'perfect_score'])
            ->delete();

        foreach ($achievements as $achievement) {
            DB::table('achievements')->updateOrInsert(
                ['code' => $achievement['code']],
                array_merge($achievement, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }
}
