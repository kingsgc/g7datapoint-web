<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StockBalanceResource\Pages;
use App\Filament\Admin\Resources\StockBalanceResource\RelationManagers;
use App\Models\StockBalance;
use App\Models\WalletFunding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockBalanceResource extends Resource
{
    protected static ?string $model = WalletFunding::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Stock Wallet';
    protected static ?string $label = "Stock Wallet";
    protected static ?int $navigationSort = 13;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Toggle::make('mtn_sme')
                    ->label('MTN SME WALLET')
                    ->helperText('Enable if you want the user to vend from mtn sme wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('mtn_cg')
                    ->label('MTN CG WALLET')
                    ->helperText('Enable if you want the user to vend from mtn cooperate gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('mtn_g')
                    ->label('MTN GIFTING WALLET')
                    ->helperText('Enable if you want the user to vend from mtn gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('airtel_sme')
                    ->label('AIRTEL SME WALLET')
                    ->helperText('Enable if you want the user to vend from airtel sme wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('airte;_cg')
                    ->label('AIRTEL CG WALLET')
                    ->helperText('Enable if you want the user to vend from airtel cooperate gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('airtel_g')
                    ->label('AIRTEL GIFTING WALLET')
                    ->helperText('Enable if you want the user to vend from airtel gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('glo_sme')
                    ->label('GLO SME WALLET')
                    ->helperText('Enable if you want the user to vend from glo sme wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('glo_cg')
                    ->label('GLO CG WALLET')
                    ->helperText('Enable if you want the user to vend from glo cooperate gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('glo_g')
                    ->label('GLO GIFTING WALLET')
                    ->helperText('Enable if you want the user to vend from glo gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('mobile_sme')
                    ->label('9MOBILE SME WALLET')
                    ->helperText('Enable if you want the user to vend from 9mobile sme wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('mobile_cg')
                    ->label('9MOBILE CG WALLET')
                    ->helperText('Enable if you want the user to vend from 9mobile cooperate gifting wallet.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\Toggle::make('mobile_g')
                    ->label('9MOBILE GIFTING WALLET')
                    ->helperText('Enable if you want the user to vend from mobile gifting wallet.')
                    ->inline(false)
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->label('Username')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('mtn_sme_bal')
                    ->label('MTN SME Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mtn_cg_bal')
                    ->label('MTN CG Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mtn_g_bal')
                    ->label('MTN Gifting Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('airtel_sme_bal')
                    ->label('AIRTEL SME Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('airtel_cg_bal')
                    ->label('AIRTEL CG Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('airtel_g_bal')
                    ->label('AIRTEL Gifting Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('glo_sme_bal')
                    ->label('GLO SME Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('glo_cg_bal')
                    ->label('GLO CG Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('glo_g_bal')
                    ->label('GLO Gifting Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mobile_sme_bal')
                    ->label('9MOBILE SME Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mobile_cg_bal')
                    ->label('9MOBILE CG Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mobile_g_bal')
                    ->label('9MOBILE Gifting Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('mtn_sme')
                    ->label('MTN SME Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('mtn_g')
                    ->label('MTN GIFTING Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('mtn_cg')
                    ->label('MTN CG Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('airtel_sme')
                    ->label('AIRTEL SME Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('airtel_g')
                    ->label('AIRTEL GIFTING Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('airtel_cg')
                    ->label('AIRTEL CG Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('glo_sme')
                    ->label('GLO SME Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('glo_g')
                    ->label('GLO GIFTING Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('glo_cg')
                    ->label('GLO CG Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('mobile_sme')
                    ->label('9MOBILE SME Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('mobile_g')
                    ->label('9MOBILE GIFTING Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('mobile_cg')
                    ->label('9MOBILE CG Enabled')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     // Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListStockBalances::route('/'),
            // 'create' => Pages\CreateStockBalance::route('/create'),
            'edit' => Pages\EditStockBalance::route('/{record}/edit'),
        ];
    }
}
