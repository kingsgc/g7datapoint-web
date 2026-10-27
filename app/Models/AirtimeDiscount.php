<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirtimeDiscount extends Model
{
    use HasFactory;
    protected $table = 'airtime_discount';
    protected $fillable = [
        'mtn_vtu_smart',
        'mtn_vtu_awuf',
        'mtn_vtu_agent',
        'mtn_vtu_special',
        'mtn_vtu_api',
        'airtel_vtu_smart',
        'airtel_vtu_awuf',
        'airtel_vtu_agent',
        'airtel_vtu_special',
        'airtel_vtu_api',
        'glo_vtu_smart',
        'glo_vtu_awuf',
        'glo_vtu_agent',
        'glo_vtu_special',
        'glo_vtu_api',
        'mobile_vtu_smart',
        'mobile_vtu_awuf',
        'mobile_vtu_agent',
        'mobile_vtu_special',
        'mobile_vtu_api',
        'mtn_share_smart',
        'mtn_share_awuf',
        'mtn_share_agent',
        'mtn_share_special',
        'mtn_share_api',
        'airtel_share_smart',
        'airtel_share_awuf',
        'airtel_share_agent',
        'airtel_share_special',
        'airtel_share_api',
        'glo_share_smart',
        'glo_share_awuf',
        'glo_share_agent',
        'glo_share_special',
        'glo_share_api',
        'mobile_share_smart',
        'mobile_share_awuf',
        'mobile_share_agent',
        'mobile_share_special',
        'mobile_share_api',
        'max_airtime',
        'min_airtime',
    ];
}
