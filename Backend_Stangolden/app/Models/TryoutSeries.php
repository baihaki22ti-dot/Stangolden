<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryoutSeries extends Model
{
    protected $table = 'tryout_series';

    protected $fillable = [
        'group_id',
        'number',
        'title',
        'description',
        'is_active',
        'open_at',
        'close_at',
    ];

    public function group()
    {
        return $this->belongsTo(TryoutGroup::class, 'group_id');
    }

    public function sessions()
    {
        return $this->hasMany(TryoutSession::class, 'series_id');
    }
}