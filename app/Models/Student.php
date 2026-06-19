<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Achievement;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'nim',
        'address',
        'birth_date',
        'religion',
        'gender',
        'phone_number',
        'prodi',
        'semester',
        'class',
        'streak',
        'exp',
        'weekly_score',
        'total_score',
    ];

    protected $casts = [
        'streak' => 'integer',
        'exp' => 'integer',
        'weekly_score' => 'integer',
        'total_score' => 'integer',
        'semester' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ranks()
    {
        return $this->belongsToMany(Rank::class, 'student_rank', 'student_id', 'rank_id')->withTimestamps();
    }

    public function updateRank()
    {
        $exp = (int) ($this->exp ?? 0);

        if ($this->exp === null) {
            $this->forceFill(['exp' => $exp])->saveQuietly();
        }

        $newRank = Rank::where('min_exp', '<=', $exp)
            ->where('max_exp', '>=', $exp)
            ->orderBy('min_exp', 'desc')
            ->first();

        if (!$newRank) {
            return [
                'rank_changed' => false,
                'previous_rank_id' => null,
                'new_rank_id' => null
            ];
        }

        $latestRank = $this->ranks()->orderByDesc('ranks.min_exp')->first();
        $rankChanged = ! $latestRank || $latestRank->id !== $newRank->id;

        $this->ranks()->sync([
            $newRank->id => ['received_at' => $rankChanged ? now() : ($latestRank?->pivot?->received_at ?? now())],
        ]);

        $this->unsetRelation('ranks');

        return [
            'rank_changed' => $rankChanged,
            'previous_rank_id' => $latestRank?->id,
            'new_rank_id' => $newRank->id
        ];
    }

    public function getCurrentRankAttribute()
    {
        $exp = (int) ($this->exp ?? 0);

        return Rank::where('min_exp', '<=', $exp)
            ->where('max_exp', '>=', $exp)
            ->orderByDesc('min_exp')
            ->first();
    }

    public function currentSection()
    {
        return $this->belongsTo(Section::class, 'current_section_id');
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'student_achievement')
            ->withPivot('unlocked_at');
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'user_id', 'user_id');
    }

    public function challengeResults()
    {
        return $this->hasMany(ChallengeResult::class, 'user_id', 'user_id');
    }

    public function currentChallenge()
    {
        return $this->belongsTo(Challenge::class, 'current_challenge_id');
    }
}
