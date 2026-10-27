<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreDataCard extends Model
{
    use HasFactory;

    protected $table = 'store_data_card';

    protected $fillable = [
        'network',
        'name',
        'pin',
        'serial',
        'plan_status',
        'buyer_username',
        'added_date',
        'bought_date',
        'data_card_id',
    ];
}
