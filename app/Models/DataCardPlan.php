<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCardPlan extends Model
{
    use HasFactory;
    protected $table = 'data_card_plan';

    protected $fillable = [
        'network',
        'load_pin',
        'name',
        'plan_type',
        'plan_status',
        'plan_day',
        'smart',
        'agent',
        'awuf',
        'special',
        'api',
        'plan_id',
        'api_mappings',
        'check_balance',
    ];

    protected $casts = [
        'api_mappings' => 'array',
    ];
}
