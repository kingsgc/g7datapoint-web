<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultCharge extends Model
{
    use HasFactory;
    protected $table = 'result_charge';
    protected $fillable = [
        'neco',
        'nabteb',
        'waec',
    ];
}
