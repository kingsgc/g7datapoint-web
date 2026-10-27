<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataSelection extends Model
{
    use HasFactory;
    protected $table = 'data_sel';
    protected $fillable = [
        'mtn_sme',
        'glo_sme',
        'airtel_sme',
        'mobile_sme',
        'mtn_cg',
        'glo_cg',
        'airtel_cg',
        'mobile_cg',
        'mtn_g',
        'glo_g',
        'airtel_g',
        'mobile_g',
    ];
}
