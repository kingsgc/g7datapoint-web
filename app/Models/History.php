<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    use HasFactory;
    protected $table = 'message';
    protected $fillable = [
        'username',
        'user_id',
        'amount',
        'message',
        'oldbal',
        'newbal',
        'adex_date',
        'plan_status',
        'transid',
        'role',
        'my_trans_id'
    ];
}
