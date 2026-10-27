<?php

use App\Models\DataPlan;
use App\Models\General;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Jenssegers\Agent\Facades\Agent;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// routes/web.php (temporary)
Route::get('/', function () {
    // Marketing landing page (blade). The React SPA serves its own routes
    // (/auth/login, /dashboard, ...) via the fallback below, so the landing
    // stays publicly reachable without being bounced to the login screen.
    $general = General::first();
    $mtn = DataPlan::where(['network' => 'MTN', 'plan_status' => 1])->orderBy('id', 'asc')->get();
    $glo = DataPlan::where(['network' => 'GLO', 'plan_status' => 1])->orderBy('id', 'asc')->get();
    $mobile = DataPlan::where(['network' => '9MOBILE', 'plan_status' => 1])->orderBy('id', 'asc')->get();
    $airtel = DataPlan::where(['network' => 'AIRTEL', 'plan_status' => 1])->orderBy('id', 'asc')->get();
    return view('index', [
        'general' => $general,
        'mtn' => $mtn,
        'airtel' => $airtel,
        'glo' => $glo,
        'mobile' => $mobile
    ]);
});
Route::get('install', function () {
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    $isAndroid = strpos($userAgent, 'Android') !== false;

    $get_started_url =  'https://mobilesub.com.ng/auth/login';

    if ($isAndroid) {
        $get_started_url = 'https://play.google.com/store/apps/details?id=com.mobilesub_adex';
    } elseif (Agent::isiOS()) {
        $get_started_url = 'https://mobilesub.com.ng/auth/register';
    }
    return redirect($get_started_url);
});
Route::get('/cache', function () {
    return
        Cache::flush();
});

/**
 * Serve the built React SPA for all non-API, non-admin paths.
 *
 * The SPA is served from the Vite build output (public/build) rather than
 * the raw public/index.html + public/static/* files. That keeps the fallback
 * in sync with the assets Vite actually produced, so in-app routes resolve
 * against hashed chunks instead of stale static files.
 *
 * Registered as a fallback so real routes (/privacy, api/*, the filament admin,
 * etc.) keep priority over the SPA router.
 */
Route::fallback(function () {
    $buildIndex = public_path('build/index.html');
    $fallbackIndex = public_path('index.html');

    if (File::exists($buildIndex)) {
        return response()->file($buildIndex, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    if (File::exists($fallbackIndex)) {
        return response()->file($fallbackIndex, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return response()->view('errors.404', [], 404);
});

Route::get('/privacy', [PageController::class, 'Policy']);
