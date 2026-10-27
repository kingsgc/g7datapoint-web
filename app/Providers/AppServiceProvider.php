<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use App\Models\General;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Vite;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }

        FilamentAsset::register([

            Js::make('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11'),
        ]);
        Response::macro('apiResponse', function ($e, $data = [], $message = '', $code = 500) {
            $exception = $e->getMessage() . ' in ' . $e->getFile() . ' at line ' . $e->getLine();
            \Log::error('Error API '  . $e->getMessage());
            $data['status'] = false;

            $data['message'] = $message == '' ? 'contact admin' : 'contact admin';

            return response()->json($data, $code);
        });

        if ($general = General::first()) {
            config([
                'app.name' => $general->app_name,
                'app.email' => $general->app_email,
                'app.app_logo' => $general->app_logo,
            ]);
        }
    }
}
