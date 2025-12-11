<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $table = 'questions';

    protected $fillable = [
        'bank_id',
        'type',
        'text',
        'media',
        'options',
        'answer_key',
        'difficulty',
        'tags',
        'explanation',
        'created_by',
        'is_active',
    ];

    // Deklarasikan casts SEKALI SAJA
    protected $casts = [
        'options'    => 'array',   // [{"id":"A","text":"..."}]
        'answer_key' => 'array',   // ["D"] atau array lain
        'tags'       => 'array',   // ["tpa","verbal","analogi"]
        'is_active'  => 'boolean',
    ];

    public function bank()
    {
        return $this->belongsTo(\App\Models\QuestionBank::class, 'bank_id');
    }
}