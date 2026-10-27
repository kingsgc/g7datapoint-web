<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cable extends Model
{
    use HasFactory;
    protected $table = 'cable';
    protected $fillable = [
    'username',
    'user_id',
    'amount',
    'charges',
    'cable_name',
    'cable_plan',
    'plan_status',
    'iuc',
    'plan_date',
    'transid',
    'my_trans_id',
    'customer_name',
    'system',
    'oldbal',
    'newbal',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerRespons',
    'quickly',
];

}
