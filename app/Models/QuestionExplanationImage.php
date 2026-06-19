<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionExplanationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'image_path',
        'sort_order',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
