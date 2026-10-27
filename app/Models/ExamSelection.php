<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSelection extends Model
{
    use HasFactory;

    protected $table = 'exam_sel';

protected $fillable = [
    'waec',
    'neco',
    'nabteb',
];
}
