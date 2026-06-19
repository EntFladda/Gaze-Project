<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rank;

class RankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ranks = [
            ['name' => 'Explorer', 'min_exp' => 0, 'max_exp' => 99],
            ['name' => 'Analyst', 'min_exp' => 100, 'max_exp' => 299],
            ['name' => 'Solver', 'min_exp' => 300, 'max_exp' => 599],
            ['name' => 'Builder', 'min_exp' => 600, 'max_exp' => 899],
            ['name' => 'Developer', 'min_exp' => 900, 'max_exp' => 1199],
            ['name' => 'Architect', 'min_exp' => 1200, 'max_exp' => 1499],
            ['name' => 'Strategist', 'min_exp' => 1500, 'max_exp' => 1799],
            ['name' => 'Innovator', 'min_exp' => 1800, 'max_exp' => 1999],
            ['name' => 'Specialist', 'min_exp' => 2000, 'max_exp' => 2099],
            ['name' => 'Expert', 'min_exp' => 2100, 'max_exp' => 999999],
        ];

        $desiredNames = collect($ranks)->pluck('name')->all();

        Rank::whereNotIn('name', $desiredNames)->delete();

        foreach ($ranks as $rank) {
            Rank::updateOrCreate(
                [
                    'name' => $rank['name'],
                ],
                [
                    'min_exp' => $rank['min_exp'],
                    'max_exp' => $rank['max_exp'],
                ]
            );
        }
    }
}
