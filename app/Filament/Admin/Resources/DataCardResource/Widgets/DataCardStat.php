<?php

namespace App\Filament\Admin\Resources\DataCardResource\Widgets;

use App\Filament\Admin\Resources\DataCardResource\Pages\ListDataCards;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class DataCardStat extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListDataCards::class;
    }
    protected function getStats(): array
    {
        //  $this->getPageTableQuery() = $this->getPageTableQuery();
        $successful =  $this->getPageTableQuery()->where('plan_status', 1)->count();
        $pending =  $this->getPageTableQuery()->where('plan_status', 0)->count();
        $failed =  $this->getPageTableQuery()->where('plan_status', 2)->count();
        $totalAmount =  $this->getPageTableQuery()->where('plan_status', 1)->sum('amount');

        return [
            Stat::make('Successful Transactions', $successful)
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description($successful ? number_format($successful) . ' completed' : 'No successful transactions')
                ->chart([3, 5, 2, 4, 7, 6, 8]),

            Stat::make('Pending Transactions', $pending)
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->description($pending ? number_format($pending) . ' awaiting processing' : 'No pending transactions')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]),

            Stat::make('Failed Transactions', $failed)
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->description($failed ? number_format($failed) . ' failed' : 'No failed transactions')
                ->chart([1, 2, 1, 0, 3, 1, 2]),

            Stat::make('Total Processed Value', '₦' . number_format($totalAmount, 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->description('All successful transactions')
                ->chart([5, 3, 7, 4, 8, 6, 9]),
        ];
    }
}
