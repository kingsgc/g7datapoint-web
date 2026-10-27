<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DataCardResource\Pages;
use App\Filament\Admin\Resources\DataCardResource\RelationManagers;
use App\Models\DataCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use App\Models\History;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Admin\Resources\DataCardResource\Widgets\DataCardStat;
class DataCardResource extends Resource
{
    protected static ?string $model = DataCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Transaction Details';
    protected static ?string $navigationLabel = 'Data Card Transaction';
    protected static ?string $modelLabel = 'Data Card Transaction Record';
    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('transid')
                            ->label('Client Reference ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('my_trans_id')
                            ->label('Reference ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->disabled(),

                        Forms\Components\TextInput::make('created_at')
                            ->label('Transaction Date')
                           ->formatStateUsing(function ($state) {
                            return $state ? \Carbon\Carbon::parse($state)->format('d M Y, h:i:s A') : 'N/A';
                        })
                            ->disabled(),

                        Forms\Components\TextInput::make('vending_system')
                            ->label('Vending System')
                            ->disabled(),

                        Forms\Components\TextInput::make('vending_url')
                            ->label('Vending URL')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('API Timing')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('request_time')
                            ->label('Request Time')
                             ->formatStateUsing(function ($state) {
                            return $state ? \Carbon\Carbon::parse($state)->format('d M Y, h:i:s A') : 'N/A';
                        })
                            ->disabled(),

                        Forms\Components\TextInput::make('response_time')
                            ->label('Response Time')
                            ->formatStateUsing(function ($state) {
                                return $state ? \Carbon\Carbon::parse($state)->format('d M Y, h:i:s A') : 'N/A';
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('processing_time')
                            ->label('Processing Duration')
                            ->formatStateUsing(function ($record) {
                                if ($record->request_time && $record->response_time) {
                                    $start = \Carbon\Carbon::parse($record->request_time);
                                    $end = \Carbon\Carbon::parse($record->response_time);
                                    return $start->diffForHumans($end, ['parts' => 2, 'short' => true]);
                                }
                                return 'N/A';
                            })
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Service Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('network')
                            ->label('Network')
                            ->disabled(),
                        Forms\Components\TextInput::make('plan_name')
                            ->label('Plan Name')
                            ->disabled(),
                        Forms\Components\TextInput::make('plan_type')
                            ->label('Plan Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->disabled(),
                        Forms\Components\TextInput::make('card_name')
                            ->label('Card Name')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Financial Information')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (₦)')
                            ->formatStateUsing(fn($state) => number_format($state, 2))
                            ->disabled(),
                        Forms\Components\TextInput::make('oldbal')
                            ->label('Previous Balance')
                            ->formatStateUsing(fn($state) => number_format($state, 2))
                            ->disabled(),

                        Forms\Components\TextInput::make('newbal')
                            ->label('New Balance')
                            ->formatStateUsing(fn($state) => number_format($state, 2))
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Select::make('plan_status')
                            ->label('Transaction Status')
                            ->options([
                                0 => 'Processing',
                                1 => 'Successful',
                                2 => 'Failed',
                            ])
                            ->required()
                            ->live(),
                        Forms\Components\Textarea::make('ServerResponse')
                            ->label('Server Response')
                            ->columnSpanFull()
                            ->rows(3)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                 Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('d M Y, h:i A') 
                    ->sortable()
                    ->description(fn ($record) => $record->my_trans_id)
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable() 
                    ->copyable()
                    ->tooltip('Click to copy username'),
                Tables\Columns\TextColumn::make('transid')
                    ->label('Reference ID')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy transaction ID'),
                Tables\Columns\TextColumn::make('network')
                    ->label('Network')
                   ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'MTN' => 'yellow',
                        'AIRTEL' => 'red',
                        'GLO' => 'green',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->searchable(),
                 Tables\Columns\TextColumn::make('plan_type')
                    ->label('Plan Type')
                    ->searchable(),
                 Tables\Columns\TextColumn::make('plan_name')
                    ->label('Plan Name')
                    ->searchable(),
                 Tables\Columns\TextColumn::make('card_name')
                    ->label('Card Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('NGN')
                    ->sortable()
                    ->color(fn($record) => $record->amount >= 0 ? 'success' : 'danger')
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
                Tables\Columns\TextColumn::make('plan_status')
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
            Tables\Columns\TextColumn::make('request_time')
                    ->label('API Timing')
                    ->formatStateUsing(function ($record) {
                        $request = $record->request_time ? \Carbon\Carbon::parse($record->request_time)->format('h:i:s') : null;
                        $response = $record->response_time ? \Carbon\Carbon::parse($record->response_time)->format('h:i:s') : null;
                        
                        return $request && $response 
                            ? "{$request} → {$response}" 
                            : ($request ? "Request: {$request}" : ($response ? "Response: {$response}" : 'N/A'));
                    })
                    ->tooltip(function ($record) {
                        $request = $record->request_time ? \Carbon\Carbon::parse($record->request_time)->format('d M Y, h:i:s A') : null;
                        $response = $record->response_time ? \Carbon\Carbon::parse($record->response_time)->format('d M Y, h:i:s A') : null;
                        
                        return $request && $response 
                            ? "Request: {$request}\nResponse: {$response}" 
                            : ($request ? "Request: {$request}" : ($response ? "Response: {$response}" : 'No data'));
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vending_system')
                    ->label('Vendor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('vending_url')
                    ->label('Vending URL')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->vending_url)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('system')
                    ->label('System')
                    ->searchable(),
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
                                fn($q) => $q->whereDate('plan_date', today())
                            )
                            ->when(
                                $data['period'] === 'yesterday',
                                fn($q) => $q->whereDate('plan_date', today()->subDay())
                            )
                            ->when(
                                $data['period'] === 'week',
                                fn($q) => $q->whereBetween('plan_date', [now()->startOfWeek(), now()->endOfWeek()])
                            )
                            ->when(
                                $data['period'] === 'month',
                                fn($q) => $q->whereMonth('plan_date', now()->month)
                            )
                            ->when(
                                $data['period'] === 'year',
                                fn($q) => $q->whereYear('plan_date', now()->year)
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
                SelectFilter::make('network')
                    ->options([
                        'MTN' => 'MTN',
                        'AIRTEL' => 'AIRTEL',
                        'GLO' => 'GLO',
                        '9MOBILE' => '9MOBILE',
                    ])
                    ->multiple()
                    ->searchable(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_successful')
                    ->label('Mark Success')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('update_data::card')
                    ->modalHeading(fn(DataCard $record) => "Confirm Transaction #{$record->my_trans_id}")
                    ->modalDescription(function (DataCard $record) {
                        if ($record->plan_status == 1) {
                            return "This transaction is already marked as successful.";
                        }
                        $amountPaid = $record->amount;
                        $baseMessage = "Confirm this transaction as successful:\n\n"
                            . "• User: {$record->username}\n"
                            . "• Amount: ₦" . number_format($record->amount, 2) . "\n"
                            ;
                        if ($record->plan_status == 2) {
                            return $baseMessage . "\n⚠️ WARNING: This will DEBIT {$amountPaid} from the user's wallet balance!";
                        }
                        return $baseMessage;
                    })
                    ->hidden(fn(DataCard $record) => $record->plan_status == 1)
                    ->action(function (DataCard $record) {
                        $user = User::find($record->user_id);
                        $amountPaid = $record->amount; 
                        $isRefundReversal = ($record->plan_status == 2);
                        if (!$user) {
                            Notification::make()
                                ->title("Unable to Find User")
                                ->danger()
                                ->send();
                            return;
                        }
                         DB::beginTransaction();
                            $user = User::where('id', $user->id)->lockForUpdate()->first();
                            $oldBal = (float) $user->bal;
                            if ($record->plan_status == 2) {
                                $newBal =  $oldBal - $amountPaid;
                                $user->update(['bal' => $newBal]);
                            }
                        History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])->update(['plan_status' => 1, ...($isRefundReversal ? [
                            'oldbal' => $oldBal,
                            'newbal' => $newBal
                        ] : [])]);
                        $record->update([
                            'plan_status' => 1,
                            ...($isRefundReversal ? [
                                'oldbal' => $oldBal,
                                'newbal' => $newBal
                            ] : [])
                        ]);
                        DB::commit();
                        Notification::make()
                            ->title($isRefundReversal ? 'Refund Reversed' : 'Transaction Confirmed')
                            ->body($isRefundReversal ?
                                "₦" . number_format($record->amount) . " debited from {$user->username}'s account" :
                                "{$record->DataCard_name} purchase completed")
                            ->success()
                            ->send();
                    }),
            Tables\Actions\Action::make('mark_failed')
                    ->label('Refund')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                     ->authorize('update_data::card')
                    ->modalHeading(fn(DataCard $record) => "Refund Transaction #{$record->my_trans_id}")
                    ->modalDescription(function (DataCard $record) {
                        if ($record->plan_status == 2) {
                            return "This transaction is already marked as failed and cannot be refunded again.";
                        }
                        $amount = number_format($record->amount, 2);
                        $amount_paid = number_format($record->amount,2);
                        return "You're about to refund:\n\n"
                            . "👤 User: {$record->username}\n"
                            . "💰 Amount: ₦{$amount}\n\n"
                            . "This will credit ₦{$amount_paid} back to the user's balance.";
                    })
                    ->hidden(fn(DataCard $record) => $record->plan_status == 2)
                    ->action(function (DataCard $record, array $data) {
                        $user = User::find($record->user_id);
                        if (!$user) {
                            Notification::make()
                                ->title("Unable to Find User")
                                ->danger()
                                ->send();
                            return;
                        }
                        DB::beginTransaction();
                            $user = User::where('id', $user->id)->lockForUpdate()->first();
                            $oldBal = (float) $user->bal;
                            $amountPaid = $record->amount;
                            $newBal =  $oldBal + $amountPaid;
                            $user->update(['bal' => $newBal]);
                        History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])->update(['plan_status' => 2, 'newbal' => $newBal, 'oldbal' => $oldBal]);
                        $record->update([
                            'plan_status' => 2,
                            'oldbal' => $oldBal,
                            'newbal' => $newBal
                        ]);
                        DB::commit();
                        Notification::make()
                            ->title('Refund Successful')
                            ->body("₦" . number_format($amountPaid, 2) . " credited to {$record->username}'s balance")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                 Tables\Actions\BulkActionGroup::make([
                   Tables\Actions\BulkAction::make('bulk_refund')
                        ->label('Bulk Refund')
                        ->icon('heroicon-o-arrow-uturn-down')
                        ->color('danger')
                        ->requiresConfirmation()
                         ->authorize('update_data::card')
                        ->modalHeading('Bulk Refund Transactions')
                        ->modalDescription(function (Collection $records) {
                            $refundable = $records->where('plan_status', '!=', 2);
                            $totalAmountRefunded = $refundable->sum(fn($record) => $record->amount);
                            return "You will refund {$refundable->count()} transactions\n"
                                . "Total Amount Paid: ₦" . number_format($totalAmountRefunded, 2);
                        })
                        ->action(function (Collection $records, array $data) {
                            DB::beginTransaction();
                            $processed = 0;
                            $totalAmount = 0;
                            foreach ($records as $record) {
                                // Skip already failed transactions
                                if ($record->plan_status == 2) continue;
                                $user = User::where('id', $record->user_id)->lockForUpdate()->first();
                                if (!$user) continue;
                                   $amount = $record->amount;
                                    $oldBal = (float) $user->bal;
                                    $user->update(['bal' => $oldBal + $amount]);
                                // Update transaction records
                                History::where([
                                    'user_id' => $user->id,
                                    'my_trans_id' => $record->my_trans_id
                                ])->update([
                                    'plan_status' => 2,
                                    'newbal' => $oldBal + $amount,
                                    'oldbal' => $oldBal
                                ]);
                                $record->update([
                                    'plan_status' => 2,
                                    'oldbal' => $oldBal,
                                    'newbal' => $oldBal + $amount,
                                ]);
                                $processed++;
                                $totalAmount += $amount;
                            }
                            DB::commit();
                            Notification::make()
                                ->title('Refund Processed')
                                ->body("Successfully refunded ₦" . number_format($totalAmount, 2) . " across {$processed} transactions")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                Tables\Actions\BulkAction::make('bulk_mark_successful')
                        ->label('Bulk Mark Successful')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                         ->authorize('update_data::card')
                        ->modalHeading('Bulk Confirm Transactions')
                        ->modalDescription(function (Collection $records) {
                            $pending = $records->where('plan_status', 0);
                            $refundReversals = $records->where('plan_status', 2);
                            return "You're confirming {$records->count()} transactions:\n\n"
                                . "• Pending confirmations: {$pending->count()}\n"
                                . ($refundReversals->count() > 0 ?
                                    "• Refund reversals: {$refundReversals->count()} (will debit user wallets)\n" : "")
                                . "\nAlready successful transactions will be skipped.";
                        })
                        ->action(function (Collection $records, array $data) {
                            DB::beginTransaction();
                            $processed = 0;
                            $reversals = 0;
                            $skipped = 0;
                            foreach ($records as $record) {
                                if ($record->plan_status == 1) {
                                    $skipped++;
                                    continue;
                                }
                                $isRefundReversal = ($record->plan_status == 2);
                                $user = User::where('id', $record->user_id)->lockForUpdate()->first();
                                if (!$user) continue;
                                // Handle wallet debit for refund reversals
                                if ($isRefundReversal) {
                                    $amount = $record->amount;
                                        $oldBal = (float) $user->bal;
                                        $user->update(['bal' => $oldBal - $amount]);
                                    $reversals++;
                                }
                                // Update transaction records
                                History::where([
                                    'user_id' => $user->id,
                                    'my_trans_id' => $record->my_trans_id
                                ])->update(['plan_status' => 1]);
                                $record->update([
                                    'plan_status' => 1,
                                    ...($isRefundReversal ? [
                                        'oldbal' => $oldBal ?? 0,
                                        'newbal' => isset($oldBal) ? $oldBal - $amount : 0
                                    ] : [])
                                ]);
                                $processed++;
                            }
                            DB::commit();
                            Notification::make()
                                ->title('Bulk Confirmation Complete')
                                ->body(
                                    "Processed {$processed} transactions\n" .
                                        ($reversals > 0 ? "• {$reversals} refund reversals\n" : "") .
                                        "• {$skipped} transactions skipped"
                                )
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
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
            'index' => Pages\ListDataCards::route('/'),
            'create' => Pages\CreateDataCard::route('/create'),
            'edit' => Pages\EditDataCard::route('/{record}/edit'),
        ];
    }
     public static function canCreate(): bool
    {
        return false;
    }
       public static function getWidgets(): array {
        return [
            DataCardStat::class
        ];
    }
}
