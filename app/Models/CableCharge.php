<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CableCharge extends Model
{
    use HasFactory;

    protected $table = 'cable_charge';

    protected $fillable = [
        'gotv',
        'startime',
        'dstv',
        'direct',
    ];
}
