<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cash extends Model
{
    use HasFactory;
    protected $table = 'cash';
    protected $fillable = [
    'username',
    'user_id',
    'amount',
    'amount_credit',
    'newbal',
    'oldbal',
    'transid',
    'my_trans_id',
    'network',
    'payment_type',
    'plan_status',
    'plan_date',
    'system',
    'sender_number',
    'receiver_number',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse',
];

}
