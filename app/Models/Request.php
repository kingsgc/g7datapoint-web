<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    public $table = 'request';

    protected $fillable = [
        'user_id',
        'username',
        'message',
        'date',
        'transid',
        'status',
        'title',
    ];
}
