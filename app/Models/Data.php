<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Data extends Model
{
    use HasFactory;
    protected $table = 'data';
    protected $fillable = [
        'network',
        'username',
        'user_id',
        'network_type',
        'plan_name',
        'amount',
        'plan_status',
        'transid',
        'plan_phone',
        'plan_date',
        'oldbal',
        'newbal',
        'system',
        'wallet',
        'api_response',
        'my_trans_id',
        'vending_system',
        'vending_url',
        'request_time',
        'response_time',
        'ServerResponse'
    ];

    //   protected $casts = [
    //     'ServerResponse' => 'json',
    // ];
}
