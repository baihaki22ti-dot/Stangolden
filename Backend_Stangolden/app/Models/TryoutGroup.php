<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TryoutGroup extends Model
{
    protected $table = 'tryout_groups';

    protected $fillable = [
        'domain',
        'name',
        'description',
        'is_active',
    ];

    public function series()
    {
        return $this->hasMany(TryoutSeries::class, 'group_id');
    }
}