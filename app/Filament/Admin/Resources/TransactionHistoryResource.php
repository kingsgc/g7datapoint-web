<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransactionHistoryResource\Widgets\TransactionStatsWidget;
use App\Models\History;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use App\Filament\Admin\Resources\TransactionHistoryResource\Pages;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Collection;

class TransactionHistoryResource extends Resource
{
    protected static ?string $model = History::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Transaction Details';
    protected static ?string $navigationLabel = 'Transaction History';
    protected static ?string $modelLabel = 'Transaction Record';
    protected static ?int $navigationSort = 14;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Transaction Information')
                            ->schema([
                                TextInput::make('transid')
                                    ->label('Reference ID')
                                    ->disabled()
                                    ->columnSpanFull(),

                                TextInput::make('username')
                                    ->label('Username')
                                    ->disabled(),

                                TextInput::make('adex_date')
                                    ->label('Date/Time')
                                    ->disabled(),
                            ])
                            ->columnSpan(1),

                        Section::make('Financial Details')
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Amount (₦)')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('oldbal')
                                    ->label('Previous Balance')
                                    ->numeric()
                                    ->disabled(),

                                TextInput::make('newbal')
                                    ->label('New Balance')
                                    ->numeric()
                                    ->disabled(),
                            ])
                            ->columnSpan(1),
                    ]),
                Section::make('Status Management')
                    ->schema([
                        Select::make('plan_status')
                            ->label('Status')
                            ->options([
                                0 => 'Processing',
                                1 => 'Successful',
                                2 => 'Failed',
                            ])
                            ->required()
                            ->live(),

                        TextInput::make('role')
                            ->label('Transaction Type')
                            ->disabled(),
                        Textarea::make('message')
                            ->label('Description')->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
             Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->my_trans_id)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy username'),
                TextColumn::make('transid')
                    ->label('Client Reference ID')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy transaction ID'),

                TextColumn::make('message')
                    ->label('Description')
                    ->searchable()
                     ->wrap()
                     ->extraAttributes([
                        'class' => 'line-clamp-2',
                    ])
                    ->tooltip(fn(TextColumn $column) => $column->getState()),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn($record) => $record->amount >= 0 ? 'success' : 'danger')
                    ->alignEnd(),
                TextColumn::make('oldbal')
                    ->label('Previous Balance')
                    ->money('NGN')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('newbal')
                    ->label('New Balance')
                    ->money('NGN')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('plan_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ((int) $state) {
                        0 => 'Processing',
                        1 => 'Successful',
                        2 => 'Failed',
                        default => 'Unknown',
                    })
                    ->color(fn($state) => match ((int) $state) {
                        1 => 'success',
                        2 => 'danger',
                        0 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('role')
                    ->label('Transaction Type')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->color('info')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('username_filter')
                    ->form([TextInput::make('username')->label('Search Username')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['username'],
                            fn(Builder $query, $username): Builder =>
                            $query->where('username', 'like', "%{$username}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['username']) {
                            return null;
                        }
                        return 'Username: ' . $data['username'];
                    }),
                Filter::make('reference_filter')
                    ->form([TextInput::make('reference')->label('Search Reference ID')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['reference'],
                            fn(Builder $query, $reference): Builder =>
                            $query->where('transid', 'like', "%{$reference}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['reference']) {
                            return null;
                        }
                        return 'Reference ID: ' . $data['reference'];
                    }),
                SelectFilter::make('plan_status')
                    ->label('Transaction Status')
                    ->options([
                        0 => 'Processing',
                        1 => 'Successful',
                        2 => 'Failed',
                    ])
                    ->multiple(),
                SelectFilter::make('role')
                    ->label('Transaction Type')
                    ->options([
                        'credit' => 'Deposit',
                        'debit' => 'Withdrawal',
                        'transfer' => 'Transfer',
                        'upgrade' => 'Upgrade',
                        'data' => 'Data',
                        'airtime' => 'AIRTIME',
                        'sms' => 'BULK SMS',
                        'cable' => 'CABLE TV',
                        'bill' => 'ELECTRICITY BILL',
                    ])
                    ->multiple(),
                Filter::make('quick_dates')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->label('Time Period')
                            ->options([
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'year' => 'This Year',
                            ])
                            ->default('today')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['period'] === 'today',
                                fn($q) => $q->whereDate('adex_date', today())
                            )
                            ->when(
                                $data['period'] === 'yesterday',
                                fn($q) => $q->whereDate('adex_date', today()->subDay())
                            )
                            ->when(
                                $data['period'] === 'week',
                                fn($q) => $q->whereBetween('adex_date', [now()->startOfWeek(), now()->endOfWeek()])
                            )
                            ->when(
                                $data['period'] === 'month',
                                fn($q) => $q->whereMonth('adex_date', now()->month)
                            )
                            ->when(
                                $data['period'] === 'year',
                                fn($q) => $q->whereYear('adex_date', now()->year)
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!isset($data['period'])) {
                            return null;
                        }

                        $labels = [
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'week' => 'This Week',
                            'month' => 'This Month',
                            'year' => 'This Year',
                        ];

                        return 'Period: ' . ($labels[$data['period']] ?? 'Custom');
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
            ])->bulkActions([
                Tables\Actions\BulkAction::make('update_status')
                    ->icon('heroicon-o-check-circle')
                     ->authorize('update_transaction::history')
                    ->action(function (Collection $records, array $data): void {
                        $records->each->update(['plan_status' => $data['status']]);
                    })
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                0 => 'Processing',
                                1 => 'Successful',
                                2 => 'Failed'
                            ])
                            ->required()
                    ])
                    ->deselectRecordsAfterCompletion()
            ])
            ->persistFiltersInSession()
            ->striped()->extremePaginationLinks()->paginated([10, 25, 50, 100])
            ->deferLoading();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactionHistories::route('/'),
            'edit' => Pages\EditTransactionHistory::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function getWidgets(): array
    {
        return [
            TransactionStatsWidget::class
        ];
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::count();
    // }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
