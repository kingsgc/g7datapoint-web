<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletFunding extends Model
{
    use HasFactory;

    protected $table = 'wallet_funding';

    protected $fillable = [
        'username',
        'user_id',
        'mtn_cg_bal',
        'mtn_sme_bal',
        'mtn_g_bal',
        'airtel_cg_bal',
        'airtel_g_bal',
        'airtel_sme_bal',
        'glo_cg_bal',
        'glo_g_bal',
        'glo_sme_bal',
        'mobile_cg_bal',
        'mobile_g_bal',
        'mobile_sme_bal',
        'mtn_cg',
        'mtn_sme',
        'mtn_g',
        'airtel_cg',
        'airtel_sme',
        'airtel_g',
        'glo_cg',
        'glo_g',
        'glo_sme',
        'mobile_cg',
        'mobile_g',
        'mobile_sme',
    ];
}
