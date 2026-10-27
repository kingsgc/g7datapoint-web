<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Data;
use App\Models\DataPlan;
use App\Models\DataSelection;
use App\Models\History;
use App\Models\Network;
use App\Models\Setting;
use App\Models\User;
use App\Models\WalletFunding;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataPurchase extends Controller
{
    public function BuyData(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('User not authenticated', 403);
        }
        $setting = Setting::first();
        $system = $request->system;
        $myTransId = bin2hex(random_bytes(8));
        $transId = $system === 'WEBSITE' ? $this->purchase_ref('DATA_') : $request->input('request-id');
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }
        if ($setting->allow_pin && $system == 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid Transaction Pin', 403);
        }
        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('Request ID Exists Before', 403);
        }
        if (Block::where('number', $request->phone)->exists()) {
            return $this->failResponse('Phone Number Blocked', 403);
        }
        $networkInfo = Network::where('plan_id', $request->network)->first();
        if (!$networkInfo) {
            return $this->failResponse('Network ID invalid', 403);
        }
        $plan = DataPlan::where([
            'network' => $networkInfo->network,
            'plan_id' => $request->data_plan,
            'plan_status' => 1
        ])->first();
        if (!$plan) {
            return $this->failResponse('Invalid Data Plan ID and Network', 403);
        }

        $userType = $this->getUserType($user);
        [$vendingSystem, $walletBalField, $lockService] = $this->resolveVending($networkInfo, $plan);

        if (!$lockService || $networkInfo->$lockService != 1) {
            return $this->failResponse("{$networkInfo->network} {$plan->plan_type} is not available right now", 403);
        }

        if (substr($request->phone, 0, 1) != '0') {
            return $this->failResponse("Invalid Phone Number {$request->phone}", 403);
        }

        if ($setting->allow_limit && $user->kyc == 0 && !$this->withinDailyLimit($user, $plan, $userType)) {
            return $this->failResponse('You have reached your daily transaction limit. Kindly contact the admin to upgrade your account.', 403);
        }

        DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        $walletStore = WalletFunding::where('user_id', $user->id)->orWhere('username', $user->username)->lockForUpdate()->first();

        $vendingAllowed = $walletStore && isset($walletStore->$vendingSystem) && $walletStore->$vendingSystem == 1 && $setting->stock == 1;
        $userBalance = $vendingAllowed ? $walletStore->$walletBalField : $user->bal;

        if (!is_numeric($userBalance) || $userBalance < $plan->$userType) {
            return $this->failResponse('Insufficient balance in your ' . ($vendingAllowed ? $vendingSystem : 'wallet'), 403);
        }

        $debit = $userBalance - $plan->$userType;

        // Debit
        $debitSuccess = $vendingAllowed
            ? $walletStore->update([$walletBalField => $debit])
            : $user->update(['bal' => $debit]);

        if (!$debitSuccess) {
            return $this->failResponse('Unable to debit user account', 403);
        }

        DB::commit();
        $dataSel = DataSelection::first(); 
        $vendorFunc = $dataSel->$vendingSystem;
        // Store transaction
        $history = [
            'user_id' => $user->id,
            'username' => $user->username,
            
            'amount' => $plan->$userType,
            'message' => 'Transaction on process ' . $networkInfo->network . ' ' . $plan->plan_type . ' ' . $plan->plan_name . $plan->plan_size . ' to ' . $request->phone,
            'oldbal' => $userBalance,
            'newbal' => $debit,
            'adex_date' => now(),
            'plan_status' => 0,
            'transid' => $transId,
            'role' => 'data',
            'my_trans_id' => $myTransId
        ];

        $data = [
            'username' => $user->username,
            'user_id' => $user->id,
            'network_type' => $plan->plan_type,
            'network' => $networkInfo->network,
            'plan_name' => $plan->plan_name . $plan->plan_size,
            'amount' => $plan->$userType,
            'plan_status' => 0,
            'transid' => $transId,
            'plan_phone' => $request->phone,
            'plan_date' => now(),
            'oldbal' => $userBalance,
            'newbal' => $debit,
            'system' => $system,
            'wallet' =>  $vendingAllowed ? strtoupper($vendingSystem) : 'wallet',
            'my_trans_id' => $myTransId,
            'vending_system' => $vendorFunc,
        ];

        $historyTransaction = History::create($history);
        $dataTransaction = Data::create($data);
        // Send data via vending class
        $dataSender = new DataSend();
      if (Str::startsWith($vendorFunc, 'adex')) {
        $vendorFunc = 'adexHandler'; 
        }
       if(Str::startsWith($vendorFunc, 'msorg')){
            $vendorFunc = 'msorgHandler';
       }
       if(Str::startsWith($vendorFunc, 'virus')) {
            $vendorFunc = 'virusHandler';
        }
        $result = $dataSender->$vendorFunc($dataTransaction, $plan);
        return $this->handleVendingResponse($result, $user, $plan, $networkInfo, $transId, $request->phone, $userBalance, $debit, $system, $vendingAllowed ? strtoupper($vendingSystem) : 'wallet', $userType, $dataTransaction, $historyTransaction);
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'network' => 'required',
            'phone' => 'required|numeric|digits:11',
            'data_plan' => 'required',
            'request-id' =>  'nullable|unique:data,transid'
        ];

        if ($system === 'WEBSITE' && $setting->allow_pin == 1) {
            $rules['pin'] = 'required|numeric|digits:4';
        }
        return Validator::make($request->all(), $rules);
    }

    private function validatePin($user, $pin)
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }

    private function getUserType($user)
    {
        return match ($user->type) {
            'SMART' => 'smart',
            'AGENT' => 'agent',
            'AWUF' => 'awuf',
            'API' => 'api',
            default => 'special'
        };
    }

    private function transactionExists($transId, $myTransId)
    {
        return Data::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

    private function withinDailyLimit($user, $plan, $userType)
    {
        $total = History::where('user_id', $user->id)
            ->whereNotIn('role', ['credit', 'debit', 'upgrade'])
            ->where('plan_status', '!=', 2)
            ->where('created_at', '>=', Carbon::today())
            ->sum('amount');

        return ($total + $plan->$userType) <= $user->user_limit;
    }

    private function resolveVending($network, $plan)
    {
        return match ($plan->plan_type) {
            'GIFTING' => [$this->buildKey($network, 'g'), $this->buildBalanceKey($network, 'g'), 'network_g'],
            'COOPERATE GIFTING' => [$this->buildKey($network, 'cg'), $this->buildBalanceKey($network, 'cg'), 'network_cg'],
            'SME' => [$this->buildKey($network, 'sme'), $this->buildBalanceKey($network, 'sme'), 'network_sme'],
            default => [null, null, null],
        };
    }

    private function buildKey($network, $suffix)
    {
        return strtolower($network->network) . '_' . $suffix;
    }

    private function buildBalanceKey($network, $suffix)
    {
        return strtolower($network->network === '9MOBILE' ? 'mobile' : $network->network) . '_' . $suffix . '_bal';
    }

    private function buildTransactionMessage($network, $plan, $phone, $status)
    {
        $networkName = $network->network;
        $planName = $plan->plan_name . $plan->plan_size;

        if ($status === 'fail') {
            return "Failed Data Purchase: {$networkName} {$planName} to {$phone}. Amount refunded.";
        }

        return match ($networkName) {
            'AIRTEL' => "You have been gifted {$planName} of Data from " . env('APP_NAME') . " Technology",
            'MTN' => match ($plan->plan_type) {
                'SME' => "Dear Customer, You have successfully shared {$planName} Data to 234" . substr($phone, -10),
                'COOPERATE GIFTING' => "Dear Customer, You have gifted {$planName}, please dial *460*261# to check your balance. Thankyou.",
                'GIFTING' => "Yello! You have gifted {$planName} to 234" . substr($phone, -10) . ". Share link https://mtnapp.page.link/myMTNNGApp with 234" . substr($phone, -10) . " to download the new MyMTN app.",
                default => "You have been gifted {$planName}"
            },
            'GLO' => "You have successfully gifted {$planName} Oneoff to 234" . substr($phone, -10),
            default => "You have been gifted {$planName}"
        };
    }

    private function handleVendingResponse($result, $user, $plan, $network, $transId, $phone, $oldBal, $newBal, $system, $wallet, $userType, DATA $dataTransaction, History $historyTransaction)
    {
        $status = 0;
        $message = "Transaction on process " . $network->network . ' ' . $plan->plan_type . ' ' . $plan->plan_name . $plan->plan_size . ' to ' . $phone; // Default pending message
        $newBalanceAfterVendingOrRefund = $newBal;
        $dataTransaction->refresh();
        if ($result === 'success') {
            $status = 1;
            $message = $this->buildTransactionMessage($network, $plan, $phone, 'success');
            $dataTransaction->update([
                'plan_status' => $status,
            ]);
            $historyTransaction->update([
                'plan_status' => $status,
                'message' => $message
            ]);
        } elseif ($result === 'fail') {
            $status = 2; // Mark as failed
            $message = $this->buildTransactionMessage($network, $plan, $phone, 'fail');
            // --- Refund Logic ---
            DB::beginTransaction();
            // Re-fetch user and walletStore with lock for update to ensure atomicity
            // and get the most current state of balances
            $user = User::where('id', $user->id)->lockForUpdate()->first();
            $walletStore = WalletFunding::where('user_id', $user->id)->orWhere('username', $user->username)->lockForUpdate()->first();
            $vendingAllowed = $walletStore && isset($walletStore->$wallet) && $walletStore->$wallet == 1;

            if ($vendingAllowed) {
                $currentWalletBal = $walletStore->{$this->buildBalanceKey($network, explode('_', $wallet)[1])};
                $refundedAmount = $plan->$userType;
                $newBalance = $currentWalletBal + $refundedAmount;
                $walletStore->update([$this->buildBalanceKey($network, explode('_', $wallet)[1]) => $newBalance]);
                $walletStore->refresh(); // Refresh walletStore to get the updated balance in memory
                $newBalanceAfterVendingOrRefund = $walletStore->{$this->buildBalanceKey($network, explode('_', $wallet)[1])};
            } else {
                $refundedAmount = $plan->$userType;
                $newBalance = $user->bal + $refundedAmount;
                $user->update(['bal' => $newBalance]);
                $user->refresh(); // Refresh user to get the updated balance in memory
                $newBalanceAfterVendingOrRefund = $user->bal;
            }

            // Update history and data records for the refund
            $historyTransaction->update([
                'message' => 'Refund for failed transaction: ' . $network->network . ' ' . $plan->plan_type . ' ' . $plan->plan_name . $plan->plan_size . ' to ' . $phone . '. Amount refunded: ₦' . number_format($refundedAmount, 2),
                'oldbal' => $newBalanceAfterVendingOrRefund, // The balance just before the refund
                'newbal' => $newBalanceAfterVendingOrRefund, // The balance after the refund
                'plan_status' => $status,
            ]);

            $dataTransaction->update([
                'plan_status' => $status,
                'oldbal' => $newBalanceAfterVendingOrRefund, // The balance just before the refund
                'newbal' => $newBalanceAfterVendingOrRefund // The balance after the refund
            ]);
            DB::commit();
            $message .= " Amount of ₦" . number_format($plan->$userType, 2) . " refunded.";
        }
        // Send webhook if available
        if ($user->webhook) {
            try {
                $responseData = $dataTransaction->api_response;
                if ($responseData) {
                    $payload = [
                        'status' => $result,
                        'request-id' => $transId,
                        'response' => $responseData
                    ];
                    $this->sendWebhook($user->webhook, $payload);
                }
            } catch (\Throwable $e) {
                Log::error("Webhook failed: " . $e->getMessage());
            }
        }
        return response()->json([
            'network' => $network->network,
            'request-id' => $transId,
            
            'transid' => $transId,
            'amount' => $plan->$userType,
            'dataplan' => $plan->plan_name . $plan->plan_size,
            'status' => $result,
            'message' => $message,
            'phone_number' => $phone,
            'oldbal' => $oldBal,
            'newbal' => $newBal,
            'system' => $system,
            'wallet_vending' => strtoupper($wallet),
            'plan_type' => $plan->plan_type,
            'response' => $dataTransaction->api_response
        ]);
    }
    private function sendWebhook($url, array $payload)
    {
        Http::post($url, $payload);
    }
    private function failResponse($message, $status = 403)
    {
        return response()->json(['status' => 'fail', 'message' => $message], $status);
    }
}
