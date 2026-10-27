<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockResultPin extends Model
{
    use HasFactory;

    protected $table = 'stock_result_pin';
    protected $fillable = [
        'exam_name',
        'exam_serial',
        'exam_pin',
        'plan_status',
        'added_by',
        'added_date',
        'bought_date',
        'buyer_username',
        'plan_id',
    ];
    protected $casts = [
        'added_date' => 'datetime',
        'bought_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
