<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryoutSession extends Model
{
    protected $table = 'tryout_sessions';

    protected $fillable = [
        'series_id',
        'key',
        'title',
        'order',
        'duration_minutes',
        'passing_score',
        'is_active',
    ];

    public function series()
    {
        return $this->belongsTo(TryoutSeries::class, 'series_id');
    }
}