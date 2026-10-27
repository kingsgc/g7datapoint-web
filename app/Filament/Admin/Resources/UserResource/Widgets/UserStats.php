<?php

namespace App\Filament\Admin\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends BaseWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->icon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Active Users', User::where('status', 1)->count())
                ->description('Status = Active')
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Wallet Balance', '₦' . number_format(User::sum('bal'), 2))
                ->description('Sum of all user balances')
                ->icon('heroicon-o-wallet')
                ->color('warning'),

            Stat::make('Total Earning Balance', '₦' . number_format(User::sum('refbal'), 2))
                ->description('Sum of all earning/referral balances')
                ->icon('heroicon-o-currency-dollar')
                ->color('info')
        ];
    }

    public static function canView(): bool
    {
        return true; // Ensure widget is always visible
    }
}
