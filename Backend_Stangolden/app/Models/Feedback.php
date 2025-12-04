<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback'; // jika migration membuat tabel 'feedback' (bukan 'feedbacks')

    protected $fillable = [
        'user_id',
        'category',
        'title',
        'message',
        'priority',
        'resolved',
        'attachment_path',
        'attachment_name',
    ];

    protected $casts = [
        'resolved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}