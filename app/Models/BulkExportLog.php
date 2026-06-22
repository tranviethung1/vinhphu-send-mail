<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_bulk_export_id',
        'status',
        'email',
        'name',
        'message',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function salaryBulkExport()
    {
        return $this->belongsTo(SalaryBulkExport::class);
    }
}
