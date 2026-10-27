<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\OtherApiResource\Pages;
use App\Models\OtherApi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OtherApiResource extends Resource
{
    protected static ?string $model = OtherApi::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = "API's";
    protected static ?string $navigationLabel = 'AychiNdo Data API';
    protected static ?int $navigationSort = 40;
    protected static ?string $modelLabel = 'AychiNdo API Credentials';
    protected static ?string $pluralModelLabel = 'AychiNdo API Credentials';

    /**
     * other_api is a singleton store read via OtherApi::first() throughout the
     * purchase flow, so only the first row is ever shown here.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->orderBy('id')->limit(1);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('aychindodata_key')
                    ->label('API Key')
                    ->required()
                    ->maxLength(65535)
                    ->helperText('The AychiNdo Data API key issued to you.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('aychindodata_secret')
                    ->label('API Secret')
                    ->required()
                    ->password()
                    ->revealable()
                    ->maxLength(65535)
                    // Stored as plain text, matching every other credential
                    // column in this table. Encrypting just this one field
                    // would tie it to APP_KEY, which is regenerated on deploy.
                    ->helperText('Stored as plain text like the other API credentials in this table.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('aychindodata_key')
                    ->label('API Key')
                    ->limit(24)
                    ->searchable(),
                Tables\Columns\TextColumn::make('configured_at')
                    ->label('Status')
                    ->getStateUsing(fn (OtherApi $record): string => filled($record->aychindodata_key) ? 'Configured' : 'Not set')
                    ->badge()
                    ->color(fn (OtherApi $record): string => filled($record->aychindodata_key) ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('updated_at')->label('Last updated')->dateTime()->toggleable(),
            ])
            ->filters([])
            // No delete or bulk-delete: this row is load-bearing for the
            // purchase controllers, which all read OtherApi::first().
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->persistFiltersInSession()
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
            'index' => Pages\ListOtherApis::route('/'),
            'create' => Pages\CreateOtherApi::route('/create'),
            'edit' => Pages\EditOtherApi::route('/{record}/edit'),
        ];
    }
}