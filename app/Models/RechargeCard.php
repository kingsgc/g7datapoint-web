<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RechargeCard extends Model
{
    use HasFactory;
    protected $table = 'recharge_card';

    protected $fillable = [
    'username',
    'user_id',
    'network',
    'plan_name',
    'plan_status',
    'amount',
    'plan_date',
    'transid',
    'my_trans_id',
    'system',
    'quantity',
    'card_name',
    'oldbal',
    'newbal',
    'load_pin',
    'check_balance',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse',
];

}
