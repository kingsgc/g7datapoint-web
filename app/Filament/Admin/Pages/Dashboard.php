<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;
    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dashboard Filters')
                    ->description('Filter the dashboard data by date range')
                    ->collapsible()
                    ->compact()
                    ->schema([
                        Select::make('date_range')
                            ->label('Time Period')
                            ->options([
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'quarter' => 'This Quarter',
                                'year' => 'This Year',
                                'custom' => 'Custom Range...',
                            ])
                            ->default('today')
                            ->live()
                            ->native(false)
                            ->columnSpanFull(),
                        Fieldset::make('Custom Date Range')
                            ->hidden(fn(Get $get) => $get('date_range') !== 'custom')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        DatePicker::make('start_date')
                                            ->label('Start Date')
                                            ->required()
                                            ->maxDate(fn(Get $get) => $get('end_date') ?: now()),

                                        DatePicker::make('end_date')
                                            ->label('End Date')
                                            ->required()
                                            ->minDate(fn(Get $get) => $get('start_date'))
                                            ->maxDate(now()),
                                    ]),
                            ]),
                    ])
                    ->columns(1),
            ])
            ->statePath('filters');
    }
}
