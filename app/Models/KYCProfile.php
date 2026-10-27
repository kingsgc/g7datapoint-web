<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KYCProfile extends Model{
    use HasFactory;
    protected $table = 'kyc_profile';
   protected $fillable = [
    'user_id',
    'user_email',
    'profile_image',
    'request_type',
    'number',
    'phone_number',
    'dob',
    'gender',
    'state_of_residence',
    'nationality',
    'first_name',
    'last_name',
    'middle_name',
    'ServerResponse',
];

}
