<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\API\TransactionCalculator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions\Action; 
use Filament\Forms\Components\Actions; 
use App\Models\User;
use Carbon\Carbon; // Import Carbon for date parsing
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class TransactionSummary extends Page
{
     use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.admin.pages.transaction-summary';

    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 1;

    public ?string $status = 'TODAY';
    public ?string $from = null;
    public ?string $to = null;
    public ?int $selectedUserId = null;

    public array $transactionsSummary = [];

    /**
     * Mount method to initialize data when the page loads.
     */
    public function mount(): void
    {
        $this->loadSummary();
    }

    /**
     * Define the form schema for filters and the proceed button.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('status')
                    ->options([
                        'TODAY' => 'Today',
                        'YESTERDAY' => 'Yesterday',
                        '7DAYS' => 'Last 7 Days',
                        '30DAYS' => 'Last 30 Days',
                        // 'ALL TIME' => 'All Time', // Re-add if needed, ensure it's handled in the controller
                        'CUSTOM' => 'Custom Range',
                    ])
                    ->label('Date Range')
                    ->default('TODAY')
                    ->reactive() 
                    ->afterStateUpdated(function () {
                        if ($this->status !== 'CUSTOM') {
                            $this->from = null;
                            $this->to = null;
                        }
                    }),

                DatePicker::make('from')
                    ->label('From Date')
                    ->nullable()
                    ->native(false)
                    ->hidden(fn () => $this->status !== 'CUSTOM')
                    ->maxDate(fn() => $this->to ? Carbon::parse($this->to) : now())
                    ->reactive(),

                DatePicker::make('to')
                    ->label('To Date')
                    ->nullable()
                    ->native(false)
                    ->hidden(fn () => $this->status !== 'CUSTOM')
                    ->minDate(fn() => $this->from ? Carbon::parse($this->from) : null)
                    ->maxDate(fn() => now())
                    ->reactive(),

                Select::make('selectedUserId')
                    ->label('Filter by User')
                    ->options(function () {
                        if ($this->selectedUserId) {
                             $user = User::find($this->selectedUserId);
                             if ($user) {
                                return [$user->id => "{$user->name} ({$user->username}) => {$user->phone}"];
                             }
                        }
                        return User::latest()->limit(20)->get()->mapWithKeys(function ($user) {
                            return [$user->id => "{$user->name} ({$user->username}) => {$user->phone}"];
                        })->toArray();
                    })
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return User::where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->mapWithKeys(function ($name, $id) {
                                $user = User::find($id);
                                return [$id => "{$user->name} ({$user->username}) => {$user->phone}"];
                            })
                            ->toArray();
                    })
                    ->getOptionLabelUsing(function (?int $value) {
                        if (!$value) {
                            return null;
                        }
                        $user = User::find($value);
                        return $user ? "{$user->name} ({$user->username}) => {$user->phone}" : null;
                    })
                    ->nullable()
                    ->reactive(),
                Actions::make([
                    Action::make('proceed')
                        ->label('Apply Filters')
                        ->action(function () {
                            $this->loadSummary(); 
                        })
                        ->button()
                        ->color('primary')
                        ->icon('heroicon-o-funnel'), 
                ])->fullWidth(),
            ]);
    }

    /**
     * Fetches transaction summary data using the TransactionCalculator.
     */
    public function loadSummary(): void
    {
        // Construct a mock Request object to pass to the controller
        $request = new Request([
            'status' => $this->status,
            'from' => $this->from,
            'to' => $this->to,
            'user_id' => $this->selectedUserId,
        ]);

        $calculator = new TransactionCalculator();
        $response = $calculator->getAdminTransactionSummary($request);
        $this->transactionsSummary = $response->getData(true);
    }
}
