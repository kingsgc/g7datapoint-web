<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AirtimeCashResource\Pages;
use App\Filament\Admin\Resources\AirtimeCashResource\RelationManagers;
use App\Models\AirtimeCash;
use App\Models\Cash;
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
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Admin\Resources\AirtimeCashResource\Widgets\CashStat;
class AirtimeCashResource extends Resource
{
    protected static ?string $model = Cash::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Transaction Details';
    protected static ?string $navigationLabel = 'Airtime 2 Cash Transaction';
    protected static ?string $modelLabel = 'Airtime 2 Cash Transaction Record';
    protected static ?int $navigationSort = 21;

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

                        Forms\Components\TextInput::make('payment_type')
                            ->label('Payment Type')
                            ->disabled(),

                        Forms\Components\TextInput::make('sender_number')
                            ->label('Sender Number')
                            ->disabled(),

                         Forms\Components\TextInput::make('receiver_number')
                            ->label('Receiver Number')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Financial Information')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (₦)')
                            ->formatStateUsing(fn($state) => number_format($state, 2))
                            ->disabled(),

                         Forms\Components\TextInput::make('amount_credit')
                            ->label('Amount Credited (₦)')
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
               TextColumn::make('created_at')
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
                    ->label('Reference ID')
                    ->searchable()
                    ->copyable()
                    ->tooltip('Click to copy transaction ID'),
                TextColumn::make('network')
                    ->label('Network')
                   ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'MTN' => 'yellow',
                        'Airtel' => 'red',
                        'Glo' => 'green',
                        '9mobile' => 'blue',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('payment_type')
                    ->label('Payment Type')
                    ->searchable(),
                TextColumn::make('sender_number')
                    ->label('Sender Number')
                    ->searchable(),
                  TextColumn::make('receiver_number')
                    ->label('Receiver Number')
                    ->searchable(),
                TextColumn::make('amount_credit')
                    ->label('Amount Credited'),
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
                TextColumn::make('system')
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
                        'Airtel' => 'Airtel',
                        'Glo' => 'Glo',
                        '9mobile' => '9mobile',
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
    ->authorize('update_airtime::cash')
    ->modalHeading(fn(Cash $record) => "Confirm Transaction #{$record->my_trans_id}")
    ->modalDescription(function (Cash $record) {
        $baseMessage = "Confirm this transaction as successful:\n\n"
            . "👤 User: {$record->username}\n"
            . "📡 Network: {$record->network}\n"
            . "💰 Amount: ₦" . number_format($record->amount, 2) . "\n"
            . "✅ Amount Credited: ₦" . number_format($record->amount_credit, 2);

        if ($record->plan_status == 1) {
            return "⚠️ This transaction is already marked as successful.";
        }

        if ($record->plan_status == 2) {
            return $baseMessage . "\n\n⚠️ NOTE: This will credit the user again because it was previously failed.";
        }

        return $baseMessage;
    })
    ->hidden(fn(Cash $record) => $record->plan_status == 1)
    ->action(function (Cash $record) {
        $user = User::find($record->user_id);
        if (!$user) {
            Notification::make()->title("Unable to Find User")->danger()->send();
            return;
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        $oldBal = (float) $user->bal;
        $newBal = $oldBal;

        // plan_status = 0 or 2 → credit amount_credit
        if (in_array($record->plan_status, [0, 2])) {
            $newBal += (float) $record->amount_credit;
            $user->update(['bal' => $newBal]);
        }

        // Update history & transaction
        History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])
            ->update([
                'plan_status' => 1,
                'oldbal' => $oldBal,
                'newbal' => $newBal
            ]);

        $record->update([
            'plan_status' => 1,
            'oldbal' => $oldBal,
            'newbal' => $newBal
        ]);

        DB::commit();

        Notification::make()
            ->title('Transaction Confirmed')
            ->body("₦" . number_format($record->amount_credit, 2) . " credited to {$user->username}'s balance")
            ->success()
            ->send();
    }),
   Tables\Actions\Action::make('mark_failed')
    ->label('Mark Fail')
    ->icon('heroicon-o-x-mark')
    ->color('danger')
    ->requiresConfirmation()
    ->authorize('update_airtime::cash')
    ->modalHeading(fn(Cash $record) => "Refund Transaction #{$record->my_trans_id}")
    ->modalDescription(function (Cash $record) {
        if ($record->plan_status == 2) {
            return "⚠️ This transaction is already marked as failed and cannot be refunded again.";
        }

        $amount_paid = number_format($record->amount_credit, 2);
        $amount = number_format($record->amount, 2);

        if ($record->plan_status == 1) {
            return "You're about to mark this transaction as failed:\n\n"
                . "👤 User: {$record->username}\n"
                . "💰 Amount: ₦{$amount}\n"
                . "✅ Amount Credited: ₦{$amount_paid}\n\n"
                . "⚠️ This will DEBIT ₦{$amount_paid} from the user's wallet.";
        }

        return "Marking as failed without balance adjustment:\n\n"
            . "👤 User: {$record->username}\n"
            . "💰 Amount: ₦{$amount}\n";
    })
    ->hidden(fn(Cash $record) => $record->plan_status == 2)
    ->action(function (Cash $record) {
        $user = User::find($record->user_id);
        if (!$user) {
            Notification::make()->title("Unable to Find User")->danger()->send();
            return;
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        $oldBal = (float) $user->bal;
        $newBal = $oldBal;

        // If transaction was successful, now failing → debit
        if ($record->plan_status == 1) {
            $newBal -= (float) $record->amount_credit;
            $user->update(['bal' => $newBal]);
        }

        History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])
            ->update([
                'plan_status' => 2,
                'oldbal' => $oldBal,
                'newbal' => $newBal
            ]);

        $record->update([
            'plan_status' => 2,
            'oldbal' => $oldBal,
            'newbal' => $newBal
        ]);

        DB::commit();
        Notification::make()
            ->title('Transaction Refunded')
            ->body(($record->plan_status == 1)
                ? "₦" . number_format($record->amount_credit, 2) . " debited from {$user->username}'s balance"
                : "Transaction marked as failed without balance adjustment")
            ->success()
            ->send();
    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                  Tables\Actions\BulkAction::make('mark_successful_bulk')
    ->label('Mark as Successful')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->authorize('update_airtime::cash')
    ->action(function (Collection $records) {
        DB::transaction(function () use ($records) {
            foreach ($records as $record) {
                $user = User::find($record->user_id);
                if (!$user) continue;

                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $oldBal = (float) $user->bal;
                $newBal = $oldBal;

                if ($record->plan_status == 0) { 
                    // Processing → Success (CREDIT)
                    $newBal = $oldBal + $record->amount_credit;
                } elseif ($record->plan_status == 2) {
                    // Failed → Success (CREDIT)
                    $newBal = $oldBal + $record->amount_credit;
                }

                if ($newBal != $oldBal) {
                    $user->update(['bal' => $newBal]);
                }

                $record->update([
                    'plan_status' => 1,
                    'oldbal' => $oldBal,
                    'newbal' => $newBal,
                ]);

                History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])
                    ->update([
                        'plan_status' => 1,
                        'oldbal' => $oldBal,
                        'newbal' => $newBal
                    ]);
            }
        });

        Notification::make()
            ->title('Bulk Update Successful')
            ->body("Selected transactions have been marked as SUCCESSFUL")
            ->success()
            ->send();
    }),
            Tables\Actions\BulkAction::make('mark_failed_bulk')
                        ->label('Mark as Failed')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize('update_airtime::cash')
                        ->action(function (Collection $records) {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    $user = User::find($record->user_id);
                                    if (!$user) continue;

                                    $user = User::where('id', $user->id)->lockForUpdate()->first();
                                    $oldBal = (float) $user->bal;
                                    $newBal = $oldBal;

                                    if ($record->plan_status == 0) {
                                        // Processing → Failed (just mark failed, no balance change)
                                    } elseif ($record->plan_status == 1) {
                                        // Success → Failed (DEBIT)
                                        $newBal = $oldBal - $record->amount_credit;
                                    }

                                    if ($newBal != $oldBal) {
                                        $user->update(['bal' => $newBal]);
                                    }

                                    $record->update([
                                        'plan_status' => 2,
                                        'oldbal' => $oldBal,
                                        'newbal' => $newBal,
                                    ]);

                                    History::where(['user_id' => $user->id, 'my_trans_id' => $record->my_trans_id])
                                        ->update([
                                            'plan_status' => 2,
                                            'oldbal' => $oldBal,
                                            'newbal' => $newBal
                                        ]);
                                }
                            });

                            Notification::make()
                                ->title('Bulk Refund Completed')
                                ->body("Selected transactions have been marked as FAILED")
                                ->success()
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
            'index' => Pages\ListAirtimeCashes::route('/'),
            'create' => Pages\CreateAirtimeCash::route('/create'),
            'edit' => Pages\EditAirtimeCash::route('/{record}/edit'),
        ];
    }

     public static function canCreate(): bool
    {
        return false;
    }
      public static function getWidgets(): array
    {
        return [
            CashStat::class
        ];
    }
}
