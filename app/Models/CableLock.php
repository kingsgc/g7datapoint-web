<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CableLock extends Model
{
    use HasFactory;
    protected $table = 'cable_result_lock';
    protected $fillable = [
        'waec',
        'neco',
        'nabteb',
        'dstv',
        'gotv',
        'startime',
    ];
}
