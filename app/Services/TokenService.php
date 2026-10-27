<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TokenService
{
    /**
     * Generate and assign a secure ADEX token to the user.
     */
    public function generateToken(int $userId): ?string
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        $secureKey = Str::upper(Str::random(40));
        $user->adex_key = $secureKey;
        $user->save();
        return $secureKey;
    }

    /**
     * Generate and assign a secure app token to the user.
     */
    public function generateAppToken(int $userId): ?string
    {
        $user = User::find($userId);
        if (!$user) {
            return null;
        }
        $secureKey = strtoupper(bin2hex(random_bytes(20)));
        $user->app_key = $secureKey;
        $user->save();
        return $secureKey;
    }

    /**
     * Verify an app token and return the user ID if valid.
     */
    public function verifyAppToken(string $appKey): ?int
    {
        $user = User::where('app_key', $appKey)->first();
        return $user?->id ?? null;
    }

    /**
     * Verify an ADEX token and return the user ID if valid.
     */
    public function verifyToken(string $adexKey): ?int
    {
        $user = User::where('adex_key', $adexKey)->first();
        return $user?->id ?? null;
    }
}
