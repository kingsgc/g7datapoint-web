<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CableResource\Pages;
use App\Filament\Admin\Resources\CableResource\RelationManagers;
use App\Models\CablePlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CableResource extends Resource
{
    protected static ?string $model = CablePlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';
    protected static ?string $navigationGroup = "Plan Management";
    protected static ?string $navigationLabel = 'Cable';
    protected static ?int $navigationSort = 44;
    protected static ?string $modelLabel = 'Cable Plan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cable Plan Details')
                    ->description('Fill in the details for the cable plan.')
                    ->schema([
                        Forms\Components\Select::make('cable_name')
                            ->label('Cable Name')
                            ->options([
                                'DSTV' => 'DSTV',
                                'STARTIME' => 'STARTIME',
                                'GOTV' => 'GOTV',
                            ])
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('plan_name')
                            ->label('Plan Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g., DStv Premium'),
                        Forms\Components\Section::make('Pricing & Status')
                            ->schema([
                                Forms\Components\TextInput::make('plan_price')
                                    ->label('Price (₦)')
                                    ->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                                Forms\Components\Toggle::make('plan_status')
                                    ->label('Is Active?')
                                    ->inline(false)
                                    ->default(true)
                                    ->helperText('If disabled, this plan will not be available to users.'),
                            ])->columns(2),
                        Forms\Components\Section::make('API Mappings')
                            ->schema([
                                Forms\Components\Repeater::make('api_mappings')
                                    ->label('A D E Mappings')
                                    ->schema([
                                        Forms\Components\Select::make('adex_api_id')
                                            ->label('A D E API')
                                            ->options(\App\Models\AdexAPI::all()->pluck('id')->mapWithKeys(function ($id) {
                                                $api = \App\Models\AdexAPI::find($id);
                                                return [$id => "{$api->website_url} => ADEX {$id}"];
                                            }))
                                            ->searchable()
                                            ->required()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set, $context) {
                                                $mappings = $get('../../api_mappings') ?? [];
                                                $duplicates = collect($mappings)->pluck('adex_api_id')->filter()->duplicates();
                                                if ($duplicates->isNotEmpty()) {
                                                    $set('adex_api_id', null);
                                                    Notification::make()
                                                        ->title('Duplicate A D E API')
                                                        ->body('This A D E API mapping is already added. Please choose a different one.')
                                                        ->danger()
                                                        ->send();
                                                }
                                            }),
                                        Forms\Components\TextInput::make('plan_id')
                                            ->label('Plan ID')
                                            ->required(),
                                    ])
                                    ->columns(2)
                                    ->default([])
                                    ->reorderable()
                                    ->addActionLabel('Add Mapping'),
                            ]),
                        Forms\Components\Section::make('External Identifiers')
                            ->schema([
                                Forms\Components\TextInput::make('quickly')->label('quickly sim')->nullable(),
                                Forms\Components\TextInput::make('vtpass')->label('VTPASS')->nullable(),
                            ])->columns(2)
                    ])
                    ->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('added_by')->label('Added By')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('cable_name')->label('Cable Name')->searchable(),
                Tables\Columns\TextColumn::make('plan_name')->label('Cable Plan Name')->searchable(),
                Tables\Columns\TextColumn::make('plan_price')->label('Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('plan_id')->label('Plan ID')->toggleable(),
                Tables\Columns\TextColumn::make('plan_status')->label('Status')->badge()->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created AT')->dateTime('M j, Y h:i A')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated AT')->dateTime('M j, Y h:i A')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_status')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('cable_name')
                    ->label('Cable Name')
                    ->searchable()
                    ->options(fn() => CablePlan::query()->distinct()->pluck('cable_name', 'cable_name')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(CablePlan $record) => $record->plan_status ? 'Deactivate' : 'Activate')
                    ->color(fn(CablePlan $record) => $record->plan_status ? 'danger' : 'success')
                    ->icon(fn(CablePlan $record) => $record->plan_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (CablePlan $record) {
                        $record->plan_status = !$record->plan_status;
                        $record->save();
                        $status = $record->plan_status ? 'activated' : 'deactivated';
                        Notification::make()->title('Success')->body("Plan {$status}.")->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Plans')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['plan_status' => 1])))
                        ->after(fn() =>  Notification::make()->title('Success')->body("Selected plans activated")->success()->send()),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Plans')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['plan_status' => 0])))
                        ->after(fn() =>  Notification::make()->title('Success')->body("Selected plans deactivated.")->success()->send()),
                ]),
            ])->defaultSort('id', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->persistFiltersInSession();
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
            'index' => Pages\ListCables::route('/'),
            'create' => Pages\CreateCable::route('/create'),
            'edit' => Pages\EditCable::route('/{record}/edit'),
        ];
    }
}
