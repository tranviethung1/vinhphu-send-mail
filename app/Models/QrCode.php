<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    protected $fillable = ['label', 'url', 'frame_type', 'bubble_text'];
}
