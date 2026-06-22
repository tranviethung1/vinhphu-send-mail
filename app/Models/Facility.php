<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefix',
        'address',
        'data_link',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function salaryFiles()
    {
        return $this->hasMany(SalaryFile::class);
    }

    public function mailLists()
    {
        return $this->hasMany(MailList::class);
    }

    public function defaultMailList()
    {
        return $this->hasOne(MailList::class)->whereRaw('is_default = true');
    }
}

