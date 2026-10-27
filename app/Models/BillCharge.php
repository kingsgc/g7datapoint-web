<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillCharge extends Model
{
    use HasFactory;
    protected $table = 'bill_charge';
    protected $fillable = [
        'bill_max',
        'bill_min',
        'bill',
        'direct',
    ];
}
