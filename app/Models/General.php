<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class General extends Model
{
    use HasFactory;

    protected $table = 'general';

    protected $fillable = [
        'app_name',
        'app_logo',
        'app_image',
        'app_phone',
        'app_email',
        'app_address',
        'facebook',
        'instagram',
        'tiktok',
        'app_group',
        'app_whatsapp',
    ];
}
