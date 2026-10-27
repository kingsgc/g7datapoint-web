<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airtime extends Model
{
    use HasFactory;
    protected $table = 'airtime';
    protected $fillable = [
        'username',
        'user_id',
        'network',
        'network_type',
        'amount',
        'oldbal',
        'newbal',
        'discount',
        'transid',
        'my_trans_id',
        'plan_date',
        'plan_status',
        'plan_phone',
        'system',
        'vending_system',
        'vending_url',
        'request_time',
        'response_time',
        'created_at',
        'updated_at',
    ];
}
