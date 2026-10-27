<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FrontendSecure
{
    /**
     * Origins allowed to call the API, derived from configuration instead of
     * a hardcoded list so the app works on whichever domain it is deployed to.
     *
     * Built from, in order:
     *   1. APP_URL           (config('app.url'))
     *   2. FRONT_END_URL     the public SPA / marketing host
     *   3. ALLOWED_ORIGINS   comma-separated extras for additional hosts
     *
     * Each value is reduced to scheme://host[:port] because that is the shape
     * of a browser's Origin header; paths and query strings are discarded.
     *
     * Values are read from config() rather than env() on purpose: Laravel
     * skips loading .env once the config is cached, so env() calls outside
     * config/*.php silently return null on a host running config:cache.
     */
    public static function allowedOrigins(): array
    {
        $candidates = array_merge(
            [config('app.url'), config('app.front_end_url')],
            explode(',', (string) config('app.allowed_origins', ''))
        );

        $origins = [];

        foreach ($candidates as $candidate) {
            $origin = self::normalizeOrigin((string) $candidate);

            if ($origin !== null) {
                $origins[] = $origin;
            }
        }

        return array_values(array_unique($origins));
    }

    /**
     * Reduce a URL to its origin, or null when it carries no host.
     */
    private static function normalizeOrigin(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return null;
        }

        $origin = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];

        if (! empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }

    public function handle($request, Closure $next)
    {
        // Local development (APP_ENV=local): allow any origin/port so the SPA works
        // with artisan serve regardless of which port it binds.
        if (app()->environment('local')) {
            return $next($request);
        }

        $appKey = $request->header('Authorization') ?: $request->query('app_key');

        // Check if the current request URL matches any of the allowed origins
        if (in_array($request->headers->get('Origin'), self::allowedOrigins(), true)) {
            return $next($request);
        }
         if($appKey == self::sharedKey()){
             return $next($request);
        }
        return response(['status' => 'fail', 'message' => 'Unauthorized. Access denied.'], 403);
    }

    /**
     * Shared key for non-browser clients (mobile app, integrations). Defaults
     * to the historical constant so existing clients keep working; set
     * FRONTEND_SECURE_KEY in .env to rotate it without touching code.
     */
    public static function sharedKey(): string
    {
        return (string) (config('app.frontend_secure_key') ?: self::myKeySecure);
    }

    public const myKeySecure = "dkfkfkfkfkfkfls55555vhgfgytrty5762ytfchcjcvcbjchgcycuydtdbjdhgbhgghgh878889yuyuyuuyyuiiuiuopopopoklkhjkhhjhghghbnbnjkjjkjkpoopuiuuipo";
}