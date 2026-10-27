<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TokenService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckJwtTokenMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\JsonResponse|\Illuminate\Http\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedURLs = FrontendSecure::allowedOrigins();
        $token = new TokenService();
        if (in_array($request->headers->get('Origin'), $allowedURLs, true)) {
            $request->merge(['system' => 'WEBSITE']);
            $user = User::where(['id' => $token->verifyToken($request->token ?? $request->id), 'status' => 1])->first();
            if (!$user) {
                return response()->json(['message' => 'Kindly reload the user dashbaord, session  expired', 'status' => 'fail'], 401);
            }
            Auth::login($user);
            return $next($request);
        }
        // Check if the Authorization header exists
        $authorizationHeader = $request->header('Authorization');
        if ($authorizationHeader) {
            if ($authorizationHeader === FrontendSecure::sharedKey()) {
                $request->merge(['system' => 'APP', 'request-id' => (string) \Illuminate\Support\Str::uuid()]);
                 $userToken = $request->token ?? $request->app_key ?? $request->user_id ?? $request->id ?? null;
                $user = User::where(['id' => $token->verifyAppToken($userToken), 'status' => 1])->first();
                if (!$user) {
                    return response()->json(['message' => 'session  expired', 'status' => 'fail'], 401);
                }
                Auth::login($user);
                return $next($request);
            }
            $request->merge(['system' => 'API']);
            $token = str_replace('Token ', '', $authorizationHeader);
            $user = User::where(['apikey' => $token, 'status' => 1])->first();
            if (!$user) {
                return response()->json(['message' => 'Invalid API KEY or user account not active', 'status' => 'fail'], 403);
            }
            Auth::login($user);
            return $next($request);
        }
        return response()->json(['message' => 'Unauthorized/Token Not Found', 'status' => 'fail'], 403);
    }
}