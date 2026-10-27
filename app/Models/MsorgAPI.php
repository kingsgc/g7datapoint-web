<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MsorgAPI extends Model
{
    use HasFactory;
    protected $table = 'msorg_api';
    protected $fillable = [
        'msorg1',
        'msorg1_website',
        'msorg2',
        'msorg2_website',
        'msorg3',
        'msorg3_website',
        'msorg4',
        'msorg4_website',
        'msorg5',
        'msorg5_website',
    ];
}
