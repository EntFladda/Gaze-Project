<?php

namespace App\Models;

use App\Support\ComputationalThinking;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['order', 'name'];

    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    public function getCtCompetencyAttribute(): array
    {
        $challengeTitles = $this->relationLoaded('challenges')
            ? $this->challenges->pluck('title')->implode(' ')
            : '';

        return ComputationalThinking::infer($this->name . ' ' . $challengeTitles);
    }
}
