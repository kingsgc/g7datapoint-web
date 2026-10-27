<?php

namespace App\Filament\Admin\Pages;

use App\Http\Controllers\MailController;
use App\Models\Deposit;
use App\Models\History;
use App\Models\Notif;
use App\Models\User;
use App\Models\WalletFunding;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Toggle;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
class CreditDebitUser extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon =  'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'Credit/Debit User';
    protected static ?int $navigationSort = 8;
    protected static string $view = 'filament.admin.pages.credit-debit-user';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'action_type' => 'credit',
            'wallet_type' => 'user_wallet',
        ]);
    }
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Transaction Details')
                    ->description('Perform wallet operations for users')
                    ->icon('heroicon-o-wallet')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Select User')
                            ->options(User::query()->pluck('username', 'id'))
                            ->searchable()
                            ->getOptionLabelUsing(fn($value): ?string => User::find($value)?->username)
                            ->getSearchResultsUsing(function (string $search): array {
                                $results = User::query()
                                    ->where(function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%")
                                            ->orWhere('phone', 'like', "%{$search}%")
                                            ->orWhere('type', 'like', "%{$search}%")
                                            ->orWhere('palmpay', 'like', "%{$search}%")
                                            ->orWhere('email', 'like', "%{$search}%")
                                            ->orWhere('username', 'like', "%{$search}%")
                                            ->orWhere('pin', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->pluck('username', 'id');
                                return $results->isNotEmpty() ? $results->toArray() : [];
                            })
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Select::make('action_type')
                            ->label('Transaction Type')
                            ->options([
                                'credit' => 'Credit User Wallet',
                                'debit' => 'Debit User Wallet',
                            ])
                            ->required()
                            ->live(),

                        Select::make('wallet_type')
                            ->label('Wallet Type')
                            ->options([
                                'user_wallet' => 'Main Wallet',
                                'mtn_cg' => 'MTN CG Wallet',
                                'mtn_sme' => 'MTN SME Wallet',
                                'mtn_g' => 'MTN Gifting Wallet',
                                'airtel_cg' => 'Airtel CG Wallet',
                                'airtel_sme' => 'Airtel SME Wallet',
                                'airtel_g' => 'Airtel Gifting Wallet',
                                'glo_cg' => 'Glo CG Wallet',
                                'glo_sme' => 'Glo SME Wallet',
                                'glo_g' => 'Glo Gifting Wallet',
                                'mobile_cg' => '9mobile CG Wallet',
                                'mobile_sme' => '9mobile SME Wallet',
                                'mobile_g' => '9mobile Gifting Wallet',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->prefix('₦')
                            ->suffix('NGN')->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 2),
                        Toggle::make('is_active')
                            ->label('Notif User Email')
                            ->onColor('success')
                            ->offColor('danger'),
                        Textarea::make('reason')
                            ->label('Transaction Reason')
                            ->required()
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ])
            ->statePath('data');
    }


    protected function getFormActions(): Action
    {
        return
            Action::make('submit_a')
            ->label('Process Transaction')
            ->color('primary')
            ->size('lg')
            ->icon('heroicon-o-check-circle')
            ->action('processTransaction')
        ;
    }
    protected function getWalletName(string $type): string
    {
        return match ($type) {
            'user_wallet' => 'Main Wallet',
            'mtn_cg' => 'MTN CG Wallet',
            'mtn_sme' => 'MTN SME Wallet',
            'mtn_g' => 'MTN Gifting Wallet',
            'airtel_cg' => 'Airtel CG Wallet',
            'airtel_sme' => 'Airtel SME Wallet',
            'airtel_g' => 'Airtel Gifting Wallet',
            'glo_cg' => 'Glo CG Wallet',
            'glo_sme' => 'Glo SME Wallet',
            'glo_g' => 'Glo Gifting Wallet',
            'mobile_cg' => '9mobile CG Wallet',
            'mobile_sme' => '9mobile SME Wallet',
            'mobile_g' => '9mobile Gifting Wallet',
            default => $type
        };
    }
    public function processTransaction(): void
    {
        $data = $this->form->getState();
        $walletName = $this->getWalletName($data['wallet_type']);
        $credit = $data['action_type'] == 'credit' ? 'to' : 'from';
        $confirmMessage =  "Are you sure you want to {$data['action_type']} ₦" .
            number_format($data['amount'], 2) . " {$credit} {$walletName}?";
        LivewireAlert::title('Confirm Transaction')
            ->withConfirmButton()
            ->timer(100000)
            ->withOptions([
                'customClass' => ['popup' => 'animate__animated animate__bounceIn'],
                'allowOutsideClick' => false,
            ])
            ->text($confirmMessage)
            ->asConfirm('Yes, proceed')
            ->confirmButtonText('Yes, proceed')
            ->withDenyButton('Cancel')
            ->onConfirm('saveData', $data)
            ->show();
    }


    public function saveData($data)
    {
        $data = $this->form->getState();
        $walletType  = $data['wallet_type'];
        $actionType  = $data['action_type'];
        $amount      = (float) $data['amount'];
        $reason      = $data['reason'];
        $notifyUser  = $data['is_active'] ?? false;
        $user = User::find($data['user_id']);
        if (!$user) {
            LivewireAlert::title('Oops...')
                ->text('The selected user could not be found.')
                ->error()
                ->timer(3000)
                ->show();
            return;
        }
        $isCredit   = $actionType === 'credit';
        $depositRef = uniqid($isCredit ? 'Credit_' : 'Debit_');
        DB::beginTransaction();
        if ($walletType === 'user_wallet') {
            $user = User::where('id', $user->id)->lockForUpdate()->first();
            $oldBal = (float) $user->bal;
            $newBal = $isCredit ? $oldBal + $amount : $oldBal - $amount;
            $user->update(['bal' => $newBal]);
        } else {
            $walletF = WalletFunding::where('user_id', $user->id)->lockForUpdate()->first();
            if (!$walletF || !isset($walletF->{$walletType . '_bal'})) {
                LivewireAlert::title('Wallet Error')
                    ->text('The specified wallet type is invalid or missing.')
                    ->error()
                    ->timer(5000)->withConfirmButton()->confirmButtonText('ok')
                    ->show();
                return;
            }
            $oldBal = (float) $walletF->{$walletType . '_bal'};
            $newBal = $isCredit ? $oldBal + $amount : $oldBal - $amount;
            $walletF->{$walletType . '_bal'} = $newBal;
            $walletF->save();
        }
        History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'message'     => $reason,
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 1,
            'transid'     => $depositRef,
            'my_trans_id' => uniqid('HT_'),
            'role'        => $isCredit ? 'credit' : 'debit',
        ]);
        Deposit::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'wallet_type' => $this->getWalletName($walletType),
            'type'        => 'Admin Funding',
            'credit_by'   => auth()->user()->username,
            'date'        => now(),
            'status'      => 1,
            'transid'     => $depositRef,
            'charges'     => 0,
        ]);
        Notif::create([
            'username' => $user->username,
            'user_id'  => $user->id,
            'title' => $isCredit ? 'Wallet Credited' : 'Wallet Debited',
            'message'  => 'Your account has been ' . ($isCredit ? 'credited' : 'debited') . ' ₦' . number_format($amount, 2) . ' by admin.',
            'date'     => now(),
            'adex'     => 0,
        ]);
        DB::commit();
        if ($notifyUser) {
            $email_data = [
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'title' => $isCredit ? 'Wallet Credited' : 'Wallet Debited',
                'sender_mail' => config('app.email'),
                'app_name' => config('app.name'),
                'wallet' => $this->getWalletName($walletType),
                'amount' => number_format($amount, 2),
                'oldbal' => number_format($oldBal, 2),
                'newbal' => number_format($newBal, 2),
                'deposit_type' => $isCredit ? 'credit' : 'debit',
                'transid' => $depositRef
            ];
            dispatch(function () use ($email_data) {
                MailController::send_mail($email_data, 'email.deposit');
            })->afterResponse();
        }
        $adminEmailData = [
            'name' => $user->name,
            'username' => strtoupper($user->username),
            'title' => $isCredit ? 'Wallet Credited' : 'Wallet Debited',
            'sender_mail' => config('app.email'),
            'app_name' => config('app.name'),
            'wallet' => $this->getWalletName($walletType),
            'amount' => number_format($amount, 2),
            'oldbal' => number_format($oldBal, 2),
            'newbal' => number_format($newBal, 2),
            'deposit_type' => $isCredit ? 'credit' : 'debit',
            'transid' => $depositRef,
            'credited_by' => strtoupper(auth()->user()->username)
        ];
        foreach (User::where('type', 'ADMIN')->get() as $admin) {
            $adminEmailData['email'] = $admin->email;
            dispatch(function () use ($adminEmailData) {
                MailController::send_mail($adminEmailData, 'email.admin');
            })->afterResponse();
        }
        LivewireAlert::title('Transaction Successful')
            ->text("{$user->username}'s {$this->getWalletName($walletType)} has been {$actionType}ed with ₦" . number_format($amount, 2))
            ->success()
            ->timer(5000)->withConfirmButton()->confirmButtonText('ok')
            ->show();
        $this->form->fill([
            'action_type' => 'credit',
            'wallet_type' => 'user_wallet',
        ]);
    }
}
