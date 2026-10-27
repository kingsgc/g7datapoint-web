<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\VirtualAccountResource\Pages;
use App\Filament\Admin\Resources\VirtualAccountResource\RelationManagers;
use App\Models\VirtualAccount;
use Filament\Actions\Modal\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
class VirtualAccountResource extends Resource
{
    protected static ?string $model = VirtualAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Virtual Accounts';
    protected static ?string $label = "User Virtual Accounts";
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('palmpay')
                    ->label('Palmpay Account')
                    ->required()
                    ->numeric()
                    ->unique(table: \App\Models\User::class, column: 'palmpay', ignoreRecord: true)
                    ->nullable()
                    ->maxLength(10),
                  Forms\Components\TextInput::make('kolo_moni')
                    ->label('KOLOMONI Account')
                    ->required()
                    ->numeric()
                    ->unique(table: \App\Models\User::class, column: 'kolo_moni', ignoreRecord: true)
                    ->nullable()
                    ->maxLength(10),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Full Name')->searchable()->toggleable()->copyable()->sortable(),
                Tables\Columns\TextColumn::make('username')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('name')->label('Full Name')->searchable()->toggleable()->copyable()->sortable(),
                Tables\Columns\TextColumn::make('palmpay')->label('Palmpay Account')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('kolo_moni')->label('KOLOMONI Account')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('bal')
                    ->label('Wallet Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('refbal')
                    ->label('Earning Balance')
                    ->money('NGN')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('delete')
                    ->label('Delete Virtual Account')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                     ->authorize('update_virtual::account')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->palmpay = null;
                        $record->kolo_moni = null;
                        $record->save();
                    }),
            ])
            ->bulkActions([

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected Accounts')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                         ->authorize('update_virtual::account')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->palmpay = null;
                                $record->kolo_moni = null;
                                $record->save();
                            }
                            Notification::make()
                                ->title('Deactivation Queued')
                                ->body('Accounts have been cleared in the background.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Selected accounts have been deactivated.'),
                ])

            ])->defaultSort('id', 'desc')->extremePaginationLinks()->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVirtualAccounts::route('/'),
            // 'create' => Pages\CreateVirtualAccount::route('/create'),
            'edit' => Pages\EditVirtualAccount::route('/{record}/edit'),
        ];
    }
}
