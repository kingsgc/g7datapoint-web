<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DepositTransactionResource\Pages;
use App\Filament\Admin\Resources\DepositTransactionResource\RelationManagers;
use App\Models\Deposit;
use App\Models\DepositTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Admin\Resources\DepositTransactionResource\Widgets\DepositStat;

class DepositTransactionResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    protected static ?string $navigationGroup = 'Transaction Details';
    protected static ?string $navigationLabel = 'Deposit Transaction';
    protected static ?string $modelLabel = 'Deposit Transaction Record';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                   Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('d M Y, h:i A') 
                    ->sortable()
                    ->searchable(query: function ($query, $search) {
                  return $query->where('created_at', 'like', "%{$search}%")
                     ->orWhere('transid', 'like', "%{$search}%");
                    })
                    ->description(fn ($record) => $record->transid)
                    ->toggleable(isToggledHiddenByDefault: false),
                    Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable() 
                    ->copyable()
                    ->tooltip('Click to copy username'),
                Tables\Columns\TextColumn::make('monify_ref')
                    ->label('Payment Reference ID')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy Payment Reference ID'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Payment Type')
                   ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_by')
                    ->label('Credited By')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn($record) => $record->amount >= 0 ? 'success' : 'danger')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('charges')
                    ->label('Charges')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn($record) => $record->charges >= 0 ? 'success' : 'danger')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('oldbal')
                    ->label('Previous Balance')
                    ->money('NGN')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('newbal')
                    ->label('New Balance')
                    ->money('NGN')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('status')
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
            ])
            ->filters([
              Tables\Filters\Filter::make('quick_dates')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->options([
                                'today' => 'Today',
                                'yesterday' => 'Yesterday',
                                'week' => 'This Week',
                                'month' => 'This Month',
                                'year' => 'This Year',
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['period'] === 'today',
                                fn($q) => $q->whereDate('date', today())
                            )
                            ->when(
                                $data['period'] === 'yesterday',
                                fn($q) => $q->whereDate('date', today()->subDay())
                            )
                            ->when(
                                $data['period'] === 'week',
                                fn($q) => $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()])
                            )
                            ->when(
                                $data['period'] === 'month',
                                fn($q) => $q->whereMonth('date', now()->month)
                            )
                            ->when(
                                $data['period'] === 'year',
                                fn($q) => $q->whereYear('date', now()->year)
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!isset($data['period'])) return null;

                        $labels = [
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'week' => 'This Week',
                            'month' => 'This Month',
                            'year' => 'This Year',
                        ];

                        return 'Period: ' . ($labels[$data['period']] ?? 'Custom');
                    }),
                Tables\Filters\Filter::make('username_search')
                    ->form([Forms\Components\TextInput::make('username')->label('Search Username')])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['username'],
                            fn($q) => $q->where('username', 'like', "%{$data['username']}%")
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['username']) return null;
                        return 'Username contains: ' . $data['username'];
                    }),
                 SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        0 => 'Processing',
                        1 => 'Successful',
                        2 => 'Failed',
                    ])
                    ->multiple()
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])->persistFiltersInSession()
            ->striped()->extremePaginationLinks()->paginated([10, 25, 50, 100])
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepositTransactions::route('/'),
            'edit' => Pages\EditDepositTransaction::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
  public static function getWidgets(): array
    {
        return [
            DepositStat::class
        ];
    }
}
