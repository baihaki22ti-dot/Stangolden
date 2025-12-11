<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryoutAttempt extends Model
{
    protected $table = 'tryout_attempts';

    protected $fillable = [
        'user_id',
        'bank_id',
        'category',
        'total_questions',
        'duration_seconds',
        'started_at',
        'deadline_at',
        'finished_at',
        'auto_submit',
        'correct_count',
        'wrong_count',
        'unanswered_count',
        'score',
        'raw_answers',
    ];

    protected $casts = [
        'auto_submit' => 'boolean',
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'finished_at' => 'datetime',
        'raw_answers' => 'array',
        'score' => 'decimal:2',
    ];

    public function bank()
    {
        return $this->belongsTo(\App\Models\QuestionBank::class, 'bank_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}