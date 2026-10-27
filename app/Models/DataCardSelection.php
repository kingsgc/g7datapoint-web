<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataCardSelection extends Model
{
    use HasFactory;
    protected $table = 'data_card_sel';

    protected $fillable = [
    'mtn',
    'airtel',
    'glo',
    'mobile',
];

}
