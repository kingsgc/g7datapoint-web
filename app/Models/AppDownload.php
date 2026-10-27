<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppDownload extends Model
{
    use HasFactory;
    protected $table = 'app_download';

    protected $fillable = [
        'app_name',
        'app_version',
        'app_link',
        'platform',
    ];
}
