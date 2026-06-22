<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'file_name',
        'selections',
    ];

    protected $casts = [
        'selections' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}

