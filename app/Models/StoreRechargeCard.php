<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreRechargeCard extends Model
{
    use HasFactory;
    protected $table = 'store_recharge_card';
    protected $fillable = [
        'network',
        'name',
        'serial',
        'pin',
        'buyer_username',
        'added_date',
        'plan_status',
        'bought_date',
        'recharge_card_id',
    ];
}
