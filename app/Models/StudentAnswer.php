<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $primaryKey = 'result_id';

    protected $fillable = [
        'user_id',
        'question_id',
        'challenge_id',
        'attempt_number',
        'answer_id',
        'selected_answer',
        'answer_text',
        'is_correct',
        'used_help',
        'help_requested_at',
        'wrong_attempts',
    ];

    protected $casts = [
        'used_help' => 'boolean',
        'help_requested_at' => 'datetime',
        'wrong_attempts' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
    public function selectedAnswer()
    {
        return $this->belongsTo(Answer::class, 'answer_id');
    }
}
