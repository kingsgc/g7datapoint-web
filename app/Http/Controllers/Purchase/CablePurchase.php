<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\Cable;
use App\Models\History;
use App\Models\Setting;
use App\Models\User;
use App\Models\Block;
use App\Models\CableID;
use App\Models\CablePlan;
use App\Models\CableCharge;
use App\Models\CableLock;
use App\Models\CableSelection;

class CablePurchase extends Controller
{
    public function BuyCable(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->failResponse('User not authenticated', 403);
        }

        $setting = Setting::first();
        $system = $request->system;
        $myTransId = bin2hex(random_bytes(8));
        $transId = $system === 'WEBSITE' 
            ? $this->purchase_ref('CABLE_') 
            : $request->input('request-id');

        // Validate input
        $validator = $this->validateInput($request, $system, $setting);
        if ($validator->fails()) {
            return $this->failResponse($validator->errors()->first(), 403);
        }

        // Pin validation
        if ($setting->allow_pin && $system === 'WEBSITE' && !$this->validatePin($user, $request->pin)) {
            return $this->failResponse('Invalid Transaction Pin', 403);
        }

        // Duplicate transaction check
        if ($this->transactionExists($transId, $myTransId)) {
            return $this->failResponse('Request ID Exists Before', 403);
        }

        // Blocked IUC check
        if (Block::where('number', $request->iuc)->exists()) {
            return $this->failResponse('IUC Number Blocked', 403);
        }

        // Cable validation
        $cableID = CableID::find($request->cable);
        if (!$cableID) {
            return $this->failResponse('Cable ID invalid', 403);
        }

        $cable_name = strtolower($cableID->cable_name);

        // Plan validation
        $cable_plan = CablePlan::where([
            'plan_id' => $request->cable_plan,
            'cable_name' => $cable_name,
            'plan_status' => 1
        ])->first();

        if (!$cable_plan) {
            return $this->failResponse('Invalid Cable Plan ID', 403);
        }

        // Availability check
        $cable_lock = CableLock::first();
        if ($cable_lock && $cable_lock->$cable_name != 1) {
            return $this->failResponse(strtoupper($cable_name) . " is not available right now", 403);
        }

        // Balance validation
        if (!is_numeric($user->bal)) {
            return $this->failResponse('Invalid account balance', 403);
        }

        // Discount calculation
        $cable_setting = CableCharge::first(); 
        $discount = 0;
        if ($cable_setting && $cable_setting->direct == 1) {
            $discount = ($cable_plan->plan_price / 100) * $cable_setting->$cable_name;
        }

        $amount = $cable_plan->plan_price - $discount;

        // Daily limit check
        if ($setting->allow_limit == 1 && $user->kyc == 0) {
            $totalDailySpend = History::where('user_id', $user->id)
                ->whereNotIn('role', ['credit', 'debit', 'upgrade', 'earning'])
                ->where('plan_status', '!=', 2)
                ->where('created_at', '>=', Carbon::today())
                ->sum('amount');

            if (($totalDailySpend + $amount) > $user->user_limit) {
                return $this->failResponse('You have reached your daily transaction limit. Kindly message the Admin To Upgrade Your Account', 403);
            }
        }

        DB::beginTransaction();

        $user = User::where('id', $user->id)->lockForUpdate()->first();
        if ($user->bal < $amount) {
            DB::rollBack();
            return $this->failResponse('Insufficient Account Balance. Kindly Fund Your Wallet => ₦' . number_format($user->bal, 2), 403);
        }

        $cableselection = CableSelection::first();
        if (!$cableselection) {
            DB::rollBack();
            return $this->failResponse('Cable selection not found', 403);
        }

        $vendorFunc = $cableselection->$cable_name;
        if (!$vendorFunc) {
            DB::rollBack();
            return $this->failResponse('Vendor method not set for ' . strtoupper($cable_name), 403);
        }

