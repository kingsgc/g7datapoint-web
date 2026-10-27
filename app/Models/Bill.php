<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;
    protected $table = 'bill';

    protected $fillable = [
    'username',
    'user_id',
    'disco_name',
    'meter_number',
    'meter_type',
    'amount',
    'charges',
    'newbal',
    'oldbal',
    'customer_name',
    'system',
    'plan_status',
    'plan_date',
    'token',
    'transid',
    'my_trans_id',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse'
];

}
