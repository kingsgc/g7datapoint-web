<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AppDownloadResource\Pages;
use App\Filament\Admin\Resources\AppDownloadResource\RelationManagers;
use App\Models\AppDownload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppDownloadResource extends Resource
{
    protected static ?string $model = AppDownload::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-arrow-down';
    protected static ?string $navigationGroup = 'General Settings';
    protected static ?int $navigationSort = 3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('app_name')
                    ->label('APP Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('app_version')
                    ->label('APP Verison')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('platform')
                    ->label('Platform')
                    ->options([
                        'android' => 'Android',
                        'ios' => 'iOS',
                        'web' => 'Web',
                    ])->required(),
                Forms\Components\TextInput::make('app_link')
                    ->label('APP Link')
                    ->required()
                    ->url()
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('app_name')->label('App Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('app_version'),
                Tables\Columns\TextColumn::make('platform'),
                Tables\Columns\TextColumn::make('app_link'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAppDownloads::route('/'),
            'create' => Pages\CreateAppDownload::route('/create'),
            'edit' => Pages\EditAppDownload::route('/{record}/edit'),
        ];
    }
}
