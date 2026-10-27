<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BlockNumberResource\Pages;
use App\Filament\Admin\Resources\BlockNumberResource\RelationManagers;
use App\Models\Block;
use App\Models\BlockNumber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BlockNumberResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Block Number';
    protected static ?string $label = "Block Numbers";
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->label('Block Number')->unique(table: \App\Models\Block::class, column: 'number', ignoreRecord: true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('added_by')->label('Added By')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('number')->label('Block Number')->searchable()->toggleable()->copyable()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->searchable()->label('Created Date')->dateTime('d M, Y'),
                Tables\Columns\TextColumn::make('updated_at')->searchable()->label('Updated Date')->dateTime('d M, Y'),
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
            'index' => Pages\ListBlockNumbers::route('/'),
            'create' => Pages\CreateBlockNumber::route('/create'),
            'edit' => Pages\EditBlockNumber::route('/{record}/edit'),
        ];
    }
}
