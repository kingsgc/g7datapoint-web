<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirusAPI extends Model
{
    use HasFactory;

    protected $table = 'virus_api';

    protected $fillable = [
        'virus1',
        'virus2',
        'virus3',
        'virus4',
        'virus5',
        'virus1_website',
        'virus2_website',
        'virus3_website',
        'virus4_website',
        'virus5_website',
    ];
}
