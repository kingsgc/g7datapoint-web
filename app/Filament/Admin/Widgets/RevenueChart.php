<?php

namespace App\Filament\Admin\Widgets;

use App\Models\History;
use Filament\Widgets\ChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
class RevenueChart extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Total Revenue';

    protected static ?int $sort = 4;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {

        $usersByMonth = History::selectRaw('MONTH(adex_date) as month, COUNT(*) as count')
            ->whereYear('adex_date', now()->year)
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
                    'label' => 'Total Revenue',
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