        if (Str::startsWith($vendorFunc, 'adex')) {
            $vendorFunc = 'adexHandler';
        }

        $adm = new IUCsend();
        $sending_data = [
            'iuc' => $request->iuc,
            'cable' => $request->cable,
            'method' => $cableselection->$cable_name
        ];

        $customer_name = $adm->$vendorFunc($sending_data);
        if (empty($customer_name)) {
            DB::rollBack();
            return $this->failResponse('Invalid IUC Number', 403);
        }

        // Deduct balance
        $oldBal = $user->bal;
        $newBal = $oldBal - $amount;
        $user->bal = $newBal;
        $user->save();

        // Save history
        $history = History::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'amount' => $amount,
            'message' => 'Transaction on process ' . strtoupper($cable_name) . ' ' . $cable_plan->plan_name . ' ₦' . $cable_plan->plan_price . ' to ' . $request->iuc,
            'oldbal' => $oldBal,
            'newbal' => $newBal,
            'adex_date' => now(),
            'plan_status' => 0,
            'transid' => $transId,
            'role' => 'cable',
            'my_trans_id' => $myTransId,
        ]);

        // Save cable transaction
        $cableTransaction = Cable::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'amount' => $cable_plan->plan_price,
            'charges' => $discount,
            'cable_name' => strtoupper($cable_name),
            'cable_plan' => $cable_plan->plan_name,
            'plan_status' => 0,
            'iuc' => $request->iuc,
            'plan_date' => now(),
            'transid' => $transId,
            'my_trans_id' => $myTransId,
            'customer_name' => $customer_name,
            'system' => $system,
            'oldbal' => $oldBal,
            'newbal' => $newBal,
            'vending_system' => $cableselection->$cable_name
        ]);

        DB::commit();

        $cableSender = new CableSend();
        if (!method_exists($cableSender, $vendorFunc)) {
            Log::error("CableSend method {$vendorFunc} does not exist.");
            // Refund and mark as failed if vending method is missing
            $this->handleVendingResponse('fail',$user,$cable_plan->plan_price,$amount,$cableTransaction,$transId,$request->iuc,$oldBal,$newBal,$system,$cable_plan,$history);
            return $this->failResponse('Internal vending system error.', 500);
        }
        $responseFromVendor = $cableSender->$vendorFunc($cableTransaction, $cable_plan);
        return $this->handleVendingResponse(
            $responseFromVendor,
            $user,
            $cable_plan->plan_price,
            $amount,
            $cableTransaction,
            $transId,
            $request->iuc,
            $oldBal,
            $newBal,
            $system,
            $cable_plan,
            $history
        );
    }
    private function handleVendingResponse(
        string $result,
        User $user,
        float $originalAmount,
        float $debitedAmount,
        Cable $cableTransaction,
        string $transId,
        string $iuc,
        float $oldBalBeforeDebit,
        float $newBalAfterDebit,
        string $system,
        CablePlan $cablePlan,
        History $historyTransaction
    ): \Illuminate\Http\JsonResponse {
        $status = 0; 
        $message = '';
        $finalUserBalance = $newBalAfterDebit; 

        DB::beginTransaction(); // Start a new transaction for status update/refund
        // Re-fetch user with lock for update to ensure atomicity for refund
        $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();

        if ($result === 'success') {
            $status = 1; // Success
            $message = $this->buildTransactionMessage($cableTransaction, $cablePlan, $iuc, $originalAmount, 'success');
            $finalUserBalance = $newBalAfterDebit; // Balance remains debited
            $cableTransaction->update([
                'plan_status' => $status,
            ]);
            $historyTransaction->update([
                'plan_status' => $status,
                'message' => $message,
            ]);
        } elseif ($result === 'fail') {
            $status = 2; 
            $message = $this->buildTransactionMessage($cableTransaction, $cablePlan, $iuc, $originalAmount, 'fail');

            // Perform Refund
            $refundAmount = $debitedAmount;
            $userForUpdate->bal += $refundAmount; // Credit back the debited amount
            $userForUpdate->save();
            // $userForUpdate->refresh();

            $finalUserBalance = $userForUpdate->bal; // Update final balance to reflect refund
            $message .= " Amount of ₦" . number_format($refundAmount, 2) . " refunded.";

            // Update history message to reflect refund
            $historyTransaction->update([
                'message' => 'Refund for failed cable transaction: ' . $cableTransaction->cable_name . ' ' . strtoupper($cablePlan->plan_name) . ' to ' . $iuc . '. ' . $message,
                'newbal' => $finalUserBalance,
                'oldbal' => $finalUserBalance,
                'plan_status' => $status,
            ]);
            $cableTransaction->update([
                'plan_status' => $status,
                'newbal' => $finalUserBalance,
                'oldbal' => $finalUserBalance,
            ]);
        } else { // 'process' or any other unexpected response
            $status = 0; // Keep as pending
            $message = $this->buildTransactionMessage($cableTransaction, $cablePlan, $iuc, $originalAmount, 'process');
            $finalUserBalance = $newBalAfterDebit; // Balance remains debited
        }
        DB::commit(); // Commit status update and potential refund
        // Send webhook if available
        if ($user->webhook) {
            try {
                $payload = [
                    'status' => ($status === 1) ? 'success' : (($status === 2) ? 'fail' : 'process'),
                    'request-id' => $transId,
                    'amount' => $originalAmount,
                    'amount_charged' => $debitedAmount,
                    'iuc' => $iuc,
                    'cable_name' => $cableTransaction->cable_name,
                    'plan_name' => $cablePlan->plan_name,
                    'message' => $message,
                    'old_balance' => $oldBalBeforeDebit,
                    'new_balance' => $finalUserBalance,
                ];
                $this->sendWebhook($user->webhook, $payload);
            } catch (\Throwable $e) {
                Log::error("Webhook failed for TransID {$transId}: " . $e->getMessage());
            }
        }
        return response()->json([
            'cable_name' => $cableTransaction->cable_name,
            'request-id' => $transId,
            'transid'           => $transId,
            'amount' => $originalAmount,
            'amount_charged' => $debitedAmount,
            'status' => $result,
            'message' => $message,
            'iuc' => $iuc,
            'oldbal' => $oldBalBeforeDebit,
            'newbal' => $finalUserBalance,
            'system' => $system,
            'wallet_vending' => 'wallet',
            'plan_name' => $cablePlan->plan_name,
        ]);
    }

    private function buildTransactionMessage(Cable $cable, CablePlan $cablePlan, string $iuc, float $amount, string $status): string
    {
        $cable_name = $cable->cable_name;
        $formattedAmount = '₦' . number_format($amount, 2);

        if ($status === 'fail') {
            return "Failed Cable Purchase: {$formattedAmount} for {$cable_name} " . strtoupper($cablePlan->plan_name) . " to {$iuc}.";
        } elseif ($status === 'success') {
            return "Successfully purchased {$formattedAmount} cable for {$cable_name} " . strtoupper($cablePlan->plan_name) . " to {$iuc}.";
        } else { // process
            return "Cable transaction on process: {$formattedAmount} for {$cable_name} " . strtoupper($cablePlan->plan_name) . " to {$iuc}.";
        }
    }

    private function transactionExists($transId, $myTransId): bool
    {
        return Cable::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

   private function sendWebhook(string $url, array $payload)
    {
        try {
            Http::timeout(10)->post($url, $payload); // Add a timeout for webhooks
        } catch (\Throwable $e) {
            Log::error("Failed to send webhook to {$url}: " . $e->getMessage());
        }
    }

    private function validateInput($request, $system, $setting)
    {
        $rules = [
            'cable' => 'required',
            'iuc' => 'required',
            'bypass' => 'required',
            'cable_plan' => 'required',
            'request-id' => 'nullable|unique:cable,transid'
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
}
