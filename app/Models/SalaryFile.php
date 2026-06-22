<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'salary_sheet_file_name',
        'salary_sheet_path',
        'salary_sheet_size',
        'month',
        'template_selection_id',
        'facility_id',
        'google_sheet_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
    public function templateSelection()
    {
        return $this->belongsTo(TemplateSelection::class);
    }
}
