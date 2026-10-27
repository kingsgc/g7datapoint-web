<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;
    protected $table = 'exam';
    protected $fillable = [
    'username',
    'user_id',
    'exam_name',
    'purchase_code',
    'oldbal',
    'newbal',
    'plan_status',
    'plan_date',
    'transid',
    'my_trans_id',
    'amount',
    'quantity',
    'vending_system',
    'vending_url',
    'request_time',
    'response_time',
    'ServerResponse',
    'system'
];

}
