<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class TotalUsersBalance extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $query =  User::query();

        return [
            Stat::make('Total Users Wallet Balance', '₦' . number_format($query->sum('bal'), 2))
                ->description(
                    'User Balance'
                )
                ->color('success'),
            Stat::make("Total User's Earning Balance", '₦' . number_format($query->sum('refbal'), 2))
                ->description(
                    'Earning Balance'
                )
                ->color('success'),
        ];
    }
    protected function getColumns(): int
    {
        return 2; // Ensures it splits into two equal columns
    }
}
