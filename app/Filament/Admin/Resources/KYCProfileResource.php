<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KYCProfileResource\Pages;
use App\Filament\Admin\Resources\KYCProfileResource\RelationManagers;
use App\Models\KYCProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
class KYCProfileResource extends Resource
{
    protected static ?string $model = KYCProfile::class;
     protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'KYC Profile';
    protected static ?int $navigationSort = 8;
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('profile_image')
                    ->label('Profile Picture')
                    ->circular()
                    ->size(40)
                    ->url(fn ($record) => $record->profile_image ? asset($record->profile_image) : null, true) // opens in new tab
                     ->openUrlInNewTab()
                      ->getStateUsing(fn ($record) => $record->profile_image 
                    ? asset($record->profile_image)
                    : null)
                    ->toggleable(),
              TextColumn::make('user_email')
                    ->label('user_email')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('first_name')
                    ->label('Verification First Name')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('last_name')
                    ->label('Verification Last Name')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('middle_name')
                    ->label('Verification Middle Name')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('request_type')
                    ->label('Verification Type')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('number')
                    ->label('ID Verification Number')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('phone_number')
                    ->label('Verification Phone Number')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('dob')
                    ->label('Verification DB')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                TextColumn::make('gender')
                    ->label('Verification Gender')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
                 TextColumn::make('state_of_residence')
                    ->label('Verification Sate Of Residence')
                    ->searchable()
                    ->sortable() 
                    ->copyable(),
               TextColumn::make('created_at')
                    ->label('Created Date/Time')
                    ->dateTime('d M Y, h:i A') 
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated Date/Time')
                    ->dateTime('d M Y, h:i A') 
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ])->persistFiltersInSession()
            ->striped()->extremePaginationLinks()->paginated([10, 25, 50, 100])
            ->deferLoading();
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
            'index' => Pages\ListKYCProfiles::route('/')
        ];
    }
}
