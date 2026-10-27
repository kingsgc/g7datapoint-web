<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCard extends Model
{
    use HasFactory;
    protected $table = 'data_card';

    protected $fillable = [
    'username',
    'user_id',
    'network',
    'plan_name',
    'plan_type',
    'amount',
    'plan_date',
    'transid',
    'my_trans_id',
    'oldbal',
    'newbal',
    'plan_status',
    'system',
    'quantity',
    'card_name',
    'load_pin',
    'check_balance',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse',
];
}
