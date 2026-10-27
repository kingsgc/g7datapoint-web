<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdexKey extends Model
{
    use HasFactory;
    protected $table = 'adex_key';

    protected $fillable = [
        'mon_app_key',
        'mon_sk_key',
        'mon_con_num',
        'mon_bvn',
        'default_limit',
        'min',
        'max',
        'psk',
        'plive',
        'bank_name',
        'account_number',
        'account_name',
        'billstack_key',
        'x_skey',
        'x_akey',
        'x_business_id',
        'withdrawal_charges'
    ];
}
