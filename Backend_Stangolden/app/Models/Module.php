<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
    'name',
    'group',
    'sub_group',
    'description',
    'pdf_path',
    'pdf_original_name',
    'pdf_size',
];
}