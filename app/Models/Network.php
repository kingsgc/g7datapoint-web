<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    use HasFactory;
    protected $table = 'network';
    protected $fillable = [
        'network',
        'network_logo',
        'network_vtu',
        'network_share',
        'network_sme',
        'network_cg',
        'network_g',
        'data_card',
        'recharge_card',
        'plan_id',
        'adex_id',
        'msorg_id',
        'virus_id',
        'cash',
        'xixapay_id',
    ];
}
