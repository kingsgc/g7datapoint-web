<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RechargeCardPlan extends Model
{
    use HasFactory;
    protected $table = 'recharge_card_plan';
    protected $fillable = [
        'network',
        'name',
        'load_pin',
        'plan_status',
        'smart',
        'awuf',
        'agent',
        'api',
        'special',
        'plan_id',
        'api_mappings',
        'check_balance',
        'free2'
    ];

    protected $casts = [
        'api_mappings' => 'array',
    ];
}
