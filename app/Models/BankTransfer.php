<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    use HasFactory;
    protected $table = 'bank_transfer';
    protected $fillable = [
        'username',
        'user_id',
        'account_name',
        'account_number',
        'bank_name',
        'bank_code',
        'amount',
        'plan_status',
        'transid',
        'date',
    ];
}
