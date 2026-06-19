<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = ['name','min_exp','max_exp'];
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_rank','rank_id','student_id')->withTimestamps();
    }
}
