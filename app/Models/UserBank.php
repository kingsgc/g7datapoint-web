<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBank extends Model
{
    use HasFactory;

    protected $table = 'user_bank';
    protected $fillable = [
        'username',
        'user_id',
        'bank_name',
        'bank',
        'account_number',
        'bank_code',
        'date',
    ];
}
