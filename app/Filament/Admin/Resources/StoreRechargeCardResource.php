<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\StoreRechargeCardResource\Pages;
use App\Filament\Admin\Resources\StoreRechargeCardResource\RelationManagers;
use App\Models\StoreRechargeCard;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StoreRechargeCardResource extends Resource
{
    protected static ?string $model = StoreRechargeCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-up-down';
    protected static ?string $navigationGroup = "Plan Management";
    protected static ?string $navigationLabel = 'Store Recharge Card Pin';
    protected static ?int $navigationSort = 50;
    protected static ?string $modelLabel = 'Store Recharge Card Pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Card Details')
                    ->schema([
                        Forms\Components\Select::make('network')
                            ->label('Network Name')
                            ->required()
                            ->options([
                                'MTN' => 'MTN',
                                'GLO' => 'GLO',
                                'AIRTEL' => 'Airtel',
                                '9MOBILE' => '9mobile',
                            ])
                            ->searchable()
                            ->placeholder('Select network type'),
                        Forms\Components\Select::make('recharge_card_id')
                            ->label('Available Plans')
                            ->options(function (Forms\Get $get) {
                                $network = $get('network');
                                if (!$network) {
                                    return [];
                                }
                                return \App\Models\RechargeCardPlan::where('network', $network)
                                    ->get()
                                    ->mapWithKeys(function ($plan) {
                                        return [
                                            $plan->id => "{$plan->name}"
                                        ];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->searchable()
                            ->placeholder('Select a plan'),
                        Forms\Components\Fieldset::make('Import PINs / Serials from File')
                            ->schema([
                                Forms\Components\Select::make('import_type')
                                    ->label('Import Type')
                                    ->options([
                                        'pins' => 'Only PINs',
                                        'serials' => 'Only Serials',
                                        'both' => 'PINs & Serials',
                                    ])
                                    ->default('both')
                                    ->required()
                                    ->helperText('For "both" type, format: Please make sure your file follows one of the accepted formats:
- For .txt: each line must be in the format PIN001|SERIAL001
- For .xlsx: use two columns (no header), first for PIN, second for SERIAL'),

                                Forms\Components\FileUpload::make('bulk_pin_file')
                                    ->label('Upload .txt or .xlsx file')
                                    ->acceptedFileTypes([
                                        'text/plain',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->disk('public')
                                    ->directory('temp-uploads')
                                    ->visibility('private')
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->openable()
                                    ->multiple(false)
                                    ->saveUploadedFileUsing(fn(TemporaryUploadedFile $file) => $file->store('temp-uploads', 'local')),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('import_bulk_pins')
                                        ->label('Import from File')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('gray')
                                        ->action(function (Forms\Get $get, Forms\Set $set) {
                                            $type = $get('import_type');
                                            $uploaded = $get('bulk_pin_file');

                                            if (is_array($uploaded)) {
                                                $uploaded = collect($uploaded)->first();
                                            }

                                            if (!$uploaded instanceof TemporaryUploadedFile && !($uploaded instanceof \Illuminate\Http\UploadedFile)) {
                                                Notification::make()
                                                    ->title('File error')
                                                    ->body('Uploaded file is invalid or missing.')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            try {
                                                $path = $uploaded->getRealPath();
                                                $extension = $uploaded->getClientOriginalExtension();
                                                $pins = [];
                                                $serials = [];

                                                if ($extension === 'txt') {
                                                    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                                    foreach ($lines as $line) {
                                                        $parts = explode('|', $line);
                                                        if ($type === 'both' && count($parts) >= 2) {
                                                            $pins[] = trim($parts[0]);
                                                            $serials[] = trim($parts[1]);
                                                        } elseif ($type === 'pins') {
                                                            $pins[] = trim($parts[0]);
                                                        } elseif ($type === 'serials') {
                                                            $serials[] = trim($parts[0]);
                                                        }
                                                    }
                                                } elseif ($extension === 'xlsx') {
                                                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                                                    $spreadsheet = $reader->load($path);
                                                    $rows = $spreadsheet->getActiveSheet()->toArray();

                                                    foreach ($rows as $row) {
                                                        if ($type === 'both' && !empty($row[0]) && !empty($row[1])) {
                                                            $pins[] = trim($row[0]);
                                                            $serials[] = trim($row[1]);
                                                        } elseif ($type === 'pins' && !empty($row[0])) {
                                                            $pins[] = trim($row[0]);
                                                        } elseif ($type === 'serials' && !empty($row[0])) {
                                                            $serials[] = trim($row[0]);
                                                        }
                                                    }
                                                }

                                                if ($type === 'pins' && empty($pins)) {
                                                    Notification::make()->title('No PINs found')->danger()->send();
                                                    return;
                                                }
                                                if ($type === 'serials' && empty($serials)) {
                                                    Notification::make()->title('No Serials found')->danger()->send();
                                                    return;
                                                }
                                                if ($type === 'both' && (empty($pins) || empty($serials))) {
                                                    Notification::make()->title('Missing data')->body('Both PINs and Serials must be present.')->danger()->send();
                                                    return;
                                                }

                                                if (!empty($pins)) {
                                                    $set('bulk_pins', implode("\n", $pins));
                                                }
                                                if (!empty($serials)) {
                                                    $set('bulk_serials', implode("\n", $serials));
                                                }

                                                Notification::make()
                                                    ->title('Import successful')
                                                    ->body(count($pins) . ' PINs and ' . count($serials) . ' serials imported.')
                                                    ->success()
                                                    ->send();
                                            } catch (\Exception $e) {
                                                Notification::make()
                                                    ->title('Import failed')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                        ->requiresConfirmation()
                                        ->modalHeading('Import File')
                                        ->modalDescription('For "both" type, format: Please make sure your file follows one of the accepted formats:
- For .txt: each line must be in the format PIN001|SERIAL001
- For .xlsx: use two columns (no header), first for PIN, second for SERIAL')
                                        ->modalSubmitActionLabel('Yes, import'),
                                ]),
                            ])->visibleOn('create'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Textarea::make('bulk_pins')
                                    ->label('Bulk PINs')
                                    ->rows(10)
                                    ->required()
                                    ->columnSpan(1)
                                    ->helperText('One PIN per line or import from file.'),

                                Forms\Components\Textarea::make('bulk_serials')
                                    ->label('Bulk Serial Numbers')
                                    ->rows(10)
                                    ->required()
                                    ->columnSpan(1)
                                    ->helperText('One Serial per line or import from file.'),
                            ])->visibleOn('create'),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('pin')
                                    ->label('PIN')
                                    ->required()
                                    ->columnSpan(1)
                                    ->rules([
                                        fn(Forms\Get $get): array => [
                                            Rule::unique('store_recharge_card', 'pin')
                                                ->ignore($get('id'))
                                                ->where('network', $get('network'))
                                        ],
                                    ])
                                    ->helperText('PIN must be unique for the selected exam.'),
                                Forms\Components\TextInput::make('serial')
                                    ->label('Serial Number')
                                    ->required()
                                    ->columnSpan(1)
                                    ->rules([
                                        fn(Forms\Get $get): array => [
                                            Rule::unique('store_recharge_card', 'serial')
                                                ->ignore($get('id'))
                                                ->where('network', $get('network'))
                                        ],
                                    ])
                                    ->helperText('Serial must be unique for the selected exam.')
                            ])->visibleOn('edit'),

                        Forms\Components\TextInput::make('serial_suffix')
                            ->label('Serial Suffix')
                            ->placeholder('-EXAM')
                            ->helperText('Optional suffix to append to each serial (e.g., "-EXAM")')
                            ->maxLength(10)->visibleOn('create')
                            ->reactive(),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generate_serials')
                                ->label('Generate Serials')
                                ->icon('heroicon-o-cog')
                                ->color('primary')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $pins = array_filter(array_map('trim', explode("\n", $get('bulk_pins') ?? '')));
                                    if (empty($pins)) {
                                        Notification::make()
                                            ->title('No PINs available')
                                            ->body('Please enter or import PINs first.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $suffix = $get('serial_suffix') ?? '';
                                    $serials = [];

                                    foreach ($pins as $_) {
                                        $serials[] = strtoupper(Str::random(10)) . $suffix;
                                    }

                                    $set('bulk_serials', implode("\n", $serials));

                                    Notification::make()
                                        ->title('Serials generated')
                                        ->body(count($serials) . ' serial numbers created.')
                                        ->success()
                                        ->send();
                                }),
                        ])->visibleOn('create')->alignCenter(),
                        Forms\Components\Toggle::make('plan_status')
                            ->label('Mark as purchased')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('network')
                    ->label('Network')
                    ->searchable()
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
                    }),

                Tables\Columns\TextColumn::make('serial')
                    ->label('Serial Number')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Serial number copied')
                    ->copyMessageDuration(1500)
                    ->description(fn($record) => 'PIN: ' . $record->pin, position: 'below'),

                Tables\Columns\TextColumn::make('plan_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'Sold' : 'Available')
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->icon(fn($state) => $state ? 'heroicon-o-check-badge' : 'heroicon-o-clock')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('buyer_username')
                    ->label('Purchased By')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('Not sold yet')
                    ->color(fn($record) => $record->plan_status ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('bought_date')
                    ->label('Purchase Date')
                    ->dateTime('M j, Y h:i A')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not sold yet')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable()
                    ->tooltip(fn($record) => $record->created_at?->format('M j, Y h:i A')),
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
                    ->multiple()
                    ->searchable()
                    ->placeholder('Filter by network type'),
                Tables\Filters\TernaryFilter::make('plan_status')
                    ->label('Status')
                    ->placeholder('All PINs')
                    ->trueLabel('Sold PINs')
                    ->falseLabel('Available PINs')
                    ->queries(
                        true: fn(Builder $query) => $query->where('plan_status', true),
                        false: fn(Builder $query) => $query->where('plan_status', false),
                    ),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'From ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'Until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(StoreRechargeCard $record) => $record->plan_status ? 'Mark as Available' : 'Mark as Sold')
                    ->color(fn(StoreRechargeCard $record) => $record->plan_status ? 'gray' : 'success')
                    ->icon(fn(StoreRechargeCard $record) => $record->plan_status ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-shopping-cart')
                    ->requiresConfirmation()
                    ->modalHeading(fn(StoreRechargeCard $record) => $record->plan_status ? 'Mark PIN as Available?' : 'Mark PIN as Sold?')
                    ->modalDescription(fn(StoreRechargeCard $record) => $record->plan_status
                        ? 'This will make the PIN available for purchase again.'
                        : 'This will mark the PIN as sold.')
                    ->action(function (StoreRechargeCard $record) {
                        $record->plan_status = !$record->plan_status;
                        $record->save();
                        $status = $record->plan_status ? 'marked as sold' : 'made available';
                        Notification::make()
                            ->title('Status Updated')
                            ->body("PIN has been {$status} successfully.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_as_sold')
                        ->label('Mark as Sold')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected PINs as Sold')
                        ->modalDescription('Are you sure you want to mark these PINs as sold?')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['plan_status' => 1])))
                        ->after(fn() => Notification::make()
                            ->title('PINs Marked as Sold')
                            ->body('Selected PINs have been marked as sold.')
                            ->success()
                            ->send()),

                    Tables\Actions\BulkAction::make('mark_as_available')
                        ->label('Mark as Available')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected PINs as Available')
                        ->modalDescription('Are you sure you want to make these PINs available again?')
                        ->action(fn($records) => $records->each(fn($record) => $record->update(['plan_status' => 0])))
                        ->after(fn() => Notification::make()
                            ->title('PINs Marked as Available')
                            ->body('Selected PINs have been made available for purchase.')
                            ->success()
                            ->send()),
                ]),
            ])->defaultSort('id', 'desc')
            ->groups([
                'network',
                'plan_status',
            ])
            ->groupingSettingsInDropdownOnDesktop()
            ->groupRecordsTriggerAction(
                fn(Tables\Actions\Action $action) => $action
                    ->label('Group records'),
            )
            ->extremePaginationLinks()
            ->paginated([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->striped()
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
            'index' => Pages\ListStoreRechargeCards::route('/'),
            'create' => Pages\CreateStoreRechargeCard::route('/create'),
            'edit' => Pages\EditStoreRechargeCard::route('/{record}/edit'),
        ];
    }
}
