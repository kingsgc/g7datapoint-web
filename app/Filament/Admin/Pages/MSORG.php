<?php

namespace App\Filament\Admin\Pages;

use App\Models\MsorgAPI;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class MSORG extends Page
{
     use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = "API's";
    protected static ?string $navigationLabel = 'MSORG';
    protected static ?int $navigationSort = 40;
    protected static ?string $title = 'MSORG WEBSITE INTEGRATION';
    protected static string $view = 'filament.admin.pages.m-s-o-r-g';

    public array $data = [];
    public function mount(): void
    {
        $record = MsorgAPI::first();

        if ($record) {
            $this->form->fill([
                'msorg1' => $record->msorg1,
                'msorg1_website' => $record->msorg1_website,
                'msorg2' => $record->msorg2,
                'msorg2_website' => $record->msorg2_website,
                'msorg3' => $record->msorg3,
                'msorg3_website' => $record->msorg3_website,
                'msorg4' => $record->msorg4,
                'msorg4_website' => $record->msorg4_website,
                'msorg5' => $record->msorg5,
                'msorg5_website' => $record->msorg5_website,
            ]);
        }
    }
    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('MSORG WEBSITE')->schema([
                Grid::make(2)->schema(
                    collect(range(1, 5))->flatMap(function ($i) {
                        return [
                            TextInput::make("msorg{$i}_website")
                                ->label("MSORG {$i} WEBSITE URL")
                                ->url()
                                ->nullable(),

                            TextInput::make("msorg{$i}")
                                ->label("MSORG {$i} TOKEN")
                                ->nullable()
                        ];
                    })->toArray()
                ),
            ])
        ])->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return
            Action::make('save_a')
            ->label('Save')
            ->action('save');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        MsorgAPI::updateOrCreate([], [
            'msorg1_website' => $data['msorg1_website'],
            'msorg1' => $data['msorg1'],
            'msorg2_website' => $data['msorg2_website'],
            'msorg2' => $data['msorg2'],
            'msorg3_website' => $data['msorg3_website'],
            'msorg3' => $data['msorg3'],
            'msorg4_website' => $data['msorg4_website'],
            'msorg4' => $data['msorg4'],
            'msorg5_website' => $data['msorg5_website'],
            'msorg5' => $data['msorg5'],
        ]);
        \Filament\Notifications\Notification::make()
            ->title('MSORG WEBSITE INTEGRATION Saved Successfully!')
            ->success()
            ->send();
    }
}
