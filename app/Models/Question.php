<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'challenge_id',
        'type',
        'description',
        'question_text',
        'help_text',
        'explanation_text',
        'explanation_image',
        'question_image',
        'score',
        'exp',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class)->orderBy('id');
    }

    public function blocks()
    {
        return $this->hasMany(QuestionBlock::class)->orderBy('sort_order')->orderBy('id');
    }

    public function explanationImages()
    {
        return $this->hasMany(QuestionExplanationImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function explanationBlocks()
    {
        return $this->hasMany(QuestionExplanationBlock::class)->orderBy('sort_order')->orderBy('id');
    }
}
