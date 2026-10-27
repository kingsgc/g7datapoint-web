<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateByKey
{
    /**
     * Handle an incoming request using token (user ID or token key).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userToken = $request->id ?? $request->token;

        if (!$userToken) {
            return response()->json([
                'status' => 401,
                'message' => 'Missing access token.'
            ], 401);
        }

        $tokenService = new TokenService();
        $userId = $tokenService->verifyToken($userToken);

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 403,
                'message' => 'Access token expired or invalid.'
            ], 403);
        }

        switch ((int) $user->status) {
            case 1:
                Auth::login($user);
                return $next($request);

            case 2:
                return response()->json([
                    'status' => 403,
                    'message' => "{$user->username}, your account has been banned."
                ], 403);

            case 3:
                return response()->json([
                    'status' => 403,
                    'message' => "{$user->username}, your account has been deactivated."
                ], 403);

            case 0:
                return response()->json([
                    'status' => 'verify',
                    'message' => "{$user->username}, your account is not yet verified."
                ]);

            default:
                return response()->json([
                    'status' => 403,
                    'message' => 'System is unable to verify user status.'
                ], 403);
        }
    }
}
