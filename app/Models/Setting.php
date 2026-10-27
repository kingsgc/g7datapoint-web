<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    protected $table = 'settings';

    protected $fillable = [
        'is_verify_email',
        'paystack',
        'bank_transfer',
        'monnify_atm',
        'monnify',
        'allow_pin',
        'wema',
        'rolex',
        'fed',
        'str',
        'earning',
        'referral',
        'notif_show',
        'notif_message',
        'is_feature',
        'referral_price',
        'referral_text',
        'monnify_charge',
        'awuf_price',
        'stock',
        'agent_price',
        'affliate_price',
        'bulk_sms',
        'bulk_length',
        'bulksms',
        'bill',
        'earning_min',
        'customer_amount',
        'charges',
        'withdraw',
        'allow_limit',
        'data_card',
        'recharge_card',
        'xixapay_charge_value',
        'xixapay_charge_type',
        'xixapay_charge_cap',
        'verify_login'
    ];
}
