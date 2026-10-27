<?php

namespace App\Filament\Admin\Pages;

use App\Models\VirusAPI;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class VIRUS extends Page
{
      use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = "API's";
    protected static ?string $navigationLabel = 'VIRUS';
    protected static ?int $navigationSort = 40;
    protected static ?string $title = 'VIRUS WEBSITE INTEGRATION';
    protected static string $view = 'filament.admin.pages.v-i-r-u-s';
    public array $data = [];

    public function mount(): void
    {
        $this->data = VirusAPI::first()?->only([
            'virus1',
            'virus1_website',
            'virus2',
            'virus2_website',
            'virus3',
            'virus3_website',
            'virus4',
            'virus4_website',
            'virus5',
            'virus5_website',
        ]) ?? [];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('VIRUS WEBSITE')->schema([
                Grid::make(2)->schema(
                    collect(range(1, 5))->flatMap(function ($i) {
                        return [
                            TextInput::make("virus{$i}_website")
                                ->label("VIRUS {$i} WEBSITE URL")
                                ->nullable()
                                ->url(),
                            TextInput::make("virus{$i}")
                                ->label("VIRUS {$i} TOKEN")
                                ->nullable()
                        ];
                    })->toArray()
                ),
            ])
        ])->statePath('data');
    }

    protected function getFormActions(): Action
    {
        return Action::make('save_a')
            ->label('Save')
            ->action('save');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        VirusAPI::updateOrCreate([], [
            'virus1' => $data['virus1'],
            'virus1_website' => $data['virus1_website'],
            'virus2' => $data['virus2'],
            'virus2_website' => $data['virus2_website'],
            'virus3' => $data['virus3'],
            'virus3_website' => $data['virus3_website'],
            'virus4' => $data['virus4'],
            'virus4_website' => $data['virus4_website'],
            'virus5' => $data['virus5'],
            'virus5_website' => $data['virus5_website'],
        ]);

        \Filament\Notifications\Notification::make()
            ->title('VIRUS WEBSITE INTEGRATION Saved Successfully!')
            ->success()
            ->send();
    }
}
