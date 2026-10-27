<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class StatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters, HasWidgetShield;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $filters = $this->filters ?? [];

        // Map filters to Carbon dates
        [$startDate, $endDate] = $this->resolveDateRange($filters);

        $query = User::query();

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $users = $query->get();

        // Prepare chart data
        if ($startDate && $endDate) {
            $diffInDays = $startDate->diffInDays($endDate);
            $format = $diffInDays > 60 ? 'M Y' : 'd M';

            $grouped = $users->groupBy(fn($user) => Carbon::parse($user->date)->format($format))->sortKeys();
            $chartData = $grouped->map->count()->values()->toArray();
        } else {
            $chartData = [];
        }

        return [
            Stat::make('All Users', $users->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart($chartData)
                ->color('success'),

            Stat::make('Total Smart User', $users->where('type', 'SMART')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),

            Stat::make('Total AWUF User', $users->where('type', 'AWUF')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),

            Stat::make('Total Agent User', $users->where('type', 'AGENT')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),

            Stat::make('Total API User', $users->where('type', 'API')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),

            Stat::make('Total SPECIAL User', $users->where('type', 'SPECIAL')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),

            Stat::make('Total ADMINS', $users->where('type', 'ADMIN')->count())
                ->description($startDate
                    ? "From {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
                    : 'All time')
                ->color('success'),
        ];
    }

    /**
     * Resolve the date range from filters.
     */
    protected function resolveDateRange(array $filters): array
    {
        $range = $filters['date_range'] ?? null;
        $start = null;
        $end = null;

        switch ($range) {
            case 'today':
                $start = now()->startOfDay();
                $end = now()->endOfDay();
                break;
            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'week':
                $start = now()->startOfWeek();
                $end = now()->endOfWeek();
                break;
            case 'month':
                $start = now()->startOfMonth();
                $end = now()->endOfMonth();
                break;
            case 'quarter':
                $start = now()->firstOfQuarter();
                $end = now()->lastOfQuarter();
                break;
            case 'year':
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                break;
            case 'custom':
                if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                    $start = Carbon::parse($filters['start_date'])->startOfDay();
                    $end = Carbon::parse($filters['end_date'])->endOfDay();
                }
                break;
        }

        return [$start, $end];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
