<?php

namespace App\Filament\Admin\Resources\TransactionHistoryResource\Widgets;

use App\Filament\Admin\Resources\TransactionHistoryResource\Pages\ListTransactionHistories;
use App\Models\History;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageTable;

class TransactionStatsWidget extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListTransactionHistories::class;
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Total Transactions', $this->getPageTableQuery()->count())
                ->icon('heroicon-o-currency-dollar')
                ->description('All-time transactions')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8])
                ->descriptionIcon('heroicon-m-arrow-trending-up'),

            Stat::make('Today\'s Volume', $this->getPageTableQuery()->whereDate('adex_date', today())->count())
                ->icon('heroicon-o-clock')
                ->description(now()->format('D, M j'))
                ->color('success')
                ->chart([3, 5, 2, 4, 7, 6, 8])
                ->descriptionIcon('heroicon-m-arrow-trending-up'),

            Stat::make('Processing Transactions', $this->getPageTableQuery()->where('plan_status', 0)->count())
                ->icon('heroicon-o-x-circle')
                ->description('Requires attention')
                ->color('danger')
                ->chart([1, 2, 1, 0, 3, 1, 2])
                ->descriptionIcon('heroicon-m-arrow-trending-down'),

            Stat::make('Avg. Amount', number_format($this->getPageTableQuery()->avg('amount') ?? 0, 2) . ' NGN')
                ->icon('heroicon-o-banknotes')
                ->description('Per transaction')
                ->color('info')
                ->chart([5, 3, 7, 4, 8, 6, 9])
        ];
    }

    protected function getColumns(): int
    {
        return 4; // Responsive grid
    }
}
