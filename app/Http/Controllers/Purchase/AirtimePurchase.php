<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Airtime;
use App\Models\Block;
use App\Models\History;
use App\Models\Network;
use App\Models\Setting;
use App\Models\User;
use App\Models\AirtimeDiscount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Purchase\AirtimeSend;
use App\Models\AirtimeSelection;
use Illuminate\Support\Str;

class AirtimePurchase extends Controller
{
    public function BuyAirtime(Request $request)
    {
        $user = auth()->user(); 
        if (!$user) {
            return $this->failResponse('User not authenticated', 403);
        }

        $setting = Setting::first();
        $system = $request->system;
        $myTransId = bin2hex(random_bytes(8));
        $transId = $system === 'WEBSITE' ? $this->purchase_ref('AIRTIME_') : $request->input('request-id');

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

        $userType = $this->getUserType($user);
        $planType = strtolower($request->plan_type); // 'vtu' or 'sns'
        $amount = $request->amount; // Raw amount from request
        // Determine the network lock field based on plan type
        $networkLockField = ($planType === 'vtu') ? 'network_vtu' : 'network_share';
        // Check if the service is available
        if ($networkInfo->$networkLockField != 1) {
            return $this->failResponse("{$networkInfo->network} " . strtoupper($planType) . " is not available right now", 403);
        }
        // Phone number validation (if not bypassed)
        // if (!filter_var($request->bypass, FILTER_VALIDATE_BOOLEAN)) { // Convert 'true'/'false' string to boolean
        //     $phoneValidationResult = $this->validatePhoneNumber($request->phone, $networkInfo->network);
        //     if (!$phoneValidationResult['status']) {
        //         return $this->failResponse($phoneValidationResult['message'], 403);
        //     }
        // }
        // Check daily transaction limit
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
        // Calculate discounted amount
        // This method also handles min/max amount validation based on AirtimeDiscount settings
        // and returns null if amount is out of range.
        $discountedAmount = $this->calculateDiscountedAmount($networkInfo->network, $planType, $userType, $amount);
        // The calculateDiscountedAmount now returns null if amount is out of range,
        // but the validation should ideally catch this first.
        // This check acts as a safeguard if the validator somehow misses it.
        if ($discountedAmount === null) {
            // This case should ideally be caught by the validator, but good for a fallback.
            return $this->failResponse('Amount is outside the allowed range for this airtime purchase.', 403);
        }


        // Start database transaction for debit and record creation
        DB::beginTransaction();

        // Re-fetch user with lock for update to prevent race conditions
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        // Check if user has sufficient balance
        if (!is_numeric($user->bal) || $user->bal < $discountedAmount) {
            // DB::rollBack();
            return $this->failResponse('Insufficient Account Balance. Kindly Fund Your Wallet => ₦' . number_format($user->bal, 2), 403);
        }

        // Debit user
        $oldBal = $user->bal;
        $newBal = $user->bal - $discountedAmount;
        $user->bal = $newBal;
        $user->save(); // Save the updated balance
        $airtimeSender = new AirtimeSend();
        $airtimeSel = AirtimeSelection::first();
        $vendingMethodName = strtolower($networkInfo->network === '9MOBILE' ? 'mobile' : $networkInfo->network) . "_" . $planType;
        $vendorFunc =  $airtimeSel->$vendingMethodName; 
        // Create History and Airtime records (initially pending)
        $history = History::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'amount' => $amount, // Original amount for history
            'message' => 'Transaction on process ' . $networkInfo->network . ' ' . strtoupper($planType) . ' to ' . $request->phone,
            'oldbal' => $oldBal,
            'newbal' => $newBal,
            'adex_date' => now(), // Using Laravel's now()
            'plan_status' => 0, // Pending
            'transid' => $transId,
            'role' => 'airtime',
            'my_trans_id' => $myTransId,
        ]);

        $airtimeTransaction = Airtime::create([
            'username' => $user->username,
            'user_id' => $user->id,
            'network' => $networkInfo->network,
            'network_type' => strtoupper($planType),
            'amount' => $amount, // Original amount
            'oldbal' => $oldBal,
            'newbal' => $newBal,
            'discount' => $discountedAmount, // This is the actual debit amount
            'transid' => $transId,
            'plan_date' => now(),
            'plan_status' => 0, // Pending
            'plan_phone' => $request->phone,
            'system' => $system,
            'my_trans_id' => $myTransId,
            'vending_system' => $vendorFunc
        ]);

        DB::commit(); // Commit debit and record creation
        if (Str::startsWith($vendorFunc, 'adex')) {
        $vendorFunc = 'adexHandler'; 
        }
       if(Str::startsWith($vendorFunc, 'msorg')){
            $vendorFunc = 'msorgHandler';
       }
       if(Str::startsWith($vendorFunc, 'virus')) {
            $vendorFunc = 'virusHandler';
        }
        // Ensure the vending method exists
        if (!method_exists($airtimeSender, $vendorFunc)) {
            Log::error("AirtimeSend method {$vendingMethodName} does not exist.");
            // Refund and mark as failed if vending method is missing
            $this->handleVendingResponse('fail', $user, $amount, $discountedAmount, $networkInfo, $transId, $request->phone, $oldBal, $newBal, $system, $planType, $airtimeTransaction, $history);
            return $this->failResponse('Internal vending system error.', 500);
        }

        $responseFromVendor = $airtimeSender->$vendorFunc($airtimeTransaction);

        // Handle the vending response (success, process, fail)
        return $this->handleVendingResponse(
            $responseFromVendor,
            $user,
            $amount, // Original amount
            $discountedAmount, // Debited amount
            $networkInfo,
            $transId,
            $request->phone,
            $oldBal,
            $newBal,
            $system,
            $planType,
            $airtimeTransaction,
            $history
        );
    }

    /**
     * Validates the input request.
     */
    private function validateInput($request, $system, $setting)
    {
        $request->merge([
            'plan_type' => strtolower($request->plan_type),
        ]);
        $rules = [
            'network' => 'required',
            'phone' => 'required|numeric|digits:11',
            'plan_type' => 'required|in:vtu,sns',
            'amount' => ['required', 'numeric', 'integer', 'gt:0'], // Base rules for amount
            'request-id' => 'nullable|string|unique:airtime,transid'
        ];

        // Fetch AirtimeDiscount settings to apply dynamic min/max rules
        $discount = AirtimeDiscount::first();

        if ($discount) {
            // Add min and max rules dynamically if discount settings are available
            $rules['amount'][] = 'min:' . $discount->min_airtime;
            $rules['amount'][] = 'max:' . $discount->max_airtime;
        } else {
            // Fallback if discount settings are not found, ensure a minimum is still enforced.
            // You might want to log a warning here if this is an unexpected state.
            $rules['amount'][] = 'min:50'; // Default minimum if no discount settings
            Log::warning("AirtimeDiscount settings not found. Using default min:50 for amount validation.");
        }


        if ($system === 'WEBSITE' && $setting->allow_pin == 1) {
            $rules['pin'] = 'required|numeric|digits:4';
        }
        return Validator::make($request->all(), $rules);
    }
    /**
     * Validates the transaction PIN.
     */
    private function validatePin($user, $pin): bool
    {
        return User::where(['id' => $user->id, 'pin' => $pin])->exists();
    }

    /**
     * Checks if a transaction with the given IDs already exists.
     */
    private function transactionExists($transId, $myTransId): bool
    {
        return Airtime::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists()
            || History::where('transid', $transId)->orWhere('my_trans_id', $myTransId)->exists();
    }

    /**
     * Determines the user type for discount calculation.
     */
    private function getUserType($user): string
    {
        return match ($user->type) {
            'SMART' => 'smart',
            'AGENT' => 'agent',
            'AWUF' => 'awuf',
            'API' => 'api',
            default => 'special'
        };
    }

    /**
     * Validates the phone number against the selected network.
     */
    private function validatePhoneNumber(string $phone, string $networkName): array
    {
        if (substr($phone, 0, 1) !== '0' || strlen($phone) !== 11) {
            return ['status' => false, 'message' => "Invalid Phone Number format: {$phone}. Must start with 0 and be 11 digits."];
        }

        $prefix = substr($phone, 0, 4);

        $validPrefixes = match ($networkName) {
            'MTN' => ['0702', '0703', '0704', '0706', '0713', '0716', '0802', '0803', '0806', '0810', '0813', '0814', '0816', '0903', '0906', '0913', '0916'],
            'GLO' => ['0705', '0711', '0715', '0717', '0805', '0807', '0811', '0815', '0817', '0905', '0915', '0917'],
            'AIRTEL' => ['0701', '0708', '0802', '0808', '0812', '0818', '0901', '0902', '0904', '0907', '0912', '0917'],
            '9MOBILE' => ['0709', '0710', '0809', '0817', '0818', '0909', '0908'],
            'NTEL' => ['0804'], // Added NTEL based on your JS
            default => [],
        };

        if (!in_array($prefix, $validPrefixes)) {
            return ['status' => false, 'message' => "This is not a {$networkName} Number: {$phone}"];
        }

        return ['status' => true, 'message' => 'Phone number validated.'];
    }

    /**
     * Calculates the discounted amount for the airtime purchase.
     * Returns null if discount settings are invalid or amount is out of range.
     */
    private function calculateDiscountedAmount(string $networkName, string $planType, string $userType, float $amount): ?float
    {
        $discount = AirtimeDiscount::first(); // Assuming only one row for discount settings

        if (!$discount) {
            Log::warning("AirtimeDiscount settings not found. No discount applied.");
            return $amount; // No discount applied if settings are missing
        }

        $realNetwork = ($networkName === '9MOBILE') ? 'mobile' : strtolower($networkName);
        $type = ($planType === 'sns') ? 'share' : $planType; // 'share' for 'Share and Sell'
        $discountField = "{$realNetwork}_{$type}_{$userType}";

        if (!array_key_exists($discountField, $discount->getAttributes())) {
            Log::warning("Discount field '{$discountField}' not found in AirtimeDiscount settings. No discount applied.");
            return $amount; // No discount if field doesn't exist
        }

        $discountPercentage = $discount->$discountField;

        // Note: Min/Max amount validation is now handled by the validateInput method.
        // This check here acts as a redundant safeguard or for cases where calculateDiscountedAmount
        // might be called outside the main request validation flow.
        if ($amount < $discount->min_airtime || $amount > $discount->max_airtime) {
            Log::warning("Amount {$amount} is outside configured min/max range ({$discount->min_airtime}-{$discount->max_airtime}). This should ideally be caught by validation.");
            return null; // Return null if amount is out of range, indicating an issue
        }

        $discountedAmount = ($amount / 100) * $discountPercentage;

        return $discountedAmount;
    }

    /**
     * Handles the response from the airtime vending service, updates records, and performs refunds if necessary.
     */
    private function handleVendingResponse(
        string $result,
        User $user,
        float $originalAmount,
        float $debitedAmount,
        Network $networkInfo,
        string $transId,
        string $phone,
        float $oldBalBeforeDebit,
        float $newBalAfterDebit,
        string $system,
        string $planType,
        Airtime $airtimeTransaction,
        History $historyTransaction
    ): \Illuminate\Http\JsonResponse {
        $status = 0; // Default to pending
        $message = '';
        $finalUserBalance = $newBalAfterDebit; // Initialize with balance after initial debit

        DB::beginTransaction(); // Start a new transaction for status update/refund

        // Re-fetch user with lock for update to ensure atomicity for refund
        $userForUpdate = User::where('id', $user->id)->lockForUpdate()->first();

        if ($result === 'success') {
            $status = 1; // Success
            $message = $this->buildTransactionMessage($networkInfo, $planType, $phone, $originalAmount, 'success');
            $finalUserBalance = $newBalAfterDebit; // Balance remains debited
            $airtimeTransaction->update([
                'plan_status' => $status,
            ]);
            $historyTransaction->update([
                'plan_status' => $status,
                'message' => $message,
            ]);
        } elseif ($result === 'fail') {
            $status = 2; // Failed
            $message = $this->buildTransactionMessage($networkInfo, $planType, $phone, $originalAmount, 'fail');

            // Perform Refund
            $refundAmount = $debitedAmount;
            $userForUpdate->bal += $refundAmount; // Credit back the debited amount
            $userForUpdate->save();
            // $userForUpdate->refresh();

            $finalUserBalance = $userForUpdate->bal; // Update final balance to reflect refund
            $message .= " Amount of ₦" . number_format($refundAmount, 2) . " refunded.";

            // Update history message to reflect refund
            $historyTransaction->update([
                'message' => 'Refund for failed airtime transaction: ' . $networkInfo->network . ' ' . strtoupper($planType) . ' to ' . $phone . '. ' . $message,
                'newbal' => $finalUserBalance,
                'oldbal' => $finalUserBalance,
                'plan_status' => $status,
            ]);
            $airtimeTransaction->update([
                'plan_status' => $status,
                'newbal' => $finalUserBalance,
                'oldbal' => $finalUserBalance,
            ]);
        } else { // 'process' or any other unexpected response
            $status = 0; // Keep as pending
            $message = $this->buildTransactionMessage($networkInfo, $planType, $phone, $originalAmount, 'process');
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
                    'phone_number' => $phone,
                    'network' => $networkInfo->network,
                    'plan_type' => strtoupper($planType),
                    'message' => $message,
                    'old_balance' => $oldBalBeforeDebit,
                    'new_balance' => $finalUserBalance,
                    // Add any other relevant data from airtimeTransaction or historyTransaction
                ];
                $this->sendWebhook($user->webhook, $payload);
            } catch (\Throwable $e) {
                Log::error("Webhook failed for TransID {$transId}: " . $e->getMessage());
            }
        }
        return response()->json([
            'network' => $networkInfo->network,
            'request-id' => $transId,
            'transid' => $transId,
            'amount' => $originalAmount,
            'discounted_amount' => $debitedAmount,
            'status' => $result,
            'message' => $message,
            'phone_number' => $phone,
            'oldbal' => $oldBalBeforeDebit,
            'newbal' => $finalUserBalance,
            'system' => $system,
            'wallet_vending' => 'wallet',
            'plan_type' => strtoupper($planType),
        ]);
    }

    /**
     * Builds a user-friendly transaction message based on status.
     */
    private function buildTransactionMessage(Network $network, string $planType, string $phone, float $amount, string $status): string
    {
        $networkName = $network->network;
        $formattedAmount = '₦' . number_format($amount, 2);

        if ($status === 'fail') {
            return "Failed Airtime Purchase: {$formattedAmount} for {$networkName} " . strtoupper($planType) . " to {$phone}.";
        } elseif ($status === 'success') {
            return "Successfully purchased {$formattedAmount} airtime for {$networkName} " . strtoupper($planType) . " to {$phone}.";
        } else { // process
            return "Airtime transaction on process: {$formattedAmount} for {$networkName} " . strtoupper($planType) . " to {$phone}.";
        }
    }

    /**
     * Sends a webhook notification.
     */
    private function sendWebhook(string $url, array $payload)
    {
        try {
            Http::timeout(10)->post($url, $payload); // Add a timeout for webhooks
        } catch (\Throwable $e) {
            Log::error("Failed to send webhook to {$url}: " . $e->getMessage());
        }
    }

    /**
     * Returns a standardized failure JSON response.
     */
    private function failResponse(string $message, int $status = 403): \Illuminate\Http\JsonResponse
    {
        return response()->json(['status' => 'fail', 'message' => $message], $status);
    }
}
