<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_achievement')->withPivot('unlocked_at');
    }
}
