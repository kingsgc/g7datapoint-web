<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Models\WalletFunding;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class WalletBalance extends BaseWidget
{
    use InteractsWithPageFilters, HasWidgetShield;

    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $query = WalletFunding::query();

        return [
            Stat::make('Total MTN SME Balance', '₦' . number_format($query->sum('mtn_sme_bal'), 2))
                ->description('MTN SME Balance'),
            Stat::make('Total AIRTEL SME Balance', '₦' . number_format($query->sum('airtel_sme_bal'), 2))
                ->description('AIRTEL SME Balance'),
            Stat::make('Total GLO SME Balance',  '₦' . number_format($query->sum('glo_sme_bal'), 2))
                ->description('GLO SME Balance'),
            Stat::make('Total 9MOBILE SME Balance',  '₦' . number_format($query->sum('mobile_sme_bal'), 2))
                ->description("9MOBILE SME Balance"),
            /// cg
            Stat::make('Total MTN CG Balance', '₦' . number_format($query->sum('mtn_cg_bal'), 2))
                ->description('MTN CG Balance'),
            Stat::make('Total AIRTEL CG Balance', '₦' . number_format($query->sum('airtel_cg_bal'), 2))
                ->description('AIRTEL CG Balance'),
            Stat::make('Total GLO CG Balance',  '₦' . number_format($query->sum('glo_cg_bal'), 2))
                ->description('GLO CG Balance'),
            Stat::make('Total 9MOBILE CG Balance',  '₦' . number_format($query->sum('mobile_cg_bal'), 2))
                ->description("9MOBILE CG Balance"),
            // gifting
            Stat::make('Total MTN GIFTING Balance', '₦' . number_format($query->sum('mtn_g_bal'), 2))
                ->description('MTN GIFTING Balance'),
            Stat::make('Total AIRTEL GIFTING Balance', '₦' . number_format($query->sum('airtel_g_bal'), 2))
                ->description('AIRTEL GIFITNG Balance'),
            Stat::make('Total GLO GIFTING Balance',  '₦' . number_format($query->sum('glo_g_bal'), 2))
                ->description('GLO GIFTING Balance'),
            Stat::make('Total 9MOBILE GIFTING Balance',  '₦' . number_format($query->sum('mobile_g_bal'), 2))
                ->description("9MOBILE GIFTING Balance"),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
