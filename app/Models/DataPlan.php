<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPlan extends Model
{
    use HasFactory;
    protected $table = 'data_plan';
    protected $fillable = [
        'network',
        'plan_type',
        'plan_name',
        'plan_size',
        'plan_day',
        'smart',
        'awuf',
        'agent',
        'api',
        'special',
        'msorg1',
        'msorg2',
        'msorg3',
        'msorg4',
        'msorg5',
        'virus1',
        'virus2',
        'virus3',
        'virus4',
        'virus5',
        'simserver',
        'simhosting',
        'smeplug',
        'msplug',
        'ogdamns',
        'plan_status',
        'megasub',
        'megasubcloud',
        'plan_id',
        'added_by',
        'easyaccess',
        'api_mappings',
        'quickly',
        'vtpass',
    ];

    protected $casts = [
        'api_mappings' => 'array',
    ];
}
