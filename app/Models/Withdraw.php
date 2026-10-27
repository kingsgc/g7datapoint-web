<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdraw extends Model
{
    use HasFactory;
    protected $table = 'withdraw';
     protected $fillable = [
        'user_id',
        'user_email',
        'bank_name',
        'accountName',
        'accountNumber',
        'amount',
        'charges',
        'narration',
        'plan_status',
        'plan_date',
        'newbal',
        'oldbal',
        'my_trans_id',
        'transid',
        'serverResponse'
    ];
}
