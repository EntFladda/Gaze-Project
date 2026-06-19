<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionExplanationBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'type',
        'content',
        'image_path',
        'sort_order',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
