<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryBulkExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_file_id',
        'template_selection_id',
        'file_path',
        'rows',
        'rows_count',
        'status',
    ];

    protected $casts = [
        'rows' => 'array',
    ];

    public function salaryFile()
    {
        return $this->belongsTo(SalaryFile::class);
    }

    public function templateSelection()
    {
        return $this->belongsTo(TemplateSelection::class);
    }

    public function logs()
    {
        return $this->hasMany(BulkExportLog::class);
    }
}

