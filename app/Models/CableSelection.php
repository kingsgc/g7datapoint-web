<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CableSelection extends Model
{
    use HasFactory;
    protected $table = 'cable_sel';

    protected $fillable = [
        'dstv',
        'gotv',
        'startime',
    ];
}
