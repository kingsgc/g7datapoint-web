<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CablePlan extends Model
{
    use HasFactory;
    protected $table = 'cable_plan';
    protected $fillable = [
        'plan_id',
        'plan_name',
        'plan_price',
        'cable_name',
        'vtpass',
        'plan_status',
        'added_by',
        'api_mappings',
        'xixapay',
    ];

    protected $casts = [
        'api_mappings' => 'array',
    ];
}
