<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if (auth('admin')->check()) {
            $user = auth('admin')->user();

            if ($user->type !== 'ADMIN' || $user->status != 1) {
                auth('admin')->logout();
                return redirect()->route('filament.admin.auth.login')
                    ->withErrors(['login' => 'Administrator privileges revoked']);
            }
        }

        return $next($request);
    }
}
