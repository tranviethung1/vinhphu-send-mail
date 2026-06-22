<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'key',
        'section',
        'code',
        'label',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Quan hệ với template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }
}
