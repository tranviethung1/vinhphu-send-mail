<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'facility_id',
        'original_filename',
        'google_sheet_url',
        'headers',
        'rows',
        'is_default',
    ];

    protected $casts = [
        'headers' => 'array',
        'rows' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}

