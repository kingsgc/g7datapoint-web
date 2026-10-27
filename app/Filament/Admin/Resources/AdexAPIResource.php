<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AdexAPIResource\Pages;
use App\Models\AdexAPI;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class AdexAPIResource extends Resource
{
    protected static ?string $model = AdexAPI::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = "API's";
    protected static ?string $navigationLabel = 'ADEX';
    protected static ?int $navigationSort = 39;
    protected static ?string $modelLabel = 'ADEX WEBSITE INTEGRATION';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')->label('Adex')
                    ->required()->readOnly()
                    ->visibleOn('edit')
                    ->maxLength(255),
                Forms\Components\TextInput::make('website_url')
                    ->label('Website URL')
                    ->required()
                    // ->unique(AdexAPI::class, 'website_url', ignoreRecord: true)
                    ->url(),
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->required()
                    ->password()
                    ->revealable()
                    ->maxLength(255),

                Forms\Components\TextInput::make('balance')
                    ->required()->readOnly()
                    ->visibleOn('edit')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Adex'),
                Tables\Columns\TextColumn::make('website_url')->label('Website')->searchable(),
                Tables\Columns\TextColumn::make('username')->label('Username')->searchable(),
                Tables\Columns\TextColumn::make('password')->label('Password')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('balance')->label('Balance')->toggleable()
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('testConnection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-wifi')
                    ->authorize('update_adex::a::p::i')
                    ->color('secondary')
                    ->action(function (AdexAPI $record) {
                        try {
                            $response = Http::withBasicAuth($record->username, $record->password)
                                ->post($record->website_url . '/api/user');
                            if ($response->ok()) {
                                $balance = $response->json('balance') ?? 'N/A';
                                $record->update(['balance' => $balance]);
                                Notification::make()
                                    ->title('Connection successful')
                                    ->body("Balance: ₦{$balance}")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Connection failed')
                                    ->body('Could not connect to the API. Please check your credentials or URL.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Connection error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkTestConnection')
                        ->label('Bulk Test Connection')
                        ->icon('heroicon-o-signal')
                        ->color('secondary')
                        ->authorize('update_adex::a::p::i')
                        ->action(function (Collection $records) {
                            $success = 0;
                            $fail = 0;
                            foreach ($records as $record) {
                                try {
                                    $response = Http::withBasicAuth($record->username, $record->password)
                                        ->post("{$record->website_url}/api/user");
                                    if ($response->successful()) {
                                        $balance = $response->json('balance') ?? 'N/A';
                                        $record->update([
                                            'balance' => $balance,
                                        ]);
                                        $success++;
                                    } else {
                                        $fail++;
                                    }
                                } catch (\Exception $e) {
                                    $fail++;
                                }
                            }
                            Notification::make()
                                ->title('Bulk Test Completed')
                                ->body("✅ Successful: {$success} | ❌ Failed: {$fail}")
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
            'index' => Pages\ListAdexAPIS::route('/'),
            'create' => Pages\CreateAdexAPI::route('/create'),
            'edit' => Pages\EditAdexAPI::route('/{record}/edit'),
        ];
    }
}
