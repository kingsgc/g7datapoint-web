<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    protected $table = 'user';

    // public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'ref',
        'status',
        'password',
        'bal',
        'refbal',
        'kyc',
        'date',
        'apikey',
        'autofund',
        'type',
        'pin',
        'profile_image',
        'sterlen',
        'fed',
        'wema',
        'rolex',
        'address',
        'about',
        'otp',
        'reason',
        'webhook',
        'user_limit',
        'adex_key',
        'app_key',
        'app_token',
        'vdf',
        'is_bvn_fail',
        'bvn',
        'monify_ref',
        'sb',
        'safe',
        'pro',
        'palmpay',
        'airtime_to_cash_limit',
        'withdrawal_limit'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'apikey',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($user) {
            if (!WalletFunding::where('user_id', $user->id)->exists()) {
                WalletFunding::create([
                    'user_id' => $user->id,
                    'username' => $user->username
                ]);
            }
        });
    }

     public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
