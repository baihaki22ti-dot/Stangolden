<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    protected $table = 'question_banks';

    protected $fillable = [
        'series_id',
        'category',
        'title',
        'description',
        'file_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function questions()
    {
        return $this->hasMany(\App\Models\Question::class, 'bank_id');
    }
}