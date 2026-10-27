<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RechargeCardSelection extends Model
{
    use HasFactory;
    protected $table = 'recharge_card_sel';

protected $fillable = [
    'mtn',
    'airtel',
    'glo',
    'mobile',
];

}
