<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashDiscount extends Model
{
    use HasFactory;
    protected $table = 'cash_discount';
    protected $fillable = [
        'mtn',
        'airtel',
        'glo',
        'mobile',
        'mtn_number',
        'airtel_number',
        'glo_number',
        'mobile_number',
    ];
}
