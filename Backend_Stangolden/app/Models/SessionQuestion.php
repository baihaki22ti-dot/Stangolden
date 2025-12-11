<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionQuestion extends Model
{
    protected $fillable = ['session_id','question_id','order','points','overrides'];

    protected $casts = [
        'points' => 'decimal:2',
        'overrides' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(TryoutSession::class, 'session_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}