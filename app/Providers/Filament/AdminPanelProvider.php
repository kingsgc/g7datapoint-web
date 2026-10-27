<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Auth\CustomLogin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Pages\Auth\Login;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Pages\SiteSettings;
use App\Http\Middleware\EnsureAdminStatus;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $general = \App\Models\General::first();
        return $panel
            ->id('admin')
            ->path(config('filament.path'))
            ->login(CustomLogin::class)
            ->brandName($general?->app_name ?? 'G7DataPoint')
            ->favicon(asset('storage/' . $general?->app_logo))
            ->authGuard('admin')
            ->navigationItems([
            NavigationItem::make('User Dashboard')
                ->url(url('/dashboard'))
                ->icon('heroicon-o-home')
                ->openUrlInNewTab(false),
            ])
            ->colors([
                'primary' => '#0bbf9e',
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                Dashboard::class,
                SiteSettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([])
            ->unsavedChangesAlerts()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                EnsureAdminStatus::class
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
