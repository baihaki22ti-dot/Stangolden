<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = ['attempt_id','session_question_id','answer','is_correct','points_awarded'];

    protected $casts = [
        'answer' => 'array',
        'is_correct' => 'boolean',
        'points_awarded' => 'decimal:2',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class, 'attempt_id');
    }

    public function sessionQuestion()
    {
        return $this->belongsTo(SessionQuestion::class, 'session_question_id');
    }
}