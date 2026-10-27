<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeSelection extends Model
{
    use HasFactory;
    protected $table = 'airtime_sel';
    protected $fillable = [
        'mtn_vtu',
        'glo_vtu',
        'airtel_vtu',
        'mobile_vtu',
        'mtn_share',
        'glo_share',
        'airtel_share',
        'mobile_share',
    ];
}
