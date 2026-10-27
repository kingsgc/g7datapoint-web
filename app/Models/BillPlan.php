<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillPlan extends Model
{
    use HasFactory;
    protected $table = 'bill_plan';

    protected $fillable = [
        'plan_id',
        'vtpass',
        'disco_name',
        'plan_status',
        'logo',
        'commission',
        'api_mappings',
        'added_by',
        'quickly',
    ];
    protected $casts = [
        'api_mappings' => 'array',
    ];
}
