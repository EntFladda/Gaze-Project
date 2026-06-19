<?php

namespace App\Models;

use App\Support\ComputationalThinking;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'section_id',
        'title',
        'description',
        'total_exp',
        'total_score',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    public function results()
    {
        return $this->hasMany(ChallengeResult::class);
    }
    public function recalculateTotals()
    {
        $this->total_score = $this->questions()->sum('score');
        $this->total_exp = $this->questions()->sum('exp');
        $this->save();
    }

    public function getCtCompetencyAttribute(): array
    {
        $sectionName = $this->relationLoaded('section') ? ($this->section?->name ?? '') : '';

        return ComputationalThinking::infer($sectionName . ' ' . $this->title);
    }
}
