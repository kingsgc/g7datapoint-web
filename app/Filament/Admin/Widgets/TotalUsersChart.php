<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class TotalUsersChart extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Total Users';

    protected static ?int $sort = 3;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        // Get counts of users grouped by month for the current year
        $usersByMonth = User::selectRaw('MONTH(date) as month, COUNT(*) as count')
            ->whereYear('date', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Fill months with 0 where there is no data
        $data = collect(range(1, 12))->map(function ($month) use ($usersByMonth) {
            return $usersByMonth->get($month, 0);
        });

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data->toArray(),
                    'borderColor' => '#facc15',
                    'backgroundColor' => 'rgba(250, 204, 21, 0.2)',
                ],
            ],
            'labels' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec',
            ],
        ];
    }
}
