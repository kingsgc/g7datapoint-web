<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DumpDataCardPin extends Model
{
    use HasFactory;
    protected $table = 'dump_data_card_pin';

    protected $fillable = [
    'username',
    'user_id',
    'serial',
    'pin',
    'network',
    'date',
    'transid',
    'my_trans_id',
];

}
