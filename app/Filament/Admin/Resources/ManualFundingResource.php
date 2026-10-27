<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ManualFundingResource\Pages;
use App\Filament\Admin\Resources\ManualFundingResource\RelationManagers;
use App\Models\BankTransfer;
use App\Models\ManualFunding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Admin\Resources\ManualFundingResource\Widgets\ManualStat;

class ManualFundingResource extends Resource
{
    protected static ?string $model = BankTransfer::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';
    protected static ?string $navigationGroup = 'Transaction Details';
    protected static ?string $navigationLabel = 'Manual Funding Transaction';
    protected static ?string $modelLabel = 'Manual Funding Transaction Record';
    protected static ?int $navigationSort = 23;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
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
                    ->description(fn($record) => $record->transid)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy username'),
                Tables\Columns\TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Account Number')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank Name')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('bank_code')
                    ->label('Bank Code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn($record) => $record->amount >= 0 ? 'success' : 'danger')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('plan_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ((int) $state) {
                        0 => 'Processing',
                        1 => 'Successful',
                        2 => 'Rejected',
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
                SelectFilter::make('plan_status')
                    ->label('Status')
                    ->options([
                        0 => 'Processing',
                        1 => 'Successful',
                        2 => 'Failed',
                    ])
                    ->multiple()
            ])
            ->actions([
                Tables\Actions\Action::make('accept')
                    ->label('Accept')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accept Payment')
                    ->modalDescription('This will mark the payment as successful. You must credit the user manually after accepting.')
                    ->visible(fn($record) => (int) $record->plan_status !== 1)
                    ->action(function ($record) {
                        $record->plan_status = 1;
                        $record->save();
                        Notification::make()
                            ->title('Payment Accepted')
                            ->body("Transaction {$record->transid} has been marked as successful. Please credit the user manually.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject Payment')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Payment')
                    ->modalDescription('This will mark the payment as rejected. You do not need to credit the user.')
                    ->visible(fn($record) => (int) $record->plan_status !== 2)
                    ->action(function ($record) {
                        $record->plan_status = 2;
                        $record->save();
                        Notification::make()
                            ->title('Payment Rejected')
                            ->body("Transaction {$record->transid} has been rejected.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkAccept')
                        ->label('Accept Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Accept Payments')
                        ->modalDescription('This will mark the selected payments as successful. You must credit the users manually after accepting.')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                // if ((int) $record->plan_status === 0) {
                                $record->update(['plan_status' => 1]);
                                // }
                            }

                            Notification::make()
                                ->title('Payments Accepted')
                                ->body("Selected transactions have been marked as successful. Please credit the users manually.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulkReject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Payments')
                        ->modalDescription('This will mark the selected payments as rejected. No manual crediting is required.')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                // if ((int) $record->plan_status === 0) {
                                $record->update(['plan_status' => 2]);
                                // }
                            }
                            Notification::make()
                                ->title('Payments Rejected')
                                ->body("Selected transactions have been rejected.")
                                ->danger()
                                ->send();
                        }),
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
            'index' => Pages\ListManualFundings::route('/'),
            // 'create' => Pages\CreateManualFunding::route('/create'),
            'edit' => Pages\EditManualFunding::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getWidgets(): array
    {
        return [
            ManualStat::class
        ];
    }
}
