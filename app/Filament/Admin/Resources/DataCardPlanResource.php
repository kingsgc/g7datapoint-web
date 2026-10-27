<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DataCardPlanResource\Pages;
use App\Models\DataCardPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DataCardPlanResource extends Resource
{
    protected static ?string $model = DataCardPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static ?string $navigationGroup = "Plan Management";
    protected static ?string $navigationLabel = 'Data Card Plans';
    protected static ?int $navigationSort = 48;
    protected static ?string $modelLabel = 'Data Card Plan';
    protected static ?string $pluralModelLabel = 'Data Card Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Information')
                    ->schema([
                        Forms\Components\Select::make('network')
                            ->label('Network')
                            ->required()
                            ->options([
                                'MTN' => 'MTN',
                                'GLO' => 'GLO',
                                'AIRTEL' => 'Airtel',
                                '9MOBILE' => '9mobile',
                            ])
                            ->native(false)
                            ->searchable()
                            ->live(),

                        Forms\Components\TextInput::make('name')
                            ->label('Plan Name')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('e.g. 10GB'),
                        Forms\Components\Select::make('plan_type')
                            ->label('Plan Type')
                            ->required()
                            ->options([
                                'SME' => 'SME',
                                'COOPERATE GIFTING' => 'Cooperate Gifting',
                                'GIFTING' => 'Gifting',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('plan_day')
                            ->label('Validity Days')
                            ->required()
                            ->placeholder('e.g. 30 Days'),

                        Forms\Components\TextInput::make('load_pin')
                            ->label('Load PIN Code')
                            ->maxLength(20)
                            ->placeholder('e.g. *131*2#'),

                        Forms\Components\TextInput::make('check_balance')
                            ->label('Balance Check Code')
                            ->maxLength(20)
                            ->placeholder('e.g. *131*4#'),
                    ])->columns(2),

                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('smart')->label('Smart Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('awuf')->label('Awuf Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('api')->label('API Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('agent')->label('Agent Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Forms\Components\TextInput::make('special')->label('Special Price')->numeric()->required()->minValue(1)->prefix('₦')->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                    ])->columns(2),

                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('plan_status')
                            ->label('Active Plan')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false),
                    ]),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('network')
                    ->label('Network')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'MTN' => 'yellow',
                        'Airtel' => 'red',
                        'Glo' => 'green',
                        '9mobile' => 'blue',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'MTN' => 'heroicon-o-signal',
                        'Airtel' => 'heroicon-o-bolt',
                        'Glo' => 'heroicon-o-wifi',
                        '9mobile' => 'heroicon-o-device-phone-mobile',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Plan Name')->description(fn($record) => 'ID: ' . $record->plan_id),
                Tables\Columns\TextColumn::make('plan_day')
                    ->label('Validity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('smart')->label('Smart Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('awuf')->label('Awuf Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('agent')->label('Agent Price')->money('NGN')->copyable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('special')->label('Special Price')->money('NGN')->copyable()->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('plan_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->toggleable()
                    ->tooltip(fn($record) => $record->updated_at?->format('M j, Y h:i A')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('network')
                    ->label('Filter by Network')
                    ->options([
                        'MTN' => 'MTN',
                        'GLO' => 'GLO',
                        'AIRTEL' => 'Airtel',
                        '9MOBILE' => '9mobile',
                    ])
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('plan_type')
                    ->label('Filter by Plan Type')
                    ->options(fn() => DataCardPlan::query()->distinct()->pluck('plan_type', 'plan_type')->filter()->toArray())
                    ->multiple()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('plan_status')
                    ->label('Status')
                    ->trueLabel('Active Plans')
                    ->falseLabel('Inactive Plans')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->color('primary'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(DataCardPlan $record) => $record->plan_status ? 'Deactivate' : 'Activate')
                    ->color(fn(DataCardPlan $record) => $record->plan_status ? 'danger' : 'success')
                    ->icon(fn(DataCardPlan $record) => $record->plan_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->action(function (DataCardPlan $record) {
                        $record->plan_status = !$record->plan_status;
                        $record->save();
                        Notification::make()
                            ->title('Status Updated')
                            ->body("Plan {$record->name} has been " . ($record->plan_status ? 'activated' : 'deactivated'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['plan_status' => true]);
                            Notification::make()
                                ->title('Plans Activated')
                                ->body('Selected plans have been activated successfully.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['plan_status' => false]);
                            Notification::make()
                                ->title('Plans Deactivated')
                                ->body('Selected plans have been deactivated successfully.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('network')
            ->groups([
                'network',
                'plan_type',
                'plan_status',
            ])
            ->groupingSettingsInDropdownOnDesktop()
            ->defaultSort('id', 'desc')
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->striped()
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDataCardPlans::route('/'),
            'create' => Pages\CreateDataCardPlan::route('/create'),
            'edit' => Pages\EditDataCardPlan::route('/{record}/edit'),
        ];
    }
}
