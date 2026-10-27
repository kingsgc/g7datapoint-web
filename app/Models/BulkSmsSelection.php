<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkSmsSelection extends Model
{
    use HasFactory;
    protected $table = 'bulksms_sel';

protected $fillable = [
    'bulksms',
];

}
