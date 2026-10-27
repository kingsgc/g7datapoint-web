<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use Webbingbrasil\FilamentCopyActions\Forms\Actions\CopyAction;
use Illuminate\Support\Str;
use App\Filament\Admin\Resources\UserResource\Widgets\UserStats;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'User Accounts';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Full Name')
                    ->required()
                    ->minLength(3)
                    ->maxLength(100)
                    ->rules(['regex:/^[a-zA-Z]+(?:\s[a-zA-Z]+)+$/'])
                    ->helperText('Must contain first and last name (e.g., ADE Developers)'),
                Forms\Components\TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->minLength(3)
                    ->maxLength(100)
                    ->visibleOn('create')
                    ->unique(table: User::class, column: 'username', ignoreRecord: true)
                    ->rules([
                        'regex:/^[a-zA-Z0-9]+$/',
                    ])
                    ->helperText('Only letters and numbers. No spaces. Must be unique.'),
                Forms\Components\FileUpload::make('profile_image')
                    ->label('Profile Image')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->directory('avatars')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->enableDownload()
                    ->enableOpen()
                    ->nullable()
                    ->hint('Upload a clear square photo.')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->unique(table: User::class, column: 'email', ignoreRecord: true)
                    ->maxLength(255)
                    ->rules(['email']),
                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number')
                    ->required()
                    ->unique(table: \App\Models\User::class, column: 'phone', ignoreRecord: true)
                    ->rules([
                        'regex:/^0[0-9]{10}$/',
                    ])
                    ->maxLength(11),
                Forms\Components\Select::make('type')
                    ->label('Account Type')
                    ->required()
                    ->options([
                        'SMART' => 'SMART USERS',
                        'AWUF' => 'AWUF USERS',
                        'AGENT' => 'AGENT USERS',
                        'SPECIAL' => 'SPECIAL USERS',
                        'API' => 'API USERS',
                        'ADMIN' => 'ADMIN USERS',
                    ])
                    ->native(false),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->nullable(),
                Forms\Components\Select::make('status')
                    ->label('Account Status')
                    ->required()
                    ->options([
                        0 => 'Unverified',
                        1 => 'Active',
                        2 => 'Banned',
                        3 => 'Deactivated',
                    ])
                    ->default(1)
                    ->native(false),
                Forms\Components\Toggle::make('kyc')
                    ->label('KYC Verified')
                    ->helperText('Enable if the user has completed KYC verification.')
                    ->inline(false)
                    ->default(false),
                Forms\Components\TextInput::make('user_limit')
                    ->label('User Limit')
                    ->numeric()
                    ->required()
                    ->default(5000)
                    ->minValue(0)
                    ->helperText('Set the maximum allowed actions or quota for the user.'),
                Forms\Components\TextInput::make('airtime_to_cash_limit')
                    ->label('Airtime to Cash Limit')
                    ->numeric()
                    ->required()
                    ->default(10000)
                    ->minValue(0)
                    ->helperText('Set the maximum allowed actions or quota for the user.'),
                Forms\Components\TextInput::make('address')
                    ->label('Address')
                    ->placeholder('e.g., 123 Example Street, Lagos')
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('webhook')
                    ->label('Webhook URL')
                    ->placeholder('e.g., https://yourdomain.com/webhook')
                    ->url()
                    ->nullable()
                    ->maxLength(255),
                Forms\Components\TextInput::make('pin')
                    ->label('PIN')
                    ->required()
                    ->password()
                    ->numeric()
                    ->length(4)
                    ->rules(['regex:/^\d{4}$/']),
                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context) => $context === 'create')
                    ->rule(Password::min(8)->mixedCase()->letters()->numbers()->symbols())
                    ->minLength(8)
                    ->revealable()
                    ->maxLength(64)
                    ->helperText('Must include uppercase, lowercase, numbers, and a special character.')
                    ->suffixAction(
                        fn() => Forms\Components\Actions\Action::make('generate')
                            ->icon('heroicon-o-sparkles')
                            ->action(function (\Livewire\Component $livewire) {
                                $state = $livewire->form->getState();
                                $state['password'] =  Str::random(12) . 'Aa1!';
                                $livewire->form->fill($state);
                                Notification::make()
                                    ->title('success')
                                    ->body('New Password Generated generated!')
                                    ->success()
                                    ->send();
                            })
                    ),
                Forms\Components\TextInput::make('apikey')
                    ->label('API Key')
                    ->readOnly()
                    ->dehydrated()
                    ->visibleOn('edit')
                    ->helperText('Treat this key like a password - keep it secure!')
                    ->suffixActions([
                        CopyAction::make(),
                        Forms\Components\Actions\Action::make('regenerate')
                            ->icon('heroicon-o-arrow-path')
                            ->color('danger')
                            ->tooltip('Generate new key')
                            ->action(function (\Livewire\Component $livewire) {
                                $state = $livewire->form->getState();
                                $state['apikey'] = Str::orderedUuid() . Str::random(40);
                                $livewire->form->fill($state);
                                Notification::make()
                                    ->title('success')
                                    ->body('New API key generated!')
                                    ->success()
                                    ->send();
                            })
                            ->requiresConfirmation()
                            ->modalHeading('Regenerate API Key?')
                            ->modalDescription('Any systems using the current key will stop working immediately.')
                            ->modalSubmitActionLabel('Confirm Regeneration')
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->size(40)->toggleable(),
                TextColumn::make('name')->label('Full Name')->searchable()->toggleable()->copyable()->sortable(),
                TextColumn::make('username')->searchable()->sortable()->copyable(),
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('phone')->label('Phone Number')->searchable()->sortable()->copyable()->toggleable(),
                TextColumn::make('pin')->label('PIN')->sortable()->searchable()->toggleable(),
                TextColumn::make('bal')
                    ->label('Wallet Balance')
                    ->money('NGN')
                    ->copyable()
                    ->searchable()
                    ->sortable()->toggleable(),
                TextColumn::make('refbal')
                    ->label('Earning Balance')
                    ->money('NGN')
                    ->searchable()
                    ->sortable()->toggleable(),
                TextColumn::make('kyc')
                    ->label('Verified')
                    ->badge()

                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->color(fn($state) => $state ? 'success' : 'danger')->toggleable(),
                TextColumn::make('status')
                    ->label('Account Status')
                    ->badge()

                    ->formatStateUsing(function ($state) {
                        return match ((int) $state) {
                            0 => 'Unverified',
                            1 => 'Active',
                            2 => 'Banned',
                            3 => 'Deactivated',
                            default => 'Unknown',
                        };
                    })
                    ->color(function ($state) {
                        return match ((int) $state) {
                            0 => 'waiting',
                            1 => 'success',
                            2 => 'danger',
                            3 => 'warning',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('date')
                    ->searchable()
                    ->label('Reg Date')
                    ->dateTime('d M, Y'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'SMART' => 'SMART USERS',
                        'AWUF' => 'AWUF USERS',
                        'AGENT' => 'AGENT USERS',
                        'SPECIAL' => 'SPECIAL USERS',
                        'API' => 'API USERS',
                        'CUSTOMER' => 'CUSTOMER CARE',
                        'ADMIN' => 'ADMIN USERS',
                    ])
                    ->label('Account Type'),
                Filter::make('verified')
                    ->query(fn(Builder $query): Builder => $query->where('kyc', true))
                    ->label('Verified Only'),
                Filter::make('with_balance')
                    ->query(fn(Builder $query): Builder => $query->where('bal', '>', 0))
                    ->label('With Balance'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->placeholder(fn($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators['created_from'] = 'Users from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators['created_until'] = 'users until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->color('danger')
                    ->authorize('update_user')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn(User $record) => $record->status != 2)
                    ->action(fn(User $record) => $record->update(['status' => 2]))
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('ban')
                        ->color('danger')
                        ->authorize('update_user')
                        ->icon('heroicon-o-no-symbol')
                        ->action(fn($records) => $records->each->update(['status' => 2])),
                ]),
            ])->groups([
                Tables\Grouping\Group::make('date')
                    ->label('User Date')
                    ->date()
                    ->collapsible(),
            ])->defaultSort('id', 'desc')->extremePaginationLinks()->paginated([10, 25, 50, 100])
            ->persistFiltersInSession();
    }
    public static function getRelations(): array
    {
        return [];
    }

    public static function getWidgets(): array
    {
        return [
            UserStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
