<?php

namespace App\Http\Controllers\Purchase;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Setting;
use App\Models\User;
use App\Models\DataCard as DataCardModel;
use App\Models\History;
use App\Models\DumpDataCardPin;
use App\Models\DataCardPlan;
use App\Models\DataCardSelection;
use App\Models\Network;
use Illuminate\Support\Facades\Validator;


class  DataCard extends Controller
{

    public function DataCardPurchase(Request $request)
    {
       
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('User authentication required.', 403);
        }
        $setting = Setting::first();
        $system  = $request->system;

        $myTransId = bin2hex(random_bytes(8));
        $transId   = $system === 'WEBSITE'
            ? $this->purchase_ref('Data_Card_')
            : $request->input('request-id');

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }

        // Pin validation
        if ($setting->allow_pin && $system === 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid transaction PIN provided.', 403);
        }

        // Duplicate transaction check
        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('Duplicate request detected. This transaction has already been processed.', 403);
        }
        $userType = $this->getUserType($user);
        $network = Network::where('plan_id', $request->network)->first();
        if ($network && $network->data_card != 1) {
            return $this->failResponse(strtoupper($network->network) . " Data Card is currently unavailable. Please try again later.", 403);
        }
        $plan = DataCardPlan::where(['network' => $network->network,'plan_id' => $request->plan_type,'plan_status' => 1])->first();
        if (!$plan) {
            return $this->failResponse('Invalid Data Plan ID and Network', 403);
        }
        // Daily limit check
        $amount =  $plan->$userType * $request->quantity;
        if ($setting->allow_limit == 1 && $user->kyc == 0) {
            $totalDailySpend = History::where('user_id', $user->id)
                ->whereNotIn('role', ['credit', 'debit', 'upgrade', 'earning'])
                ->where('plan_status', '!=', 2)
                ->where('created_at', '>=', Carbon::today())
                ->sum('amount');

            if (($totalDailySpend + $amount) > $user->user_limit) {
                return $this->failResponse('Daily transaction limit reached. Please contact support to upgrade your account.', 403);
            }
        }
         // Balance validation
        if (!is_numeric($user->bal)) {
            return $this->failResponse('Invalid account balance.', 403);
        }
         DB::beginTransaction();
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        if ($user->bal < $amount) {
            DB::rollBack();
            return $this->failResponse('Insufficient wallet balance. Available balance: ₦' . number_format($user->bal, 2), 403);
        }
        $dataCardselection = DataCardSelection::first();
        if (!$dataCardselection) {
            DB::rollBack();
            return $this->failResponse('Data Card vendor configuration not found.', 403);
        }
        if ($network->network == '9MOBILE') {
        $vending = 'mobile';
        } else {
        $vending = strtolower($network->network);
        }
         $vendorFunc = $dataCardselection->$vending;
        if (!$vendorFunc) {
            DB::rollBack();
            return $this->failResponse('Vendor method not configured for ' . strtoupper($network->network) . '.', 403);
        }
         if (Str::startsWith($vendorFunc, 'adex')) {
            $vendorFunc = 'adexHandler';
        }
          // Deduct balance
        $oldBal = $user->bal;
        $newBal = $oldBal - $amount;
        $user->bal = $newBal;
        $user->save();

          // Save history
        $history = History::create([
            'username'    => $user->username,
            'user_id'     => $user->id,
            'amount'      => $amount,
            'message'     => 'Processing purchase of ' . strtoupper($network->network) . ' data card pin(s) worth ₦' . number_format($amount, 2) . ' (' . $request->quantity . ' quantity).',
            'oldbal'      => $oldBal,
            'newbal'      => $newBal,
            'adex_date'   => now(),
            'plan_status' => 0,
            'transid'     => $transId,
            'role'        => 'datacard',
            'my_trans_id' => $myTransId,
        ]);
          // Save exam transaction
        $dataCardTransaction = DataCardModel::create([
            'username'        => $user->username,
            'user_id'         => $user->id,
            'amount'          => $amount,
            'network'         => $network->network,
            'plan_status'     => 0,
            'quantity'        => $request->quantity,
            'plan_date'       => now(),
            'transid'         => $transId,
            'my_trans_id'     => $myTransId,
            'plan_type' => $plan->plan_type,
            'plan_name' => $plan->name . $plan->plan_size,
            'system'          => $system,
            'oldbal'          => $oldBal,
            'newbal'          => $newBal,
            'vending_system'  => $dataCardselection->$vending,
            'card_name' => $request->card_name,
            'check_balance' => $plan->check_balance,
            'load_pin' => $plan->load_pin,
        ]);
         DB::commit();
        $dataCardSender = new DataCardSend();
        if (!method_exists($dataCardSender, $vendorFunc)) {
            Log::error("Data Card method {$vendorFunc} does not exist.");
            $this->handleVendingResponse('fail', $user, $amount, $amount, $dataCardTransaction, $myTransId, $request->quantity, $oldBal, $newBal, $system, $history);
            return $this->failResponse('Internal vending system error. Please contact support.', 500);
        }
        $responseFromVendor = $dataCardSender->$vendorFunc($dataCardTransaction, $plan);
        return $this->handleVendingResponse(
            $responseFromVendor,
            $user,
            $amount,
            $amount,
            $dataCardTransaction,
            $myTransId,
            $request->quantity,
            $oldBal,
            $newBal,
            $system,
            $history
        );
    }
    private function handleVendingResponse(
    string $result,
    User $user,
    float $originalAmount,
    float $debitedAmount,
    DataCardModel $transaction,
    string $transId,
    string $quantity,
    float $oldBalBeforeDebit,
    float $newBalAfterDebit,
    string $system,
    History $history
): \Illuminate\Http\JsonResponse {
    $transaction->refresh();
    $status = 0;
    $message = '';
    $finalUserBalance = $newBalAfterDebit;
    DB::beginTransaction();
    $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();
    if ($result === 'success') {
        $pinsData = DumpDataCardPin::where([
                'network'  => $transaction->network,
                'username' => $user->username,
                'my_trans_id'  => $transId
            ])->get();

        $soldPins = [];
        $soldSerials = [];
        foreach ($pinsData as $pinRow) {
            $soldPins[] = $pinRow->pin;
            $soldSerials[] = $pinRow->serial;
        }

        $status = 1;
        $message = "{$transaction->plan_name} data card purchase successful.";

        // Update records
        $transaction->update(['plan_status' => $status]);
        $history->update(['plan_status' => $status, 'message' => $message]);

        DB::commit();

        return response()->json([
            'network'        => $transaction->network,
            'request-id'     => $transaction->transid,
             'transid'    => $transaction->transid,
            'amount'         => $originalAmount,
            'quantity'       => $quantity,
            'status'         => 'success',
            'message'        => $message,
            'card_name'      => $transaction->card_name,
            'oldbal'         => $oldBalBeforeDebit,
            'newbal'         => $finalUserBalance,
            'system'         => $system,
            'serial'         => implode(',', $soldSerials),
            'pin'            => implode(',', $soldPins),
            'load_pin'       => $transaction->load_pin,
            'check_balance'  => $transaction->check_balance
        ]);

    } elseif ($result === 'fail') {
        $status = 2;
        $message = "{$transaction->plan_name} data card purchase failed.";
        
        // Refund user
        $userForUpdate->bal += $debitedAmount;
        $userForUpdate->save();
        $finalUserBalance = $userForUpdate->bal;

        // Update transaction and history
        $history->update([
            'message'     => $message . ' Refund of ₦' . number_format($debitedAmount, 2) . ' processed.',
            'newbal'      => $finalUserBalance,
            'oldbal'      => $finalUserBalance,
            'plan_status' => $status,
        ]);

        $transaction->update([
            'plan_status' => $status,
            'newbal'      => $finalUserBalance,
            'oldbal'      => $finalUserBalance,
        ]);

        DB::commit();

        return response()->json([
            'network'    => $transaction->network,
            'request-id' => $transaction->transid,
             'transid'    => $transaction->transid,
            'amount'     => $originalAmount,
            'quantity'   => $quantity,
            'status'     => 'fail',
            'message'    => $message,
            'card_name'  => $transaction->card_name,
            'oldbal'     => $oldBalBeforeDebit,
            'newbal'     => $finalUserBalance,
            'system'     => $system,
        ]);
    } else {
        $status = 0;
        $message = "Purchase in progress for {$transaction->plan_name} ({$quantity} units).";
        DB::commit();
        return response()->json([
            'network'    => $transaction->network,
            'request-id' => $transaction->transid,
             'transid'    => $transaction->transid,
            'amount'     => $originalAmount,
            'quantity'   => $quantity,
            'status'     => 'process',
            'message'    => $message,
            'card_name'  => $transaction->card_name,
            'oldbal'     => $oldBalBeforeDebit,
            'newbal'     => $finalUserBalance,
            'system'     => $system,
        ]);
    }
}

    private function transactionExists($transId, $myTransId): bool
    {
        return DataCardModel::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

    private function sendWebhook(string $url, array $payload)
    {
        try {
            Http::timeout(10)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error("Failed to send webhook to {$url}: " . $e->getMessage());
        }
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'network' => 'required|exists:network,plan_id',
            'quantity' => 'required|numeric|integer|not_in:0|gt:0|min:1|max:100',
            'card_name' => 'required|string|max:200',
            'plan_type' => 'required|string',
        ];
        if ($system === 'WEBSITE' && $setting->allow_pin == 1) {
            $rules['pin'] = 'required|numeric|digits:4';
        }
        return Validator::make($request->all(), $rules);
    }

    private function failResponse(string $message, int $status = 403)
    {
        return response()->json(['status' => 'fail', 'message' => $message], $status);
    }

    private function validatePin($user, $pin): bool
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }
      private function getUserType($user){
        return match ($user->type) {
            'SMART' => 'smart',
            'AGENT' => 'agent',
            'AWUF' => 'awuf',
            'API' => 'api',
            default => 'special'
        };
    }
}
