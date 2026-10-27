<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FeatureResource\Pages;
use App\Filament\Admin\Resources\FeatureResource\RelationManagers;
use Filament\Forms\Components\FileUpload;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'General Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Feature Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Feature Description')
                    ->rows(3)
                    ->maxLength(500)
                    ->nullable(),
                FileUpload::make('image')
                    ->label('Feature Icon')
                    ->image()
                    ->multiple(false)
                    ->disk('public')
                    ->imagePreviewHeight('100')
                    ->directory('features/icons')
                    ->maxSize(5120)
                    ->hint('Max 5MB (PNG, JPG)')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                TextInput::make('link')
                    ->label('Feature Link')
                    ->placeholder('https://wa.me/...')
                    ->url(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\ImageColumn::make('image')->label('Feature Icon')->circular()->size(40),
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
            'index' => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit' => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
