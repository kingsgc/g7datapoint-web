<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NetworkResource\Pages;
use App\Models\Network;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class NetworkResource extends Resource
{
    protected static ?string $model = Network::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = "Plan Management";
    protected static ?string $navigationLabel = 'Networks';
    protected static ?int $navigationSort = 47;
    protected static ?string $modelLabel = 'Network';
    protected static ?string $pluralModelLabel = 'Networks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Network Information')
                    ->schema([
                        Forms\Components\TextInput::make('network')
                            ->label('Network Name')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. MTN, Airtel, Glo'),
                        Forms\Components\FileUpload::make('network_logo')
                            ->label('Network Logo')
                            ->image()
                            ->directory('network-logos')
                            ->preserveFilenames()
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200')
                            ->getUploadedFileNameForStorageUsing(
                                fn(TemporaryUploadedFile $file): string => (string) str(Str::slug($file->getClientOriginalName()))
                                    ->prepend('network-logo-')
                                    ->append('.' . $file->getClientOriginalExtension())
                            ),
                        Forms\Components\TextInput::make('adex_id')
                            ->label('Adex ID')
                            ->maxLength(20)
                            ->placeholder('Adex reference ID'),
                        Forms\Components\TextInput::make('msorg_id')
                            ->label('MSOrg ID')
                            ->maxLength(20)
                            ->placeholder('MSOrg reference ID'),
                        Forms\Components\TextInput::make('virus_id')
                            ->label('Virus ID')
                            ->maxLength(20)
                            ->placeholder('Virus reference ID'),
                        Forms\Components\TextInput::make('xixapay_id')
                            ->label('XIXAPAY ID')
                            ->maxLength(20)
                            ->placeholder('XIXAPAY reference ID'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('network_logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->network) . '&color=FFFFFF&background=111827'),

                Tables\Columns\TextColumn::make('network')
                    ->label('Network Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn($record) => 'Plan ID: ' . $record->plan_id),
                Tables\Columns\TextColumn::make('xixapay_id')
                    ->label('Xixapay ID')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('Not set')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('adex_id')
                    ->label('Adex ID')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('Not set')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('msorg_id')
                    ->label('MSOrg ID')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('Not set')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('virus_id')
                    ->label('Virus ID')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->placeholder('Not set')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y h:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('network')
                    ->label('Filter by Network')
                    ->options(fn() => Network::pluck('network', 'network')->unique())
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil')
                    ->color('primary'),

                Tables\Actions\Action::make('view_plans')
                    ->label('View Plans')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->url(fn($record) => '#'), // Replace with actual route when available
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add New Network')
                    ->icon('heroicon-o-plus'),
            ])
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
            ->striped();
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
            'index' => Pages\ListNetworks::route('/'),
            'create' => Pages\CreateNetwork::route('/create'),
            'edit' => Pages\EditNetwork::route('/{record}/edit'),
        ];
    }
}
