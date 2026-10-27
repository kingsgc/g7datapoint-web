<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherApi extends Model
{
    use HasFactory;
    protected $table = 'other_api';

    /**
     * Kept in sync with the real other_api schema. The credential columns
     * are longtext because they hold upstream API keys/secrets of varying
     * length; they are never exposed to the browser, so they are not
     * declared hidden-by-default here — callers that serialise this model
     * should pick fields explicitly.
     */
    protected $fillable = [
        'simhosting',
        'simserver',
        'smeplug',
        'msplug',
        'ogdamns',
        'vtpass_username',
        'vtpass_password',
        'hollatag_username',
        'hollatag_password',
        'easy_access',
        'megasub',
        'megasubcloud',
        'quickly_email',
        'quickly_pass',
        'kuda_email',
        'kuda_apikey',
        'resopot_apikey',
        'aychindodata_key',
        'aychindodata_secret',
    ];
}
