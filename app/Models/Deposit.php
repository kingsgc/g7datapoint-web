<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    use HasFactory;
    protected $table = 'deposit';
    protected $fillable = [
        'username',
        'user_id',
        'amount',
        'oldbal',
        'newbal',
        'wallet_type',
        'type',
        'credit_by',
        'date',
        'status',
        'transid',
        'charges',
        'monify_ref',
    ];
}
