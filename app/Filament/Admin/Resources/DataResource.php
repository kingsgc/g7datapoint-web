<?php

namespace App\Filament\Admin\Resources;

use App\Models\DataPlan;
use App\Models\AdexAPI;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Admin\Resources\DataResource\Pages;
use Filament\Notifications\Notification;

class DataResource extends Resource
{
    protected static ?string $model = DataPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';
    protected static ?string $navigationGroup = "Plan Management";
    protected static ?string $navigationLabel = 'Data';
    protected static ?int $navigationSort = 43;
    protected static ?string $modelLabel = 'Data Plan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Info')
                    ->schema([
                        Forms\Components\Select::make('network')
                            ->label('Network')
                            ->options([
                                'MTN' => 'MTN',
                                'GLO' => 'GLO',
                                'AIRTEL' => 'Airtel',
                                '9MOBILE' => '9mobile',
                            ])
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('plan_name')
                            ->label('Plan Name')
                            ->required(),

                        Forms\Components\Select::make('plan_size')
                            ->label('Plan Size')
                            ->options([
                                'MB' => 'MB',
                                'GB' => 'GB',
                                'TB' => 'TB',
                            ])
                            ->required(),

                        Forms\Components\Select::make('plan_type')
                            ->label('Plan Type')
                            ->options([
                                'SME' => 'SME',
                                'COOPERATE GIFTING' => 'Cooperate Gifting',
                                'GIFTING' => 'Gifting',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('plan_day')
                            ->label('Duration (e.g. 30 days)')
                            ->required(),

                        Forms\Components\Toggle::make('plan_status')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('smart')->label('Smart Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('awuf')->label('Awuf Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('api')->label('API Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('agent')->label('Agent Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('special')->label('Special Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
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

                                        // Count how many times the selected API ID appears
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
                        Forms\Components\TextInput::make('quickly')->label('Quicklysim')->nullable(),
                        Forms\Components\TextInput::make('msorg1')->label('MSORG 1')->nullable(),
                        Forms\Components\TextInput::make('msorg2')->label('MSORG 2')->nullable(),
                        Forms\Components\TextInput::make('msorg3')->label('MSORG 3')->nullable(),
                        Forms\Components\TextInput::make('msorg4')->label('MSORG 4')->nullable(),
                        Forms\Components\TextInput::make('msorg5')->label('MSORG 5')->nullable(),

                        Forms\Components\TextInput::make('virus1')->label('VIRUS 1')->nullable(),
                        Forms\Components\TextInput::make('virus2')->label('VIRUS 2')->nullable(),
                        Forms\Components\TextInput::make('vorus3')->label('VIRUS 3')->nullable(),
                        Forms\Components\TextInput::make('virus4')->label('VIRUS 4')->nullable(),
                        Forms\Components\TextInput::make('virus5')->label('VIRUS 5')->nullable(),
                        
                        Forms\Components\TextInput::make('easyaccess')->label('Easy Access')->nullable(),
                        Forms\Components\TextInput::make('simserver')->label('SIMSERVER')->nullable(),
                        Forms\Components\TextInput::make('smeplug')->label('SME Plug')->nullable(),
                        Forms\Components\TextInput::make('ogdamns')->label('OGDAMNS')->nullable(),
                        Forms\Components\TextInput::make('simhosting')->label('SIM Hosting')->nullable(),
                        Forms\Components\TextInput::make('megasub')->label('MEGA SUB')->nullable(),
                        Forms\Components\TextInput::make('megasubcloud')->label('101 Terabyte')->nullable(),
                        Forms\Components\TextInput::make('vtpass')->label('VTPASS')->nullable(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('added_by')->label('Added By')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('network')->label('Network')->searchable(),
                Tables\Columns\TextColumn::make('plan_display')->label('Plan Name')->getStateUsing(fn($record) => "{$record->plan_name}{$record->plan_size}")->searchable(['plan_name', 'plan_size']),
                Tables\Columns\TextColumn::make('plan_type')->label('Type')->searchable(),
                Tables\Columns\TextColumn::make('plan_day')->label('Duration')->searchable(),

                Tables\Columns\TextColumn::make('smart')->label('Smart Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('awuf')->label('Awuf Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('agent')->label('Agent Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('special')->label('Special Price')->money('NGN')->copyable()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('plan_id')->label('Plan ID')->toggleable(),

                Tables\Columns\TextColumn::make('plan_status')->label('Status')->badge()->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created AT')->dateTime('M j, Y h:i A')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated AT')->dateTime('M j, Y h:i A')->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('network')
                    ->label('Network')
                    ->options([
                        'MTN' => 'MTN',
                        'GLO' => 'GLO',
                        'AIRTEL' => 'Airtel',
                        '9MOBILE' => '9mobile',
                    ])
                    ->searchable(),

                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('Plan Type')
                    ->options(fn() => DataPlan::query()->distinct()->pluck('plan_type', 'plan_type')->filter()->toArray()),

                Tables\Filters\TernaryFilter::make('plan_status')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(DataPlan $record) => $record->plan_status ? 'Deactivate' : 'Activate')
                    ->color(fn(DataPlan $record) => $record->plan_status ? 'danger' : 'success')
                    ->icon(fn(DataPlan $record) => $record->plan_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (DataPlan $record) {
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
            ])
            ->defaultSort('id', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->persistFiltersInSession();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListData::route('/'),
            'create' => Pages\CreateData::route('/create'),
            'edit' => Pages\EditData::route('/{record}/edit'),
        ];
    }
}
