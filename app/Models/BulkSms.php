<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkSms extends Model
{
    use HasFactory;
    protected $table = 'bulksms';
    protected $fillable = [
    'username',
    'user_id',
    'amount',
    'newbal',
    'oldbal',
    'plan_date',
    'transid',
    'my_trans_id',
    'plan_status',
    'correct_number',
    'wrong_number',
    'total_number',
    'total_correct_number',
    'total_wrong_number',
    'message',
    'sender_name',
    'numbers',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse',
    'system'
];
}
