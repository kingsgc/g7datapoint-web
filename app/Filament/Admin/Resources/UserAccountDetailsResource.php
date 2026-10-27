<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserAccountDetailsResource\Pages;
use App\Filament\Admin\Resources\UserAccountDetailsResource\RelationManagers;
use App\Models\UserBank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;

class UserAccountDetailsResource extends Resource
{
    protected static ?string $model = UserBank::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'User Account Details';
    protected static ?string $label = "User Account Details";
    protected static ?int $navigationSort = 12;
    public static function form(Form $form): Form
    {
        $banks = cache()->remember('paystack_banks_new', 3600, function () {
            $response = Http::get('https://api.paystack.co/bank');
            if ($response->successful()) {
                return collect($response->json('data'))->keyBy('name');
            }
            return collect();
        });
        return $form
            ->schema([
                Forms\Components\Select::make('bank')
                    ->label('Bank')
                    ->required()
                    ->options($banks->pluck('name', 'name'))->reactive()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) use ($banks) {
                        $bankCode = $banks[$state]['code'] ?? null;
                        $set('bank_code', $bankCode);
                    }),
                Forms\Components\TextInput::make('bank_code')
                    ->label('Bank Code')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('account_number')
                    ->label('Account Number')
                    ->required()
                    ->numeric()
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('bank_name')
                    ->label('Account Name')
                    ->required()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Account Name')->searchable()->toggleable()->copyable()->sortable(),
                Tables\Columns\TextColumn::make('bank')->label('Banks')->searchable()->toggleable()->copyable(),
                Tables\Columns\TextColumn::make('bank_code')->label('Bank Code')->searchable()->toggleable()->copyable(),
                Tables\Columns\TextColumn::make('account_number')->label('Account Number')->searchable()->toggleable()->copyable(),
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
            ])->defaultSort('id', 'desc')->extremePaginationLinks()->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListUserAccountDetails::route('/'),
            // 'create' => Pages\CreateUserAccountDetails::route('/create'),
            'edit' => Pages\EditUserAccountDetails::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
